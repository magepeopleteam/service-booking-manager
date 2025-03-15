<?php
/**
 * Service Booking Manager - Elementor Widget Class
 */

if (!defined('ABSPATH')) {
    exit;
}

// Only define the widget class if Elementor is loaded
if (!class_exists('MPWPB_Booking_Widget') && did_action('elementor/loaded') && class_exists('\Elementor\Widget_Base')) {
    class MPWPB_Booking_Widget extends \Elementor\Widget_Base {
        
        public function get_name() {
            return 'mpwpb_booking_form';
        }

        public function get_title() {
            return esc_html__('Service Booking Form', 'service-booking-manager');
        }

        public function get_icon() {
            return 'eicon-form-horizontal';
        }

        public function get_categories() {
            return ['service-booking-manager'];
        }

        public function get_keywords() {
            return ['booking', 'service', 'form', 'schedule'];
        }

        protected function register_controls() {
            $this->start_controls_section(
                'content_section',
                [
                    'label' => esc_html__('Booking Form', 'service-booking-manager'),
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
                    'label_block' => true,
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
                'orderby' => 'title',
                'order' => 'ASC'
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
} 