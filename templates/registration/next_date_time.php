<?php
	/*
   * @Author 		engr.sumonazma@gmail.com
   * Copyright: 	mage-people.com
   */
	if (!defined('ABSPATH')) {
		exit;
	}
	$post_id = $post_id ?? get_the_id();
	// Falls back to the real service post id when there's no linked WooCommerce
	// product meta -- that meta is only ever written by Admin/MPWPB_Hidden_Product.php,
	// which itself only loads when WooCommerce payment mode is active
	// (Admin/MPWPB_Admin.php::load_file()). Without this fallback, Custom
	// Payment mode (WooCommerce off) leaves this empty, "Proceed to Checkout"
	// posts an empty link_id, MPWPB_Woocommerce::build_booking_item_from_request()
	// can't resolve a valid post, MPWPB_Native_Checkout::add_to_cart() returns
	// '', and the JS success handler does `window.location.href = ''`, which
	// just reloads the current page instead of proceeding to checkout.
	$link_wc_product = MPWPB_Global_Function::get_post_info($post_id, 'link_wc_product', $post_id);
	$service_text = $service_text ?? MPWPB_Function::get_service_text($post_id);
?>
    <div class="next_date_area">
        <div class="justifyBetween ">

            <button class="_mpBtn_dBR mpActive" type="button" id="mpwpb_display_date_time" style="display: none">
                <i class="fas fa-long-arrow-alt-left _mR_xs"></i>
				<?php echo esc_html__('Back', 'service-booking-manager'); ?>
            </button>

            <button class="_mpBtn_dBR mpActive mpwpb_date_time_prev" type="button" id="mpwpb_display_service_btn">
                <i class="fas fa-long-arrow-alt-left _mR_xs"></i>
				<?php esc_html_e('Back', 'service-booking-manager'); ?>
            </button>
            <h4 class="alignCenter mpwpb-total">
				<?php esc_html_e('Total :', 'service-booking-manager'); ?>&nbsp;&nbsp;
                <span class="mpwpb_total_bill textTheme" id="mpwpd_all_total_bill"><?php echo wp_kses_post(MPWPB_Global_Function::wc_price($post_id, 0)); ?></span>
            </h4>
<!--            <div class="mpqpb_next_prev_btn_display" id="mpwpb_show_hide_staff_member" style="display: none"><span class="mpwpb_next_prev_btn">Next Staff Member</span></div>-->

            <?php
            if( is_plugin_active('service-booking-manager-pro/MPWPB_Plugin_Pro.php') && $enable_staff_member === 'on' ){
            ?>
            <button class="_mpBtn_dBR mActive" id="mpwpb_show_hide_staff_member" type="button" data-wc_link_id="<?php echo esc_attr($link_wc_product); ?>" style="display: none">
                <?php esc_html_e('Next Staff Member', 'service-booking-manager'); ?>
                <i class="fas fa-long-arrow-alt-right _mL_xs"></i>
            </button>
            <?php }?>

            <button class="_mpBtn_dBR mActive mpwpb_date_time_next" id="mpwpb_date_time_next_btn_id" type="button" data-wc_link_id="<?php echo esc_attr($link_wc_product); ?>" data-alert="<?php esc_html_e('Please Select Date & Time', 'service-booking-manager'); ?>" style="display: none">
				<?php esc_html_e('Proceed to Checkout', 'service-booking-manager'); ?>
                <i class="fas fa-long-arrow-alt-right _mL_xs"></i>
            </button>
        </div>
		<?php if (class_exists('MPWPB_Partial_Payment') && MPWPB_Partial_Payment::is_enabled()) : ?>
        <div class="mpwpb-payment-choice-row" id="mpwpb_payment_choice_wrap">
            <label class="mpwpb-payment-choice-option">
                <input type="radio" name="mpwpb_payment_choice" value="full" checked/>
                <?php esc_html_e('Pay in Full', 'service-booking-manager'); ?>
            </label>
            <label class="mpwpb-payment-choice-option">
                <input type="radio" name="mpwpb_payment_choice" value="partial"/>
                <?php esc_html_e('Pay Deposit Now', 'service-booking-manager'); ?>
            </label>
            <span class="mpwpb-payment-choice-due" id="mpwpb_payment_choice_due" style="display:none;">
				<?php esc_html_e('Due Now:', 'service-booking-manager'); ?> <strong id="mpwpb_payment_choice_due_amount"></strong>
            </span>
        </div>
		<?php endif; ?>
    </div>
<?php