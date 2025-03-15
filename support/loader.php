<?php
/**
 * Service Booking Manager - Support Loader
 */

if (!defined('ABSPATH')) {
    exit;
}

// Create necessary directories
function mpwpb_create_support_directories() {
    $dirs = array(
        __DIR__ . '/js/blocks',
        __DIR__ . '/widgets',
    );

    foreach ($dirs as $dir) {
        if (!file_exists($dir)) {
            wp_mkdir_p($dir);
        }
    }
}
mpwpb_create_support_directories();

// Register block assets
function mpwpb_register_block_assets() {
    if (!function_exists('register_block_type')) {
        return;
    }

    wp_register_script(
        'mpwpb-booking-block',
        plugins_url('js/blocks/booking-block.js', __FILE__),
        array('wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n', 'wp-data'),
        filemtime(__DIR__ . '/js/blocks/booking-block.js')
    );
}
add_action('init', 'mpwpb_register_block_assets');

// Load Gutenberg Block
if (function_exists('register_block_type')) {
    require_once(__DIR__ . '/class-mpwpb-blocks.php');
}

// Load Elementor Widget
function mpwpb_load_elementor_widget() {
    // Check if Elementor is installed and activated
    if (!did_action('elementor/loaded')) {
        return;
    }

    // Initialize Elementor integration after Elementor is fully loaded
    add_action('elementor/init', function() {
        require_once(__DIR__ . '/class-mpwpb-elementor.php');
        new MPWPB_Elementor();
    });
}
add_action('plugins_loaded', 'mpwpb_load_elementor_widget', 20); 