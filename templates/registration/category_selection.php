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
	//echo '<pre>'; print_r($all_services); echo '</pre>';
	$all_category = $all_category ?? MPWPB_Function::get_category($post_id);
	$category_text = $category_text ?? MPWPB_Function::get_category_text($post_id);
	$sub_category_active = MP_Global_Function::get_post_info($post_id, 'mpwpb_sub_category_active', 'off');
	if (sizeof($all_category) > 0) {
		?>
        <div class="_dShadow_7 mpwpb_category_area">
			<?php foreach ($all_services as $all_service) {
				$category_name = array_key_exists('category', $all_service) ? $all_service['category'] : '';
				$category_icon = array_key_exists('icon', $all_service) ? $all_service['icon'] : '';
				$category_image = array_key_exists('image', $all_service) ? $all_service['image'] : '';
				$all_sub_category = array_key_exists('sub_category', $all_service) ? $all_service['sub_category'] : [];
				//echo '<pre>'; print_r($all_sub_category); echo '</pre>';
				?>
                <div class="mpwpb_category_section">
                    <div class="mpwpb_item_box mpwpb_category_item" data-category="<?php echo esc_attr($category_name); ?>">
                        <h6 class="alignCenter">
							<?php if ($category_icon) { ?>
                                <span class="<?php echo esc_attr($category_icon); ?> _mR_xs"></span>
							<?php } ?>
							<?php if ($category_image) { ?>
                                <div class="bg_image_area">
                                    <div data-bg-image="<?php echo esc_attr(MP_Global_Function::get_image_url('', $category_image, 'medium')); ?>"></div>
                                </div>
							<?php } ?>
							<?php echo esc_html($category_name); ?>
                        </h6>
                        <span class="fas fa-check mpwpb_item_check _circleIcon_xs"></span>
                    </div>
					<?php if (sizeof($all_sub_category) > 0 && $sub_category_active=='on') { ?>
                        <div class="mpwpb_sub_category_area">
							<?php foreach ($all_sub_category as $sub_category_item) {
								$sub_category_name = array_key_exists('name', $sub_category_item) ? $sub_category_item['name'] : '';
								$sub_category_icon = array_key_exists('icon', $sub_category_item) ? $sub_category_item['icon'] : '';
								$sub_category_image = array_key_exists('image', $sub_category_item) ? $sub_category_item['image'] : '';
								?>
                                <div class="mpwpb_item_box mpwpb_sub_category_item " data-category="<?php echo esc_attr($category_name); ?>" data-sub-category="<?php echo esc_attr($sub_category_name); ?>">
									<?php if ($sub_category_image) { ?>
                                        <div class="_mB_xs">
                                            <div class="bg_image_area">
                                                <div data-bg-image="<?php echo esc_attr(MP_Global_Function::get_image_url('', $sub_category_image, 'medium')); ?>"></div>
                                            </div>
                                        </div>
									<?php } ?>
                                    <h6 class="alignCenter">
										<?php if ($sub_category_icon) { ?>
                                            <span class="<?php echo esc_attr($sub_category_icon); ?> _mR_xs"></span>
										<?php } ?>
										<?php echo esc_html($sub_category_name); ?>
                                    </h6>
                                    <span class="fas fa-check mpwpb_item_check _circleIcon_xs"></span>
                                </div>
							<?php } ?>
                        </div>
					<?php } ?>
                </div>
			<?php } ?>
        </div>
		<?php
	}