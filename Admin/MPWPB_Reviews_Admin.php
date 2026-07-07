<?php
/*
* @Author 		MagePeople Team
* Copyright: 	mage-people.com
*/
if (!defined('ABSPATH')) {
    die;
} // Cannot access pages directly.

if (!class_exists('MPWPB_Reviews_Admin')) {
    class MPWPB_Reviews_Admin {
        public function __construct() {
            // Add reviews menu item
            add_action('admin_menu', array($this, 'add_reviews_menu'));
            
            // Handle review status changes
            add_action('admin_init', array($this, 'handle_review_actions'));

            // CSV export of the (filtered) reviews list.
            add_action('admin_init', array($this, 'maybe_export_csv'));
            
            // Add AJAX handlers for admin actions
            add_action('wp_ajax_mpwpb_change_review_status', array($this, 'change_review_status'));
            add_action('wp_ajax_mpwpb_delete_review', array($this, 'delete_review'));

            // Frontend review submission -- logged-in customers only (no
            // nopriv variant), matching the rest of the account-area AJAX
            // handlers (e.g. MPWPB_User_Dashboard).
            add_action('wp_ajax_mpwpb_submit_review', array($this, 'submit_review'));
        }
        
        /**
         * Add Reviews menu item to admin menu
         */
        public function add_reviews_menu() {
            add_submenu_page(
                'edit.php?post_type=mpwpb_item',
                esc_html__('Reviews', 'service-booking-manager'),
                esc_html__('Reviews', 'service-booking-manager'),
                'manage_options',
                'mpwpb-reviews',
                array($this, 'reviews_page')
            );
        }
        
        /**
         * Display the reviews management page
         */
        public function reviews_page() {
            // Create reviews table if it doesn't exist
            $this->create_reviews_table();

            // Get reviews with pagination
            $page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
            $per_page = 10;
            $offset = ($page - 1) * $per_page;

            $status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
            $service_filter = isset($_GET['service_id']) ? intval($_GET['service_id']) : 0;
            $rating_filter = isset($_GET['rating']) ? intval($_GET['rating']) : 0;

            $reviews = $this->get_reviews($status_filter, $service_filter, $rating_filter, $per_page, $offset);
            $total_reviews = $this->get_reviews_count($status_filter, $service_filter, $rating_filter);
            $total_pages = ceil($total_reviews / $per_page);

            $showing_start = $total_reviews > 0 ? $offset + 1 : 0;
            $showing_end = min($offset + $per_page, $total_reviews);

            // Get services for filter dropdown. This previously queried the
            // non-existent 'mpwpb_service' post type (a post-meta key
            // elsewhere in the plugin, not a CPT) so the dropdown always
            // rendered with no options -- the actual service CPT is
            // 'mpwpb_item' (same one get_edit_post_link()/get_the_title()
            // below already assume for $review->service_id).
            $services = get_posts(array(
                'post_type' => 'mpwpb_item',
                'posts_per_page' => -1,
                'post_status' => 'publish'
            ));

            $avatar_palette = array('#6366f1', '#ec4899', '#0ea5e9', '#f59e0b', '#10b981', '#8b5cf6');

            ?>
            <div class="wrap mpwpb-reviews-page">
                <div class="mpwpb-reviews-header">
                    <div>
                        <h1><?php esc_html_e('Service Reviews', 'service-booking-manager'); ?></h1>
                        <p class="mpwpb-reviews-subtitle"><?php esc_html_e('Manage and respond to customer feedback across all service categories.', 'service-booking-manager'); ?></p>
                    </div>
                    <a class="mpwpb-reviews-export-btn" href="<?php echo esc_url(wp_nonce_url(add_query_arg(array('action' => 'export_csv'), remove_query_arg('paged')), 'mpwpb_export_reviews_csv')); ?>">
                        <span class="dashicons dashicons-download"></span>
                        <?php esc_html_e('Export CSV', 'service-booking-manager'); ?>
                    </a>
                </div>

                <div class="mpwpb-reviews-filters-card">
                    <form method="get">
                        <input type="hidden" name="post_type" value="mpwpb_service">
                        <input type="hidden" name="page" value="mpwpb-reviews">

                        <div class="mpwpb-reviews-filter-group">
                            <label><?php esc_html_e('Service Type', 'service-booking-manager'); ?></label>
                            <select name="service_id">
                                <option value=""><?php esc_html_e('All Services', 'service-booking-manager'); ?></option>
                                <?php foreach ($services as $service) : ?>
                                    <option value="<?php echo esc_attr($service->ID); ?>" <?php selected($service_filter, $service->ID); ?>>
                                        <?php echo esc_html($service->post_title); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mpwpb-reviews-filter-group">
                            <label><?php esc_html_e('Approval Status', 'service-booking-manager'); ?></label>
                            <select name="status">
                                <option value=""><?php esc_html_e('All Statuses', 'service-booking-manager'); ?></option>
                                <option value="approved" <?php selected($status_filter, 'approved'); ?>><?php esc_html_e('Approved', 'service-booking-manager'); ?></option>
                                <option value="pending" <?php selected($status_filter, 'pending'); ?>><?php esc_html_e('Pending', 'service-booking-manager'); ?></option>
                            </select>
                        </div>

                        <div class="mpwpb-reviews-filter-group">
                            <label><?php esc_html_e('Rating', 'service-booking-manager'); ?></label>
                            <select name="rating">
                                <option value=""><?php esc_html_e('Any Rating', 'service-booking-manager'); ?></option>
                                <?php for ($i = 5; $i >= 1; $i--) : ?>
                                    <option value="<?php echo esc_attr($i); ?>" <?php selected($rating_filter, $i); ?>>
                                        <?php echo esc_html($i); ?> <?php esc_html_e('Stars', 'service-booking-manager'); ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <button type="submit" class="mpwpb-reviews-filter-btn">
                            <span class="dashicons dashicons-filter"></span>
                            <?php esc_html_e('Filter', 'service-booking-manager'); ?>
                        </button>
                    </form>
                </div>

                <?php if (empty($reviews)) : ?>
                    <div class="mpwpb-reviews-table-card">
                        <div class="mpwpb-no-reviews">
                            <p><?php esc_html_e('No reviews found.', 'service-booking-manager'); ?></p>
                        </div>
                    </div>
                <?php else : ?>
                    <div class="mpwpb-reviews-table-card">
                        <div class="mpwpb-reviews-table-scroll">
                        <table class="mpwpb-reviews-table">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('ID', 'service-booking-manager'); ?></th>
                                    <th><?php esc_html_e('Service', 'service-booking-manager'); ?></th>
                                    <th><?php esc_html_e('Customer', 'service-booking-manager'); ?></th>
                                    <th><?php esc_html_e('Rating', 'service-booking-manager'); ?></th>
                                    <th><?php esc_html_e('Review', 'service-booking-manager'); ?></th>
                                    <th><?php esc_html_e('Date', 'service-booking-manager'); ?></th>
                                    <th><?php esc_html_e('Status', 'service-booking-manager'); ?></th>
                                    <th><?php esc_html_e('Actions', 'service-booking-manager'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reviews as $review) :
                                    $service_title = get_the_title($review->service_id);
                                    if ($service_title === '') {
                                        $service_title = esc_html__('Unknown Service', 'service-booking-manager');
                                    }
                                    $avatar_color = $avatar_palette[$review->service_id % count($avatar_palette)];
                                ?>
                                    <tr>
                                        <td class="mpwpb-reviews-id">#REV-<?php echo esc_html($review->id); ?></td>
                                        <td>
                                            <a class="mpwpb-reviews-service-cell" href="<?php echo esc_url(get_edit_post_link($review->service_id)); ?>">
                                                <span class="mpwpb-reviews-avatar" style="background-color: <?php echo esc_attr($avatar_color); ?>">
                                                    <?php echo esc_html(mb_substr($service_title, 0, 1)); ?>
                                                </span>
                                                <strong><?php echo esc_html($service_title); ?></strong>
                                            </a>
                                        </td>
                                        <td>
                                            <?php
                                            $user_info = get_userdata($review->user_id);
                                            if ($user_info) {
                                                echo '<a href="' . esc_url(get_edit_user_link($review->user_id)) . '">' . esc_html($review->user_name) . '</a>';
                                            } else {
                                                echo esc_html($review->user_name);
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <div class="mpwpb-reviews-stars">
                                            <?php
                                            for ($i = 1; $i <= 5; $i++) {
                                                if ($i <= $review->rating) {
                                                    echo '<span class="dashicons dashicons-star-filled"></span>';
                                                } else {
                                                    echo '<span class="dashicons dashicons-star-empty"></span>';
                                                }
                                            }
                                            ?>
                                            </div>
                                        </td>
                                        <td class="mpwpb-reviews-content-cell">
                                            <strong><?php echo esc_html($review->title); ?></strong>
                                            <p><?php echo esc_html(wp_trim_words($review->content, 12)); ?></p>
                                        </td>
                                        <td class="mpwpb-reviews-date"><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($review->date_created))); ?></td>
                                        <td>
                                            <span class="mpwpb-review-status mpwpb-status-<?php echo esc_attr($review->status); ?>">
                                                <?php echo esc_html(ucfirst($review->status)); ?>
                                            </span>
                                        </td>
                                        <td class="mpwpb-review-actions">
                                            <button type="button" class="mpwpb-reviews-icon-btn mpwpb-view-review-btn" title="<?php esc_attr_e('View', 'service-booking-manager'); ?>" data-id="<?php echo esc_attr($review->id); ?>" data-title="<?php echo esc_attr($review->title); ?>" data-content="<?php echo esc_attr($review->content); ?>">
                                                <span class="dashicons dashicons-visibility"></span>
                                                <span class="screen-reader-text"><?php esc_html_e('View', 'service-booking-manager'); ?></span>
                                            </button>

                                            <?php if ($review->status === 'pending') : ?>
                                                <a class="mpwpb-reviews-icon-btn mpwpb-reviews-icon-btn--approve" title="<?php esc_attr_e('Approve', 'service-booking-manager'); ?>" href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=mpwpb-reviews&action=approve&review_id=' . $review->id), 'mpwpb_approve_review')); ?>">
                                                    <span class="dashicons dashicons-yes-alt"></span>
                                                    <span class="screen-reader-text"><?php esc_html_e('Approve', 'service-booking-manager'); ?></span>
                                                </a>
                                            <?php else : ?>
                                                <a class="mpwpb-reviews-icon-btn" title="<?php esc_attr_e('Mark Pending', 'service-booking-manager'); ?>" href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=mpwpb-reviews&action=pending&review_id=' . $review->id), 'mpwpb_pending_review')); ?>">
                                                    <span class="dashicons dashicons-backup"></span>
                                                    <span class="screen-reader-text"><?php esc_html_e('Mark Pending', 'service-booking-manager'); ?></span>
                                                </a>
                                            <?php endif; ?>

                                            <a class="mpwpb-reviews-icon-btn mpwpb-reviews-icon-btn--danger" title="<?php esc_attr_e('Delete', 'service-booking-manager'); ?>" href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=mpwpb-reviews&action=delete&review_id=' . $review->id), 'mpwpb_delete_review')); ?>" onclick="return confirm('<?php echo esc_js(__('Are you sure you want to delete this review?', 'service-booking-manager')); ?>')">
                                                <span class="dashicons dashicons-trash"></span>
                                                <span class="screen-reader-text"><?php esc_html_e('Delete', 'service-booking-manager'); ?></span>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        </div>

                        <div class="mpwpb-reviews-footer">
                            <span class="mpwpb-reviews-showing">
                                <?php echo esc_html(sprintf(
                                    /* translators: 1: first row number, 2: last row number, 3: total review count */
                                    __('Showing %1$d-%2$d of %3$d reviews', 'service-booking-manager'),
                                    $showing_start,
                                    $showing_end,
                                    $total_reviews
                                )); ?>
                            </span>
                            <?php if ($total_pages > 1) : ?>
                                <div class="mpwpb-reviews-pagination">
                                    <?php
                                    echo paginate_links(array(
                                        'base' => add_query_arg('paged', '%#%'),
                                        'format' => '',
                                        'prev_text' => '<span class="dashicons dashicons-arrow-left-alt2"></span>',
                                        'next_text' => '<span class="dashicons dashicons-arrow-right-alt2"></span>',
                                        'total' => $total_pages,
                                        'current' => $page
                                    ));
                                    ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Review Detail Modal -->
                <div id="mpwpb-review-modal" class="mpwpb-modal" style="display: none;">
                    <div class="mpwpb-modal-content">
                        <span class="mpwpb-close">&times;</span>
                        <h2 id="mpwpb-review-title"></h2>
                        <div id="mpwpb-review-content"></div>
                    </div>
                </div>

                <style>
                    .mpwpb-reviews-page{background:#f8fafc;margin-right:20px;padding:20px 0;}
                    .mpwpb-reviews-page *{box-sizing:border-box;}

                    .mpwpb-reviews-header{display:flex;align-items:flex-start;justify-content:space-between;gap:20px;margin-bottom:20px;padding-left:20px;flex-wrap:wrap;}
                    .mpwpb-reviews-header h1{font-size:24px;font-weight:700;color:#0f172a;margin:0 0 6px;padding:0;}
                    .mpwpb-reviews-subtitle{margin:0;color:#64748b;font-size:13.5px;}
                    .mpwpb-reviews-export-btn{display:inline-flex;align-items:center;gap:6px;background:#fff;color:#2563eb;border:1px solid #cbd5e1;border-radius:8px;padding:9px 16px;font-size:13px;font-weight:600;text-decoration:none;white-space:nowrap;}
                    .mpwpb-reviews-export-btn:hover{background:#eff6ff;border-color:#2563eb;color:#1d4ed8;}
                    .mpwpb-reviews-export-btn .dashicons{font-size:16px;width:16px;height:16px;line-height:16px;}

                    .mpwpb-reviews-filters-card{background:#fff;border:1px solid #eef1f5;border-radius:12px;box-shadow:0 1px 3px rgba(15,23,42,.05);padding:18px 20px;margin-bottom:20px;}
                    .mpwpb-reviews-filters-card form{display:flex;align-items:flex-end;gap:18px;flex-wrap:wrap;}
                    .mpwpb-reviews-filter-group{display:flex;flex-direction:column;gap:6px;min-width:170px;}
                    .mpwpb-reviews-filter-group label{font-size:12px;font-weight:600;color:#64748b;}
                    .mpwpb-reviews-filter-group select{border:1px solid #d8dee7;border-radius:8px;padding:8px 10px;font-size:13px;color:#0f172a;background:#fff;min-height:36px;}
                    .mpwpb-reviews-filter-btn{display:inline-flex;align-items:center;gap:6px;background:#2563eb;color:#fff;border:none;border-radius:8px;padding:10px 20px;font-size:13px;font-weight:600;cursor:pointer;min-height:36px;}
                    .mpwpb-reviews-filter-btn:hover{background:#1d4ed8;}
                    .mpwpb-reviews-filter-btn .dashicons{font-size:15px;width:15px;height:15px;line-height:15px;}

                    .mpwpb-reviews-table-card{background:#fff;border:1px solid #eef1f5;border-radius:12px;box-shadow:0 1px 3px rgba(15,23,42,.05);overflow:hidden;}
                    .mpwpb-reviews-table-scroll{overflow-x:auto;}
                    .mpwpb-reviews-table{width:100%;border-collapse:collapse;font-size:13px;}
                    .mpwpb-reviews-table th{text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.03em;color:#94a3b8;background:#f8fafc;padding:12px 16px;border-bottom:1px solid #eef1f5;white-space:nowrap;}
                    .mpwpb-reviews-table td{padding:14px 16px;border-bottom:1px solid #f1f5f9;vertical-align:middle;color:#334155;}
                    .mpwpb-reviews-table tbody tr:last-child td{border-bottom:none;}
                    .mpwpb-reviews-table tbody tr:hover{background:#f8fafc;}
                    .mpwpb-reviews-id{color:#94a3b8;font-weight:600;white-space:nowrap;}

                    .mpwpb-reviews-service-cell{display:flex;align-items:center;gap:10px;text-decoration:none;color:#0f172a;white-space:nowrap;}
                    .mpwpb-reviews-avatar{flex:0 0 auto;width:30px;height:30px;border-radius:8px;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:13px;text-transform:uppercase;}

                    .mpwpb-reviews-stars{white-space:nowrap;}
                    .mpwpb-reviews-stars .dashicons{font-size:15px;width:15px;height:15px;line-height:15px;}
                    .mpwpb-reviews-stars .dashicons-star-filled{color:#f59e0b;}
                    .mpwpb-reviews-stars .dashicons-star-empty{color:#e2e8f0;}

                    .mpwpb-reviews-content-cell{min-width:220px;max-width:340px;}
                    .mpwpb-reviews-content-cell strong{display:block;color:#0f172a;margin-bottom:2px;}
                    .mpwpb-reviews-content-cell p{margin:0;color:#64748b;font-size:12.5px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
                    .mpwpb-reviews-date{color:#64748b;white-space:nowrap;}

                    .mpwpb-review-status{display:inline-block;padding:4px 12px;border-radius:999px;font-weight:600;font-size:12px;white-space:nowrap;}
                    .mpwpb-status-approved{background-color:#dcfce7;color:#16a34a;}
                    .mpwpb-status-pending{background-color:#fef9c3;color:#ca8a04;}

                    .mpwpb-review-actions{white-space:nowrap;}
                    .mpwpb-reviews-icon-btn{display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:8px;background:#f1f5f9;border:none;color:#475569;cursor:pointer;margin-right:6px;text-decoration:none;vertical-align:middle;}
                    .mpwpb-reviews-icon-btn:last-child{margin-right:0;}
                    .mpwpb-reviews-icon-btn .dashicons{font-size:16px;width:16px;height:16px;line-height:16px;}
                    .mpwpb-reviews-icon-btn:hover{background:#e2e8f0;color:#0f172a;}
                    .mpwpb-reviews-icon-btn--approve{color:#16a34a;}
                    .mpwpb-reviews-icon-btn--approve:hover{background:#dcfce7;color:#16a34a;}
                    .mpwpb-reviews-icon-btn--danger:hover{background:#fee2e2;color:#dc2626;}

                    .mpwpb-no-reviews{padding:40px 20px;text-align:center;color:#64748b;}

                    .mpwpb-reviews-footer{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;padding:14px 20px;border-top:1px solid #eef1f5;}
                    .mpwpb-reviews-showing{font-size:12.5px;color:#64748b;}
                    .mpwpb-reviews-pagination{display:flex;align-items:center;gap:6px;}
                    .mpwpb-reviews-pagination .page-numbers{display:inline-flex;align-items:center;justify-content:center;min-width:30px;height:30px;padding:0 8px;border-radius:8px;background:#fff;border:1px solid #e2e8f0;color:#475569;font-size:12.5px;text-decoration:none;}
                    .mpwpb-reviews-pagination .page-numbers.current{background:#2563eb;border-color:#2563eb;color:#fff;}
                    .mpwpb-reviews-pagination .page-numbers:hover:not(.current){background:#f1f5f9;}
                    .mpwpb-reviews-pagination .page-numbers .dashicons{font-size:14px;width:14px;height:14px;line-height:14px;}
                    .mpwpb-reviews-pagination .page-numbers.dots{border-color:transparent;background:transparent;}

                    /* Modal Styles */
                    .mpwpb-modal {
                        position: fixed;
                        z-index: 9999;
                        left: 0;
                        top: 0;
                        width: 100%;
                        height: 100%;
                        overflow: auto;
                        background-color: rgba(0,0,0,0.4);
                    }
                    .mpwpb-modal-content {
                        background-color: #fefefe;
                        margin: 10% auto;
                        padding: 20px;
                        border: 1px solid #888;
                        width: 60%;
                        max-width: 800px;
                        border-radius: 5px;
                        position: relative;
                    }
                    .mpwpb-close {
                        color: #aaa;
                        float: right;
                        font-size: 28px;
                        font-weight: bold;
                        cursor: pointer;
                        position: absolute;
                        right: 15px;
                        top: 10px;
                    }
                    .mpwpb-close:hover {
                        color: black;
                    }
                </style>

                <script>
                jQuery(document).ready(function($) {
                    // View review modal
                    $('.mpwpb-view-review-btn').on('click', function() {
                        var title = $(this).data('title');
                        var content = $(this).data('content');

                        $('#mpwpb-review-title').text(title);
                        $('#mpwpb-review-content').html('<p>' + content + '</p>');
                        $('#mpwpb-review-modal').show();
                    });

                    // Close modal
                    $('.mpwpb-close').on('click', function() {
                        $('#mpwpb-review-modal').hide();
                    });

                    // Close modal when clicking outside
                    $(window).on('click', function(event) {
                        if ($(event.target).is('#mpwpb-review-modal')) {
                            $('#mpwpb-review-modal').hide();
                        }
                    });
                });
                </script>
            </div>
            <?php
        }

        /**
         * Streams a CSV of the reviews matching the current filters (all
         * matching rows, not just the current page) and exits. Hooked on
         * admin_init like handle_review_actions(), triggered by the
         * "Export CSV" button on the reviews list.
         */
        public function maybe_export_csv() {
            if (!isset($_GET['page']) || $_GET['page'] !== 'mpwpb-reviews' || !isset($_GET['action']) || $_GET['action'] !== 'export_csv') {
                return;
            }

            if (!current_user_can('manage_options')) {
                wp_die(esc_html__('You do not have permission to perform this action', 'service-booking-manager'));
            }
            check_admin_referer('mpwpb_export_reviews_csv');

            $status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
            $service_filter = isset($_GET['service_id']) ? intval($_GET['service_id']) : 0;
            $rating_filter = isset($_GET['rating']) ? intval($_GET['rating']) : 0;

            $reviews = $this->get_reviews($status_filter, $service_filter, $rating_filter, 0, 0);

            nocache_headers();
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=service-reviews-' . date('Y-m-d') . '.csv');

            $output = fopen('php://output', 'w');
            fputcsv($output, array('ID', 'Service', 'Customer', 'Rating', 'Title', 'Review', 'Date', 'Status'));
            foreach ($reviews as $review) {
                $service_title = get_the_title($review->service_id);
                fputcsv($output, array(
                    $review->id,
                    $service_title !== '' ? $service_title : __('Unknown Service', 'service-booking-manager'),
                    $review->user_name,
                    $review->rating,
                    $review->title,
                    $review->content,
                    $review->date_created,
                    ucfirst($review->status),
                ));
            }
            fclose($output);
            exit;
        }
        
        /**
         * Handle review actions (approve, pending, delete)
         */
        public function handle_review_actions() {
            if (!isset($_GET['page']) || $_GET['page'] !== 'mpwpb-reviews' || !isset($_GET['action']) || !isset($_GET['review_id'])) {
                return;
            }
            
            $action = sanitize_text_field($_GET['action']);
            $review_id = intval($_GET['review_id']);
            
            switch ($action) {
                case 'approve':
                    check_admin_referer('mpwpb_approve_review');
                    $this->update_review_status($review_id, 'approved');
                    break;
                    
                case 'pending':
                    check_admin_referer('mpwpb_pending_review');
                    $this->update_review_status($review_id, 'pending');
                    break;
                    
                case 'delete':
                    check_admin_referer('mpwpb_delete_review');
                    $this->delete_review_by_id($review_id);
                    break;
            }
            
            // Redirect back to reviews page
            wp_redirect(admin_url('admin.php?page=mpwpb-reviews'));
            exit;
        }
        
        /**
         * AJAX handler for changing review status
         */
        public function change_review_status() {
            check_ajax_referer('mpwpb_admin_nonce', 'nonce');
            
            if (!current_user_can('manage_options')) {
                wp_send_json_error(array('message' => esc_html__('You do not have permission to perform this action', 'service-booking-manager')));
            }
            
            $review_id = isset($_POST['review_id']) ? intval($_POST['review_id']) : 0;
            $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
            
            if (!$review_id || !in_array($status, array('approved', 'pending'))) {
                wp_send_json_error(array('message' => esc_html__('Invalid request', 'service-booking-manager')));
            }
            
            $result = $this->update_review_status($review_id, $status);
            
            if ($result) {
                wp_send_json_success(array('message' => esc_html__('Review status updated successfully', 'service-booking-manager')));
            } else {
                wp_send_json_error(array('message' => esc_html__('Failed to update review status', 'service-booking-manager')));
            }
        }
        
        /**
         * AJAX handler for deleting a review
         */
        public function delete_review() {
            check_ajax_referer('mpwpb_admin_nonce', 'nonce');
            
            if (!current_user_can('manage_options')) {
                wp_send_json_error(array('message' => esc_html__('You do not have permission to perform this action', 'service-booking-manager')));
            }
            
            $review_id = isset($_POST['review_id']) ? intval($_POST['review_id']) : 0;
            
            if (!$review_id) {
                wp_send_json_error(array('message' => esc_html__('Invalid request', 'service-booking-manager')));
            }
            
            $result = $this->delete_review_by_id($review_id);

            if ($result) {
                wp_send_json_success(array('message' => esc_html__('Review deleted successfully', 'service-booking-manager')));
            } else {
                wp_send_json_error(array('message' => esc_html__('Failed to delete review', 'service-booking-manager')));
            }
        }

        /**
         * True if $user_id has at least one booking on record for
         * $service_id -- the gate for who's allowed to leave a review.
         * Public + static so the frontend template can check this before
         * even showing the review form, not just at submission time.
         */
        public static function user_can_review($service_id, $user_id) {
            if (!$service_id || !$user_id) {
                return false;
            }
            $bookings = get_posts(array(
                'post_type'      => 'mpwpb_booking',
                'posts_per_page' => 1,
                'fields'         => 'ids',
                'meta_query'     => array(
                    'relation' => 'AND',
                    array('key' => 'mpwpb_id', 'value' => $service_id, 'compare' => '='),
                    array('key' => 'mpwpb_user_id', 'value' => $user_id, 'compare' => '='),
                ),
            ));
            return !empty($bookings);
        }

        /**
         * AJAX handler for a logged-in customer submitting a review. A user
         * may submit as many reviews for the same service as they like --
         * each is its own row, always starting 'pending'.
         * update_service_rating() only ever averages 'approved' rows, so
         * this can't be used to inflate a service's rating without going
         * through admin approval first.
         */
        public function submit_review() {
            check_ajax_referer('mpwpb_nonce', 'nonce');

            if (!is_user_logged_in()) {
                wp_send_json_error(array('message' => esc_html__('Please log in to leave a review.', 'service-booking-manager')));
            }

            $service_id = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;
            $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
            $title = isset($_POST['title']) ? sanitize_text_field(wp_unslash($_POST['title'])) : '';
            $content = isset($_POST['content']) ? sanitize_textarea_field(wp_unslash($_POST['content'])) : '';

            if (!$service_id || get_post_type($service_id) !== 'mpwpb_item') {
                wp_send_json_error(array('message' => esc_html__('Invalid service.', 'service-booking-manager')));
            }
            if ($rating < 1 || $rating > 5) {
                wp_send_json_error(array('message' => esc_html__('Please select a rating between 1 and 5 stars.', 'service-booking-manager')));
            }
            if ($content === '') {
                wp_send_json_error(array('message' => esc_html__('Please write a short review.', 'service-booking-manager')));
            }

            $user = wp_get_current_user();

            if (!self::user_can_review($service_id, $user->ID)) {
                wp_send_json_error(array('message' => esc_html__('You can only review services you have booked.', 'service-booking-manager')));
            }

            $this->create_reviews_table();

            global $wpdb;
            $table_name = $wpdb->prefix . 'mpwpb_reviews';

            $wpdb->insert(
                $table_name,
                array(
                    'service_id'   => $service_id,
                    'user_id'      => $user->ID,
                    'user_name'    => $user->display_name,
                    'rating'       => $rating,
                    'title'        => $title,
                    'content'      => $content,
                    'status'       => 'pending',
                    'date_created' => current_time('mysql'),
                ),
                array('%d', '%d', '%s', '%d', '%s', '%s', '%s', '%s')
            );

            wp_send_json_success(array(
                'message' => esc_html__('Thank you! Your review has been submitted and is awaiting approval.', 'service-booking-manager'),
            ));
        }

        /**
         * Update review status
         */
        private function update_review_status($review_id, $status) {
            global $wpdb;
            
            $table_name = $wpdb->prefix . 'mpwpb_reviews';
            
            $result = $wpdb->update(
                $table_name,
                array('status' => $status),
                array('id' => $review_id),
                array('%s'),
                array('%d')
            );
            
            if ($result) {
                // Get the service ID for this review
                $review = $wpdb->get_row($wpdb->prepare("SELECT service_id FROM $table_name WHERE id = %d", $review_id));
                
                if ($review) {
                    // Update service rating metadata
                    $this->update_service_rating($review->service_id);
                }
            }
            
            return $result !== false;
        }
        
        /**
         * Delete a review by ID
         */
        private function delete_review_by_id($review_id) {
            global $wpdb;
            
            $table_name = $wpdb->prefix . 'mpwpb_reviews';
            
            // Get the service ID before deleting
            $review = $wpdb->get_row($wpdb->prepare("SELECT service_id FROM $table_name WHERE id = %d", $review_id));
            
            $result = $wpdb->delete(
                $table_name,
                array('id' => $review_id),
                array('%d')
            );
            
            if ($result && $review) {
                // Update service rating metadata
                $this->update_service_rating($review->service_id);
            }
            
            return $result !== false;
        }
        
        /**
         * Get reviews with filters and pagination
         */
        private function get_reviews($status = '', $service_id = 0, $rating = 0, $limit = 20, $offset = 0) {
            global $wpdb;

            $table_name = $wpdb->prefix . 'mpwpb_reviews';

            // Check if table exists first
            if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
                return array();
            }

            $sql = "SELECT * FROM $table_name WHERE 1=1";
            $prepare_args = array();

            if ($status) {
                $sql .= " AND status = %s";
                $prepare_args[] = $status;
            }

            if ($service_id) {
                $sql .= " AND service_id = %d";
                $prepare_args[] = $service_id;
            }

            if ($rating) {
                $sql .= " AND rating = %d";
                $prepare_args[] = $rating;
            }

            $sql .= " ORDER BY date_created DESC";

            // $limit = 0 means "no limit" (used by the CSV export, which
            // needs every matching row, not just the current page).
            if ($limit > 0) {
                $sql .= " LIMIT %d OFFSET %d";
                $prepare_args[] = $limit;
                $prepare_args[] = $offset;
            }

            if (empty($prepare_args)) {
                return $wpdb->get_results($sql);
            }
            return $wpdb->get_results($wpdb->prepare($sql, $prepare_args));
        }

        /**
         * Get total count of reviews with filters
         */
        private function get_reviews_count($status = '', $service_id = 0, $rating = 0) {
            global $wpdb;

            $table_name = $wpdb->prefix . 'mpwpb_reviews';

            // Check if table exists first
            if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
                return 0;
            }

            $sql = "SELECT COUNT(*) FROM $table_name WHERE 1=1";
            $prepare_args = array();

            if ($status) {
                $sql .= " AND status = %s";
                $prepare_args[] = $status;
            }

            if ($service_id) {
                $sql .= " AND service_id = %d";
                $prepare_args[] = $service_id;
            }

            if ($rating) {
                $sql .= " AND rating = %d";
                $prepare_args[] = $rating;
            }

            if (empty($prepare_args)) {
                return (int) $wpdb->get_var($sql);
            }
            return (int) $wpdb->get_var($wpdb->prepare($sql, $prepare_args));
        }
        
        /**
         * Create the reviews table if it doesn't exist
         */
        private function create_reviews_table() {
            global $wpdb;
            
            $table_name = $wpdb->prefix . 'mpwpb_reviews';
            
            if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
                $charset_collate = $wpdb->get_charset_collate();
                
                $sql = "CREATE TABLE $table_name (
                    id bigint(20) NOT NULL AUTO_INCREMENT,
                    service_id bigint(20) NOT NULL,
                    user_id bigint(20) NOT NULL,
                    user_name varchar(100) NOT NULL,
                    rating int(1) NOT NULL,
                    title varchar(255) NOT NULL,
                    content text NOT NULL,
                    status varchar(20) NOT NULL DEFAULT 'pending',
                    date_created datetime NOT NULL,
                    PRIMARY KEY  (id)
                ) $charset_collate;";
                
                require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
                dbDelta($sql);
            }
        }
        
        /**
         * Update service rating metadata
         */
        private function update_service_rating($service_id) {
            global $wpdb;
            
            $table_name = $wpdb->prefix . 'mpwpb_reviews';
            
            $reviews = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table_name
                WHERE service_id = %d
                AND status = 'approved'",
                $service_id
            ));
            
            $total_rating = 0;
            $review_count = count($reviews);
            
            foreach ($reviews as $review) {
                $total_rating += $review->rating;
            }
            
            $average_rating = $review_count > 0 ? $total_rating / $review_count : 0;
            
            // Update service meta
            update_post_meta($service_id, 'mpwpb_service_review_ratings', number_format($average_rating, 1));
            update_post_meta($service_id, 'mpwpb_service_rating_scale', esc_html__('Out of 5', 'service-booking-manager'));
            update_post_meta($service_id, 'mpwpb_service_rating_text', sprintf(
                esc_html__('(%d ratings)', 'service-booking-manager'),
                $review_count
            ));
        }
    }
    
    new MPWPB_Reviews_Admin();
}