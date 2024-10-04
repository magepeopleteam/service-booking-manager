<?php
	// Template Name: Default Theme
	/*
   * @Author 		engr.sumonazma@gmail.com
   * Copyright: 	mage-people.com
   */
	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}
	$post_id = $post_id ?? get_the_id();
	$all_dates = $all_dates ?? MPWPB_Function::get_date($post_id);
	$all_services = $all_services ?? MP_Global_Function::get_post_info($post_id, 'mpwpb_category_infos', array());
	$extra_services = $extra_services ?? MP_Global_Function::get_post_info($post_id, 'mpwpb_extra_service', array());
    $sub_title = MP_Global_Function::get_post_info( $post_id, 'mpwpb_shortcode_sub_title' );
?>
	<div class="mpStyle mpwpb_default_theme">
		<div class="mpContainer">
            <h2><?php the_title(); ?></h2>
			<?php if ( $sub_title ) { ?>
				<p><?php echo esc_html( $sub_title ); ?></p>
			<?php } ?>

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
	                            <?php include(MPWPB_Function::template_path('registration/category_selection.php')); ?>
								<?php include(MPWPB_Function::template_path('registration/service_selection.php')); ?>
								<?php include(MPWPB_Function::template_path('registration/extra_services.php')); ?>

                            </div>
							<?php include(MPWPB_Function::template_path('registration/date_time_select.php')); ?>
                            <div class="mpwpb_order_proceed_area"></div>
                            
	                        <?php include(MPWPB_Function::template_path('registration/next_service.php')); ?>
	                        <?php include(MPWPB_Function::template_path('registration/next_date_time.php')); ?>
                        </div>
                    </div>
                </div>
            </div>
		</div>
	</div>
<?php do_action( 'mpwpb_after_details_page' ); ?>