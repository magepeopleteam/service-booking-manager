<?php
	if ( ! defined( 'ABSPATH' ) ) {
		die;
	}
	$post_id           = $post_id ?? get_the_id();
	$category_infos    = $category_infos??MPWPB_Function::get_post_info( $post_id, 'mpwpb_category_infos', array() );
	$category_name     = $category_name ?? '';
	$all_dates         = $all_dates ?? MPWPB_Function::get_all_date( $post_id );
	$short_date_format = $short_date_format ?? MPWPB_Function::get_general_settings( 'date_format_short', 'M , Y' );
	if ( sizeof( $category_infos ) > 0 && sizeof( $all_dates ) > 0 ) {
		foreach ( $category_infos as $categories ) {
			$current_category_name = array_key_exists( 'category', $categories ) ? $categories['category'] : '';
			$category_name         = $category_name ?: $current_category_name;
			if ( $current_category_name == $category_name ) {
				$service_infos  = array_key_exists( 'service', $categories ) ? $categories['service'] : '';
				if ( sizeof( $service_infos ) > 0 ) {
					foreach ( $service_infos as $service_info ) {
						$image              = array_key_exists( 'img', $service_info ) ? $service_info['img'] : '';
						$service_name       = array_key_exists( 'name', $service_info ) ? $service_info['name'] : '';
						$service_price      = array_key_exists( 'price', $service_info ) ? $service_info['price'] : 0;
						$service_price      = MPWPB_Function::wc_price( $post_id, $service_price );
						$service_price_raw  = MPWPB_Function::price_convert_raw( $service_price );
						$service_details_id = array_key_exists( 'details_id', $service_info ) ? $service_info['details_id'] : '';
						$unique_id          = uniqid();

						?>
						<div class="dLayout_xs">
							<form action="" method="post" class="mpwpb_registration_area">
								<input type="hidden" name="mpwpb_post_id" value="<?php echo esc_attr( $post_id ); ?>"/>
								<input type="hidden" name="mpwpb_category" value="<?php echo esc_attr( $category_name ); ?>"/>
								<input type="hidden" name="mpwpb_service" value="<?php echo esc_attr( $service_name ); ?>"/>
								<div class="flexWrap">
									<div class="col_4 _pR">
										<div class="bg_image_area">
											<div data-bg-image="<?php echo esc_attr( MPWPB_Function::get_image_url( '', $image, 'medium' ) ); ?>"></div>
										</div>
									</div>
									<div class="col_8">
										<div class="justifyBetween">
											<h5><?php echo esc_html( $service_name ); ?></h5>
											<h3 class="textTheme"><?php echo MPWPB_Function::esc_html( $service_price ); ?></h3>
										</div>
										<div class="divider"></div>
										<?php if ( $service_details_id ) { ?>
											<div class="mp_wp_editor" data-placeholder>
												<div>
													<?php echo MPWPB_Function::esc_html( get_post_field( 'post_content', $service_details_id ) ); ?>
												</div>
											</div>
										<?php } ?>
										<div class="divider"></div>
										<div class="justifyEnd">
											<button type="button" class="dButton mpwpb_price_calculation" data-open-icon="far fa-check-circle" data-close-icon="" data-collapse-target="<?php echo esc_attr( $unique_id ); ?>" data-open-text="<?php esc_html_e( 'Select Now', 'mpwpb_plugin' ); ?>" data-close-text="<?php esc_html_e( 'Selected', 'mpwpb_plugin' ); ?>" data-add-class="mpActive">
												<span data-text><?php esc_html_e( 'Select Now', 'mpwpb_plugin' ); ?></span><span data-icon></span>
												<input type="hidden" data-price data-value="<?php echo esc_attr( $service_price_raw ); ?>" value=""/>
											</button>
										</div>
									</div>
								</div>
								<div data-collapse="<?php echo esc_attr( $unique_id ); ?>">
									<h4 class="_textTheme"><?php esc_html_e( 'Choose Extra Features', 'mpwpb_plugin' ); ?></h4>
									<div class="divider"></div>
									<?php include( MPWPB_Function::template_path( 'registration/extra_services.php' ) ); ?>
									<?php include( MPWPB_Function::template_path( 'registration/date_time_select.php' ) ); ?>
									<div class="divider"></div>
									<?php include( MPWPB_Function::template_path( 'registration/book_now.php' ) ); ?>
								</div>
							</form>
						</div>
						<?php
					}
				}
			}
		}
		//echo '<pre>';print_r( $category_infos );echo '</pre>';
	}
