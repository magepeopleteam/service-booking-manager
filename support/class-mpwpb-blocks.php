<?php
/**
 * Service Booking Manager - Gutenberg Block
 */

if (!defined('ABSPATH')) {
    exit;
}

class MPWPB_Blocks {
    
    public function __construct() {
        add_action('init', array($this, 'register_booking_block'));
    }

    public function register_booking_block() {
        if (!function_exists('register_block_type')) {
            return;
        }

        // Register block script
        wp_register_script(
            'mpwpb-booking-block',
            plugins_url('js/blocks/booking-block.js', dirname(__FILE__)),
            array('wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n', 'wp-server-side-render')
        );

        // Add forms data to script
        wp_localize_script(
            'mpwpb-booking-block',
            'mpwpbBlockData',
            array(
                'forms' => $this->get_booking_forms()
            )
        );

        register_block_type('service-booking-manager/booking-form', array(
            'editor_script' => 'mpwpb-booking-block',
            'render_callback' => array($this, 'render_booking_block'),
            'attributes' => array(
                'post_id' => array(
                    'type' => 'string',
                    'default' => ''
                )
            )
        ));
    }

    private function get_booking_forms() {
        $forms = array(
            array(
                'value' => '',
                'label' => esc_html__('Select a form...', 'service-booking-manager')
            )
        );
        
        $args = array(
            'post_type' => 'mpwpb_item',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        );
        
        $query = new WP_Query($args);
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $forms[] = array(
                    'value' => (string)get_the_ID(),
                    'label' => get_the_title()
                );
            }
        }
        wp_reset_postdata();
        
        return $forms;
    }

    public function render_booking_block($attributes) {
        $post_id = !empty($attributes['post_id']) ? $attributes['post_id'] : '';
        if (empty($post_id)) {
            return '<div class="mpwpb-notice">' . esc_html__('Please select a booking form.', 'service-booking-manager') . '</div>';
        }
        
        return do_shortcode('[service-booking post_id="' . esc_attr($post_id) . '"]');
    }
}

new MPWPB_Blocks(); 