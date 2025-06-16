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

        public function ssnb_is_staff_booked( $staff_term_id, $date, $time ) {
            $args = array(
                'post_type' => 'mpwpb_booking',
                'posts_per_page' => -1,
                'meta_query' => array(
                    array('key' => 'mpwpb_staff_term_id', 'value' => $staff_term_id),
                    array('key' => 'mpwpb_staff_date', 'value' => $date),
                    array('key' => 'mpwpb_staff_time', 'value' => $time),
                ),
            );
            $bookings = get_posts($args);
            return !empty($bookings);
        }
        public function ssnb_is_staff_booked_new( $staff_term_id, $datetime ) {
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

        public static function get_date_check( $staff_id, $check_date, $time ) {

            $off_days_arr = [];
            $travel_type = get_user_meta( $staff_id, 'date_type', true );

            $check_by_time = self::mpwpb_is_staff_available_time( $staff_id, $time, $check_date );
//            error_log( print_r( [ '$check_by_time' => $check_by_time ], true ) );

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

        }

        public static function mpwpb_is_staff_available_time_old( $user_id, $check_time, $date = null ) {
            if (!$date) {
                $day = strtolower(date('l')); // আজকের দিন (monday, tuesday etc)
            }else{
                $day = strtolower( date('l', strtotime( $date ) ) );
            }

            $prefix = "mpwpb_{$day}_";
            $default_end = "mpwpb_default_end_time";
            $default_start = "mpwpb_default_start_time";

            $default_start_time = get_user_meta( $user_id, 'mpwpb_default_start_time', true);
            $default_end_time = get_user_meta($user_id,'mpwpb_default_end_time', true);

            $start_time = get_user_meta( $user_id, $prefix . 'start_time', true);
            $end_time = get_user_meta($user_id, $prefix . 'end_time', true);
            $start_break = get_user_meta($user_id, $prefix . 'start_break_time', true);
            $end_break = get_user_meta($user_id, $prefix . 'end_break_time', true);

            $check_time = (int) $check_time;
            $start_time = (int) $start_time;
            $end_time = (int) $end_time;
            $start_break = (int) $start_break;
            $end_break = (int) $end_break;

            if ($check_time < $start_time || $check_time >= $end_time) {
                return false;
            }

            if ($check_time >= $start_break && $check_time < $end_break) {
                return false;
            }

            return true;
        }

        public static function mpwpb_is_staff_available_time( $user_id, $check_time, $date = null ) {
            // দিন বের করা
            if (!$date) {
                $day = strtolower(date('l')); // আজকের দিন (monday, tuesday etc)
            } else {
                $day = strtolower(date('l', strtotime($date)));
            }

            $prefix = "mpwpb_{$day}_";

            $default_start_time = (int) get_user_meta($user_id, 'mpwpb_default_start_time', true);
            $default_end_time = (int) get_user_meta($user_id, 'mpwpb_default_end_time', true);

            $start_time = (int) get_user_meta($user_id, $prefix . 'start_time', true);
            $end_time = (int) get_user_meta($user_id, $prefix . 'end_time', true);
            $start_break = (int) get_user_meta($user_id, $prefix . 'start_break_time', true);
            $end_break = (int) get_user_meta($user_id, $prefix . 'end_break_time', true);

            $check_time = (int) $check_time;

            if ($default_start_time && $default_end_time) {
                if ($check_time < $default_start_time || $check_time >= $default_end_time) {
                    return false;
                }
            } else {
                if ($check_time < $start_time || $check_time >= $end_time) {
                    return false;
                }

                if ($check_time >= $start_break && $check_time < $end_break) {
                    return false;
                }
            }

            return true;
        }



        public function mpwpb_get_available_staff() {

            $date = sanitize_text_field( $_POST['staff_date'] );
            $time = sanitize_text_field( $_POST['staff_time'] );

            $count = 1;
            $all_staffs = get_users(['role' => 'mpwpb_staff']);

            if ( sizeof($all_staffs) > 0) {
                $available_staff = [];
                foreach ($all_staffs as $staff_data ) {
                    $staff_id = $staff_data->ID;
                    if ( $this->mpwpb_is_staff_booked( $staff_id, $date, $time ) ) {
                        $available_staff[] = $staff_data;
                    }
                }
            }

            if (!empty( $available_staff ) ) {
                echo '<option value="">Select Staff</option>';
                foreach ( $available_staff as $staff) {
                    echo '<option value="' . esc_attr( $staff->data->ID ) . '">' . esc_html( $staff->data->display_name) . '</option>';
                }
            } else {
                echo '<option value="">No Staff Available</option>';
            }

            wp_die();
        }

    }

    new MPWPB_Staff_Booking();
}