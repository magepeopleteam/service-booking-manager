<?php
	/*
   * @Author 		engr.sumonazma@gmail.com
   * Copyright: 	mage-people.com
   */
	if (!defined('ABSPATH')) {
		exit;
	}
	$post_id = $post_id ?? get_the_id();
	$link_wc_product = MPWPB_Global_Function::get_post_info($post_id, 'link_wc_product');
	$service_text = $service_text ?? MPWPB_Function::get_service_text($post_id);
?>
    <div class="next_date_area">
        <div class="justifyBetween ">
            <button class="_mpBtn_dBR mpActive mpwpb_date_time_prev" type="button">
                <i class="fas fa-long-arrow-alt-left _mR_xs"></i>
				<?php echo esc_html__('Previous', 'service-booking-manager') . ' ' . esc_html($service_text); ?>
            </button>
            <h4 class="alignCenter mpwpb-total">
				<?php esc_html_e('Total :', 'service-booking-manager'); ?>&nbsp;&nbsp;
                <span class="mpwpb_total_bill textTheme" id="mpwpd_all_total_bill"><?php echo wp_kses_post(MPWPB_Global_Function::wc_price($post_id, 0)); ?></span>
            </h4>
<!--            <div class="mpqpb_next_prev_btn_display" id="mpwpb_show_hide_staff_member" style="display: none"><span class="mpwpb_next_prev_btn">Next Staff Member</span></div>-->

            <?php
            if( $enable_staff_member === 'on' ){
            ?>
            <button class="_mpBtn_dBR mActive" id="mpwpb_show_hide_staff_member" type="button" data-wc_link_id="<?php echo esc_attr($link_wc_product); ?>" style="display: none">
                <?php esc_html_e('Next Staff Member', 'service-booking-manager'); ?>
                <i class="fas fa-long-arrow-alt-right _mL_xs"></i>
            </button>
            <?php }?>

            <button class="_mpBtn_dBR mActive mpwpb_date_time_next" id="mpwpb_date_time_next_btn_id" type="button" data-wc_link_id="<?php echo esc_attr($link_wc_product); ?>" data-alert="<?php esc_html_e('Please Select Date & Time', 'service-booking-manager'); ?>" style="display: none">
				<?php esc_html_e('Next Summary', 'service-booking-manager'); ?>
                <i class="fas fa-long-arrow-alt-right _mL_xs"></i>
            </button>
        </div>
    </div>
<?php