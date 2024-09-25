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

            // save faq data
            add_action('wp_ajax_mpwpb_faq_data_save', [$this, 'save_faq_data_settings']);
            add_action('wp_ajax_nopriv_mpwpb_faq_data_save', [$this, 'save_faq_data_settings']);
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
                        $this->show_faq_data($post_id);
                    ?>
                    <br>
                    <button class="button mpwpb-sidebar-open">Add FAQ</button>
                </section>
                <!-- sidebar collapse open -->
                <div class="mpwpb-sidebar-container">
                    <div class="mpwpb-sidebar-content">
                        <span class="mpwpb-sidebar-close"><i class="fas fa-times"></i></span>
                        <?php $this->add_faq_data($post_id); ?>
                    </div>
                </div>
            </div>
            <?php
        }

        public function show_faq_data($post_id){
            $mpwpb_faq = get_post_meta($post_id,'mpwpb_faq',true);
            if( ! empty($mpwpb_faq)){
                foreach ($mpwpb_faq as $key => $value) {
                    ?>
                    <section class="faq-header" data-collapse-target="#faq-content-<?php echo esc_attr($key); ?>">
                        <label class="label">
                            <p><?php echo esc_html($value['title']); ?></p>
                            <div class="faq-action">
                                <span class="mpwpb-sidebar-open" data-id="<?php echo esc_attr($key); ?>"><i class="fas fa-edit"></i></span>
                                <span><i class="fas fa-trash"></i></span>
                            </div>
                        </label>
                    </section>
                    <section class="faq-content mB" data-collapse="#faq-content-<?php echo esc_attr($key); ?>">
                            <?php echo esc_html($value['content']); ?>
                    </section>
                    
                    <?php
                }
            }
        }

        public function add_faq_data($post_id){
            ?>
                <div id="mpwpb-faq-msg"></div>
                <h4><?php _e('Add F.A.Q.','service-booking-manage'); ?></h4>
                <p><?php _e('Add title','service-booking-manage'); ?></p>
                <input id="mpwpb-post-id" type="hidden" name="mpwpb_post_id" value="<?php echo $post_id; ?>"> 
                <input id="mpwpb-faq-input-title" type="text" name="mpwpb_faq[][title]"> 
                <p><?php _e('Add Content','service-booking-manage'); ?></p>
                <textarea id="mpwpb-faq-input-content" name="mpwpb_faq[][content]"></textarea><br>
                <p><input type="submit" name="faq_save" class="button button-primary button-large" value="save"><p>
            <?php
        }
        function content_editor($post_id) {   
            $content = get_post_meta($post_id, 'mpwpb_faq_content[]', true);
            wp_editor($content, 'mpwpb_faq_content', array(
                'mpwpb_faq_content' => 'mpwpb_faq_content',
                'editor_height' => 200,
            ));
        }
        function my_enqueue_editor_styles() {
            add_editor_style(); // This will enqueue the default editor styles
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
            }
            $mpwpb_faq[$count] = [
                'title'=>$_POST['mpwpb_faq_title'],
                'content'=>$_POST['mpwpb_faq_content'],
            ];
            if(update_post_meta($post_id, 'mpwpb_faq', $mpwpb_faq)){
                wp_send_json_success(__('Data updated successfully', 'mptbm_plugin_pro'));
            }
            die;
        }
    }
    new MPWPB_Faq_Settings();
}