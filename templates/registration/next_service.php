<?php
	/*
   * @Author 		engr.sumonazma@gmail.com
   * Copyright: 	mage-people.com
   */
	if (!defined('ABSPATH')) {
		exit;
	}
	$post_id = $post_id ?? get_the_id();
	$service_text = $service_text ?? MPWPB_Function::get_service_text($post_id);
?>
	<div class="next_service_area">
		<div class="justifyBetween">
			<h3 class="alignCenter"><?php esc_html_e('Total :', 'service-booking-manager'); ?>&nbsp;&nbsp;<span class="mpwpb_total_bill textTheme"><?php echo MP_Global_Function::wc_price($post_id, 0); ?></span></h3>
			<button class="_mpBtn_dBR mActive mpwpb_service_next" type="button" data-alert="<?php echo esc_html__('Please Select', 'service-booking-manager') . ' ' . $service_text; ?>">
				<?php esc_html_e('Next Date & Time', 'service-booking-manager'); ?>
				<i class="fas fa-long-arrow-alt-right _mL_xs"></i>
			</button>
		</div>
	</div>
<?php