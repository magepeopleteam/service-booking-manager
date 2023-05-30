<?php
	if (!defined('ABSPATH')) {
		die;
	} // Cannot access pages directly.
	if (!class_exists('MPWPB_Function')) {
		class MPWPB_Function {
			public function __construct() {
			}
			//*********Date and Time**********************//
			public static function date_picker_format(): string {
				$format = self::get_general_settings('date_format', 'D d M , yy');
				$date_format = 'Y-m-d';
				$date_format = $format == 'yy/mm/dd' ? 'Y/m/d' : $date_format;
				$date_format = $format == 'yy-dd-mm' ? 'Y-d-m' : $date_format;
				$date_format = $format == 'yy/dd/mm' ? 'Y/d/m' : $date_format;
				$date_format = $format == 'dd-mm-yy' ? 'd-m-Y' : $date_format;
				$date_format = $format == 'dd/mm/yy' ? 'd/m/Y' : $date_format;
				$date_format = $format == 'mm-dd-yy' ? 'm-d-Y' : $date_format;
				$date_format = $format == 'mm/dd/yy' ? 'm/d/Y' : $date_format;
				$date_format = $format == 'd M , yy' ? 'j M , Y' : $date_format;
				$date_format = $format == 'D d M , yy' ? 'D j M , Y' : $date_format;
				$date_format = $format == 'M d , yy' ? 'M  j, Y' : $date_format;
				return $format == 'D M d , yy' ? 'D M  j, Y' : $date_format;
			}
			
			//************************************************************Partially custom Function******************************//
			//***********Template********************//
			public static function all_details_template() {
				$template_path = get_stylesheet_directory() . '/mpwpb_templates/themes/';
				$default_path = MPWPB_PLUGIN_DIR . '/templates/themes/';
				$dir = is_dir($template_path) ? glob($template_path . "*") : glob($default_path . "*");
				$names = [];
				foreach ($dir as $filename) {
					if (is_file($filename)) {
						$file = basename($filename);
						$name = str_replace("?>", "", strip_tags(file_get_contents($filename, false, null, 24, 16)));
						$names[$file] = $name;
					}
				}
				$name = [];
				foreach ($names as $key => $value) {
					$name[$key] = $value;
				}
				return apply_filters('filter_mpwpb_details_template', $name);
			}
			public static function details_template_path($post_id = ''): string {
				$post_id = $post_id ?? get_the_id();
				$template_name = MP_Global_Function::get_post_info($post_id, 'mpwpb_theme_file', 'default.php');
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
				return MP_Global_Function::get_settings('mpwpb_general_settings', $key, $default);
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
				$text = MP_Global_Function::get_post_info($post_id, 'mpwpb_category_text');
				return $text ?: self::get_general_settings('category_text', esc_html__('Category', 'service-booking-manager'));
			}
			public static function get_sub_category_text($post_id) {
				$text = MP_Global_Function::get_post_info($post_id, 'mpwpb_sub_category_text');
				return $text ?: self::get_general_settings('sub_category_text', esc_html__('Sub-Category', 'service-booking-manager'));
			}
			public static function get_service_text($post_id) {
				$text = MP_Global_Function::get_post_info($post_id, 'mpwpb_service_text');
				return $text ?: self::get_general_settings('service_text', esc_html__('Service', 'service-booking-manager'));
			}
			//*******************************//
			public static function get_category($post_id, $all_services = []) {
				$categories = [];
				$all_services = $all_services ?: MP_Global_Function::get_post_info($post_id, 'mpwpb_category_infos', []);
				$category_active = MP_Global_Function::get_post_info($post_id, 'mpwpb_category_active', 'on');
				if ($category_active == 'on' && sizeof($all_services) > 0) {
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
				$category_active = MP_Global_Function::get_post_info($post_id, 'mpwpb_category_active', 'on');
				$sub_category_active = MP_Global_Function::get_post_info($post_id, 'mpwpb_sub_category_active', 'off');
				$all_services = $all_services ?: MP_Global_Function::get_post_info($post_id, 'mpwpb_category_infos', []);
				$count = 0;
				if (sizeof($all_services) > 0) {
					foreach ($all_services as $category_info) {
						$category_name = array_key_exists('category', $category_info) ? $category_info['category'] : '';
						$category_name = $category_active == 'on' ? $category_name : '';
						$sub_categories = array_key_exists('sub_category', $category_info) ? $category_info['sub_category'] : [];
						if ($category_name && sizeof($sub_categories) > 0) {
							foreach ($sub_categories as $sub_category) {
								$sub_category_name = array_key_exists('name', $sub_category) ? $sub_category['name'] : '';
								$sub_category_icon = array_key_exists('icon', $sub_category) ? $sub_category['icon'] : '';
								$sub_category_image = array_key_exists('image', $sub_category) ? $sub_category['image'] : '';
								$sub_category_name = $category_active == 'on' && $sub_category_active == 'on' ? $sub_category_name : '';
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
				$category_active = MP_Global_Function::get_post_info($post_id, 'mpwpb_category_active', 'on');
				$sub_category_active = MP_Global_Function::get_post_info($post_id, 'mpwpb_sub_category_active', 'off');
				$all_services = MP_Global_Function::get_post_info($post_id, 'mpwpb_category_infos', []);
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
										$all_service_item[$count]['category'] = $category_active == 'on' ? $category_name : '';
										$all_service_item[$count]['sub_category'] = $category_active == 'on' && $sub_category_active == 'on' ? $sub_category_name : '';
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
			public static function get_all_date($post_id) {
				$dates = [];
				$now = strtotime(current_time('Y-m-d'));
				$start_date = MP_Global_Function::get_post_info($post_id, 'mpwpb_service_start_date');
				$end_date = MP_Global_Function::get_post_info($post_id, 'mpwpb_service_end_date');
				$all_dates = MP_Global_Function::date_separate_period($start_date, $end_date);
				$all_off_dates = MP_Global_Function::get_post_info($post_id, 'mpwpb_off_dates', []);
				$all_off_days = MP_Global_Function::get_post_info($post_id, 'mpwpb_off_days');
				$all_off_days = explode(',', $all_off_days);
				$off_dates = [];
				foreach ($all_off_dates as $off_date) {
					$off_dates[] = date('Y-m-d', strtotime($off_date));
				}
				foreach ($all_dates as $date) {
					$date = $date->format('Y-m-d');
					if ($now <= strtotime($date)) {
						$day = strtolower(date('l', strtotime($date)));
						if (!in_array($date, $off_dates) && !in_array($day, $all_off_days)) {
							$dates[] = $date;
						}
					}
				}
				return apply_filters('mpwpb_get_date', $dates, $post_id);
			}
			public static function get_time_slot($post_id, $start_date) {
				$all_slots = [];
				$slot_length = MP_Global_Function::get_post_info($post_id, 'mpwpb_time_slot_length', 30);
				$slot_length = $slot_length * 60;
				$day_name = strtolower(date('l', strtotime($start_date)));
				$start_time = MP_Global_Function::get_post_info($post_id, 'mpwpb_' . $day_name . '_start_time');
				if (!$start_time) {
					$day_name = 'default';
					$start_time = MP_Global_Function::get_post_info($post_id, 'mpwpb_' . $day_name . '_start_time');
				}
				$start_time = $start_time * 3600;
				$end_time = MP_Global_Function::get_post_info($post_id, 'mpwpb_' . $day_name . '_end_time') * 3600;
				$start_time_break = MP_Global_Function::get_post_info($post_id, 'mpwpb_' . $day_name . '_start_break_time',0) * 3600;
				$end_time_break = MP_Global_Function::get_post_info($post_id, 'mpwpb_' . $day_name . '_end_break_time',0) * 3600;
				for ($i = $start_time; $i <= $end_time; $i = $i + $slot_length) {
					if ($i < $start_time_break || $i >= $end_time_break) {
						$all_slots[] = $start_date . ' ' . date('H:i', $i);
					}
				}
				return $all_slots;
			}
			//*************Price*********************************//
			public static function get_price($post_id, $service_name, $category_name = '', $sub_category_name = '', $date = '') {
				$all_service = MP_Global_Function::get_post_info($post_id, 'mpwpb_category_infos', []);
				$price = 0;
				if (sizeof($all_service) > 0) {
					foreach ($all_service as $categories) {
						$current_category_name = array_key_exists('category', $categories) ? $categories['category'] : '';
						if (($current_category_name && $category_name && $current_category_name == $category_name) || (!$current_category_name && !$category_name)) {
							$sub_categories = array_key_exists('sub_category', $categories) ? $categories['sub_category'] : [];
							if (sizeof($sub_categories) > 0) {
								foreach ($sub_categories as $sub_category) {
									$current_sub_category_name = array_key_exists('name', $sub_category) ? $sub_category['name'] : '';
									if (($current_sub_category_name && $sub_category_name && $current_sub_category_name == $sub_category_name) || (!$current_sub_category_name && !$sub_category_name)) {
										$service_infos = array_key_exists('service', $sub_category) ? $sub_category['service'] : [];
										if (sizeof($service_infos) > 0) {
											foreach ($service_infos as $service_info) {
												$current_service_name = array_key_exists('name', $service_info) ? $service_info['name'] : '';
												if ($current_service_name == $service_name) {
													$price = array_key_exists('price', $service_info) ? $service_info['price'] : 0;
												}
											}
										}
									}
								}
							}
						}
					}
				}
				$price = MP_Global_Function::wc_price($post_id, $price);
				$price = MP_Global_Function::price_convert_raw($price);
				return apply_filters('mpwpb_price_filter', $price, $post_id, $category_name, $service_name, $date);
			}
			public static function get_extra_price($post_id, $ex_service_types, $ex_service_category = '') {
				$ex_price = 0;
				$extra_services = MP_Global_Function::get_post_info($post_id, 'mpwpb_extra_service', []);
				if (sizeof($extra_services) > 0) {
					foreach ($extra_services as $group_service) {
						$group_service_name = array_key_exists('group_service', $group_service) ? $group_service['group_service'] : '';
						if (($group_service_name && $ex_service_category && $group_service_name == $ex_service_category) || (!$group_service_name && !$ex_service_category)) {
							$service_infos = array_key_exists('group_service_info', $group_service) ? $group_service['group_service_info'] : [];
							if (sizeof($service_infos) > 0) {
								foreach ($service_infos as $service_info) {
									$service_name = array_key_exists('name', $service_info) ? $service_info['name'] : '';
									if ($service_name && $service_name == $ex_service_types) {
										$ex_price = $ex_price + array_key_exists('price', $service_info) ? $service_info['price'] : 0;
									}
								}
							}
						}
					}
				}
				$ex_price = MP_Global_Function::wc_price($post_id, $ex_price);
				$ex_price = MP_Global_Function::price_convert_raw($ex_price);
				return apply_filters('mpwpb_price_filter', $ex_price, $post_id, $ex_service_category, $ex_service_types);
			}
			//************* seat ******************//
			public static function get_total_available($post_id, $date) {
				$total = MP_Global_Function::get_post_info($post_id, 'mpwpb_capacity_per_session', 1);
				$sold = MPWPB_Query::query_all_sold($post_id, $date)->post_count;
				$available = $total - $sold;
				return max(0, $available);
			}
		}
		new MPWPB_Function();
	}