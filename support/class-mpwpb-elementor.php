<?php
/**
 * Service Booking Manager - Elementor Widget
 */

if (!defined('ABSPATH')) {
    exit;
}

class MPWPB_Elementor {

    public function __construct() {
        // Register widget category if needed
        add_action('elementor/elements/categories_registered', array($this, 'register_widget_category'));
        
        // Register the widget
        add_action('elementor/widgets/register', array($this, 'register_widgets'));
    }

    public function register_widget_category($elements_manager) {
        $elements_manager->add_category(
            'service-booking-manager',
            [
                'title' => esc_html__('Service Booking Manager', 'service-booking-manager'),
                'icon' => 'fa fa-calendar',
            ]
        );
    }

    public function register_widgets($widgets_manager) {
        // Include widget class file
        require_once(__DIR__ . '/widgets/class-mpwpb-booking-widget.php');
        
        // Register the widget if Elementor is loaded
        if (class_exists('\Elementor\Widget_Base') && class_exists('MPWPB_Booking_Widget')) {
            $widgets_manager->register(new MPWPB_Booking_Widget());
        }
    }
}

// Initialize the Elementor integration
new MPWPB_Elementor();

class MPWPB_Booking_Widget extends \Elementor\Widget_Base {

    public function get_name() {
        return 'mpwpb_booking_form';
    }

    public function get_title() {
        return esc_html__('Service Booking Form', 'service-booking-manager');
    }

    public function get_icon() {
        return 'eicon-calendar';
    }

    public function get_categories() {
        return ['general'];
    }

    protected function register_controls() {
        $this->start_controls_section(
            'content_section',
            [
                'label' => esc_html__('Content', 'service-booking-manager'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $forms = $this->get_booking_forms();
        
        $this->add_control(
            'form_id',
            [
                'label' => esc_html__('Select Booking Form', 'service-booking-manager'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => $forms,
                'default' => '',
            ]
        );

        $this->end_controls_section();
    }

    private function get_booking_forms() {
        $forms = array('' => esc_html__('Select a form...', 'service-booking-manager'));
        
        $args = array(
            'post_type' => 'mpwpb_item',
            'post_status' => 'publish',
            'posts_per_page' => -1,
        );
        
        $query = new WP_Query($args);
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $forms[get_the_ID()] = get_the_title();
            }
        }
        wp_reset_postdata();
        
        return $forms;
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        
        if (empty($settings['form_id'])) {
            echo '<div class="mpwpb-notice">' . esc_html__('Please select a booking form.', 'service-booking-manager') . '</div>';
            return;
        }
        
        echo do_shortcode('[service-booking post_id="' . esc_attr($settings['form_id']) . '"]');
    }
} 