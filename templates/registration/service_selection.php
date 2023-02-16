<?php
	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}
	$post_id      = $post_id ?? get_the_id();
	$all_services = $all_services ?? MPWPB_Function::get_post_info( $post_id, 'mpwpb_category_infos', array() );
	if ( sizeof( $all_services ) > 0 ) {
		//echo '<pre>';print_r($all_services);echo '</pre>';
		?>
		<div class="mpwpb_service_area mT">
			<input type="hidden" name="mpwpb_category" value="">
			<input type="hidden" name="mpwpb_sub_category" value="">
			<div class="groupRadioCheck flexWrap">
				<input type="hidden" name="mpwpb_service" value="">
				<?php
					foreach ( $all_services as $all_service ) {
						$category_name  = array_key_exists( 'category', $all_service ) ? $all_service['category'] : '';
						$sub_categories = array_key_exists( 'sub_category', $all_service ) ? $all_service['sub_category'] : array();
						if ( sizeof( $sub_categories ) > 0 ) {
							foreach ( $sub_categories as $sub_category ) {
								$sub_category_name = array_key_exists( 'name', $sub_category ) ? $sub_category['name'] : '';
								$services          = array_key_exists( 'service', $sub_category ) ? $sub_category['service'] : array();
								?>
								<div class="fullWidth sub_category_area" data-category="<?php echo esc_attr( $category_name ); ?>">
									<h5><?php echo esc_html( $sub_category_name ? esc_html( $sub_category_name ) : esc_html__( 'Select Service', 'mpwpb_plugin' ) ); ?></h5>
									<div class="divider"></div>
									<?php
										if ( sizeof( $services ) > 0 ) {
											foreach ( $services as $service ) {
												$image              = array_key_exists( 'img', $service ) ? $service['img'] : '';
												$service_name       = array_key_exists( 'name', $service ) ? $service['name'] : '';
												$service_price      = array_key_exists( 'price', $service ) ? $service['price'] : 0;
												$service_price      = MPWPB_Function::wc_price( $post_id, $service_price );
												$service_price_raw  = MPWPB_Function::price_convert_raw( $service_price );
												$service_details_id = array_key_exists( 'details_id', $service ) ? $service['details_id'] : '';
												?>
												<div class="dLayout_xs mpwpb_price_calculation" data-price data-value="<?php echo esc_attr( $service_price_raw ); ?>" data-category="<?php echo esc_attr( $category_name ); ?>" data-sub-category="<?php echo esc_attr( $sub_category_name ); ?>" data-radio-check="<?php echo esc_attr( $service_name ); ?>" data-open-icon="fas fa-check" data-close-icon="">
													<div class="flexWrap">
														<div class="w_150 _pR">
															<div class="bg_image_area">
																<div data-bg-image="<?php echo esc_attr( MPWPB_Function::get_image_url( '', $image, 'medium' ) ); ?>"></div>
																<h2 class="fullAbsolute allCenter textTheme"><span data-icon></span></h2>
															</div>
														</div>
														<div class="flexAuto">
															<div class="justifyBetween">
																<h5><?php echo esc_html( $service_name ); ?></h5>
																<h3 class="textTheme"><?php echo MPWPB_Function::esc_html( $service_price ); ?></h3>
															</div>
															<?php if ( $service_details_id ) { ?>
																<div class="divider"></div>
																<div class="mp_wp_editor" data-placeholder>
																	<div>
																		<?php echo MPWPB_Function::esc_html( get_post_field( 'post_content', $service_details_id ) ); ?>
																	</div>
																</div>
															<?php } ?>
														</div>
													</div>
												</div>
												<?php
											}
										}
									?>
								</div>
								<?php
							}
						}
					}
				?>
			</div>
		</div>
		<?php
	}