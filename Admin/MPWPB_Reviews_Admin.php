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
            
            // Add AJAX handlers for admin actions
            add_action('wp_ajax_mpwpb_change_review_status', array($this, 'change_review_status'));
            add_action('wp_ajax_mpwpb_delete_review', array($this, 'delete_review'));
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
            $per_page = 20;
            $offset = ($page - 1) * $per_page;
            
            $status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
            $service_filter = isset($_GET['service_id']) ? intval($_GET['service_id']) : 0;
            
            $reviews = $this->get_reviews($status_filter, $service_filter, $per_page, $offset);
            $total_reviews = $this->get_reviews_count($status_filter, $service_filter);
            $total_pages = ceil($total_reviews / $per_page);
            
            // Get services for filter dropdown
            $services = get_posts(array(
                'post_type' => 'mpwpb_service',
                'posts_per_page' => -1,
                'post_status' => 'publish'
            ));
            
            ?>
            <div class="wrap">
                <h1 class="wp-heading-inline"><?php esc_html_e('Service Reviews', 'service-booking-manager'); ?></h1>
                
                <div class="mpwpb-admin-reviews-filters">
                    <form method="get">
                        <input type="hidden" name="post_type" value="mpwpb_service">
                        <input type="hidden" name="page" value="mpwpb-reviews">
                        
                        <select name="status">
                            <option value=""><?php esc_html_e('All Statuses', 'service-booking-manager'); ?></option>
                            <option value="approved" <?php selected($status_filter, 'approved'); ?>><?php esc_html_e('Approved', 'service-booking-manager'); ?></option>
                            <option value="pending" <?php selected($status_filter, 'pending'); ?>><?php esc_html_e('Pending', 'service-booking-manager'); ?></option>
                        </select>
                        
                        <select name="service_id">
                            <option value=""><?php esc_html_e('All Services', 'service-booking-manager'); ?></option>
                            <?php foreach ($services as $service) : ?>
                                <option value="<?php echo esc_attr($service->ID); ?>" <?php selected($service_filter, $service->ID); ?>>
                                    <?php echo esc_html($service->post_title); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        
                        <input type="submit" class="button" value="<?php esc_attr_e('Filter', 'service-booking-manager'); ?>">
                    </form>
                </div>
                
                <?php if (empty($reviews)) : ?>
                    <div class="mpwpb-no-reviews">
                        <p><?php esc_html_e('No reviews found.', 'service-booking-manager'); ?></p>
                    </div>
                <?php else : ?>
                    <table class="wp-list-table widefat fixed striped">
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
                            <?php foreach ($reviews as $review) : ?>
                                <tr>
                                    <td><?php echo esc_html($review->id); ?></td>
                                    <td>
                                        <a href="<?php echo esc_url(get_edit_post_link($review->service_id)); ?>">
                                            <?php echo esc_html(get_the_title($review->service_id)); ?>
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
                                        <?php 
                                        for ($i = 1; $i <= 5; $i++) {
                                            if ($i <= $review->rating) {
                                                echo '<span class="dashicons dashicons-star-filled" style="color: #ffb900;"></span>';
                                            } else {
                                                echo '<span class="dashicons dashicons-star-empty" style="color: #ccc;"></span>';
                                            }
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <strong><?php echo esc_html($review->title); ?></strong>
                                        <p><?php echo esc_html(wp_trim_words($review->content, 15)); ?></p>
                                    </td>
                                    <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($review->date_created))); ?></td>
                                    <td>
                                        <span class="mpwpb-review-status mpwpb-status-<?php echo esc_attr($review->status); ?>">
                                            <?php echo esc_html(ucfirst($review->status)); ?>
                                        </span>
                                    </td>
                                    <td class="mpwpb-review-actions">
                                        <?php if ($review->status === 'pending') : ?>
                                            <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=mpwpb-reviews&action=approve&review_id=' . $review->id), 'mpwpb_approve_review')); ?>" class="button button-primary">
                                                <?php esc_html_e('Approve', 'service-booking-manager'); ?>
                                            </a>
                                        <?php else : ?>
                                            <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=mpwpb-reviews&action=pending&review_id=' . $review->id), 'mpwpb_pending_review')); ?>" class="button">
                                                <?php esc_html_e('Pending', 'service-booking-manager'); ?>
                                            </a>
                                        <?php endif; ?>
                                        
                                        <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=mpwpb-reviews&action=delete&review_id=' . $review->id), 'mpwpb_delete_review')); ?>" class="button" onclick="return confirm('<?php esc_attr_e('Are you sure you want to delete this review?', 'service-booking-manager'); ?>')">
                                            <?php esc_html_e('Delete', 'service-booking-manager'); ?>
                                        </a>
                                        
                                        <button type="button" class="button mpwpb-view-review-btn" data-id="<?php echo esc_attr($review->id); ?>" data-title="<?php echo esc_attr($review->title); ?>" data-content="<?php echo esc_attr($review->content); ?>">
                                            <?php esc_html_e('View', 'service-booking-manager'); ?>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <?php if ($total_pages > 1) : ?>
                        <div class="tablenav bottom">
                            <div class="tablenav-pages">
                                <span class="displaying-num">
                                    <?php echo sprintf(_n('%s item', '%s items', $total_reviews, 'service-booking-manager'), number_format_i18n($total_reviews)); ?>
                                </span>
                                <span class="pagination-links">
                                    <?php
                                    echo paginate_links(array(
                                        'base' => add_query_arg('paged', '%#%'),
                                        'format' => '',
                                        'prev_text' => '&laquo;',
                                        'next_text' => '&raquo;',
                                        'total' => $total_pages,
                                        'current' => $page
                                    ));
                                    ?>
                                </span>
                            </div>
                        </div>
                    <?php endif; ?>
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
                    .mpwpb-admin-reviews-filters {
                        margin: 15px 0;
                    }
                    .mpwpb-admin-reviews-filters select {
                        margin-right: 10px;
                    }
                    .mpwpb-review-status {
                        display: inline-block;
                        padding: 3px 8px;
                        border-radius: 3px;
                        font-weight: bold;
                    }
                    .mpwpb-status-approved {
                        background-color: #dff0d8;
                        color: #3c763d;
                    }
                    .mpwpb-status-pending {
                        background-color: #fcf8e3;
                        color: #8a6d3b;
                    }
                    .mpwpb-review-actions .button {
                        margin-right: 5px;
                    }
                    
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
        private function get_reviews($status = '', $service_id = 0, $limit = 20, $offset = 0) {
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
            
            $sql .= " ORDER BY date_created DESC LIMIT %d OFFSET %d";
            $prepare_args[] = $limit;
            $prepare_args[] = $offset;
            
            return $wpdb->get_results($wpdb->prepare($sql, $prepare_args));
        }
        
        /**
         * Get total count of reviews with filters
         */
        private function get_reviews_count($status = '', $service_id = 0) {
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
            
            return $wpdb->get_var($wpdb->prepare($sql, $prepare_args));
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