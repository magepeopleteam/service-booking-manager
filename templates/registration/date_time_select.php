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
	$short_date_format = $short_date_format ?? MP_Global_Function::get_settings('mp_global_settings', 'date_format_short', 'M , Y');
	$extra_services = $extra_services ?? MP_Global_Function::get_post_info($post_id, 'mpwpb_extra_service', array());
?>
    <div class="_dShadow_7_mB_xs mpwpb_date_time_area">
        <div class="mpwpb_date_carousel groupRadioCheck">
            <header class="_dFlex_alignCenter_justifyBetween">
                <input type="hidden" name="mpwpb_date">
                <h3><?php esc_html_e('Choose Date & Time', 'service-booking-manager'); ?></h3>
				<?php include(MPWPB_Function::template_path('layout/carousel_indicator.php')); ?>
            </header>
            <div class="owl-theme mpwpb-owl-carousel">
		        <?php
			        if (sizeof($all_dates) > 0) {
				        $start_date = $all_dates[0];
				        $end_date = end($all_dates);
				        while (strtotime($start_date) <= strtotime($end_date)) {
					        ?>
                            <div class="fdColumn mpwpb_date_time_line">
                                <div class="_bgTheme_mB_xs_padding_xs fdColumn">
                                    <strong><?php echo MP_Global_Function::date_format($start_date); ?></strong>
                                </div>
						        <?php if (!in_array($start_date, $all_dates)) { ?>
                                    <div class="_mpBtn_mpDisabled_fullHeight_bgLight">
                                        <h4 class="_rotate_90"><?php esc_html_e('Closed', 'service-booking-manager'); ?></h4>
                                    </div>
						        <?php } else {
							        $all_time_slots = MPWPB_Function::get_time_slot($post_id, $start_date);
							        if (sizeof($all_time_slots) > 0) {
								        foreach ($all_time_slots as $slot) {
									        $available = MPWPB_Function::get_total_available($post_id, $slot);
									        if ($available > 0) {
										        ?>
                                                <button type="button" class="_mpBtn to-book" data-date="<?php echo MP_Global_Function::date_format($slot, 'full') ?>" data-radio-check="<?php echo esc_attr($slot); ?>" data-open-icon="fas fa-check" data-close-icon="">
                                                    <!-- <span data-icon></span> --><?php echo date_i18n('h:i A', strtotime($slot)); ?>
                                                </button>
									        <?php } else { ?>
                                                <button type="button" class="_mpBtn mActive booked"><?php esc_html_e('Booked', 'service-booking-manager'); ?></button>
										        <?php
									        }
								        }
							        }
						        } ?>
                            </div>
					        <?php
					        $start_date = date('Y-m-d', strtotime($start_date . ' +1 day'));
				        }
			        } else {
				        ?>
                        <h5><?php echo __('Date not available', 'service-booking-manager'); ?></h5>
				        <?php
			        }
		        ?>
            </div>
        </div>
    </div>
<?php
