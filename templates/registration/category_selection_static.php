<?php
	/*
   * @Author 		engr.sumonazma@gmail.com
   * Copyright: 	mage-people.com
   */
	if (!defined('ABSPATH')) {
		exit;
	}
	$post_id = $post_id ?? get_the_id();
	$all_category = $all_category??MP_Global_Function::get_post_info($post_id, 'mpwpb_category_service', array());
	$all_sub_category = $all_sub_category??MP_Global_Function::get_post_info($post_id, 'mpwpb_sub_category_service', array());
	$all_services = $all_services??MP_Global_Function::get_post_info($post_id, 'mpwpb_service', array());
	if (sizeof($all_category) > 0) {
		foreach ($all_category as $cat_key=>$category) { ?>
            <div class="mpwpb_item_box" data-category="<?php echo esc_attr($cat_key+1); ?>" data-target-popup="#mpwpb_static_popup">
                <h2><?php echo esc_html($category['name']); ?></h2>
                <i class="fas fa-chevron-right mpwpb_item_check"></i>
            </div>
		<?php }
	} else {
		if (sizeof($all_services) > 0) {
			foreach ($all_services as $key=>$service_item) {
				$category_name = array_key_exists('parent_cat', $service_item) && $service_item['parent_cat']? (int)$service_item['parent_cat']+1 : '';
				$sub_category_name = array_key_exists('sub_cat', $service_item)&& $service_item['sub_cat'] ?(int) $service_item['sub_cat']+1 : '';
				$service_name = array_key_exists('name', $service_item) ? $service_item['name'] : ''; ?>
                <div class="mpwpb_item_box" data-target-popup="#mpwpb_static_popup" data-category="<?php echo esc_attr($category_name); ?>" data-sub-category="<?php echo esc_attr($sub_category_name); ?>" data-service="<?php echo esc_attr($key+1); ?>">
                    <h2><?php echo esc_html($service_name); ?></h2>
                    <i class="fas fa-chevron-right mpwpb_item_check"></i>
                </div>
			<?php }
		}
	}