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

        public function mpwpb_get_available_staff() {

            $date = sanitize_text_field( $_POST['staff_date'] );
            $time = sanitize_text_field( $_POST['staff_time'] );
            $date_time = sanitize_text_field( $_POST['date_time'] );

            $terms = get_terms(array(
                'taxonomy' => 'mpwpb_staff',
                'hide_empty' => false
            ));

            $available = [];

            foreach ($terms as $term) {
                /*if (!$this->ssnb_is_staff_booked( $term->term_id, $date, $time ) ) {
                    $available[] = $term;
                }*/
                if (!$this->ssnb_is_staff_booked_new( $term->term_id, $date_time ) ) {
                    $available[] = $term;
                }
            }
            if (!empty($available)) {
                echo '<option value="">Select Staff</option>';
                foreach ($available as $staff) {
                    echo '<option value="' . esc_attr($staff->term_id) . '">' . esc_html($staff->name) . '</option>';
                }
            } else {
                echo '<option value="">No Staff Available</option>';
            }

            wp_die();
        }

    }

    new MPWPB_Staff_Booking();
}