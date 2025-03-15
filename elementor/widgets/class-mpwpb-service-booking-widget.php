<?php
if (!defined('ABSPATH')) {
    die;
} // Cannot access directly.

class MPWPB_Service_Booking_Widget extends \Elementor\Widget_Base {
    public function get_name() {
        return 'mpwpb_service_booking';
    }

    public function get_title() {
        return esc_html__('Service Booking', 'service-booking-manager');
    }

    public function get_icon() {
        return 'eicon-calendar';
    }

    public function get_categories() {
        return ['mpwpb-elements'];
    }

    protected function _register_controls() {
        $this->start_controls_section(
            'content_section',
            [
                'label' => esc_html__('Content', 'service-booking-manager'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        // Get all service posts
        $services = get_posts([
            'post_type' => MPWPB_Function::get_cpt(),
            'post_status' => 'publish',
            'numberposts' => -1
        ]);

        $service_options = [
            '' => esc_html__('Select a Service', 'service-booking-manager')
        ];
        
        foreach ($services as $service) {
            $service_options[$service->ID] = $service->post_title;
        }

        $this->add_control(
            'post_id',
            [
                'label' => esc_html__('Select Service', 'service-booking-manager'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => $service_options,
                'default' => '',
            ]
        );

        $this->end_controls_section();

        // Add style controls
        $this->start_controls_section(
            'style_section',
            [
                'label' => esc_html__('Style', 'service-booking-manager'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'background_color',
            [
                'label' => esc_html__('Background Color', 'service-booking-manager'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .mpStyle' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'text_color',
            [
                'label' => esc_html__('Text Color', 'service-booking-manager'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .mpStyle' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        
        if (empty($settings['post_id'])) {
            echo '<div class="elementor-alert elementor-alert-info">';
            echo esc_html__('Please select a service in the widget settings.', 'service-booking-manager');
            echo '</div>';
            return;
        }

        echo do_shortcode('[service-booking post_id="' . esc_attr($settings['post_id']) . '"]');
    }

    protected function _content_template() {
        ?>
        <# if ( settings.post_id ) { #>
            <div class="elementor-alert elementor-alert-info">
                <?php echo esc_html__('Service Booking Preview for Service ID: ', 'service-booking-manager'); ?> {{{ settings.post_id }}}
            </div>
            <div class="mpStyle mpwpb_registration_short_code mpwpb_registration mpwpb-static-template">
                <div class="sidebar">
                    <!-- Preview of the booking form structure -->
                    <div class="preview-form-structure">
                        <div class="service-selection">
                            <h3><?php esc_html_e('Service Selection', 'service-booking-manager'); ?></h3>
                            <div class="preview-placeholder"></div>
                        </div>
                        <div class="date-time-selection">
                            <h3><?php esc_html_e('Date & Time Selection', 'service-booking-manager'); ?></h3>
                            <div class="preview-placeholder"></div>
                        </div>
                        <div class="booking-details">
                            <h3><?php esc_html_e('Booking Details', 'service-booking-manager'); ?></h3>
                            <div class="preview-placeholder"></div>
                        </div>
                    </div>
                </div>
            </div>
        <# } else { #>
            <div class="elementor-alert elementor-alert-info">
                <?php echo esc_html__('Please select a service in the widget settings.', 'service-booking-manager'); ?>
            </div>
        <# } #>
        <?php
    }

    public function get_script_depends() {
        return ['mpwpb-elementor-preview'];
    }

    public function get_style_depends() {
        return ['mpwpb-elementor-preview'];
    }
} 