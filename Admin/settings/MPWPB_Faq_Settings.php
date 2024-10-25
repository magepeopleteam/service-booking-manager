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
            add_action('wp_ajax_mpwpb_faq_data_update', [$this, 'faq_data_update']);
            add_action('wp_ajax_nopriv_mpwpb_faq_data_update', [$this, 'faq_data_update']);

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
                    <div class="faq-lists">
                        <?php 
                            $mpwpb_faq = get_post_meta($post_id,'mpwpb_faq',true);
                            $mpwpb_faq = !empty($mpwpb_faq) ? $mpwpb_faq : [];
                            if( ! empty($mpwpb_faq)){
                                foreach ($mpwpb_faq as $value) {
                                    $this->show_faq_data($value);
                                }
                            }else{
                                _e('No data available','service-booking-manager');
                            }
                        ?>
                    </div>
                    <br>
                    <button class="button create-new-faq" type="button"><?php _e('Add New FAQ','service-booking-manager'); ?></button>
                </section>
                <!-- sidebar collapse open -->
                <div class="mpwpb-sidebar-container">
                    <div class="mpwpb-sidebar-content">
                        <span class="mpwpb-sidebar-close"><i class="fas fa-times"></i></span>
                        <div class="mpwpb-faq-form">
                            <div id="mpwpb-faq-msg"></div>
                            <h4><?php _e('Add F.A.Q.','service-booking-manager'); ?></h4>
                            <p><?php _e('Add title','service-booking-manager'); ?></p>
                            <input type="hidden" name="faq_post_id" value="<?php echo $post_id; ?>"> 
                            <input type="hidden" name="faq_id" value="<?php echo count($mpwpb_faq); ?>"> 
                            <input type="text"   name="faq_title"> 
                            <p><?php _e('Add Content','service-booking-manager'); ?></p>
                            <?php 
                                $content = ''; // You can set default content if needed.
                                $editor_id = 'faq_content_id'; // ID for the editor (used internally by wp_editor).
                                $settings = array(
                                    'textarea_name' => 'faq_content_id',
                                    'media_buttons' => true,
                                    'textarea_rows' => 10,
                                );
                                wp_editor( $content, $editor_id, $settings );
                            ?>
                            <p><input type="submit" name="faq_save" class="button button-primary button-large faq_save" value="save"><p>
                        </div>
                    </div>
                </div>
            </div>
            <?php
        }

        public function show_faq_data($value){
        ?>
        <div class="mpwpb-faq-items" data-id="<?php echo esc_attr($value['id']); ?>">
            <section class="faq-header" data-collapse-target="#faq-content-<?php echo esc_attr($value['id']); ?>">
                <label class="label">
                    <p><?php echo esc_html($value['title']); ?></p>
                    <div class="faq-action">
                        <span class="mpwpb-faq-item-edit" ><i class="fas fa-edit"></i></span>
                        <span class="mpwpb-faq-item-delete"><i class="fas fa-trash"></i></span>
                    </div>
                </label>
            </section>
            <section class="faq-content mB" data-collapse="#faq-content-<?php echo esc_attr($value['id']); ?>">
                <?php echo wpautop(wp_kses_post($value['content'])); ?>
            </section>
        </div>
        <?php
        }

        public function save_faq_settings($post_id) {
            if (get_post_type($post_id) == MPWPB_Function::get_cpt()) {
                $mpwpb_faq_active = MP_Global_Function::get_submit_info('mpwpb_faq_active');
                update_post_meta($post_id, 'mpwpb_faq_active', $mpwpb_faq_active);
            }
        }

        public function faq_data_update() {
            $post_id = $_POST['faq_post_id'];
            $mpwpb_faq = get_post_meta($post_id,'mpwpb_faq',true)??[];
            $new_data = [
                'id'=>$_POST['faq_id'],
                'title'=> sanitize_text_field($_POST['faq_title']),
                'content'=> wp_kses_post($_POST['faq_content']),
            ];
            $mpwpb_faq[$_POST['faq_id']] =  $new_data;

            $mpwpb_faq = array_values($mpwpb_faq);
            $result = update_post_meta($post_id, 'mpwpb_faq', $mpwpb_faq);
            $mpwpb_faq = get_post_meta($post_id,'mpwpb_faq',true)??[];
            if($result){
                ob_start();
                $resultMessage = __('Data inserted successfully', 'mptbm_plugin_pro');
                foreach($mpwpb_faq as $value){
                    $this->show_faq_data($value);
                }
                $html_output = ob_get_clean();
                wp_send_json_success([
                    'message' => $resultMessage,
                    'html' => $html_output,
                    'faq_id' => $_POST['faq_id'],
                ]);
            }
            
            
            die;
        }
        public function save_faq_data_settings() {
            $post_id = $_POST['faq_post_id'];
            $mpwpb_faq = get_post_meta($post_id,'mpwpb_faq',true)??[];
            $new_data = [
                'id'=>$_POST['faq_id'],
                'title'=> sanitize_text_field($_POST['faq_title']),
                'content'=> wp_kses_post($_POST['faq_content']),
            ];
            array_push($mpwpb_faq,$new_data);
            $result = update_post_meta($post_id, 'mpwpb_faq', $mpwpb_faq);
            $mpwpb_faq = get_post_meta($post_id,'mpwpb_faq',true)??[];
            if($result){
                ob_start();
                $resultMessage = __('Data inserted successfully', 'mptbm_plugin_pro');
                foreach($mpwpb_faq as $value){
                    $this->show_faq_data($value);
                }
                $html_output = ob_get_clean();
                wp_send_json_success([
                    'message' => $resultMessage,
                    'html' => $html_output,
                    'faq_id' => count($mpwpb_faq),
                ]);
            }
            
            
            die;
        }

        public function faq_delete_item(){
            $post_id = $_POST['faq_post_id'];
            $mpwpb_faq = get_post_meta($post_id,'mpwpb_faq',true)?:[];
            if( ! empty($mpwpb_faq)){
                if(isset($_POST['faq_id'])){
                    unset($mpwpb_faq[$_POST['faq_id']]);
                    $mpwpb_faq = array_values($mpwpb_faq);
                    $result = update_post_meta($post_id, 'mpwpb_faq', $mpwpb_faq);
                }
            }
            else{
                $result = update_post_meta($post_id, 'mpwpb_faq', []);
            }
            
            
            if($result){
                wp_send_json_success(__('Data Deleted successfully', 'mptbm_plugin_pro'));
            }
            die;
        }
    }
    new MPWPB_Faq_Settings();
}