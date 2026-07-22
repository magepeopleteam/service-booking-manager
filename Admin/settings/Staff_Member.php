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
            // No dropdown widget (Select2 or otherwise) for this field --
            // just a plain clickable grid of staff tiles, so this handler
            // only needs the tile click-to-toggle + save-on-change wiring.
            wp_enqueue_script('jquery');
            wp_add_inline_script('jquery', "
                jQuery(document).ready(function($) {
                    $(document).on('click', '.mpwpb-staff-tile', function() {
                        var \$tile = $(this);
                        var id = \$tile.data('staff-id');
                        var isSelected = \$tile.toggleClass('is-selected').hasClass('is-selected');
                        $('#mpwpb_staff_selector option[value=\"' + id + '\"]').prop('selected', isSelected);
                        $('#mpwpb_staff_selector').trigger('change');
                    });

                    $('#mpwpb_staff_selector').on('change', function() {
                        var selectedStaff = $(this).val();
                        var postId = $('#post_ID').val();

                        $.post(ajaxurl, {
                            action: 'save_selected_staff_meta',
                            staff_ids: selectedStaff,
                            post_id: postId,
                            nonce: mpwpb_admin_ajax.nonce,
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
            // Verify nonce
            if (!isset($_POST['mpwpb_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['mpwpb_nonce'])), 'mpwpb_nonce')) {
                return;
            }

            // Save staff member enable setting
            $enable_staff_member = isset($_POST['mpwpb_staff_member_add']) ? sanitize_text_field(wp_unslash($_POST['mpwpb_staff_member_add'])) : 'no';
            update_post_meta($post_id, 'mpwpb_staff_member_add', $enable_staff_member);
        }

        public function staff_member_settings($post_id) {
            $checked = '';
            $load_selection = 'none';
            $enable_staff_member = get_post_meta($post_id, 'mpwpb_staff_member_add', true);
            if ($enable_staff_member === 'on') {
                $checked = 'checked';

                $load_selection = 'block';
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
                                <input type="checkbox" class="mpwpb_staff_member_add" name="mpwpb_staff_member_add" <?php echo esc_attr($checked) ?>>
                                <span class="roundSwitch" data-collapse-target="#mpwpb_staff_member_add"></span>
                            </label>
                        </div>
                    </label>
                </section>

                <section id="mpwpb_add_staff_container" style="display: <?php echo esc_attr( $load_selection );?>">
                    <div class="mpwpb_add_staff_container" >
                        <label><?php esc_html_e('Select Staff', 'service-booking-manager'); ?></label>
                        <!-- Plain clickable grid, no dropdown/popup -- every staff
                             member is always visible; clicking a tile toggles it.
                             The real <select multiple> below stays in sync (kept
                             for the existing save-on-change AJAX, which reads its
                             .val()) but is hidden -- it's never interacted with
                             directly. -->
                        <div class="mpwpb-staff-grid" id="mpwpb_staff_grid">
                            <?php foreach ($users as $user):
                                $attachment_id = get_user_meta($user->ID, 'mpwpb_custom_profile_image', true);
                                $avatar_url = $attachment_id ? wp_get_attachment_image_url($attachment_id, 'thumbnail') : '';
                                if (!$avatar_url) {
                                    $avatar_url = get_avatar_url($user->ID, ['size' => 96]);
                                }
                                $is_selected = in_array($user->ID, $selected_staff_ids);
                                ?>
                                <button type="button" class="mpwpb-staff-tile<?php echo $is_selected ? ' is-selected' : ''; ?>" data-staff-id="<?php echo esc_attr($user->ID); ?>">
                                    <span class="mpwpb-staff-tile-check"><span class="dashicons dashicons-yes"></span></span>
                                    <img class="mpwpb-staff-tile-avatar" src="<?php echo esc_url($avatar_url); ?>" alt=""/>
                                    <span class="mpwpb-staff-tile-name"><?php echo esc_html($user->display_name); ?></span>
                                </button>
                            <?php endforeach; ?>
                        </div>
                        <select id="mpwpb_staff_selector" multiple style="display:none;">
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo esc_attr($user->ID); ?>" <?php selected(in_array($user->ID, $selected_staff_ids), true); ?>></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                </section>
                <style>
                    .mpwpb-staff-grid {
                        display: flex;
                        flex-wrap: wrap;
                        gap: 10px;
                    }
                    .mpwpb-staff-tile {
                        display: flex;
                        flex-direction: column;
                        align-items: center;
                        position: relative;
                        width: 88px;
                        margin: 0;
                        padding: 10px 8px 8px;
                        border: 1px solid #e5e7eb;
                        border-radius: 12px;
                        background: #fff;
                        box-shadow: 0 1px 3px rgba(15, 23, 42, .06);
                        cursor: pointer;
                    }
                    .mpwpb-staff-tile.is-selected {
                        border-color: var(--mpwpb_color_theme);
                        background: #f0f7ff;
                    }
                    .mpwpb-staff-tile-avatar {
                        width: 48px;
                        height: 48px;
                        border-radius: 50%;
                        object-fit: cover;
                        background: #f1f5f9;
                        margin-bottom: 6px;
                    }
                    /* Doubled classes below (.foo.foo) deliberately bump
                       specificity to (0,2,0) -- mpwpb_plugin_global.css has a
                       shared rule, `.mpwpb_style span{display:flex}` (0,1,1),
                       that would otherwise beat a plain single-class rule on
                       any <span> here (both of these are spans), silently
                       forcing the "selected" checkmark to always show and
                       breaking this name label's centering. */
                    .mpwpb-staff-tile-name.mpwpb-staff-tile-name {
                        display: block;
                        font-size: 12px;
                        font-weight: 600;
                        color: #334155;
                        text-align: center;
                        max-width: 100%;
                        overflow: hidden;
                        text-overflow: ellipsis;
                        white-space: nowrap;
                    }
                    .mpwpb-staff-tile-check.mpwpb-staff-tile-check {
                        position: absolute;
                        top: 4px;
                        right: 4px;
                        width: 16px;
                        height: 16px;
                        display: none;
                        align-items: center;
                        justify-content: center;
                        border-radius: 50%;
                        background: var(--mpwpb_color_theme);
                        color: #fff;
                        font-size: 10px;
                        line-height: 1;
                    }
                    .mpwpb-staff-tile-check .dashicons {
                        font-size: 10px;
                        width: 10px;
                        height: 10px;
                    }
                    .mpwpb-staff-tile.is-selected .mpwpb-staff-tile-check.mpwpb-staff-tile-check {
                        display: flex;
                    }
                </style>
            </div>
            <?php
        }

        public function save_selected_staff_meta() {
            // Verify nonce

            if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'mpwpb_admin_nonce')) {
                wp_send_json_error('Invalid nonce!');
            }
            
            $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
            $staff_ids = isset($_POST['staff_ids']) ? array_map('intval', $_POST['staff_ids']) : [];

            if ($post_id && current_user_can('edit_post', $post_id)) {
                // Save the selected staff IDs as an array
                update_post_meta($post_id, 'mpwpb_selected_staff_ids', $staff_ids);
                wp_send_json_success('Staff members saved successfully');
            } else {
                wp_send_json_error('Invalid post ID or insufficient permissions');
            }
        }
    }

    new Staff_Member();
}
