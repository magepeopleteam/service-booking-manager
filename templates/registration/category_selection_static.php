<?php
	/*
   * @Author 		engr.sumonazma@gmail.com
   * Copyright: 	mage-people.com
   */
	if (!defined('ABSPATH')) {
		exit;
	}
	$post_id = $post_id ?? get_the_id();

    if( $shortcode === 'yes' ){
        $all_category = MPWPB_Global_Function::get_post_info( $post_id, 'mpwpb_category_service', array() );
        $all_sub_category = MPWPB_Global_Function::get_post_info( $post_id, 'mpwpb_sub_category_service', array() );
        $all_services = MPWPB_Global_Function::get_post_info( $post_id, 'mpwpb_service', array() );
    }else{
        $all_category = $all_category ?? MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_category_service', array());
        $all_sub_category = $all_sub_category ?? MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_sub_category_service', array());
        $all_services = $all_services ?? MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_service', array());
    }

    $filtered_parent_cat = $filtered_sub_category = [];
    if( is_array( $all_services ) && !empty( $all_services ) ){
        $service_parent_cat = array_column( $all_services, 'parent_cat' );
        if( !empty( $service_parent_cat ) ){
            $filtered = array_unique(array_filter( $service_parent_cat, function($value ) {
                return $value !== '' && $value !== null;
            }));
            $filtered_parent_cat = array_values($filtered);
        }

    }

    if( is_array( $all_services ) && !empty( $all_services ) ){
        $sub_category = array_column( $all_sub_category, 'cat_id');
        if( !empty( $sub_category ) ){
            $filtered = array_unique(array_filter( $sub_category, function($value ) {
                return $value !== '' && $value !== null;
            }));
            $filtered_sub_category = array_values($filtered);
        }
    }

	if (sizeof($all_category) > 0) {
		foreach ($all_category as $cat_key => $category) {
			$category_icon = array_key_exists('icon', $category) ? $category['icon'] : '';
			$category_image = array_key_exists('image', $category) ? $category['image'] : '';

            if( in_array( $cat_key, $filtered_sub_category ) || in_array( $cat_key, $filtered_parent_cat ) ){
			?>
            <div class="mpwpb_item_box" data-category="<?php echo esc_attr($cat_key + 1); ?>" data-target-popup="#mpwpb_static_popup">
                <div class="alignCenter">
					<?php if ($category_icon) { ?>
                        <span class="<?php echo esc_attr($category_icon); ?> _mR_xs"></span>
					<?php } ?>
					<?php if ($category_image) { ?>
                        <div class="bg_image_area">
                            <div data-bg-image="<?php echo esc_attr(MPWPB_Global_Function::get_image_url('', $category_image, 'medium')); ?>"></div>
                        </div>
					<?php } ?>
                    <h2><?php echo esc_html($category['name']); ?></h2>
                </div>
                <i class="fas fa-chevron-right mpwpb_item_check"></i>
            </div>
		<?php }
        }
	} else {
		if (sizeof($all_services) > 0) {
			foreach ($all_services as $key => $service_item) {
				$category_name = array_key_exists('parent_cat', $service_item) && ($service_item['parent_cat'] || $service_item['parent_cat'] == 0) ? (int)$service_item['parent_cat'] + 1 : '';
				$sub_category_name = array_key_exists('sub_cat', $service_item) && ($service_item['sub_cat'] || $service_item['sub_cat'] == 0) ? (int)$service_item['sub_cat'] + 1 : '';
				$service_name = array_key_exists('name', $service_item) ? $service_item['name'] : '';
				$service_image = array_key_exists('image', $service_item) ? $service_item['image'] : '';
				$service_icon = array_key_exists('icon', $service_item) ? $service_item['icon'] : '';

                ?>
                <div class="mpwpb_item_box" data-target-popup="#mpwpb_static_popup" data-category="<?php echo esc_attr($category_name); ?>" data-sub-category="<?php echo esc_attr($sub_category_name); ?>" data-service="<?php echo esc_attr($key + 1); ?>">
                <div class="alignCenter">
	                <?php if ($service_icon) { ?>
                        <span class="<?php echo esc_attr($service_icon); ?> _mR_xs"></span>
	                <?php } ?>
	                <?php if ($service_image) { ?>
                        <div class="bg_image_area">
                            <div data-bg-image="<?php echo esc_attr(MPWPB_Global_Function::get_image_url('', $service_image, 'medium')); ?>"></div>
                        </div>
	                <?php } ?>
                    <h2><?php echo esc_html($service_name); ?></h2>
                </div>
                    <i class="fas fa-chevron-right mpwpb_item_check"></i>
                </div>
			<?php }
		}
	}