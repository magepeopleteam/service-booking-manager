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

    error_log( print_r( [ '$all_services' => $all_services ], true ) );
    $category_selection_parent_cat = $category_selection_sub_category = $services_sub_cat = [];
    if( is_array( $all_services ) && !empty( $all_services ) ){
        $service_parent_cat = array_column( $all_services, 'parent_cat' );
        $service_sub_cat = array_column( $all_services, 'sub_cat' );
        if( !empty( $service_parent_cat ) ){
            $filtered = array_unique(array_filter( $service_parent_cat, function($value ) {
                return $value !== '' && $value !== null;
            }));
            $category_selection_parent_cat = array_values($filtered);
        }
        if( !empty( $service_sub_cat ) ){
            $sub_cat_filtered = array_unique(array_filter( $service_sub_cat, function($value ) {
                return $value !== '' && $value !== null;
            }));
            $services_sub_cat = array_values( $sub_cat_filtered );
        }
    }
    if( is_array( $all_services ) && !empty( $all_services ) ){
        $sub_category = array_column( $all_sub_category, 'cat_id');
        if( !empty( $sub_category ) ){
            $filtered = array_unique(array_filter( $sub_category, function($value ) {
                return $value !== '' && $value !== null;
            }));
            $category_selection_sub_category = array_values($filtered);
        }
    }

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

	if (sizeof($all_category) > 0) {

        if (sizeof( $all_services) > 0 && sizeof($all_category) > 0 ) {
            ?>
            <div class="_dShadow_7_mB_xs mpwpb_without_cat_service_area">
                <header>
                    <input type="hidden" name="mpwpb_category" value="">
                    <input type="hidden" name="mpwpb_sub_category" value="">
                    <!--                <h5>--><?php //echo esc_html__('Select', 'service-booking-manager') . ' ' . esc_html($service_text); ?><!--</h5>-->
                </header>
                <?php
                foreach ($all_services as $service_key => $service_item) {
                    //                    error_log( print_r( [ '$all_services' => $all_services ], true ) );

                    $category_name = array_key_exists('parent_cat', $service_item) && ($service_item['parent_cat'] || $service_item['parent_cat']==0)? (int)$service_item['parent_cat']+1 : '';
                    $sub_category_name = array_key_exists('sub_cat', $service_item)&& ($service_item['sub_cat']|| $service_item['sub_cat']==0)  ?(int) $service_item['sub_cat']+1 : '';
                    $service_name = array_key_exists('name', $service_item) ? $service_item['name'] : '';
                    $service_image = array_key_exists('image', $service_item) ? $service_item['image'] : '';
                    $service_icon = array_key_exists('icon', $service_item) ? $service_item['icon'] : '';
                    $service_price = array_key_exists('price', $service_item) ? $service_item['price'] : 0;
                    $service_wc_price = MPWPB_Global_Function::wc_price($post_id, $service_price);
                    $service_price = MPWPB_Global_Function::price_convert_raw($service_wc_price);
                    $service_details = array_key_exists('details', $service_item) ? $service_item['details'] : '';
                    $service_duration = array_key_exists('duration', $service_item) ? $service_item['duration'] : '';
                    $unique_id = '#service_' . uniqid();;

                    $multiple_service_check = get_post_meta( $post_id, 'mpwpb_multiple_service_select', true );

                    if( empty( $category_name ) && empty( $sub_category_name ) ){
                        //echo '<pre>'; print_r($sub_category_name); echo '</pre>';
                        ?>
                        <div class="mpwpb_service_item" id="mpwpb_service_item<?php echo esc_attr( $service_key+1 )?>" data-price="<?php echo esc_attr($service_price); ?>" data-category="<?php echo esc_attr($category_name); ?>" data-sub-category="<?php echo esc_attr($sub_category_name); ?>" data-service="<?php echo esc_attr($service_key+1); ?>" data-service-qty="1">
                            <div class="_dFlex">
                                <?php if ($service_image) { ?>
                                    <div class="bg_image_area _w_75_mR_xs">
                                        <div data-bg-image="<?php echo esc_attr(MPWPB_Global_Function::get_image_url('', $service_image, 'medium')); ?>"></div>
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
                                                    <span data-text>&nbsp;</span>
                                                </div>
                                            <?php } ?>
                                            <?php if ($service_duration) { ?>
                                                <h6 class="textTheme alignCenter">
                                                    <span class="fas fa-clock mR_xs"></span>
                                                    <span><?php echo wp_kses_post($service_duration); ?></span>
                                                </h6>
                                            <?php } ?>
                                            <h6 class="_textTheme_min_100"><?php echo wp_kses_post($service_wc_price); ?></h6>
                                        </div>
                                    </div>
                                    <!--<div>
                                        <button type="button" class="_mpBtn_btLight_4 _min_125 mpwpb_service_button" data-open-text="<?php /*esc_attr_e('Add', 'service-booking-manager'); */?>" data-close-text="<?php /*esc_attr_e('Added', 'service-booking-manager'); */?>" data-add-class="mActive">
                                            <span data-text><?php /*esc_html_e('Add', 'service-booking-manager'); */?></span>
                                            <span data-icon="" class="mL_xs fas fa-plus"></span>
                                            <input type="hidden" name="mpwpb_service[]" value="">
                                        </button>
                                    </div>-->

                                    <div class="alignCenter quantity-box" >
                                        <?php if( $multiple_service_check === 'on' ){?>
                                            <div class="mR_xs min_100 mpwpb_service_inc_dec_holder" data-service-collapse="<?php echo esc_attr($unique_id); ?>" style="display: none">
                                                <div class="groupContent qtyIncDec">
                                                    <div class="service_decQty addonGroupContent">
                                                        <span class="fas fa-minus"></span>
                                                    </div>
                                                    <label>
                                                        <input type="text"
                                                               class="formControl inputIncDec mpwpb_number_validation"
                                                               data-price="<?php echo esc_attr($service_price); ?>"
                                                               name="mpwpb_service_qtt[]"
                                                               value="<?php echo esc_attr(max(1, 0)); ?>"
                                                               min="<?php echo esc_attr(1); ?>"
                                                               max="<?php echo esc_attr(10); ?>"
                                                        />
                                                    </label>
                                                    <div class="service_incQty addonGroupContent">
                                                        <span class="fas fa-plus"></span>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php }?>
                                        <button type="button" class="_mpBtn_btLight_4 _min_125 mpwpb_service_button" data-open-text="<?php esc_attr_e('Add', 'service-booking-manager'); ?>" data-close-text="<?php esc_attr_e('Added', 'service-booking-manager'); ?>" data-add-class="mActive">
                                            <span data-text><?php esc_html_e('Add', 'service-booking-manager'); ?></span>
                                            <span data-icon="" class="mL_xs fas fa-plus"></span>
                                            <input type="hidden" name="mpwpb_service[]" value="">
                                        </button>
                                    </div>

                                </div>
                            </div>
                            <div data-collapse="<?php echo esc_attr($unique_id); ?>"><?php echo esc_html($service_details); ?></div>
                        </div>
                        <?php
                    }
                } ?>
            </div>
            <?php
        }

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
                    if( isset( $parent_min_max[ $cat_key ] ) ){
                        $max_min_price = $parent_min_max[ $cat_key ];
                        $min_price = wc_price( $max_min_price['min'] );
                    }else{
                        $min_price = '';
                    }
                }else{
                    $min_price = '';
                }

//                $max_price = wc_price( $max_min_price['max'] );

            if( in_array( $cat_key, $category_selection_sub_category ) || in_array( $cat_key, $category_selection_parent_cat ) ){
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
					<?php if ( sizeof( $all_sub_category ) > 0) {
						foreach ($all_sub_category as $sub_key => $sub_category_item) {
							$cat_id = array_key_exists('cat_id', $sub_category_item) ? $sub_category_item['cat_id'] : '';
							if ($cat_key == $cat_id) {
								$sub_category_name = array_key_exists('name', $sub_category_item) ? $sub_category_item['name'] : '';
								$sub_category_icon = array_key_exists('icon', $sub_category_item) ? $sub_category_item['icon'] : '';
								$sub_category_image = array_key_exists('image', $sub_category_item) ? $sub_category_item['image'] : '';

                                if( !empty( $sub_min_max ) ) {
                                    if( isset( $sub_min_max[$sub_key] ) ){
                                        $sub_max_min_price = $sub_min_max[$sub_key];
                                        $sub_min_price = wc_price($sub_max_min_price['min']);
                                    }else{
                                        $sub_max_min_price = '';
                                    }

                                }else{
                                    $sub_min_price = '';
                                }

                                if( in_array( $sub_key, $services_sub_cat ) ){
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
                        }
					} ?>
                </div>
			<?php } } ?>
        </div>
		<?php

	}