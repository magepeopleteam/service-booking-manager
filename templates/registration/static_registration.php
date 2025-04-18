<?php
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
?>
    <div class="mpwpb_static_theme">
        <div class="mpwpb_static_area">
            <div class="mpwpb_static ">
				<?php include(MPWPB_Function::template_path('layout/title_details_page.php')); ?>
                <div class="mpwpb_static_cateogry">
					<?php include(MPWPB_Function::template_path('registration/category_selection_static.php')); ?>
                </div>
            </div>
        </div>
        <div class="mpwpb_popup mpwpb_style" data-popup="#mpwpb_static_popup">
            <div class="mpwpb_popup_main_area">
                <div class="mpwpb_popup_header">
                    <h5><?php echo esc_html($title); ?></h5>
                    <p><?php echo esc_html($sub_title); ?></p>
                    <span class="fas fa-times mpwpb_popup_close"></span>
                </div>
                <div class="mpwpb_popup_body">
                    <div class="mpwpb-popup-content">
                        <div class="service-items">
                            <div class="all_service_area ">
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
                                        <div class="mpwpb_summary_item" data-service="<?php echo esc_attr($service_key + 1); ?>" data-service-category="<?php echo esc_attr($category_name); ?>" data-service-sub-category="<?php echo esc_attr($sub_category_name); ?>">
                                            <span class="fas fa-check mpwpb_item_check _circleIcon_xs"></span>
                                            <div class="flexWrap justifyBetween">
                                                <h6 class="mR_xs"><?php echo esc_html($service_name); ?></h6>
                                                <p><span class="textTheme">x1</span>&nbsp;|&nbsp; <span class="textTheme service_price"><?php echo wp_kses_post($service_wc_price); ?></span></p>
                                            </div>
                                        </div>
									<?php } ?>
								<?php } ?>
								<?php
									//echo '<pre>';									print_r($extra_services);									echo '</pre>';
									if (sizeof($extra_services) > 0) {
										foreach ($extra_services as $ex_service_info) {
											$ex_service_price = array_key_exists('price', $ex_service_info) ? $ex_service_info['price'] : 0;
											$ex_service_price = MPWPB_Global_Function::wc_price($post_id, $ex_service_price);
											$ex_service_price_raw = MPWPB_Global_Function::price_convert_raw($ex_service_price);
											$ex_service_name = array_key_exists('name', $ex_service_info) ? $ex_service_info['name'] : '';
											if ($ex_service_name && $ex_service_price) {
												?>
                                                <div class="mpwpb_summary_item" data-extra-service="<?php echo esc_attr($ex_service_info['name']); ?>">
                                                    <span class="fas fa-check mpwpb_item_check _circleIcon_xs"></span>
                                                    <div class="flexWrap justifyBetween">
                                                        <h6 class="mR_xs">                                                                <?php echo esc_html($ex_service_info['name']); ?>                                                            </h6>
                                                        <p>
                                                            <span class="textTheme ex_service_qty">x1</span>&nbsp;|&nbsp;
                                                            <span class="textTheme"><?php echo wp_kses_post($ex_service_price); ?></span>
                                                        </p>
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
                </div>
                <div class="popupFooter _justifyBetween">
					<?php include(MPWPB_Function::template_path('registration/next_service.php')); ?>
					<?php include(MPWPB_Function::template_path('registration/next_date_time.php')); ?>
                </div>
            </div>
        </div>
    </div>
<?php