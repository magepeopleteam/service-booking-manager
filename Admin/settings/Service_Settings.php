<?php
/**
 * @author Rubel Mia rubelcuet10@gmail.com>
 * @license mage-people.com
 * @var 1.0.0
 */
if (!defined('ABSPATH'))
    die;
if (!class_exists('Service_Settings')) {
    class Service_Settings{

        public function __construct() {
            add_action('add_mpwpb_settings_tab_content', [$this, 'service_settings']);
            add_action('save_post', [$this, 'add_custom_meta_on_post_create'], 10, 3);
        }

        function add_custom_meta_on_post_create( $post_id, $post, $update ) {
            if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
            if ($update) return;
            if ( $post->post_type === 'mpwpb_item') {
                add_post_meta($post_id, 'mpwpb_service_multiple_category_check', 'on', true);
                add_post_meta($post_id, 'mpwpb_multiple_service_select', 'on', true);
            }
        }
        public function service_settings( $post_id ){
            $multiple_category_check = get_post_meta( $post_id, 'mpwpb_service_multiple_category_check', true );
            $multiple_service_check = get_post_meta( $post_id, 'mpwpb_multiple_service_select', true );
            $checked = '';
            $mp_service_checked = '';
            if( $multiple_category_check === 'on' ){
                $checked = 'checked';
            }
            if( $multiple_service_check === 'on' ){
                $mp_service_checked = 'checked';
            }
            ?>

            <div class="tabsItem" data-tabs="#mpwpb_service_settings">
                <header>
                    <h2><?php esc_html_e('Service Settings', 'service-booking-manager'); ?></h2>
                    <span><?php esc_html_e('Service Settings will be here.', 'service-booking-manager'); ?></span>
                </header>
                <section>
                    <label class="label">
                        
                        <p><?php esc_html_e('Enable Multiple Category Check', 'service-booking-manager'); ?></p>
                        
                        <label class="roundSwitchLabel">
                            <input type="checkbox" name="mpwpb_service_multiple_category_check" <?php echo esc_attr( $checked )?>>
                            <span class="roundSwitch" data-collapse-target="#mpwpb_service_multiple_category_check"></span>
                        </label>
                    </label>
                </section>
                <section>
                    <label class="label">
                        <p><?php esc_html_e('Enable Multiple Service Select', 'service-booking-manager'); ?></p>
                        
                        <label class="roundSwitchLabel">
                            <input type="checkbox" name="mpwpb_multiple_service_select" <?php echo esc_attr( $mp_service_checked )?>>
                            <span class="roundSwitch" data-collapse-target="#mpwpb_multiple_service_select"></span>
                        </label>
                    </label>
                </section>
            </div>
        <?php }

    }

    new Service_Settings();
}