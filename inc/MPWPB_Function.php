<?php
	/*
   * @Author 		engr.sumonazma@gmail.com
   * Copyright: 	mage-people.com
   */
	if (!defined('ABSPATH')) {
		die;
	} // Cannot access pages directly.
	if (!class_exists('MPWPB_Function')) {
		class MPWPB_Function {
			public function __construct() {}
			//************************************************************Partially custom Function******************************//
			//***********Template********************//
			public static function details_template_path($post_id = ''): string {
				$post_id = $post_id ?? get_the_id();
				$template_name = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_template', 'default.php');
				$file_name = 'themes/' . $template_name;
				$dir = MPWPB_PLUGIN_DIR . '/templates/' . $file_name;
				if (!file_exists($dir)) {
					$file_name = 'themes/default.php';
				}
				return self::template_path($file_name);
			}
			public static function template_path($file_name): string {
				$template_path = get_stylesheet_directory() . '/mpwpb_templates/';
				$default_dir = MPWPB_PLUGIN_DIR . '/templates/';
				$dir = is_dir($template_path) ? $template_path : $default_dir;
				$file_path = $dir . $file_name;
				return locate_template(['mpwpb_templates/' . $file_name]) ? $file_path : $default_dir . $file_name;
			}
			//************************//
			public static function get_general_settings($key, $default = '') {
				return MPWPB_Global_Function::get_settings('mpwpb_general_settings', $key, $default);
			}
			//*****************//
			public static function get_cpt(): string {
				return 'mpwpb_item';
			}
			public static function get_name() {
				return self::get_general_settings('label', esc_html__('Service Booking', 'service-booking-manager'));
			}
			public static function get_slug() {
				return self::get_general_settings('slug', 'service-booking');
			}
			public static function get_icon() {
				return self::get_general_settings('icon', 'dashicons-list-view');
			}
			public static function get_category_label() {
				return self::get_general_settings('category_label', esc_html__('Category', 'service-booking-manager'));
			}
			public static function get_category_slug() {
				return self::get_general_settings('category_slug', 'service-category');
			}
			public static function get_organizer_label() {
				return self::get_general_settings('organizer_label', esc_html__('Organizer', 'service-booking-manager'));
			}
			public static function get_organizer_slug() {
				return self::get_general_settings('organizer_slug', 'service-organizer');
			}
			//*************************************************************Full Custom Function******************************//
			//*******************************//
			public static function get_category_text($post_id) {
				$text = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_category_text');
				return $text ?: self::get_general_settings('category_text', esc_html__('Category', 'service-booking-manager'));
			}
			public static function get_sub_category_text($post_id) {
				$text = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_sub_category_text');
				return $text ?: self::get_general_settings('sub_category_text', esc_html__('Sub-Category', 'service-booking-manager'));
			}
			public static function get_service_text($post_id) {
				$text = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_service_text');
				return $text ?: self::get_general_settings('service_text', esc_html__('Service', 'service-booking-manager'));
			}
			//*******************************//
			public static function get_category_name($post_id,$cat_id='') {
				if($cat_id && $cat_id>0 && $post_id){
					$all_category = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_category_service', array());
					if (sizeof($all_category) > 0) {
						foreach ($all_category as $cat_key => $category) {
							if($cat_key+1 == $cat_id){
								return array_key_exists('name', $category) ? $category['name'] : '';
							}
						}
					}
				}
				return '';
			}
			public static function get_sub_category_name($post_id,$sub_cat_id='') {
				if($sub_cat_id && $sub_cat_id>0 && $post_id){
					$all_category = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_sub_category_service', array());
					if (sizeof($all_category) > 0) {
						foreach ($all_category as $cat_key => $category) {
							if($cat_key+1 == $sub_cat_id){
								return array_key_exists('name', $category) ? $category['name'] : '';
							}
						}
					}
				}
				return '';
			}
			public static function get_service_name($post_id,$service_id='') {
				if($service_id && $service_id>0 && $post_id){
					$all_category = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_service', array());
					if (sizeof($all_category) > 0) {
						foreach ($all_category as $cat_key => $category) {
							if($cat_key+1 == $service_id){
								return array_key_exists('name', $category) ? $category['name'] : '';
							}
						}
					}
				}
				return '';
			}
			public static function get_category($post_id, $all_services = []) {
				$categories = [];
				$all_services = $all_services ?: MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_category_infos', []);
				if (sizeof($all_services) > 0) {
					$count = 0;
					foreach ($all_services as $service) {
						if (array_key_exists('category', $service) && $service['category']) {
							$categories[$count]['name'] = $service['category'];
							$categories[$count]['icon'] = array_key_exists('icon', $service) ? $service['icon'] : '';
							$categories[$count]['image'] = array_key_exists('image', $service) ? $service['image'] : '';
							$count++;
						}
					}
				}
				return $categories;
			}
			public static function get_sub_category($post_id, $all_services = []) {
				$sub_category_list = [];
				$all_services = $all_services ?: MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_category_infos', []);
				$count = 0;
				if (sizeof($all_services) > 0) {
					foreach ($all_services as $category_info) {
						$category_name = array_key_exists('category', $category_info) ? $category_info['category'] : '';
						$sub_categories = array_key_exists('sub_category', $category_info) ? $category_info['sub_category'] : [];
						if ($category_name && sizeof($sub_categories) > 0) {
							foreach ($sub_categories as $sub_category) {
								$sub_category_name = array_key_exists('name', $sub_category) ? $sub_category['name'] : '';
								$sub_category_icon = array_key_exists('icon', $sub_category) ? $sub_category['icon'] : '';
								$sub_category_image = array_key_exists('image', $sub_category) ? $sub_category['image'] : '';
								if ($sub_category_name) {
									$sub_category_list[$count]['category'] = $category_name;
									$sub_category_list[$count]['sub_category'] = $sub_category_name;
									$sub_category_list[$count]['icon'] = $sub_category_icon;
									$sub_category_list[$count]['image'] = $sub_category_image;
									$count++;
								}
							}
						}
					}
				}
				return $sub_category_list;
			}
			public static function get_all_service($post_id) {
				$all_service_item = [];
				$all_services = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_category_infos', []);
				$count = 0;
				if (sizeof($all_services) > 0) {
					foreach ($all_services as $category_info) {
						$category_name = array_key_exists('category', $category_info) ? $category_info['category'] : '';
						$sub_categories = array_key_exists('sub_category', $category_info) ? $category_info['sub_category'] : [];
						if (sizeof($sub_categories) > 0) {
							foreach ($sub_categories as $sub_category) {
								$sub_category_name = array_key_exists('name', $sub_category) ? $sub_category['name'] : '';
								$services = array_key_exists('service', $sub_category) ? $sub_category['service'] : [];
								if (sizeof($services) > 0) {
									foreach ($services as $service) {
										$all_service_item[$count]['category'] = $category_name;
										$all_service_item[$count]['sub_category'] = $sub_category_name;
										$all_service_item[$count]['service'] = array_key_exists('name', $service) ? $service['name'] : '';
										$all_service_item[$count]['price'] = array_key_exists('price', $service) ? $service['price'] : '';
										$all_service_item[$count]['image'] = array_key_exists('image', $service) ? $service['image'] : '';
										$all_service_item[$count]['icon'] = array_key_exists('icon', $service) ? $service['icon'] : '';
										$all_service_item[$count]['duration'] = array_key_exists('duration', $service) ? $service['duration'] : '';
										$all_service_item[$count]['details'] = array_key_exists('details', $service) ? $service['details'] : '';
										$count++;
									}
								}
							}
						}
					}
				}
				return $all_service_item;
			}
			//*********Date and Time**********************//
			public static function get_date($post_id, $expire = false) {
				$now = current_time('Y-m-d');
				$date_type = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_date_type', 'repeated');
				$all_dates = [];
				$off_days = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_off_days');
				$all_off_days = explode(',', $off_days);
				$all_off_dates = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_off_dates', array());
				$off_dates = [];
				foreach ($all_off_dates as $off_date) {
					$off_dates[] = date_i18n('Y-m-d', strtotime($off_date));
				}
				if ($date_type == 'repeated') {
					$start_date = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_repeated_start_date', $now);
					if (strtotime($now) >= strtotime($start_date) && !$expire) {
						$start_date = $now;
					}
					$repeated_after = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_repeated_after', 1);
					$active_days = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_active_days', 10) - 1;
					$end_date = date_i18n('Y-m-d', strtotime($start_date . ' +' . $active_days . ' day'));
					$dates = MPWPB_Global_Function::date_separate_period($start_date, $end_date, $repeated_after);
					foreach ($dates as $date) {
						$date = $date->format('Y-m-d');
						$day = strtolower(date_i18n('l', strtotime($date)));
						if (!in_array($date, $off_dates) && !in_array($day, $all_off_days)) {
							$all_dates[] = $date;
						}
					}
				}
				else {
					$particular_date_lists = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_particular_dates', array());
					if (sizeof($particular_date_lists)) {
						foreach ($particular_date_lists as $particular_date) {
							if ($particular_date && ($expire || strtotime($now) <= strtotime($particular_date)) && !in_array($particular_date, $off_dates) && !in_array($particular_date, $all_off_days)) {
								$all_dates[] = $particular_date;
							}
						}
					}
				}
				return apply_filters('mpwpb_get_date', $all_dates, $post_id);
			}
			public static function get_time_slot($post_id, $start_date) {
				$now_full = current_time( 'Y-m-d H:i' );

				$all_slots = [];
				$slot_length = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_time_slot_length', 30);
				$slot_length = $slot_length * 60;
				$day_name = strtolower(date_i18n('l', strtotime($start_date)));
				$start_time = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_' . $day_name . '_start_time');
				if (!$start_time) {
					$day_name = 'default';
					$start_time = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_' . $day_name . '_start_time', 10);
				}
				$start_time = $start_time * 3600;
				$end_time = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_' . $day_name . '_end_time', 18) * 3600;
				$start_time_break = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_' . $day_name . '_start_break_time', 0) * 3600;
				$end_time_break = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_' . $day_name . '_end_break_time', 0) * 3600;
				for ($i = $start_time; $i <= $end_time; $i = $i + $slot_length) {
					if ($i < $start_time_break || $i >= $end_time_break) {
						$date_time=$start_date . ' ' . date_i18n('H:i', $i);
						$slice_time = self::slice_buffer_time( $date_time );
						if(strtotime($now_full)<strtotime($slice_time)){
							$all_slots[] = $date_time;
						}

					}
				}
				return $all_slots;
			}
			public static function slice_buffer_time( $date ) {
				$buffer_time = MPWPB_Global_Function::get_settings( 'mpwpb_general_settings', 'buffer_time', 0 ) * 60;
				if ( $buffer_time > 0 ) {
					$date = date_i18n( 'Y-m-d H:i', strtotime( $date ) - $buffer_time );
				}
				return $date;
			}
			//*************Price*********************************//
			public static function get_price($post_id, $service_id,$date = '') {
				$price = 0;
				if($service_id && $service_id>0 && $post_id){
					$all_category = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_service', array());
					if (sizeof($all_category) > 0) {
						foreach ($all_category as $cat_key => $category) {
							if($cat_key+1 == $service_id){
								$price= array_key_exists('price', $category) ? $category['price'] : 0;
							}
						}
					}
				}
				$price = MPWPB_Global_Function::wc_price($post_id, $price);
				$price = MPWPB_Global_Function::price_convert_raw($price);
				return apply_filters('mpwpb_price_filter', $price, $post_id, $service_id, $date);
			}
			public static function get_extra_price($post_id, $ex_service_types, $ex_service_category = '') {
				$ex_price = 0;
				$extra_services = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_extra_service', []);
				if (sizeof($extra_services) > 0) {
					foreach ($extra_services as $service_info) {
						$service_name = array_key_exists('name', $service_info) ? $service_info['name'] : '';
						if ($service_name && $service_name == $ex_service_types) {
							$ex_price = $ex_price + array_key_exists('price', $service_info) ? $service_info['price'] : 0;
						}
					}
				}
				$ex_price = MPWPB_Global_Function::wc_price($post_id, $ex_price);
				$ex_price = MPWPB_Global_Function::price_convert_raw($ex_price);
				return apply_filters('mpwpb_price_filter', $ex_price, $post_id, $ex_service_category, $ex_service_types);
			}
			//************* seat ******************//
			public static function get_total_available($post_id, $date) {
				$total = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_capacity_per_session', 1);
				$sold = MPWPB_Query::query_all_sold($post_id, $date)->post_count;
				$available = $total - $sold;
				return max(0, $available);
			}
		}
		new MPWPB_Function();
	}