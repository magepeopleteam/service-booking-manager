<?php
/*
* @Author 		MagePeople Team
* Copyright: 	mage-people.com
*/
if (!defined('ABSPATH')) {
    die;
} // Cannot access pages directly.

if (!class_exists('MPWPB_User_Dashboard')) {
    class MPWPB_User_Dashboard {
        public function __construct() {
            add_shortcode('mpwpb-user-dashboard', array($this, 'user_dashboard'));
            add_action('wp_ajax_mpwpb_cancel_booking', array($this, 'cancel_booking'));
            add_action('wp_ajax_mpwpb_reschedule_booking', array($this, 'reschedule_booking'));
            add_action('wp_ajax_mpwpb_update_user_profile', array($this, 'update_user_profile'));
            add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        }

        /**
         * Enqueue necessary scripts and styles for the dashboard
         */
        public function enqueue_scripts() {
            wp_enqueue_style('mpwpb-user-dashboard', MPWPB_PLUGIN_URL . '/assets/frontend/mpwpb_user_dashboard.css', array(), time());
            wp_enqueue_script('mpwpb-user-dashboard', MPWPB_PLUGIN_URL . '/assets/frontend/mpwpb_user_dashboard.js', array('jquery'), time(), true);
            wp_localize_script('mpwpb-user-dashboard', 'mpwpb_dashboard', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('mpwpb_dashboard_nonce'),
                'cancel_confirm' => esc_html__('Are you sure you want to cancel this booking?', 'service-booking-manager'),
                'reschedule_confirm' => esc_html__('Are you sure you want to reschedule this booking?', 'service-booking-manager')
            ));
        }

        /**
         * Main dashboard shortcode callback
         */
        public function user_dashboard($atts) {
            ob_start();
            
            // Check if user is logged in
            if (!is_user_logged_in()) {
                $this->login_form();
                return ob_get_clean();
            }

            $user_id = get_current_user_id();
            $tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'bookings';
            
            // Dashboard tabs
         // Dashboard tabs
$tabs = array(
    'bookings' => esc_html__('My Bookings', 'service-booking-manager'),
    'upcoming' => esc_html__('Upcoming Appointments', 'service-booking-manager'),
    'reviews' => esc_html__('My Reviews', 'service-booking-manager'),
    'profile' => esc_html__('My Profile', 'service-booking-manager')
);

            
            ?>
            <div class="mpStyle mpwpb-user-dashboard">
                <div class="mpwpb-dashboard-tabs">
                    <ul class="mpwpb-tabs-nav">
                        <?php foreach ($tabs as $tab_key => $tab_label) : ?>
                            <li class="<?php echo $tab === $tab_key ? 'active' : ''; ?>">
                                <a href="?tab=<?php echo esc_attr($tab_key); ?>"><?php echo esc_html($tab_label); ?></a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <div class="mpwpb-dashboard-content">
                    <?php
                    switch ($tab) {
                        case 'upcoming':
                            $this->upcoming_appointments($user_id);
                            break;
                        case 'reviews':
                            do_action('mpwpb_dashboard_content', 'reviews', $user_id);
                            break;
                        case 'profile':
                            $this->user_profile($user_id);
                            break;
                        default:
                            $this->booking_history($user_id);
                            break;
                    }
                    
                    ?>
                </div>
            </div>
            <?php
            
            return ob_get_clean();
        }

        /**
         * Display login form for non-logged in users
         */
        private function login_form() {
            ?>
            <div class="mpStyle mpwpb-login-form">
                <h3><?php esc_html_e('Please login to view your bookings', 'service-booking-manager'); ?></h3>
                <?php wp_login_form(array('redirect' => get_permalink())); ?>
                <p>
                    <?php esc_html_e('Don\'t have an account?', 'service-booking-manager'); ?>
                    <a href="<?php echo esc_url(wp_registration_url()); ?>"><?php esc_html_e('Register here', 'service-booking-manager'); ?></a>
                </p>
            </div>
            <?php
        }

        /**
         * Display booking history
         */
        private function booking_history($user_id) {
            $bookings = $this->get_user_bookings($user_id);
            
            if (empty($bookings)) {
                echo '<div class="mpwpb-no-bookings">' . esc_html__('You have no bookings yet.', 'service-booking-manager') . '</div>';
                return;
            }
            
            ?>
            <div class="mpwpb-booking-history">
                <h3><?php esc_html_e('My Booking History', 'service-booking-manager'); ?></h3>
                <table class="mpwpb-bookings-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Booking ID', 'service-booking-manager'); ?></th>
                            <th><?php esc_html_e('Service', 'service-booking-manager'); ?></th>
                            <th><?php esc_html_e('Date', 'service-booking-manager'); ?></th>
                            <th><?php esc_html_e('Time', 'service-booking-manager'); ?></th>
                            <th><?php esc_html_e('Status', 'service-booking-manager'); ?></th>
                            <th><?php esc_html_e('Actions', 'service-booking-manager'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $booking) : ?>
                            <tr>
                                <td>#<?php echo esc_html($booking->ID); ?></td>
                                <td><?php echo esc_html(get_the_title($booking->mpwpb_id)); ?></td>
                                <td><?php echo esc_html(MPWPB_Global_Function::date_format($booking->mpwpb_date)); ?></td>
                                <td><?php echo esc_html(MPWPB_Global_Function::date_format($booking->mpwpb_date, 'time')); ?></td>
                                <td>
                                    <span class="mpwpb-status mpwpb-status-<?php echo esc_attr(strtolower($booking->mpwpb_order_status)); ?>">
                                        <?php echo esc_html(ucfirst($booking->mpwpb_order_status)); ?>
                                    </span>
                                </td>
                                <td class="mpwpb-actions">
                                    <?php if ($this->can_cancel_booking($booking)) : ?>
                                        <button class="mpwpb-btn mpwpb-cancel-btn" data-id="<?php echo esc_attr($booking->ID); ?>">
                                            <?php esc_html_e('Cancel', 'service-booking-manager'); ?>
                                        </button>
                                    <?php endif; ?>
                                    
                                    <?php if ($this->can_reschedule_booking($booking)) : ?>
                                        <button class="mpwpb-btn mpwpb-reschedule-btn" data-id="<?php echo esc_attr($booking->ID); ?>" data-service="<?php echo esc_attr($booking->mpwpb_id); ?>">
                                            <?php esc_html_e('Reschedule', 'service-booking-manager'); ?>
                                        </button>
                                    <?php endif; ?>
                                    
                                    <a href="<?php echo esc_url(get_permalink($booking->mpwpb_id)); ?>" class="mpwpb-btn mpwpb-view-btn">
                                        <?php esc_html_e('View Service', 'service-booking-manager'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Reschedule Modal -->
            <div id="mpwpb-reschedule-modal" class="mpwpb-modal">
                <div class="mpwpb-modal-content">
                    <span class="mpwpb-close">&times;</span>
                    <h3><?php esc_html_e('Reschedule Booking', 'service-booking-manager'); ?></h3>
                    <form id="mpwpb-reschedule-form">
                        <input type="hidden" id="booking_id" name="booking_id">
                        <input type="hidden" id="service_id" name="service_id">
                        
                        <div class="mpwpb-form-group">
                            <label for="new_date"><?php esc_html_e('Select New Date', 'service-booking-manager'); ?></label>
                            <select id="new_date" name="new_date" required>
                                <option value=""><?php esc_html_e('Select Date', 'service-booking-manager'); ?></option>
                                <!-- Options will be populated via AJAX -->
                            </select>
                        </div>
                        
                        <div class="mpwpb-form-group">
                            <label for="new_time"><?php esc_html_e('Select New Time', 'service-booking-manager'); ?></label>
                            <select id="new_time" name="new_time" required>
                                <option value=""><?php esc_html_e('Select Time', 'service-booking-manager'); ?></option>
                                <!-- Options will be populated via AJAX -->
                            </select>
                        </div>
                        
                        <div class="mpwpb-form-group">
                            <button type="submit" class="mpwpb-btn mpwpb-submit-btn">
                                <?php esc_html_e('Confirm Reschedule', 'service-booking-manager'); ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <?php
        }

        /**
         * Display upcoming appointments
         */
        private function upcoming_appointments($user_id) {
            $bookings = $this->get_user_upcoming_bookings($user_id);
            
            if (empty($bookings)) {
                echo '<div class="mpwpb-no-bookings">' . esc_html__('You have no upcoming appointments.', 'service-booking-manager') . '</div>';
                return;
            }
            
            ?>
            <div class="mpwpb-upcoming-appointments">
                <h3><?php esc_html_e('My Upcoming Appointments', 'service-booking-manager'); ?></h3>
                <div class="mpwpb-appointments-grid">
                    <?php foreach ($bookings as $booking) : ?>
                        <div class="mpwpb-appointment-card">
                            <div class="mpwpb-appointment-image">
                                <?php echo get_the_post_thumbnail($booking->mpwpb_id, 'thumbnail'); ?>
                            </div>
                            <div class="mpwpb-appointment-details">
                                <h4><?php echo esc_html(get_the_title($booking->mpwpb_id)); ?></h4>
                                <p class="mpwpb-appointment-date">
                                    <i class="far fa-calendar-alt"></i>
                                    <?php echo esc_html(MPWPB_Global_Function::date_format($booking->mpwpb_date)); ?>
                                </p>
                                <p class="mpwpb-appointment-time">
                                    <i class="far fa-clock"></i>
                                    <?php echo esc_html(MPWPB_Global_Function::date_format($booking->mpwpb_date, 'time')); ?>
                                </p>
                                <p class="mpwpb-appointment-status">
                                    <span class="mpwpb-status mpwpb-status-<?php echo esc_attr(strtolower($booking->mpwpb_order_status)); ?>">
                                        <?php echo esc_html(ucfirst($booking->mpwpb_order_status)); ?>
                                    </span>
                                </p>
                                <div class="mpwpb-appointment-actions">
                                    <?php if ($this->can_cancel_booking($booking)) : ?>
                                        <button class="mpwpb-btn mpwpb-cancel-btn" data-id="<?php echo esc_attr($booking->ID); ?>">
                                            <?php esc_html_e('Cancel', 'service-booking-manager'); ?>
                                        </button>
                                    <?php endif; ?>
                                    
                                    <?php if ($this->can_reschedule_booking($booking)) : ?>
                                        <button class="mpwpb-btn mpwpb-reschedule-btn" data-id="<?php echo esc_attr($booking->ID); ?>" data-service="<?php echo esc_attr($booking->mpwpb_id); ?>">
                                            <?php esc_html_e('Reschedule', 'service-booking-manager'); ?>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php
        }

        /**
         * Display user profile management form
         */
        private function user_profile($user_id) {
            $user = get_userdata($user_id);
            ?>
            <div class="mpwpb-user-profile">
                <h3><?php esc_html_e('My Profile', 'service-booking-manager'); ?></h3>
                
                <form id="mpwpb-profile-form" class="mpwpb-profile-form">
                    <div class="mpwpb-form-row">
                        <div class="mpwpb-form-group">
                            <label for="first_name"><?php esc_html_e('First Name', 'service-booking-manager'); ?></label>
                            <input type="text" id="first_name" name="first_name" value="<?php echo esc_attr($user->first_name); ?>" required>
                        </div>
                        
                        <div class="mpwpb-form-group">
                            <label for="last_name"><?php esc_html_e('Last Name', 'service-booking-manager'); ?></label>
                            <input type="text" id="last_name" name="last_name" value="<?php echo esc_attr($user->last_name); ?>" required>
                        </div>
                    </div>
                    
                    <div class="mpwpb-form-row">
                        <div class="mpwpb-form-group">
                            <label for="email"><?php esc_html_e('Email', 'service-booking-manager'); ?></label>
                            <input type="email" id="email" name="email" value="<?php echo esc_attr($user->user_email); ?>" required>
                        </div>
                        
                        <div class="mpwpb-form-group">
                            <label for="phone"><?php esc_html_e('Phone', 'service-booking-manager'); ?></label>
                            <input type="tel" id="phone" name="phone" value="<?php echo esc_attr(get_user_meta($user_id, 'billing_phone', true)); ?>">
                        </div>
                    </div>
                    
                    <div class="mpwpb-form-row">
                        <div class="mpwpb-form-group">
                            <label for="address"><?php esc_html_e('Address', 'service-booking-manager'); ?></label>
                            <input type="text" id="address" name="address" value="<?php echo esc_attr(get_user_meta($user_id, 'billing_address_1', true)); ?>">
                        </div>
                        
                        <div class="mpwpb-form-group">
                            <label for="city"><?php esc_html_e('City', 'service-booking-manager'); ?></label>
                            <input type="text" id="city" name="city" value="<?php echo esc_attr(get_user_meta($user_id, 'billing_city', true)); ?>">
                        </div>
                    </div>
                    
                    <div class="mpwpb-form-row">
                        <div class="mpwpb-form-group">
                            <label for="password"><?php esc_html_e('New Password (leave blank to keep current)', 'service-booking-manager'); ?></label>
                            <input type="password" id="password" name="password">
                        </div>
                        
                        <div class="mpwpb-form-group">
                            <label for="password_confirm"><?php esc_html_e('Confirm New Password', 'service-booking-manager'); ?></label>
                            <input type="password" id="password_confirm" name="password_confirm">
                        </div>
                    </div>
                    
                    <div class="mpwpb-form-group">
                        <label for="preferences"><?php esc_html_e('Service Preferences', 'service-booking-manager'); ?></label>
                        <textarea id="preferences" name="preferences" rows="3"><?php echo esc_textarea(get_user_meta($user_id, 'mpwpb_service_preferences', true)); ?></textarea>
                        <p class="description"><?php esc_html_e('Enter any preferences you have for your service bookings.', 'service-booking-manager'); ?></p>
                    </div>
                    
                    <div class="mpwpb-form-group">
                        <button type="submit" class="mpwpb-btn mpwpb-submit-btn">
                            <?php esc_html_e('Update Profile', 'service-booking-manager'); ?>
                        </button>
                        <div id="mpwpb-profile-message" class="mpwpb-message"></div>
                    </div>
                </form>
            </div>
            <?php
        }

        /**
         * Get all bookings for a user
         */
        private function get_user_bookings($user_id) {
            global $wpdb;
            
            $bookings = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}posts p
                    JOIN {$wpdb->prefix}postmeta pm ON p.ID = pm.post_id
                    WHERE p.post_type = 'mpwpb_booking'
                    AND pm.meta_key = 'mpwpb_user_id'
                    AND pm.meta_value = %d
                    ORDER BY p.post_date DESC",
                    $user_id
                )
            );
            
            if (empty($bookings)) {
                return array();
            }
            
            $formatted_bookings = array();
            foreach ($bookings as $booking) {
                $booking_data = new stdClass();
                $booking_data->ID = $booking->ID;
                
                // Get booking meta data
                $meta = get_post_meta($booking->ID);
                $booking_data->mpwpb_id = isset($meta['mpwpb_id']) ? $meta['mpwpb_id'][0] : '';
                $booking_data->mpwpb_date = isset($meta['mpwpb_date']) ? $meta['mpwpb_date'][0] : '';
                $booking_data->mpwpb_order_id = isset($meta['mpwpb_order_id']) ? $meta['mpwpb_order_id'][0] : '';
                $booking_data->mpwpb_order_status = isset($meta['mpwpb_order_status']) ? $meta['mpwpb_order_status'][0] : '';
                
                $formatted_bookings[] = $booking_data;
            }
            
            return $formatted_bookings;
        }

        /**
         * Get upcoming bookings for a user
         */
        private function get_user_upcoming_bookings($user_id) {
            $bookings = $this->get_user_bookings($user_id);
            $upcoming = array();
            $current_time = current_time('timestamp');
            
            foreach ($bookings as $booking) {
                $booking_time = strtotime($booking->mpwpb_date);
                if ($booking_time > $current_time && $booking->mpwpb_order_status != 'cancelled') {
                    $upcoming[] = $booking;
                }
            }
            
            return $upcoming;
        }

        /**
         * Check if a booking can be cancelled
         */
        private function can_cancel_booking($booking) {
            // Check if booking is in the future and not already cancelled
            $booking_time = strtotime($booking->mpwpb_date);
            $current_time = current_time('timestamp');
            $cancellation_period = 24 * 60 * 60; // 24 hours in seconds
            
            return ($booking_time > ($current_time + $cancellation_period)) && $booking->mpwpb_order_status != 'cancelled';
        }

        /**
         * Check if a booking can be rescheduled
         */
        private function can_reschedule_booking($booking) {
            // Check if booking is in the future and not cancelled
            $booking_time = strtotime($booking->mpwpb_date);
            $current_time = current_time('timestamp');
            $reschedule_period = 48 * 60 * 60; // 48 hours in seconds
            
            return ($booking_time > ($current_time + $reschedule_period)) && $booking->mpwpb_order_status != 'cancelled';
        }

        /**
         * AJAX handler for cancelling a booking
         */
        public function cancel_booking() {
            // Verify nonce
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mpwpb_dashboard_nonce')) {
                wp_send_json_error(array('message' => esc_html__('Security check failed', 'service-booking-manager')));
            }
            
            $booking_id = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : 0;
            
            if (!$booking_id) {
                wp_send_json_error(array('message' => esc_html__('Invalid booking ID', 'service-booking-manager')));
            }
            
            // Check if user owns this booking
            $user_id = get_current_user_id();
            $booking_user_id = get_post_meta($booking_id, 'mpwpb_user_id', true);
            
            if ($user_id != $booking_user_id) {
                wp_send_json_error(array('message' => esc_html__('You do not have permission to cancel this booking', 'service-booking-manager')));
            }
            
            // Update booking status
            update_post_meta($booking_id, 'mpwpb_order_status', 'cancelled');
            
            // Get order ID and update WooCommerce order if applicable
            $order_id = get_post_meta($booking_id, 'mpwpb_order_id', true);
            if ($order_id) {
                $order = wc_get_order($order_id);
                if ($order) {
                    $order->update_status('cancelled', esc_html__('Booking cancelled by customer', 'service-booking-manager'));
                }
            }
            
            wp_send_json_success(array(
                'message' => esc_html__('Booking cancelled successfully', 'service-booking-manager')
            ));
        }

        /**
         * AJAX handler for rescheduling a booking
         */
        public function reschedule_booking() {
            // Verify nonce
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mpwpb_dashboard_nonce')) {
                wp_send_json_error(array('message' => esc_html__('Security check failed', 'service-booking-manager')));
            }
            
            $booking_id = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : 0;
            $new_date = isset($_POST['new_date']) ? sanitize_text_field($_POST['new_date']) : '';
            $new_time = isset($_POST['new_time']) ? sanitize_text_field($_POST['new_time']) : '';
            
            if (!$booking_id || !$new_date || !$new_time) {
                wp_send_json_error(array('message' => esc_html__('Missing required fields', 'service-booking-manager')));
            }
            
            // Check if user owns this booking
            $user_id = get_current_user_id();
            $booking_user_id = get_post_meta($booking_id, 'mpwpb_user_id', true);
            
            if ($user_id != $booking_user_id) {
                wp_send_json_error(array('message' => esc_html__('You do not have permission to reschedule this booking', 'service-booking-manager')));
            }
            
            // Format new date and time
            $new_datetime = $new_date . ' ' . $new_time;
            
            // Update booking date
            update_post_meta($booking_id, 'mpwpb_date', $new_datetime);
            
            // Add note to order if applicable
            $order_id = get_post_meta($booking_id, 'mpwpb_order_id', true);
            if ($order_id) {
                $order = wc_get_order($order_id);
                if ($order) {
                    $order->add_order_note(
                        sprintf(
                            esc_html__('Booking rescheduled by customer. New date/time: %s', 'service-booking-manager'),
                            MPWPB_Global_Function::date_format($new_datetime) . ' ' . MPWPB_Global_Function::date_format($new_datetime, 'time')
                        )
                    );
                }
            }
            
            wp_send_json_success(array(
                'message' => esc_html__('Booking rescheduled successfully', 'service-booking-manager')
            ));
        }

        /**
         * AJAX handler for updating user profile
         */
        public function update_user_profile() {
            // Verify nonce
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mpwpb_dashboard_nonce')) {
                wp_send_json_error(array('message' => esc_html__('Security check failed', 'service-booking-manager')));
            }
            
            $user_id = get_current_user_id();
            
            // Update user data
            $userdata = array(
                'ID' => $user_id,
                'first_name' => isset($_POST['first_name']) ? sanitize_text_field($_POST['first_name']) : '',
                'last_name' => isset($_POST['last_name']) ? sanitize_text_field($_POST['last_name']) : '',
                'user_email' => isset($_POST['email']) ? sanitize_email($_POST['email']) : ''
            );
            
            // Update password if provided
            if (!empty($_POST['password'])) {
                $userdata['user_pass'] = $_POST['password'];
            }
            
            $user_id = wp_update_user($userdata);
            
            if (is_wp_error($user_id)) {
                wp_send_json_error(array('message' => $user_id->get_error_message()));
            }
            
            // Update user meta
            update_user_meta($user_id, 'billing_phone', isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '');
            update_user_meta($user_id, 'billing_address_1', isset($_POST['address']) ? sanitize_text_field($_POST['address']) : '');
            update_user_meta($user_id, 'billing_city', isset($_POST['city']) ? sanitize_text_field($_POST['city']) : '');
            update_user_meta($user_id, 'mpwpb_service_preferences', isset($_POST['preferences']) ? sanitize_textarea_field($_POST['preferences']) : '');
            
            wp_send_json_success(array(
                'message' => esc_html__('Profile updated successfully', 'service-booking-manager')
            ));
        }
    }
    
    new MPWPB_User_Dashboard();
}