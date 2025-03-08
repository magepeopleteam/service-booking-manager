<?php
/*
* @Author 		engr.sumonazma@gmail.com
* Copyright: 	mage-people.com
*/
if (!defined('ABSPATH')) {
    die;
} // Cannot access pages directly.

// Load the Analytics Dashboard
if (file_exists(MPWPB_PLUGIN_DIR . '/Admin/MPWPB_Analytics_Dashboard.php')) {
    require_once MPWPB_PLUGIN_DIR . '/Admin/MPWPB_Analytics_Dashboard.php';
}

// Make sure the dashboard is loaded on plugins_loaded
add_action('plugins_loaded', function() {
    if (file_exists(MPWPB_PLUGIN_DIR . '/Admin/MPWPB_Analytics_Dashboard.php') && !class_exists('MPWPB_Analytics_Dashboard')) {
        require_once MPWPB_PLUGIN_DIR . '/Admin/MPWPB_Analytics_Dashboard.php';
    }
});