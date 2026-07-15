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

            // Save the Review Settings tab.
            add_action('admin_init', array($this, 'save_review_settings'));

            // Add AJAX handlers for admin actions
            add_action('wp_ajax_mpwpb_change_review_status', array($this, 'change_review_status'));
            add_action('wp_ajax_mpwpb_delete_review', array($this, 'delete_review'));

            // Frontend review submission -- logged-in customers only (no
            // nopriv variant), matching the rest of the account-area AJAX
            // handlers (e.g. MPWPB_User_Dashboard).
            add_action('wp_ajax_mpwpb_submit_review', array($this, 'submit_review'));

            // Manual "Review Request" button/modal -- Order List, Service
            // Queue (wp-admin) and My Appointment (front-end staff
            // dashboard) all call these same two actions.
            add_action('wp_ajax_mpwpb_get_review_request_data', array($this, 'ajax_get_review_request_data'));
            add_action('wp_ajax_mpwpb_send_review_request', array($this, 'ajax_send_review_request'));

            // Daily background check for bookings due an automatic review
            // request -- same scheduling idiom as the only other cron job
            // in this codebase (MPWPB_Audit_Logs::cleanup_old_logs()).
            add_action('mpwpb_review_request_daily_cron', array($this, 'process_auto_review_requests'));
            if (!wp_next_scheduled('mpwpb_review_request_daily_cron')) {
                wp_schedule_event(time(), 'daily', 'mpwpb_review_request_daily_cron');
            }
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
         * Display the reviews management page -- a tab shell (Review List /
         * Review Requested / Review Settings) around the three render_*_tab()
         * methods below. Tab switching itself is handled entirely by the
         * plugin's existing generic tab component (mp_global/assets/mp_style/
         * mpwpb_plugin_global.js listens for .mpwpb_style [data-tabs-target]),
         * the same markup contract already used by the per-service Settings
         * metabox (Admin/MPWPB_Settings.php) -- no new JS needed here.
         */
        public function reviews_page() {
            // Create reviews table if it doesn't exist
            $this->create_reviews_table();

            $this->render_styles();
            ?>
            <div class="wrap mpwpb-reviews-page">
                <div class="mpwpb-reviews-header">
                    <div>
                        <h1><?php esc_html_e('Service Reviews', 'service-booking-manager'); ?></h1>
                        <p class="mpwpb-reviews-subtitle"><?php esc_html_e('Manage customer feedback, request reviews from past customers, and configure the request email.', 'service-booking-manager'); ?></p>
                    </div>
                    <a class="mpwpb-reviews-export-btn" href="<?php echo esc_url(wp_nonce_url(add_query_arg(array('action' => 'export_csv'), remove_query_arg('paged')), 'mpwpb_export_reviews_csv')); ?>">
                        <span class="dashicons dashicons-download"></span>
                        <?php esc_html_e('Export CSV', 'service-booking-manager'); ?>
                    </a>
                </div>

                <div class="mpwpb_style">
                    <div class="mpwpb_tabs mpwpb-reviews-tabs">
                        <div class="tabLists">
                            <ul>
                                <li data-tabs-target="#mpwpb_review_list"><span class="dashicons dashicons-list-view"></span><?php esc_html_e('Review List', 'service-booking-manager'); ?></li>
                                <li data-tabs-target="#mpwpb_review_requested"><span class="dashicons dashicons-email-alt"></span><?php esc_html_e('Review Requested', 'service-booking-manager'); ?></li>
                                <li data-tabs-target="#mpwpb_review_settings"><span class="dashicons dashicons-admin-generic"></span><?php esc_html_e('Review Settings', 'service-booking-manager'); ?></li>
                            </ul>
                        </div>
                        <div class="tabsContent">
                            <div class="tabsItem" data-tabs="#mpwpb_review_list">
                                <?php $this->render_review_list_tab(); ?>
                            </div>
                            <div class="tabsItem" data-tabs="#mpwpb_review_requested">
                                <?php $this->render_review_requested_tab(); ?>
                            </div>
                            <div class="tabsItem" data-tabs="#mpwpb_review_settings">
                                <?php $this->render_review_settings_tab(); ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Review Detail Modal (used by the Review List tab) -->
                <div id="mpwpb-review-modal" class="mpwpb-modal" style="display: none;">
                    <div class="mpwpb-modal-content">
                        <span class="mpwpb-close">&times;</span>
                        <h2 id="mpwpb-review-title"></h2>
                        <div id="mpwpb-review-content"></div>
                    </div>
                </div>
            </div>
            <?php
            $this->render_list_scripts();
        }

        /**
         * Tab 1: the pre-existing reviews list -- filters, table, pagination,
         * "View" modal trigger. Behaviourally identical to the page's old
         * (pre-tabs) content, just no longer wrapping its own <div class="wrap">
         * or header (both now live once in reviews_page(), shared across tabs).
         */
        private function render_review_list_tab() {
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

            // Get services for filter dropdown. The actual service CPT is
            // 'mpwpb_item' (same one get_edit_post_link()/get_the_title()
            // below already assume for $review->service_id).
            $services = get_posts(array(
                'post_type' => 'mpwpb_item',
                'posts_per_page' => -1,
                'post_status' => 'publish'
            ));

            $avatar_palette = array('#6366f1', '#ec4899', '#0ea5e9', '#f59e0b', '#10b981', '#8b5cf6');
            ?>
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
            <?php endif;
        }

        /**
         * Tab 2: bookings whose service date has already passed, that don't
         * have a review yet, so staff can nudge the customer -- manually via
         * the per-row action here, or automatically via
         * process_auto_review_requests() if enabled in the Settings tab.
         */
        private function render_review_requested_tab() {
            $page = isset($_GET['requested_paged']) ? max(1, intval($_GET['requested_paged'])) : 1;
            $per_page = 10;
            $offset = ($page - 1) * $per_page;

            $service_filter = isset($_GET['requested_service_id']) ? intval($_GET['requested_service_id']) : 0;
            $request_status_filter = isset($_GET['requested_status']) ? sanitize_text_field($_GET['requested_status']) : '';

            $filtered = $this->filter_eligible_bookings($service_filter, $request_status_filter);
            $total = count($filtered);
            $total_pages = ceil($total / $per_page);
            $rows = array_slice($filtered, $offset, $per_page);

            $showing_start = $total > 0 ? $offset + 1 : 0;
            $showing_end = min($offset + $per_page, $total);

            $services = get_posts(array(
                'post_type' => 'mpwpb_item',
                'posts_per_page' => -1,
                'post_status' => 'publish'
            ));
            ?>
            <div class="mpwpb-reviews-filters-card">
                <form method="get">
                    <input type="hidden" name="post_type" value="mpwpb_service">
                    <input type="hidden" name="page" value="mpwpb-reviews">

                    <div class="mpwpb-reviews-filter-group">
                        <label><?php esc_html_e('Service Type', 'service-booking-manager'); ?></label>
                        <select name="requested_service_id">
                            <option value=""><?php esc_html_e('All Services', 'service-booking-manager'); ?></option>
                            <?php foreach ($services as $service) : ?>
                                <option value="<?php echo esc_attr($service->ID); ?>" <?php selected($service_filter, $service->ID); ?>>
                                    <?php echo esc_html($service->post_title); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mpwpb-reviews-filter-group">
                        <label><?php esc_html_e('Request Status', 'service-booking-manager'); ?></label>
                        <select name="requested_status">
                            <option value=""><?php esc_html_e('All', 'service-booking-manager'); ?></option>
                            <option value="not_sent" <?php selected($request_status_filter, 'not_sent'); ?>><?php esc_html_e('Not Sent', 'service-booking-manager'); ?></option>
                            <option value="sent" <?php selected($request_status_filter, 'sent'); ?>><?php esc_html_e('Sent', 'service-booking-manager'); ?></option>
                        </select>
                    </div>

                    <button type="submit" class="mpwpb-reviews-filter-btn">
                        <span class="dashicons dashicons-filter"></span>
                        <?php esc_html_e('Filter', 'service-booking-manager'); ?>
                    </button>
                </form>
            </div>

            <?php if (empty($rows)) : ?>
                <div class="mpwpb-reviews-table-card">
                    <div class="mpwpb-no-reviews">
                        <p><?php esc_html_e('No bookings are currently eligible for a review request.', 'service-booking-manager'); ?></p>
                    </div>
                </div>
            <?php else : ?>
                <div class="mpwpb-reviews-table-card">
                    <div class="mpwpb-reviews-table-scroll">
                    <table class="mpwpb-reviews-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Customer', 'service-booking-manager'); ?></th>
                                <th><?php esc_html_e('Service', 'service-booking-manager'); ?></th>
                                <th><?php esc_html_e('Booking Date', 'service-booking-manager'); ?></th>
                                <th><?php esc_html_e('Request Status', 'service-booking-manager'); ?></th>
                                <th><?php esc_html_e('Actions', 'service-booking-manager'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $row) :
                                $service_title = get_the_title($row['service_id']);
                                if ($service_title === '') {
                                    $service_title = esc_html__('Unknown Service', 'service-booking-manager');
                                }
                                $was_sent = $row['request_status'] === 'sent';
                            ?>
                                <tr>
                                    <td>
                                        <strong><?php echo esc_html($row['customer_name'] !== '' ? $row['customer_name'] : __('Guest', 'service-booking-manager')); ?></strong><br>
                                        <span class="mpwpb-reviews-date"><?php echo esc_html($row['customer_email']); ?></span>
                                    </td>
                                    <td>
                                        <a href="<?php echo esc_url(get_edit_post_link($row['service_id'])); ?>"><?php echo esc_html($service_title); ?></a>
                                    </td>
                                    <td class="mpwpb-reviews-date"><?php echo esc_html(date_i18n(get_option('date_format'), $row['booking_timestamp'])); ?></td>
                                    <td>
                                        <?php if ($was_sent) : ?>
                                            <span class="mpwpb-review-status mpwpb-status-approved">
                                                <?php echo esc_html(sprintf(
                                                    /* translators: %s: date the review request was sent */
                                                    __('Sent %s', 'service-booking-manager'),
                                                    date_i18n(get_option('date_format'), strtotime($row['request_date']))
                                                )); ?>
                                            </span>
                                        <?php else : ?>
                                            <span class="mpwpb-review-status mpwpb-status-notsent"><?php esc_html_e('Not Sent', 'service-booking-manager'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a class="mpwpb-reviews-send-request-btn" href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=mpwpb-reviews&action=send_review_request&booking_id=' . $row['booking_id']), 'mpwpb_send_review_request')); ?>">
                                            <?php echo esc_html($was_sent ? __('Resend', 'service-booking-manager') : __('Send Request', 'service-booking-manager')); ?>
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
                                /* translators: 1: first row number, 2: last row number, 3: total booking count */
                                __('Showing %1$d-%2$d of %3$d bookings', 'service-booking-manager'),
                                $showing_start,
                                $showing_end,
                                $total
                            )); ?>
                        </span>
                        <?php if ($total_pages > 1) : ?>
                            <div class="mpwpb-reviews-pagination">
                                <?php
                                echo paginate_links(array(
                                    'base' => add_query_arg('requested_paged', '%#%'),
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
            <?php endif;
        }

        /**
         * Tab 3: auto-send toggle/delay + the request email's subject/body,
         * stored as one option array (mpwpb_review_settings) -- same "one
         * array option per settings screen" convention as the Pro plugin's
         * mpwpb_email_settings, read back via the existing
         * MPWPB_Global_Function::get_settings() helper.
         */
        private function render_review_settings_tab() {
            $settings_saved = isset($_GET['review_settings_saved']);

            $auto_send_enabled = MPWPB_Global_Function::get_settings('mpwpb_review_settings', 'auto_send_enabled', 'no');
            $send_after_days = MPWPB_Global_Function::get_settings('mpwpb_review_settings', 'send_after_days', 3);
            $email_subject = MPWPB_Global_Function::get_settings('mpwpb_review_settings', 'email_subject', $this->default_review_email_subject());
            $email_body = MPWPB_Global_Function::get_settings('mpwpb_review_settings', 'email_body', $this->default_review_email_body());
            ?>
            <div class="mpwpb-reviews-table-card mpwpb-review-settings-card">
                <?php if ($settings_saved) : ?>
                    <div class="mpwpb-reviews-settings-notice"><?php esc_html_e('Review settings saved.', 'service-booking-manager'); ?></div>
                <?php endif; ?>

                <form method="post">
                    <?php wp_nonce_field('mpwpb_save_review_settings', 'mpwpb_review_settings_nonce'); ?>
                    <input type="hidden" name="mpwpb_save_review_settings" value="1">

                    <div class="mpwpb-review-settings-row">
                        <label><?php esc_html_e('Automatically send review requests', 'service-booking-manager'); ?></label>
                        <select name="auto_send_enabled">
                            <option value="no" <?php selected($auto_send_enabled, 'no'); ?>><?php esc_html_e('No', 'service-booking-manager'); ?></option>
                            <option value="yes" <?php selected($auto_send_enabled, 'yes'); ?>><?php esc_html_e('Yes', 'service-booking-manager'); ?></option>
                        </select>
                        <p class="mpwpb-review-settings-desc"><?php esc_html_e('When enabled, a daily background task emails eligible customers automatically. Manually sending from the Review Requested tab always works regardless of this setting.', 'service-booking-manager'); ?></p>
                    </div>

                    <div class="mpwpb-review-settings-row">
                        <label><?php esc_html_e('Send after (days)', 'service-booking-manager'); ?></label>
                        <input type="number" min="0" step="1" name="send_after_days" value="<?php echo esc_attr($send_after_days); ?>" class="mpwpb-review-settings-number">
                        <p class="mpwpb-review-settings-desc"><?php esc_html_e('Number of days after the service date before the automatic request is sent.', 'service-booking-manager'); ?></p>
                    </div>

                    <div class="mpwpb-review-settings-row">
                        <label><?php esc_html_e('Email Subject', 'service-booking-manager'); ?></label>
                        <input type="text" name="email_subject" value="<?php echo esc_attr($email_subject); ?>" class="mpwpb-review-settings-text">
                    </div>

                    <div class="mpwpb-review-settings-row">
                        <label><?php esc_html_e('Email Body', 'service-booking-manager'); ?></label>
                        <?php
                        wp_editor($email_body, 'mpwpb_review_email_body', array(
                            'textarea_name' => 'email_body',
                            'textarea_rows' => 10,
                            'media_buttons' => false,
                        ));
                        ?>
                        <p class="mpwpb-review-settings-desc">
                            <?php esc_html_e('Available placeholders:', 'service-booking-manager'); ?>
                            <code>{customer_name}</code> <code>{service_name}</code> <code>{booking_date}</code> <code>{site_name}</code> <code>{review_link}</code>
                        </p>
                    </div>

                    <button type="submit" class="mpwpb-reviews-filter-btn"><?php esc_html_e('Save Settings', 'service-booking-manager'); ?></button>
                </form>
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
         * Handle review actions (approve, pending, delete, send_review_request)
         */
        public function handle_review_actions() {
            if (!isset($_GET['page']) || $_GET['page'] !== 'mpwpb-reviews' || !isset($_GET['action'])) {
                return;
            }
			if (!current_user_can('manage_options')) {
				wp_die(esc_html__('You do not have permission to perform this action', 'service-booking-manager'), '', array('response' => 403));
			}

			$action = sanitize_key(wp_unslash($_GET['action']));

            if ($action === 'send_review_request') {
                if (!isset($_GET['booking_id'])) {
                    return;
                }
                check_admin_referer('mpwpb_send_review_request');
                $this->send_review_request_email(intval($_GET['booking_id']));
                wp_redirect(admin_url('admin.php?page=mpwpb-reviews'));
                exit;
            }

            if (!isset($_GET['review_id'])) {
                return;
            }

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

                default:
                    return;
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
         * Emails a booking's customer asking them to review the service they
         * booked. Reuses the same subject/body-with-{placeholders} + nl2br()
         * + wp_mail() convention already used by
         * Admin/settings/Waiting_List.php::send_slot_available_notification()
         * -- the only precedent for a configurable-template email in this
         * (non-pro) plugin. Public so both the manual admin action and the
         * daily cron can call it the same way.
         */
        public function send_review_request_email($booking_id) {
            $booking = get_post($booking_id);
            if (!$booking || $booking->post_type !== 'mpwpb_booking') {
                return false;
            }

            $customer_email = get_post_meta($booking_id, 'mpwpb_billing_email', true);
            if (!$customer_email || !is_email($customer_email)) {
                return false;
            }

            $subject_template = MPWPB_Global_Function::get_settings('mpwpb_review_settings', 'email_subject', $this->default_review_email_subject());
            $body_template = MPWPB_Global_Function::get_settings('mpwpb_review_settings', 'email_body', $this->default_review_email_body());
            list($subject, $body) = $this->resolve_review_email_content($booking_id, $subject_template, $body_template);

            return $this->dispatch_review_email($booking_id, $customer_email, $subject, $body);
        }

        /**
         * Fills in {customer_name}/{service_name}/{booking_date}/{site_name}/
         * {review_link} against one booking -- split out of
         * send_review_request_email() so the manual Review Request modal
         * (Order List/Service Queue/My Appointment) can show an admin/staff
         * member the exact resolved text *before* sending, not just the raw
         * template.
         *
         * @return array{0: string, 1: string} [$subject, $body]
         */
        private function resolve_review_email_content($booking_id, $subject_template, $body_template) {
            $customer_name = get_post_meta($booking_id, 'mpwpb_billing_name', true);
            $service_id = get_post_meta($booking_id, 'mpwpb_id', true);
            $service_title = get_the_title($service_id);

            // Legacy rows can still be comma-joined for recurring bookings --
            // same defensive first-segment handling already used by
            // MPWPB_Staff_DashBoard::get_staff_appointments().
            $date_raw = get_post_meta($booking_id, 'mpwpb_date', true);
            $first_date = trim(explode(',', $date_raw)[0]);
            $booking_date_formatted = date_i18n(get_option('date_format'), strtotime($first_date));

            $placeholders = array('{customer_name}', '{service_name}', '{booking_date}', '{site_name}', '{review_link}');
            $replacements = array(
                $customer_name !== '' ? $customer_name : __('Customer', 'service-booking-manager'),
                $service_title,
                $booking_date_formatted,
                get_bloginfo('name'),
                get_permalink($service_id),
            );

            return array(
                str_replace($placeholders, $replacements, $subject_template),
                str_replace($placeholders, $replacements, $body_template),
            );
        }

        /**
         * Actually sends a (already-resolved) review-request email and
         * records it -- both the meta flags the eligibility/cron system
         * already checks (so a manually-sent request correctly counts as
         * "sent" and the daily cron won't also email the same customer)
         * and a MPWPB_Booking_History entry (so "already sent" history
         * shows up at the top of the manual Review Request modal).
         */
        private function dispatch_review_email($booking_id, $customer_email, $subject, $body) {
            $sent = wp_mail($customer_email, $subject, nl2br($body), array('Content-Type: text/html; charset=UTF-8'));

            if ($sent) {
                update_post_meta($booking_id, 'mpwpb_review_request_status', 'sent');
                update_post_meta($booking_id, 'mpwpb_review_request_date', current_time('mysql'));
                if (class_exists('MPWPB_Booking_History')) {
                    MPWPB_Booking_History::log($booking_id, MPWPB_Booking_History::ACTION_REVIEW_REQUEST_SENT, '', $customer_email, $subject);
                }
            }

            return $sent;
        }

        /**
         * Same ownership model as MPWPB_Booking_Notes::resolve_role_for_booking()
         * / MPWPB_Order_List::update_service_status() -- an admin may send a
         * review request for any booking, a staff member only for one
         * actually assigned to them, everyone else (including the customer
         * themselves) gets nothing.
         */
        private function current_user_can_send_review_request($booking_id) {
            if (!$booking_id || get_post_type($booking_id) !== 'mpwpb_booking') {
                return false;
            }
            if (current_user_can('manage_options')) {
                return true;
            }
            $user = wp_get_current_user();
            if (in_array('mpwpb_staff', (array) $user->roles, true)) {
                $assigned_staff_id = (int) get_post_meta($booking_id, 'mpwpb_staff_term_id', true);
                return $assigned_staff_id && $assigned_staff_id === (int) $user->ID;
            }
            return false;
        }

        /**
         * Accepts either of the two nonce actions already localized for the
         * two contexts this is reachable from -- wp-admin (Order List/
         * Service Queue) and the front-end staff dashboard (My Appointment)
         * -- same pattern as MPWPB_Booking_Notes::verify_nonce_or_die().
         */
        private function verify_review_request_nonce_or_die() {
            $nonce = isset($_REQUEST['nonce']) ? sanitize_text_field(wp_unslash($_REQUEST['nonce'])) : '';
            if (!wp_verify_nonce($nonce, 'mpwpb_admin_nonce') && !wp_verify_nonce($nonce, 'mpwpb_dashboard_nonce')) {
                wp_send_json_error(array('message' => esc_html__('Security check failed.', 'service-booking-manager')));
            }
        }

        /**
         * Past review-request sends for one booking, newest first, for the
         * "already sent" history shown at the top of the Review Request
         * modal -- filtered out of the booking's full MPWPB_Booking_History
         * (which also holds staff/status/cancel/reschedule entries).
         */
        private function get_review_request_history($booking_id) {
            if (!class_exists('MPWPB_Booking_History')) {
                return array();
            }
            $history = array();
            foreach (array_reverse(MPWPB_Booking_History::get_for_booking($booking_id)) as $row) {
                if ($row->action_type !== MPWPB_Booking_History::ACTION_REVIEW_REQUEST_SENT) {
                    continue;
                }
                $performer = get_userdata($row->performed_by_user_id);
                $history[] = array(
                    'sent_to' => $row->new_date,
                    'subject' => $row->note,
                    'when'    => MPWPB_Global_Function::date_format($row->date_created) . ' ' . MPWPB_Global_Function::date_format($row->date_created, 'time'),
                    'by'      => $performer ? $performer->display_name : esc_html__('Unknown', 'service-booking-manager'),
                );
            }
            return $history;
        }

        /**
         * AJAX: loads the Review Request modal's content for one booking --
         * the resolved (placeholder-free) subject/body an admin/staff member
         * would actually send, whether sending is even possible right now,
         * and the past-sends history. Doesn't send anything or mark
         * anything read/sent itself.
         */
        public function ajax_get_review_request_data() {
            $this->verify_review_request_nonce_or_die();
            $booking_id = isset($_REQUEST['booking_id']) ? absint($_REQUEST['booking_id']) : 0;
            if (!$this->current_user_can_send_review_request($booking_id)) {
                wp_send_json_error(array('message' => esc_html__('You do not have permission to do this.', 'service-booking-manager')));
            }

            $customer_email = get_post_meta($booking_id, 'mpwpb_billing_email', true);
            $has_account = (bool) get_post_meta($booking_id, 'mpwpb_user_id', true);
            $reason = '';
            if (!$has_account) {
                // Mirrors get_eligible_review_bookings_raw()'s own reasoning:
                // a guest booking's customer can never actually submit a
                // review (submit_review() requires login), so requesting one
                // would be misleading busywork, not just unusual.
                $reason = esc_html__('This booking has no registered customer account, so they cannot actually submit a review.', 'service-booking-manager');
            } elseif (!$customer_email || !is_email($customer_email)) {
                $reason = esc_html__('No valid billing email on file for this booking.', 'service-booking-manager');
            }

            $subject_template = MPWPB_Global_Function::get_settings('mpwpb_review_settings', 'email_subject', $this->default_review_email_subject());
            $body_template = MPWPB_Global_Function::get_settings('mpwpb_review_settings', 'email_body', $this->default_review_email_body());
            list($subject, $body) = $this->resolve_review_email_content($booking_id, $subject_template, $body_template);

            wp_send_json_success(array(
                'subject'  => $subject,
                'body'     => $body,
                'can_send' => $reason === '',
                'reason'   => $reason,
                'history'  => $this->get_review_request_history($booking_id),
            ));
        }

        /**
         * AJAX: actually sends the review request, using whatever
         * subject/body the admin/staff member has in the modal at send time
         * (already-resolved text, possibly hand-edited -- not re-run through
         * placeholder substitution).
         */
        public function ajax_send_review_request() {
            $this->verify_review_request_nonce_or_die();
            $booking_id = isset($_REQUEST['booking_id']) ? absint($_REQUEST['booking_id']) : 0;
            if (!$this->current_user_can_send_review_request($booking_id)) {
                wp_send_json_error(array('message' => esc_html__('You do not have permission to do this.', 'service-booking-manager')));
            }

            $subject = isset($_REQUEST['subject']) ? sanitize_text_field(wp_unslash($_REQUEST['subject'])) : '';
            $body = isset($_REQUEST['body']) ? sanitize_textarea_field(wp_unslash($_REQUEST['body'])) : '';
            if ($subject === '' || $body === '') {
                wp_send_json_error(array('message' => esc_html__('Subject and message cannot be empty.', 'service-booking-manager')));
            }

            $customer_email = get_post_meta($booking_id, 'mpwpb_billing_email', true);
            if (!$customer_email || !is_email($customer_email)) {
                wp_send_json_error(array('message' => esc_html__('No valid billing email on file for this booking.', 'service-booking-manager')));
            }

            $sent = $this->dispatch_review_email($booking_id, $customer_email, $subject, $body);
            if (!$sent) {
                wp_send_json_error(array('message' => esc_html__('The email could not be sent. Please try again.', 'service-booking-manager')));
            }

            wp_send_json_success(array(
                'message' => esc_html__('Review request sent.', 'service-booking-manager'),
                'history' => $this->get_review_request_history($booking_id),
            ));
        }

        /**
         * Hooked on the daily mpwpb_review_request_daily_cron -- a no-op
         * unless auto-send is explicitly turned on in the Settings tab.
         */
        public function process_auto_review_requests() {
            $auto_send_enabled = MPWPB_Global_Function::get_settings('mpwpb_review_settings', 'auto_send_enabled', 'no');
            if ($auto_send_enabled !== 'yes') {
                return;
            }

            $send_after_days = (int) MPWPB_Global_Function::get_settings('mpwpb_review_settings', 'send_after_days', 3);
            $cutoff_timestamp = current_time('timestamp') - ($send_after_days * DAY_IN_SECONDS);

            $eligible = $this->get_eligible_review_bookings_raw($cutoff_timestamp);

            foreach ($eligible as $row) {
                if ($row['request_status'] === 'sent') {
                    continue;
                }
                $this->send_review_request_email($row['booking_id']);
            }
        }

        /**
         * Saves the Review Settings tab. Hooked on admin_init like
         * handle_review_actions()/maybe_export_csv(), self-guarding on the
         * presence of its own POST field + nonce.
         */
        public function save_review_settings() {
            if (!isset($_POST['mpwpb_save_review_settings'])) {
                return;
            }
            if (!isset($_GET['page']) || $_GET['page'] !== 'mpwpb-reviews') {
                return;
            }
            if (!current_user_can('manage_options')) {
                wp_die(esc_html__('You do not have permission to perform this action', 'service-booking-manager'));
            }
            check_admin_referer('mpwpb_save_review_settings', 'mpwpb_review_settings_nonce');

            $settings = array(
                'auto_send_enabled' => (isset($_POST['auto_send_enabled']) && $_POST['auto_send_enabled'] === 'yes') ? 'yes' : 'no',
                'send_after_days'   => isset($_POST['send_after_days']) ? max(0, intval($_POST['send_after_days'])) : 3,
                'email_subject'     => isset($_POST['email_subject']) ? sanitize_text_field(wp_unslash($_POST['email_subject'])) : '',
                'email_body'        => isset($_POST['email_body']) ? wp_kses_post(wp_unslash($_POST['email_body'])) : '',
            );

            update_option('mpwpb_review_settings', $settings);

            wp_redirect(add_query_arg('review_settings_saved', '1', admin_url('admin.php?page=mpwpb-reviews')));
            exit;
        }

        /**
         * Default review-request email subject (used both to pre-fill the
         * Settings tab and as the send-time fallback before any setting has
         * ever been saved).
         */
        private function default_review_email_subject() {
            return __('How was your {service_name} experience?', 'service-booking-manager');
        }

        /**
         * Default review-request email body (see default_review_email_subject()).
         */
        private function default_review_email_body() {
            return __("Hi {customer_name},\n\nThank you for booking {service_name} on {booking_date}. We'd love to hear how it went!\n\nPlease take a moment to leave a review:\n{review_link}\n\nThanks,\n{site_name}", 'service-booking-manager');
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
         * All mpwpb_booking posts eligible for a review request as of
         * $cutoff_timestamp: service date on/before the cutoff, not
         * cancelled, has a logged-in customer (mpwpb_user_id set -- reviews
         * require being logged in via user_can_review(), so a guest could
         * never actually complete one), and no existing review row (any
         * status) for that customer+service pair yet.
         *
         * Deliberately filters in PHP rather than via meta_query date
         * comparison (mpwpb_date has no seconds component, which makes
         * meta_query's DATETIME type comparison unreliable) -- mirrors the
         * same "fetch then filter in PHP" style already used for date
         * comparisons elsewhere in this plugin (e.g.
         * MPWPB_Staff_DashBoard::get_user_upcoming_bookings()).
         */
        private function get_eligible_review_bookings_raw($cutoff_timestamp) {
            global $wpdb;

            $bookings = get_posts(array(
                'post_type'      => 'mpwpb_booking',
                'posts_per_page' => -1,
                'post_status'    => 'publish',
                'meta_query'     => array(
                    array('key' => 'mpwpb_order_status', 'value' => 'cancelled', 'compare' => '!='),
                ),
            ));

            $reviewed_pairs = array();
            $reviews_table = $wpdb->prefix . 'mpwpb_reviews';
            if ($wpdb->get_var("SHOW TABLES LIKE '$reviews_table'") == $reviews_table) {
                $rows = $wpdb->get_results("SELECT DISTINCT user_id, service_id FROM $reviews_table");
                foreach ($rows as $row) {
                    $reviewed_pairs[$row->user_id . '_' . $row->service_id] = true;
                }
            }

            $eligible = array();
            foreach ($bookings as $booking) {
                $user_id = get_post_meta($booking->ID, 'mpwpb_user_id', true);
                if ($user_id === '' || $user_id === null) {
                    continue;
                }

                $service_id = get_post_meta($booking->ID, 'mpwpb_id', true);
                if (isset($reviewed_pairs[$user_id . '_' . $service_id])) {
                    continue;
                }

                $date_raw = get_post_meta($booking->ID, 'mpwpb_date', true);
                $first_date = trim(explode(',', $date_raw)[0]);
                $booking_timestamp = $first_date !== '' ? strtotime($first_date) : false;
                if (!$booking_timestamp || $booking_timestamp > $cutoff_timestamp) {
                    continue;
                }

                $eligible[] = array(
                    'booking_id'        => $booking->ID,
                    'user_id'           => $user_id,
                    'service_id'        => $service_id,
                    'booking_timestamp' => $booking_timestamp,
                    'customer_name'     => get_post_meta($booking->ID, 'mpwpb_billing_name', true),
                    'customer_email'    => get_post_meta($booking->ID, 'mpwpb_billing_email', true),
                    'request_status'    => get_post_meta($booking->ID, 'mpwpb_review_request_status', true),
                    'request_date'      => get_post_meta($booking->ID, 'mpwpb_review_request_date', true),
                );
            }

            usort($eligible, function ($a, $b) {
                return $b['booking_timestamp'] <=> $a['booking_timestamp'];
            });

            return $eligible;
        }

        /**
         * get_eligible_review_bookings_raw() (as of right now) narrowed to
         * the Review Requested tab's own Service/Request-Status filters.
         */
        private function filter_eligible_bookings($service_filter = 0, $request_status_filter = '') {
            $all = $this->get_eligible_review_bookings_raw(current_time('timestamp'));

            if (!$service_filter && $request_status_filter === '') {
                return $all;
            }

            return array_values(array_filter($all, function ($item) use ($service_filter, $request_status_filter) {
                if ($service_filter && (int) $item['service_id'] !== (int) $service_filter) {
                    return false;
                }
                if ($request_status_filter === 'sent' && $item['request_status'] !== 'sent') {
                    return false;
                }
                if ($request_status_filter === 'not_sent' && $item['request_status'] === 'sent') {
                    return false;
                }
                return true;
            }));
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

        /**
         * All inline CSS for the Reviews page (list, requested, settings
         * tabs) -- kept as one method (rather than one giant inline block in
         * reviews_page() like before) now that the page has grown to 3 tabs.
         */
        private function render_styles() {
            ?>
            <style>
                .mpwpb-reviews-page{background:#f8fafc;margin-right:20px;padding:20px 0;}
                .mpwpb-reviews-page *{box-sizing:border-box;}

                .mpwpb-reviews-header{display:flex;align-items:flex-start;justify-content:space-between;gap:20px;margin-bottom:20px;padding-left:20px;flex-wrap:wrap;}
                .mpwpb-reviews-header h1{font-size:24px;font-weight:700;color:#0f172a;margin:0 0 6px;padding:0;}
                .mpwpb-reviews-subtitle{margin:0;color:#64748b;font-size:13.5px;}
                .mpwpb-reviews-export-btn{display:inline-flex;align-items:center;gap:6px;background:#fff;color:#2563eb;border:1px solid #cbd5e1;border-radius:8px;padding:9px 16px;font-size:13px;font-weight:600;text-decoration:none;white-space:nowrap;}
                .mpwpb-reviews-export-btn:hover{background:#eff6ff;border-color:#2563eb;color:#1d4ed8;}
                .mpwpb-reviews-export-btn .dashicons{font-size:16px;width:16px;height:16px;line-height:16px;}

                .mpwpb-reviews-tabs{margin:0 20px 20px;}

                /* Overrides the shared .mpwpb_tabs solid-fill active state
                   (mp_global/assets/mp_style/mpwpb_plugin_global.css) with a
                   plain underline + icon style, scoped to this page only --
                   that shared default is reused as-is everywhere else (e.g.
                   the per-service Settings metabox), so it isn't touched. */
                .mpwpb-reviews-tabs .tabLists{background:#fff;border:1px solid #eef1f5;border-radius:12px;box-shadow:0 1px 3px rgba(15,23,42,.05);padding:0 8px;}
                .mpwpb-reviews-tabs .tabLists ul{display:flex;list-style:none;margin:0;padding:0;}
                .mpwpb-reviews-tabs [data-tabs-target]{background:transparent !important;color:#64748b !important;font-weight:600 !important;font-size:13.5px;padding:16px 18px !important;margin-bottom:-1px;border-bottom:3px solid transparent !important;gap:8px;}
                .mpwpb-reviews-tabs [data-tabs-target] .dashicons{font-size:16px;width:16px;height:16px;line-height:16px;color:#94a3b8;}
                .mpwpb-reviews-tabs [data-tabs-target]:hover{color:#0f172a !important;border-color:#cbd5e1 !important;}
                .mpwpb-reviews-tabs [data-tabs-target]:hover .dashicons{color:#64748b;}
                .mpwpb-reviews-tabs [data-tabs-target].active{color:#16a34a !important;border-color:#16a34a !important;}
                .mpwpb-reviews-tabs [data-tabs-target].active .dashicons{color:#16a34a;}
                .mpwpb-reviews-tabs .tabsContent{padding:20px 0 0;}

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
                .mpwpb-reviews-table th{text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.03em;color:#64748b;background:#eef2ff;padding:12px 16px;border-bottom:1px solid #e0e7ff;white-space:nowrap;}
                .mpwpb-reviews-table td{padding:14px 16px;border-bottom:1px solid #f1f5f9;vertical-align:middle;color:#334155;}
                .mpwpb-reviews-table tbody tr:last-child td{border-bottom:none;}
                .mpwpb-reviews-table tbody tr:hover{background:#f8fafc;}
                .mpwpb-reviews-id{color:#94a3b8;font-weight:600;white-space:nowrap;}

                .mpwpb-reviews-service-cell{display:flex;align-items:center;gap:10px;text-decoration:none;color:#0f172a;white-space:nowrap;}
                .mpwpb-reviews-avatar{flex:0 0 auto;width:30px;height:30px;border-radius:8px;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:13px;text-transform:uppercase;}

                .mpwpb-reviews-stars{white-space:nowrap;display:inline-flex;}
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
                .mpwpb-status-notsent{background-color:#e2e8f0;color:#64748b;}

                .mpwpb-review-actions{white-space:nowrap;}
                td.mpwpb-review-actions{display:inline-flex;}
                .mpwpb-reviews-icon-btn{display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:8px;background:#f1f5f9;border:none;color:#475569;cursor:pointer;margin-right:6px;text-decoration:none;vertical-align:middle;}
                .mpwpb-reviews-icon-btn:last-child{margin-right:0;}
                .mpwpb-reviews-icon-btn .dashicons{font-size:16px;width:16px;height:16px;line-height:16px;}
                .mpwpb-reviews-icon-btn:hover{background:#e2e8f0;color:#0f172a;}
                .mpwpb-reviews-icon-btn--approve{color:#16a34a;}
                .mpwpb-reviews-icon-btn--approve:hover{background:#dcfce7;color:#16a34a;}
                .mpwpb-reviews-icon-btn--danger:hover{background:#fee2e2;color:#dc2626;}

                .mpwpb-reviews-send-request-btn{display:inline-flex;align-items:center;justify-content:center;padding:6px 14px;border-radius:8px;background:#2563eb;color:#fff;font-size:12.5px;font-weight:600;text-decoration:none;white-space:nowrap;}
                .mpwpb-reviews-send-request-btn:hover{background:#1d4ed8;color:#fff;}

                .mpwpb-no-reviews{padding:40px 20px;text-align:center;color:#64748b;}

                .mpwpb-reviews-footer{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;padding:14px 20px;border-top:1px solid #eef1f5;}
                .mpwpb-reviews-showing{font-size:12.5px;color:#64748b;}
                .mpwpb-reviews-pagination{display:flex;align-items:center;gap:6px;}
                .mpwpb-reviews-pagination .page-numbers{display:inline-flex;align-items:center;justify-content:center;min-width:30px;height:30px;padding:0 8px;border-radius:8px;background:#fff;border:1px solid #e2e8f0;color:#475569;font-size:12.5px;text-decoration:none;}
                .mpwpb-reviews-pagination .page-numbers.current{background:#2563eb;border-color:#2563eb;color:#fff;}
                .mpwpb-reviews-pagination .page-numbers:hover:not(.current){background:#f1f5f9;}
                .mpwpb-reviews-pagination .page-numbers .dashicons{font-size:14px;width:14px;height:14px;line-height:14px;}
                .mpwpb-reviews-pagination .page-numbers.dots{border-color:transparent;background:transparent;}

                .mpwpb-review-settings-card{padding:24px;}
                .mpwpb-reviews-settings-notice{background:#dcfce7;color:#16a34a;border-radius:8px;padding:10px 16px;margin-bottom:16px;font-size:13px;font-weight:600;}
                .mpwpb-review-settings-row{margin-bottom:20px;max-width:640px;}
                .mpwpb-review-settings-row label{display:block;font-size:13px;font-weight:600;color:#0f172a;margin-bottom:8px;}
                .mpwpb-review-settings-row select{border:1px solid #d8dee7;border-radius:8px;padding:8px 10px;font-size:13px;color:#0f172a;background:#fff;min-height:36px;}
                .mpwpb-review-settings-desc{margin:8px 0 0;color:#64748b;font-size:12.5px;}
                .mpwpb-review-settings-desc code{background:#f1f5f9;padding:2px 6px;border-radius:4px;font-size:11.5px;}
                .mpwpb-review-settings-number{width:120px;border:1px solid #d8dee7;border-radius:8px;padding:8px 10px;font-size:13px;}
                .mpwpb-review-settings-text{width:100%;border:1px solid #d8dee7;border-radius:8px;padding:8px 10px;font-size:13px;}

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
            <?php
        }

        /**
         * View-modal open/close JS for the Review List tab (unchanged from
         * before the tabs refactor, just factored out of reviews_page()).
         */
        private function render_list_scripts() {
            ?>
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
            <?php
        }
    }

    new MPWPB_Reviews_Admin();
}
