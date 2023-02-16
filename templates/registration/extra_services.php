<?php
	if ( ! defined( 'ABSPATH' ) ) {
		die;
	}
	$post_id        = $post_id ?? get_the_id();
	$extra_services = $extra_services ?? MPWPB_Function::get_post_info( $post_id, 'mpwpb_extra_service', array() );
	if ( sizeof( $extra_services ) > 0 ) {
		?>
		<h4 class="_textTheme"><?php esc_html_e( 'Choose Extra Features (Optional)', 'mpwpb_plugin' ); ?></h4>
		<div class="divider"></div>
		<?php
		foreach ( $extra_services as $group_service ) {
			$group_service_name = array_key_exists( 'group_service', $group_service ) ? $group_service['group_service'] : '';
			$ex_service_infos   = array_key_exists( 'group_service_info', $group_service ) ? $group_service['group_service_info'] : [];
			if ( $group_service_name && sizeof( $ex_service_infos ) > 0 ) {
				?>
				<h5><?php echo esc_html( $group_service_name ); ?></h5>
				<div class="divider"></div>
				<?php
				foreach ( $ex_service_infos as $ex_service_info ) {
					$ex_service_price     = array_key_exists( 'price', $ex_service_info ) ? $ex_service_info['price'] : 0;
					$ex_service_price     = MPWPB_Function::wc_price( $post_id, $ex_service_price );
					$ex_service_price_raw = MPWPB_Function::price_convert_raw( $ex_service_price );
					$ex_unique_id         = '#ex_service_' . uniqid();
					?>
					<div class="dLayout_xs mpwpb_extra_service_item">
						<div class="flexWrap">
							<div class="w_100 _pR">
								<div class="bg_image_area">
									<div data-bg-image="<?php echo esc_attr( MPWPB_Function::get_image_url( '', $ex_service_info['img'], 'medium' ) ); ?>"></div>
									<h2 class="fullAbsolute allCenter textTheme"><span data-icon></span></h2>
								</div>
							</div>
							<div class="flexAuto">
								<div class="justifyBetween">
									<h6><?php echo esc_html( $ex_service_info['name'] ); ?></h6>
									<h6 class="textTheme"><?php echo MPWPB_Function::esc_html( $ex_service_price ); ?></h6>
								</div>
								<div class="divider"></div>
								<div class="justifyBetween">
									<div>
										<?php if ( $ex_service_info['details'] ) { ?>
											<p><?php echo esc_html( $ex_service_info['details'] ); ?></p>
										<?php } ?>
									</div>
									<div class="dFlex">
										<div class="mR_xs" data-collapse="<?php echo esc_attr( $ex_unique_id ); ?>">
											<?php MPWPB_Layout::qty_input( 'mpwpb_extra_service_qty[]', $ex_service_price_raw, $ex_service_info['qty'], 1, 0, $ex_service_info['qty'] ); ?>
										</div>
										<button type="button" class="_mpBtn_min_150 mpwpb_price_calculation" data-extra-item data-collapse-target="<?php echo esc_attr( $ex_unique_id ); ?>" data-open-icon="far fa-check-circle" data-close-icon="" data-open-text="<?php esc_attr_e( 'Select', 'mpwpb_plugin' ); ?>" data-close-text="<?php esc_attr_e( 'Selected', 'mpwpb_plugin' ); ?>" data-add-class="mpActive">
											<input type="hidden" name="mpwpb_extra_service[]" data-value="<?php echo esc_attr( $group_service_name ); ?>" value=""/>
											<input type="hidden" name="mpwpb_extra_service_type[]" data-value="<?php echo esc_attr( $ex_service_info['name'] ); ?>" value=""/>
											<span data-text><?php esc_html_e( 'Select', 'mptbm_plugin' ); ?></span>
											<span data-icon class="mL_xs"></span>
										</button>
									</div>
								</div>
							</div>
						</div>
					</div>
					<?php
				}
			}
		}
	}