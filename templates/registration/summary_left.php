<?php
	/*
   * @Author 		engr.sumonazma@gmail.com
   * Copyright: 	mage-people.com
   */
	if (!defined('ABSPATH')) {
		exit;
	}
	$post_id = $post_id ?? get_the_id();
	$all_services = $all_services ?? MP_Global_Function::get_post_info($post_id, 'mpwpb_category_infos', array());
	$all_category = $all_category ?? MPWPB_Function::get_category($post_id);
	$all_sub_category = $all_sub_category ?? MPWPB_Function::get_sub_category($post_id);
	$all_service_list = $all_service_list ?? MPWPB_Function::get_all_service($post_id);
	$extra_services = $extra_services ?? MP_Global_Function::get_post_info($post_id, 'mpwpb_extra_service', array());
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

				<?php if (sizeof($all_service_list) > 0) { ?>
					<?php foreach ($all_service_list as $service_list) { ?>
                        <div class="mpwpb_summary_item" data-service="<?php echo esc_attr($service_list['service']); ?>" data-service-category="<?php echo esc_attr($service_list['category']); ?>" data-service-sub-category="<?php echo esc_attr($service_list['sub_category']); ?>">
                            <span class="fas fa-check mpwpb_item_check _circleIcon_xs"></span>
                            <div class="flexWrap justifyBetween">
                                <h6 class="mR_xs"><?php echo esc_html($service_list['service']); ?></h6>
                                <p><span class="textTheme">x1</span>&nbsp;|&nbsp; <span class="textTheme service_price"><?php echo MP_Global_Function::wc_price($post_id, $service_list['price']); ?></span></p>
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
                                                <span class="textTheme"><?php echo MP_Global_Function::wc_price($post_id, $ex_service_price); ?></span>
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
                                                <h5><span class="mpwpb_total_bill textTheme"><?php echo MP_Global_Function::wc_price($post_id, 0); ?></span></h5>
                                            </div>
                                        </div>
            </div>
        </div>
    </div>
<?php