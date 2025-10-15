<?php
	/*
   * @Author 		engr.sumonazma@gmail.com
   * Copyright: 	mage-people.com
   */
	if (!defined('ABSPATH')) {
		die;
	}
	$post_id = $post_id ?? get_the_id();
	$all_dates = $all_dates ?? MPWPB_Function::get_date($post_id);

	$enable_waiting_list = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_enable_waiting_list', 'no');
	$enable_recurring = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_enable_recurring', 'no');
	$recurring_types = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_recurring_types', array( 'daily','weekly', 'bi-weekly', 'monthly' ) );

    $max_recurring_count = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_max_recurring_count', 10);
	$recurring_discount = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_recurring_discount', 0);

    $enable_staff_member = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_staff_member_add', 'no');

	$short_date_format = $short_date_format ?? MPWPB_Global_Function::get_settings('mpwpb_global_settings', 'date_format_short', 'M , Y');
	$extra_services = $extra_services ?? MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_extra_service', array());

    $off_days_recurring = MPWPB_Global_Function::get_post_info( $post_id, 'mpwpb_off_days' );
    $all_off_dates_recurring = MPWPB_Global_Function::get_post_info( $post_id, 'mpwpb_off_dates', array() );
    $off_dates_recurring = [];
    foreach ($all_off_dates_recurring as $off_date) {
        $off_dates_recurring[] = date_i18n('Y-m-d', strtotime($off_date));
    }
    $off_dates_recurring = implode(',', $off_dates_recurring);

?>

<div class="mpwpb_off_days_dates" style="display: none; visibility: hidden; margin: 0; padding: 0" >
    <input type="hidden" class="mpwpb_off_days_data" id="mpwpb_off_days_data" value="<?php echo esc_attr( $off_days_recurring );?>">
    <input type="hidden" class="mpwpb_off_dates_data" id="mpwpb_off_dates_data" value="<?php echo esc_attr( $off_dates_recurring );?>">
</div>
<!-- Popup Wrapper -->
<div class="mpwpb_edit_recurring_datetime_popup" style="display:none;">
    <div class="mpwpb_edit_recurring_datetime_overlay"></div>
    <div class="mpwpb_edit_recurring_datetime_modal">
        <h3>Select Time</h3>
        <div class="mpwpb_edit_recurring_datetime_timeslot_wrap" id="mpwpb_edit_recurring_datetime_timeslot_wrap">
            <input type="hidden" id="mpwpb_get_selected_time" value="">
            <div class="mpwpb_recurring_time_holedr" id="mpwpb_recurring_time_holedr">
                <div class="mpwpb_recurring_loader">Loading...</div>
            </div>

            <!-- Add more as needed -->
        </div>
        <div class="mpwpb_edit_recurring_datetime_input_wrap">
            <h3>Selecte Date</h3>
            <input type="text" id="date_type_edit_recurring" class="formControl date_type_edit_recurring" value=""/>
            <input type="hidden" id="mpwpb_date_edit_recurring" class="mpwpb_date_edit_recurring" value=""/>
        </div>
        <div class="mpwpb_edit_date_btn_holder">
            <button class="mpwpb_recurring_datetime_set" id="mpwpb_recurring_datetime_set" data-recurringli-id="">Set</button>
            <button class="mpwpb_edit_recurring_datetime_close">Close</button>
        </div>

    </div>
</div>


<!-- Popup Wrapper -->

    <div class="_dShadow_7_mB_xs mpwpb_date_time_area">
        <div class="mpwpb_date_carousel groupRadioCheck" id="mpwpb_datetime_holder">
            <header class="_dFlex_alignCenter_justifyBetween">
                <input type="hidden" name="mpwpb_date">
                <h3 class="mpwpb_date_staff_select"><?php esc_html_e('Choose Date & Time', 'service-booking-manager'); ?></h3>
				<?php include(MPWPB_Function::template_path('layout/carousel_indicator.php')); ?>
            </header>
            <div class="" >
                <div class="owl-theme mpwpb-owl-carousel" id="mpwpb_datetime_holder1">
                    <?php if (sizeof($all_dates) > 0) {
                        wp_kses_post( MPWPB_Details_Layout::display_booking_date( $post_id, $all_dates ) );
                    ?>
                </div>
                <div class="mpwpb_select_time_holder" id="<?php echo esc_attr( $post_id );?>">
                    <?php
                        wp_kses_post( MPWPB_Details_Layout::display_booking_time( $post_id, $all_dates ) );
                    ?>
                </div>
                <?php
                } else {
                ?>
                    <h5><?php esc_html_e('Date not available', 'service-booking-manager'); ?></h5> <?php
                }
                if ( is_plugin_active('service-booking-manager-pro/MPWPB_Plugin_Pro.php') ) {
                    if ($enable_recurring === 'yes') { ?>
                        <div class="_dShadow_7_mB_xs mpwpb_recurring_booking_area" id="mpwpb_recurring_booking_area" style="display: none;">
                            <div class="mpwpb_recurring_booking">
                                <header class="_dFlex_alignCenter_justifyBetween">
                                    <h3><?php esc_html_e('Recurring Booking Options', 'service-booking-manager'); ?></h3>
                                </header>
                                <div class="mpwpb_recurring_options">
                                    <div class="mpwpb_recurring_toggle">
                                        <label class="switch">
                                            <input type="checkbox" name="mpwpb_enable_recurring_booking" id="mpwpb_enable_recurring_booking">
                                            <span class="slider round"></span>
                                        </label>
                                        <label for="mpwpb_enable_recurring_booking"><?php esc_html_e('Enable Recurring Booking', 'service-booking-manager'); ?></label>
                                    </div>

                                    <div class="mpwpb_recurring_settings" style="display: none;">
                                        <div class="mpwpb_weekday_selector" id="mpwpb_weekday_selector" style="display: none">
                                            <label><input type="checkbox" name="recurring_days[]" value="sun"> <?php esc_html_e('Sunday', 'service-booking-manager'); ?></label>
                                            <label><input type="checkbox" name="recurring_days[]" value="mon"> <?php esc_html_e('Monday', 'service-booking-manager'); ?></label>
                                            <label><input type="checkbox" name="recurring_days[]" value="tue"> <?php esc_html_e('Tuesday', 'service-booking-manager'); ?></label>
                                            <label><input type="checkbox" name="recurring_days[]" value="wed"> <?php esc_html_e('Wednesday', 'service-booking-manager'); ?></label>
                                            <label><input type="checkbox" name="recurring_days[]" value="thu"> <?php esc_html_e('Thursday', 'service-booking-manager'); ?></label>
                                            <label><input type="checkbox" name="recurring_days[]" value="fri"> <?php esc_html_e('Friday', 'service-booking-manager'); ?></label>
                                            <label><input type="checkbox" name="recurring_days[]" value="sat"> <?php esc_html_e('Saturday', 'service-booking-manager'); ?></label>
                                        </div>
                                        <div class="mpwpb_recurring_type">
                                            <label><?php esc_html_e('Recurring Type', 'service-booking-manager'); ?></label>
                                            <select name="mpwpb_recurring_type" id="mpwpb_recurring_type">
                                                <option value=""><?php esc_html_e('Select Recurring Type', 'service-booking-manager'); ?></option>
                                                <?php foreach ($recurring_types as $type) {
                                                    $label = '';
                                                    switch ($type) {
                                                        case 'daily':
                                                            $label = esc_html__('Daily', 'service-booking-manager');
                                                            break;
                                                        case 'weekly':
                                                            $label = esc_html__('Weekly', 'service-booking-manager');
                                                            break;
                                                        case 'bi-weekly':
                                                            $label = esc_html__('Bi-Weekly', 'service-booking-manager');
                                                            break;
                                                        case 'monthly':
                                                            $label = esc_html__('Monthly', 'service-booking-manager');
                                                            break;
                                                    }
                                                    ?>
                                                    <option value="<?php echo esc_attr($type); ?>"><?php echo $label; ?></option>
                                                <?php } ?>
                                            </select>
                                        </div>

                                        <div class="mpwpb_recurring_count">
                                            <label><?php esc_html_e('Number of Occurrences', 'service-booking-manager'); ?></label>
                                            <input type="number" name="mpwpb_recurring_count" id="mpwpb_recurring_count" min="2" max="<?php echo esc_attr($max_recurring_count); ?>" value="2">
                                            <input type="hidden" name="mpwpb_recurring_count_hidden" id="mpwpb_recurring_count_hidden" min="2" max="<?php echo esc_attr($max_recurring_count); ?>" value="2">
                                            <p class="description"><?php esc_html_e('Maximum allowed:', 'service-booking-manager'); ?> <?php echo esc_html($max_recurring_count); ?></p>
                                        </div>

                                        <?php if ($recurring_discount > 0) { ?>
                                            <div class="mpwpb_recurring_discount">
                                                <p data-discount="<?php echo esc_attr($recurring_discount); ?>"><?php esc_html_e('Discount Applied:', 'service-booking-manager'); ?> <?php echo esc_html($recurring_discount); ?>%</p>
                                            </div>
                                        <?php } ?>

                                        <div class="mpwpb_recurring_dates" style="display: none;">
                                            <h4><?php esc_html_e('Recurring Dates', 'service-booking-manager'); ?></h4>
                                            <ul id="mpwpb_recurring_dates_list"></ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php }
                }
                ?>
            </div>
        </div>

    </div>

<?php
    if ( $enable_staff_member === 'on' ) { ?>
        <div class="_dShadow_7_mB_xs mpwpb_date_time_area">
            <div class="mpwpb_date_carousel groupRadioCheck" id="mpwpb_staff_member_booking_area" style="display: none;">
                <div class="_dShadow_7_mB_xs mpwpb_staff_member_booking_area" >
                    <header class="_dFlex_alignCenter_justifyBetween">
                        <h3 class="mpwpb_date_staff_select"><?php esc_html_e('Select Staff', 'service-booking-manager'); ?></h3>
                    </header>

                    <input type="hidden" class="mpwpb_staff_member_booking" name="mpwpb_staff_member_booking" id="mpwpb_staff_member_booking" value="">
                    <div class="mpwpb_staff_member_booking" id="mpwpb_staff_member_holder"></div>
                </div>
            </div>
        </div>
    <?php }

    if ($enable_waiting_list === 'yes') {
        include(MPWPB_Function::template_path('registration/waiting_list_modal.php'));
    }
?>
