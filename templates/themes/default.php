<?php

/**
   * Tempalte Name: Static Template
   * 
   * @author Shahadat Hossain <raselsha@gmail.com>
   * @copyright 2024 mage-people.com
   */
	if (!defined('ABSPATH')) {
		exit;
	}
	$post_id = $post_id ?? get_the_id();
	$service_text = $service_text ?? MPWPB_Function::get_service_text($post_id);
	$all_services = $all_services ?? MP_Global_Function::get_post_info($post_id, 'mpwpb_category_infos', array());
	$all_category = $all_category ?? MPWPB_Function::get_category($post_id);
	$all_sub_category = $all_sub_category ?? MPWPB_Function::get_sub_category($post_id);
	$all_service_list = $all_service_list ?? MPWPB_Function::get_all_service($post_id);
	$extra_services = $extra_services ?? MP_Global_Function::get_post_info($post_id, 'mpwpb_extra_service', array());
    $title     = MP_Global_Function::get_post_info( $post_id, 'mpwpb_shortcode_title' );
	$sub_title = MP_Global_Function::get_post_info( $post_id, 'mpwpb_shortcode_sub_title' );
?>
<div class="mpStyle mpwpb-default-template mpwpb_registration">

<header style="background-image: url('<?php echo get_the_post_thumbnail_url(); ?>');">
        <div class="template-header" >
            <div class="header-content">
                <h2><?php the_title(); ?></h2>
                <!-- dispaly service static page reatings using this hook -->
                <?php do_action('mpwpb_service_show_ratings'); ?>
                <!-- dispaly service static page feature heighlight using this hook -->
                <?php do_action('mpwpb_service_feature_heighlight'); ?>
            </div>
        </div>
    </header>
    <main>
        <div class="main">
                    <!-- dispaly service static page nav using this hook -->
                <?php do_action('mpwpb_service_nav'); ?>
                
                <!-- dispaly service overview section using this hook -->
                <?php do_action('mpwpb_service_overview'); ?>
                
                <!-- dispaly service FAQ section using this hook -->
                <?php do_action('mpwpb_service_faq'); ?>

                <!-- dispaly service Details section using this hook -->
                <?php do_action('mpwpb_service_details'); ?>

                <!-- dispaly service Reviews section using this hook -->
                <?php do_action('mpwpb_service_reviews'); ?>
        </div>
        <div class="sidebar">
            <div class="booking-area">
                <div class="header">
                    <h5><?php //echo esc_html( $title ); ?></h5>
                    <p><?php //echo esc_html( $sub_title ); ?></p>
                </div>
                <div class="content">
                    <div class="service-items">
                        <div class="all_service_area ">
                            <?php include(MPWPB_Function::template_path('registration/category_selection.php')); ?>
                            <?php include(MPWPB_Function::template_path('registration/service_selection.php')); ?>
                            <?php include(MPWPB_Function::template_path('registration/extra_services.php')); ?>
                        </div>
                        <?php include(MPWPB_Function::template_path('registration/date_time_select.php')); ?>
                        <div class="mpwpb_order_proceed_area"></div>
                    </div>
                    <?php
// Determine if prices include tax
$prices_include_tax = wc_prices_include_tax();
?>

<div class="mpwpb_summary_area_left_content">
    <?php if (sizeof($all_category) > 0) { ?>
        <div class="mpwpb_summary_item" data-category>
            <span class="fas fa-check mpwpb_item_check _circleIcon_xs"></span>
            <h6></h6>
        </div>
    <?php } ?>

    <?php if (sizeof($all_sub_category) > 0) { ?>
        <div class="mpwpb_summary_item" data-sub-category>
            <span class="fas fa-check mpwpb_item_check _circleIcon_xs"></span>
            <h6></h6>
        </div>
    <?php } ?>

    <?php if (sizeof($all_service_list) > 0) { ?>
        <?php foreach ($all_service_list as $service_list) { ?>
            <div class="mpwpb_summary_item" data-service="<?php echo esc_attr($service_list['service']); ?>" data-service-category="<?php echo esc_attr($service_list['category']); ?>" data-service-sub-category="<?php echo esc_attr($service_list['sub_category']); ?>">
                <span class="fas fa-check mpwpb_item_check _circleIcon_xs"></span>
                <div class="flexWrap justifyBetween">
                    <h6 class="mR_xs"><?php echo esc_html($service_list['service']); ?></h6>

                    <?php
                    // Calculate tax amount
                    $price = $service_list['price'];
                    $taxes = WC_Tax::get_rates(get_option('woocommerce_tax_class'));
                    $tax_rate = array_sum(array_column($taxes, 'rate'));
                    $tax_amount = $price * ($tax_rate / 100);

                    // Total price considering tax inclusion
                    $total_price_with_tax = $prices_include_tax ? $price : $price + $tax_amount;
                    ?>
                    <p>
                        <span class="textTheme">x1</span>&nbsp;|&nbsp; 
                        

                        <?php if ($prices_include_tax) { // Only show tax if prices don't include tax ?>
                            <span class="textTheme service_price"><?php echo MP_Global_Function::wc_price($post_id, $total_price_with_tax); ?></span>
    <span class="textTheme tax_amount">(Tax: <?php echo MP_Global_Function::wc_price($post_id, $tax_amount); ?>)</span>
<?php } elseif (!$prices_include_tax) { // Optional: You can provide an alternative action or output here ?>
    <!-- Optional alternative output when tax is included -->
    <span class="textTheme tax_inclusive"><?php echo MP_Global_Function::wc_price($post_id, $price); ?></span>
<?php } ?>

                    </p>
                </div>
            </div>
        <?php } ?>
    <?php } ?>

    <?php
    if (sizeof($extra_services) > 0) {
        foreach ($extra_services as $group_service) {
            $group_service_name = array_key_exists('group_service', $group_service) ? $group_service['group_service'] : '';
            $ex_service_infos = array_key_exists('group_service_info', $group_service) ? $group_service['group_service_info'] : [];
            if (sizeof($ex_service_infos) > 0) {
                foreach ($ex_service_infos as $ex_service_info) {
                    $ex_service_price = array_key_exists('price', $ex_service_info) ? $ex_service_info['price'] : 0;

                    // Calculate tax for extra service
                    $tax_amount = $ex_service_price * ($tax_rate / 100);
                    $total_price_with_tax = $prices_include_tax ? $ex_service_price : $ex_service_price + $tax_amount;
                    ?>
                    <div class="mpwpb_summary_item" data-extra-service="<?php echo esc_attr($ex_service_info['name']); ?>">
                        <span class="fas fa-check mpwpb_item_check _circleIcon_xs"></span>
                        <div class="flexWrap justifyBetween">
                            <h6 class="mR_xs">
                                <?php
                                echo esc_html($ex_service_info['name']);
                                if ($group_service_name) {
                                    echo esc_html('(&nbsp;' . $group_service_name . '&nbsp;)');
                                }
                                ?>
                            </h6>
                            <p>
                                <span class="textTheme ex_service_qty">x1</span>&nbsp;|&nbsp;
                                <span class="textTheme"><?php echo MP_Global_Function::wc_price($post_id, $total_price_with_tax); ?></span>

                                <?php if (!$prices_include_tax) { // Only show tax for extra services if prices don't include tax ?>
                                    <span class="textTheme tax_amount">(Tax: <?php echo MP_Global_Function::wc_price($post_id, $tax_amount); ?>)</span>
                                <?php } ?>
                            </p>
                        </div>
                    </div>
                    <?php
                }
            }
        }
    }
    ?>
    
    <div class="mpwpb_summary_item" data-date>
        <span class="fas fa-check mpwpb_item_check _circleIcon_xs"></span>
        <h6></h6>
    </div>
    <div class="mpwpb_summary_item">
        <span class="fas fa-check mpwpb_item_check _circleIcon_xs"></span>
        <div class="flexWrap justifyBetween">
            <h5 class="mR_xs"><?php esc_html_e('Total :', 'service-booking-manager'); ?></h5>
            <h5><span class="mpwpb_total_bill textTheme"><?php echo MP_Global_Function::wc_price($post_id, 0); ?></span></h5>
        </div>
    </div>
</div>



                </div>
                <div class="footer _justifyBetween">
                    <?php include(MPWPB_Function::template_path('registration/next_service.php')); ?>
                    <?php include(MPWPB_Function::template_path('registration/next_date_time.php')); ?>
                </div>
            </div>
        </div>
    </main>

    
</div>
<?php
