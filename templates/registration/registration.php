<?php
	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}
	$post_id        = $post_id ?? get_the_id();
	$all_dates      = $all_dates ?? MPWPB_Function::get_all_date( $post_id );
	$all_services   = $all_services ?? MPWPB_Function::get_post_info( $post_id, 'mpwpb_category_infos', array() );
	$extra_services = $extra_services ?? MPWPB_Function::get_post_info( $post_id, 'mpwpb_extra_service', array() );
	$service_text   = $service_text ?? MPWPB_Function::get_service_text( $post_id );
	if ( sizeof( $all_services ) > 0 && sizeof( $all_dates ) > 0 ) {
		?>
		<form action="" method="post" class="mpwpb_registration">
			<div class="mpRow">
				<div class="leftSidebar">
					<div class="mp_sticky_menu">
						<div class="dLayout dShadow_7 fdColumn">
							<div class="registration_tab_item mptbm_service_tab mpActive">
								<img src="<?php echo esc_attr( MPWPB_PLUGIN_URL . '/assets/helper/images/service_icon.png' ); ?>" alt="<?php esc_attr_e( 'Services', 'mpwpb_plugin' ); ?>"/>
								<span><?php esc_html_e( 'Services', 'mpwpb_plugin' ); ?></span>
							</div>
							<div class="registration_tab_item mpwpb_date_time_tab mpDisabled">
								<img src="<?php echo esc_attr( MPWPB_PLUGIN_URL . '/assets/helper/images/date_time_icon.png' ); ?>" alt="<?php esc_attr_e( 'Date & Time', 'mpwpb_plugin' ); ?>"/>
								<span><?php esc_html_e( 'Date & Time', 'mpwpb_plugin' ); ?></span>
							</div>
							<div class="registration_tab_item mptbm_summary_tab mpDisabled">
								<img src="<?php echo esc_attr( MPWPB_PLUGIN_URL . '/assets/helper/images/summary_icon.png' ); ?>" alt="<?php esc_attr_e( 'Summary', 'mpwpb_plugin' ); ?>"/>
								<span><?php esc_html_e( 'Summary', 'mpwpb_plugin' ); ?></span>
							</div>
						</div>
						<?php include( MPWPB_Function::template_path( 'registration/summary_left.php' ) ); ?>
					</div>
				</div>
				<div class="mainSection">
					<div class="dShadow_7 fdColumn">
						<div class="all_service_area fdColumn">
							<?php include( MPWPB_Function::template_path( 'registration/category_selection.php' ) ); ?>
							<?php include( MPWPB_Function::template_path( 'registration/sub_category_selection.php' ) ); ?>
							<?php include( MPWPB_Function::template_path( 'registration/service_selection.php' ) ); ?>
							<?php include( MPWPB_Function::template_path( 'registration/extra_services.php' ) ); ?>
							<div class="next_date_time_area">
								<div class="divider"></div>
								<div class="justifyBetween">
									<div></div>
									<button class="_mpBtn_mT_xs_radius mActive mpwpb_service_next" type="button" data-alert="<?php echo esc_html__( 'Please Select', 'mpwpb_plugin' ) . ' ' . $service_text; ?>">
										<?php esc_html_e( 'Next Date & Time', 'mpwpb_plugin' ); ?>
										<i class="fas fa-long-arrow-alt-right _mL_xs"></i>
									</button>
								</div>
							</div>
						</div>
						<?php include( MPWPB_Function::template_path( 'registration/date_time_select.php' ) ); ?>
						<?php include( MPWPB_Function::template_path( 'registration/summary_section.php' ) ); ?>
					</div>
				</div>
			</div>
		</form>
	<?php } ?>