<?php
	/*
   * @Author 		engr.sumonazma@gmail.com
   * Copyright: 	mage-people.com
   */
	if (!defined('ABSPATH')) {
		exit;
	}
	$post_id = $post_id ?? get_the_id();

	$service_text = $service_text ?? MPWPB_Function::get_service_text($post_id);
	$all_services = $all_services??MP_Global_Function::get_post_info($post_id, 'mpwpb_service', array());
	//echo '<pre>'; print_r($all_services); echo '</pre>';
	if (sizeof($all_services) > 0) {
		?>
        <div class="_dShadow_7_mB_xs mpwpb_service_area">
            <header>
                <input type="hidden" name="mpwpb_category" value="">
                <input type="hidden" name="mpwpb_sub_category" value="">
                <h5><?php echo esc_html__('Select', 'service-booking-manager') . ' ' . $service_text; ?></h5>
            </header>
			<?php
				foreach ($all_services as $service_key=>$service_item) {
					$category_name = array_key_exists('parent_cat', $service_item) ? $service_item['parent_cat'] : '';
					$sub_category_name = array_key_exists('sub_cat', $service_item) ? $service_item['sub_cat'] : '';
					$service_name = array_key_exists('name', $service_item) ? $service_item['name'] : '';
					$service_image = array_key_exists('image', $service_item) ? $service_item['image'] : '';
					$service_icon = array_key_exists('icon', $service_item) ? $service_item['icon'] : '';
					$service_price = array_key_exists('price', $service_item) ? $service_item['price'] : 0;
					//$service_price = MPWPB_Function::get_price($post_id, $service_key, $category_name, $sub_category_name);
					$service_details = array_key_exists('details', $service_item) ? $service_item['details'] : '';
					$service_duration = array_key_exists('duration', $service_item) ? $service_item['duration'] : '';
					$unique_id = '#service_' . uniqid();
					?>
                    <div class="mpwpb_service_item" data-price="<?php echo esc_attr($service_price); ?>" data-category="<?php echo esc_attr($category_name+1); ?>" data-sub-category="<?php echo esc_attr($sub_category_name+1); ?>" data-service="<?php echo esc_attr($service_key+1); ?>">
                        <div class="_dFlex">
							<?php if ($service_image) { ?>
                                <div class="bg_image_area _w_75_mR_xs">
                                    <div data-bg-image="<?php echo esc_attr(MP_Global_Function::get_image_url('', $service_image, 'medium')); ?>"></div>
                                </div>
							<?php } ?>
                            <div class="_dFlex_justifyBetween_fullWidth">
                                <div class="_fdColumn_fullWidth">
                                    <div class="alignCenter">
										<?php if ($service_icon) { ?>
                                            <span class="<?php echo esc_attr($service_icon); ?> mR_xs"></span>
										<?php } ?>
                                        <h6><?php echo esc_html($service_name); ?></h6>
                                    </div>
                                    <div class="_equalChild">
                                        <?php if (isset($ex_service_info['details'])) { ?>
                                            <div data-collapse-target="<?php echo esc_attr($unique_id); ?>" data-read data-open-text="<?php esc_attr_e('Close Details', 'service-booking-manager'); ?>" data-close-text="<?php esc_attr_e('View Details', 'service-booking-manager'); ?>">
                                                <span data-text><?php esc_html_e('', 'service-booking-manager'); ?></span>
                                            </div>
                                        <?php } ?>
										<?php if ($service_duration) { ?>
                                            <h6 class="textTheme alignCenter">
                                                <span class="fas fa-clock mR_xs"></span>
                                                <span><?php echo MP_Global_Function::esc_html($service_duration); ?></span>
                                            </h6>
										<?php } ?>
                                        <h6 class="_textTheme_min_100"><?php echo wc_price($service_price); ?></h6>
                                    </div>
                                </div>
                                <div>
                                    <button type="button" class="_mpBtn_btLight_4 _min_125 mpwpb_service_button"  data-open-text="<?php esc_attr_e('Add', 'service-booking-manager'); ?>" data-close-text="<?php esc_attr_e('Added', 'service-booking-manager'); ?>" data-add-class="mActive">
                                        <span data-text><?php esc_html_e('Add', 'service-booking-manager'); ?></span>
                                        <span data-icon="" class="mL_xs fas fa-plus"></span>
                                        <input type="hidden" name="mpwpb_service[]" value="">
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div data-collapse="<?php echo esc_attr($unique_id); ?>"><?php echo esc_html($service_details); ?></div>
                    </div>
				<?php } ?>
        </div>
		<?php
	}