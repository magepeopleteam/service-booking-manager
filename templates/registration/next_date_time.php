<?php
	/*
   * @Author 		engr.sumonazma@gmail.com
   * Copyright: 	mage-people.com
   */
	if (!defined('ABSPATH')) {
		exit;
	}
	$post_id = $post_id ?? get_the_id();
	$link_wc_product = MP_Global_Function::get_post_info($post_id, 'link_wc_product');
	$service_text = $service_text ?? MPWPB_Function::get_service_text($post_id);
?>
    <div class="next_date_area">
        <div class="justifyBetween ">
            <button class="_mpBtn_dBR mpActive mpwpb_date_time_prev" type="button">
                <i class="fas fa-long-arrow-alt-left _mR_xs"></i>
				<?php echo esc_html__('Previous', 'service-booking-manager') . ' ' . $service_text; ?>
            </button>
            <h4 class="alignCenter mpwpb-total">
				<?php esc_html_e('Total :', 'service-booking-manager'); ?>&nbsp;&nbsp;
                <span class="mpwpb_total_bill textTheme"><?php echo MP_Global_Function::wc_price($post_id, 0); ?></span>
            </h4>
            <button class="_mpBtn_dBR mActive mpwpb_date_time_next" type="button" data-wc_link_id="<?php echo esc_attr($link_wc_product); ?>" data-alert="<?php esc_html_e('Please Select Date & Time', 'service-booking-manager'); ?>">
				<?php esc_html_e('Next Summary', 'service-booking-manager'); ?>
                <i class="fas fa-long-arrow-alt-right _mL_xs"></i>
            </button>
        </div>
    </div>
<?php