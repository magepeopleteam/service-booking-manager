<?php
/**
 * @author Rubel Mia rubelcuet10@gmail.com>
 * @license mage-people.com
 * @var 1.0.0
 */
if (!defined('ABSPATH'))
    die;
if (!class_exists('Staff_Member')) {
    class Staff_Member
    {
        public function __construct() {
            add_action('add_mpwpb_settings_tab_content', [$this, 'staff_member_settings']);
            add_action('mpwpb_settings_save', [$this, 'add_staff_member_meta_on_post_create'], 10, 1);
        }

        function add_staff_member_meta_on_post_create( $post_id ) {
            if (!isset($_POST['mpwpb_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['mpwpb_nonce'])), 'mpwpb_nonce')) {
                return;
            }
            $enable_staff_member = isset($_POST['mpwpb_staff_member_add']) ? sanitize_text_field(wp_unslash($_POST['mpwpb_staff_member_add'])) : 'no';
            update_post_meta( $post_id, 'mpwpb_staff_member_add', $enable_staff_member );
        }
        public function staff_member_settings( $post_id ){
            $checked = '';
            $enable_staff_member = get_post_meta( $post_id, 'mpwpb_staff_member_add', true );
            if( $enable_staff_member === 'on' ){
                $checked = 'checked';
            }
            ?>

            <div class="tabsItem" data-tabs="#mpwpb_staff_members">
                <header>
                    <h2><?php esc_html_e('Staff Member Settings', 'service-booking-manager'); ?></h2>
                    <span><?php esc_html_e('Staff Member will be here.', 'service-booking-manager'); ?></span>
                </header>
                <section>
                    <label class="label">
                        <div>
                            <p><?php esc_html_e('Enable Staff Member Add', 'service-booking-manager'); ?></p>
                            <span><?php esc_html_e('Enable Staff Member Add', 'service-booking-manager'); ?></span>
                        </div>
                        <div>
                            <label class="roundSwitchLabel">
                                <input type="checkbox" name="mpwpb_staff_member_add" <?php echo esc_attr( $checked )?>>
                                <span class="roundSwitch" data-collapse-target="#mpwpb_staff_member_add"></span>
                            </label>
                        </div>
                    </label>
                </section>

            </div>
        <?php }

    }

    new Staff_Member();
}