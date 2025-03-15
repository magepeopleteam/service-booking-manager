<?php
if (!defined('ABSPATH')) {
    die;
} // Cannot access directly.

class MPWPB_Elementor {
    private static $_instance = null;

    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct() {
        add_action('elementor/widgets/widgets_registered', array($this, 'register_widgets'));
        add_action('elementor/elements/categories_registered', array($this, 'add_widget_categories'));
        add_action('elementor/frontend/after_register_scripts', array($this, 'register_frontend_scripts'));
        add_action('elementor/frontend/after_register_styles', array($this, 'register_frontend_styles'));
    }

    public function add_widget_categories($elements_manager) {
        $elements_manager->add_category(
            'mpwpb-elements',
            [
                'title' => esc_html__('Service Booking', 'service-booking-manager'),
                'icon' => 'fa fa-plug',
            ]
        );
    }

    public function register_widgets() {
        require_once(__DIR__ . '/widgets/class-mpwpb-service-booking-widget.php');
        \Elementor\Plugin::instance()->widgets_manager->register_widget_type(new \MPWPB_Service_Booking_Widget());
    }

    public function register_frontend_scripts() {
        wp_register_script(
            'mpwpb-elementor-preview',
            MPWPB_PLUGIN_URL . '/elementor/assets/js/preview.js',
            array('jquery'),
            true
        );
    }

    public function register_frontend_styles() {
        wp_register_style(
            'mpwpb-elementor-preview',
            MPWPB_PLUGIN_URL . '/elementor/assets/css/preview.css',
            array()
        );
    }
}

// Initialize
MPWPB_Elementor::instance(); 