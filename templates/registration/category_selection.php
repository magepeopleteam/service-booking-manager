<?php
	/*
   * @Author 		engr.sumonazma@gmail.com
   * Copyright: 	mage-people.com
   */
	if (!defined('ABSPATH')) {
		exit;
	}
	$post_id = $post_id ?? get_the_id();
	$all_category = $all_category ?? MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_category_service', array());
	$all_sub_category = $all_sub_category ?? MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_sub_category_service', array());
	$all_services = $all_services ?? MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_service', array());


    $parent_category_prices = [];
    $sub_category_prices = [];

    if( is_array( $all_services ) && !empty( $all_services ) ){
        foreach ($all_services as $item) {
            $parent = $item['parent_cat'];
            $sub = $item['sub_cat'];
            $price = $item['price'];
            if( $parent >= 0 ) {
                $parent_category_prices[$parent][] = $price;
            }
            if( $sub >= 0 ){
                $sub_category_prices[$sub][] = $price;
            }

        }
    }

    $parent_min_max = [];
    if( !empty( $parent_category_prices ) ){
        foreach ($parent_category_prices as $parent_cat => $prices) {
            $parent_min_max[$parent_cat] = [
                'min' => min($prices),
                'max' => max($prices)
            ];
        }
    }


    $sub_min_max = [];
    if( !empty( $sub_category_prices ) ){
        foreach ($sub_category_prices as $sub_cat => $prices) {
            $sub_min_max[$sub_cat] = [
                'min' => min($prices),
                'max' => max($prices)
            ];
        }
    }



	$category_text = $category_text ?? MPWPB_Function::get_category_text($post_id);
	//echo '<pre>'; print_r($all_sub_category); echo '</pre>';
	if (sizeof($all_category) > 0) {
		?>
        <div class="_dShadow_7 mpwpb_category_area">
            <!--<header>
                <h5><?php /*esc_html_e('Select Type', 'service-booking-manager'); */?></h5>
            </header>-->
			<?php foreach ($all_category as $cat_key => $category) {
				$category_name = array_key_exists('name', $category) ? $category['name'] : '';
				$category_icon = array_key_exists('icon', $category) ? $category['icon'] : '';
				$category_image = array_key_exists('image', $category) ? $category['image'] : '';

                if( !empty( $parent_min_max ) ){
                    $max_min_price = $parent_min_max[ $cat_key ];
                    $min_price = wc_price( $max_min_price['min'] );
                }else{
                    $min_price = '';
                }

//                $max_price = wc_price( $max_min_price['max'] );

				?>
                <div class="mpwpb_category_section">
                    <div class="mpwpd_item_box_direction mpwpb_item_box mpwpb_category_item " data-category="<?php echo esc_attr($cat_key + 1); ?>" style=" align-items: flex-start">
                        <div class="mpwpd_category_info_holder">
                            <div class="alignCenter _fullWidth">
                                <?php if ($category_icon) { ?>
                                <div class="mpwpd_bg_image_area">
                                    <span class="<?php echo esc_attr($category_icon); ?> _mR_xs" style="font-size: 30px"></span>
                                </div>
                                <?php } ?>
                                <?php if ($category_image) { ?>
                                    <div class="mpwpd_bg_image_area">
                                        <div data-bg-image="<?php echo esc_attr(MPWPB_Global_Function::get_image_url('', $category_image, 'medium')); ?>"></div>
                                    </div>
                                <?php } ?>
                                <div class="mpwpb_category_info_holder">
                                    <div class="mpwpb_category_info">
                                        <h6><?php echo esc_html($category_name); ?></h6>
                                        <span class="fas fa-check mpwpb_item_check _circleIcon_xs" style="top: 15px"></span>
                                    </div>
                                    <div class="mpwpd_category_min_price"><?php esc_attr_e( 'Price Start At:', 'service-booking-manager' );?> <?php echo wp_kses_post( $min_price )?> </div>
                                </div>

                            </div>
                        </div>

                    </div>
					<?php if (sizeof($all_sub_category) > 0) {
						foreach ($all_sub_category as $sub_key => $sub_category_item) {
							$cat_id = array_key_exists('cat_id', $sub_category_item) ? $sub_category_item['cat_id'] : '';
							if ($cat_key == $cat_id) {
								$sub_category_name = array_key_exists('name', $sub_category_item) ? $sub_category_item['name'] : '';
								$sub_category_icon = array_key_exists('icon', $sub_category_item) ? $sub_category_item['icon'] : '';
								$sub_category_image = array_key_exists('image', $sub_category_item) ? $sub_category_item['image'] : '';

                                if( !empty( $sub_min_max ) ) {
                                    $sub_max_min_price = $sub_min_max[$sub_key];
                                    $sub_min_price = wc_price($sub_max_min_price['min']);
                                }else{
                                    $sub_min_price = '';
                                }

								?>
                                <div class="mpwpb_sub_category_area">
                                    <div class="mpwpd_item_box_direction mpwpb_item_box mpwpb_sub_category_item " data-category="<?php echo esc_attr($cat_key + 1); ?>" data-sub-category="<?php echo esc_attr($sub_key + 1); ?>" style=" align-items: flex-start">
                                        <div class="mpwpd_category_info_holder">
                                            <div class="alignCenter _fullWidth">
                                                <?php if ($sub_category_image) { ?>
                                                <div class="mpwpd_bg_image_area">
                                                    <div data-bg-image="<?php echo esc_attr(MPWPB_Global_Function::get_image_url('', $sub_category_image, 'medium')); ?>"></div>
                                                </div>
                                                <?php } ?>
                                                <?php if ($sub_category_icon) { ?>
                                                <div class="mpwpd_bg_image_area">
                                                    <span class="<?php echo esc_attr($sub_category_icon); ?> _mR_xs" style="font-size: 30px"
                                                    ></span>
                                                </div>
                                                <?php } ?>
                                                <div class="mpwpb_category_info_holder">
                                                    <div class="mpwpb_category_info">
                                                        <h6><?php echo esc_html($sub_category_name); ?></h6>
                                                        <span class="fas fa-check mpwpb_item_check _circleIcon_xs" style="top: 15px"></span>
                                                    </div>
                                                    <div class="mpwpd_category_min_price"><?php esc_attr_e( 'Price Start At:', 'service-booking-manager' );?> <?php echo wp_kses_post( $sub_min_price )?> </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
							<?php }
						}
					} ?>
                </div>
			<?php } ?>
        </div>
		<?php
	}