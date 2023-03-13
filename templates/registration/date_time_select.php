<?php
	if ( ! defined( 'ABSPATH' ) ) {
		die;
	}
	$post_id           = $post_id ?? get_the_id();
	$all_dates         = $all_dates ?? MPWPB_Function::get_all_date( $post_id );
	$short_date_format = $short_date_format ?? MPWPB_Function::get_general_settings( 'date_format_short', 'M , Y' );
	$extra_services    = $extra_services ?? MPWPB_Function::get_post_info( $post_id, 'mpwpb_extra_service', array() );
	$service_text      = $service_text ?? MPWPB_Function::get_service_text( $post_id );
?>
	<div class="mpwpb_date_time_area">
		<div class="mpwpb_date_carousel groupRadioCheck">
			<input type="hidden" name="mpwpb_date">
			<h3 class="mB_xs"><?php esc_html_e( 'Choose Date & Time', 'mpwpb_plugin' ); ?></h3>
			<?php include( MPWPB_Function::template_path( 'layout/carousel_indicator.php' ) ); ?>
			<div class="divider"></div>
			<div class="owl-theme owl-carousel">
				<?php
					$start_date = $all_dates[0];
					$end_date   = end( $all_dates );
					while ( strtotime( $start_date ) <= strtotime( $end_date ) ) {
						?>
						<div class="fdColumn mpwpb_date_time_line">
							<div class="_bgTheme_mB_xs_radius_padding_xs fdColumn">
								<h2 class="textWhite"><?php echo date_i18n( 'd', strtotime( $start_date ) ); ?></h2>
								<p><?php echo date_i18n( $short_date_format, strtotime( $start_date ) ); ?></p>
								<h6 class="textWhite textUppercase"><?php echo date_i18n( 'l', strtotime( $start_date ) ); ?></h6>
							</div>
							<?php if ( ! in_array( $start_date, $all_dates ) ) { ?>
								<button type="button" class="_mpBtn_radius"><?php esc_html_e( 'Closed', 'mpwpb_plugin' ); ?></button>
							<?php } else {
								$all_time_slots = MPWPB_Function::get_time_slot( $post_id, $start_date );
								if ( sizeof( $all_time_slots ) > 0 ) {
									foreach ( $all_time_slots as $slot ) {
										$available = MPWPB_Function::get_total_available( $post_id, $slot );
										if ( $available > 0 ) {
											?>
											<button type="button" class="_mpBtn_radius" data-date="<?php echo MPWPB_Function::date_format( $slot, 'full' ) ?>" data-radio-check="<?php echo esc_attr( $slot ); ?>" data-open-icon="fas fa-check" data-close-icon="">
												<span data-icon></span><?php echo date_i18n( 'h:i A', strtotime( $slot ) );  ?>
											</button>
										<?php } else { ?>
											<button type="button" class="_mpBtn_radius"><?php esc_html_e( 'Fully Booked', 'mpwpb_plugin' ); ?></button>
											<?php
										}
									}
								}
							} ?>
						</div>
						<?php
						$start_date = date( 'Y-m-d', strtotime( $start_date . ' +1 day' ) );
					}
				?>
			</div>
			<div class="divider"></div>
			<div class="justifyBetween">
				<button class="_mpBtn_mT_xs_radius mpActive mpwpb_date_time_prev" type="button">
					<i class="fas fa-long-arrow-alt-left _mR_xs"></i>
					<?php echo esc_html__( 'Previous', 'mpwpb_plugin' ) . ' ' . $service_text; ?>
				</button>
				<h3 class="alignCenter"><?php esc_html_e( 'Total :', 'mpwpb_plugin' ); ?>&nbsp;&nbsp;<i class="mpwpb_total_bill textTheme"><?php echo MPWPB_Function::wc_price( $post_id, 0 ); ?></i></h3>
				<button class="_mpBtn_mT_xs_radius mActive mpwpb_date_time_next" type="button" data-alert="<?php esc_html_e( 'Please Select Date & Time', 'mpwpb_plugin' ); ?>">
					<?php esc_html_e( 'Next Summary', 'mpwpb_plugin' ); ?>
					<i class="fas fa-long-arrow-alt-right _mL_xs"></i>
				</button>
			</div>
		</div>
	</div>
<?php