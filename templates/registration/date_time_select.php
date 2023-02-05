<?php
	if ( ! defined( 'ABSPATH' ) ) {
		die;
	}
	$post_id           = $post_id ?? get_the_id();
	$all_dates         = $all_dates ?? MPWPB_Function::get_all_date( $post_id );
	$short_date_format = $short_date_format ?? MPWPB_Function::get_general_settings( 'date_format_short', 'M , Y' );
?>
	<div class="mpwpb_date_carousel groupRadioCheck">
		<input type="hidden" name="mpwpb_date">
		<h4 class="_textTheme_mT_xs"><?php esc_html_e( 'Choose Date & Time', 'mpwpb_plugin' ); ?></h4>
		<?php include( MPWPB_Function::template_path( 'layout/carousel_indicator.php' ) ); ?>
		<div class="divider"></div>
		<div class="owl-theme owl-carousel">
			<?php
				$start_date = $all_dates[0];
				$end_date   = end( $all_dates );
				while ( strtotime( $start_date ) <= strtotime( $end_date ) ) {
					?>
					<div class="fdColumn mpwpb_date_time_line">
						<h3 class="textTheme"><?php echo date_i18n( 'd', strtotime( $start_date ) ); ?></h3>
						<p class="textWarning"><?php echo date_i18n( $short_date_format, strtotime( $start_date ) ); ?></p>
						<h6 class="textInfo textUppercase"><?php echo date_i18n( 'l', strtotime( $start_date ) ); ?></h6>
						<div class="divider"></div>
						<?php if ( ! in_array( $start_date, $all_dates ) ) { ?>
							<h6><?php esc_html_e( 'Closed', 'mpwpb_plugin' ); ?></h6>
						<?php } else {
							$all_time_slots = MPWPB_Function::get_time_slot( $post_id, $start_date );
							if ( sizeof( $all_time_slots ) > 0 ) {
								foreach ( $all_time_slots as $slot ) {
									?>
									<button type="button" class="_dButton_xs bgWhite textColor_1" data-radio-check="<?php echo esc_attr( $slot ); ?>" data-icon-change data-open-icon="fas fa-check" data-close-icon="">
										<span data-icon></span><?php echo date_i18n( 'h:i A', strtotime( $slot ) ); ?>
									</button>
									<?php
								}
							}
						} ?>
					</div>
					<?php
					$start_date = date( 'Y-m-d', strtotime( $start_date . ' +1 day' ) );
				}
			?>
		</div>
	</div>
<?php