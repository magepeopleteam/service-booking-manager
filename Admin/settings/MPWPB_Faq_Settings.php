<?php
/**
 * @author Sahahdat Hossain <raselsha@gmail.com>
 * @license mage-people.com
 * @var 1.0.0
 */

if( ! defined('ABSPATH') ) die;

if( ! class_exists('MPWPB_Faq_Settings')){
    class MPWPB_Faq_Settings{
        
        public function __construct() {
            add_action('add_mpwpb_settings_tab_content', [$this, 'faq_settings']);

            add_action('mpwpb_settings_save', [$this, 'save_faq_settings'], 10, 1);

            add_action('admin_enqueue_scripts',  [$this, 'my_custom_editor_enqueue']);
            // save faq data
            add_action('wp_ajax_mpwpb_faq_data_save', [$this, 'save_faq_data_settings']);
            add_action('wp_ajax_nopriv_mpwpb_faq_data_save', [$this, 'save_faq_data_settings']);
            
            // mpwpb_delete_faq_data
            add_action('wp_ajax_mpwpb_faq_delete_item', [$this, 'faq_delete_item']);
            add_action('wp_ajax_nopriv_mpwpb_faq_delete_item', [$this, 'faq_delete_item']);
        }

        public function my_custom_editor_enqueue() {
            // Enqueue necessary scripts
            wp_enqueue_script('jquery');
            wp_enqueue_script('editor');
            wp_enqueue_script('media-upload');
            wp_enqueue_script('thickbox');
            wp_enqueue_style('thickbox');
        }
        

        
        public function faq_settings($post_id) {
            $mpwpb_faq_active = MP_Global_Function::get_post_info($post_id, 'mpwpb_faq_active', 'off');
            $active_class = $mpwpb_faq_active == 'on' ? 'mActive' : '';
            $mpwpb_faq_active_checked = $mpwpb_faq_active == 'on' ? 'checked' : '';
            ?>
            <div class="tabsItem" data-tabs="#mpwpb_faq_settings">
                <header>
                    <h2><?php esc_html_e('FAQ Settings', 'service-booking-manager'); ?></h2>
                    <span><?php esc_html_e('FAQ Settings will be here.', 'service-booking-manager'); ?></span>
                </header>
                <section class="section">
                        <h2><?php esc_html_e('FAQ Settings', 'service-booking-manager'); ?></h2>
                        <span><?php esc_html_e('FAQ Settings section one.', 'service-booking-manager'); ?></span>
                </section>
                <section>
                    <label class="label">
                        <div>
                            <p><?php esc_html_e('Enable FAQ Section', 'service-booking-manage'); ?></p>
                            <span><?php esc_html_e('Enable FAQ Section', 'service-booking-manage'); ?></span>
                        </div>
                        <div>
                            <?php MP_Custom_Layout::switch_button('mpwpb_faq_active', $mpwpb_faq_active_checked); ?>
                        </div>
                    </label>
                </section>
                <section class="mpwpb-faq <?php echo $active_class; ?>" data-collapse="#mpwpb_faq_active">
                    <?php 
                        $mpwpb_faq = get_post_meta($post_id,'mpwpb_faq',true);
                        if( ! empty($mpwpb_faq)){
                            foreach ($mpwpb_faq as $key => $value) {
                                $this->show_faq_data($key,$value['title'],$value['content']);
                            }
                        }
                    ?>
                    <br>
                    <button class="button mpwpb-sidebar-open" type="button"><?php _e('Add FAQ','service-booking-manager'); ?></button>
                </section>
                <!-- sidebar collapse open -->
                <div class="mpwpb-sidebar-container">
                    <div class="mpwpb-sidebar-content">
                        <span class="mpwpb-sidebar-close"><i class="fas fa-times"></i></span>
                        <?php $this->add_faq_form($post_id); ?>
                    </div>
                </div>
            </div>
            <?php
        }

        public function show_faq_data($key,$title,$content){
        ?>
        <div class="mpwpb-faq-items" data-id="<?php echo esc_attr($key); ?>">
            <section class="faq-header" data-collapse-target="#faq-content-<?php echo esc_attr($key); ?>">
                <label class="label">
                    <p><?php echo esc_html($title); ?></p>
                    <div class="faq-action">
                        <span class="mpwpb-sidebar-open" ><i class="fas fa-edit"></i></span>
                        <span class="mpwpb-faq-item-delete"><i class="fas fa-trash"></i></span>
                    </div>
                </label>
            </section>
            <section class="faq-content mB" data-collapse="#faq-content-<?php echo esc_attr($key); ?>">
                <?php echo wpautop(wp_kses_post($content)); ?>
            </section>
        </div>
        <?php

        }

        public function add_faq_form($post_id){
            ?>
            <div class="mpwpb-faq-form">
                <div id="mpwpb-faq-msg"></div>
                <h4><?php _e('Add F.A.Q.','service-booking-manager'); ?></h4>
                <p><?php _e('Add title','service-booking-manager'); ?></p>
                <input type="hidden" name="mpwpb_post_id" value="<?php echo $post_id; ?>"> 
                <input type="text"   name="mpwpb_faq_title"> 
                <p><?php _e('Add Content','service-booking-manager'); ?></p>
                <?php $this->show_editor($post_id); ?>
                <p><input type="submit" name="mpwpb_faq_save" class="button button-primary button-large" value="save"><p>
            </div>
            <?php
        }

        public function show_editor($post_id) {
            $content = ''; // You can set default content if needed.
            $editor_id = 'mpwpb_faq_content'; // ID for the editor (used internally by wp_editor).
            $settings = array(
                'textarea_name' => 'mpwpb_faq_content',
                'media_buttons' => true,
                'textarea_rows' => 10,
            );
            wp_editor( $content, $editor_id, $settings );
        }

        public function save_faq_settings($post_id) {
            if (get_post_type($post_id) == MPWPB_Function::get_cpt()) {
                $mpwpb_faq_active = MP_Global_Function::get_submit_info('mpwpb_faq_active');
                update_post_meta($post_id, 'mpwpb_faq_active', $mpwpb_faq_active);
            }
        }

        public function save_faq_data_settings() {
            $post_id = $_POST['mpwpb_faq_postID'];
            $mpwpb_faq = get_post_meta($post_id,'mpwpb_faq',true)?:[];
            $count = 0;
            if( ! empty($mpwpb_faq)){
                $count = count($mpwpb_faq);
                if(isset($_POST['itemId'])){
                    $count = $_POST['itemId'];
                }
            }

            $mpwpb_faq[$count] = [
                'title'=> sanitize_text_field($_POST['mpwpb_faq_title']),
                'content'=> wp_kses_post($_POST['mpwpb_faq_content']),
            ];
            // $mpwpb_faq =[];
            if(update_post_meta($post_id, 'mpwpb_faq', $mpwpb_faq)){
                ob_start();
                $resultMessage = __('Data updated successfully', 'mptbm_plugin_pro');
                $this->show_faq_data($count,$_POST['mpwpb_faq_title'],$_POST['mpwpb_faq_content']);
                $html_output = ob_get_clean();
                wp_send_json_success(array(
                    'message' => $resultMessage,
                    'html' => $html_output,
                ));
            }
            die;
        }

        public function faq_delete_item(){
            $post_id = $_POST['mpwpb_faq_postID'];
            $mpwpb_faq = get_post_meta($post_id,'mpwpb_faq',true)?:[];
            if( ! empty($mpwpb_faq)){
                if(isset($_POST['itemId'])){
                    unset($mpwpb_faq[$_POST['itemId']]);
                    $mpwpb_faq = array_values($mpwpb_faq);
                }
            }
            if(update_post_meta($post_id, 'mpwpb_faq', $mpwpb_faq)){
                wp_send_json_success(__('Data Deleted successfully', 'mptbm_plugin_pro'));
            }
            die;
        }
    }
    new MPWPB_Faq_Settings();
}