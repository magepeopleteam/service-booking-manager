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
			public static function sanitize_details_template_name($template_name): string {
				$allowed_templates = array('default.php', 'static.php');
				$template_name = sanitize_file_name((string)$template_name);
				$template_name = basename($template_name);
				if (!in_array($template_name, $allowed_templates, true)) {
					return 'static.php';
				}
				return $template_name;
			}
			private static function normalize_template_relative_path($file_name): string {
				$file_name = wp_normalize_path(ltrim((string)$file_name, '/\\'));
				if ($file_name === '' || strpos($file_name, '..') !== false) {
					return '';
				}
				return preg_replace('#/+#', '/', $file_name);
			}
			public static function details_template_path($post_id = ''): string {
				$post_id = $post_id ?? get_the_id();
				$template_name = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_template', 'static.php');
				$template_name = self::sanitize_details_template_name($template_name);
				$file_name = 'themes/' . $template_name;
				$dir = MPWPB_PLUGIN_DIR . '/templates/' . $file_name;
				if (!file_exists($dir)) {
					$file_name = 'themes/default.php';
				}
				return self::template_path($file_name);
			}
			public static function template_path($file_name): string {
				$default_dir = trailingslashit(MPWPB_PLUGIN_DIR . '/templates');
				$file_name = self::normalize_template_relative_path($file_name);
				if (!$file_name) {
					return $default_dir . 'themes/default.php';
				}
				$default_file_path = $default_dir . $file_name;
				if (!file_exists($default_file_path)) {
					return $default_dir . 'themes/default.php';
				}
				$theme_file = locate_template(array('mpwpb_templates/' . $file_name));
				if ($theme_file) {
					$resolved_theme_file = realpath($theme_file);
					$allowed_theme_dirs = array(
						realpath(get_stylesheet_directory() . '/mpwpb_templates'),
						realpath(get_template_directory() . '/mpwpb_templates'),
					);
					if ($resolved_theme_file) {
						$resolved_theme_file = wp_normalize_path($resolved_theme_file);
						foreach ($allowed_theme_dirs as $allowed_theme_dir) {
							if (!$allowed_theme_dir) {
								continue;
							}
							$allowed_theme_dir = trailingslashit(wp_normalize_path($allowed_theme_dir));
							if (strpos($resolved_theme_file, $allowed_theme_dir) === 0) {
								return $resolved_theme_file;
							}
						}
					}
				}
				return $default_file_path;
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
				$all_off_days = array_map('strtolower', array_map('trim', explode(',', $off_days)));
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
						
						// Get English day name regardless of locale
						$day_num = date('w', strtotime($date)); // 0=Sunday, 1=Monday, etc.
						$english_days = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
						$day = $english_days[$day_num];
						
						// Check if date should be excluded (either off date OR off day)
						$is_off_date = in_array($date, $off_dates);
						$is_off_day = in_array($day, $all_off_days);
						
						if (!$is_off_date && !$is_off_day) {
							$all_dates[] = $date;
						}
					}
				}
				else {
					$particular_date_lists = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_particular_dates', array());
					if (sizeof($particular_date_lists)) {
						foreach ($particular_date_lists as $particular_date) {
							// Get English day name regardless of locale
							$day_num = date('w', strtotime($particular_date)); // 0=Sunday, 1=Monday, etc.
							$english_days = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
							$day = $english_days[$day_num];
							
							if ($particular_date && ($expire || strtotime($now) <= strtotime($particular_date)) && !in_array($particular_date, $off_dates) && !in_array($day, $all_off_days)) {
								$all_dates[] = $particular_date;
							}
						}
					}
				}
				return apply_filters('mpwpb_get_date', $all_dates, $post_id);
			}
			/**
			 * Quick-pick slot lengths (minutes => label) offered in the editor.
			 * Purely a convenience list -- admins can enter any custom length,
			 * so nothing downstream may treat these as the allowed set.
			 */
			public static function slot_length_presets(): array {
				return apply_filters('mpwpb_slot_length_presets', array(
					10 => __('10 min', 'service-booking-manager'),
					15 => __('15 min', 'service-booking-manager'),
					30 => __('30 min', 'service-booking-manager'),
					45 => __('45 min', 'service-booking-manager'),
					60 => __('1 Hour', 'service-booking-manager'),
					90 => __('1h 30m', 'service-booking-manager'),
					120 => __('2 Hours', 'service-booking-manager'),
					180 => __('3 Hours', 'service-booking-manager'),
				));
			}

			/**
			 * Booking slot length (in minutes) configured for a service.
			 *
			 * The editor offers presets plus a free "Custom" value, so this can be
			 * any positive number of minutes -- never assume one of the old fixed
			 * options. Clamped to >= 1 so slot maths can never step by zero.
			 */
			public static function get_slot_length($post_id, $fallback = 30) {
				return max(1, (int) MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_time_slot_length', $fallback));
			}

			/**
			 * Slot length recorded ON a booking, falling back to the service's
			 * current setting for bookings created before the length was stored
			 * per booking (so existing orders still render a duration).
			 */
			public static function get_booking_slot_length($booking_id, $post_id = 0) {
				$stored = (int) get_post_meta($booking_id, 'mpwpb_slot_length', true);
				if ($stored > 0) {
					return $stored;
				}
				$post_id = $post_id ?: (int) get_post_meta($booking_id, 'mpwpb_id', true);
				return $post_id ? self::get_slot_length($post_id) : 0;
			}

			/**
			 * Human label for a slot length -- "45 min", "1 Hour", "2 Hours", "1h 30m".
			 */
			public static function format_slot_duration($minutes) {
				$minutes = (int) $minutes;
				if ($minutes < 1) {
					return '';
				}
				$hours = intdiv($minutes, 60);
				$mins = $minutes % 60;
				if ($hours > 0 && $mins > 0) {
					/* translators: 1: whole hours, 2: leftover minutes */
					return sprintf(__('%1$dh %2$dm', 'service-booking-manager'), $hours, $mins);
				}
				if ($hours > 0) {
					return $hours === 1
						? __('1 Hour', 'service-booking-manager')
						/* translators: %d: number of hours */
						: sprintf(__('%d Hours', 'service-booking-manager'), $hours);
				}
				/* translators: %d: number of minutes */
				return sprintf(__('%d min', 'service-booking-manager'), $mins);
			}

			/**
			 * Best-effort parse of a free-text duration into whole minutes.
			 * Understands "45", "30m", "30 min", "2h", "2 hours", "1h 30m".
			 * Returns 0 when nothing recognisable is found.
			 */
			public static function parse_duration_to_minutes($duration) {
				$duration = strtolower(trim((string) $duration));
				if ($duration === '') {
					return 0;
				}
				$minutes = 0;
				$matched = false;
				if (preg_match('/(\d+(?:\.\d+)?)\s*(?:h|hr|hrs|hour|hours)\b/', $duration, $m)) {
					$minutes += (float) $m[1] * 60;
					$matched = true;
				}
				if (preg_match('/(\d+(?:\.\d+)?)\s*(?:m|min|mins|minute|minutes)\b/', $duration, $m)) {
					$minutes += (float) $m[1];
					$matched = true;
				}
				// A bare number is taken as minutes ("45").
				if (!$matched && preg_match('/^(\d+(?:\.\d+)?)$/', $duration, $m)) {
					$minutes = (float) $m[1];
					$matched = true;
				}
				return $matched ? (int) round($minutes) : 0;
			}

			/**
			 * Display label for an admin-entered service duration.
			 *
			 * That field is free text, so values are normalised to minutes and
			 * re-rendered through format_slot_duration() -- "240m" reads as
			 * "4 Hours" instead of "240m". Anything not recognisable as a
			 * duration (e.g. "Half day") is returned untouched so custom wording
			 * is never mangled.
			 */
			public static function format_duration_text($duration) {
				$duration = trim((string) $duration);
				if ($duration === '') {
					return '';
				}
				$minutes = self::parse_duration_to_minutes($duration);
				return $minutes > 0 ? self::format_slot_duration($minutes) : $duration;
			}

			/**
			 * "10:00 am - 11:00 am" for a slot, using the site's configured time
			 * format (including the plugin's 24-hour option). Falls back to just
			 * the start time when no usable length is known, so callers can use
			 * this unconditionally.
			 */
			public static function format_slot_time_range($start, $minutes) {
				$timestamp = $start ? strtotime($start) : false;
				if (!$timestamp) {
					return '';
				}
				$start_label = MPWPB_Global_Function::date_format($start, 'time');
				$minutes = (int) $minutes;
				if ($minutes < 1) {
					return $start_label;
				}
				$end = date_i18n('Y-m-d H:i', $timestamp + ($minutes * MINUTE_IN_SECONDS));
				return $start_label . ' - ' . MPWPB_Global_Function::date_format($end, 'time');
			}

			public static function get_time_slot($post_id, $start_date) {
				$now_full = current_time( 'Y-m-d H:i' );

				$all_slots = [];
				$slot_length = self::get_slot_length($post_id) * 60;
				// Get English day name regardless of locale
				$day_num = date('w', strtotime($start_date)); // 0=Sunday, 1=Monday, etc.
				$english_days = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
				$day_name = $english_days[$day_num];
				
				
				$default_start = (float) MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_default_start_time', 10);
				$default_end = (float) MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_default_end_time', 18);
				$default_break_start = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_default_start_break_time', '');
				$default_break_end = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_default_end_break_time', '');
				$day_start = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_' . $day_name . '_start_time', '');
				$day_end = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_' . $day_name . '_end_time', '');
				$day_break_start = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_' . $day_name . '_start_break_time', '');
				$day_break_end = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_' . $day_name . '_end_break_time', '');

				$start_time = (float) ($day_start === '' ? $default_start : $day_start) * HOUR_IN_SECONDS;
				$end_time = (float) ($day_end === '' ? $default_end : $day_end) * HOUR_IN_SECONDS;
				$break_start_value = $day_break_start === '' ? $default_break_start : $day_break_start;
				$break_end_value = $day_break_end === '' ? $default_break_end : $day_break_end;
				$start_time_break = $break_start_value === '' ? 0 : (float) $break_start_value * HOUR_IN_SECONDS;
				$end_time_break = $break_end_value === '' ? 0 : (float) $break_end_value * HOUR_IN_SECONDS;
				$has_break = $start_time_break > 0 && $end_time_break > $start_time_break;

				if ($end_time <= $start_time) {
					return array();
				}
				for ($i = $start_time; $i + $slot_length <= $end_time; $i += $slot_length) {
					$slot_end = $i + $slot_length;
					$overlaps_break = $has_break && $i < $end_time_break && $slot_end > $start_time_break;
					if (!$overlaps_break) {
						$hours = floor($i / HOUR_IN_SECONDS);
						$minutes = floor(($i % HOUR_IN_SECONDS) / MINUTE_IN_SECONDS);
						$date_time = $start_date . ' ' . sprintf('%02d:%02d', $hours, $minutes);
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
			
			/**
			 * Format time based on 24-hour setting
			 *
			 * @param string $time Time in H:i format
			 * @return string Formatted time
			 */
			public static function format_time($time) {
				$use_24hour = MPWPB_Global_Function::get_settings('mpwpb_global_settings', 'time_format_24hour', 'no');
				
				if ($use_24hour === 'yes') {
					// Already in 24-hour format, return as is
					return $time;
				} else {
					// Convert to 12-hour format
					$time_obj = date_create($time);
					return date_format($time_obj, 'g:i A');
				}
			}
		}
		new MPWPB_Function();
	}
