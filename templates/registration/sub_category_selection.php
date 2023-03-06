<?php
	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}
	$post_id           = $post_id ?? get_the_id();
	$all_sub_category  = $all_sub_category ?? MPWPB_Function::get_sub_category( $post_id );
	$category_text     = $category_text ?? MPWPB_Function::get_category_text( $post_id );
	$sub_category_text = $sub_category_text ?? MPWPB_Function::get_sub_category_text( $post_id );
	$service_text      = $service_text ?? MPWPB_Function::get_service_text( $post_id );
	if ( sizeof( $all_sub_category ) > 0 ) {
		?>
		<div class="mpwpb_sub_category_area">
			<h3 class="mB_xs"><?php echo esc_html__( 'Select', 'mpwpb_plugin' ) . ' ' . $sub_category_text; ?></h3>
			<div class="divider"></div>
			<div class="flexWrapJustifyBetween">
				<?php
					foreach ( $all_sub_category as $sub_category_item ) {
						$category_name      = array_key_exists( 'category', $sub_category_item ) ? $sub_category_item['category'] : '';
						$sub_category_name  = array_key_exists( 'sub_category', $sub_category_item ) ? $sub_category_item['sub_category'] : '';
						$sub_category_icon  = array_key_exists( 'icon', $sub_category_item ) ? $sub_category_item['icon'] : '';
						$sub_category_image = array_key_exists( 'image', $sub_category_item ) ? $sub_category_item['image'] : '';
						?>
						<div class="mpwpb_item_box mpwpb_sub_category_item dShadow_8" data-category="<?php echo esc_attr( $category_name ); ?>" data-sub-category="<?php echo esc_attr( $sub_category_name ); ?>">

							<?php if ( $sub_category_image ) { ?>
								<div class="bg_image_area">
									<div data-bg-image="<?php echo esc_attr( MPWPB_Function::get_image_url( '', $sub_category_image, 'medium' ) ); ?>"></div>
								</div>
							<?php } ?>
							<?php if ( $sub_category_icon ) { ?>
								<div class="allCenter mpwpb_icon_area">
									<span class="<?php echo esc_attr( $sub_category_icon ); ?>"></span>
								</div>
							<?php } ?>
							<h4 class="_mT_textCenter"><?php echo esc_html( $sub_category_name ); ?></h4>
							<span class="fas fa-check mpwpb_item_check _circleIcon_xs"></span>
						</div>
					<?php } ?>
			</div>
			<div class="divider"></div>
			<div class="justifyBetween">
				<button class="_mpBtn_mT_xs_radius mpActive mpwpb_sub_category_prev" type="button">
					<i class="fas fa-long-arrow-alt-left _mR_xs"></i>
					<?php echo esc_html__( 'Previous', 'mpwpb_plugin' ) . ' ' . $category_text; ?>
				</button>
				<button class="_mpBtn_mT_xs_radius mActive mpwpb_sub_category_next" type="button" data-alert="<?php echo esc_html__( 'Please Select', 'mpwpb_plugin' ) . ' ' . $sub_category_text; ?>">
					<?php echo esc_html__( 'Next', 'mpwpb_plugin' ) . ' ' . $service_text; ?>
					<i class="fas fa-long-arrow-alt-right _mL_xs"></i>
				</button>
			</div>
		</div>
		<?php
	}