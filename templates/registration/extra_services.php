<?php
	/*
   * @Author 		engr.sumonazma@gmail.com
   * Copyright: 	mage-people.com
   */
	if (!defined('ABSPATH')) {
		die;
	}
	$post_id = $post_id ?? get_the_id();
	$extra_services = $extra_services ?? MP_Global_Function::get_post_info($post_id, 'mpwpb_extra_service', array());
	$service_text = $service_text ?? MPWPB_Function::get_service_text($post_id);
	$extra_service_active = $extra_service_active ?? MP_Global_Function::get_post_info($post_id, 'mpwpb_extra_service_active', 'off');
	if (sizeof($extra_services) > 0 && $extra_service_active == 'on') {
		?>
        <div class="_dShadow_7_mB_xs mpwpb_extra_service_area">
            <h5><?php esc_html_e('Choose Extra Features (Optional)', 'service-booking-manager'); ?></h5>
			<?php
				foreach ($extra_services as $group_service) {
					$group_service_name = array_key_exists('group_service', $group_service) ? $group_service['group_service'] : '';
					$ex_service_infos = array_key_exists('group_service_info', $group_service) ? $group_service['group_service_info'] : [];
					if ($group_service_name && sizeof($ex_service_infos) > 0) {
						?>
                        <h6><?php echo esc_html($group_service_name); ?></h6>
						<?php
					}
					if (sizeof($ex_service_infos) > 0) {
						foreach ($ex_service_infos as $ex_service_info) {
							$ex_service_price = array_key_exists('price', $ex_service_info) ? $ex_service_info['price'] : 0;
							$ex_service_price = MP_Global_Function::wc_price($post_id, $ex_service_price);
							$ex_service_price_raw = MP_Global_Function::price_convert_raw($ex_service_price);
							$ex_service_image = array_key_exists('image', $ex_service_info) ? $ex_service_info['image'] : '';
							$ex_service_icon = array_key_exists('icon', $ex_service_info) ? $ex_service_info['icon'] : '';
							$ex_unique_id = '#ex_service_' . uniqid();
							$unique_id = '#service_' . uniqid();
							?>
                            <div class="mpwpb_item_box mpwpb_extra_service_item">
                                <div class="dFlex">
									<?php if ($ex_service_image) { ?>
                                        <div class="service_img_area alignCenter">
                                            <div class="bg_image_area">
                                                <div data-bg-image="<?php echo esc_attr(MP_Global_Function::get_image_url('', $ex_service_image, 'medium')); ?>"></div>
                                            </div>
                                        </div>
									<?php } ?>
                                    <div class="_dFlex_justifyBetween_fullWidth">
                                        <div class="alignCenter">
											<?php if ($ex_service_icon) { ?>
                                                <span class="<?php echo esc_attr($ex_service_icon); ?> _mR_xs"></span>
											<?php } ?>
                                            <h6>
												<?php echo esc_html($ex_service_info['name']); ?>
                                                <sub class="textTheme">&nbsp;/ &nbsp;&nbsp;<?php echo MP_Global_Function::esc_html($ex_service_price); ?></sub>
                                            </h6>
                                        </div>
										<?php if ($ex_service_info['details']) { ?>
                                            <div class="_mL_xs" data-collapse-target="<?php echo esc_attr($unique_id); ?>" data-read data-open-text="<?php esc_attr_e('Load More', 'service-booking-manager'); ?>" data-close-text="<?php esc_attr_e('Less More', 'service-booking-manager'); ?>">
                                                <span data-text><?php esc_html_e('Load More', 'service-booking-manager'); ?></span>
                                            </div>
										<?php } ?>
                                        <div class="alignCenter">
                                            <div class="mR_xs min_100" data-collapse="<?php echo esc_attr($ex_unique_id); ?>">
												<?php MP_Custom_Layout::qty_input('mpwpb_extra_service_qty[]', $ex_service_price_raw, $ex_service_info['qty'], 1, 0, $ex_service_info['qty']); ?>
                                            </div>
                                            <button type="button" class="_mpBtn_btLight_2_min_150 mpwpb_price_calculation" data-extra-item data-collapse-target="<?php echo esc_attr($ex_unique_id); ?>" data-open-icon="fas fa-check-circle" data-close-icon="" data-open-text="<?php esc_attr_e('Select', 'service-booking-manager'); ?>" data-close-text="<?php esc_attr_e('Selected', 'service-booking-manager'); ?>" data-add-class="mActive">
                                                <input type="hidden" name="mpwpb_extra_service[]" data-value="<?php echo esc_attr($group_service_name); ?>" value=""/>
                                                <input type="hidden" name="mpwpb_extra_service_type[]" data-value="<?php echo esc_attr($ex_service_info['name']); ?>" value=""/>
                                                <span data-text><?php esc_html_e('Select', 'service-booking-manager'); ?></span>
                                                <span data-icon class="mL_xs"></span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <span class="fas fa-check mpwpb_item_check _circleIcon_xs"></span>
								<?php if ($ex_service_info['details']) { ?>
                                    <div data-collapse="<?php echo esc_attr($unique_id); ?>">
                                        <div class="divider"></div>
                                        <div><?php echo esc_html($ex_service_info['details']); ?></div>
                                    </div>
								<?php } ?>
                            </div>
							<?php
						}
					}
				}
			?>
        </div>
		<?php
	}