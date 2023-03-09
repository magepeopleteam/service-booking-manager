<?php
	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}
	$post_id       = $post_id ?? get_the_id();
	$all_category  = $all_category ?? MPWPB_Function::get_category( $post_id );
	$category_text = $category_text ?? MPWPB_Function::get_category_text( $post_id );
	if ( sizeof( $all_category ) > 0 ) {
		?>
		<div class="mpwpb_category_area">
			<h3 class="mB_xs"><?php echo esc_html__( 'Select', 'mpwpb_plugin' ) . ' ' . $category_text; ?></h3>
			<div class="divider"></div>
			<div class="flexWrapJustifyBetween">
				<?php
					foreach ( $all_category as $category_item ) {
						$category_name  = array_key_exists( 'name', $category_item ) ? $category_item['name'] : '';
						$category_icon  = array_key_exists( 'icon', $category_item ) ? $category_item['icon'] : '';
						$category_image = array_key_exists( 'image', $category_item ) ? $category_item['image'] : '';
						?>
						<div class="mpwpb_item_box mpwpb_category_item dShadow_8" data-category="<?php echo esc_attr( $category_name ); ?>">
							<h4 class="mB_xs"><?php echo esc_html( $category_name ); ?></h4>
							<?php if ( $category_image ) { ?>
								<div class="bg_image_area">
									<div data-bg-image="<?php echo esc_attr( MPWPB_Function::get_image_url( '', $category_image, 'medium' ) ); ?>"></div>
								</div>
							<?php } ?>
							<?php if ( $category_icon ) { ?>
								<div class="allCenter mpwpb_icon_area">
									<span class="<?php echo esc_attr( $category_icon ); ?>"></span>
								</div>
							<?php } ?>
							<span class="fas fa-check mpwpb_item_check _circleIcon_xs"></span>
						</div>
					<?php } ?>
			</div>
		</div>
		<?php
	}