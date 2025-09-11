<?php
	/*
* @Author 		engr.sumonazma@gmail.com
* Copyright: 	mage-people.com
*/
	if (!defined('ABSPATH')) {
		die;
	} // Cannot access pages directly.
	if (!class_exists('MPWPB_Global_Function')) {
		class MPWPB_Global_Function {
			public function __construct() {
				add_action('mpwpb_load_date_picker_js', [$this, 'date_picker_js'], 10, 2);
			}
			public static function query_post_type($post_type, $show = -1, $page = 1): WP_Query {
				$args = array(
					'post_type' => $post_type,
					'posts_per_page' => $show,
					'paged' => $page,
					'post_status' => 'publish'
				);
				return new WP_Query($args);
			}
			public static function get_all_post_id($post_type, $show = -1, $page = 1, $status = 'publish'): array {
				$all_data = get_posts(array(
					'fields' => 'ids',
					'post_type' => $post_type,
					'posts_per_page' => $show,
					'paged' => $page,
					'post_status' => $status
				));
				return array_unique($all_data);
			}
			public static function get_post_info($post_id, $key, $default = '') {
				$data = get_post_meta($post_id, $key, true) ?: $default;
				return self::data_sanitize($data);
			}
			//***********************************//
			public static function get_taxonomy($name) {
				return get_terms(array('taxonomy' => $name, 'hide_empty' => false));
			}
			public static function get_term_meta($meta_id, $meta_key, $default = '') {
				$data = get_term_meta($meta_id, $meta_key, true) ?: $default;
				return self::data_sanitize($data);
			}
			public static function get_all_term_data($term_name, $value = 'name') {
				$all_data = [];
				$taxonomies = self::get_taxonomy($term_name);
				if ($taxonomies && is_array($taxonomies) && sizeof($taxonomies) > 0) {
					foreach ($taxonomies as $taxonomy) {
						$all_data[] = $taxonomy->$value;
					}
				}
				return $all_data;
			}
			//***********************************//
			public static function data_sanitize( $array ) {
				if ( ! is_array( $array ) ) {
					return sanitize_text_field( $array );
				}

				foreach ( $array as $key => $value ) {
					if ( is_array( $value ) ) {
						$array[ $key ] = self::data_sanitize( $value );
					} else {
						if ( filter_var( $value, FILTER_VALIDATE_EMAIL ) ) {
							$array[ $key ] = sanitize_email( $value );
						} elseif ( filter_var( $value, FILTER_VALIDATE_URL ) ) {
							$array[ $key ] = esc_url_raw( $value );
						} else {
							$array[ $key ] = sanitize_text_field( $value );
						}
					}
				}

				return $array;
			}
			//**************Date related*********************//
			public static function date_picker_format_without_year($key = 'date_format'): string {
				$format = self::get_settings('mpwpb_global_settings', $key, 'D d M , yy');
				$date_format = 'm-d';
				$date_format = $format == 'yy/mm/dd' ? 'm/d' : $date_format;
				$date_format = $format == 'yy-dd-mm' ? 'd-m' : $date_format;
				$date_format = $format == 'yy/dd/mm' ? 'd/m' : $date_format;
				$date_format = $format == 'dd-mm-yy' ? 'd-m' : $date_format;
				$date_format = $format == 'dd/mm/yy' ? 'd/m' : $date_format;
				$date_format = $format == 'mm-dd-yy' ? 'm-d' : $date_format;
				$date_format = $format == 'mm/dd/yy' ? 'm/d' : $date_format;
				$date_format = $format == 'd M , yy' ? 'j M' : $date_format;
				$date_format = $format == 'D d M , yy' ? 'D j M' : $date_format;
				$date_format = $format == 'M d , yy' ? 'M  j' : $date_format;
				return $format == 'D M d , yy' ? 'D M  j' : $date_format;
			}
			public static function date_picker_format($key = 'date_format'): string {
				$format = self::get_settings('mpwpb_global_settings', $key, 'D d M , yy');
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
			public function date_picker_js($selector, $dates) {
				$start_date = $dates[0];
				$start_year = date_i18n('Y', strtotime($start_date));
				$start_month = (date_i18n('n', strtotime($start_date)) - 1);
				$start_day = date_i18n('j', strtotime($start_date));
				$end_date = end($dates);
				$end_year = date_i18n('Y', strtotime($end_date));
				$end_month = (date_i18n('n', strtotime($end_date)) - 1);
				$end_day = date_i18n('j', strtotime($end_date));
				$all_date = [];
				foreach ($dates as $date) {
					$all_date[] = '"' . date_i18n('j-n-Y', strtotime($date)) . '"';
				}
				?>
                <script>
                    jQuery(document).ready(function () {
                        jQuery("<?php echo esc_attr($selector); ?>").datepicker({
                            dateFormat: mpwpb_date_format,
                            minDate: new Date(<?php echo esc_attr($start_year); ?>, <?php echo esc_attr($start_month); ?>, <?php echo esc_attr($start_day); ?>),
                            maxDate: new Date(<?php echo esc_attr($end_year); ?>, <?php echo esc_attr($end_month); ?>, <?php echo esc_attr($end_day); ?>),
                            autoSize: true,
                            changeMonth: true,
                            changeYear: true,
                            beforeShowDay: WorkingDates,
                            onSelect: function (dateString, data) {
                                let date = data.selectedYear + '-' + ('0' + (parseInt(data.selectedMonth) + 1)).slice(-2) + '-' + ('0' + parseInt(data.selectedDay)).slice(-2);
                                jQuery(this).closest('label').find('input[type="hidden"]').val(date).trigger('change');
                            }
                        });
                        function WorkingDates(date) {
                            let availableDates = [<?php echo esc_attr(implode(',', $all_date)); ?>];
                            let dmy = date.getDate() + "-" + (date.getMonth() + 1) + "-" + date.getFullYear();
                            if (jQuery.inArray(dmy, availableDates) !== -1) {
                                return [true, "", "Available"];
                            } else {
                                return [false, "", "unAvailable"];
                            }
                        }
                    });
                </script>
				<?php
			}
			public static function date_format($date, $format = 'date') {
				$date_format = get_option('date_format');
				$time_format = get_option('time_format');
				
				// Check if 24-hour format is enabled
				$use_24hour = self::get_settings('mpwpb_global_settings', 'time_format_24hour', 'no');
				if ($use_24hour === 'yes') {
					$time_format = 'H:i'; // Force 24-hour format
				}
				
				$wp_settings = $date_format . '  ' . $time_format;
				//$timezone = wp_timezone_string();
				$timestamp = strtotime($date);
				if ($format == 'date') {
					$date = date_i18n($date_format, $timestamp);
				} elseif ($format == 'time') {
					$date = date_i18n($time_format, $timestamp);
				} elseif ($format == 'full') {
					$date = date_i18n($wp_settings, $timestamp);
				} elseif ($format == 'day') {
					$date = date_i18n('d', $timestamp);
				} elseif ($format == 'month') {
					$date = date_i18n('M', $timestamp);
				} elseif ($format == 'year') {
					$date = date_i18n('Y', $timestamp);
				} else {
					$date = date_i18n($format, $timestamp);
				}
				return $date;
			}
			public static function date_separate_period($start_date, $end_date, $repeat = 1): DatePeriod {
				$repeat = max($repeat, 1);
				$_interval = "P" . $repeat . "D";
				$end_date = date_i18n('Y-m-d', strtotime($end_date . ' +1 day'));
				return new DatePeriod(new DateTime($start_date), new DateInterval($_interval), new DateTime($end_date));
			}
			public static function check_time_exit_date($date) {
				if ($date) {
					$parse_date = date_parse($date);
					if (($parse_date['hour'] && $parse_date['hour'] > 0) || ($parse_date['minute'] && $parse_date['minute'] > 0) || ($parse_date['second'] && $parse_date['second'] > 0)) {
						return true;
					}
				}
				return false;
			}
			public static function sort_date($a, $b) {
				return strtotime($a) - strtotime($b);
			}
			public static function sort_date_array($a, $b) {
				$dateA = strtotime($a['time']);
				$dateB = strtotime($b['time']);
				if ($dateA == $dateB) {
					return 0;
				} elseif ($dateA > $dateB) {
					return 1;
				} else {
					return -1;
				}
			}
			//***********************************//
			public static function get_settings($section, $key, $default = '') {
				$options = get_option($section);
				if (isset($options[$key]) && $options[$key]) {
					$default = $options[$key];
				}
				return $default;
			}
			public static function get_style_settings($key, $default = '') {
				return self::get_settings('mpwpb_style_settings', $key, $default);
			}
			public static function get_slider_settings($key, $default = '') {
				return self::get_settings('mpwpb_slider_settings', $key, $default);
			}
			public static function get_licence_settings($key, $default = '') {
				return self::get_settings('mpwpb_license_settings', $key, $default);
			}
			//***********************************//
			public static function price_convert_raw($price) {
				$display_suffix = get_option('woocommerce_price_display_suffix') ? get_option('woocommerce_price_display_suffix') : '';
				$price = wp_strip_all_tags($price);
				$price = str_replace(get_woocommerce_currency_symbol(), '', $price);
				$price = str_replace($display_suffix, '', $price);
				$price = str_replace(wc_get_price_thousand_separator(), 't_s', $price);
				$price = str_replace(wc_get_price_decimal_separator(), 'd_s', $price);
				$price = str_replace('t_s', '', $price);
				$price = str_replace('d_s', '.', $price);
				$price = str_replace('&nbsp;', '', $price);
				return max($price, 0);
			}
			public static function wc_price($post_id, $price, $args = array()): string {
				$num_of_decimal = get_option('woocommerce_price_num_decimals', 2);
				$args = wp_parse_args($args, array(
					'qty' => '',
					'price' => '',
				));
				$_product = self::get_post_info($post_id, 'link_wc_product', $post_id);
				$product = wc_get_product($_product);
				$qty = '' !== $args['qty'] ? max(0.0, (float)$args['qty']) : 1;
				$tax_with_price = get_option('woocommerce_tax_display_shop');
				if ('' === $price) {
					return '';
				} elseif (empty($qty)) {
					return 0.0;
				}
				$line_price = (float)$price * (int)$qty;
				$return_price = $line_price;
				if ($product && $product->is_taxable()) {
					if (!wc_prices_include_tax()) {
						$tax_rates = WC_Tax::get_rates($product->get_tax_class());
						$taxes = WC_Tax::calc_tax($line_price, $tax_rates);
						if ('yes' === get_option('woocommerce_tax_round_at_subtotal')) {
							$taxes_total = array_sum($taxes);
						} else {
							$taxes_total = array_sum(array_map('wc_round_tax_total', $taxes));
						}
						$return_price = $tax_with_price == 'excl' ? round($line_price, $num_of_decimal) : round($line_price + $taxes_total, $num_of_decimal);
					} else {
						$tax_rates = WC_Tax::get_rates($product->get_tax_class());
						$base_tax_rates = WC_Tax::get_base_tax_rates($product->get_tax_class('unfiltered'));
						if (!empty(WC()->customer) && WC()->customer->get_is_vat_exempt()) { // @codingStandardsIgnoreLine.
							$remove_taxes = apply_filters('woocommerce_adjust_non_base_location_prices', true) ? WC_Tax::calc_tax($line_price, $base_tax_rates, true) : WC_Tax::calc_tax($line_price, $tax_rates, true);
							if ('yes' === get_option('woocommerce_tax_round_at_subtotal')) {
								$remove_taxes_total = array_sum($remove_taxes);
							} else {
								$remove_taxes_total = array_sum(array_map('wc_round_tax_total', $remove_taxes));
							}
							// $return_price = round( $line_price, $num_of_decimal);
							$return_price = round($line_price - $remove_taxes_total, $num_of_decimal);
						} else {
							$base_taxes = WC_Tax::calc_tax($line_price, $base_tax_rates, true);
							$modded_taxes = WC_Tax::calc_tax($line_price - array_sum($base_taxes), $tax_rates);
							if ('yes' === get_option('woocommerce_tax_round_at_subtotal')) {
								$base_taxes_total = array_sum($base_taxes);
								$modded_taxes_total = array_sum($modded_taxes);
							} else {
								$base_taxes_total = array_sum(array_map('wc_round_tax_total', $base_taxes));
								$modded_taxes_total = array_sum(array_map('wc_round_tax_total', $modded_taxes));
							}
							$return_price = $tax_with_price == 'excl' ? round($line_price - $base_taxes_total, $num_of_decimal) : round($line_price - $base_taxes_total + $modded_taxes_total, $num_of_decimal);
						}
					}
				}
				$return_price = apply_filters('woocommerce_get_price_including_tax', $return_price, $qty, $product);
				$display_suffix = get_option('woocommerce_price_display_suffix') ? get_option('woocommerce_price_display_suffix') : '';
				return wc_price($return_price) . ' ' . $display_suffix;
			}
			public static function get_wc_raw_price($post_id, $price, $args = array()) {
				$price = self::wc_price($post_id, $price, $args = array());
				return self::price_convert_raw($price);
			}
			//***********************************//
			public static function get_image_url($post_id = '', $image_id = '', $size = 'full') {
				if ($post_id) {
					$image_id = get_post_thumbnail_id($post_id);
					$image_id = $image_id ?: self::get_post_info($post_id, 'mp_thumbnail');
				}
				return wp_get_attachment_image_url($image_id, $size);
			}
			public static function get_page_by_slug($slug) {
				if ($pages = get_pages()) {
					foreach ($pages as $page) {
						if ($slug === $page->post_name) {
							return $page;
						}
					}
				}
				return false;
			}
			//***********************************//
			public static function check_plugin($plugin_dir_name, $plugin_file): int {
				include_once ABSPATH . 'wp-admin/includes/plugin.php';
				$plugin_dir = ABSPATH . 'wp-content/plugins/' . $plugin_dir_name;
				if (is_plugin_active($plugin_dir_name . '/' . $plugin_file)) {
					return 1;
				} elseif (is_dir($plugin_dir)) {
					return 2;
				} else {
					return 0;
				}
			}
			public static function check_woocommerce(): int {
				include_once(ABSPATH . 'wp-admin/includes/plugin.php');
				$plugin_dir = ABSPATH . 'wp-content/plugins/woocommerce';
				if (is_plugin_active('woocommerce/woocommerce.php')) {
					return 1;
				} elseif (is_dir($plugin_dir)) {
					return 2;
				} else {
					return 0;
				}
			}
			public static function check_product_in_cart($post_id) {
				$status = self::check_woocommerce();
				if ($status == 1) {
					$product_id = self::get_post_info($post_id, 'link_wc_product');
					foreach (WC()->cart->get_cart() as $cart_item) {
						if ($cart_item['product_id'] == $product_id) {
							return true;
						}
					}
				}
				return false;
			}
			public static function wc_product_sku($product_id) {
				if ($product_id) {
					return new WC_Product($product_id);
				}
				return null;
			}
			//***********************************//
			public static function week_day(): array {
				return [
					'monday' => esc_html__('Monday', 'service-booking-manager'),
					'tuesday' => esc_html__('Tuesday', 'service-booking-manager'),
					'wednesday' => esc_html__('Wednesday', 'service-booking-manager'),
					'thursday' => esc_html__('Thursday', 'service-booking-manager'),
					'friday' => esc_html__('Friday', 'service-booking-manager'),
					'saturday' => esc_html__('Saturday', 'service-booking-manager'),
					'sunday' => esc_html__('Sunday', 'service-booking-manager'),
				];
			}
			public static function get_plugin_data($data) {
				$plugin_data = get_plugin_data(__FILE__);
				return $plugin_data[$data];
			}
			public static function array_to_string($array) {
				$ids = '';
				if (sizeof($array) > 0) {
					foreach ($array as $data) {
						if ($data) {
							$ids = $ids ? $ids . ',' . $data : $data;
						}
					}
				}
				return $ids;
			}
		}
		new MPWPB_Global_Function();
	}
	if (class_exists('MPWPB_Plugin_Pro') && get_option('mpwpb_conflict_update_pro') != 'completed') {
		if (!class_exists('MP_Global_Function')) {
			class MP_Global_Function {
				public static function get_post_info($post_id, $key, $default = '') {
					$data = get_post_meta($post_id, $key, true) ?: $default;
					return self::data_sanitize($data);
				}
				public static function data_sanitize($data) {
					$data = maybe_unserialize($data);
					if (is_string($data)) {
						$data = maybe_unserialize($data);
						if (is_array($data)) {
							$data = self::data_sanitize($data);
						} else {
							$data = sanitize_text_field(stripslashes(wp_strip_all_tags($data)));
						}
					} elseif (is_array($data)) {
						foreach ($data as &$value) {
							if (is_array($value)) {
								$value = self::data_sanitize($value);
							} else {
								$value = sanitize_text_field(stripslashes(wp_strip_all_tags($value)));
							}
						}
					}
					return $data;
				}
				public static function date_picker_format($key = 'date_format'): string {
					$format = self::get_settings('mpwpb_global_settings', $key, 'D d M , yy');
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
				public static function date_format($date, $format = 'date') {
					$date_format = get_option('date_format');
					$time_format = get_option('time_format');
					
					// Check if 24-hour format is enabled
					$use_24hour = self::get_settings('mpwpb_global_settings', 'time_format_24hour', 'no');
					if ($use_24hour === 'yes') {
						$time_format = 'H:i'; // Force 24-hour format
					}
					
					$wp_settings = $date_format . '  ' . $time_format;
					//$timezone = wp_timezone_string();
					$timestamp = strtotime($date);
					if ($format == 'date') {
						$date = date_i18n($date_format, $timestamp);
					} elseif ($format == 'time') {
						$date = date_i18n($time_format, $timestamp);
					} elseif ($format == 'full') {
						$date = date_i18n($wp_settings, $timestamp);
					} elseif ($format == 'day') {
						$date = date_i18n('d', $timestamp);
					} elseif ($format == 'month') {
						$date = date_i18n('M', $timestamp);
					} elseif ($format == 'year') {
						$date = date_i18n('Y', $timestamp);
					} else {
						$date = date_i18n($format, $timestamp);
					}
					return $date;
				}
				public static function get_settings($section, $key, $default = '') {
					$options = get_option($section);
					if (isset($options[$key]) && $options[$key]) {
						$default = $options[$key];
					}
					return $default;
				}
				public static function wc_price($post_id, $price, $args = array()): string {
					$num_of_decimal = get_option('woocommerce_price_num_decimals', 2);
					$args = wp_parse_args($args, array(
						'qty' => '',
						'price' => '',
					));
					$_product = self::get_post_info($post_id, 'link_wc_product', $post_id);
					$product = wc_get_product($_product);
					$qty = '' !== $args['qty'] ? max(0.0, (float)$args['qty']) : 1;
					$tax_with_price = get_option('woocommerce_tax_display_shop');
					if ('' === $price) {
						return '';
					} elseif (empty($qty)) {
						return 0.0;
					}
					$line_price = (float)$price * (int)$qty;
					$return_price = $line_price;
					if ($product && $product->is_taxable()) {
						if (!wc_prices_include_tax()) {
							$tax_rates = WC_Tax::get_rates($product->get_tax_class());
							$taxes = WC_Tax::calc_tax($line_price, $tax_rates);
							if ('yes' === get_option('woocommerce_tax_round_at_subtotal')) {
								$taxes_total = array_sum($taxes);
							} else {
								$taxes_total = array_sum(array_map('wc_round_tax_total', $taxes));
							}
							$return_price = $tax_with_price == 'excl' ? round($line_price, $num_of_decimal) : round($line_price + $taxes_total, $num_of_decimal);
						} else {
							$tax_rates = WC_Tax::get_rates($product->get_tax_class());
							$base_tax_rates = WC_Tax::get_base_tax_rates($product->get_tax_class('unfiltered'));
							if (!empty(WC()->customer) && WC()->customer->get_is_vat_exempt()) { // @codingStandardsIgnoreLine.
								$remove_taxes = apply_filters('woocommerce_adjust_non_base_location_prices', true) ? WC_Tax::calc_tax($line_price, $base_tax_rates, true) : WC_Tax::calc_tax($line_price, $tax_rates, true);
								if ('yes' === get_option('woocommerce_tax_round_at_subtotal')) {
									$remove_taxes_total = array_sum($remove_taxes);
								} else {
									$remove_taxes_total = array_sum(array_map('wc_round_tax_total', $remove_taxes));
								}
								// $return_price = round( $line_price, $num_of_decimal);
								$return_price = round($line_price - $remove_taxes_total, $num_of_decimal);
							} else {
								$base_taxes = WC_Tax::calc_tax($line_price, $base_tax_rates, true);
								$modded_taxes = WC_Tax::calc_tax($line_price - array_sum($base_taxes), $tax_rates);
								if ('yes' === get_option('woocommerce_tax_round_at_subtotal')) {
									$base_taxes_total = array_sum($base_taxes);
									$modded_taxes_total = array_sum($modded_taxes);
								} else {
									$base_taxes_total = array_sum(array_map('wc_round_tax_total', $base_taxes));
									$modded_taxes_total = array_sum(array_map('wc_round_tax_total', $modded_taxes));
								}
								$return_price = $tax_with_price == 'excl' ? round($line_price - $base_taxes_total, $num_of_decimal) : round($line_price - $base_taxes_total + $modded_taxes_total, $num_of_decimal);
							}
						}
					}
					$return_price = apply_filters('woocommerce_get_price_including_tax', $return_price, $qty, $product);
					$display_suffix = get_option('woocommerce_price_display_suffix') ? get_option('woocommerce_price_display_suffix') : '';
					return wc_price($return_price) . ' ' . $display_suffix;
				}
			}
		}
	}