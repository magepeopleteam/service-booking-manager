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
            $args = array(
                'post_type'      => 'mpwpb_booking',
                'posts_per_page' => -1,
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
                ),
            );

            $bookings = get_posts( $args );

            return ! empty( $bookings );
        }


        public static function mpwpb_is_repeated_date( $start_date, $repeat_every_days, $check_date ) {
            // Convert to DateTime objects
            $start = new DateTime($start_date);
            $check = new DateTime($check_date);

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
                $flat_dates = !empty($particular_dates) ? call_user_func_array('array_merge', $particular_dates) : [];

                return in_array($check_date, $flat_dates);
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

            // 4. If no condition matched
            return false;
        }


        public static function mpwpb_is_staff_available_time( $user_id, $check_time, $date = null) {
            if (!$date) {
                $day = strtolower(date('l'));
            } else {
                $day = strtolower(date('l', strtotime($date)));
            }

            $prefix = "mpwpb_{$day}_";

            $default_start_time = (int) get_user_meta($user_id, 'mpwpb_default_start_time', true);
            $default_end_time = (int) get_user_meta($user_id, 'mpwpb_default_end_time', true);
            $default_start_break_time = (int) get_user_meta($user_id, 'mpwpb_default_start_break_time', true);
            $default_end_break_time = (int) get_user_meta($user_id, 'mpwpb_default_end_break_time', true);

            $start_time = (int) get_user_meta($user_id, $prefix . 'start_time', true);
            $end_time = (int) get_user_meta($user_id, $prefix . 'end_time', true);
            $start_break = (int) get_user_meta($user_id, $prefix . 'start_break_time', true);
            $end_break = (int) get_user_meta($user_id, $prefix . 'end_break_time', true);

            $check_time = (int) $check_time;

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

                if ( !empty( $available_staff ) ) {
                    $html = '<div class="mpwp_select_staff_grid">
                                <div class="mpwp_select_staff_card selected">
                                    <input type="hidden" class="mpwpb_selected_staff" name="mpwpb_selected_staff_id[]" value="">
                                    <div class="mpwp_select_staff_icon">👥</div>
                                    <div class="mpwp_select_staff_name">Any Staff</div>
                                </div>
                            ';
                    foreach ( $available_staff as $staff ) {
                        $image_id   = get_user_meta( $staff->ID, 'mpwpb_custom_profile_image', true );
                        $image_url  = esc_url( wp_get_attachment_url( $image_id ) );
                         $html .=
                                '<div class="mpwp_select_staff_card">
                                    <input type="hidden" class="mpwpb_selected_staff" name="mpwpb_selected_staff_id[]" value="'.$staff->ID.'">
                                    <img class="mpwpb_select_staff_image" src="'.$image_url.'" alt="'.$staff->user_nicename.'" class="mpwp_select_staff_img">
                                    <div class="mpwp_select_staff_name">' . esc_html($staff->display_name) . '</div>
                                    <div class="mpwp_select_staff_email">' . esc_html($staff->user_email) . '</div>
                                </div>';

                    }
                    $html .= '</div> </div>';

                    $response['html'] = $html;
                    $response['count'] = count($available_staff);
                }
            }

            wp_send_json($response); // return JSON and end execution
        }


    }

    new MPWPB_Staff_Booking();
}