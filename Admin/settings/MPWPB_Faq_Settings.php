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
            
            add_action('mpwpb_settings_save', [$this, 'save_faq_settings']);
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
                        $faq_items = [
                            [
                                'title' => 'Title One',
                                'content' => 'Content for FAQ one.',
                            ],
                            [
                                'title' => 'Title Two',
                                'content' => 'Content for FAQ two.',
                            ],
                            [
                                'title' => 'Title Three',
                                'content' => 'Content for FAQ three.',
                            ],
                            [
                                'title' => 'Title Four',
                                'content' => 'Content for FAQ four.',
                            ],
                            [
                                'title' => 'Title Five',
                                'content' => 'Content for FAQ five.',
                            ],
                        ];
                        foreach ($faq_items as $key => $item) {
                            ?>
                            <section class="faq-header" data-collapse-target="#faq-content-<?php echo esc_attr($key); ?>">
                                <label class="label">
                                    <p><?php echo esc_html($item['title']); ?></p>
                                    <div class="faq-action">
                                        <span class="mpwpb-sidebar-open"><i class="fas fa-edit"></i></span>
                                        <span><i class="fas fa-trash"></i></span>
                                    </div>
                                </label>
                            </section>
                            <section class="faq-content mB" data-collapse="#faq-content-<?php echo esc_attr($key); ?>">
                                <?php echo esc_html($item['content']); ?>
                            </section>
                            <?php
                        }
                    ?>
                </section>
                <div class="mpwpb-sidebar-container">
                    <div class="mpwpb-sidebar-content">
                        <span class="mpwpb-sidebar-close"><i class="fas fa-times"></i></span>
                        <p>Add F.A.Q.</p>
                        <input type="text">
                        <?php $this->content_editor($post_id);?>
                    </div>
                </div>
                <script>

                </script>
            </div>
            <?php
        }

        function content_editor($post_id) {   
            add_action('admin_init', [$this,'my_enqueue_editor_styles']);     
            $content = get_post_meta($post_id, 'mpwpb_faq_content[]', true);
            wp_editor($content, 'my_meta_box_editor', array(
                'textarea_name' => 'my_meta_box_content',
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
    }
    new MPWPB_Faq_Settings();
}