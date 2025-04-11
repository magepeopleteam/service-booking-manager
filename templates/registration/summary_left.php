<?php
	/*
   * @Author 		engr.sumonazma@gmail.com
   * Copyright: 	mage-people.com
   */
	if (!defined('ABSPATH')) {
		exit;
	}
	$post_id = $post_id ?? get_the_id();
	$all_category = $all_category??MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_category_service', array());
	$all_sub_category = $all_sub_category??MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_sub_category_service', array());
	$all_services = $all_services??MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_service', array());
	$extra_services = $extra_services ?? MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_extra_service', array());
?>
    <div class="mpwpb_summary_area_left dShadow ">
        <div class="fdColumn">
            <h5 class="mpwpb_summary_area_left_title"><?php esc_html_e('Cart Summary', 'service-booking-manager'); ?></h5>
            <div class="mpwpb_summary_area_left_content mp_sticky_on_scroll">
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
					<?php foreach ($all_services as $key=>$service_item) {
						$category_name = array_key_exists('parent_cat', $service_item) && ($service_item['parent_cat'] || $service_item['parent_cat']==0)? (int)$service_item['parent_cat']+1 : '';
						$sub_category_name = array_key_exists('sub_cat', $service_item)&& ($service_item['sub_cat']|| $service_item['sub_cat']==0)  ?(int) $service_item['sub_cat']+1 : '';
						$service_name = array_key_exists('name', $service_item) ? $service_item['name'] : '';
						$service_price = array_key_exists('price', $service_item) ? $service_item['price'] : 0;
						$service_wc_price = MPWPB_Global_Function::wc_price($post_id, $service_price);
						$service_price = MPWPB_Global_Function::price_convert_raw($service_wc_price);
                        ?>
                        <div class="mpwpb_summary_item" data-service="<?php echo esc_attr($key+1); ?>" data-service-category="<?php echo esc_attr($category_name); ?>" data-service-sub-category="<?php echo esc_attr($sub_category_name); ?>">
                            <span class="fas fa-check mpwpb_item_check _circleIcon_xs"></span>
                            <div class="flexWrap justifyBetween">
                                <h6 class="mR_xs"><?php echo esc_html($service_name); ?></h6>
                                <p><span class="textTheme">x1</span>&nbsp;|&nbsp; <span class="textTheme service_price"><?php echo wp_kses_post($service_wc_price); ?></span></p>
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
                                                <span class="textTheme"><?php echo wp_kses_post(MPWPB_Global_Function::wc_price($post_id, $ex_service_price)); ?></span>
                                            </p>
                                        </div>
                                    </div>
									<?php
								}
							}
						}
					}
				?>
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
<?php