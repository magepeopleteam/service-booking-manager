<?php
/**
 * @author Sahahdat Hossain <raselsha@gmail.com>
 * @license mage-people.com
 * @var 1.0.0
 */

if( ! defined('ABSPATH') ) die;

if( ! class_exists('MPWPB_Service_Details')){
    class MPWPB_Service_Details{
        
        public function __construct() {
            add_action('add_mpwpb_settings_tab_content', [$this, 'service_details']);
            add_action('mpwpb_settings_save', [$this, 'save_service_details']);
        }

        public function service_details($post_id) {

            $service_overview_status = MP_Global_Function::get_post_info($post_id, 'mpwpb_service_overview_status', 'off');
            $service_details_status = MP_Global_Function::get_post_info($post_id, 'mpwpb_service_details_status', 'off');
            
            $service_overview_checked = $service_overview_status == 'on'? 'checked': '';
            $service_overview_class = $service_overview_status == 'on'? 'mActive': '';

            $service_details_checked = $service_details_status == 'on'? 'checked': '';
            $active_class = $service_details_status == 'on'? 'mActive': '';
            
            $service_overview_content = get_post_meta($post_id,'mpwpb_service_overview_content',true);
            $service_details_content = get_post_meta($post_id,'mpwpb_service_details_content',true);

            ?>
            <div class="tabsItem" data-tabs="#mpwpb_service_details">
                <header>
                    <h2><?php esc_html_e('Service Details Settings', 'service-booking-manager'); ?></h2>
                    <span><?php esc_html_e('Service Details will be here.', 'service-booking-manager'); ?></span>
                </header>

                <!-- service overview -->
                <section class="section">
                        <h2><?php esc_html_e('Service Overview', 'service-booking-manager'); ?></h2>
                        <span><?php esc_html_e('Service Overview', 'service-booking-manager'); ?></span>
                </section>
                <section>
                    <label class="label">
                        <div>
                            <p><?php esc_html_e('Enable Service Overview', 'service-booking-manage'); ?></p>
                            <span><?php esc_html_e('Enable Service Overview', 'service-booking-manage'); ?></span>
                        </div>
                        <div>
                            <?php MP_Custom_Layout::switch_button('mpwpb_service_overview_status', $service_overview_checked); ?>
                        </div>
                    </label>
                </section>
                <section class="mpwpb-service-overview <?php echo $service_overview_class; ?>" data-collapse="#mpwpb_service_overview_status">
                    <?php 
                        $this->show_editor($service_overview_content,'mpwpb_service_overview_content');
                    ?>
                </section>
                <!-- service details -->
                <section class="section">
                        <h2><?php esc_html_e('Service Details', 'service-booking-manager'); ?></h2>
                        <span><?php esc_html_e('Service Details', 'service-booking-manager'); ?></span>
                </section>
                <section>
                    <label class="label">
                        <div>
                            <p><?php esc_html_e('Enable Service Details', 'service-booking-manage'); ?></p>
                            <span><?php esc_html_e('Enable Service Details', 'service-booking-manage'); ?></span>
                        </div>
                        <div>
                            <?php MP_Custom_Layout::switch_button('mpwpb_service_details_status', $service_details_checked); ?>
                        </div>
                    </label>
                </section>
                <section class="mpwpb-service-details <?php echo $active_class; ?>" data-collapse="#mpwpb_service_details_status">
                    <?php 
                        $this->show_editor($service_details_content,'mpwpb_service_details_content');
                    ?>
                </section>
                
            </div>
            <?php
        }

        public function show_editor($content,$field_name) {
            $content = $content; // You can set default content if needed.
            $editor_id = $field_name; // ID for the editor (used internally by wp_editor).
            $settings = array();
            wp_editor( $content, $editor_id, $settings );
        }

        public function save_service_details($post_id) {
            if (get_post_type($post_id) == MPWPB_Function::get_cpt()) {
                $service_overview_status = MP_Global_Function::get_submit_info('mpwpb_service_overview_status','off');
                $service_details_status = MP_Global_Function::get_submit_info('mpwpb_service_details_status','off');

                $service_overview_content = wp_kses_post($_POST['mpwpb_service_overview_content']);;
                $service_details_content =  wp_kses_post($_POST['mpwpb_service_details_content']);
                
                update_post_meta($post_id, 'mpwpb_service_overview_status', $service_overview_status);
                update_post_meta($post_id, 'mpwpb_service_details_status', $service_details_status);

                update_post_meta($post_id, 'mpwpb_service_overview_content', $service_overview_content);
                update_post_meta($post_id, 'mpwpb_service_details_content', $service_details_content);
            }
        }
    }
    new MPWPB_Service_Details();
}