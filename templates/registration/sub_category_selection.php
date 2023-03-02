<?php
	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}
	$post_id          = $post_id ?? get_the_id();
	$all_sub_category = $all_sub_category ?? MPWPB_Function::get_sub_category( $post_id );
	if ( sizeof( $all_sub_category ) > 0 ) {
		?>
		<div class="mpwpb_sub_category_area">
			<h5><?php esc_html_e( 'Select Sub-category', 'mpwpb_plugin' ); ?></h5>
			<div class="divider"></div>
			<div class="flexWrapJustifyBetween">
				<?php
					foreach ( $all_sub_category as $sub_category_item ) {
						$category_name      = array_key_exists( 'category', $sub_category_item ) ? $sub_category_item['category'] : '';
						$sub_category_name  = array_key_exists( 'sub_category', $sub_category_item ) ? $sub_category_item['sub_category'] : '';
						$sub_category_icon  = array_key_exists( 'icon', $sub_category_item ) ? $sub_category_item['icon'] : '';
						$sub_category_image = array_key_exists( 'image', $sub_category_item ) ? $sub_category_item['image'] : '';
						?>
						<div class="mpwpb_item_box mpwpb_sub_category_item dShadow_3" data-category="<?php echo esc_attr( $category_name ); ?>" data-sub-category="<?php echo esc_attr( $sub_category_name ); ?>" data-open-icon="far fa-check-circle" data-close-icon="">
							<h5 class="mB_xs"><?php echo esc_html( $sub_category_name ); ?></h5>
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
							<span class="fas fa-check mpwpb_item_check _circleIcon_xs"></span>
						</div>
					<?php } ?>
			</div>
			<div class="divider"></div>
			<div class="justifyBetween">
				<button class="mpBtn mpActive mpwpb_sub_category_prev" type="button">
					<i class="fas fa-long-arrow-alt-left _mR_xs"></i>
					<?php esc_html_e( 'Previous Category', 'mpwpb_plugin' ); ?>
				</button>
				<button class="mpBtn mpActive mpwpb_sub_category_next" type="button">
					<?php esc_html_e( 'Next Service', 'mpwpb_plugin' ); ?>
					<i class="fas fa-long-arrow-alt-right _mL_xs"></i>
				</button>
			</div>
		</div>
		<?php
	}