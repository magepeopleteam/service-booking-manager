<?php
	if ( ! defined( 'ABSPATH' ) ) {
		die;
	} // Cannot access pages directly.
	if ( ! class_exists( 'MPWPB_Function' ) ) {
		class MPWPB_Function {
			public function __construct() {
				add_filter( 'use_block_editor_for_post_type', [ $this, 'disable_gutenberg' ], 10, 2 );
				add_action( 'mpwpb_date_picker_js', array( $this, 'date_picker_js' ), 10, 2 );
			}
			//************************************//
			public function disable_gutenberg( $current_status, $post_type ) {
				$user_status = self::get_general_settings( 'disable_block_editor', 'yes' );
				if ( $post_type === self::get_cpt_name() && $user_status == 'yes' ) {
					return false;
				}
				return $current_status;
			}
			public function date_picker_js( $selector, $dates ) {
				$start_date  = $dates[0];
				$start_year  = date( 'Y', strtotime( $start_date ) );
				$start_month = ( date( 'n', strtotime( $start_date ) ) - 1 );
				$start_day   = date( 'j', strtotime( $start_date ) );
				$end_date    = end( $dates );
				$end_year    = date( 'Y', strtotime( $end_date ) );
				$end_month   = ( date( 'n', strtotime( $end_date ) ) - 1 );
				$end_day     = date( 'j', strtotime( $end_date ) );
				$all_date    = [];
				foreach ( $dates as $date ) {
					$all_date[] = '"' . date( 'j-n-Y', strtotime( $date ) ) . '"';
				}
				?>
				<script>
							jQuery(document).ready(function () {
								jQuery("<?php echo esc_attr( $selector ); ?>").datepicker({
									dateFormat: mp_date_format,
									minDate: new Date(<?php echo esc_attr($start_year); ?>, <?php echo esc_attr($start_month); ?>, <?php echo esc_attr($start_day); ?>),
									maxDate: new Date(<?php echo esc_attr($end_year); ?>, <?php echo esc_attr($end_month); ?>, <?php echo esc_attr($end_day); ?>),
									autoSize: true,
									beforeShowDay: WorkingDates,
									onSelect: function (dateString, data) {
										let date = data.selectedYear + '-' + (parseInt(data.selectedMonth) + 1) + '-' + data.selectedDay;
										jQuery(this).closest('label').find('input[type="hidden"]').val(date).trigger('change');
									}
								});

								function WorkingDates(date) {
									let availableDates = [<?php echo implode( ',', $all_date ); ?>];
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
			//************************************//
			public static function wc_product_sku( $product_id ) {
				if ( $product_id ) {
					return new WC_Product( $product_id );
				}
				return null;
			}
			//************************************//
			public static function get_post_info( $tour_id, $key, $default = '' ) {
				$data = get_post_meta( $tour_id, $key, true ) ?: $default;
				return self::data_sanitize( $data );
			}
			public static function data_sanitize( $data ) {
				$data = maybe_unserialize( $data );
				if ( is_string( $data ) ) {
					$data = maybe_unserialize( $data );
					if ( is_array( $data ) ) {
						$data = self::data_sanitize( $data );
					} else {
						$data = sanitize_text_field( $data );
					}
				} elseif ( is_array( $data ) ) {
					foreach ( $data as &$value ) {
						if ( is_array( $value ) ) {
							$value = self::data_sanitize( $value );
						} else {
							$value = sanitize_text_field( $value );
						}
					}
				}
				return $data;
			}
			public static function submit_sanitize( $key, $default = '' ) {
				$data = $_POST[ $key ] ?? $default;
				$data = stripslashes( strip_tags( $data ) );
				return self::data_sanitize( $data );
			}
			public static function get_submit_info( $key, $default = '' ) {
				$data = $_POST[ $key ] ?? $default;
				return self::data_sanitize( $data );
			}
			//*******************************//
			public static function get_plugin_data( $data ) {
				$plugin_data = get_plugin_data( __FILE__ );
				return $plugin_data[ $data ];
			}
			//***********Template********************//
			public static function all_details_template() {
				$template_path = get_stylesheet_directory() . '/mpwpb_templates/themes/';
				$default_path  = MPWPB_PLUGIN_DIR . '/templates/themes/';
				$dir           = is_dir( $template_path ) ? glob( $template_path . "*" ) : glob( $default_path . "*" );
				$names         = array();
				foreach ( $dir as $filename ) {
					if ( is_file( $filename ) ) {
						$file           = basename( $filename );
						$name           = str_replace( "?>", "", strip_tags( file_get_contents( $filename, false, null, 24, 16 ) ) );
						$names[ $file ] = $name;
					}
				}
				$name = [];
				foreach ( $names as $key => $value ) {
					$name[ $key ] = $value;
				}
				return apply_filters( 'filter_mpwpb_details_template', $name );
			}
			public static function details_template_path($post_id=''): string {
				$post_id       = $post_id??get_the_id();
				$template_name = self::get_post_info( $post_id, 'mpwpb_theme_file', 'default.php' );
				$file_name     = 'themes/' . $template_name;
				$dir           = MPWPB_PLUGIN_DIR . '/templates/' . $file_name;
				if ( ! file_exists( $dir ) ) {
					$file_name = 'themes/default.php';
				}
				return self::template_path( $file_name );
			}
			public static function template_path( $file_name ): string {
				$template_path = get_stylesheet_directory() . '/mpwpb_templates/';
				$default_dir   = MPWPB_PLUGIN_DIR . '/templates/';
				$dir           = is_dir( $template_path ) ? $template_path : $default_dir;
				$file_path     = $dir . $file_name;
				return locate_template( array( 'mpwpb_templates/' . $file_name ) ) ? $file_path : $default_dir . $file_name;
			}
			//*******************************//
			public static function get_category_text( $post_id ) {
				$text = MPWPB_Function::get_post_info( $post_id, 'mpwpb_category_text' );
				return $text ?: self::get_general_settings( 'category_text', esc_html__( 'Category', 'bookingmaster' ) );
			}
			public static function get_sub_category_text( $post_id ) {
				$text = MPWPB_Function::get_post_info( $post_id, 'mpwpb_sub_category_text' );
				return $text ?: self::get_general_settings( 'sub_category_text', esc_html__( 'Sub-Category', 'bookingmaster' ) );
			}
			public static function get_service_text( $post_id ) {
				$text = MPWPB_Function::get_post_info( $post_id, 'mpwpb_service_text' );
				return $text ?: self::get_general_settings( 'service_text', esc_html__( 'Service', 'bookingmaster' ) );
			}
			//*******************************//
			public static function get_category( $post_id, $all_services = array() ) {
				$categories      = [];
				$all_services    = $all_services ?: MPWPB_Function::get_post_info( $post_id, 'mpwpb_category_infos', array() );
				$category_active = MPWPB_Function::get_post_info( $post_id, 'mpwpb_category_active', 'on' );
				if ( $category_active == 'on' && sizeof( $all_services ) > 0 ) {
					$count = 0;
					foreach ( $all_services as $service ) {
						if ( array_key_exists( 'category', $service ) && $service['category'] ) {
							$categories[ $count ]['name']  = $service['category'];
							$categories[ $count ]['icon']  = array_key_exists( 'icon', $service ) ? $service['icon'] : '';
							$categories[ $count ]['image'] = array_key_exists( 'image', $service ) ? $service['image'] : '';
							$count ++;
						}
					}
				}
				return $categories;
			}
			public static function get_sub_category( $post_id, $all_services = array() ) {
				$sub_category_list   = [];
				$category_active     = MPWPB_Function::get_post_info( $post_id, 'mpwpb_category_active', 'on' );
				$sub_category_active = MPWPB_Function::get_post_info( $post_id, 'mpwpb_sub_category_active', 'off' );
				$all_services        = $all_services ?: MPWPB_Function::get_post_info( $post_id, 'mpwpb_category_infos', array() );
				$count               = 0;
				if ( sizeof( $all_services ) > 0 ) {
					foreach ( $all_services as $category_info ) {
						$category_name  = array_key_exists( 'category', $category_info ) ? $category_info['category'] : '';
						$category_name  = $category_active == 'on' ? $category_name : '';
						$sub_categories = array_key_exists( 'sub_category', $category_info ) ? $category_info['sub_category'] : array();
						if ( $category_name && sizeof( $sub_categories ) > 0 ) {
							foreach ( $sub_categories as $sub_category ) {
								$sub_category_name  = array_key_exists( 'name', $sub_category ) ? $sub_category['name'] : '';
								$sub_category_icon  = array_key_exists( 'icon', $sub_category ) ? $sub_category['icon'] : '';
								$sub_category_image = array_key_exists( 'image', $sub_category ) ? $sub_category['image'] : '';
								$sub_category_name  = $category_active == 'on' && $sub_category_active == 'on' ? $sub_category_name : '';
								if ( $sub_category_name ) {
									$sub_category_list[ $count ]['category']     = $category_name;
									$sub_category_list[ $count ]['sub_category'] = $sub_category_name;
									$sub_category_list[ $count ]['icon']         = $sub_category_icon;
									$sub_category_list[ $count ]['image']        = $sub_category_image;
									$count ++;
								}
							}
						}
					}
				}
				return $sub_category_list;
			}
			public static function get_all_service( $post_id ) {
				$all_service_item    = [];
				$category_active     = MPWPB_Function::get_post_info( $post_id, 'mpwpb_category_active', 'on' );
				$sub_category_active = MPWPB_Function::get_post_info( $post_id, 'mpwpb_sub_category_active', 'off' );
				$all_services        = MPWPB_Function::get_post_info( $post_id, 'mpwpb_category_infos', array() );
				$count               = 0;
				if ( sizeof( $all_services ) > 0 ) {
					foreach ( $all_services as $category_info ) {
						$category_name  = array_key_exists( 'category', $category_info ) ? $category_info['category'] : '';
						$sub_categories = array_key_exists( 'sub_category', $category_info ) ? $category_info['sub_category'] : array();
						if ( sizeof( $sub_categories ) > 0 ) {
							foreach ( $sub_categories as $sub_category ) {
								$sub_category_name = array_key_exists( 'name', $sub_category ) ? $sub_category['name'] : '';
								$services          = array_key_exists( 'service', $sub_category ) ? $sub_category['service'] : array();
								if ( sizeof( $services ) > 0 ) {
									foreach ( $services as $service ) {
										$all_service_item[ $count ]['category']     = $category_active == 'on' ? $category_name : '';
										$all_service_item[ $count ]['sub_category'] = $category_active == 'on' && $sub_category_active == 'on' ? $sub_category_name : '';
										$all_service_item[ $count ]['service']      = array_key_exists( 'name', $service ) ? $service['name'] : '';
										$all_service_item[ $count ]['price']        = array_key_exists( 'price', $service ) ? $service['price'] : '';
										$all_service_item[ $count ]['image']        = array_key_exists( 'image', $service ) ? $service['image'] : '';
										$all_service_item[ $count ]['icon']         = array_key_exists( 'icon', $service ) ? $service['icon'] : '';
										$all_service_item[ $count ]['duration']     = array_key_exists( 'duration', $service ) ? $service['duration'] : '';
										$all_service_item[ $count ]['details']      = array_key_exists( 'details', $service ) ? $service['details'] : '';
										$count ++;
									}
								}
							}
						}
					}
				}
				return $all_service_item;
			}
			//*********Date and Time**********************//
			public static function get_all_date( $post_id ) {
				$dates         = [];
				$now           = strtotime( current_time( 'Y-m-d' ) );
				$start_date    = self::get_post_info( $post_id, 'mpwpb_service_start_date' );
				$end_date      = self::get_post_info( $post_id, 'mpwpb_service_end_date' );
				$all_dates     = self::date_separate_period( $start_date, $end_date );
				$all_off_dates = self::get_post_info( $post_id, 'mpwpb_off_dates', array() );
				$all_off_days  = self::get_post_info( $post_id, 'mpwpb_off_days' );
				$all_off_days  = explode( ',', $all_off_days );
				$off_dates     = array();
				foreach ( $all_off_dates as $off_date ) {
					$off_dates[] = date( 'Y-m-d', strtotime( $off_date ) );
				}
				foreach ( $all_dates as $date ) {
					$date = $date->format( 'Y-m-d' );
					if ( $now <= strtotime( $date ) ) {
						$day = strtolower( date( 'l', strtotime( $date ) ) );
						if ( ! in_array( $date, $off_dates ) && ! in_array( $day, $all_off_days ) ) {
							$dates[] = $date;
						}
					}
				}
				return apply_filters( 'mpwpb_get_date', $dates, $post_id );
			}
			public static function get_time_slot( $post_id, $start_date ) {
				$all_slots   = [];
				$slot_length = MPWPB_Function::get_post_info( $post_id, 'mpwpb_time_slot_length', 30 );
				$slot_length = $slot_length * 60;
				$day_name    = strtolower( date( 'l', strtotime( $start_date ) ) );
				$start_time  = MPWPB_Function::get_post_info( $post_id, 'mpwpb_' . $day_name . '_start_time' );
				if ( ! $start_time ) {
					$day_name   = 'default';
					$start_time = MPWPB_Function::get_post_info( $post_id, 'mpwpb_' . $day_name . '_start_time' );
				}
				$start_time = $start_time * 3600;
				//$start_time=$start_time+strtotime($start_date);
				$end_time         = MPWPB_Function::get_post_info( $post_id, 'mpwpb_' . $day_name . '_end_time' ) * 3600;
				$start_time_break = MPWPB_Function::get_post_info( $post_id, 'mpwpb_' . $day_name . '_start_break_time' ) * 3600;
				$end_time_break   = MPWPB_Function::get_post_info( $post_id, 'mpwpb_' . $day_name . '_end_break_time' ) * 3600;
				for ( $i = $start_time; $i <= $end_time; $i = $i + $slot_length ) {
					if ( $i < $start_time_break || $i >= $end_time_break ) {
						$all_slots[] = $start_date . ' ' . date( 'H:i', $i );
					}
				}
				return $all_slots;
			}
			public static function datetime_format( $date, $type = 'date-time-text' ) {
				$date_format = get_option( 'date_format' );
				$time_format = get_option( 'time_format' );
				$wp_settings = $date_format . '  ' . $time_format;
				$timezone    = wp_timezone_string();
				$timestamp   = strtotime( $date . ' ' . $timezone );
				if ( $type == 'date-time' ) {
					$date = wp_date( $wp_settings, $timestamp );
				} elseif ( $type == 'date-text' ) {
					$date = wp_date( $date_format, $timestamp );
				} elseif ( $type == 'date' ) {
					$date = wp_date( $date_format, $timestamp );
				} elseif ( $type == 'time' ) {
					$date = wp_date( $time_format, $timestamp, wp_timezone() );
				} elseif ( $type == 'day' ) {
					$date = wp_date( 'd', $timestamp );
				} elseif ( $type == 'month' ) {
					$date = wp_date( 'M', $timestamp );
				} elseif ( $type == 'date-time-text' ) {
					$date = wp_date( $wp_settings, $timestamp, wp_timezone() );
				} else {
					$date = wp_date( $type, $timestamp );
				}
				return $date;
			}
			public static function date_format( $date, $format = 'date' ) {
				$date_format = get_option( 'date_format' );
				$time_format = get_option( 'time_format' );
				$wp_settings = $date_format . '  ' . $time_format;
				$timezone    = wp_timezone_string();
				$timestamp   = strtotime( $date . ' ' . $timezone );
				if ( $format == 'date' ) {
					$date = date_i18n( $date_format, $timestamp );
				} elseif ( $format == 'time' ) {
					$date = date_i18n( $time_format, $timestamp );
				} elseif ( $format == 'full' ) {
					$date = date_i18n( $wp_settings, $timestamp );
				} elseif ( $format == 'day' ) {
					$date = date_i18n( 'd', $timestamp );
				} elseif ( $format == 'month' ) {
					$date = date_i18n( 'M', $timestamp );
				} elseif ( $format == 'year' ) {
					$date = date_i18n( 'Y', $timestamp );
				} else {
					$date = date_i18n( $format, $timestamp );
				}
				return $date;
			}
			public static function date_picker_format(): string {
				$format      = self::get_general_settings( 'date_format', 'D d M , yy' );
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
			public static function date_separate_period( $start_date, $end_date, $repeat = 1 ): DatePeriod {
				$repeat    = $repeat > 1 ? $repeat : 1;
				$_interval = "P" . $repeat . "D";
				$end_date  = date( 'Y-m-d', strtotime( $end_date . ' +1 day' ) );
				return new DatePeriod( new DateTime( $start_date ), new DateInterval( $_interval ), new DateTime( $end_date ) );
			}
			//*************Price*********************************//
			public static function get_price( $post_id, $service_name, $category_name = '', $sub_category_name = '', $date = '' ) {
				$all_service = MPWPB_Function::get_post_info( $post_id, 'mpwpb_category_infos', array() );
				$price       = 0;
				if ( sizeof( $all_service ) > 0 ) {
					foreach ( $all_service as $categories ) {
						$current_category_name = array_key_exists( 'category', $categories ) ? $categories['category'] : '';
						if ( ( $current_category_name && $category_name && $current_category_name == $category_name ) || ( ! $current_category_name && ! $category_name ) ) {
							$sub_categories = array_key_exists( 'sub_category', $categories ) ? $categories['sub_category'] : array();
							if ( sizeof( $sub_categories ) > 0 ) {
								foreach ( $sub_categories as $sub_category ) {
									$current_sub_category_name = array_key_exists( 'name', $sub_category ) ? $sub_category['name'] : '';
									if ( ( $current_sub_category_name && $sub_category_name && $current_sub_category_name == $sub_category_name ) || ( ! $current_sub_category_name && ! $sub_category_name ) ) {
										$service_infos = array_key_exists( 'service', $sub_category ) ? $sub_category['service'] : [];
										if ( sizeof( $service_infos ) > 0 ) {
											foreach ( $service_infos as $service_info ) {
												$current_service_name = array_key_exists( 'name', $service_info ) ? $service_info['name'] : '';
												if ( $current_service_name == $service_name ) {
													$price = array_key_exists( 'price', $service_info ) ? $service_info['price'] : 0;
												}
											}
										}
									}
								}
							}
						}
					}
				}
				$price = self::wc_price( $post_id, $price );
				$price = self::price_convert_raw( $price );
				return apply_filters( 'mpwpb_price_filter', $price, $post_id, $category_name, $service_name, $date );
			}
			public static function get_extra_price( $post_id, $ex_service_category, $ex_service_types ) {
				$ex_price       = 0;
				$extra_services = MPWPB_Function::get_post_info( $post_id, 'mpwpb_extra_service', array() );
				if ( sizeof( $extra_services ) > 0 ) {
					foreach ( $extra_services as $group_service ) {
						$group_service_name = array_key_exists( 'group_service', $group_service ) ? $group_service['group_service'] : '';
						if ( $group_service_name == $ex_service_category ) {
							$service_infos = array_key_exists( 'group_service_info', $group_service ) ? $group_service['group_service_info'] : [];
							if ( sizeof( $service_infos ) > 0 ) {
								foreach ( $service_infos as $service_info ) {
									$service_name = array_key_exists( 'name', $service_info ) ? $service_info['name'] : '';
									if ( $service_name && $service_name == $ex_service_types ) {
										$ex_price = $ex_price + array_key_exists( 'price', $service_info ) ? $service_info['price'] : 0;
									}
								}
							}
						}
					}
				}
				$ex_price = self::wc_price( $post_id, $ex_price );
				$ex_price = self::price_convert_raw( $ex_price );
				return apply_filters( 'mpwpb_price_filter', $ex_price, $post_id, $ex_service_category, $ex_service_types );
			}
			public static function price_convert_raw( $price ) {
				$price = wp_strip_all_tags( $price );
				$price = str_replace( get_woocommerce_currency_symbol(), '', $price );
				$price = str_replace( wc_get_price_thousand_separator(), '', $price );
				$price = str_replace( wc_get_price_decimal_separator(), '.', $price );
				return max( $price, 0 );
			}
			public static function wc_price( $post_id, $price, $args = array() ): string {
				$num_of_decimal = get_option( 'woocommerce_price_num_decimals', 2 );
				$args           = wp_parse_args( $args, array(
					'qty'   => '',
					'price' => '',
				) );
				$_product       = self::get_post_info( $post_id, 'link_wc_product', $post_id );
				$product        = wc_get_product( $_product );
				$qty            = '' !== $args['qty'] ? max( 0.0, (float) $args['qty'] ) : 1;
				$tax_with_price = get_option( 'woocommerce_tax_display_shop' );
				if ( '' === $price ) {
					return '';
				} elseif ( empty( $qty ) ) {
					return 0.0;
				}
				$line_price   = (float) $price * (int) $qty;
				$return_price = $line_price;
				if ( $product->is_taxable() ) {
					if ( ! wc_prices_include_tax() ) {
						$tax_rates = WC_Tax::get_rates( $product->get_tax_class() );
						$taxes     = WC_Tax::calc_tax( $line_price, $tax_rates );
						if ( 'yes' === get_option( 'woocommerce_tax_round_at_subtotal' ) ) {
							$taxes_total = array_sum( $taxes );
						} else {
							$taxes_total = array_sum( array_map( 'wc_round_tax_total', $taxes ) );
						}
						$return_price = $tax_with_price == 'excl' ? round( $line_price, $num_of_decimal ) : round( $line_price + $taxes_total, $num_of_decimal );
					} else {
						$tax_rates      = WC_Tax::get_rates( $product->get_tax_class() );
						$base_tax_rates = WC_Tax::get_base_tax_rates( $product->get_tax_class( 'unfiltered' ) );
						if ( ! empty( WC()->customer ) && WC()->customer->get_is_vat_exempt() ) { // @codingStandardsIgnoreLine.
							$remove_taxes = apply_filters( 'woocommerce_adjust_non_base_location_prices', true ) ? WC_Tax::calc_tax( $line_price, $base_tax_rates, true ) : WC_Tax::calc_tax( $line_price, $tax_rates, true );
							if ( 'yes' === get_option( 'woocommerce_tax_round_at_subtotal' ) ) {
								$remove_taxes_total = array_sum( $remove_taxes );
							} else {
								$remove_taxes_total = array_sum( array_map( 'wc_round_tax_total', $remove_taxes ) );
							}
							// $return_price = round( $line_price, $num_of_decimal);
							$return_price = round( $line_price - $remove_taxes_total, $num_of_decimal );
						} else {
							$base_taxes   = WC_Tax::calc_tax( $line_price, $base_tax_rates, true );
							$modded_taxes = WC_Tax::calc_tax( $line_price - array_sum( $base_taxes ), $tax_rates );
							if ( 'yes' === get_option( 'woocommerce_tax_round_at_subtotal' ) ) {
								$base_taxes_total   = array_sum( $base_taxes );
								$modded_taxes_total = array_sum( $modded_taxes );
							} else {
								$base_taxes_total   = array_sum( array_map( 'wc_round_tax_total', $base_taxes ) );
								$modded_taxes_total = array_sum( array_map( 'wc_round_tax_total', $modded_taxes ) );
							}
							$return_price = $tax_with_price == 'excl' ? round( $line_price - $base_taxes_total, $num_of_decimal ) : round( $line_price - $base_taxes_total + $modded_taxes_total, $num_of_decimal );
						}
					}
				}
				$return_price   = apply_filters( 'woocommerce_get_price_including_tax', $return_price, $qty, $product );
				$display_suffix = get_option( 'woocommerce_price_display_suffix' ) ? get_option( 'woocommerce_price_display_suffix' ) : '';
				return wc_price( $return_price ) . ' ' . $display_suffix;
			}
			//************* seat ******************//
			public static function get_total_available( $post_id, $date ) {
				$total     = self::get_post_info( $post_id, 'mpwpb_capacity_per_session', 1 );
				$sold      = MPWPB_Query::query_all_sold( $post_id, $date )->post_count;
				$available = $total - $sold;
				return max( 0, $available );
			}
			//*******************************//
			public static function get_image_url( $post_id = '', $image_id = '', $size = 'full' ) {
				if ( $post_id ) {
					$image_id = self::get_post_info( $post_id, 'mptbm_list_thumbnail' );
					$image_id = $image_id ?: get_post_thumbnail_id( $post_id );
				}
				return wp_get_attachment_image_url( $image_id, $size );
			}
			//*******************************//
			public static function array_to_string( $array ) {
				$ids = '';
				if ( sizeof( $array ) > 0 ) {
					foreach ( $array as $data ) {
						if ( $data ) {
							$ids = $ids ? $ids . ',' . $data : $data;
						}
					}
				}
				return $ids;
			}
			//*******************************//
			public static function get_faq( $tour_id ) {
				return self::get_post_info( $tour_id, 'mptbm_faq', array() );
			}
			public static function get_why_choose_us( $tour_id ) {
				return self::get_post_info( $tour_id, 'mptbm_why_choose_us_texts', array() );
			}
			//*******************************//
			public static function get_taxonomy( $name ) {
				return get_terms( array( 'taxonomy' => $name, 'hide_empty' => false ) );
			}
			//************************//
			public static function all_tax_list(): array {
				global $wpdb;
				$table_name = $wpdb->prefix . 'wc_tax_rate_classes';
				$result     = $wpdb->get_results( "SELECT * FROM $table_name" );
				$tax_list   = [];
				foreach ( $result as $tax ) {
					$tax_list[ $tax->slug ] = $tax->name;
				}
				return $tax_list;
			}
			//************************//
			public static function get_settings( $options, $key, $default = '' ) {
				if ( isset( $options[ $key ] ) && $options[ $key ] ) {
					$default = $options[ $key ];
				}
				return $default;
			}
			public static function get_general_settings( $key, $default = '' ) {
				$options = get_option( 'mpwpb_general_settings' );
				return self::get_settings( $options, $key, $default );
			}
			public static function get_slider_settings( $key, $default = '' ) {
				$options = get_option( 'super_slider_settings' );
				return self::get_settings( $options, $key, $default );
			}
			public static function get_style_settings( $key, $default = '' ) {
				$options = get_option( 'mpwpb_style_settings' );
				return self::get_settings( $options, $key, $default );
			}
			//*****************//
			public static function get_cpt_name(): string {
				return 'mpwpb_item';
			}
			public static function get_name() {
				return self::get_general_settings( 'label', esc_html__( 'WP Easy Booking', 'mptbm_plugin' ) );
			}
			public static function get_slug() {
				return self::get_general_settings( 'slug', 'bookingmaster' );
			}
			public static function get_icon() {
				return self::get_general_settings( 'icon', 'dashicons-list-view' );
			}
			public static function get_category_label() {
				return self::get_general_settings( 'category_label', esc_html__( 'Category', 'mptbm_plugin' ) );
			}
			public static function get_category_slug() {
				return self::get_general_settings( 'category_slug', 'service-category' );
			}
			public static function get_organizer_label() {
				return self::get_general_settings( 'organizer_label', esc_html__( 'Organizer', 'mptbm_plugin' ) );
			}
			public static function get_organizer_slug() {
				return self::get_general_settings( 'organizer_slug', 'service-organizer' );
			}
			//***********************//
			public static function week_day(): array {
				return [
					'monday'    => esc_html__( 'Monday', 'mptbm_plugin' ),
					'tuesday'   => esc_html__( 'Tuesday', 'mptbm_plugin' ),
					'wednesday' => esc_html__( 'Wednesday', 'mptbm_plugin' ),
					'thursday'  => esc_html__( 'Thursday', 'mptbm_plugin' ),
					'friday'    => esc_html__( 'Friday', 'mptbm_plugin' ),
					'saturday'  => esc_html__( 'Saturday', 'mptbm_plugin' ),
					'sunday'    => esc_html__( 'Sunday', 'mptbm_plugin' ),
				];
			}
			//***********************//
			public static function get_order_details( $order_id ) {
				$all_orders  = [];
				$guest_query = MPWPB_Query::get_order_info( $order_id );
				if ( $guest_query->found_posts > 0 ) {
					$attendee_query = $guest_query->posts;
					foreach ( $attendee_query as $_attendee ) {
						$attendee_id                              = $_attendee->ID;
						$order_id                                 = MPWPB_Function::get_post_info( $attendee_id, 'mpwpb_order_id' );
						$all_orders[ $order_id ]['attendee_id'][] = $attendee_id;
						if ( ! array_key_exists( 'order_date', $all_orders[ $order_id ] ) ) {
							$all_orders[ $order_id ]['order_date']            = $_attendee->post_date;
							$all_orders[ $order_id ]['payment_method']        = MPWPB_Function::get_post_info( $attendee_id, 'mpwpb_payment_method' );
						}
					}
				}
				return $all_orders;
			}
			//***********************//
			public static function esc_html( $string ): string {
				$allow_attr = array(
					'input'    => [
						'type'               => [],
						'class'              => [],
						'id'                 => [],
						'name'               => [],
						'value'              => [],
						'size'               => [],
						'placeholder'        => [],
						'min'                => [],
						'max'                => [],
						'checked'            => [],
						'required'           => [],
						'disabled'           => [],
						'readonly'           => [],
						'step'               => [],
						'data-default-color' => [],
						'data-price'         => [],
					],
					'p'        => [ 'class' => [] ],
					'img'      => [ 'class' => [], 'id' => [], 'src' => [], 'alt' => [], ],
					'fieldset' => [
						'class' => []
					],
					'label'    => [
						'for'   => [],
						'class' => []
					],
					'select'   => [
						'class'      => [],
						'name'       => [],
						'id'         => [],
						'data-price' => [],
					],
					'option'   => [
						'class'    => [],
						'value'    => [],
						'id'       => [],
						'selected' => [],
					],
					'textarea' => [
						'class' => [],
						'rows'  => [],
						'id'    => [],
						'cols'  => [],
						'name'  => [],
					],
					'h2'       => [ 'class' => [], 'id' => [], ],
					'a'        => [ 'class' => [], 'id' => [], 'href' => [], ],
					'div'      => [
						'class'                 => [],
						'id'                    => [],
						'data-ticket-type-name' => [],
					],
					'span'     => [
						'class'             => [],
						'id'                => [],
						'data'              => [],
						'data-input-change' => [],
					],
					'i'        => [
						'class' => [],
						'id'    => [],
						'data'  => [],
					],
					'table'    => [
						'class' => [],
						'id'    => [],
						'data'  => [],
					],
					'tr'       => [
						'class' => [],
						'id'    => [],
						'data'  => [],
					],
					'td'       => [
						'class' => [],
						'id'    => [],
						'data'  => [],
					],
					'thead'    => [
						'class' => [],
						'id'    => [],
						'data'  => [],
					],
					'tbody'    => [
						'class' => [],
						'id'    => [],
						'data'  => [],
					],
					'th'       => [
						'class' => [],
						'id'    => [],
						'data'  => [],
					],
					'svg'      => [
						'class'   => [],
						'id'      => [],
						'width'   => [],
						'height'  => [],
						'viewBox' => [],
						'xmlns'   => [],
					],
					'g'        => [
						'fill' => [],
					],
					'path'     => [
						'd' => [],
					],
					'br'       => array(),
					'em'       => array(),
					'strong'   => array(),
				);
				return wp_kses( $string, $allow_attr );
			}
		}
		new MPWPB_Function();
	}