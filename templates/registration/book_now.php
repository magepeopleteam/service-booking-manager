<?php
	if ( ! defined( 'ABSPATH' ) ) {
		die;
	}
	$post_id           = $post_id ?? get_the_id();
	$product_id         = $product_id??MPWPB_Function::get_post_info( $post_id, 'link_wc_product' );
	?>
<div class="justifyBetween">
	<h5>
		<?php esc_html_e( 'Total :', 'mpwpb_plugin' ); ?>&nbsp;&nbsp;
		<i class="mpwpb_total_bill textTheme"><?php echo MPWPB_Function::wc_price( $post_id, 0 ); ?></i>
	</h5>
	<div>
		<button class="warningButton mpwpb_book_now" type="button">
			<span class="fas fa-cart-plus mR_xs"></span>
			<?php esc_html_e( 'Add to Cart', 'mpwpb_plugin' ); ?>
		</button>
		<button type="submit" name="add-to-cart" value="<?php echo esc_html( $product_id ); ?>" class="dNone mpwpb_add_to_cart">
			<?php esc_html_e( 'Add to Cart', 'mpwpb_plugin' ); ?>
		</button>
	</div>
</div>
