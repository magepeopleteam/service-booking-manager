<?php
/*
* @Author 		rubelcuet10@gmail.com
* Copyright: 	mage-people.com
*/
if (!defined('ABSPATH')) {
    die;
} // Cannot access pages directly.
if (!class_exists('MPWPB_Staff_DashBoard')) {
    class MPWPB_Staff_DashBoard{
        public function __construct(){

            add_action( 'woocommerce_account_dashboard', [ $this, 'mpwpb_my_dashboard_content'] );

            // Deliberately distinct from Frontend/MPWPB_User_Dashboard.php's
            // wp_ajax_mpwpb_cancel_booking/reschedule_booking -- both classes
            // used to register the exact same action names, so whichever hook
            // ran first silently ate the other's request.
            add_action('wp_ajax_mpwpb_staff_cancel_booking', array($this, 'cancel_booking'));
            add_action('wp_ajax_mpwpb_staff_reschedule_booking', array($this, 'reschedule_booking'));
            add_action('wp_ajax_mpwpb_update_user_profile', array($this, 'update_user_profile'));

            add_action('wp_ajax_mpwpb_save_specific_schedule', array($this, 'mpwpb_save_specific_schedule'));
        }


        function mpwpb_save_specific_schedule() {
            if (!is_user_logged_in()) {
                wp_send_json_error('User not logged in');
            }
            if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'mpwpb_dashboard_nonce')) {
                wp_send_json_error('Security check failed');
            }
            $user_id = get_current_user_id();
            $current_user = wp_get_current_user();
            $is_staff = in_array('mpwpb_staff', (array) $current_user->roles, true);
            if (!$is_staff && !current_user_can('create_users') && !current_user_can('manage_options')) {
                wp_send_json_error('Unauthorized request');
            }

            $off_dates_json = isset($_POST['offDates_str']) ? wp_unslash(sanitize_text_field($_POST['offDates_str'])) : '';
            $off_days = isset($_POST['offDays']) ? sanitize_text_field(wp_unslash($_POST['offDays'])) : '';
            $off_dates = json_decode($off_dates_json, true);
            if (!$user_id) {
                wp_send_json_error('User not logged in');
            }
            if (!isset($_POST['schedule']) || !is_array($_POST['schedule'])) {
                wp_send_json_error('Invalid or missing schedule data');
            }
            $raw_schedule = $_POST['schedule'];
            foreach ($raw_schedule as $day => $times ) {
                $start = isset($times['start_time']) ? sanitize_text_field($times['start_time']) : '';
                $end = isset($times['end_time']) ? sanitize_text_field($times['end_time']) : '';
                $break_start = isset($times['start_break_time']) ? sanitize_text_field($times['start_break_time']) : '';
                $break_end = isset($times['end_break_time']) ? sanitize_text_field($times['end_break_time']) : '';
                    if( $break_start !== '' &&  $break_end === '' ){
                        $break_start = '';
                    } else if( $break_start === '' &&  $break_end !== '' ){
                        $break_end = '';
                    }
                    if( $start !== '' &&  $end === '' ){
                        $start = '';
                    } else if( $start === '' &&  $end !== '' ){
                        $end = '';
                    }
                    $start_key = 'mpwpb_' . $day . '_start_time';
                    $end_key = 'mpwpb_' . $day . '_end_time';
                    $start_default_key = 'mpwpb_' . $day . '_start_break_time';
                    $end_default_key = 'mpwpb_' . $day . '_end_break_time';

                    update_user_meta( $user_id, $start_key, $start );
                    update_user_meta( $user_id, $end_key, $end );
                    update_user_meta( $user_id, $start_default_key, $break_start );
                    update_user_meta( $user_id, $end_default_key, $break_end );
                }
                update_user_meta( $user_id, 'mpwpb_off_days', $off_days );
                update_user_meta( $user_id, 'mpwpb_off_dates', $off_dates );

            wp_send_json_success('Schedule saved successfully');
        }


        function mpwpb_my_dashboard_content() {
            $current_user = wp_get_current_user();
//            if ( in_array('mpwpb_staff', $current_user->roles) ) {
                $this->mpwpb_show_staff_info_on_dashboard();
//            }
        }

        function mpwpb_show_staff_info_on_dashboard() {
            $current_user = wp_get_current_user();
            echo $this->user_dashboard($current_user);
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


        public static function staff_off_on_day_settings($user_id = '') {
            $date_type = $user_id ? get_user_meta($user_id, 'date_type') : [];
            $date_type = sizeof($date_type) > 0 ? current($date_type) : 'repeated';
            $off_days = $user_id ? get_user_meta($user_id, 'mpwpb_off_days') : [];
            $off_days = sizeof($off_days) > 0 ? current($off_days) : '';
            $days = MPWPB_Global_Function::week_day();
            $off_day_array = explode(',', $off_days);
            ob_start()
            ?>
            <div class="mpPanel mT_xs <?php echo esc_attr($date_type == 'repeated' ? 'mActive' : ''); ?>" data-collapse="#mp_repeated">
                <div class="mpPanelHeader _bgColor_6" data-collapse-target="#mpwpb_staff_off_on_day_setting" data-open-icon="fa-minus" data-close-icon="fa-plus">
                    <h6 class="_textBlack">
                        <span data-icon class="fas fa-plus mR_xs"></span><?php esc_html_e('Off Days & Dates Settings', 'service-booking-manager'); ?>
                    </h6>
                </div>
                <div class="mpPanelBody" data-collapse="#mpwpb_staff_off_on_day_setting">
                    <div class="dFlex">
                        <span class="_fs_label_w_200"><?php esc_html_e('Off Day', 'service-booking-manager'); ?></span>
                        <div class="groupCheckBox flexWrap">
                            <input type="hidden" name="mpwpb_off_days" value="<?php echo esc_attr($off_days); ?>"/>
                            <?php foreach ($days as $key => $day) { ?>
                                <label class="customCheckboxLabel _w_200">
                                    <input type="checkbox" <?php echo esc_attr(in_array($key, $off_day_array) ? 'checked' : ''); ?> data-checked="<?php echo esc_attr($key); ?>"/>
                                    <span class="customCheckbox"><?php echo esc_html($day); ?></span>
                                </label>
                            <?php } ?>
                        </div>
                    </div>
                    <div class="divider"></div>
                    <div class="dFlex">
                        <span class="_fs_label_w_200"><?php esc_html_e('Off Dates', 'service-booking-manager'); ?></span>
                        <div class="mp_settings_area">
                            <div class="mp_item_insert mp_sortable_area">
                                <?php
                                $off_day_lists = $user_id ? get_user_meta($user_id, 'mpwpb_off_dates') : [];
                                $off_day_lists = sizeof($off_day_lists) > 0 ? current($off_day_lists) : [];
                                if (sizeof($off_day_lists) > 0) {
                                    foreach ($off_day_lists as $off_day) {
                                        if ($off_day) {
                                            MPWPB_Date_Time_Settings::particular_date_item('mpwpb_off_dates[]', $off_day);
                                        }
                                    }
                                }
                                ?>

                                <div class="mpwpb_hidden_content" style="display: none">
                                    <div class="mpwpb_hidden_item">
                                        <?php MPWPB_Date_Time_Settings::particular_date_item('mpwpb_off_dates[]'); ?>
                                    </div>
                                </div>

                            </div>
                            <?php MPWPB_Custom_Layout::add_new_button(esc_html__('Add New Off date', 'service-booking-manager')); ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php
            return ob_get_clean();
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
            $approve_holiday_modify = get_user_meta($user_id, 'mpwpb_staff_modify_holiday', true);
            $tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'bookings';

            // Dashboard tabs
            // Dashboard tabs
            $tabs = array(
                'bookings' => esc_html__('Users Bookings', 'service-booking-manager'),
                'upcoming' => esc_html__('Upcoming Appointments', 'service-booking-manager'),
                'reviews' => esc_html__('My Reviews', 'service-booking-manager'),
//                'profile' => esc_html__('My Profile', 'service-booking-manager'),
                'holiday' => esc_html__('Manage Holidays', 'service-booking-manager')
            );
            if( $approve_holiday_modify === 'no' ){
                unset($tabs['holiday']);
            }
            ?>
                <div class="wrap">
                    <div class="mpwpb_style mpwpb_staff_page">
                        <div class="_dLayout_dShadow_1">
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
                                        case 'holiday':
                                            if( $approve_holiday_modify === 'yes' ){
                                                // MPWPB_Staffs (Admin/MPWPB_Staffs.php) is dead code, superseded
                                                // by MPWPB_Staff_Members (Admin/MPWPB_Staff_Members.php, the one
                                                // actually require_once'd from Admin/MPWPB_Admin.php) -- the old
                                                // class name here was never valid and this tab fataled whenever
                                                // an approved staff member actually reached it.
                                                $mpwpb_staff_members = new MPWPB_Staff_Members();
                                                wp_kses_post( $mpwpb_staff_members->schedule_settings( $user_id ) );
                                                echo self::staff_off_on_day_settings( $user_id );
                                            ?>
                                            <button type="button" id="saveScheduleBtn" class="mpmw_staff_button_primary">
                                                <i class="fas fa-save"></i> Save Schedule
                                            </button>
                                            <?php
                                            }
                                            break;
                                        default:
                                            $this->booking_history($user_id);
                                            break;
                                    }

                                    ?>
                                </div>
                            </div>
                        </div>
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
                <h3><?php esc_html_e('Users Booking History', 'service-booking-manager'); ?></h3>
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
                                <?php if ($this->can_cancel_booking($booking) && 1 === 2 ) : ?>
                                    <button class="mpwpb-btn mpwpb-cancel-btn" data-id="<?php echo esc_attr($booking->ID); ?>">
                                        <?php esc_html_e('Cancel', 'service-booking-manager'); ?>
                                    </button>
                                <?php endif; ?>

                                <?php if ($this->can_reschedule_booking($booking) && 1 === 2 ) : ?>
                                    <button class="mpwpb-btn mpwpb-reschedule-btn" data-id="<?php echo esc_attr($booking->ID); ?>" data-service="<?php echo esc_attr($booking->mpwpb_id); ?>">
                                        <?php esc_html_e('Reschedule', 'service-booking-manager'); ?>
                                    </button>
                                <?php endif; ?>

                                <!--<a href="<?php /*echo esc_url(get_permalink($booking->mpwpb_id)); */?>" class="mpwpb-btn mpwpb-view-btn">
                                    <?php /*esc_html_e('View Service', 'service-booking-manager'); */?>
                                </a>-->

                                <div class="mpwpb-service-wrapper">
                                    <span class="mpwpb-btn mpwpb-view-btn mpwpb_view_selected_service_staff">
                                    <?php esc_html_e('View Service', 'service-booking-manager'); ?>
                                </span>

                                    <?php
                                    $mpwpb_services = $booking->mpwpb_service;
                                    if (!empty($mpwpb_services)) :
                                        echo '<div class="mpwpb-service-staff_card" style="display: none;">';
                                        foreach ($mpwpb_services as $service) :
                                            if (!empty($service['name'])) : ?>
                                                <div class="mpwpb-service-card">
                                                    <h3 class="mpwpb-service-title"><?php echo esc_html($service['name']); ?></h3>
                                                </div>
                                            <?php endif;
                                        endforeach;
                                        echo '</div>';
                                    else :
                                        echo '<p>'. esc_html_e('No services found.', 'service-booking-manager').'</p>';
                                    endif;
                                    ?>
                                </div>

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
        private function get_user_bookings_old($user_id) {
            global $wpdb;

            $bookings = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}posts p
                    JOIN {$wpdb->prefix}postmeta pm ON p.ID = pm.post_id
                    WHERE p.post_type = 'mpwpb_booking'
                    AND pm.meta_key = 'mpwpb_staff_term_id'
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

        private function get_user_bookings($user_id) {
            $args = array(
                'post_type'      => 'mpwpb_booking',
                'posts_per_page' => -1,
                'orderby'        => 'date',
                'order'          => 'DESC',
                'meta_query'     => array(
                    'relation' => 'AND',
                    array(
                        'key'   => 'mpwpb_staff_term_id',
                        'value' => $user_id,
                        'compare' => '=',
                    ),
                    array(
                        'key' => 'mpwpb_backend_order',
                        'value' => 'yes',
                        'compare' => '!='
                    )
                ),
            );

            $query = new WP_Query($args);

            if (!$query->have_posts()) {
                return array();
            }

            $formatted_bookings = array();

            foreach ($query->posts as $post) {
                $booking_data = new stdClass();
                $booking_data->ID = $post->ID;

                // Get booking meta data
                $meta = get_post_meta($post->ID);
                $booking_data->mpwpb_id           = isset($meta['mpwpb_id'][0]) ? $meta['mpwpb_id'][0] : '';
                $booking_data->mpwpb_date         = isset($meta['mpwpb_date'][0]) ? $meta['mpwpb_date'][0] : '';
                $booking_data->mpwpb_order_id     = isset($meta['mpwpb_order_id'][0]) ? $meta['mpwpb_order_id'][0] : '';
                $booking_data->mpwpb_order_status = isset($meta['mpwpb_order_status'][0]) ? $meta['mpwpb_order_status'][0] : '';
                $booking_data->mpwpb_service = isset($meta['mpwpb_service'][0]) ? maybe_unserialize($meta['mpwpb_service'][0]) : array();
                if (!is_array($booking_data->mpwpb_service)) {
                    $booking_data->mpwpb_service = array();
                }
                $formatted_bookings[] = $booking_data;
            }

            wp_reset_postdata();

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
            return $booking->mpwpb_order_status != 'cancelled'
                && MPWPB_Booking_History::is_within_lead_time($booking->mpwpb_date, 'cancel');
        }

        /**
         * Check if a booking can be rescheduled
         */
        private function can_reschedule_booking($booking) {
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

            // Check if this staff member owns this booking
            $user_id = get_current_user_id();
            $booking_staff_id = get_post_meta($booking_id, 'mpwpb_staff_term_id', true);

            if ($user_id != $booking_staff_id) {
                wp_send_json_error(array('message' => esc_html__('You do not have permission to cancel this booking', 'service-booking-manager')));
            }

            $result = MPWPB_Booking_History::cancel($booking_id, __('Booking cancelled by staff.', 'service-booking-manager'));
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

            // Check if this staff member owns this booking -- matches
            // cancel_booking() above (previously checked mpwpb_user_id here,
            // which meant a staff member's own reschedule was always rejected).
            $user_id = get_current_user_id();
            $booking_staff_id = get_post_meta($booking_id, 'mpwpb_staff_term_id', true);

            if ($user_id != $booking_staff_id) {
                wp_send_json_error(array('message' => esc_html__('You do not have permission to reschedule this booking', 'service-booking-manager')));
            }

            $new_datetime = $new_date . ' ' . $new_time;
            $note = sprintf(
                /* translators: %s: new booking date/time */
                __('Booking rescheduled by staff. New date/time: %s', 'service-booking-manager'),
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

        /**
         * Whether the given (or current) user has the staff role -- shared
         * gate for the "My Service"/"My Appointment" WooCommerce My Account
         * menu items (Frontend/MPWPB_Wc_Staff_Account.php) and their Custom
         * Payment dashboard tab counterparts (Frontend/MPWPB_Custom_Payment_My_Account.php).
         */
        public static function is_staff($user_id = null): bool {
            $user_id = $user_id ?: get_current_user_id();
            $user = get_userdata($user_id);
            return $user && in_array('mpwpb_staff', (array) $user->roles, true);
        }

        /**
         * @return WP_Post[] Services (mpwpb_item) this staff member is
         * assigned to, via mpwpb_selected_staff_ids (an array of ints saved
         * by Admin/settings/Staff_Member.php::save_selected_staff_meta() --
         * array_map('intval', ...), so it's serialized as e.g.
         * a:2:{i:0;i:12;i:1;i:20;} -- matching "i:12;" (not a quoted "12",
         * that pattern is for serialized strings) is what actually finds it.
         */
        public static function get_staff_services($user_id): array {
            return get_posts(array(
                'post_type' => MPWPB_Function::get_cpt(),
                'post_status' => 'publish',
                'posts_per_page' => -1,
                'meta_query' => array(
                    array(
                        'key' => 'mpwpb_selected_staff_ids',
                        'value' => 'i:' . (int) $user_id . ';',
                        'compare' => 'LIKE',
                    ),
                ),
            ));
        }

        /**
         * Bookings assigned to this staff member (mpwpb_staff_term_id --
         * despite the name this is a WP user ID, see get_user_bookings()
         * above), optionally restricted to a date range. Comparison uses
         * only the first date segment, same "first slot only" rule
         * Frontend/MPWPB_Native_Checkout.php::render_booking_recap() uses
         * for recurring/multi-date bookings.
         *
         * @param string $start_date 'Y-m-d', or '' for no lower bound.
         * @param string $end_date   'Y-m-d', or '' for no upper bound.
         * @return object[]
         */
        public static function get_staff_appointments($user_id, string $start_date = '', string $end_date = ''): array {
            $query = new WP_Query(array(
                'post_type' => 'mpwpb_booking',
                'posts_per_page' => -1,
                'orderby' => 'date',
                'order' => 'DESC',
                'meta_query' => array(
                    'relation' => 'AND',
                    array('key' => 'mpwpb_staff_term_id', 'value' => $user_id, 'compare' => '='),
                    array('key' => 'mpwpb_backend_order', 'value' => 'yes', 'compare' => '!='),
                ),
            ));

            $start_ts = $start_date ? strtotime($start_date) : false;
            $end_ts = $end_date ? strtotime($end_date . ' 23:59:59') : false;

            $bookings = array();
            foreach ($query->posts as $post) {
                $meta = get_post_meta($post->ID);
                $date_raw = $meta['mpwpb_date'][0] ?? '';
                $first_segment = is_string($date_raw) ? trim(explode(',', $date_raw)[0]) : '';
                $booking_time = $first_segment ? strtotime($first_segment) : false;

                if ($start_ts && (!$booking_time || $booking_time < $start_ts)) {
                    continue;
                }
                if ($end_ts && (!$booking_time || $booking_time > $end_ts)) {
                    continue;
                }

                $booking_data = new stdClass();
                $booking_data->ID = $post->ID;
                $booking_data->mpwpb_id = $meta['mpwpb_id'][0] ?? '';
                $booking_data->mpwpb_date = $date_raw;
                $booking_data->mpwpb_order_status = $meta['mpwpb_order_status'][0] ?? '';
                $booking_data->mpwpb_billing_name = $meta['mpwpb_billing_name'][0] ?? '';
                $bookings[] = $booking_data;
            }
            wp_reset_postdata();

            return $bookings;
        }

        /**
         * "My Service" tab content -- services this staff member is
         * assigned to. Shared by the WooCommerce My Account endpoint
         * (Frontend/MPWPB_Wc_Staff_Account.php) and the Custom Payment
         * dashboard (Frontend/MPWPB_Custom_Payment_My_Account.php).
         */
        public static function render_my_service_tab($user_id): void {
            $services = self::get_staff_services($user_id);
            ?>
            <div class="mpwpb-staff-services">
                <h3><?php esc_html_e('My Service', 'service-booking-manager'); ?></h3>
                <?php if (empty($services)) : ?>
                    <div class="mpwpb-no-bookings"><?php esc_html_e("You haven't been assigned to any services yet.", 'service-booking-manager'); ?></div>
                <?php else : ?>
                    <table class="mpwpb-bookings-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Service', 'service-booking-manager'); ?></th>
                                <th><?php esc_html_e('Category', 'service-booking-manager'); ?></th>
                                <th><?php esc_html_e('Status', 'service-booking-manager'); ?></th>
                                <th><?php esc_html_e('Actions', 'service-booking-manager'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($services as $service) :
                                $terms = get_the_terms($service->ID, 'mpwpb_category');
                                $category = ($terms && !is_wp_error($terms)) ? implode(', ', wp_list_pluck($terms, 'name')) : '—';
                                ?>
                                <tr>
                                    <td><?php echo esc_html(get_the_title($service)); ?></td>
                                    <td><?php echo esc_html($category); ?></td>
                                    <td><span class="mpwpb-status mpwpb-status-<?php echo esc_attr($service->post_status); ?>"><?php echo esc_html(ucfirst($service->post_status)); ?></span></td>
                                    <td><a class="mpwpb-btn mpwpb-view-btn" href="<?php echo esc_url(get_permalink($service)); ?>"><?php esc_html_e('View Service', 'service-booking-manager'); ?></a></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            <?php
        }

        /**
         * "My Appointment" tab content -- this staff member's bookings,
         * with a From/To date-range filter (GET params so the URL stays
         * shareable/bookmarkable, matching the rest of the plugin's
         * account-area conventions). Shared the same way as
         * render_my_service_tab() above.
         */
        public static function render_my_appointment_tab($user_id): void {
            $start_date = isset($_GET['mpwpb_appt_from']) ? sanitize_text_field(wp_unslash($_GET['mpwpb_appt_from'])) : '';
            $end_date = isset($_GET['mpwpb_appt_to']) ? sanitize_text_field(wp_unslash($_GET['mpwpb_appt_to'])) : '';
            $bookings = self::get_staff_appointments($user_id, $start_date, $end_date);
            ?>
            <div class="mpwpb-staff-appointments">
                <h3><?php esc_html_e('My Appointment', 'service-booking-manager'); ?></h3>
                <form method="get" class="mpwpb-appt-filter">
                    <?php foreach ($_GET as $key => $value) :
                        if (in_array($key, array('mpwpb_appt_from', 'mpwpb_appt_to'), true) || is_array($value)) {
                            continue;
                        }
                        ?>
                        <input type="hidden" name="<?php echo esc_attr($key); ?>" value="<?php echo esc_attr(wp_unslash($value)); ?>"/>
                    <?php endforeach; ?>
                    <label>
                        <?php esc_html_e('From', 'service-booking-manager'); ?>
                        <input type="date" name="mpwpb_appt_from" value="<?php echo esc_attr($start_date); ?>"/>
                    </label>
                    <label>
                        <?php esc_html_e('To', 'service-booking-manager'); ?>
                        <input type="date" name="mpwpb_appt_to" value="<?php echo esc_attr($end_date); ?>"/>
                    </label>
                    <button type="submit" class="mpwpb-btn mpwpb-submit-btn"><?php esc_html_e('Filter', 'service-booking-manager'); ?></button>
                    <?php if ($start_date || $end_date) : ?>
                        <a class="mpwpb-btn" href="<?php echo esc_url(remove_query_arg(array('mpwpb_appt_from', 'mpwpb_appt_to'))); ?>"><?php esc_html_e('Clear', 'service-booking-manager'); ?></a>
                    <?php endif; ?>
                </form>
                <?php if (empty($bookings)) : ?>
                    <div class="mpwpb-no-bookings"><?php esc_html_e('No appointments found for the selected range.', 'service-booking-manager'); ?></div>
                <?php else : ?>
                    <table class="mpwpb-bookings-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Booking ID', 'service-booking-manager'); ?></th>
                                <th><?php esc_html_e('Service', 'service-booking-manager'); ?></th>
                                <th><?php esc_html_e('Customer', 'service-booking-manager'); ?></th>
                                <th><?php esc_html_e('Date', 'service-booking-manager'); ?></th>
                                <th><?php esc_html_e('Time', 'service-booking-manager'); ?></th>
                                <th><?php esc_html_e('Status', 'service-booking-manager'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings as $booking) : ?>
                                <tr>
                                    <td>#<?php echo esc_html($booking->ID); ?></td>
                                    <td><?php echo esc_html(get_the_title($booking->mpwpb_id)); ?></td>
                                    <td><?php echo esc_html($booking->mpwpb_billing_name ?: '—'); ?></td>
                                    <td><?php echo esc_html(MPWPB_Global_Function::date_format($booking->mpwpb_date)); ?></td>
                                    <td><?php echo esc_html(MPWPB_Global_Function::date_format($booking->mpwpb_date, 'time')); ?></td>
                                    <td><span class="mpwpb-status mpwpb-status-<?php echo esc_attr(strtolower($booking->mpwpb_order_status)); ?>"><?php echo esc_html(ucfirst($booking->mpwpb_order_status)); ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            <?php
        }

        /**
         * Whether this staff member is allowed to change their own
         * schedule/availability -- the same admin-controlled per-staff flag
         * (set on the Staff Members admin screen, Admin/MPWPB_Staffs.php)
         * that already gates the older embedded "Manage Holidays" tab
         * (user_dashboard()'s 'holiday' case above). "My Schedule" respects
         * the same flag rather than introducing a second, conflicting
         * permission model.
         */
        public static function can_modify_own_schedule($user_id): bool {
            return get_user_meta($user_id, 'mpwpb_staff_modify_holiday', true) === 'yes';
        }

        /**
         * "My Schedule" tab content -- lets a staff member view/edit their
         * own weekly hours, break times, and off days/dates. Reuses the
         * exact same rendering, JS (#saveScheduleBtn handler in
         * mpwpb_user_dashboard.js), and AJAX action
         * (wp_ajax_mpwpb_save_specific_schedule, handled by
         * mpwpb_save_specific_schedule() above, which already authorizes
         * staff themselves, not just admins) as the older "Manage Holidays"
         * tab -- same data, same save path, just a more prominent/dedicated
         * entry point. Shared the same way as render_my_service_tab()/
         * render_my_appointment_tab() above.
         */
        public static function render_my_schedule_tab($user_id): void {
            if (!self::can_modify_own_schedule($user_id)) {
                echo '<div class="mpwpb-message info">' . esc_html__("Your schedule can only be changed by an administrator. Please contact them if you'd like to update your availability.", 'service-booking-manager') . '</div>';
                return;
            }
            ?>
            <div class="mpwpb-staff-schedule">
                <h3><?php esc_html_e('My Schedule', 'service-booking-manager'); ?></h3>
                <p class="mpwpb-schedule-intro"><?php esc_html_e('Set your weekly working hours, break times, and the days/dates you are unavailable. Saving here updates the same availability used to calculate your bookable time slots.', 'service-booking-manager'); ?></p>
                <?php
                $mpwpb_staff_members = new MPWPB_Staff_Members();
                $mpwpb_staff_members->schedule_settings($user_id);
                echo self::staff_off_on_day_settings($user_id);
                ?>
                <button type="button" id="saveScheduleBtn" class="mpwpb-btn mpwpb-submit-btn">
                    <i class="fas fa-save"></i> <?php esc_html_e('Save Schedule', 'service-booking-manager'); ?>
                </button>
            </div>
            <?php
        }

    }

    new MPWPB_Staff_DashBoard();
}
