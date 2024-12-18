<?php
	/*
   * @Author 		engr.sumonazma@gmail.com
   * Copyright: 	mage-people.com
   */
	if (!defined('ABSPATH')) {
		exit;
	}
	$post_id = $post_id ?? get_the_id();
	$all_category = $all_category ?? MP_Global_Function::get_post_info($post_id, 'mpwpb_category_service', array());
	$all_sub_category = $all_sub_category ?? MP_Global_Function::get_post_info($post_id, 'mpwpb_sub_category_service', array());
	$all_services = $all_services ?? MP_Global_Function::get_post_info($post_id, 'mpwpb_service', array());
    //echo '<pre>'; print_r($all_services); echo '</pre>';
	$category_text = $category_text ?? MPWPB_Function::get_category_text($post_id);
	$sub_category_active = MP_Global_Function::get_post_info($post_id, 'mpwpb_sub_category_active', 'off');
	if (sizeof($all_category) > 0) {
		?>
        <div class="_dShadow_7 mpwpb_category_area">
			<?php foreach ($all_category as $cat_key => $category) {
				$category_name = array_key_exists('name', $category) ? $category['name'] : '';
				$category_icon = array_key_exists('icon', $category) ? $category['icon'] : '';
				$category_image = array_key_exists('image', $category) ? $category['image'] : '';
				//echo '<pre>'; print_r($all_sub_category); echo '</pre>';
				?>
                <div class="mpwpb_category_section">
                    <div class="mpwpb_item_box mpwpb_category_item" data-category="<?php echo esc_attr($cat_key+1); ?>">
                        <div class="alignCenter _fullWidth">
							<?php if ($category_icon) { ?>
                                <span class="<?php echo esc_attr($category_icon); ?> _mR_xs"></span>
							<?php } ?>
							<?php if ($category_image) { ?>
                                <div class="bg_image_area">
                                    <div data-bg-image="<?php echo esc_attr(MP_Global_Function::get_image_url('', $category_image, 'medium')); ?>"></div>
                                </div>
							<?php } ?>
                            <h6><?php echo esc_html($category_name); ?></h6>
                        </div>
                        <span class="fas fa-check mpwpb_item_check _circleIcon_xs"></span>
                    </div>
					<?php if (sizeof($all_sub_category) > 0 && $sub_category_active == 'on') { ?>
                        <div class="mpwpb_sub_category_area">
							<?php foreach ($all_sub_category as $sub_key => $sub_category_item) {
								$cat_id = array_key_exists('cat_id', $sub_category_item) ? $sub_category_item['cat_id'] : '';
								if ($cat_key == $cat_id) {
									$sub_category_name = array_key_exists('name', $sub_category_item) ? $sub_category_item['name'] : '';
									$sub_category_icon = array_key_exists('icon', $sub_category_item) ? $sub_category_item['icon'] : '';
									$sub_category_image = array_key_exists('image', $sub_category_item) ? $sub_category_item['image'] : '';
									?>
                                    <div class="mpwpb_item_box mpwpb_sub_category_item " data-category="<?php echo esc_attr($cat_key+1); ?>" data-sub-category="<?php echo esc_attr($sub_key+1); ?>">
                                        <div class="alignCenter _fullWidth">
											<?php if ($sub_category_image) { ?>
                                                <div class="bg_image_area">
                                                    <div data-bg-image="<?php echo esc_attr(MP_Global_Function::get_image_url('', $sub_category_image, 'medium')); ?>"></div>
                                                </div>
											<?php } ?>
											<?php if ($sub_category_icon) { ?>
                                                <span class="<?php echo esc_attr($sub_category_icon); ?> _mR_xs"></span>
											<?php } ?>
                                            <h6><?php echo esc_html($sub_category_name); ?></h6>
                                        </div>
                                        <span class="fas fa-check mpwpb_item_check _circleIcon_xs"></span>
                                    </div>
								<?php }
							} ?>
                        </div>
					<?php } ?>
                </div>
			<?php } ?>
        </div>
		<?php
	}