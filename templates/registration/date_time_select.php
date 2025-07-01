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
	$recurring_types = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_recurring_types', array('weekly', 'bi-weekly', 'monthly'));
	$max_recurring_count = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_max_recurring_count', 10);
	$recurring_discount = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_recurring_discount', 0);

    $enable_staff_member = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_staff_member_add', 'no');

	$short_date_format = $short_date_format ?? MPWPB_Global_Function::get_settings('mpwpb_global_settings', 'date_format_short', 'M , Y');
	$extra_services = $extra_services ?? MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_extra_service', array());


    function display_time( $post_id, $all_dates ){
        $start_date = $all_dates[0];
        $end_date = end($all_dates);
        $start = 0;
        while (strtotime($start_date) <= strtotime($end_date)) {
            if( $start === 0 ){
                $display = 'flex';
            }else{
                $display = 'none';
            }
            ?>
            <div class="mpwpb_time_display" id="<?php echo esc_attr($start_date);?>" style="display: <?php echo esc_attr( $display );?>" data-date-filder="<?php echo esc_attr( $start_date );?>">
                   <?php
                $all_time_slots = MPWPB_Function::get_time_slot( $post_id, $start_date );
                if (sizeof($all_time_slots) > 0) {
                    foreach ($all_time_slots as $slot) {
                        $available = MPWPB_Function::get_total_available($post_id, $slot );
                        if ($available > 0) {
                            ?>
                            <button type="button" class=" to-book mpwpb_time_btn" data-date="<?php echo esc_attr(MPWPB_Global_Function::date_format($slot, 'full')); ?>" data-radio-check="<?php echo esc_attr($slot); ?>" data-open-icon="fas fa-check" data-close-icon="">
                                <!-- <span data-icon></span> --><?php echo esc_html(date_i18n('h:i A', strtotime($slot))); ?>
                            </button>
                        <?php } else {
                            ?>
                            <button type="button" class="_mpBtn mActive booked"><?php esc_html_e('Booked', 'service-booking-manager'); ?></button>
                            <?php
                        }
                    }
                } ?>
            </div>
            <?php
                $start_date = date_i18n('Y-m-d', strtotime($start_date . ' +1 day'));
                $start++;
        }
    }
    function display_date_time( $post_id, $all_dates ){
        $start_date = $all_dates[0];
        $end_date = end($all_dates);

        $loop_start = 0;
        while (strtotime($start_date) <= strtotime($end_date)) {
            if( $loop_start === 0 ){
                $selected = 'mpwpb_get_date_selected';
            }else{
                $selected = '';
            }
            ?>
            <div class="fdColumn mpwpb_date_time_line">

                <?php if (!in_array($start_date, $all_dates)) {
                    ?>
                    <div class="_mpBtn_mpDisabled_fullHeight_bgLight mpwpb_get_close_date">
                        <h6 class="_rotate_90 mpwpb_close_text"><?php esc_html_e('Closed', 'service-booking-manager'); ?></h6>
                        <div class="mpwpb_close_date"><?php echo esc_html(MPWPB_Global_Function::date_format($start_date)); ?></div>
                    </div>
                <?php } else { ?>
                    <div class="<?php echo esc_attr( $selected );?> mpwpb_get_date" data-find-time="<?php echo esc_attr( $start_date );?>">
                        <strong><?php echo esc_html(MPWPB_Global_Function::date_format($start_date)); ?></strong>
                    </div>
                <?php } ?>
            </div>
            <?php
            $start_date = date_i18n('Y-m-d', strtotime($start_date . ' +1 day'));
            $loop_start++;
        }
    }

?>
    <div class="_dShadow_7_mB_xs mpwpb_date_time_area">
        <div class="mpwpb_date_carousel groupRadioCheck">
            <header class="_dFlex_alignCenter_justifyBetween">
                <input type="hidden" name="mpwpb_date">
                <h3 id="mpwpb_show_hide_date_time" class="mpwpb_date_staff_select"><?php esc_html_e('Choose Date & Time', 'service-booking-manager'); ?></h3>
				<?php include(MPWPB_Function::template_path('layout/carousel_indicator.php')); ?>
            </header>
            <div class="" id="mpwpb_datetime_holder">
                <div class="owl-theme mpwpb-owl-carousel" id="mpwpb_datetime_holder1">
                    <?php if (sizeof($all_dates) > 0) {
                        wp_kses_post( display_date_time( $post_id, $all_dates ) );
                    ?>
                </div>
                <div class="mpwpb_select_time_holder">
                    <?php
                        wp_kses_post( display_time( $post_id, $all_dates ) );
                    ?>
                </div>
                <?php
                } else {
                ?>
                    <h5><?php esc_html_e('Date not available', 'service-booking-manager'); ?></h5> <?php
                }
                if ($enable_recurring === 'yes') { ?>
                    <div class="_dShadow_7_mB_xs mpwpb_recurring_booking_area" id="mpwpb_recurring_booking_area" style="display: block;">
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
                                    <div class="mpwpb_recurring_type">
                                        <label><?php esc_html_e('Recurring Type', 'service-booking-manager'); ?></label>
                                        <select name="mpwpb_recurring_type" id="mpwpb_recurring_type">
                                            <option value=""><?php esc_html_e('Select Recurring Type', 'service-booking-manager'); ?></option>
                                            <?php foreach ($recurring_types as $type) {
                                                $label = '';
                                                switch ($type) {
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
                ?>
            </div>

        </div>
    </div>

<?php
    if ( $enable_staff_member === 'on' ) { ?>
        <div class="_dShadow_7_mB_xs mpwpb_date_time_area">
            <div class="mpwpb_date_carousel groupRadioCheck">
                <div class="_dShadow_7_mB_xs mpwpb_staff_member_booking_area" id="mpwpb_staff_member_booking_area" style="display: none;">
                    <header class="_dFlex_alignCenter_justifyBetween">
                        <h3 id="mpwpb_show_hide_staff_member" class="mpwpb_date_staff_select"><?php esc_html_e('Select Staff', 'service-booking-manager'); ?></h3>
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
