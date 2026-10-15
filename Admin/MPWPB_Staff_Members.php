<?php
if (!defined('ABSPATH')) {
    die;
} // Cannot access pages directly.
if (!class_exists('MPWPB_Staff_Members')) {
    class MPWPB_Staff_Members{

        public function __construct() {
            add_action('admin_menu', array($this, 'staffs_menu'));

            add_action('wp_ajax_get_mpwpb_get_staff_form', array($this, 'get_mpwpb_get_staff_form'));
            /*********************************************/
            add_action('wp_ajax_mpwpb_delete_staff', array($this, 'mpwpb_delete_staff'));
            /************************/
            add_action('wp_ajax_get_mpwpb_staff_end_time_slot', array($this, 'get_mpwpb_staff_end_time_slot'));
            /***/
            add_action('wp_ajax_get_mpwpb_staff_start_break_time', array($this, 'get_mpwpb_staff_start_break_time'));
            /***/
            add_action('wp_ajax_get_mpwpb_staff_end_break_time', array($this, 'get_mpwpb_staff_end_break_time'));
            add_action('wp_ajax_mpwpb_check_staff_availability', array($this, 'check_staff_availability'));
        }
        /** Pre-submit check for the Add Staff form: is this username/email already registered? */
        public function check_staff_availability() {
            check_ajax_referer('mpwpb_admin_nonce', 'nonce');
            if (!current_user_can('create_users')) {
                wp_send_json_error(array('message' => 'forbidden'), 403);
            }
            $login = isset($_POST['user_login']) ? sanitize_user(wp_unslash($_POST['user_login'])) : '';
            $email = isset($_POST['user_email']) ? sanitize_email(wp_unslash($_POST['user_email'])) : '';
            wp_send_json_success(array(
                'username_taken' => $login !== '' && (bool) username_exists($login),
                'email_taken' => $email !== '' && (bool) email_exists($email),
            ));
        }
        private function ensure_staff_manage_permission(): bool {
            if (!current_user_can('create_users')) {
                wp_send_json_error('Unauthorized request');
                return false;
            }
            return true;
        }

        public function staffs_menu() {
            $cpt = MPWPB_Function::get_cpt();
            add_submenu_page('edit.php?post_type=' . $cpt, esc_html__('Staff Members', 'service-booking-manager'), esc_html__('Staff Members', 'service-booking-manager'), 'manage_options', 'mpwpb_staffs', array($this, 'mpwpb_staff_service'));
        }

        public function save_custom_user_profile_image( $user_id ) {
            $profile_image = isset( $_POST['mpwpb_custom_profile_image'] ) ? sanitize_text_field( $_POST['mpwpb_custom_profile_image'] ) : '';
            update_user_meta( $user_id, 'mpwpb_custom_profile_image', intval( $profile_image ) );
        }

        public static function get_custom_user_profile_image ( $user_id, $size = 'thumbnail', $class_name = '' ) {
            $attachment_id = get_user_meta( $user_id, 'mpwpb_custom_profile_image', true);
            if ( $attachment_id ) {
                $image = wp_get_attachment_image($attachment_id, $size, false, ['class' => $class_name ] );
            } else {
                $image =  get_avatar( $user_id, 70); // fallback to default avatar
            }

            return $image;
        }
        public function custom_user_profile_image_field( $user_id ) {
            $image_url = esc_url( wp_get_attachment_url( get_user_meta( $user_id, 'mpwpb_custom_profile_image', true) ) );
            if ( ! $image_url ) {
                $image_url = MPWPB_PLUGIN_URL . '/mp_global/assets/images/staff_fallback.webp';
            }
            ?>
            <div class="mpwpb-sm__photo">
                <input type="hidden" name="mpwpb_custom_profile_image" id="mpwpb_custom_profile_image" value="<?php echo esc_attr(get_user_meta( $user_id, 'mpwpb_custom_profile_image', true)); ?>" />
                <span class="mpwpb-sm__avatar mpwpb-sm__avatar--lg"><img src="<?php echo esc_attr( $image_url );?>" id="mpwpb_custom_profile_image_preview" alt="" /></span>
            </div>
            <?php
        }
        /** "Change photo / Remove photo" -- same button ids the media-uploader script binds to. */
        public function profile_image_buttons() {
            ?>
            <div class="mpwpb-sm__photo-actions">
                <button type="button" class="mpwpb-sm__btn mpwpb-sm__btn--sm" id="upload_profile_image_button"><?php esc_html_e('Change photo', 'service-booking-manager'); ?></button>
                <button type="button" class="mpwpb-sm__btn mpwpb-sm__btn--sm mpwpb-sm__btn--quiet-danger" id="remove_profile_image_button"><?php esc_html_e('Remove photo', 'service-booking-manager'); ?></button>
            </div>
            <?php
        }
        public function mpwpb_staff_service() {
            $save_result = $this->save_staff();
            // After a save, keep that staff member open in the editor.
            $edit_id = (is_array($save_result) && !empty($save_result['user_id'])) ? (int) $save_result['user_id'] : '';
            ?>
            <div class="wrap">
                <div class="mpwpb_style mpwpb_staff_page mpwpb-sm">
                    <header class="mpwpb-sm__head">
                        <div class="mpwpb-sm__head-text">
                            <h1><?php esc_html_e('Staff', 'service-booking-manager'); ?></h1>
                            <p><?php esc_html_e('Who can be booked, and when they work.', 'service-booking-manager'); ?></p>
                        </div>
                        <button type="button" class="mpwpb-sm__btn mpwpb-sm__btn--primary" data-sm-add>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                            <?php esc_html_e('Add staff', 'service-booking-manager'); ?>
                        </button>
                    </header>
                    <?php if (is_array($save_result) && !empty($save_result['message'])) { ?>
                        <p class="mpwpb-sm__flash is-<?php echo esc_attr($save_result['type'] === 'success' ? 'success' : 'error'); ?>" role="status"><?php echo esc_html($save_result['message']); ?></p>
                    <?php } ?>
                    <div class="mpwpb-sm__layout">
                        <aside class="mpwpb-sm__aside" aria-label="<?php esc_attr_e('Staff list', 'service-booking-manager'); ?>">
                            <div class="mpwpb-sm__search">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg>
                                <input type="search" data-sm-search placeholder="<?php esc_attr_e('Search staff', 'service-booking-manager'); ?>" aria-label="<?php esc_attr_e('Search staff', 'service-booking-manager'); ?>">
                            </div>
                            <div class="mpwpb_staff_list" id="mpwpb_staff_lists_holder">
                                <?php $this->staff_list(); ?>
                            </div>
                        </aside>
                        <div class="mpwpb-sm__main mpwpb_add_staff" id="mpwpb_staff_members_holder">
                            <?php $this->staff_form($edit_id); ?>
                        </div>
                    </div>
                </div>
            </div>
            <style>
                div.notice,
                #update-nag, .update-nag {display: none;}
            </style>
            <?php
        }
        /** Working-days count for a list card: "7 days", "5 days", or "3 dates" for one-time schedules. */
        private function staff_days_label($user_id) {
            $date_type = get_user_meta($user_id, 'date_type', true) ?: 'repeated';
            if ($date_type === 'particular') {
                $dates = get_user_meta($user_id, 'mpwpb_particular_dates', true);
                $n = is_array($dates) ? count(array_filter($dates)) : 0;
                /* translators: %d: number of dates */
                return sprintf(_n('%d date', '%d dates', $n, 'service-booking-manager'), $n);
            }
            $off = array_filter(explode(',', (string) get_user_meta($user_id, 'mpwpb_off_days', true)));
            $n = max(0, count(MPWPB_Global_Function::week_day()) - count($off));
            /* translators: %d: number of working days a week */
            return sprintf(_n('%d day', '%d days', $n, 'service-booking-manager'), $n);
        }
        public function staff_list() {
            $all_staffs = get_users(['role' => 'mpwpb_staff']);
            if (sizeof($all_staffs) > 0) {
                ?>
                <ul class="mpwpb-sm__list">
                    <?php foreach ($all_staffs as $staff) {
                        $staff_img = $this->get_custom_user_profile_image($staff->ID, 'thumbnail', 'mpwpb_staff_image');
                        $search = strtolower($staff->display_name . ' ' . $staff->user_login . ' ' . $staff->user_email);
                        ?>
                        <li class="mpwpb-sm__item" data-staff-id="<?php echo esc_attr($staff->ID); ?>" data-search="<?php echo esc_attr($search); ?>">
                            <button type="button" class="mpwpb-sm__pick" id="mpwpb_edit_staff" data-staff-id="<?php echo esc_attr($staff->ID); ?>">
                                <span class="mpwpb-sm__avatar"><?php echo wp_kses_post($staff_img); ?></span>
                                <span class="mpwpb-sm__pick-text">
                                    <span class="mpwpb-sm__pick-name"><?php echo esc_html($staff->display_name); ?></span>
                                    <span class="mpwpb-sm__pick-mail"><?php echo esc_html($staff->user_email); ?></span>
                                </span>
                                <span class="mpwpb-sm__days"><?php echo esc_html($this->staff_days_label($staff->ID)); ?></span>
                            </button>
                            <?php // Kept for the existing delete handler; the editor's "Remove staff" clicks it. ?>
                            <button type="button" class="mpwpb-sm__del" id="mpwpb_delete_staff" data-staff-id="<?php echo esc_attr($staff->ID); ?>" hidden><?php echo esc_html__('Remove staff.', 'service-booking-manager') . ' : ' . esc_html($staff->display_name); ?></button>
                        </li>
                    <?php } ?>
                </ul>
                <p class="mpwpb-sm__count" data-sm-count><?php
                    /* translators: %d: number of staff members */
                    echo esc_html(sprintf(_n('%d staff member', '%d staff members', count($all_staffs), 'service-booking-manager'), count($all_staffs)));
                ?></p>
                <p class="mpwpb-sm__none" data-sm-nomatch hidden><?php esc_html_e('No staff match your search.', 'service-booking-manager'); ?></p>
            <?php } else { ?>
                <p class="mpwpb-sm__empty"><?php esc_html_e('No staff yet. Use "Add staff" to create the first one.', 'service-booking-manager'); ?></p>
                <?php
            }
        }
        public function staff_form($user_id = '') {
            if (!current_user_can('create_users')) {
                ?>
                <h3><?php esc_html_e('You have no permission to create staff', 'service-booking-manager'); ?></h3><?php
            } else {
                $users = get_users(array('role__not_in' => array('administrator')));
                $user_info = $user_id ? get_userdata($user_id) : false;
                if (!$user_info) {
                    $user_id = '';
                }
                $staff_name = $user_id ? $user_info->user_login : '';
                $staff_email = $user_id ? $user_info->user_email : '';
                $staff_first_name = $user_id ? $user_info->first_name : '';
                $staff_last_name = $user_id ? $user_info->last_name : '';

                $approve_holiday_modify = get_user_meta( $user_id, 'mpwpb_staff_modify_holiday', true);
                ?>
                <form action="" method="post" class="mpwpb_staff_form mpwpb-sm__form" novalidate>
                    <?php wp_nonce_field('mpwpb_add_staff_nonce', 'mpwpb_add_staff_nonce'); ?>
                    <input type="hidden" id="mpwpb_user_id" name="mpwpb_user_id" value="<?php echo esc_attr($user_id); ?>"/>

                    <section class="mpwpb-sm__card" aria-labelledby="mpwpb-sm-profile-title">
                        <div class="mpwpb-sm__profile-head">
                            <?php $this->custom_user_profile_image_field( $user_id ); ?>
                            <div class="mpwpb-sm__who">
                                <h2 id="mpwpb-sm-profile-title"><?php echo $user_id ? esc_html($user_info->display_name) : esc_html__('New staff member', 'service-booking-manager'); ?></h2>
                                <?php $this->profile_image_buttons(); ?>
                            </div>
                            <?php if ($user_id) { ?>
                                <button type="button" class="mpwpb-sm__btn mpwpb-sm__btn--danger" data-sm-remove="<?php echo esc_attr($user_id); ?>"><?php esc_html_e('Remove staff', 'service-booking-manager'); ?></button>
                            <?php } ?>
                        </div>

                        <div class="mpwpb-sm__grid">
                            <label class="mpwpb-sm__field">
                                <span><?php esc_html_e('WordPress user', 'service-booking-manager'); ?></span>
                                <select name="mpwpb_user" class="formControl mpwpb_user_select">
                                    <option value=" "><?php esc_html_e('Add new user', 'service-booking-manager'); ?></option>
                                    <?php foreach ($users as $user) { ?>
                                        <option value="<?php echo esc_attr($user->ID); ?>" <?php selected($user_id, $user->ID); ?>><?php echo esc_html(ucfirst($user->data->display_name)); ?></option>
                                    <?php } ?>
                                </select>
                            </label>
                            <label class="mpwpb-sm__field">
                                <span><?php esc_html_e('Username', 'service-booking-manager'); ?></span>
                                <input type="text" class="formControl mpwpb_id_validation" name="mpwpb_user_name" value="<?php echo esc_attr($staff_name); ?>" placeholder="<?php esc_attr_e('At least 3 characters', 'service-booking-manager'); ?>" autocomplete="off" <?php disabled((bool) $user_id); ?> required/>
                            </label>
                            <label class="mpwpb-sm__field">
                                <span><?php esc_html_e('First name', 'service-booking-manager'); ?></span>
                                <input type="text" class="formControl mpwpb_name_validation" name="mpwpb_staff_first_name" value="<?php echo esc_attr($staff_first_name); ?>" placeholder="<?php esc_attr_e('First name', 'service-booking-manager'); ?>"/>
                            </label>
                            <label class="mpwpb-sm__field">
                                <span><?php esc_html_e('Last name', 'service-booking-manager'); ?></span>
                                <input type="text" class="formControl mpwpb_name_validation" name="mpwpb_staff_last_name" value="<?php echo esc_attr($staff_last_name); ?>" placeholder="<?php esc_attr_e('Last name', 'service-booking-manager'); ?>"/>
                            </label>
                            <label class="mpwpb-sm__field">
                                <span><?php esc_html_e('Email', 'service-booking-manager'); ?></span>
                                <input type="email" class="formControl" name="mpwpb_user_mail" value="<?php echo esc_attr($staff_email); ?>" placeholder="<?php esc_attr_e('name@example.com', 'service-booking-manager'); ?>" required/>
                            </label>
                            <?php if ($user_id) { ?>
                                <div class="mpwpb-sm__field">
                                    <span><?php esc_html_e('Password', 'service-booking-manager'); ?></span>
                                    <p class="mpwpb-sm__note"><?php esc_html_e('Set when the account was created. The staff member can change it from their WordPress profile.', 'service-booking-manager'); ?></p>
                                </div>
                            <?php } else { ?>
                                <label class="mpwpb-sm__field">
                                    <span><?php esc_html_e('Password', 'service-booking-manager'); ?></span>
                                    <input type="password" class="formControl" name="mpwpb_user_password" value="" placeholder="<?php esc_attr_e('At least 8 characters', 'service-booking-manager'); ?>" autocomplete="new-password" required/>
                                </label>
                            <?php } ?>
                        </div>

                        <label class="mpwpb-sm__toggle-row">
                            <span class="mpwpb-sm__toggle-text">
                                <strong><?php esc_html_e('Can manage own holidays', 'service-booking-manager'); ?></strong>
                                <span><?php esc_html_e('Lets this staff member add days off from their account.', 'service-booking-manager'); ?></span>
                            </span>
                            <input type="hidden" name="mpwpb_staff_modify_holiday" value="no"/>
                            <input type="checkbox" name="mpwpb_staff_modify_holiday" value="yes" <?php checked($approve_holiday_modify, 'yes'); ?>/>
                        </label>
                    </section>

                    <?php // .mpPanelBody: the schedule-type pills and "Copy Monday to all" look up their select/table inside it (mpwpb_admin.js). ?>
                    <section class="mpwpb-sm__card mpPanelBody mpwpb-sm__hours" aria-labelledby="mpwpb-sm-hours-title">
                        <?php $this->general_settings($user_id); ?>
                        <?php $this->schedule_settings($user_id); ?>
                        <?php $this->off_on_day_settings($user_id); ?>
                    </section>

                    <div class="mpwpb-sm__actions">
                        <button class="mpwpb-sm__btn" type="button" id="mpwpb_staff_form_cancel" data-sm-cancel><?php esc_html_e('Cancel', 'service-booking-manager'); ?></button>
                        <button class="mpwpb-sm__btn mpwpb-sm__btn--primary" type="submit"><?php echo $user_id ? esc_html__('Save changes', 'service-booking-manager') : esc_html__('Add staff member', 'service-booking-manager'); ?></button>
                    </div>
                </form>
                <?php
            }
        }
        public function general_settings($user_id = '') {
            $date_type = $user_id ? get_user_meta($user_id, 'date_type') : [];
            $date_type = sizeof($date_type) > 0 ? current($date_type) : 'repeated';
            $date_format = MPWPB_Global_Function::date_picker_format();
            $now = date_i18n($date_format, strtotime(current_time('Y-m-d')));
            $repeated_start_date = $user_id ? get_user_meta($user_id, 'mpwpb_repeated_start_date') : [];
            $repeated_start_date = sizeof($repeated_start_date) > 0 ? current($repeated_start_date) : '';
            $hidden_repeated_start_date = $repeated_start_date ? date_i18n('Y-m-d', strtotime($repeated_start_date)) : '';
            $visible_repeated_start_date = $repeated_start_date ? date_i18n($date_format, strtotime($repeated_start_date)) : '';
            $repeated_after = $user_id ? get_user_meta($user_id, 'mpwpb_repeated_after') : [];
            $repeated_after = sizeof($repeated_after) > 0 ? current($repeated_after) : 1;
            ?>
            <div class="mpwpb-sm__hours-head">
                <h2 id="mpwpb-sm-hours-title"><?php esc_html_e('Working hours', 'service-booking-manager'); ?></h2>
                <div class="mpwpb_schedule_type_pills mpwpb-sm__seg" role="group" aria-label="<?php esc_attr_e('Schedule type', 'service-booking-manager'); ?>">
                    <button type="button" class="mpwpb_schedule_type_pill<?php echo esc_attr($date_type == 'repeated' ? ' mpActive' : ''); ?>" data-schedule-type="repeated" aria-pressed="<?php echo $date_type == 'repeated' ? 'true' : 'false'; ?>"><?php esc_html_e('Every week', 'service-booking-manager'); ?></button>
                    <button type="button" class="mpwpb_schedule_type_pill<?php echo esc_attr($date_type == 'particular' ? ' mpActive' : ''); ?>" data-schedule-type="particular" aria-pressed="<?php echo $date_type == 'particular' ? 'true' : 'false'; ?>"><?php esc_html_e('Specific dates', 'service-booking-manager'); ?></button>
                </div>
            </div>
            <select class="formControl mpwpb_visually_hidden" name="mpwpb_date_type" data-collapse-target tabindex="-1" aria-hidden="true">
                <option disabled><?php esc_html_e('Please select ...', 'service-booking-manager'); ?></option>
                <option value="particular" data-option-target="#mp_particular" <?php selected($date_type, 'particular'); ?>><?php esc_html_e('Particular', 'service-booking-manager'); ?></option>
                <option value="repeated" data-option-target="#mp_repeated" <?php selected($date_type, 'repeated'); ?>><?php esc_html_e('Repeated', 'service-booking-manager'); ?></option>
            </select>
            <div data-collapse="#mp_repeated" class="mpwpb-sm__repeat<?php echo esc_attr($date_type == 'repeated' ? ' mActive' : ''); ?>">
                <label class="mpwpb-sm__field">
                    <span><?php esc_html_e('Starts on', 'service-booking-manager'); ?></span>
                    <input type="hidden" name="mpwpb_repeated_start_date" value="<?php echo esc_attr($hidden_repeated_start_date); ?>"/>
                    <input type="text" readonly name="" class="formControl date_type" value="<?php echo esc_attr($visible_repeated_start_date); ?>" placeholder="<?php echo esc_attr($now); ?>"/>
                </label>
                <label class="mpwpb-sm__field">
                    <span><?php esc_html_e('Repeats every', 'service-booking-manager'); ?></span>
                    <span class="mpwpb-sm__inline">
                        <input type="text" inputmode="numeric" name="mpwpb_repeated_after" class="formControl mpwpb_number_validation mpwpb-sm__num" value="<?php echo esc_attr($repeated_after); ?>"/>
                        <?php esc_html_e('week(s)', 'service-booking-manager'); ?>
                    </span>
                </label>
            </div>
            <div data-collapse="#mp_particular" class="mpwpb-sm__dates<?php echo esc_attr($date_type == 'particular' ? ' mActive' : ''); ?>">
                <p class="mpwpb-sm__note"><?php esc_html_e('Works only on these dates, at the usual hours below.', 'service-booking-manager'); ?></p>
                <div class="mp_settings_area">
                    <div class="mp_item_insert mp_sortable_area">
                        <?php
                        $particular_date_lists = $user_id ? get_user_meta($user_id, 'mpwpb_particular_dates') : [];
                        $particular_date_lists = sizeof($particular_date_lists) > 0 ? current($particular_date_lists) : '';
                        if (is_array($particular_date_lists) && sizeof($particular_date_lists)) {
                            foreach ($particular_date_lists as $particular_date) {
                                if ($particular_date) {
                                    MPWPB_Date_Time_Settings::particular_date_item('mpwpb_particular_dates[]', $particular_date);
                                }
                            }
                        }
                        ?>
                    </div>
                    <?php MPWPB_Custom_Layout::add_new_button(esc_html__('Add date', 'service-booking-manager')); ?>
                    <div class="mpwpb_hidden_content">
                        <div class="mpwpb_hidden_item">
                            <?php MPWPB_Date_Time_Settings::particular_date_item('mpwpb_particular_dates[]'); ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php
        }
        public function schedule_settings($user_id = '') {
            $use_24hour = MPWPB_Global_Function::get_settings('mpwpb_global_settings', 'time_format_24hour', 'no');
            $off_days = $user_id ? get_user_meta($user_id, 'mpwpb_off_days') : [];
            $off_days = sizeof($off_days) > 0 ? current($off_days) : '';
            $off_day_keys = array_filter(explode(',', (string) $off_days));
            ?>
            <div class="mpwpb-sm__sched">
                <div class="mpwpb-sm__sched-tools">
                    <button type="button" id="mpwpb_copy_monday_to_all" class="mpwpb-sm__btn mpwpb-sm__btn--sm" title="<?php esc_attr_e('Copy Monday\'s hours to every other day', 'service-booking-manager'); ?>"><?php esc_html_e('Copy Monday to all days', 'service-booking-manager'); ?></button>
                    <span class="mpwpb-sm__fmt" title="<?php esc_attr_e('Set under Settings → 24 Hour Time Format', 'service-booking-manager'); ?>"><?php echo esc_html($use_24hour === 'yes' ? __('24-hour clock', 'service-booking-manager') : __('12-hour clock', 'service-booking-manager')); ?></span>
                </div>
                <table class="mpwpb-sm__table">
                    <tbody>
                    <?php
                    $this->time_slot_tr($user_id, 'default');
                    $days = MPWPB_Global_Function::week_day();
                    foreach ($days as $key => $day) {
                        $this->time_slot_tr($user_id, $key, in_array($key, $off_day_keys, true));
                    }
                    ?>
                    </tbody>
                </table>
            </div>
            <?php
        }
        public function off_on_day_settings($user_id = '') {
            $date_type = $user_id ? get_user_meta($user_id, 'date_type') : [];
            $date_type = sizeof($date_type) > 0 ? current($date_type) : 'repeated';
            $off_days = $user_id ? get_user_meta($user_id, 'mpwpb_off_days') : [];
            $off_days = sizeof($off_days) > 0 ? current($off_days) : '';
            $days = MPWPB_Global_Function::week_day();
            $off_day_array = explode(',', $off_days);
            ?>
            <div class="mpwpb-sm__offdays<?php echo esc_attr($date_type == 'repeated' ? ' mActive' : ''); ?>" data-collapse="#mp_repeated">
                <?php // Weekly off days: driven by the day switches above (mpwpb-staff-management-modern.js); kept for the saved value. ?>
                <div class="groupCheckBox mpwpb-sm__offdays-weekly" hidden>
                    <input type="hidden" name="mpwpb_off_days" value="<?php echo esc_attr($off_days); ?>"/>
                    <?php foreach ($days as $key => $day) { ?>
                        <label class="customCheckboxLabel">
                            <input type="checkbox" <?php echo esc_attr(in_array($key, $off_day_array) ? 'checked' : ''); ?> data-checked="<?php echo esc_attr($key); ?>"/>
                            <span class="customCheckbox"><?php echo esc_html($day); ?></span>
                        </label>
                    <?php } ?>
                </div>
                <h3 class="mpwpb-sm__sub"><?php esc_html_e('Days off', 'service-booking-manager'); ?></h3>
                <p class="mpwpb-sm__note"><?php esc_html_e('One-off dates this person is away, such as leave or holidays.', 'service-booking-manager'); ?></p>
                <div class="mp_settings_area">
                    <div class="mp_item_insert mp_sortable_area">
                        <?php
                        $off_day_lists = $user_id ? get_user_meta($user_id, 'mpwpb_off_dates') : [];
                        $off_day_lists = sizeof($off_day_lists) > 0 ? current($off_day_lists) : [];
                        if (is_array($off_day_lists) && sizeof($off_day_lists) > 0) {
                            foreach ($off_day_lists as $off_day) {
                                if ($off_day) {
                                    MPWPB_Date_Time_Settings::particular_date_item('mpwpb_off_dates[]', $off_day);
                                }
                            }
                        }
                        ?>
                    </div>
                    <?php MPWPB_Custom_Layout::add_new_button(esc_html__('Add day off', 'service-booking-manager')); ?>
                    <div class="mpwpb_hidden_content">
                        <div class="mpwpb_hidden_item">
                            <?php MPWPB_Date_Time_Settings::particular_date_item('mpwpb_off_dates[]'); ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php
        }

        //*****************************//
        public function time_slot_tr($user_id, $day, $is_off = false) {
            $start_name = 'mpwpb_' . $day . '_start_time';
            $default_start_time = $day == 'default' ? 10 : '';
            $start_time = $user_id ? get_user_meta($user_id, $start_name) : [];
            $start_time = sizeof($start_time) > 0 ? current($start_time) : $default_start_time;
            $end_name = 'mpwpb_' . $day . '_end_time';
            $default_end_time = $day == 'default' ? 18 : '';
            $end_time = $user_id ? get_user_meta($user_id, $end_name) : [];
            $end_time = sizeof($end_time) > 0 ? current($end_time) : $default_end_time;
            $start_name_break = 'mpwpb_' . $day . '_start_break_time';
            $start_time_break = $user_id ? get_user_meta($user_id, $start_name_break) : [];
            $start_time_break = sizeof($start_time_break) > 0 ? current($start_time_break) : '';
            $is_default = $day === 'default';
            $week = MPWPB_Global_Function::week_day();
            $label = $is_default ? __('Usual hours', 'service-booking-manager') : (isset($week[$day]) ? $week[$day] : ucfirst($day));
            $classes = 'mpwpb-sm__row' . ($is_default ? ' is-default' : '') . ($is_off ? ' mpwpb-staff-row-off' : '');
            ?>
            <tr class="<?php echo esc_attr($classes); ?>" data-sm-day="<?php echo esc_attr($day); ?>">
                <th scope="row" class="mpwpb-sm__row-day"><?php echo esc_html($label); ?></th>
                <td class="mpwpb-sm__row-switch">
                    <?php if (!$is_default) { ?>
                        <button type="button" class="mpwpb-sm__switch" role="switch" aria-checked="<?php echo $is_off ? 'false' : 'true'; ?>" data-sm-day-toggle aria-label="<?php
                            /* translators: %s: day name */
                            echo esc_attr(sprintf(__('%s: working', 'service-booking-manager'), $label)); ?>"><span></span></button>
                    <?php } ?>
                </td>
                <td class="mpwpb-sm__row-summary" data-sm-summary></td>
                <td class="mpwpb_start_time" data-day-name="<?php echo esc_attr($day); ?>">
                    <label>
                        <span class="screen-reader-text"><?php
                            /* translators: %s: day name */
                            echo esc_html(sprintf(__('%s start', 'service-booking-manager'), $label)); ?></span>
                        <select class="formControl" name="<?php echo esc_attr($start_name); ?>">
                            <option value="" <?php echo esc_attr($start_time == '' ? 'selected' : ''); ?>>
                                <?php $this->default_text($day); ?>
                            </option>
                            <?php $this->time_slot($start_time); ?>
                        </select>
                    </label>
                </td>
                <td class="mpwpb-sm__to"><?php esc_html_e('to', 'service-booking-manager'); ?></td>
                <td class="mpwpb_end_time">
                    <?php $this->end_time_slot($day, $start_time, $user_id); ?>
                </td>
                <td class="mpwpb-sm__break-label"><?php esc_html_e('Break', 'service-booking-manager'); ?></td>
                <td class="mpwpb_start_break_time">
                    <?php $this->start_break_time_slot($day, $start_time, $end_time, $user_id) ?>
                </td>
                <td class="mpwpb-sm__to mpwpb-sm__to--break"><?php esc_html_e('to', 'service-booking-manager'); ?></td>
                <td class="mpwpb_end_break_time">
                    <?php $this->end_break_time_slot($day, $start_time_break, $end_time, $user_id) ?>
                </td>
                <td class="mpwpb-sm__row-actions">
                    <?php if (!$is_default) { ?>
                        <button type="button" class="mpwpb-sm__btn mpwpb-sm__btn--sm" data-sm-custom><?php esc_html_e('Set different hours', 'service-booking-manager'); ?></button>
                    <?php } ?>
                </td>
            </tr>
            <?php
        }
        public function end_time_slot($day, $start_time, $user_id = '') {
            $end_name = 'mpwpb_' . $day . '_end_time';
            $default_end_time = $day == 'default' ? 18 : '';
            $end_time = $user_id ? get_user_meta($user_id, $end_name) : [];
            $end_time = sizeof($end_time) > 0 ? current($end_time) : $default_end_time;
            ?>
            <label>
                <select class="formControl " name="<?php echo esc_attr($end_name); ?>">
                    <?php if ($start_time == '') { ?>
                        <option value="" selected><?php $this->default_text($day); ?></option>
                    <?php } ?>
                    <?php $this->time_slot($end_time, $start_time); ?>
                </select>
            </label>
            <?php
        }
        public function start_break_time_slot($day, $start_time, $end_time = '', $user_id = '') {
            $start_name_break = 'mpwpb_' . $day . '_start_break_time';
            $start_time_break = $user_id ? get_user_meta($user_id, $start_name_break) : [];
            $start_time_break = sizeof($start_time_break) > 0 ? current($start_time_break) : '';
            ?>
            <label>
                <select class="formControl" name="<?php echo esc_attr($start_name_break); ?>">
                    <option value="" <?php echo esc_attr(!$start_time_break ? 'selected' : ''); ?>><?php esc_html_e('No Break', 'service-booking-manager'); ?></option>
                    <?php $this->time_slot($start_time_break, $start_time, $end_time); ?>
                </select>
            </label>
            <?php
        }
        public function end_break_time_slot($day, $start_time_break, $end_time, $user_id = '') {
            $end_name_break = 'mpwpb_' . $day . '_end_break_time';
            $end_time_break = $user_id ? get_user_meta($user_id, $end_name_break) : [];
            $end_time_break = sizeof($end_time_break) > 0 ? current($end_time_break) : '';
            ?>
            <label>
                <select class="formControl" name="<?php echo esc_attr($end_name_break); ?>">
                    <?php if ($start_time_break == '') { ?>
                        <option value="" selected><?php esc_html_e('No Break', 'service-booking-manager'); ?></option>
                    <?php } ?>
                    <?php $this->time_slot($end_time_break, $start_time_break, $end_time); ?>
                </select>
            </label>
            <?php
        }
        public function time_slot($time, $stat_time = '', $end_time = '') {
            if ($stat_time >= 0 || $stat_time == '') {
                $time_count = $stat_time == '' ? 0 : $stat_time;
                $end_time = $end_time != '' ? $end_time : 23.5;
                for ($i = $time_count; $i <= $end_time; $i = $i + 0.5) {
                    if ($stat_time == 'yes' || $i > $time_count) {
                        ?>
                        <option value="<?php echo esc_attr($i); ?>" <?php echo esc_attr($time != '' && $time == $i ? 'selected' : ''); ?>><?php echo esc_html($this->format_time_option($i)); ?></option>
                        <?php
                    }
                }
            }
        }
        public function default_text($day) {
            if ($day == 'default') {
                esc_html_e('Please select', 'service-booking-manager');
            } else {
                esc_html_e('Default', 'service-booking-manager');
            }
        }
        //*****************************//
        public function get_mpwpb_get_staff_form() {
            if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'mpwpb_admin_nonce')) {
                wp_send_json_error('Invalid nonce!'); // Prevent unauthorized access
            }
            if (!$this->ensure_staff_manage_permission()) {
                return;
            }
            $user_id = isset($_POST['user_id']) ? sanitize_text_field(wp_unslash($_POST['user_id'])) : '';
            $this->staff_form($user_id);
            die();
        }
        public function mpwpb_delete_staff() {
            if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'mpwpb_admin_nonce')) {
                wp_send_json_error('Invalid nonce!'); // Prevent unauthorized access
            }
            if (!$this->ensure_staff_manage_permission()) {
                return;
            }
            $staff_id = isset($_POST['staff_id']) ? sanitize_text_field(wp_unslash($_POST['staff_id'])) : '';
            if ($staff_id > 0) {
                wp_delete_user($staff_id);
                $this->staff_list();
            }
            die();
        }
        public function get_mpwpb_staff_end_time_slot() {
            if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'mpwpb_admin_nonce')) {
                wp_send_json_error('Invalid nonce!'); // Prevent unauthorized access
            }
            if (!$this->ensure_staff_manage_permission()) {
                return;
            }
            $user_id = isset($_POST['user_id']) ? sanitize_text_field(wp_unslash($_POST['user_id'])) : '';
            $day = isset($_POST['day_name']) ? sanitize_text_field(wp_unslash($_POST['day_name'])) : '';
            $start_time = isset($_POST['start_time']) ? sanitize_text_field(wp_unslash($_POST['start_time'])) : '';
            $this->end_time_slot($day, $start_time, $user_id);
            die();
        }
        public function get_mpwpb_staff_start_break_time() {
            if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'mpwpb_admin_nonce')) {
                wp_send_json_error('Invalid nonce!'); // Prevent unauthorized access
            }
            if (!$this->ensure_staff_manage_permission()) {
                return;
            }
            $user_id = isset($_POST['user_id']) ? sanitize_text_field(wp_unslash($_POST['user_id'])) : '';
            $day = isset($_POST['day_name']) ? sanitize_text_field(wp_unslash($_POST['day_name'])) : '';
            $start_time = isset($_POST['start_time']) ? sanitize_text_field(wp_unslash($_POST['start_time'])) : '';
            $end_time = isset($_POST['end_time']) ? sanitize_text_field(wp_unslash($_POST['end_time'])) : '';
            $this->start_break_time_slot($day, $start_time, $end_time, $user_id);
            die();
        }
        public function get_mpwpb_staff_end_break_time() {
            if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'mpwpb_admin_nonce')) {
                wp_send_json_error('Invalid nonce!'); // Prevent unauthorized access
            }
            if (!$this->ensure_staff_manage_permission()) {
                return;
            }
            $user_id = isset($_POST['user_id']) ? sanitize_text_field(wp_unslash($_POST['user_id'])) : '';
            $day = isset($_POST['day_name']) ? sanitize_text_field(wp_unslash($_POST['day_name'])) : '';
            $start_time = isset($_POST['start_time']) ? sanitize_text_field(wp_unslash($_POST['start_time'])) : '';
            $end_time = isset($_POST['end_time']) ? sanitize_text_field(wp_unslash($_POST['end_time'])) : '';
            $this->end_break_time_slot($day, $start_time, $end_time, $user_id);
            die();
        }
        /*************************************/
        public function save_staff() {
            if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
                return;
            }
            if (!current_user_can('create_users')) {
                return;
            }
            if (!isset($_POST['mpwpb_add_staff_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['mpwpb_add_staff_nonce'])), 'mpwpb_add_staff_nonce')) {
                return;
            }
            $user_id = isset($_POST['mpwpb_user']) ? absint(wp_unslash($_POST['mpwpb_user'])) : 0;
            $is_new_user = false;
            if (!$user_id) {
                $user_name = isset($_POST['mpwpb_user_name']) ? sanitize_text_field(wp_unslash($_POST['mpwpb_user_name'])) : '';
                $user_pass = isset($_POST['mpwpb_user_password']) ? sanitize_text_field(wp_unslash($_POST['mpwpb_user_password'])) : '';
                $user_email = isset($_POST['mpwpb_user_mail']) ? sanitize_email(wp_unslash($_POST['mpwpb_user_mail'])) : '';
                // Same rules the form checks in the browser (mpwpb_admin.js),
                // repeated here so they hold even if that check is bypassed.
                if ($user_name !== '' && strlen($user_name) < 3) {
                    return array('type' => 'error', 'message' => __('Username must be at least 3 characters.', 'service-booking-manager'));
                }
                if ($user_pass !== '' && strlen($user_pass) < 8) {
                    return array('type' => 'error', 'message' => __('Password must be at least 8 characters.', 'service-booking-manager'));
                }
                if (isset($_POST['mpwpb_user_mail']) && $_POST['mpwpb_user_mail'] !== '' && !is_email($user_email)) {
                    return array('type' => 'error', 'message' => __('Please enter a valid email address.', 'service-booking-manager'));
                }
                if ($user_name && $user_pass && $user_email) {
                    $created = wp_create_user($user_name, $user_pass, $user_email);
                    // A failed create returns a WP_Error object, which is still
                    // truthy -- carried on into wp_update_user(), (int) cast it to
                    // 1 and turned user #1 (the site admin) into a staff member.
                    if (is_wp_error($created) || !$created) {
                        return array(
                            'type' => 'error',
                            'message' => is_wp_error($created) ? wp_strip_all_tags($created->get_error_message()) : __('Staff user could not be created.', 'service-booking-manager'),
                        );
                    }
                    $user_id = (int) $created;
                    $is_new_user = true;
                } else {
                    return array(
                        'type' => 'error',
                        'message' => __('Username, password and email are required to add a new staff member.', 'service-booking-manager'),
                    );
                }
            }
            if ($user_id) {
                $first_name = isset($_POST['mpwpb_staff_first_name']) ? sanitize_text_field(wp_unslash($_POST['mpwpb_staff_first_name'])) : '';
                $last_name = isset($_POST['mpwpb_staff_last_name']) ? sanitize_text_field(wp_unslash($_POST['mpwpb_staff_last_name'])) : '';
                $modify_holiday = isset($_POST['mpwpb_staff_modify_holiday']) ? sanitize_text_field(wp_unslash($_POST['mpwpb_staff_modify_holiday'])) : '';
                $userinfo = array(
                    'ID' => $user_id,
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                );
                wp_update_user($userinfo);
                // New accounts become staff outright; an existing user picked from
                // the list gains the staff role on top of what they already have,
                // so making someone staff never strips an admin/manager role.
                $staff_user = new WP_User($user_id);
                if ($is_new_user) {
                    $staff_user->set_role('mpwpb_staff');
                } elseif (!in_array('mpwpb_staff', (array) $staff_user->roles, true)) {
                    $staff_user->add_role('mpwpb_staff');
                }
                $date_type = isset($_POST['mpwpb_date_type']) ? sanitize_text_field(wp_unslash($_POST['mpwpb_date_type'])) : 'repeated';
                update_user_meta($user_id, 'date_type', $date_type);
                update_user_meta($user_id, 'mpwpb_staff_modify_holiday', $modify_holiday);
                //**********************//
                $particular_dates = isset($_POST['mpwpb_particular_dates']) ? array_map('sanitize_text_field', wp_unslash($_POST['mpwpb_particular_dates'])) : [];
                $particular = array();
                if (sizeof($particular_dates) > 0) {
                    foreach ($particular_dates as $particular_date) {
                        if ($particular_date) {
                            $particular[] = date_i18n('Y-m-d', strtotime($particular_date));
                        }
                    }
                }
                update_user_meta($user_id, 'mpwpb_particular_dates', $particular);
                //*************************//
                $repeated_start_date = isset($_POST['mpwpb_repeated_start_date']) ? sanitize_text_field(wp_unslash($_POST['mpwpb_repeated_start_date'])) : '';
                $repeated_start_date = $repeated_start_date ? date_i18n('Y-m-d', strtotime($repeated_start_date)) : '';
                update_user_meta($user_id, 'mpwpb_repeated_start_date', $repeated_start_date);
                $repeated_after = isset($_POST['mpwpb_repeated_after']) ? sanitize_text_field(wp_unslash($_POST['mpwpb_repeated_after'])) : 1;
                update_user_meta($user_id, 'mpwpb_repeated_after', $repeated_after);
                //**********************//
                $this->save_schedule($user_id, 'default');
                $days = MPWPB_Global_Function::week_day();
                foreach ($days as $key => $day) {
                    $this->save_schedule($user_id, $key);
                }
                //**********************//
                $off_days = isset($_POST['mpwpb_off_days']) ? sanitize_text_field(wp_unslash($_POST['mpwpb_off_days'])) : '';
                update_user_meta($user_id, 'mpwpb_off_days', $off_days);
                //**********************//
                $off_dates = isset($_POST['mpwpb_off_dates']) ? array_map('sanitize_text_field', wp_unslash($_POST['mpwpb_off_dates'])) : [];
                $_off_dates = array();
                if (sizeof($off_dates) > 0) {
                    foreach ($off_dates as $off_date) {
                        if ($off_date) {
                            $_off_dates[] = date_i18n('Y-m-d', strtotime($off_date));
                        }
                    }
                }
                update_user_meta($user_id, 'mpwpb_off_dates', $_off_dates);

                $this->save_custom_user_profile_image( $user_id );

                $staff_user = get_userdata($user_id);
                $staff_label = $staff_user ? $staff_user->display_name : '';
                return array(
                    'type' => 'success',
                    'user_id' => $user_id,
                    'message' => $is_new_user
                        /* translators: %s: staff member display name */
                        ? sprintf(__('Staff member "%s" added.', 'service-booking-manager'), $staff_label)
                        /* translators: %s: staff member display name */
                        : sprintf(__('Staff member "%s" updated.', 'service-booking-manager'), $staff_label),
                );
            }
            return null;
        }
        public function save_schedule($user_id, $day) {
            if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
                return;
            }
            if (!current_user_can('create_users')) {
                return;
            }
            if (!isset($_POST['mpwpb_add_staff_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['mpwpb_add_staff_nonce'])), 'mpwpb_add_staff_nonce')) {
                return;
            }
            $start_name = 'mpwpb_' . $day . '_start_time';
            $start_time = isset($_POST[$start_name]) ? sanitize_text_field(wp_unslash($_POST[$start_name])) : '';
            update_user_meta($user_id, $start_name, $start_time);
            $end_name = 'mpwpb_' . $day . '_end_time';
            $end_time = isset($_POST[$end_name]) ? sanitize_text_field(wp_unslash($_POST[$end_name])) : '';
            update_user_meta($user_id, $end_name, $end_time);
            $start_name_break = 'mpwpb_' . $day . '_start_break_time';
            $start_time_break = isset($_POST[$start_name_break]) ? sanitize_text_field(wp_unslash($_POST[$start_name_break])) : '';
            update_user_meta($user_id, $start_name_break, $start_time_break);
            $end_name_break = 'mpwpb_' . $day . '_end_break_time';
            $end_time_break = isset($_POST[$end_name_break]) ? sanitize_text_field(wp_unslash($_POST[$end_name_break])) : '';
            update_user_meta($user_id, $end_name_break, $end_time_break);
        }
        
        /**
         * Format time option based on 24-hour setting
         *
         * @param float $hour Hour value (e.g., 10.5 for 10:30)
         * @return string Formatted time
         */
        public function format_time_option($hour) {
            $use_24hour = MPWPB_Global_Function::get_settings('mpwpb_global_settings', 'time_format_24hour', 'no');
            
            if ($use_24hour === 'yes') {
                // Format as 24-hour (H:i)
                $hours = floor($hour);
                $minutes = ($hour - $hours) * 60;
                return sprintf('%02d:%02d', $hours, $minutes);
            } else {
                // Format as 12-hour (h:i A)
                return date_i18n('h:i A', $hour * 3600);
            }
        }

    }

    new MPWPB_Staff_Members();
}
