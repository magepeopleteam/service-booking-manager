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
            $mpwpb_service_details_active = MP_Global_Function::get_post_info($post_id, 'mpwpb_service_details_active', 'off');
            $mpwpb_service_details_content = MP_Global_Function::get_post_info($post_id, 'mpwpb_service_details_content', '');
            $active_class = $mpwpb_service_details_active == 'on' ? 'mActive' : '';
            $mpwpb_service_details_checked = $mpwpb_service_details_active == 'on' ? 'checked' : '';
            ?>
            <div class="tabsItem" data-tabs="#mpwpb_service_details">
                <header>
                    <h2><?php esc_html_e('Service Details Settings', 'service-booking-manager'); ?></h2>
                    <span><?php esc_html_e('Service Details will be here.', 'service-booking-manager'); ?></span>
                </header>
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
                            <?php MP_Custom_Layout::switch_button('mpwpb_service_details_active', $mpwpb_service_details_checked); ?>
                        </div>
                    </label>
                </section>
                <section class="mpwpb-service-details <?php echo $active_class; ?>" data-collapse="#mpwpb_service_details_active">
                    <?php 
                        $this->show_editor($mpwpb_service_details_content);
                    ?>
                </section>
            </div>
            <?php
        }

        public function show_editor($mpwpb_service_details_content) {
            $content = $mpwpb_service_details_content; // You can set default content if needed.
            $editor_id = 'mpwpb_service_details_content'; // ID for the editor (used internally by wp_editor).
            $settings = array(
                'textarea_name' => 'mpwpb_service_details_content',
                'media_buttons' => true,
                'textarea_rows' => 10,
                'teeny'         => true, // Enables a simpler editor
                'quicktags'     => true, // Disables quicktags
                'tinymce'       => array(
                    'toolbar1' => 'bold,italic,underline,strikethrough,blockquote,alignleft,aligncenter,alignright,alignjustify,link,unlink,wp_more,spellchecker',
                    'toolbar2' => 'pastetext,removeformat,wp_help', // Additional buttons in a second row
                ),
            );
            wp_editor( $content, $editor_id, $settings );
        }

        public function save_service_details($post_id) {
            if (get_post_type($post_id) == MPWPB_Function::get_cpt()) {
                $mpwpb_service_details_active = MP_Global_Function::get_submit_info('mpwpb_service_details_active');
                $mpwpb_service_details_content = MP_Global_Function::get_submit_info('mpwpb_service_details_content');
                update_post_meta($post_id, 'mpwpb_service_details_active', $mpwpb_service_details_active);
                update_post_meta($post_id, 'mpwpb_service_details_content', $mpwpb_service_details_content);;
            }
        }
    }
    new MPWPB_Service_Details();
}