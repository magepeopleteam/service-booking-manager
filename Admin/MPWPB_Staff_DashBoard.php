<?php
/*
* @Author 		rubelcuet10@gmail.com
* Copyright: 	mage-people.com
*/
if (!defined('ABSPATH')) {
    die;
} // Cannot access pages directly.
if (!class_exists('MPWPB_Staff_DashBoard')) {
    class MPWPB_Staff_DashBoard{
        public function __construct(){
            add_action('show_user_profile', [ $this, 'mpwpb_add_custom_user_profile_fields']);
            add_action('edit_user_profile', [ $this, 'mpwpb_add_custom_user_profile_fields']);

            add_action('personal_options_update', [ $this, 'mpwpb_save_custom_user_profile_fields']);
            add_action('edit_user_profile_update', [ $this, 'mpwpb_save_custom_user_profile_fields']);

            add_action('wp_dashboard_setup', [ $this, 'mpwpb_add_dashboard_widget'] );
        }

        public function mpwpb_add_dashboard_widget() {
            wp_add_dashboard_widget(
                'mpwpb_staff_dashboard_widget',
                __('Your Staff Info', 'service-booking-manager'),
                [$this, 'mpwpb_show_staff_info_on_dashboard']
            );
        }


        function mpwpb_show_staff_info_on_dashboard() {
            $current_user = wp_get_current_user();

            // Only show for staff
            if (in_array('mpwpb_staff', (array) $current_user->roles)) {
                $phone = get_user_meta($current_user->ID, 'staff_phone', true);
                $skill = get_user_meta($current_user->ID, 'staff_skill', true);
                echo '<div class="mpwpb-staff-top-box">';
                echo '<h3>👷 Staff Profile</h3>';
                echo '<p><strong>Name:</strong> ' . esc_html($current_user->display_name) . '</p>';
                echo '<p><strong>Phone:</strong> ' . esc_html($phone) . '</p>';
                echo '<p><strong>Skill:</strong> ' . esc_html($skill) . '</p>';
                echo '</div>';
            }
        }

        function mpwpb_add_custom_user_profile_fields($user) {
            if (!in_array('mpwpb_staff', (array) $user->roles)) {
                return;
            }
            ?>
            <h3><?php esc_html_e('Staff Extra Information', 'service-booking-manager'); ?></h3>
            <table class="form-table">
                <tr>
                    <th><label for="staff_phone"><?php esc_html_e('Phone Number', 'service-booking-manager'); ?></label></th>
                    <td>
                        <input type="text" name="staff_phone" id="staff_phone" value="<?php echo esc_attr(get_user_meta($user->ID, 'staff_phone', true)); ?>" class="regular-text" />
                    </td>
                </tr>
                <tr>
                    <th><label for="staff_skill"><?php esc_html_e('Skill/Expertise', 'service-booking-manager'); ?></label></th>
                    <td>
                        <input type="text" name="staff_skill" id="staff_skill" value="<?php echo esc_attr(get_user_meta($user->ID, 'staff_skill', true)); ?>" class="regular-text" />
                    </td>
                </tr>
            </table>
            <?php

//                $this->staff_form($user->ID);
        }

        function mpwpb_save_custom_user_profile_fields($user_id) {
            if (!current_user_can('edit_user', $user_id)) {
                return false;
            }

            if (isset($_POST['staff_phone'])) {
                update_user_meta($user_id, 'staff_phone', sanitize_text_field($_POST['staff_phone']));
            }

            if (isset($_POST['staff_skill'])) {
                update_user_meta($user_id, 'staff_skill', sanitize_text_field($_POST['staff_skill']));
            }
        }

    }

    new MPWPB_Staff_DashBoard();
}