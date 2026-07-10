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
            // Names deliberately avoid mpwpb_get_available_dates/times -- the
            // Pro add-on's Admin/MPWPB_Backend_Order_Management.php already
            // registers wp_ajax_mpwpb_get_available_times for its own backend
            // order-creation screen; reusing that name here would collide.
            add_action('wp_ajax_mpwpb_dashboard_get_available_dates', array($this, 'ajax_get_available_dates'));
            add_action('wp_ajax_mpwpb_dashboard_get_available_times', array($this, 'ajax_get_available_times'));
            add_action('wp_ajax_mpwpb_update_user_profile', array($this, 'update_user_profile'));
            add_action('wp_ajax_mpwpb_submit_gdpr_request', array($this, 'submit_gdpr_request'));
            add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        }

        /**
         * Enqueue necessary scripts and styles for the dashboard
         */
        public function enqueue_scripts() {
            wp_enqueue_style('mpwpb-user-dashboard', MPWPB_PLUGIN_URL . '/assets/frontend/mpwpb_user_dashboard.css', array(), time());
            wp_enqueue_script('mpwpb-user-dashboard', MPWPB_PLUGIN_URL . '/assets/frontend/mpwpb_user_dashboard.js', array('jquery'), time(), true);
            // 'staff' picks the mpwpb_staff_* AJAX action names (Admin/MPWPB_Staff_DashBoard.php)
            // instead of the customer ones below -- both used to register the
            // same action names and silently collided.
            $is_staff = is_user_logged_in() && in_array('mpwpb_staff', (array) wp_get_current_user()->roles, true);
            wp_localize_script('mpwpb-user-dashboard', 'mpwpb_dashboard', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('mpwpb_dashboard_nonce'),
                'context' => $is_staff ? 'staff' : 'customer',
                'cancel_confirm' => __('Are you sure you want to cancel this booking?', 'service-booking-manager'),
                'reschedule_confirm' => __('Are you sure you want to reschedule this booking?', 'service-booking-manager')
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
if (MPWPB_Global_Function::is_gdpr_enabled()) {
    $tabs['privacy'] = esc_html__('Privacy & Data', 'service-booking-manager');
}

            
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
                            self::user_profile($user_id);
                            break;
                        case 'privacy':
                            if (MPWPB_Global_Function::is_gdpr_enabled()) {
                                self::privacy_data_tab($user_id);
                            }
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
                                    <?php self::render_booking_actions($booking->ID, $booking->mpwpb_id, false); ?>

                                    <?php
                                    // Creates an unrelated NEW booking (pre-filled from this one, editable
                                    // before payment) rather than modifying this one, so unlike Cancel/
                                    // Reschedule it's available regardless of status or the lead-time cutoff.
                                    $reorder_url = self::get_reorder_url($booking->ID, $booking->mpwpb_id);
                                    ?>
                                    <a href="<?php echo esc_url($reorder_url); ?>" class="mpwpb-btn mpwpb-reorder-btn">
                                        <?php esc_html_e('Reorder', 'service-booking-manager'); ?>
                                    </a>

                                    <a href="<?php echo esc_url(get_permalink($booking->mpwpb_id)); ?>" class="mpwpb-btn mpwpb-view-btn">
                                        <?php esc_html_e('View Service', 'service-booking-manager'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php self::render_reschedule_modal(); ?>
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
                                    <?php self::render_booking_actions($booking->ID, $booking->mpwpb_id, false); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <?php self::render_reschedule_modal(); ?>
            <?php
        }

        /**
         * Cancel/Reschedule button pair for one booking -- shared by
         * booking_history(), upcoming_appointments(), and
         * Frontend/MPWPB_Wc_Account_Order_Actions.php's WooCommerce View
         * Order integration, so all three stay in sync instead of drifting
         * (duplicated inline markup is exactly what caused this dashboard's
         * cancel/reschedule AJAX bug in the first place).
         *
         * @param bool $render_modal Pass true when this is the only booking on
         *             the page (e.g. a single WC order's View Order page).
         *             Table/grid callers render N buttons but only ONE modal
         *             (fixed element IDs), so they pass false here and call
         *             render_reschedule_modal() once after their loop instead.
         */
        /**
         * URL for "Reorder" -- lands on the booking's service page with
         * ?mpwpb_reorder={booking_id}, resolved server-side by
         * MPWPB_Static_Template::get_reorder_prefill(). Shared by the
         * dashboard's own booking_history() and
         * Frontend/MPWPB_Wc_Account_Order_Actions.php's WooCommerce My
         * Account integration so both stay in sync.
         */
        public static function get_reorder_url($booking_id, $service_id) {
            return add_query_arg('mpwpb_reorder', $booking_id, get_permalink($service_id));
        }

        /**
         * Finds the single booking created for an order (WC or native --
         * both write mpwpb_order_id via MPWPB_Woocommerce::create_bookings_from_data()).
         * This plugin's checkout model is one booking per order (single hidden
         * product / single-item cart at checkout) -- if more than one is ever
         * found, no-op defensively rather than guess which booking to act on.
         * Shared by Frontend/MPWPB_Wc_Account_Order_Actions.php (WooCommerce
         * My Account) and Frontend/MPWPB_Custom_Payment_My_Account.php
         * (Custom Payment dashboard) so both stay in sync.
         */
        public static function get_booking_for_order($order_id) {
            $ids = get_posts(array(
                'post_type' => 'mpwpb_booking',
                'posts_per_page' => 2,
                'fields' => 'ids',
                'meta_key' => 'mpwpb_order_id',
                'meta_value' => $order_id,
            ));
            return count($ids) === 1 ? (int) $ids[0] : 0;
        }

        public static function render_booking_actions($booking_id, $service_id, $render_modal = false) {
            $booking = (object) array(
                'mpwpb_date' => get_post_meta($booking_id, 'mpwpb_date', true),
                'mpwpb_order_status' => get_post_meta($booking_id, 'mpwpb_order_status', true),
            );
            // Shared by both My Account surfaces (WooCommerce's own Orders page
            // via Frontend/MPWPB_Wc_Account_Order_Actions.php, and the Custom
            // Payment dashboard via Frontend/MPWPB_Custom_Payment_My_Account.php)
            // so paid/due visibility and the Pay Balance button only need to
            // exist in one place.
            self::render_payment_status($booking_id);
            if (self::can_cancel_booking($booking)) : ?>
                <button class="mpwpb-btn mpwpb-cancel-btn" data-id="<?php echo esc_attr($booking_id); ?>">
                    <?php esc_html_e('Cancel', 'service-booking-manager'); ?>
                </button>
            <?php endif;
            if (self::can_reschedule_booking($booking)) : ?>
                <button class="mpwpb-btn mpwpb-reschedule-btn" data-id="<?php echo esc_attr($booking_id); ?>" data-service="<?php echo esc_attr($service_id); ?>">
                    <?php esc_html_e('Reschedule', 'service-booking-manager'); ?>
                </button>
            <?php endif;
            if ($render_modal) {
                self::render_reschedule_modal();
            }
        }

        /**
         * Paid/due amounts + "Pay Balance" link for a booking with an
         * outstanding balance (mpwpb_order_status === 'partially-paid', see
         * MPWPB_Partial_Payment::compute_display_status()) -- a no-op for any
         * booking that was paid in full, so this is safe to call
         * unconditionally from render_booking_actions() above.
         */
        public static function render_payment_status($booking_id): void {
            if (!class_exists('MPWPB_Partial_Payment')) {
                return;
            }
            $amount_due = MPWPB_Partial_Payment::get_amount_due($booking_id);
            $real_status = get_post_meta($booking_id, 'mpwpb_real_order_status', true);
            if ($amount_due <= 0 || in_array($real_status, MPWPB_Partial_Payment::TERMINAL_NON_PAYABLE_STATUSES, true)) {
                return;
            }
            $amount_paid = MPWPB_Partial_Payment::get_amount_paid($booking_id);
            ?>
            <div class="mpwpb-payment-status">
                <span class="mpwpb-payment-status-row">
                    <?php esc_html_e('Paid:', 'service-booking-manager'); ?>
                    <strong><?php echo wp_kses_post(MPWPB_Global_Function::wc_price(0, $amount_paid)); ?></strong>
                </span>
                <span class="mpwpb-payment-status-row mpwpb-payment-status-due">
                    <?php esc_html_e('Balance Due:', 'service-booking-manager'); ?>
                    <strong><?php echo wp_kses_post(MPWPB_Global_Function::wc_price(0, $amount_due)); ?></strong>
                </span>
                <a class="mpwpb-btn mpwpb-pay-balance-btn" href="<?php echo esc_url(add_query_arg('mpwpb_pay_balance', $booking_id, home_url('/'))); ?>">
                    <?php esc_html_e('Pay Balance', 'service-booking-manager'); ?>
                </a>
            </div>
            <?php
        }

        public static function render_reschedule_modal() { ?>
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
         * Display user profile management form
         */
        /** public+static so other shortcodes (e.g. Frontend/MPWPB_Custom_Payment_My_Account.php) can reuse this same account-details form instead of duplicating it. */
        public static function user_profile($user_id) {
            $user = get_userdata($user_id);
            $role_key = $user && !empty($user->roles) ? $user->roles[0] : '';
            $role_names = wp_roles()->role_names;
            $role_label = $role_key && isset($role_names[$role_key]) ? translate_user_role($role_names[$role_key]) : esc_html__('Customer', 'service-booking-manager');
            $initials = $user ? trim(mb_substr($user->first_name, 0, 1) . mb_substr($user->last_name, 0, 1)) : '';
            if (!$initials) {
                $initials = $user ? mb_substr($user->display_name, 0, 1) : '';
            }
            $total_bookings = count(get_posts(array(
                'post_type' => 'mpwpb_booking',
                'post_status' => 'any',
                'posts_per_page' => -1,
                'fields' => 'ids',
                'meta_key' => 'mpwpb_user_id',
                'meta_value' => $user_id,
            )));
            ?>
            <div class="mpwpb-user-profile mpwpb-profile-v2">
                <div class="mpwpb-profile-header">
                    <h3><?php esc_html_e('My Profile', 'service-booking-manager'); ?></h3>
                    <p class="mpwpb-profile-subtitle"><?php esc_html_e('Manage your account settings, personal details, and security preferences.', 'service-booking-manager'); ?></p>
                </div>
                <div class="mpwpb-profile-layout">
                    <div class="mpwpb-profile-main">
                        <form id="mpwpb-profile-form" class="mpwpb-profile-form">
                            <div class="mpwpb-profile-card">
                                <div class="mpwpb-profile-card-head">
                                    <span class="fas fa-user"></span>
                                    <h4><?php esc_html_e('Personal Information', 'service-booking-manager'); ?></h4>
                                </div>
                                <div class="mpwpb-profile-card-body">
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
                                            <label for="email"><?php esc_html_e('Email Address', 'service-booking-manager'); ?></label>
                                            <input type="email" id="email" name="email" value="<?php echo esc_attr($user->user_email); ?>" required>
                                        </div>
                                        <div class="mpwpb-form-group">
                                            <label for="phone"><?php esc_html_e('Phone Number', 'service-booking-manager'); ?></label>
                                            <input type="tel" id="phone" name="phone" value="<?php echo esc_attr(get_user_meta($user_id, 'billing_phone', true)); ?>" placeholder="<?php esc_attr_e('e.g., +1 (555) 234 5678', 'service-booking-manager'); ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mpwpb-profile-card">
                                <div class="mpwpb-profile-card-head">
                                    <span class="fas fa-location-dot"></span>
                                    <h4><?php esc_html_e('Address Details', 'service-booking-manager'); ?></h4>
                                </div>
                                <div class="mpwpb-profile-card-body">
                                    <div class="mpwpb-form-group mpwpb-form-group-full">
                                        <label for="address"><?php esc_html_e('Street Address', 'service-booking-manager'); ?></label>
                                        <input type="text" id="address" name="address" value="<?php echo esc_attr(get_user_meta($user_id, 'billing_address_1', true)); ?>" placeholder="<?php esc_attr_e('e.g., 123 Industrial Way', 'service-booking-manager'); ?>">
                                    </div>
                                    <div class="mpwpb-form-group mpwpb-form-group-full">
                                        <label for="city"><?php esc_html_e('City', 'service-booking-manager'); ?></label>
                                        <input type="text" id="city" name="city" value="<?php echo esc_attr(get_user_meta($user_id, 'billing_city', true)); ?>" placeholder="<?php esc_attr_e('e.g., New York', 'service-booking-manager'); ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="mpwpb-profile-card mpwpb-profile-card-security">
                                <div class="mpwpb-profile-card-head">
                                    <span class="fas fa-lock"></span>
                                    <h4><?php esc_html_e('Security', 'service-booking-manager'); ?></h4>
                                </div>
                                <div class="mpwpb-profile-card-body">
                                    <div class="mpwpb-form-row">
                                        <div class="mpwpb-form-group">
                                            <label for="password"><?php esc_html_e('New Password', 'service-booking-manager'); ?></label>
                                            <input type="password" id="password" name="password" minlength="8" autocomplete="new-password" data-lpignore="true" data-1p-ignore="true">
                                            <p class="mpwpb-field-hint"><?php esc_html_e('Leave blank to keep your current password. Must be at least 8 characters.', 'service-booking-manager'); ?></p>
                                        </div>
                                        <div class="mpwpb-form-group">
                                            <label for="password_confirm"><?php esc_html_e('Confirm New Password', 'service-booking-manager'); ?></label>
                                            <input type="password" id="password_confirm" name="password_confirm" minlength="8" autocomplete="new-password" data-lpignore="true" data-1p-ignore="true">
                                            <p class="mpwpb-field-hint"><?php esc_html_e('Passwords must match exactly.', 'service-booking-manager'); ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mpwpb-profile-card">
                                <div class="mpwpb-profile-card-head">
                                    <span class="fas fa-note-sticky"></span>
                                    <h4><?php esc_html_e('Service Preferences', 'service-booking-manager'); ?></h4>
                                </div>
                                <div class="mpwpb-profile-card-body">
                                    <div class="mpwpb-form-group mpwpb-form-group-full">
                                        <label for="preferences"><?php esc_html_e('Preferences & Notes', 'service-booking-manager'); ?></label>
                                        <textarea id="preferences" name="preferences" rows="4" placeholder="<?php esc_attr_e('Describe your preferred service intervals, communication methods, or any special instructions...', 'service-booking-manager'); ?>"><?php echo esc_textarea(get_user_meta($user_id, 'mpwpb_service_preferences', true)); ?></textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="mpwpb-profile-submit-row">
                                <button type="submit" class="mpwpb-btn mpwpb-submit-btn">
                                    <?php esc_html_e('Update Profile', 'service-booking-manager'); ?>
                                </button>
                                <div id="mpwpb-profile-message" class="mpwpb-message"></div>
                            </div>
                        </form>
                    </div>

                    <div class="mpwpb-profile-sidebar">
                        <div class="mpwpb-profile-card mpwpb-profile-summary-card">
                            <div class="mpwpb-profile-avatar"><?php echo esc_html(mb_strtoupper($initials)); ?></div>
                            <div class="mpwpb-profile-name"><?php echo esc_html($user->display_name); ?></div>
                            <div class="mpwpb-profile-role"><?php echo esc_html($role_label); ?></div>
                            <span class="mpwpb-profile-badge"><?php esc_html_e('Active', 'service-booking-manager'); ?></span>
                        </div>
                        <div class="mpwpb-profile-card">
                            <div class="mpwpb-profile-card-head">
                                <h4><?php esc_html_e('Account Summary', 'service-booking-manager'); ?></h4>
                            </div>
                            <div class="mpwpb-profile-card-body mpwpb-profile-summary-list">
                                <div class="mpwpb-profile-summary-row">
                                    <span><?php esc_html_e('Member Since', 'service-booking-manager'); ?></span>
                                    <strong><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($user->user_registered))); ?></strong>
                                </div>
                                <div class="mpwpb-profile-summary-row">
                                    <span><?php esc_html_e('Total Bookings', 'service-booking-manager'); ?></span>
                                    <strong><?php echo esc_html($total_bookings); ?></strong>
                                </div>
                            </div>
                            <a href="<?php echo esc_url(add_query_arg('tab', 'bookings')); ?>" class="mpwpb-btn mpwpb-btn-outline mpwpb-profile-sidebar-link"><?php esc_html_e('View My Bookings', 'service-booking-manager'); ?></a>
                        </div>
                    </div>
                </div>
            </div>
            <?php
        }

        /**
         * "Privacy & Data" tab: lets a logged-in customer request that their
         * data be handled per GDPR's right to erasure. Never applies the
         * request itself -- it only queues it (MPWPB_Gdpr_Requests::create_request())
         * for an admin to approve or reject from the GDPR tab on the
         * Settings screen (Admin/MPWPB_Gdpr_Requests.php::execute_deletion()). Only reachable
         * when MPWPB_Global_Function::is_gdpr_enabled().
         */
        /** public+static so other shortcodes (e.g. Frontend/MPWPB_Custom_Payment_My_Account.php) can reuse this same GDPR request form instead of duplicating it. */
        public static function privacy_data_tab($user_id) {
            $existing = class_exists('MPWPB_Gdpr_Requests') ? MPWPB_Gdpr_Requests::get_latest_request_for_user($user_id) : null;
            ?>
            <div class="mpwpb-user-profile mpwpb-gdpr-privacy">
                <h3><?php esc_html_e('Privacy & Data', 'service-booking-manager'); ?></h3>
                <p class="description"><?php esc_html_e('You can request that we delete or anonymize the personal data we hold about you. An admin reviews and approves every request before anything is changed.', 'service-booking-manager'); ?></p>

                <?php if ($existing && $existing->post_status === 'mpwpb_pending') : ?>
                    <div class="mpwpb-message info">
                        <?php esc_html_e('Your data request is pending admin review. You\'ll see an updated status here once it\'s resolved.', 'service-booking-manager'); ?>
                    </div>
                <?php else : ?>
                    <?php if ($existing && $existing->post_status === 'mpwpb_rejected') : ?>
                        <div class="mpwpb-message error">
                            <?php esc_html_e('Your previous data request was reviewed and rejected. You may submit a new request below.', 'service-booking-manager'); ?>
                        </div>
                    <?php elseif ($existing && $existing->post_status === 'mpwpb_approved') : ?>
                        <div class="mpwpb-message success">
                            <?php esc_html_e('Your previous data request was approved and applied. You may submit a new request below if needed.', 'service-booking-manager'); ?>
                        </div>
                    <?php endif; ?>
                    <form id="mpwpb-gdpr-request-form" class="mpwpb-profile-form">
                        <div class="mpwpb-form-group">
                            <label class="mpwpb-inline-choice">
                                <input type="radio" name="mpwpb_gdpr_strategy" value="keep_accounting" checked/>
                                <span><?php esc_html_e('Keep booking for accounting', 'service-booking-manager'); ?></span>
                            </label>
                            <p class="description"><?php esc_html_e('Your booking records stay (for our accounting/tax records), but we redact whichever fields you select below.', 'service-booking-manager'); ?></p>
                        </div>
                        <div class="mpwpb-form-group" id="mpwpb-gdpr-sub-options">
                            <label class="mpwpb-inline-choice"><input type="checkbox" name="mpwpb_gdpr_delete_profile" value="1"/> <span><?php esc_html_e('Delete customer profile', 'service-booking-manager'); ?></span></label>
                            <label class="mpwpb-inline-choice"><input type="checkbox" name="mpwpb_gdpr_delete_phone" value="1"/> <span><?php esc_html_e('Delete phone', 'service-booking-manager'); ?></span></label>
                            <label class="mpwpb-inline-choice"><input type="checkbox" name="mpwpb_gdpr_delete_address" value="1"/> <span><?php esc_html_e('Delete address', 'service-booking-manager'); ?></span></label>
                            <label class="mpwpb-inline-choice"><input type="checkbox" name="mpwpb_gdpr_delete_notes" value="1"/> <span><?php esc_html_e('Delete notes', 'service-booking-manager'); ?></span></label>
                        </div>
                        <div class="mpwpb-form-group">
                            <label class="mpwpb-inline-choice">
                                <input type="radio" name="mpwpb_gdpr_strategy" value="remove_everything"/>
                                <span><?php esc_html_e('Completely remove everything', 'service-booking-manager'); ?></span>
                            </label>
                            <p class="description"><?php esc_html_e('All of your booking/order records and profile data are permanently deleted. Your site login itself is not affected.', 'service-booking-manager'); ?></p>
                        </div>
                        <div class="mpwpb-form-group">
                            <button type="submit" class="mpwpb-btn mpwpb-submit-btn"><?php esc_html_e('Submit Data Request', 'service-booking-manager'); ?></button>
                            <div id="mpwpb-gdpr-request-message" class="mpwpb-message"></div>
                        </div>
                    </form>
                    <script>
                    (function ($) {
                        "use strict";
                        function toggleSubOptions() {
                            var isKeep = $('input[name="mpwpb_gdpr_strategy"]:checked').val() === 'keep_accounting';
                            $('#mpwpb-gdpr-sub-options').toggle(isKeep);
                        }
                        $(document).on('change', 'input[name="mpwpb_gdpr_strategy"]', toggleSubOptions);
                        toggleSubOptions();
                        $('#mpwpb-gdpr-request-form').on('submit', function (e) {
                            e.preventDefault();
                            var $form = $(this);
                            var $btn = $form.find('.mpwpb-submit-btn');
                            var $msg = $('#mpwpb-gdpr-request-message');
                            $btn.prop('disabled', true);
                            $msg.removeClass('error success').hide();
                            $.post(mpwpb_dashboard.ajaxurl, $form.serialize() + '&action=mpwpb_submit_gdpr_request&nonce=' + encodeURIComponent(mpwpb_dashboard.nonce))
                                .done(function (response) {
                                    if (response && response.success) {
                                        $msg.addClass('success').text(response.data.message).show();
                                        $form.find('button, input').prop('disabled', true);
                                    } else {
                                        $btn.prop('disabled', false);
                                        $msg.addClass('error').text((response && response.data && response.data.message) ? response.data.message : 'Something went wrong.').show();
                                    }
                                })
                                .fail(function () {
                                    $btn.prop('disabled', false);
                                    $msg.addClass('error').text('Request failed. Please try again.').show();
                                });
                        });
                    }(jQuery));
                    </script>
                <?php endif; ?>
            </div>
            <?php
        }

        /**
         * AJAX: customer submits a GDPR data request from the Privacy & Data
         * tab above. Only ever queues the request (MPWPB_Gdpr_Requests::create_request());
         * an admin must still approve it from the GDPR Compliance Tools page
         * before any data actually changes.
         */
        public function submit_gdpr_request() {
            if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'mpwpb_dashboard_nonce')) {
                wp_send_json_error(array('message' => esc_html__('Security check failed', 'service-booking-manager')));
            }
            if (!is_user_logged_in()) {
                wp_send_json_error(array('message' => esc_html__('You must be logged in to do this.', 'service-booking-manager')));
            }
            if (!MPWPB_Global_Function::is_gdpr_enabled() || !class_exists('MPWPB_Gdpr_Requests')) {
                wp_send_json_error(array('message' => esc_html__('This feature is not currently available.', 'service-booking-manager')));
            }
            $user_id = get_current_user_id();
            $strategy = isset($_POST['mpwpb_gdpr_strategy']) ? sanitize_key(wp_unslash($_POST['mpwpb_gdpr_strategy'])) : '';
            $sub_options = array(
                'profile' => !empty($_POST['mpwpb_gdpr_delete_profile']),
                'phone' => !empty($_POST['mpwpb_gdpr_delete_phone']),
                'address' => !empty($_POST['mpwpb_gdpr_delete_address']),
                'notes' => !empty($_POST['mpwpb_gdpr_delete_notes']),
            );
            $result = MPWPB_Gdpr_Requests::create_request($user_id, $strategy, $sub_options);
            if (is_wp_error($result)) {
                wp_send_json_error(array('message' => $result->get_error_message()));
            }
            wp_send_json_success(array(
                'message' => esc_html__('Your data request has been submitted and is pending admin approval.', 'service-booking-manager'),
            ));
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
                    LEFT JOIN {$wpdb->prefix}postmeta pm2 ON p.ID = pm2.post_id AND pm2.meta_key = 'mpwpb_backend_order'
                    WHERE p.post_type = 'mpwpb_booking'
                    AND pm.meta_key = 'mpwpb_user_id'
                    AND pm.meta_value = %d
                    AND (pm2.meta_value != 'yes' OR pm2.meta_value IS NULL)
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
        private static function can_cancel_booking($booking) {
            return $booking->mpwpb_order_status != 'cancelled'
                && MPWPB_Booking_History::is_within_lead_time($booking->mpwpb_date, 'cancel');
        }

        /**
         * Check if a booking can be rescheduled
         */
        private static function can_reschedule_booking($booking) {
            return $booking->mpwpb_order_status != 'cancelled'
                && MPWPB_Booking_History::is_within_lead_time($booking->mpwpb_date, 'reschedule');
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

            $result = MPWPB_Booking_History::cancel($booking_id, __('Booking cancelled by customer.', 'service-booking-manager'));
            if (is_wp_error($result)) {
                wp_send_json_error(array('message' => $result->get_error_message()));
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

            $new_datetime = $new_date . ' ' . $new_time;
            $note = sprintf(
                /* translators: %s: new booking date/time */
                __('Booking rescheduled by customer. New date/time: %s', 'service-booking-manager'),
                MPWPB_Global_Function::date_format($new_datetime) . ' ' . MPWPB_Global_Function::date_format($new_datetime, 'time')
            );

            $result = MPWPB_Booking_History::reschedule($booking_id, $new_datetime, $note);
            if (is_wp_error($result)) {
                wp_send_json_error(array('message' => $result->get_error_message()));
            }

            wp_send_json_success(array(
                'message' => esc_html__('Booking rescheduled successfully', 'service-booking-manager')
            ));
        }

        /**
         * AJAX: populate the reschedule modal's "Select New Date" dropdown.
         * Response shape matches what mpwpb_user_dashboard.js already expects.
         */
        public function ajax_get_available_dates() {
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mpwpb_dashboard_nonce')) {
                wp_send_json_error(array('message' => esc_html__('Security check failed', 'service-booking-manager')));
            }
            $service_id = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;
            if (!$service_id || get_post_type($service_id) !== MPWPB_Function::get_cpt()) {
                wp_send_json_error(array('message' => esc_html__('Invalid service.', 'service-booking-manager')));
            }

            $dates = MPWPB_Function::get_date($service_id);
            $out = array();
            foreach ($dates as $date) {
                $out[] = array('value' => $date, 'label' => MPWPB_Global_Function::date_format($date));
            }
            wp_send_json_success(array('dates' => $out));
        }

        /**
         * AJAX: populate the reschedule modal's "Select New Time" dropdown for
         * a chosen date, skipping slots already at full capacity.
         */
        public function ajax_get_available_times() {
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mpwpb_dashboard_nonce')) {
                wp_send_json_error(array('message' => esc_html__('Security check failed', 'service-booking-manager')));
            }
            $service_id = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;
            $date = isset($_POST['date']) ? sanitize_text_field(wp_unslash($_POST['date'])) : '';
            if (!$service_id || get_post_type($service_id) !== MPWPB_Function::get_cpt() || !$date) {
                wp_send_json_error(array('message' => esc_html__('Invalid request.', 'service-booking-manager')));
            }

            $slots = MPWPB_Function::get_time_slot($service_id, $date);
            $out = array();
            foreach ($slots as $slot) {
                if (MPWPB_Function::get_total_available($service_id, $slot) > 0) {
                    $time = date('H:i', strtotime($slot));
                    $out[] = array('value' => $time, 'label' => MPWPB_Function::format_time($time));
                }
            }
            wp_send_json_success(array('times' => $out));
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
            
            // Update password if provided -- enforced here (not just the UI
            // hint) since "Must be at least 8 characters" / "Passwords must
            // match exactly" would otherwise just be text with nothing
            // behind it.
            if (!empty($_POST['password'])) {
                $password = (string) $_POST['password'];
                $password_confirm = isset($_POST['password_confirm']) ? (string) $_POST['password_confirm'] : '';
                if (strlen($password) < 8) {
                    wp_send_json_error(array('message' => esc_html__('Password must be at least 8 characters long.', 'service-booking-manager')));
                }
                if ($password !== $password_confirm) {
                    wp_send_json_error(array('message' => esc_html__('Passwords do not match.', 'service-booking-manager')));
                }
                $userdata['user_pass'] = $password;
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