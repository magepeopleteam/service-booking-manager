<?php
	/*
   * @Author 		engr.sumonazma@gmail.com
   * Copyright: 	mage-people.com
   */
	if (!defined('ABSPATH')) {
		exit;
	}
	$post_id = $post_id ?? get_the_id();
	$all_sub_category = $all_sub_category ?? MPWPB_Function::get_sub_category($post_id);
	$category_text = $category_text ?? MPWPB_Function::get_category_text($post_id);
	$sub_category_text = $sub_category_text ?? MPWPB_Function::get_sub_category_text($post_id);
	$service_text = $service_text ?? MPWPB_Function::get_service_text($post_id);
	if (sizeof($all_sub_category) > 0) {
		?>
        <div class="_dShadow_7_mL_xs mpwpb_sub_category_area">
            <h5><?php echo esc_html__('Select', 'service-booking-manager') . ' ' . $sub_category_text; ?></h5>
			<?php
				foreach ($all_sub_category as $sub_category_item) {
					$category_name = array_key_exists('category', $sub_category_item) ? $sub_category_item['category'] : '';
					$sub_category_name = array_key_exists('sub_category', $sub_category_item) ? $sub_category_item['sub_category'] : '';
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
		<?php
	}