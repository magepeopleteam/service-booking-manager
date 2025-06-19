<?php
/**
 * @author Rubel Mia rubelcuet10@gmail.com>
 * @license mage-people.com
 * @version 1.0.0
 */
if (!defined('ABSPATH')) {
    die;
}

if (!class_exists('Staff_Member')) {
    class Staff_Member {
        public function __construct() {
            add_action('add_mpwpb_settings_tab_content', [$this, 'staff_member_settings']);
            add_action('mpwpb_settings_save', [$this, 'add_staff_member_meta_on_post_create'], 10, 1);
            add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
            add_action('wp_ajax_save_selected_staff_meta', [$this, 'save_selected_staff_meta']);
        }

        public function enqueue_admin_assets() {

            wp_enqueue_script('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', ['jquery'], null, true);
            wp_enqueue_style('select2-css', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css');

            wp_add_inline_script('select2', "
                jQuery(document).ready(function($) {
                    $('#mpwpb_staff_selector').select2({
                        placeholder: 'Select staff',
                        closeOnSelect: false
                    });

                    $('#mpwpb_staff_selector').on('change', function() {
                        var selectedStaff = $(this).val();
                        var postId = $('#post_ID').val();
                        
                        $.post(ajaxurl, {
                            action: 'save_selected_staff_meta',
                            staff_ids: selectedStaff,
                            post_id: postId,
                        }, function(response) {
                            if (response.success) {
                                console.log('Saved');
                            } else {
                                console.log('Failed');
                            }
                        });
                    });
                });
            ");
        }

        public function add_staff_member_meta_on_post_create($post_id) {
            if (!isset($_POST['mpwpb_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['mpwpb_nonce'])), 'mpwpb_nonce')) {
                return;
            }

            $enable_staff_member = isset($_POST['mpwpb_staff_member_add']) ? sanitize_text_field(wp_unslash($_POST['mpwpb_staff_member_add'])) : 'no';
            update_post_meta($post_id, 'mpwpb_staff_member_add', $enable_staff_member);
        }

        public function staff_member_settings($post_id) {
            $checked = '';
            $enable_staff_member = get_post_meta($post_id, 'mpwpb_staff_member_add', true);
            if ($enable_staff_member === 'on') {
                $checked = 'checked';
            }

            $selected_staff_ids = get_post_meta($post_id, 'mpwpb_selected_staff_ids', true);
            if (!is_array($selected_staff_ids)) {
                $selected_staff_ids = [];
            }

            $users = get_users(['role__in' => ['mpwpb_staff']]); // Customize as needed
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
                                <input type="checkbox" name="mpwpb_staff_member_add" <?php echo esc_attr($checked) ?>>
                                <span class="roundSwitch" data-collapse-target="#mpwpb_staff_member_add"></span>
                            </label>
                        </div>
                    </label>
                </section>

                <section>
                    <label for="mpwpb_staff_selector"><?php esc_html_e('Select Staff', 'service-booking-manager'); ?></label>
                    <select id="mpwpb_staff_selector" multiple style="width: 100%;">
                        <?php foreach ($users as $user): ?>
                            <option value="<?php echo esc_attr($user->ID); ?>" <?php selected(in_array($user->ID, $selected_staff_ids), true); ?>>
                                <?php echo esc_html($user->display_name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </section>
            </div>
            <?php
        }

        public function save_selected_staff_meta() {
            $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
            $staff_ids = isset($_POST['staff_ids']) ? array_map('intval', $_POST['staff_ids']) : [];

            if ($post_id && current_user_can('edit_post', $post_id)) {
                update_post_meta($post_id, 'mpwpb_selected_staff_ids', $staff_ids);
                wp_send_json_success();
            } else {
                wp_send_json_error();
            }
        }
    }

    new Staff_Member();
}
