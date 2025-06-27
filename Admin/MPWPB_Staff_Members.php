<?php
if (!defined('ABSPATH')) {
    die;
} // Cannot access pages directly.
if (!class_exists('MPWPB_Staff_Members')) {
    class MPWPB_Staff_Members{

        public function __construct() {
            add_action('admin_menu', array($this, 'staffs_menu'));
            add_action('wp_ajax_get_mpwpb_get_staff_form', array($this, 'get_mpwpb_get_staff_form'));
            add_action('wp_ajax_nopriv_get_mpwpb_get_staff_form', array($this, 'get_mpwpb_get_staff_form'));
            /*********************************************/
            add_action('wp_ajax_mpwpb_delete_staff', array($this, 'mpwpb_delete_staff'));
            add_action('wp_ajax_nopriv_mpwpb_delete_staff', array($this, 'mpwpb_delete_staff'));
            /************************/
            add_action('wp_ajax_get_mpwpb_staff_end_time_slot', array($this, 'get_mpwpb_staff_end_time_slot'));
            add_action('wp_ajax_nopriv_get_mpwpb_staff_end_time_slot', array($this, 'get_mpwpb_staff_end_time_slot'));
            /***/
            add_action('wp_ajax_get_mpwpb_staff_start_break_time', array($this, 'get_mpwpb_staff_start_break_time'));
            add_action('wp_ajax_nopriv_get_mpwpb_staff_start_break_time', array($this, 'get_mpwpb_staff_start_break_time'));
            /***/
            add_action('wp_ajax_get_mpwpb_staff_end_break_time', array($this, 'get_mpwpb_staff_end_break_time'));
            add_action('wp_ajax_nopriv_get_mpwpb_staff_end_break_time', array($this, 'get_mpwpb_staff_end_break_time'));
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
            ?>
            <div class="profile-section">
                <h3>Profile Image</h3>

                <div class="mpwpb_profile_image_show">
                    <input type="hidden" name="mpwpb_custom_profile_image" id="mpwpb_custom_profile_image" value="<?php echo esc_attr(get_user_meta( $user_id, 'mpwpb_custom_profile_image', true)); ?>" />
                </div>
                <div class="upload-area">

                    <?php if( $image_url ){?>
                        <img src="<?php echo esc_attr( $image_url );?>" id="mpwpb_custom_profile_image_preview" style="width:100px;height:auto;" />
                        <p style="color: #6b7280; font-size: 14px;"><?php esc_attr_e('Uploaded Imag', 'service-booking-manager'); ?>e</p>
                    <?php }else{?>
                        <div style="font-size: 32px; margin-bottom: 8px; color: #9ca3af;">📁</div>
                        <p style="color: #6b7280; font-size: 14px;"><?php esc_attr_e('Upload Image', 'service-booking-manager'); ?></p>
                    <?php }?>


                    <div class="upload-buttons">
                        <input type="button" class="btn btn-primary" value="<?php esc_attr_e('Add Image', 'service-booking-manager'); ?>" id="upload_profile_image_button" />
                        <input type="button" class="btn btn-secondary" value="<?php esc_attr_e('Remove Image', 'service-booking-manager'); ?>" id="remove_profile_image_button" />
                    </div>
                </div>
            </div>

            <?php
        }
        public function mpwpb_staff_service() {
            $this->save_staff();
            ?>
            <div class="wrap">
                <div class="mpwpb_style mpwpb_staff_page">
                    <div class="_dLayout_dShadow_1">
                        <div class="mpwpb_staff_tabs">
                            <div class="header">
                                <h1>
                                    <div class="header-icon">👥</div>
                                    <?php esc_html_e('Staff Management', 'service-booking-manager'); ?>
                                </h1>
                                <div class="mpwpb_add_update_tab">
                                    <div class="buttonGroup">
                                        <button class="_mpBtn mpwpb_staff_tab_switch mpwpb_staff_tab_active" id="mpwpb_staff_lists"  type="button" title="<?php esc_attr_e('Staff Lists', 'service-booking-manager'); ?>">
                                            <span class="fas fa-users"></span><?php esc_html_e('Staff Lists', 'service-booking-manager'); ?>
                                        </button>
                                        <button class="_mpBtn mpwpb_staff_tab_switch" id="mpwpb_staff_members" type="button" title="<?php esc_attr_e('Add New Staff', 'service-booking-manager'); ?>">
                                            <span class="fas fa-plus-square"></span><?php esc_html_e('Add/Update Staff', 'service-booking-manager'); ?>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="tabsContent _pad_zero">
                                <div class="tabsItem mpwpb_staff_list" id="mpwpb_staff_lists_holder">
                                    <?php $this->staff_list(); ?>
                                </div>
                                <div class="tabsItem  mpwpb_add_staff" id="mpwpb_staff_members_holder" style="display: none">
                                    <?php $this->staff_form(); ?>
                                </div>
                            </div>
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
        public function staff_list() {
            $count = 1;
            $all_staffs = get_users(['role' => 'mpwpb_staff']);
            //echo '<pre>';print_r($all_staffs);echo '</pre>';
            if (sizeof($all_staffs) > 0) {
                ?>
                <table>
                    <thead>
                    <tr>
                        <th class="_w_50"><?php esc_html_e('SI.', 'service-booking-manager'); ?></th>
                        <th><?php esc_html_e('User Image', 'service-booking-manager'); ?></th>
                        <th><?php esc_html_e('User Name', 'service-booking-manager'); ?></th>
                        <th><?php esc_html_e('Staff Name', 'service-booking-manager'); ?></th>
                        <th><?php esc_html_e('Staff Email', 'service-booking-manager'); ?></th>
                        <th class="_w_125"><?php esc_html_e('Action', 'service-booking-manager'); ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($all_staffs as $staff) {
                        $staff_img = $this->get_custom_user_profile_image ( $staff->ID,'thumbnail', 'mpwpb_staff_image' );
                        ?>
                        <tr>
                            <th><?php echo esc_html($count . '.'); ?></th>
                            <td class="mpwpb_staff_image_holder"><?php echo wp_kses_post($staff_img); ?></td>
                            <td><?php echo esc_html($staff->user_login); ?></td>
                            <td>                                    <?php echo esc_html($staff->display_name); ?>                                </td>
                            <td><?php echo esc_html($staff->user_email); ?></td>
                            <td>
                                <div class="buttonGroup">
                                    <button class="_mpBtn_xs_textGray" id="mpwpb_edit_staff" data-staff-id="<?php echo esc_attr($staff->ID); ?>" type="button" title="<?php esc_attr_e('edit Staff Details.', 'service-booking-manager'); ?>">
                                        <span class="fas fa-edit mp_zero"></span>
                                    </button>
                                    <button class="_mpBtn_xs_textDanger" id="mpwpb_delete_staff" type="button" data-staff-id="<?php echo esc_attr($staff->ID); ?>" title="<?php echo esc_attr__('Remove staff.', 'service-booking-manager') . ' : ' . esc_attr($staff->display_name); ?>">
                                        <span class="fas fa-trash-alt mp_zero"></span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php $count++; ?>
                    <?php } ?>
                    </tbody>
                </table>
            <?php } else { ?>
                <h3><?php esc_html_e('No Staff Found!', 'service-booking-manager'); ?></h3>
                <?php
            }
        }
        public function staff_form($user_id = '') {
            if (!current_user_can('create_users')) {
                ?>
                <h3><?php esc_html_e('You have no permission to create staff', 'service-booking-manager'); ?></h3><?php
            } else {
                $users = get_users(array('role__not_in' => array('administrator')));
                $user_info = $user_id ? get_userdata($user_id) : [];
                //$user_meta = $user_id?get_user_meta( $user_id):[];
                $staff_name = $user_id ? $user_info->user_login : '';
                $staff_pass = $user_id ? $user_info->user_pass : '';
                $staff_email = $user_id ? $user_info->user_email : '';
                $staff_first_name = $user_id ? $user_info->first_name : '';
                $staff_last_name = $user_id ? $user_info->last_name : '';

                $approve_holiday_modify = get_user_meta( $user_id, 'mpwpb_staff_modify_holiday', true);
                //echo '<pre>'; print_r($user_info); echo '</pre>';
                ?>
                <form action="" method="post">
                    <?php wp_nonce_field('mpwpb_add_staff_nonce', 'mpwpb_add_staff_nonce'); ?>
                    <input type="hidden" id="mpwpb_user_id" name="mpwpb_user_id" value="<?php echo esc_attr($user_id); ?>"/>
                    <div class="mpRow">
                        <div class="col_5 _infoLayout_xs">
                            <h4><?php esc_html_e('Staff Information', 'service-booking-manager'); ?></h4>
                            <label>
                                <span class=" _w_200"><?php esc_html_e('Select User', 'service-booking-manager'); ?></span>
                                <select name="mpwpb_user" class="formControl mpwpb_user_select">
                                    <option value=" "><?php esc_html_e('Add New User', 'service-booking-manager'); ?></option>
                                    <?php if (sizeof($users) > 0) { ?>
                                        <?php foreach ($users as $user) { ?>
                                            <option value="<?php echo esc_attr($user->ID); ?>" <?php echo esc_attr($user_id == $user->ID ? 'selected' : ''); ?>>
                                                <?php echo esc_html(ucfirst($user->data->display_name)); ?>
                                            </option>
                                        <?php } ?>
                                    <?php } ?>
                                </select>
                            </label>

                            <label class="mpwpb_user_info">
                                <span  class=" _w_200"> <i class="fa-solid fa-user"></i> <?php esc_html_e('User Name', 'service-booking-manager'); ?></span>
                                <input type="text" class="formControl mpwpb_id_validation mpwpb_input_height" name="mpwpb_user_name" value="<?php echo esc_attr($staff_name); ?>" placeholder="<?php esc_html_e('Please Type Staff Name.....', 'service-booking-manager'); ?>" <?php echo esc_attr($user_id ? 'disabled' : ''); ?> required/>
                            </label>
                            <label class="mpwpb_user_info">
                                <span class=" _w_200"> <i class="fa-solid fa-key"></i> <?php esc_html_e('Staff Password', 'service-booking-manager'); ?></span>
                                <input type="password" class="formControl mpwpb_input_height" name="mpwpb_user_password" value="<?php echo esc_attr($staff_pass); ?>" placeholder="<?php esc_html_e('Please Type Staff Password.....', 'service-booking-manager'); ?>" <?php echo esc_attr($user_id ? 'disabled' : ''); ?> required/>
                            </label>
                            <label class="mpwpb_user_info">
                                <span class=" _w_200"> <i class="fa-solid fa-envelope"></i> <?php esc_html_e('Staff Email', 'service-booking-manager'); ?></span>
                                <input type="email" class="formControl mpwpb_input_height" name="mpwpb_user_mail" value="<?php echo esc_attr($staff_email); ?>" placeholder="<?php esc_html_e('Please Type Staff Email.....', 'service-booking-manager'); ?>" required/>
                            </label>
                            <label class="mpwpb_user_info">
                                <span class=" _w_200"><?php esc_html_e('Staff First Name', 'service-booking-manager'); ?></span>
                                <input type="text" class="formControl mpwpb_name_validation mpwpb_input_height" name="mpwpb_staff_first_name" value="<?php echo esc_attr($staff_first_name); ?>" placeholder="<?php esc_html_e('Please Type Staff First Name.....', 'service-booking-manager'); ?>"/>
                            </label>
                            <label class="mpwpb_user_info">
                                <span class=" _w_200"><?php esc_html_e('Staff Last Name', 'service-booking-manager'); ?></span>
                                <input type="text" class="formControl mpwpb_name_validation mpwpb_input_height" name="mpwpb_staff_last_name" value="<?php echo esc_attr($staff_last_name); ?>" placeholder="<?php esc_html_e('Please Type Staff Last Name.....', 'service-booking-manager'); ?>"/>
                            </label>

                            <label class="mpwpb_user_info">
                                <span class=" _w_200"><?php esc_html_e('Staff Modify Holiday', 'service-booking-manager'); ?></span>
                                <select name="mpwpb_staff_modify_holiday" class="mpwpb_staff_modify_holiday">
                                    <option value="no" <?php echo ($approve_holiday_modify === 'no') ? 'selected' : ''; ?>>No</option>
                                    <option value="yes" <?php echo ($approve_holiday_modify === 'yes') ? 'selected' : ''; ?>>Yes</option>
                                </select>
                            </label>

                            <?php
                            // Show upload field in user profile (backend)
                            wp_kses_post( $this->custom_user_profile_image_field( $user_id ) );
                            ?>

                        </div>
                        <div class="col_7 _borL_pL">
                            <h4><?php esc_html_e('Staff Schedule', 'service-booking-manager'); ?></h4>
                            <?php $this->general_settings($user_id); ?>
                            <?php $this->schedule_settings($user_id); ?>
                            <?php $this->off_on_day_settings($user_id); ?>
                        </div>
                    </div>
                    <div class="justifyBetween _mT_xs">
                        <div></div>
                        <button class="themeButton" type="submit" title="<?php esc_attr_e('Save Staff', 'service-booking-manager'); ?>">
                            <span class="fas fa-plus-square _mR_xs"></span>
                            <?php
                            if ($user_id) {
                                esc_html_e('Update Staff', 'service-booking-manager');
                            } else {
                                esc_html_e('Save New Staff', 'service-booking-manager');
                            }
                            ?>
                        </button>
                    </div>
                </form>
                <?php
            }
        }
        public function general_settings($user_id = '') {
            $date_type = $user_id ? get_user_meta($user_id, 'date_type') : [];
            $date_type = sizeof($date_type) > 0 ? current($date_type) : 'repeated';
            //echo '<pre>'; print_r($date_type); echo '</pre>';
            $date_format = MPWPB_Global_Function::date_picker_format();
            $now = date_i18n($date_format, strtotime(current_time('Y-m-d')));
            $repeated_start_date = $user_id ? get_user_meta($user_id, 'mpwpb_repeated_start_date') : [];
            $repeated_start_date = sizeof($repeated_start_date) > 0 ? current($repeated_start_date) : '';
            $hidden_repeated_start_date = $repeated_start_date ? date_i18n('Y-m-d', strtotime($repeated_start_date)) : '';
            $visible_repeated_start_date = $repeated_start_date ? date_i18n($date_format, strtotime($repeated_start_date)) : '';
            $repeated_after = $user_id ? get_user_meta($user_id, 'mpwpb_repeated_after') : [];
            $repeated_after = sizeof($repeated_after) > 0 ? current($repeated_after) : 1;
            ?>
            <div class="mpPanel _mT_xs">
                <div class="mpPanelHeader _bgColor_6" data-collapse-target="#mpwpb_staff_general_setting" data-open-icon="fa-minus" data-close-icon="fa-plus">
                    <h6 class="_textBlack">
                        <span data-icon class="fas fa-plus mR_xs"></span><?php esc_html_e('General Settings', 'service-booking-manager'); ?>
                    </h6>
                </div>
                <div class="mpPanelBody" data-collapse="#mpwpb_staff_general_setting">
                    <label>
                        <span class="_w_200"><?php esc_html_e('Date Type', 'service-booking-manager'); ?></span>
                        <select class="formControl" name="mpwpb_date_type" data-collapse-target>
                            <option disabled selected><?php esc_html_e('Please select ...', 'service-booking-manager'); ?></option>
                            <option value="particular" data-option-target="#mp_particular" <?php echo esc_attr($date_type == 'particular' ? 'selected' : ''); ?>><?php esc_html_e('Particular', 'service-booking-manager'); ?></option>
                            <option value="repeated" data-option-target="#mp_repeated" <?php echo esc_attr($date_type == 'repeated' ? 'selected' : ''); ?>><?php esc_html_e('Repeated', 'service-booking-manager'); ?></option>
                        </select>
                    </label>
                    <div data-collapse="#mp_particular" class="<?php echo esc_attr($date_type == 'particular' ? 'mActive' : ''); ?>">
                        <div class="_dFlex">
                            <span class="_fs_label_w_200"><?php esc_html_e('Particular Dates', 'service-booking-manager'); ?></span>
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
                                <?php MPWPB_Custom_Layout::add_new_button(esc_html__('Add New Particular date', 'service-booking-manager')); ?>
                                <div class="mpwpb_hidden_content">
                                    <div class="mpwpb_hidden_item">
                                        <?php MPWPB_Date_Time_Settings::particular_date_item('mpwpb_particular_dates[]'); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div data-collapse="#mp_repeated" class="<?php echo esc_attr($date_type == 'repeated' ? 'mActive' : ''); ?>">
                        <label>
                            <span class="_w_200"><?php esc_html_e('Repeated Start Date', 'service-booking-manager'); ?></span>
                            <input type="hidden" name="mpwpb_repeated_start_date" value="<?php echo esc_attr($hidden_repeated_start_date); ?>"/>
                            <input type="text" readonly name="" class="formControl date_type" value="<?php echo esc_attr($visible_repeated_start_date); ?>" placeholder="<?php echo esc_attr($now); ?>"/>
                        </label>
                    </div>
                    <div data-collapse="#mp_repeated" class="<?php echo esc_attr($date_type == 'repeated' ? 'mActive' : ''); ?>">
                        <label>
                            <span class="_w_200"><?php esc_html_e('Repeated after', 'service-booking-manager'); ?></span>
                            <input type="text" name="mpwpb_repeated_after" class="formControl mpwpb_number_validation" value="<?php echo esc_attr($repeated_after); ?>"/>
                        </label>
                    </div>
                </div>
            </div>
            <?php
        }
        public function schedule_settings($user_id = '') {
            ?>
            <div class="mpPanel mT_xs">
                <div class="mpPanelHeader _bgColor_6" data-collapse-target="#mpwpb_staff_schedule_setting" data-open-icon="fa-minus" data-close-icon="fa-plus">
                    <h6 class="_textBlack">
                        <span data-icon class="fas fa-plus mR_xs"></span><?php esc_html_e('Time Schedule Settings', 'service-booking-manager'); ?>
                    </h6>
                </div>
                <div class="mpPanelBody" data-collapse="#mpwpb_staff_schedule_setting">
                    <table>
                        <thead>
                        <tr>
                            <th><?php esc_html_e('Day', 'service-booking-manager'); ?></th>
                            <th><?php esc_html_e('Start Time', 'service-booking-manager'); ?></th>
                            <th><?php esc_html_e('To', 'service-booking-manager'); ?></th>
                            <th><?php esc_html_e('End Time', 'service-booking-manager'); ?></th>
                            <th colspan="3" style="background-color: #e3d3d3;"><?php esc_html_e('Break Time', 'service-booking-manager'); ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                        $this->time_slot_tr($user_id, 'default');
                        $days = MPWPB_Global_Function::week_day();
                        foreach ($days as $key => $day) {
                            $this->time_slot_tr($user_id, $key);
                        }
                        ?>
                        </tbody>
                    </table>
                </div>
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
            <div class="mpPanel mT_xs <?php echo esc_attr($date_type == 'repeated' ? 'mActive' : ''); ?>" data-collapse="#mp_repeated">
                <div class="mpPanelHeader _bgColor_6" data-collapse-target="#mpwpb_staff_off_on_day_setting" data-open-icon="fa-minus" data-close-icon="fa-plus">
                    <h6 class="_textBlack">
                        <span data-icon class="fas fa-plus mR_xs"></span><?php esc_html_e('Off Days & Dates Settings', 'service-booking-manager'); ?>
                    </h6>
                </div>
                <div class="mpPanelBody" data-collapse="#mpwpb_staff_off_on_day_setting">
                    <div class="dFlex">
                        <span class="_fs_label_w_200"><?php esc_html_e('Off Day', 'service-booking-manager'); ?></span>
                        <div class="groupCheckBox flexWrap">
                            <input type="hidden" name="mpwpb_off_days" value="<?php echo esc_attr($off_days); ?>"/>
                            <?php foreach ($days as $key => $day) { ?>
                                <label class="customCheckboxLabel _w_200">
                                    <input type="checkbox" <?php echo esc_attr(in_array($key, $off_day_array) ? 'checked' : ''); ?> data-checked="<?php echo esc_attr($key); ?>"/>
                                    <span class="customCheckbox"><?php echo esc_html($day); ?></span>
                                </label>
                            <?php } ?>
                        </div>
                    </div>
                    <div class="dFlex">
                        <span class="_fs_label_w_200"><?php esc_html_e('Off Dates', 'service-booking-manager'); ?></span>
                        <div class="mp_settings_area">
                            <div class="mp_item_insert mp_sortable_area">
                                <?php
                                $off_day_lists = $user_id ? get_user_meta($user_id, 'mpwpb_off_dates') : [];
                                $off_day_lists = sizeof($off_day_lists) > 0 ? current($off_day_lists) : [];
                                if (sizeof($off_day_lists) > 0) {
                                    foreach ($off_day_lists as $off_day) {
                                        if ($off_day) {
                                            MPWPB_Date_Time_Settings::particular_date_item('mpwpb_off_dates[]', $off_day);
                                        }
                                    }
                                }
                                ?>
                            </div>
                            <?php MPWPB_Custom_Layout::add_new_button(esc_html__('Add New Off date', 'service-booking-manager')); ?>
                            <div class="mpwpb_hidden_content">
                                <div class="mpwpb_hidden_item">
                                    <?php MPWPB_Date_Time_Settings::particular_date_item('mpwpb_off_dates[]'); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php
        }

        //*****************************//
        public function time_slot_tr($user_id, $day) {
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
            ?>
            <tr>
                <th style="text-transform: capitalize;"><?php echo esc_html($day); ?></th>
                <td class="mpwpb_start_time" data-day-name="<?php echo esc_attr($day); ?>">
                    <?php //echo '<pre>'; print_r( $start_time );echo '</pre>'; ?>
                    <label>
                        <select class="formControl" name="<?php echo esc_attr($start_name); ?>">
                            <option value="" <?php echo esc_attr($start_time == '' ? 'selected' : ''); ?>>
                                <?php $this->default_text($day); ?>
                            </option>
                            <?php $this->time_slot($start_time); ?>
                        </select>
                    </label>
                </td>
                <td class="textCenter">
                    <strong><?php esc_html_e('To', 'service-booking-manager'); ?></strong>
                </td>
                <td class="mpwpb_end_time">
                    <?php $this->end_time_slot($day, $start_time, $user_id); ?>
                </td>
                <td style="background-color: #e3d3d3;" class="mpwpb_start_break_time">
                    <?php $this->start_break_time_slot($day, $start_time, $end_time, $user_id) ?>
                </td>
                <td class="textCenter" style="background-color: #e3d3d3;">
                    <strong><?php esc_html_e('To', 'service-booking-manager'); ?></strong>
                </td>
                <td style="background-color: #e3d3d3;" class="mpwpb_end_break_time">
                    <?php $this->end_break_time_slot($day, $start_time_break, $end_time, $user_id) ?>
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
                        <option value="<?php echo esc_attr($i); ?>" <?php echo esc_attr($time != '' && $time == $i ? 'selected' : ''); ?>><?php echo esc_html(date_i18n('h:i A', $i * 3600)); ?></option>
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
            $user_id = isset($_POST['user_id']) ? sanitize_text_field(wp_unslash($_POST['user_id'])) : '';
            $this->staff_form($user_id);
            die();
        }
        public function mpwpb_delete_staff() {
            if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'mpwpb_admin_nonce')) {
                wp_send_json_error('Invalid nonce!'); // Prevent unauthorized access
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
            $user_id = isset($_POST['user_id']) ? sanitize_text_field(wp_unslash($_POST['user_id'])) : '';
            $day = isset($_POST['day_name']) ? sanitize_text_field(wp_unslash($_POST['day_name'])) : '';
            $start_time = isset($_POST['start_time']) ? sanitize_text_field(wp_unslash($_POST['start_time'])) : '';
            $end_time = isset($_POST['end_time']) ? sanitize_text_field(wp_unslash($_POST['end_time'])) : '';
            $this->end_break_time_slot($day, $start_time, $end_time, $user_id);
            die();
        }
        /*************************************/
        public function save_staff() {
            if (!isset($_POST['mpwpb_add_staff_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['mpwpb_add_staff_nonce'])), 'mpwpb_add_staff_nonce') && defined('DOING_AUTOSAVE') && DOING_AUTOSAVE && !current_user_can('create_users')) {
                return;
            }
            $user_id = isset($_POST['mpwpb_user']) ? absint(wp_unslash($_POST['mpwpb_user'])) : '';
            if (!$user_id) {
                $user_name = isset($_POST['mpwpb_user_name']) ? sanitize_text_field(wp_unslash($_POST['mpwpb_user_name'])) : '';
                $user_pass = isset($_POST['mpwpb_user_password']) ? sanitize_text_field(wp_unslash($_POST['mpwpb_user_password'])) : '';
                $user_email = isset($_POST['mpwpb_user_mail']) ? sanitize_email(wp_unslash($_POST['mpwpb_user_mail'])) : '';
                if ($user_name && $user_pass && $user_email) {
                    $user_id = wp_create_user($user_name, $user_pass, $user_email);
                    if (!$user_id || is_wp_error($user_id)) {
                        echo 'sorry user not create';
                    }
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
                    'role' => 'mpwpb_staff',
                );
                wp_update_user($userinfo);
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
                $off_days = isset($_POST['mpwpb_off_days']) ? $_POST['mpwpb_off_days'] : [];
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
            }
        }
        public function save_schedule($user_id, $day) {
            if (!isset($_POST['mpwpb_add_staff_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['mpwpb_add_staff_nonce'])), 'mpwpb_add_staff_nonce') && defined('DOING_AUTOSAVE') && DOING_AUTOSAVE && !current_user_can('create_users')) {
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

    }

    new MPWPB_Staff_Members();
}