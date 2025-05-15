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
        }

        public function service_settings( $post_id ){
            $multiple_category_check = get_post_meta( $post_id, 'mpwpb_service_multiple_category_check', true );
            $checked = '';
            if( $multiple_category_check === 'on' ){
                $checked = 'checked';
            }
            ?>

            <div class="tabsItem" data-tabs="#mpwpb_service_settings">
                <header>
                    <h2><?php esc_html_e('Service Settings', 'service-booking-manager'); ?></h2>
                    <span><?php esc_html_e('Service Settings will be here.', 'service-booking-manager'); ?></span>
                </header>
                <section>
                    <label class="label">
                        <div>
                            <p><?php esc_html_e('Enable Multiple Category Check', 'service-booking-manager'); ?></p>
                            <span><?php esc_html_e('Enable Multiple Category Check', 'service-booking-manager'); ?></span>
                        </div>
                        <div>
                            <label class="roundSwitchLabel">
                                <input type="checkbox" name="mpwpb_service_multiple_category_check" <?php echo esc_attr( $checked )?>>
                                <span class="roundSwitch" data-collapse-target="#mpwpb_service_multiple_category_check"></span>
                            </label>
                        </div>
                    </label>
                </section>
            </div>
        <?php }

    }

    new Service_Settings();
}