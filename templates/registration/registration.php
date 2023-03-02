<?php
	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}
	$post_id        = $post_id ?? get_the_id();
	$all_dates      = $all_dates ?? MPWPB_Function::get_all_date( $post_id );
	$all_services   = $all_services ?? MPWPB_Function::get_post_info( $post_id, 'mpwpb_category_infos', array() );
	$extra_services = $extra_services ?? MPWPB_Function::get_post_info( $post_id, 'mpwpb_extra_service', array() );
	//echo '<pre>'; print_r( $all_services ); echo '</pre>';
	if ( sizeof( $all_services ) > 0 && sizeof( $all_dates ) > 0 ) {
		?>
		<form action="" method="post" class="mpwpb_registration">
			<div class="mpRow">
				<div class="leftSidebar">
					<div class="dLayout dShadow fdColumn">
						<div class="registration_tab_item mptbm_service_tab mpActive">
							<img src="<?php echo esc_attr( MPWPB_PLUGIN_URL . '/assets/helper/images/service_icon.png' ); ?>" alt="<?php esc_attr_e( 'Services', 'mpwpb_plugin' ); ?>"/>
							<span><?php esc_html_e( 'Services', 'mpwpb_plugin' ); ?></span>
						</div>
						<?php if ( sizeof( $extra_services ) > 0 ) { ?>
							<div class="registration_tab_item mptbm_extra_service_tab mpDisabled">
								<img src="<?php echo esc_attr( MPWPB_PLUGIN_URL . '/assets/helper/images/extra_icon.png' ); ?>" alt="<?php esc_attr_e( 'Extra Services', 'mpwpb_plugin' ); ?>"/>
								<span><?php esc_html_e( 'Extra Services', 'mpwpb_plugin' ); ?></span>
							</div>
						<?php } ?>
						<div class="registration_tab_item mpwpb_date_time mpDisabled">
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
				<div class="mainSection">
					<div class="dLayout dShadow fdColumn">
						<?php include( MPWPB_Function::template_path( 'registration/category_selection.php' ) ); ?>
						<?php include( MPWPB_Function::template_path( 'registration/sub_category_selection.php' ) ); ?>
						<?php include( MPWPB_Function::template_path( 'registration/service_selection.php' ) ); ?>
						<?php include( MPWPB_Function::template_path( 'registration/extra_services.php' ) ); ?>
						<?php include( MPWPB_Function::template_path( 'registration/date_time_select.php' ) ); ?>
						<?php include( MPWPB_Function::template_path( 'registration/summary_section.php' ) ); ?>
					</div>
				</div>
			</div>
		</form>
	<?php } ?>