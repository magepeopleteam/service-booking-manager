<?php
	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}
	$post_id           = $post_id ?? get_the_id();
	$all_category      = $all_category ?? MPWPB_Function::get_category( $post_id );
	$all_services      = $all_services ?? MPWPB_Function::get_post_info( $post_id, 'mpwpb_category_infos', array() );
	$all_sub_category  = $all_sub_category ?? MPWPB_Function::get_sub_category( $post_id );
	$all_service_list  = $all_service_list ?? MPWPB_Function::get_all_service( $post_id );
	$category_text     = $category_text ?? MPWPB_Function::get_category_text( $post_id );
	$sub_category_text = $sub_category_text ?? MPWPB_Function::get_sub_category_text( $post_id );
	$service_text      = $service_text ?? MPWPB_Function::get_service_text( $post_id );
	$extra_services    = $extra_services ?? MPWPB_Function::get_post_info( $post_id, 'mpwpb_extra_service', array() );
	if ( sizeof( $all_service_list ) > 0 ) {
		//echo '<pre>';print_r($all_services);echo '</pre>';
		?>
		<div class="mpwpb_service_area mT">
			<input type="hidden" name="mpwpb_category" value="">
			<input type="hidden" name="mpwpb_sub_category" value="">
			<input type="hidden" name="mpwpb_service" value="">
			<h3 class="mB_xs"><?php echo esc_html__( 'Select', 'mpwpb_plugin' ) . ' ' . $service_text; ?></h3>
			<div class="divider"></div>
			<div class="flexWrapJustifyBetween">
				<?php
					foreach ( $all_service_list as $service_item ) {
						$category_name     = array_key_exists( 'category', $service_item ) ? $service_item['category'] : '';
						$sub_category_name = array_key_exists( 'sub_category', $service_item ) ? $service_item['sub_category'] : '';
						$service_name      = array_key_exists( 'service', $service_item ) ? $service_item['service'] : '';
						$service_image     = array_key_exists( 'image', $service_item ) ? $service_item['image'] : '';
						$service_icon      = array_key_exists( 'icon', $service_item ) ? $service_item['icon'] : '';
						$service_price     = array_key_exists( 'price', $service_item ) ? $service_item['price'] : 0;
						$service_price     = MPWPB_Function::get_price( $post_id, $service_name, $category_name, $sub_category_name );
						$service_details   = array_key_exists( 'details', $service_item ) ? $service_item['details'] : '';
						$service_duration  = array_key_exists( 'duration', $service_item ) ? $service_item['duration'] : '';
						?>
						<div class="mpwpb_item_box mpwpb_service_item dShadow_8" data-price="<?php echo esc_attr( $service_price ); ?>" data-category="<?php echo esc_attr( $category_name ); ?>" data-sub-category="<?php echo esc_attr( $sub_category_name ); ?>" data-service="<?php echo esc_attr( $service_name ); ?>">
							<div class="dFlex">
								<div class="service_img_area alignCenter">
									<?php if ( $service_image ) { ?>
										<div class="bg_image_area">
											<div data-bg-image="<?php echo esc_attr( MPWPB_Function::get_image_url( '', $service_image, 'medium' ) ); ?>"></div>
										</div>
									<?php } ?>
									<?php if ( $service_icon ) { ?>
										<div class="allCenter mpwpb_icon_area">
											<span class="<?php echo esc_attr( $service_icon ); ?>"></span>
										</div>
									<?php } ?>
								</div>
								<div class="fdColumn">
									<h5 class="mB_xs"><?php echo esc_html( $service_name ); ?></h5>
									<?php if ( $service_details ) { ?>
										<span><?php echo MPWPB_Function::esc_html( $service_details ); ?></span>
									<?php } ?>
									<div class="justifyBetween _mT">
										<h6 class="_dLayout_xs_textTheme_bTheme"><?php echo wc_price( $service_price ); ?></h6>
										<?php if ( $service_duration ) { ?>
											<h5 class="textTheme alignCenter">
												<span class="fas fa-clock mR_xs"></span>
												<span><?php echo MPWPB_Function::esc_html( $service_duration ); ?></span>
											</h5>
										<?php } ?>
									</div>
								</div>
							</div>
							<span class="fas fa-check mpwpb_item_check _circleIcon_xs"></span>
						</div>
					<?php } ?>
			</div>
			<div class="divider"></div>
			<?php include( MPWPB_Function::template_path( 'registration/extra_services.php' ) ); ?>
			<div class="justifyBetween">
				<?php if ( sizeof( $all_category ) > 0 ) { ?>
					<button class="_mpBtn_mT_xs_radius mpActive mpwpb_service_prev" type="button">
						<i class="fas fa-long-arrow-alt-left _mR_xs"></i>
						<?php
							if ( sizeof( $all_sub_category ) > 0 ) {
								echo esc_html__( 'Previous', 'mpwpb_plugin' ) . ' ' . $sub_category_text;
							} else {
								echo esc_html__( 'Previous', 'mpwpb_plugin' ) . ' ' . $category_text;
							}
						?>
					</button>
				<?php } else { ?>
					<div></div>
				<?php } ?>
				<button class="_mpBtn_mT_xs_radius mActive mpwpb_service_next" type="button" data-alert="<?php echo esc_html__( 'Please Select', 'mpwpb_plugin' ) . ' ' . $service_text; ?>">
					<?php esc_html_e( 'Next Date & Time', 'mpwpb_plugin' ); ?>
					<i class="fas fa-long-arrow-alt-right _mL_xs"></i>
				</button>
			</div>
		</div>
		<?php
	}