<?php
	/*
   * @Author 		engr.sumonazma@gmail.com
   * Copyright: 	mage-people.com
   */
	if (!defined('ABSPATH')) {
		die;
	} // Cannot access pages directly.
	get_header();
	the_post();
	do_action('mpwpb_single_page_before_wrapper');
	if (post_password_required()) {
		echo get_the_password_form(); // WPCS: XSS ok.
	} else {
		do_action('woocommerce_before_single_product');
		$post_id = get_the_id();
		$all_dates = MPWPB_Function::get_date($post_id);
		//echo '<pre>';print_r($all_dates);echo '</pre>';
		$product_id = MP_Global_Function::get_post_info($post_id, 'link_wc_product');
		$all_category = MP_Global_Function::get_post_info($post_id, 'mpwpb_category_service', array());
		$all_sub_category = MP_Global_Function::get_post_info($post_id, 'mpwpb_sub_category_service', array());
		$all_services = MP_Global_Function::get_post_info($post_id, 'mpwpb_service', array());
		$category_text = $category_text ?? MPWPB_Function::get_category_text($post_id);
		$sub_category_text = $sub_category_text ?? MPWPB_Function::get_sub_category_text($post_id);;
		$service_text = $service_text ?? MPWPB_Function::get_service_text($post_id);
		$extra_services = MP_Global_Function::get_post_info($post_id, 'mpwpb_extra_service', array());
		$short_date_format = MP_Global_Function::get_settings('mp_global_settings', 'date_format_short', 'M , Y');
		$template_name = MP_Global_Function::get_post_info($post_id, 'mpwpb_template', 'default.php');

		include_once(MPWPB_Function::details_template_path($post_id));
	}
	do_action('mpwpb_single_page_after_wrapper');
	get_footer();