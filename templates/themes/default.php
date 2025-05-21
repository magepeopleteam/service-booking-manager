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
	$all_services = $all_services ?? MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_service', array());
	$all_category = $all_category ?? MPWPB_Function::get_category($post_id);
	$all_sub_category = $all_sub_category ?? MPWPB_Function::get_sub_category($post_id);
	$all_service_list = $all_service_list ?? MPWPB_Function::get_all_service($post_id);
	$extra_services = $extra_services ?? MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_extra_service', array());
	$title = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_shortcode_title');
	$sub_title = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_shortcode_sub_title');

    $is_multiselect = get_post_meta( $post_id, 'mpwpb_service_multiple_category_check', true );
?>
    <div class="mpwpb_style mpwpb-default-template mpwpb_registration">
        <header style="background-image: url('<?php echo esc_url(get_the_post_thumbnail_url()); ?>');">
            <div class="template-header">
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
                        <h5><?PHP // echo esc_html( $title ); ?></h5>
                        <p><?php // echo esc_html( $sub_title ); ?></p>
                    </div>
                    <div class="content">
                        <div class="service-items">
                            <div class="all_service_area ">
                                <div class="selection-header">
                                    <h3><?php esc_html_e('Select Service Type', 'service-booking-manager'); ?></h3>
                                    <p><?php esc_html_e('Choose the perfect wash for your vehicle', 'service-booking-manager'); ?></p>
                                </div>

                                <div class="mpwpb_selected_control" id="mpwpb_selected_control" style="display: none">
                                    <input type="hidden" id="mpwpb_multi_category_select" value="<?php echo esc_attr( $is_multiselect )?>">
                                    <div class="mpwpb_show_all_category_holder mpwpb_selected_category" id="mpwpb_show_all_category" >
                                        <?php esc_html_e( 'All Category', 'service-booking-manager' );?> <i class="fa-solid fa-arrow-right"></i>
                                    </div>
                                    <div class="mpwpb_text_icon_holder">
                                        <div class="mpwpb_selected_category_text mpwpb_category_selected_item mpActive mpwpb_selected_category" data-category=""></div>
                                        <div class="mpwpb_arrow_icon_holder" style="display: none"><i class="fa-solid fa-arrow-right"></i></div>
                                    </div>
                                    <div class="mpwpb_selected_sub_category_text mpwpb_selected_category" data-category='' data-sub-category=""></div>
                                </div>


								<?php include(MPWPB_Function::template_path('registration/category_selection.php')); ?>
								<?php include(MPWPB_Function::template_path('registration/service_selection.php')); ?>
								<?php include(MPWPB_Function::template_path('registration/extra_services.php')); ?>
                            </div>
							<?php include(MPWPB_Function::template_path('registration/date_time_select.php')); ?>
                            <div class="mpwpb_order_proceed_area"></div>
                        </div>

                        <div class="service-cart">
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

                                <?php if (sizeof($all_services) > 0) { ?>
                                    <?php foreach ($all_services as $service_key => $service_item) {
                                        $category_name = array_key_exists('parent_cat', $service_item) && ($service_item['parent_cat'] || $service_item['parent_cat'] == 0) ? (int)$service_item['parent_cat'] + 1 : '';
                                        $sub_category_name = array_key_exists('sub_cat', $service_item) && ($service_item['sub_cat'] || $service_item['sub_cat'] == 0) ? (int)$service_item['sub_cat'] + 1 : '';
                                        $service_name = array_key_exists('name', $service_item) ? $service_item['name'] : '';
                                        $service_price = array_key_exists('price', $service_item) ? $service_item['price'] : 0;
                                        $service_wc_price = MPWPB_Global_Function::wc_price($post_id, $service_price);
                                        $service_price = MPWPB_Global_Function::price_convert_raw($service_wc_price);
                                        ?>
                                        <div class="mpwpb_summary_item" id="mpwpb_summary_cart_item<?php echo esc_attr($service_key + 1); ?>" data-service="<?php echo esc_attr($service_key + 1); ?>" data-service-category="<?php echo esc_attr($category_name); ?>" data-service-sub-category="<?php echo esc_attr($sub_category_name); ?>">
                                            <span class="fas fa-check mpwpb_item_check _circleIcon_xs"></span>
                                            <div class="flexWrap justifyBetween">
                                                <h6 class="mR_xs"><?php echo esc_html($service_name); ?></h6>
                                                <div class="mpwpb_price_qty_remove_holder">
                                                    <p><span class="textTheme mpwpd_cart_service_qty">x1</span>&nbsp;|&nbsp; <span class="textTheme service_price"><?php echo wp_kses_post($service_wc_price); ?></span></p>
                                                    <button class="mpwpb_service_button_remove">✕</button>
                                                </div>

                                            </div>

                                        </div>
                                    <?php } ?>
                                <?php } ?>
                                <?php
                                //echo '<pre>';									print_r($extra_services);									echo '</pre>';
                                if (sizeof($extra_services) > 0) {
                                    foreach ($extra_services as $ex_key => $ex_service_info) {
                                        $ex_service_price = array_key_exists('price', $ex_service_info) ? $ex_service_info['price'] : 0;
                                        $ex_service_price = MPWPB_Global_Function::wc_price($post_id, $ex_service_price);
                                        $ex_service_price_raw = MPWPB_Global_Function::price_convert_raw($ex_service_price);
                                        $ex_service_name = array_key_exists('name', $ex_service_info) ? $ex_service_info['name'] : '';
                                        if ($ex_service_name && $ex_service_price) {
                                            ?>
                                            <div class="mpwpb_summary_item" data-extra-service="<?php echo esc_attr($ex_service_info['name']); ?>" data-ex_service="<?php echo esc_attr( $ex_key + 1); ?>">
                                                <span class="fas fa-check mpwpb_item_check _circleIcon_xs"></span>
                                                <div class="flexWrap justifyBetween">
                                                    <h6 class="mR_xs"><?php echo esc_html($ex_service_info['name']); ?></h6>
                                                    <div class="mpwpb_price_qty_remove_holder">
                                                        <p>
                                                            <span class="textTheme ex_service_qty">x1</span>&nbsp;|&nbsp;
                                                            <span class="textTheme"><?php echo wp_kses_post($ex_service_price); ?></span>
                                                        </p>
                                                        <button class="mpwpb_ex_service_button_remove">✕</button>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php
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
                                        <h5><span class="mpwpb_total_bill textTheme"><?php echo wp_kses_post(MPWPB_Global_Function::wc_price($post_id, 0)); ?></span></h5>
                                    </div>
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
