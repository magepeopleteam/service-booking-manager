<?php

/*
* @Author 		MagePeople Team
* Copyright: 	mage-people.com
*/
if (!defined('ABSPATH')) {
    die;
} // Cannot access pages directly.

if (!class_exists('MPWPB_Staff_Booking')) {
    class MPWPB_Staff_Booking
    {
        public function __construct() {
            add_action('wp_ajax_mpwpb_get_available_staff', [ $this, 'mpwpb_get_available_staff'] );
            add_action('wp_ajax_nopriv_mpwpb_get_available_staff', [ $this, 'mpwpb_get_available_staff'] );
        }


        public function mpwpb_is_staff_booked_new_1( $staff_term_id, $datetime ) {
            $args = array(
                'post_type' => 'mpwpb_booking',
                'posts_per_page' => -1,
                'meta_query' => array(
                    array('key' => 'mpwpb_staff_term_id', 'value' => $staff_term_id),
                    array('key' => 'mpwpb_date', 'value' => $datetime ),
                ),
            );

            $bookings = get_posts($args);

            return !empty($bookings);
        }

        public function mpwpb_is_staff_booked_order( $staff_term_id, $datetime ) {
            // Only existence is ever checked (!empty($bookings)) -- this used
            // to fetch every matching booking as full WP_Post objects with no
            // row limit (posts_per_page => -1) and compute found_rows on top,
            // for EACH staff member on EVERY time-slot click. That full scan
            // gets slower as real booking volume grows, which is what made
            // the "Next Staff Member" button feel like it took a while to
            // appear -- this now stops at the first match and only fetches
            // an ID, not a full post.
            $args = array(
                'post_type'      => 'mpwpb_booking',
                'posts_per_page' => 1,
                'fields'         => 'ids',
                'no_found_rows'  => true,
                'meta_query'     => array(
                    'relation' => 'AND',
                    array(
                        'key'     => 'mpwpb_staff_term_id',
                        'value'   => $staff_term_id,
                        'compare' => '='
                    ),
                    array(
                        'key'     => 'mpwpb_date',
                        'value'   => $datetime,
                        'compare' => 'LIKE'
                    ),
                    array(
                        'key' => 'mpwpb_backend_order',
                        'value' => 'yes',
                        'compare' => '!='
                    )
                ),
            );

            $bookings = get_posts( $args );

            return ! empty( $bookings );
        }


        public static function mpwpb_is_repeated_date( $start_date, $repeat_every_days, $check_date ) {
            // No start date set = no restriction. `new DateTime('')` means "right
            // now" (time included), so an empty start used to make today's own
            // midnight-based date count as "before start" and every staff member
            // without a start date was unavailable for the rest of the day.
            if (empty($start_date)) {
                return true;
            }
            $repeat_every_days = max(1, (int) $repeat_every_days);
            // Compare calendar dates only -- a time-of-day on either side would
            // otherwise shift the result for the same day.
            $start = new DateTime($start_date);
            $check = new DateTime($check_date);
            $start->setTime(0, 0, 0);
            $check->setTime(0, 0, 0);

            // If check date is before start date, it's false
            if ($check < $start) {
                return false;
            }

            // Calculate difference in days
            $diff_days = $start->diff($check)->days;

            $aa = ($diff_days % $repeat_every_days === 0);
            return  $aa;
        }

        public function mpwpb_is_staff_booked( $staff_id, $date, $time ) {

            return $this->get_date_check( $staff_id, $date, $time );
        }

        /*public static function get_date_check_old( $staff_id, $check_date, $time ) {

            $off_days_arr = [];
            $travel_type = get_user_meta( $staff_id, 'date_type', true );

            if( self::mpwpb_is_staff_available_time( $staff_id, $time, $check_date ) ){
                return true;
            }

            if ($travel_type == 'particular') {
                $particular_dates =get_user_meta( $staff_id, 'mpwpb_particular_dates', array());
                $flat_dates = call_user_func_array('array_merge', $particular_dates);
                $result = in_array($check_date, $flat_dates);
                if( $result ){
                    return true;
                }else{
                    return false;
                }
            } else if ($travel_type == 'repeated') {
                $check_day = strtolower( date('l', strtotime( $check_date ) ) );

                $start_date = get_user_meta( $staff_id, 'mpwpb_repeated_start_date', true);
                $repeat_after = (int) get_user_meta( $staff_id, 'mpwpb_repeated_after', true ) ?: 1;

                $get_off_dates = get_user_meta( $staff_id, 'mpwpb_off_dates', array() ) ? : array();
                $get_off_dates = call_user_func_array('array_merge', $get_off_dates );
                $get_off_days = get_user_meta( $staff_id, 'mpwpb_off_days',true) ? : '';
                if( $get_off_days ){
                    $off_days_arr = explode(',', $get_off_days);
                }

                if ( self::mpwpb_is_repeated_date( $start_date, $repeat_after, $check_date ) ) {

                    if( in_array( $check_date, $get_off_dates ) ){
                        return false;
                    }

                    if( in_array( $check_day, $off_days_arr ) ){
                        return false;
                    }

                    return true;
                }
            }

        }*/

        public static function get_date_check($staff_id, $check_date, $time) {
            $travel_type = get_user_meta($staff_id, 'date_type', true);

            // 1. Check availability by date and time
            /*if (self::mpwpb_is_staff_available_time($staff_id, $time, $check_date)) {
                return true;
            }*/

            // 2. Handle "particular" travel type
            if ($travel_type === 'particular') {
                $particular_dates = get_user_meta($staff_id, 'mpwpb_particular_dates', false); // Use `false` to get all values
                $flat_dates = !empty($particular_dates) ? array_values(array_filter(call_user_func_array('array_merge', $particular_dates))) : [];

                // Dates listed = only those dates. None listed = no date
                // restriction (handled below with the off days/dates checks),
                // same rule as an empty "repeat from" start date.
                if (!empty($flat_dates)) {
                    return in_array($check_date, $flat_dates);
                }
            }

            // 3. Handle "repeated" travel type
            if ($travel_type === 'repeated') {
                $check_day = strtolower(date('l', strtotime($check_date)));

                $start_date = get_user_meta($staff_id, 'mpwpb_repeated_start_date', true);
                $repeat_after = (int) get_user_meta($staff_id, 'mpwpb_repeated_after', true) ?: 1;

                $get_off_dates = get_user_meta($staff_id, 'mpwpb_off_dates', false);
                $off_dates = !empty($get_off_dates) ? call_user_func_array('array_merge', $get_off_dates) : [];

                $get_off_days = get_user_meta($staff_id, 'mpwpb_off_days', true);
                $off_days_arr = !empty($get_off_days) ? array_map('strtolower', array_map('trim', explode(',', $get_off_days))) : [];

                if (self::mpwpb_is_repeated_date($start_date, $repeat_after, $check_date)) {
                    // Check if it's an off date
                    if (in_array($check_date, $off_dates)) {
                        return false;
                    }

                    // Check if it's an off day (e.g., 'sunday')
                    if (in_array($check_day, $off_days_arr)) {
                        return false;
                    }

                    return true;
                }
            }

            // 4. No usable schedule (no type set, or a One-time Schedule with no
            // dates): no date restriction -- available unless today is one of
            // their off dates/off days.
            if ($travel_type !== 'repeated') {
                $check_day = strtolower(date('l', strtotime($check_date)));
                $get_off_dates = get_user_meta($staff_id, 'mpwpb_off_dates', false);
                $off_dates = !empty($get_off_dates) ? call_user_func_array('array_merge', $get_off_dates) : [];
                $get_off_days = get_user_meta($staff_id, 'mpwpb_off_days', true);
                $off_days_arr = !empty($get_off_days) ? array_map('strtolower', array_map('trim', explode(',', $get_off_days))) : [];
                return !in_array($check_date, $off_dates) && !in_array($check_day, $off_days_arr);
            }
            return false;
        }


        public static function mpwpb_is_staff_available_time( $user_id, $check_time, $date = null) {
            if (!$date) {
                $day = strtolower(date('l'));
            } else {
                $day = strtolower(date('l', strtotime($date)));
            }

            $prefix = "mpwpb_{$day}_";

            $default_start_time = (float) get_user_meta($user_id, 'mpwpb_default_start_time', true);
            $default_end_time = (float) get_user_meta($user_id, 'mpwpb_default_end_time', true);
            $default_start_break_time = (float) get_user_meta($user_id, 'mpwpb_default_start_break_time', true);
            $default_end_break_time = (float) get_user_meta($user_id, 'mpwpb_default_end_break_time', true);

            $start_time = (float) get_user_meta($user_id, $prefix . 'start_time', true);
            $end_time = (float) get_user_meta($user_id, $prefix . 'end_time', true);
            $start_break = (float) get_user_meta($user_id, $prefix . 'start_break_time', true);
            $end_break = (float) get_user_meta($user_id, $prefix . 'end_break_time', true);

            $check_time = (float) $check_time;

            if( $start_time && $end_time ) {
                if ($check_time < $start_time || $check_time >= $end_time) {
                    return false;
                }
                if ($start_break && $end_break && $check_time >= $start_break && $check_time < $end_break) {
                    return false;
                }
            }else if ( $default_start_time && $default_end_time ) {
                if ($check_time < $default_start_time || $check_time >= $default_end_time) {
                    return false;
                }
                if ($default_start_break_time && $default_end_break_time && $check_time >= $default_start_break_time && $check_time < $default_end_break_time) {
                    return false;
                }
            }

            return true;
        }




        public function mpwpb_get_available_staff_old() {
            $service_id = isset($_POST['service_id']) ? sanitize_text_field($_POST['service_id']) : '';
            $date       = isset($_POST['staff_date']) ? sanitize_text_field(wp_unslash($_POST['staff_date'])) : '';
            $time       = isset($_POST['staff_time']) ? sanitize_text_field($_POST['staff_time']) : '';
            $date_time  = isset($_POST['date_time']) ? sanitize_text_field($_POST['date_time']) : '';

            $response = [
                'html' => '<option value="">No Staff Available</option>',
                'count' => 0
            ];

            if ($service_id) {
                $available_staff = [];

                $enable_staff_member = get_post_meta( $service_id, 'mpwpb_staff_member_add', true );
                if( $enable_staff_member === 'on' ){
                    $get_selected_staff = get_post_meta( $service_id, 'mpwpb_selected_staff_ids', array() );

                    $flat_selected_staff_ids = is_array($get_selected_staff) ? call_user_func_array('array_merge', $get_selected_staff) : [];
                    if (!empty($flat_selected_staff_ids)) {
                        $all_staffs = get_users([
                            'include' => $flat_selected_staff_ids,
                            'role'    => 'mpwpb_staff'
                        ]);

                        foreach ($all_staffs as $staff_data) {
                            $staff_id = $staff_data->ID;
                            if ($this->mpwpb_is_staff_booked($staff_id, $date, $time)) {
                                if (!$this->mpwpb_is_staff_booked_order($staff_id, $date_time)) {
                                    if (self::mpwpb_is_staff_available_time($staff_id, $time, $date_time)) {
                                        $available_staff[] = $staff_data;
                                    }
                                }
                            }
                        }
                    }
                }

                if (!empty($available_staff)) {
                    $html = '<option value="">Select Staff</option>';
                    foreach ($available_staff as $staff) {
                        $html .= '<option value="' . esc_attr($staff->ID) . '">' . esc_html($staff->display_name) . '</option>';
                    }
                    $response['html'] = $html;
                    $response['count'] = count($available_staff);
                }
            }

            wp_send_json($response); // return JSON and end execution
        }

        public function mpwpb_get_available_staff() {
			if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'mpwpb_nonce')) {
				wp_send_json_error(array('message' => esc_html__('Security check failed.', 'service-booking-manager')), 403);
			}
            $service_id = isset($_POST['service_id']) ? sanitize_text_field($_POST['service_id']) : '';
            $date       = isset($_POST['staff_date']) ? sanitize_text_field(wp_unslash($_POST['staff_date'])) : '';
            $time       = isset($_POST['staff_time']) ? sanitize_text_field($_POST['staff_time']) : '';
            $date_time  = isset($_POST['date_time']) ? sanitize_text_field($_POST['date_time']) : '';

            $response = [
                'html' => '',
                'count' => 0
            ];
			if ($service_id && get_post_type($service_id) === MPWPB_Function::get_cpt() && get_post_status($service_id) === 'publish') {
                $available_staff = [];

                $enable_staff_member = get_post_meta($service_id, 'mpwpb_staff_member_add', true);
                if ($enable_staff_member === 'on') {
					$get_selected_staff = get_post_meta($service_id, 'mpwpb_selected_staff_ids', true);
					$flat_selected_staff_ids = array();
					if (is_array($get_selected_staff)) {
						array_walk_recursive($get_selected_staff, static function ($staff_id) use (&$flat_selected_staff_ids) {
							$flat_selected_staff_ids[] = absint($staff_id);
						});
						$flat_selected_staff_ids = array_values(array_unique(array_filter($flat_selected_staff_ids)));
					}
                    if (!empty($flat_selected_staff_ids)) {
                        $all_staffs = get_users([
                            'include' => $flat_selected_staff_ids,
                            'role' => 'mpwpb_staff'
                        ]);

                        foreach ($all_staffs as $staff_data) {
                            $staff_id = $staff_data->ID;
                            if ($this->mpwpb_is_staff_booked($staff_id, $date, $time)) {
                                if (!$this->mpwpb_is_staff_booked_order($staff_id, $date_time)) {
                                    if (self::mpwpb_is_staff_available_time($staff_id, $time, $date_time)) {
                                        $available_staff[] = $staff_data;
                                    }
                                }
                            }
                        }
                    }
                    $response['html'] = self::render_staff_cards($available_staff, $date_time);
                    $response['count'] = !empty($available_staff) ? count($available_staff) : 1;
                }
            }

            wp_send_json( $response ); // return JSON and end execution
        }

        /**
         * Staff step cards: "Any Staff" first (pre-selected -- an empty
         * mpwpb_staff_member_booking already means "any"), then each free staff
         * member with their photo, or their initial when they have none.
         * Every card keeps .mpwp_select_staff_card + its hidden
         * .mpwpb_selected_staff input, which mpwpb_registration.js's click
         * handler reads.
         */
        private static function render_staff_cards($available_staff, $date_time) {
            $time_label = '';
            if ($date_time && strtotime($date_time)) {
                $time_label = date_i18n(get_option('time_format'), strtotime($date_time));
            }
            $check_icon = '<span class="mpwp_select_staff_radio" aria-hidden="true"><svg width="10" height="10" viewBox="0 0 16 16" fill="none"><path d="M3 8.5L6.5 12L13 4.5" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/></svg></span>';

            $html = '<div class="mpwp_select_staff_grid">';
            $html .= '<button type="button" class="mpwp_select_staff_card selected" aria-pressed="true">'
                . '<input type="hidden" class="mpwpb_selected_staff" name="mpwpb_selected_staff_id[]" value="">'
                . '<span class="mpwp_select_staff_avatar mpwp_select_staff_avatar--any" aria-hidden="true"><svg width="22" height="22" viewBox="0 0 24 24" fill="none"><circle cx="9" cy="8" r="3.2" stroke="currentColor" stroke-width="2"/><path d="M3.5 19c.6-3 2.8-5 5.5-5s4.9 2 5.5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><circle cx="17" cy="9" r="2.6" stroke="currentColor" stroke-width="2"/><path d="M16 14.2c2.4.2 4 1.9 4.5 4.8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>'
                . '<span class="mpwp_select_staff_text">'
                . '<span class="mpwp_select_staff_name">' . esc_html__('Any Staff', 'service-booking-manager') . '</span>'
                . '<span class="mpwp_select_staff_meta">' . esc_html__('First available', 'service-booking-manager') . '</span>'
                . '</span>'
                . $check_icon
                . '</button>';

            foreach ((array) $available_staff as $staff) {
                $image_id = get_user_meta($staff->ID, 'mpwpb_custom_profile_image', true);
                $image_url = $image_id ? wp_get_attachment_image_url($image_id, 'thumbnail') : '';
                if ($image_url) {
                    $avatar = '<img class="mpwp_select_staff_avatar" src="' . esc_url($image_url) . '" alt="">';
                } else {
                    $initial = function_exists('mb_substr') ? mb_strtoupper(mb_substr($staff->display_name, 0, 1)) : strtoupper(substr($staff->display_name, 0, 1));
                    $avatar = '<span class="mpwp_select_staff_avatar mpwp_select_staff_avatar--initial" aria-hidden="true">' . esc_html($initial) . '</span>';
                }
                $meta = $time_label
                    /* translators: %s: booked time, e.g. 4:00 pm */
                    ? sprintf(esc_html__('Available at %s', 'service-booking-manager'), esc_html($time_label))
                    : esc_html__('Available', 'service-booking-manager');
                $html .= '<button type="button" class="mpwp_select_staff_card" aria-pressed="false">'
                    . '<input type="hidden" class="mpwpb_selected_staff" name="mpwpb_selected_staff_id[]" value="' . esc_attr($staff->ID) . '">'
                    . $avatar
                    . '<span class="mpwp_select_staff_text">'
                    . '<span class="mpwp_select_staff_name">' . esc_html($staff->display_name) . '</span>'
                    . '<span class="mpwp_select_staff_meta">' . $meta . '</span>'
                    . '</span>'
                    . $check_icon
                    . '</button>';
            }
            $html .= '</div>';
            return $html;
        }
    }

    new MPWPB_Staff_Booking();
}
