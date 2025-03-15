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
}

// Ensure we have a valid post object
global $post;
if (!isset($post) || !is_object($post)) {
    $post = get_post(get_the_ID());
    setup_postdata($post);
} else {
    setup_postdata($post);
}

do_action('mpwpb_single_page_before_wrapper');
if (post_password_required()) {
    echo wp_kses_post(get_the_password_form()); // WPCS: XSS ok.
} else {
    do_action('woocommerce_before_single_product');
    
    // Get post ID safely
    $post_id = $post->ID;
    if (!$post_id) {
        return;
    }

    // Load all required data with proper validation
    $all_dates = MPWPB_Function::get_date($post_id);
    $product_id = MP_Global_Function::get_post_info($post_id, 'link_wc_product', '');
    $all_category = MP_Global_Function::get_post_info($post_id, 'mpwpb_category_service', array());
    $all_sub_category = MP_Global_Function::get_post_info($post_id, 'mpwpb_sub_category_service', array());
    $all_services = MP_Global_Function::get_post_info($post_id, 'mpwpb_service', array());
    
    // Get text settings with proper validation
    $category_text = isset($category_text) ? $category_text : MPWPB_Function::get_category_text($post_id);
    $sub_category_text = isset($sub_category_text) ? $sub_category_text : MPWPB_Function::get_sub_category_text($post_id);
    $service_text = isset($service_text) ? $service_text : MPWPB_Function::get_service_text($post_id);
    
    // Load additional settings
    $extra_services = MP_Global_Function::get_post_info($post_id, 'mpwpb_extra_service', array());
    $short_date_format = MP_Global_Function::get_settings('mp_global_settings', 'date_format_short', 'M , Y');
    $template_name = MP_Global_Function::get_post_info($post_id, 'mpwpb_template', 'default.php');

    // Include the template with proper validation
    $template_path = MPWPB_Function::details_template_path($post_id);
    if (file_exists($template_path)) {
        include_once($template_path);
    }
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
