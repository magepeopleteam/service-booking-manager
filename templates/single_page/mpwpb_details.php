<?php
	/*
   * @Author 		engr.sumonazma@gmail.com
   * Copyright: 	mage-people.com
   */
	if (!defined('ABSPATH')) {
		die;
	} // Cannot access pages directly.
if ( wp_is_block_theme() ) {  ?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<?php
	$block_content = do_blocks( '
		<!-- wp:group {"layout":{"type":"constrained"}} -->
		<div class="wp-block-group">
		<!-- wp:post-content /-->
		</div>
		<!-- /wp:group -->'
 	);
    wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<div class="wp-site-blocks">
<header class="wp-block-template-part site-header">
    <?php block_header_area(); ?>
</header>
</div>
<?php
} else {
    get_header();	
    the_post();
}
	do_action('mpwpb_single_page_before_wrapper');
	if (post_password_required()) {
		echo wp_kses_post(get_the_password_form()); // WPCS: XSS ok.
	} else {
		do_action('woocommerce_before_single_product');
		$post_id = get_the_id();
		$all_dates = MPWPB_Function::get_date($post_id);
		//echo '<pre>';print_r($all_dates);echo '</pre>';
		$product_id = MPWPB_Global_Function::get_post_info($post_id, 'link_wc_product');
		$all_category = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_category_service', array());
		$all_sub_category = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_sub_category_service', array());
		$all_services = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_service', array());
		$category_text = $category_text ?? MPWPB_Function::get_category_text($post_id);
		$sub_category_text = $sub_category_text ?? MPWPB_Function::get_sub_category_text($post_id);;
		$service_text = $service_text ?? MPWPB_Function::get_service_text($post_id);
		$extra_services = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_extra_service', array());
		$short_date_format = MPWPB_Global_Function::get_settings('mpwpb_global_settings', 'date_format_short', 'M , Y');
		$template_name = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_template', 'default.php');

		include_once(MPWPB_Function::details_template_path($post_id));
	}
	do_action('mpwpb_single_page_after_wrapper');
if ( wp_is_block_theme() ) {
// Code for block themes goes here.
?>
<footer class="wp-block-template-part">
    <?php block_footer_area(); ?>
</footer>
<?php wp_footer(); ?>
</body>    
<?php
} else {
    get_footer();
}
