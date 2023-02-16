<?php
	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}
	$post_id      = $post_id ?? get_the_id();
	$all_category = $all_category ?? MPWPB_Function::get_category($post_id);
	if ( sizeof( $all_category ) > 0 ) {
		?>
		<div class="mpwpb_category_area">
			<h5><?php esc_html_e( 'Select category', 'mpwpb_plugin' ); ?></h5>
			<div class="divider"></div>
			<div class="groupRadioCheck flexWrap">
				<?php foreach ( $all_category as $category_name ) { ?>
					<button type="button" class="_mpBtn_min_150_mR mpwpb_category_item" data-category="<?php echo esc_attr( $category_name ); ?>" data-radio-check="<?php echo esc_attr( $category_name ); ?>" data-open-icon="far fa-check-circle" data-close-icon="">
						<?php echo esc_html( $category_name ); ?><span data-icon class="mL_xs"></span>
					</button>
				<?php } ?>
			</div>
		</div>
		<?php
	}