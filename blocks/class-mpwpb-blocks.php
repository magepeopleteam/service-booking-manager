<?php
if (!defined('ABSPATH')) {
    die;
} // Cannot access directly.

class MPWPB_Blocks {
    public function __construct() {
        add_action('init', array($this, 'register_blocks'));
    }

    public function register_blocks() {
        if (!function_exists('register_block_type')) {
            return;
        }

        wp_register_script(
            'mpwpb-service-booking-block',
            plugins_url('blocks/js/service-booking-block.js', dirname(__FILE__)),
            array('wp-blocks', 'wp-element', 'wp-components', 'wp-editor')
        );

        register_block_type('mpwpb/service-booking', array(
            'editor_script' => 'mpwpb-service-booking-block',
            'render_callback' => array($this, 'render_service_booking_block'),
            'attributes' => array(
                'post_id' => array(
                    'type' => 'string',
                    'default' => ''
                )
            )
        ));
    }

    public function render_service_booking_block($attributes) {
        if (empty($attributes['post_id'])) {
            return '<div class="components-placeholder"><div class="components-placeholder__label">Service Booking</div><div class="components-placeholder__instructions">Please select a service in the block settings.</div></div>';
        }

        $shortcode = sprintf('[service-booking post_id="%s"]', esc_attr($attributes['post_id']));
        return do_shortcode($shortcode);
    }
}

new MPWPB_Blocks(); 