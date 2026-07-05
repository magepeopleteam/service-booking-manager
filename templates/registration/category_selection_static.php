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

	/**
	 * Always-visible category/sub-category/service tree (replaces the old
	 * click-to-drill-down cards). Leaf service nodes keep the exact same
	 * .mpwpb_item_box class + data-category/data-sub-category/data-service
	 * attributes as before, so the existing click handler (assets/frontend/
	 * mpwpb_registration.js:290-309) still opens the booking popup and
	 * simulates the matching real selection click inside it, completely
	 * unchanged. Category/sub-category rows are new — they only toggle
	 * expand/collapse locally (assets/frontend/mpwpb-service-tree.js), they
	 * never open the popup themselves.
	 */
	if (!function_exists('mpwpb_service_leaf')) {
		function mpwpb_service_leaf($post_id, $svc_key, $service_item, $cat_attr, $sub_attr) {
			$service_name = array_key_exists('name', $service_item) ? $service_item['name'] : '';
			$price = array_key_exists('price', $service_item) ? $service_item['price'] : 0;
			$wc_price = MPWPB_Global_Function::wc_price($post_id, $price);
			$duration = array_key_exists('duration', $service_item) ? $service_item['duration'] : '';
			?>
	        <div class="mpwpb_item_box mpwpb-tree-service" data-target-popup="#mpwpb_static_popup" data-category="<?php echo esc_attr($cat_attr); ?>" data-sub-category="<?php echo esc_attr($sub_attr); ?>" data-service="<?php echo esc_attr($svc_key + 1); ?>">
	            <span class="mpwpb-tree-checkbox" data-tree-checkbox></span>
	            <span class="mpwpb-tree-service-main">
	                <span class="mpwpb-tree-service-name"><?php echo esc_html($service_name); ?></span>
					<?php if ($duration) { ?><span class="mpwpb-tree-duration"><i class="fas fa-clock"></i> <?php echo wp_kses_post($duration); ?></span><?php } ?>
	            </span>
	            <span class="mpwpb-tree-service-side">
	                <span class="mpwpb-tree-price"><?php echo wp_kses_post($wc_price); ?></span>
	                <span class="mpwpb-tree-tap-hint" data-tree-tap-hint><?php esc_html_e('Tap to add', 'service-booking-manager'); ?></span>
	            </span>
	        </div>
			<?php
		}
	}

	if (sizeof($all_category) > 0 || sizeof($all_services) > 0) { ?>
		<div class="mpwpb-tree-header">
			<h4><?php esc_html_e('Our services', 'service-booking-manager'); ?></h4>
			<p><?php esc_html_e('Tap a service to start booking. You can add more inside.', 'service-booking-manager'); ?></p>
		</div>
	<?php }

	if (sizeof($all_category) > 0) {
		// Group services under their category / sub-category (raw 0-based
		// array indices as keys — the +1 display convention is applied only
		// when rendering data-category/data-sub-category attributes below).
		$tree = array();
		foreach ($all_category as $cat_key => $category) {
			// Loose comparison intentional: parent_cat/cat_id are stored as
			// numeric strings ("0", "1"...) while $cat_key is always an int.
			if (in_array($cat_key, $filtered_sub_category) || in_array($cat_key, $filtered_parent_cat)) {
				$tree[$cat_key] = array(
					'name' => array_key_exists('name', $category) ? $category['name'] : '',
					'own_services' => array(),
					'subcats' => array(),
				);
			}
		}
		if (sizeof($all_sub_category) > 0) {
			foreach ($all_sub_category as $sub_key => $sub_item) {
				$cat_id = array_key_exists('cat_id', $sub_item) && $sub_item['cat_id'] !== '' ? (int) $sub_item['cat_id'] : null;
				if ($cat_id !== null && isset($tree[$cat_id])) {
					$tree[$cat_id]['subcats'][$sub_key] = array(
						'name' => array_key_exists('name', $sub_item) ? $sub_item['name'] : '',
						'services' => array(),
					);
				}
			}
		}
		foreach ($all_services as $svc_key => $svc) {
			$parent = array_key_exists('parent_cat', $svc) && $svc['parent_cat'] !== '' && $svc['parent_cat'] !== null ? (int) $svc['parent_cat'] : null;
			$sub = array_key_exists('sub_cat', $svc) && $svc['sub_cat'] !== '' && $svc['sub_cat'] !== null ? (int) $svc['sub_cat'] : null;
			if ($parent === null || !isset($tree[$parent])) {
				continue;
			}
			if ($sub !== null && isset($tree[$parent]['subcats'][$sub])) {
				$tree[$parent]['subcats'][$sub]['services'][$svc_key] = $svc;
			} else {
				$tree[$parent]['own_services'][$svc_key] = $svc;
			}
		}
		?>
        <div class="mpwpb-service-tree">
			<?php foreach ($tree as $cat_key => $cat_node):
				$own_count = sizeof($cat_node['own_services']);
				$sub_total = 0;
				foreach ($cat_node['subcats'] as $sub_node) { $sub_total += sizeof($sub_node['services']); }
				$total_count = $own_count + $sub_total;
				if ($total_count < 1) { continue; }
				?>
                <div class="mpwpb-tree-cat">
                    <div class="mpwpb-tree-row mpwpb-tree-cat-row" data-tree-toggle>
                        <i class="fas fa-folder mpwpb-tree-folder-icon"></i>
                        <span class="mpwpb-tree-name"><?php echo esc_html($cat_node['name']); ?></span>
                        <span class="mpwpb-tree-count"><?php echo esc_html($total_count); ?></span>
                        <i class="fas fa-chevron-down mpwpb-tree-chevron"></i>
                    </div>
                    <div class="mpwpb-tree-children">
						<?php foreach ($cat_node['own_services'] as $svc_key => $svc) {
							mpwpb_service_leaf($post_id, $svc_key, $svc, $cat_key + 1, '');
						} ?>
						<?php foreach ($cat_node['subcats'] as $sub_key => $sub_node):
							$sub_count = sizeof($sub_node['services']);
							if ($sub_count < 1) { continue; }
							?>
                            <div class="mpwpb-tree-subcat">
                                <div class="mpwpb-tree-row mpwpb-tree-subcat-row" data-tree-toggle>
                                    <span class="mpwpb-tree-corner"></span>
                                    <span class="mpwpb-tree-name"><?php echo esc_html($sub_node['name']); ?></span>
                                    <span class="mpwpb-tree-count"><?php echo esc_html($sub_count); ?></span>
                                    <i class="fas fa-chevron-down mpwpb-tree-chevron"></i>
                                </div>
                                <div class="mpwpb-tree-children mpwpb-tree-children--sub">
									<?php foreach ($sub_node['services'] as $svc_key => $svc) {
										mpwpb_service_leaf($post_id, $svc_key, $svc, $cat_key + 1, $sub_key + 1);
									} ?>
                                </div>
                            </div>
						<?php endforeach; ?>
                    </div>
                </div>
			<?php endforeach; ?>
        </div>
	<?php } else {
		if (sizeof($all_services) > 0) { ?>
        <div class="mpwpb-service-tree mpwpb-service-tree--flat">
			<?php foreach ($all_services as $key => $service_item) {
				mpwpb_service_leaf($post_id, $key, $service_item, '', '');
			} ?>
        </div>
	<?php }
	}