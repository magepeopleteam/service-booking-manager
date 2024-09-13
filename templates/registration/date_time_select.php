<?php
	/*
   * @Author 		engr.sumonazma@gmail.com
   * Copyright: 	mage-people.com
   */
	if (!defined('ABSPATH')) {
		die;
	}
	$post_id = $post_id ?? get_the_id();
	$link_wc_product = MP_Global_Function::get_post_info($post_id, 'link_wc_product');
	$all_dates = $all_dates ?? MPWPB_Function::get_date($post_id);
	$short_date_format = $short_date_format ?? MP_Global_Function::get_settings('mp_global_settings', 'date_format_short', 'M , Y');
	$extra_services = $extra_services ?? MP_Global_Function::get_post_info($post_id, 'mpwpb_extra_service', array());
	$service_text = $service_text ?? MPWPB_Function::get_service_text($post_id);
?>
    <div class="_dShadow_7_mB_xs mpwpb_date_time_area">
        <div class="mpwpb_date_carousel groupRadioCheck">
            <header class="_dFlex_alignCenter_justifyBetween">
                <input type="hidden" name="mpwpb_date">
                <h3><?php esc_html_e('Choose Date & Time', 'service-booking-manager'); ?></h3>
				<?php include(MPWPB_Function::template_path('layout/carousel_indicator.php')); ?>
            </header>
            <div class="padding">
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
                                                    <button type="button" class="_mpBtn" data-date="<?php echo MP_Global_Function::date_format($slot, 'full') ?>" data-radio-check="<?php echo esc_attr($slot); ?>" data-open-icon="fas fa-check" data-close-icon="">
                                                        <span data-icon></span><?php echo date_i18n('h:i A', strtotime($slot)); ?>
                                                    </button>
												<?php } else { ?>
                                                    <button type="button" class="_mpBtn"><?php esc_html_e('Fully Booked', 'service-booking-manager'); ?></button>
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
                <div class="divider"></div>
                <div class="justifyBetween mpwpb-booking-navigation">
                    <button class="_mpBtn_dBR_padding mpActive mpwpb_date_time_prev" type="button">
                        <i class="fas fa-long-arrow-alt-left _mR_xs"></i>
						<?php echo esc_html__('Previous', 'service-booking-manager') . ' ' . $service_text; ?>
                    </button>
                    <h4 class="alignCenter mpwpb-total">
						<?php esc_html_e('Total :', 'service-booking-manager'); ?>&nbsp;&nbsp;
                        <span class="mpwpb_total_bill textTheme"><?php echo MP_Global_Function::wc_price($post_id, 0); ?></span>
                    </h4>
                    <button class="_mpBtn_dBR_padding mActive mpwpb_date_time_next" type="button" data-wc_link_id="<?php echo esc_attr($link_wc_product); ?>" data-alert="<?php esc_html_e('Please Select Date & Time', 'service-booking-manager'); ?>">
						<?php esc_html_e('Next Summary', 'service-booking-manager'); ?>
                        <i class="fas fa-long-arrow-alt-right _mL_xs"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
<?php
