<?php
/*
* @Author 		MagePeople Team
* Copyright: 	mage-people.com
*/
if (!defined('ABSPATH')) {
    die;
} // Cannot access pages directly.

if (!class_exists('MPWPB_Waiting_List')) {
    class MPWPB_Waiting_List {
        public function __construct() {
            // Enqueue scripts and styles
            add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        }

        /**
         * Enqueue scripts and styles for waiting list functionality
         */
        public function enqueue_scripts() {

            $post_content = isset( get_post()->post_content ) ? get_post()->post_content : '';
            // Only enqueue on service booking pages
            if( $post_content ){
                if (is_singular(MPWPB_Function::get_cpt()) || has_shortcode( $post_content, 'mpwpb-registration')) {
                    wp_enqueue_style('mpwpb-waiting-list', MPWPB_PLUGIN_URL . '/assets/frontend/mpwpb_waiting_list.css', array());
                    wp_enqueue_script('mpwpb-waiting-list', MPWPB_PLUGIN_URL . '/assets/frontend/mpwpb_waiting_list.js', array('jquery'),  true);
                }
            }
        }
    }
    new MPWPB_Waiting_List();
}