<?php
	if ( ! defined( 'ABSPATH' ) ) {
		die;
	} // Cannot access pages directly.
	get_header();
	the_post();
	do_action( 'mpwpb_single_page_before_wrapper' );
	if ( post_password_required() ) {
		echo get_the_password_form(); // WPCS: XSS ok.
	} else {
		do_action( 'woocommerce_before_single_product' );
		$post_id                   = get_the_id();
		$all_dates      = MPWPB_Function::get_all_date( $post_id );
		$product_id         = MPWPB_Function::get_post_info( $post_id, 'link_wc_product' );
		$all_services    = MPWPB_Function::get_post_info( $post_id, 'mpwpb_category_infos', array() );
		$all_category = MPWPB_Function::get_category($post_id);
		$extra_services = MPWPB_Function::get_post_info( $post_id, 'mpwpb_extra_service', array() );
		$short_date_format = MPWPB_Function::get_general_settings( 'date_format_short', 'M , Y' );
		$template_name = MPWPB_Function::get_post_info( $post_id, 'mpwpb_theme_file', 'default.php' );
		include_once( MPWPB_Function::details_template_path() );
	}
	do_action( 'mpwpb_single_page_after_wrapper' );
	get_footer();