<?php
	$post_id        = $post_id ?? get_the_id();
	$product_id     = $product_id ?? MPWPB_Function::get_post_info( $post_id, 'link_wc_product' );
	$payment_system = MPWPB_Function::get_general_settings( 'payment_system', array( 'direct_order', 'woocommerce' ) );
	$current_payment_system=current( $payment_system );
	if ( sizeof( $payment_system ) > 1 ) {
		$current_payment_system='';
		?>
		<div class="mpwpb_payment_system_area mT">
			<h3><?php esc_html_e( 'Select Payment System', 'servicebookingmanager' ); ?></h3>
			<div class="divider"></div>
			<div class="groupRadioCheck pT_xs">
				<input type="hidden" name="mpwpb_payment_system">
				<div class="allCenter">
					<?php if ( in_array( 'direct_order', $payment_system ) ) { ?>
						<div class="dLayout_xs min_200 mR" data-radio-check="direct_order" data-open-icon="fas fa-dot-circle" data-close-icon="far fa-circle">
							<h5 class="allCenter"><span data-icon class="far fa-circle mR_xs"></span><?php esc_html_e( 'Pay on service', 'servicebookingmanager' ); ?></h5>
						</div>
					<?php } ?>
					<?php if ( in_array( 'woocommerce', $payment_system ) ) { ?>
						<div class="dLayout_xs min_200" data-radio-check="woocommerce" data-open-icon="fas fa-dot-circle" data-close-icon="far fa-circle">
							<h5 class="allCenter"><span data-icon class="far fa-circle mR_xs"></span><?php esc_html_e( 'To pay', 'servicebookingmanager' ); ?></h5>
						</div>
					<?php } ?>
				</div>
			</div>
		</div>
		<?php
	} else {
		?>
		<input type="hidden" name="mpwpb_payment_system" value="<?php echo esc_attr( $current_payment_system ); ?>"/>
		<?php
	}
?>
	<div class="mpwpb_direct_order_info mT <?php echo esc_attr($current_payment_system=='direct_order'?'':'dNone'); ?>">
		<h3><?php esc_html_e( 'Billing Information', 'servicebookingmanager' ); ?></h3>
		<div class="divider"></div>
		<label>
			<span class="min_150"><?php esc_html_e( 'Billing Name : ', 'servicebookingmanager' ); ?></span>
			<input type="text" class="formControl mp_name_validation" name="mpwpb_bill_name" placeholder="<?php esc_attr_e( 'Billing Name Here....', 'servicebookingmanager' ); ?>"/>
		</label>
		<label class="mT_xs">
			<span class="min_150"><?php esc_html_e( 'Billing E-Mail : ', 'servicebookingmanager' ); ?></span>
			<input type="email" class="formControl" name="mpwpb_bill_email" placeholder="<?php esc_attr_e( 'Billing E-mail Here....', 'servicebookingmanager' ); ?>"/>
		</label>
	</div>
	<input type="hidden" name="mpwpb_product_id" value="<?php echo esc_attr( $product_id ); ?>"/>
	<input type="hidden" name="post_id" value="<?php echo esc_attr( $post_id ); ?>"/>
	<div class="mpwpb_book_now_area">
		<div class="divider"></div>
		<div class="justifyCenter mT_xs">
			<button class="_mpBtn_mT_xs_bBR mActive mpwpb_book_now" data-submit-path="<?php echo esc_attr( get_home_url() . '/mpwpb-order-details/' ); ?>" type="button" data-alert="<?php esc_attr_e( 'Please select Payment Method', 'servicebookingmanager' ); ?>">
				<span class="fas fa-cart-plus mR_xs"></span>
				<?php esc_html_e( 'Proceed Order', 'servicebookingmanager' ); ?>
			</button>
			<button type="submit" name="add-to-cart" class="dNone mpwpb_add_to_cart" value="<?php echo esc_attr( $product_id ); ?>">
				<?php esc_html_e( 'Proceed Order', 'servicebookingmanager' ); ?>
			</button>
		</div>
	</div>
<?php