<?php
	/*
   * @Author 		engr.sumonazma@gmail.com
   * Copyright: 	mage-people.com
   */
	if (!defined('ABSPATH')) {
		exit;
	}
	$post_id = $post_id ?? get_the_id();
	$all_dates = $all_dates ?? MPWPB_Function::get_date($post_id);
	$all_services = $all_services ?? MP_Global_Function::get_post_info($post_id, 'mpwpb_category_infos', array());
	$extra_services = $extra_services ?? MP_Global_Function::get_post_info($post_id, 'mpwpb_extra_service', array());
	$service_text = $service_text ?? MPWPB_Function::get_service_text($post_id);
?>
    <div class="mpwpb_registration mp_sticky_section">
        <div class="mpRow">
            <div class="leftSidebar">
                <div class="mp_sticky_area">
                    <div class="_dLayout_dShadow_7_bRL_dFlex_fdColumn">
                        <div class="registration_tab_item mpwpb_service_tab mpActive">
                            <img src="<?php echo esc_attr(MPWPB_PLUGIN_URL . '/assets/images/service_icon.png'); ?>" alt="<?php esc_attr_e('Services', 'service-booking-manager'); ?>"/>
                            <span><?php esc_html_e('Services', 'service-booking-manager'); ?></span>
                        </div>
                        <div class="registration_tab_item mpwpb_date_time_tab mpDisabled">
                            <img src="<?php echo esc_attr(MPWPB_PLUGIN_URL . '/mp_global/assets/images/date_time_icon.png'); ?>" alt="<?php esc_attr_e('Date & Time', 'service-booking-manager'); ?>"/>
                            <span><?php esc_html_e('Date & Time', 'service-booking-manager'); ?></span>
                        </div>
                        <div class="registration_tab_item mpwpb_order_proceed_tab mpDisabled">
                            <img src="<?php echo esc_attr(MPWPB_PLUGIN_URL . '/assets/images/summary_icon.png'); ?>" alt="<?php esc_attr_e('Order Proceed', 'service-booking-manager'); ?>"/>
                            <span><?php esc_html_e('Order Proceed', 'service-booking-manager'); ?></span>
                        </div>
                    </div>
					<?php include(MPWPB_Function::template_path('registration/summary_left.php')); ?>
                </div>
            </div>
            <div class="mainSection ">
                <div class="_dFlex_fdColumn mpwpb_main_section mp_sticky_depend_area">
                    <div class="all_service_area ">
                        <div class="_equalChild _mB_xs">
							<?php include(MPWPB_Function::template_path('registration/category_selection.php')); ?>
							<?php //include(MPWPB_Function::template_path('registration/category_selection_static.php')); ?>
                        </div>
						<?php include(MPWPB_Function::template_path('registration/service_selection.php')); ?>
						<?php include(MPWPB_Function::template_path('registration/extra_services.php')); ?>
                        <div class="next_date_time_area">
                            <div class="justifyBetween">
                                <h3 class="alignCenter"><?php esc_html_e('Total :', 'service-booking-manager'); ?>&nbsp;&nbsp;<span class="mpwpb_total_bill textTheme"><?php echo MP_Global_Function::wc_price($post_id, 0); ?></span></h3>
                                <button class="_mpBtn_dBR_padding mActive mpwpb_service_next" type="button" data-alert="<?php echo esc_html__('Please Select', 'service-booking-manager') . ' ' . $service_text; ?>">
									<?php esc_html_e('Next Date & Time', 'service-booking-manager'); ?>
                                    <i class="fas fa-long-arrow-alt-right _mL_xs"></i>
                                </button>
                            </div>
                        </div>
                    </div>
					<?php include(MPWPB_Function::template_path('registration/date_time_select.php')); ?>
                    <div class="mpwpb_order_proceed_area"></div>
					<?php //include( MPWPB_Function::template_path( 'registration/summary_section.php' ) ); ?>
                </div>
            </div>
        </div>
    </div>
<?php