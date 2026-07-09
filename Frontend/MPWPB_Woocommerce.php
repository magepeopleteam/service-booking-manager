<?php
	/*
* @Author 		engr.sumonazma@gmail.com
* Copyright: 	mage-people.com
*/
	if (!defined('ABSPATH')) {
		die;
	} // Cannot access pages directly.
	if (!class_exists('MPWPB_Woocommerce')) {
		class MPWPB_Woocommerce {
			public function __construct() {
				add_filter('woocommerce_add_cart_item_data', array($this, 'add_cart_item_data'), 90, 3);
				add_action('woocommerce_before_calculate_totals', array($this, 'before_calculate_totals'), 90, 1);
				add_filter('woocommerce_cart_item_thumbnail', array($this, 'cart_item_thumbnail'), 90, 3);
				add_filter('woocommerce_get_item_data', array($this, 'get_item_data'), 90, 2);
				//************//
				//add_filter('woocommerce_add_to_cart_redirect', [$this, 'add_to_cart_redirect'], 10, 2);
				//************//
				add_action('woocommerce_after_checkout_validation', array($this, 'after_checkout_validation'));
				add_action('woocommerce_checkout_create_order_line_item', array($this, 'checkout_create_order_line_item'), 90, 4);
				add_action('woocommerce_checkout_order_processed', array($this, 'checkout_order_processed'), 90, 3);
				add_action('woocommerce_store_api_checkout_order_processed', array($this, 'checkout_order_processed'), 90, 3);
				add_filter('woocommerce_order_status_changed', array($this, 'order_status_changed'), 10, 4);
				add_filter('woocommerce_get_return_url', array($this, 'filter_return_url'), 10, 2);
				add_filter('woocommerce_checkout_fields', array($this, 'maybe_hide_billing_fields'), 999);
				add_filter('query_vars', array($this, 'add_thank_you_query_var'));
				add_action('template_redirect', array($this, 'maybe_render_wc_thank_you'));
				/*****************************/
				add_action('wp_ajax_mpwpb_add_to_cart', [$this, 'mpwpb_add_to_cart']);
				add_action('wp_ajax_nopriv_mpwpb_add_to_cart', [$this, 'mpwpb_add_to_cart']);
			}
			/**
			 * "Show Billing Info" setting: hides WooCommerce's billing fieldset
			 * on checkout when the admin turns it off. Default 'on' preserves
			 * the plugin's existing (unmodified) checkout behavior.
			 */
			public function maybe_hide_billing_fields($fields) {
				if (MPWPB_Global_Function::get_payment_setting('wc_show_billing_info', 'on') !== 'on') {
					unset($fields['billing']);
				}
				return $fields;
			}
			/**
			 * "After Confirming the Order, Redirect To" setting.
			 */
			public function filter_return_url($return_url, $order) {
				if (!$order || MPWPB_Global_Function::get_payment_setting('wc_order_confirm_redirect', 'default') !== 'plugin_thank_you') {
					return $return_url;
				}
				return add_query_arg(['mpwpb_wc_thankyou' => '1', 'mpwpb_order' => $order->get_id()], home_url('/'));
			}
			public function add_thank_you_query_var($vars) {
				$vars[] = 'mpwpb_wc_thankyou';
				return $vars;
			}
			public function maybe_render_wc_thank_you(): void {
				if (!get_query_var('mpwpb_wc_thankyou')) {
					return;
				}
				$order_id = isset($_GET['mpwpb_order']) ? absint($_GET['mpwpb_order']) : 0;
				$order = $order_id ? wc_get_order($order_id) : null;
				get_header();
				echo '<div class="mpwpb_style" style="max-width:640px;margin:40px auto;">';
				if ($order) {
					echo '<h2>' . esc_html__('Thank you, your booking is confirmed!', 'service-booking-manager') . '</h2>';
					echo '<p>' . esc_html(
						sprintf(
							/* translators: %s: order reference number */
							__('Order reference: #%s', 'service-booking-manager'),
							$order_id
						)
					) . '</p>';
				} else {
					echo '<p>' . esc_html__('No booking information found.', 'service-booking-manager') . '</p>';
				}
				echo '</div>';
				get_footer();
				exit;
			}
			public function add_cart_item_data($cart_item_data, $product_id) {
				$booking_item = self::build_booking_item_from_request($product_id);
				if (!empty($booking_item)) {
					$cart_item_data = array_merge($cart_item_data, $booking_item);
				}
				return $cart_item_data;
			}
			/**
			 * Builds the booking selection array from the current $_POST request.
			 * Shared by the WooCommerce cart bridge (add_cart_item_data) and the
			 * native (non-WooCommerce) add-to-cart path so both stay in sync.
			 */
			public static function build_booking_item_from_request($product_id): array {
				$cart_item_data = array();
				$linked_id = MPWPB_Global_Function::get_post_info($product_id, 'link_mpwpb_id', $product_id);
				$product_id = is_string(get_post_status($linked_id)) ? $linked_id : $product_id;
                $enable_recurring = MPWPB_Global_Function::get_post_info( $product_id, 'mpwpb_enable_recurring', 'no');
                $recurring_discount = MPWPB_Global_Function::get_post_info( $product_id, 'mpwpb_recurring_discount', 0 );

                $discountPercent = 0;
                if( $enable_recurring === 'yes' ){
                    $discountPercent = $recurring_discount;
                }

				if (isset($_POST['nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'mpwpb_nonce')) {
					if (get_post_type($product_id) == MPWPB_Function::get_cpt()) {
						$category = isset($_POST['mpwpb_category']) ? sanitize_text_field(wp_unslash($_POST['mpwpb_category'])) : '';
						$sub_category = isset($_POST['mpwpb_sub_category']) ? sanitize_text_field(wp_unslash($_POST['mpwpb_sub_category'])) : '';
						$services = isset($_POST['mpwpb_service']) ? array_map('sanitize_text_field', wp_unslash($_POST['mpwpb_service'])) : [];
						$services_qty = isset($_POST['mpwpb_service_qty']) ? array_map('sanitize_text_field', wp_unslash($_POST['mpwpb_service_qty'])) : [];
						$date = isset($_POST['mpwpb_date']) ? sanitize_text_field(wp_unslash($_POST['mpwpb_date'])) : '';
						$all_service = [];
						if (is_array($services) && sizeof($services)) {
							foreach ($services as $key => $service) {
								$all_service[$key]['name'] = MPWPB_Function::get_service_name($product_id, $service);
								$all_service[$key]['price'] = MPWPB_Function::get_price($product_id, $service, $date);
								$all_service[$key]['qty'] = $services_qty[ $service ];
							}
						}

						$ex_service_types = isset($_POST['mpwpb_extra_service_type']) ? array_map('sanitize_text_field', wp_unslash($_POST['mpwpb_extra_service_type'])) : [];
						$ex_service_qty = isset($_POST['mpwpb_extra_service_qty']) ? array_map('sanitize_text_field', wp_unslash($_POST['mpwpb_extra_service_qty'])) : [];
						$ex_service_group = isset($_POST['mpwpb_extra_service']) ? array_map('sanitize_text_field', wp_unslash($_POST['mpwpb_extra_service'])) : [];
						$is_recurring_on = isset($_POST['is_recurring_on']) ? sanitize_text_field( wp_unslash( $_POST['is_recurring_on'] ) ) : 'off';
						$total_price = self::get_cart_total_price($product_id, $all_service, $ex_service_types, $ex_service_qty, $ex_service_group);
                        if( $is_recurring_on === 'on' ){
                            $recurringCount = isset($_POST['recurringCount']) ? sanitize_text_field( wp_unslash( $_POST['recurringCount'] ) ) : 1;
                            $total_price = self::calculate_discounted_total( $total_price, $recurringCount, $discountPercent );
                        }

                        $mpwpb_staff_member_id = isset($_POST['mpwpb_staff_member']) ? sanitize_text_field(wp_unslash($_POST['mpwpb_staff_member'])) : '';
                        if( $mpwpb_staff_member_id ){
                            $mpwpb_staff_date = get_userdata($mpwpb_staff_member_id);
                            $mpwpb_staff_member = $mpwpb_staff_date->display_name;
                        }else{
                            $mpwpb_staff_member = '';
                        }

                        $cart_item_data['mpwpb_staff_name'] = $mpwpb_staff_member;
                        $cart_item_data['mpwpb_staff_member_id'] = $mpwpb_staff_member_id;

						$cart_item_data['mpwpb_category'] = MPWPB_Function::get_category_name($product_id, $category);
						$cart_item_data['mpwpb_sub_category'] = MPWPB_Function::get_sub_category_name($product_id, $sub_category);
						$cart_item_data['mpwpb_service'] = $all_service;
						$cart_item_data['mpwpb_date'] = $date;
						$cart_item_data['mpwpb_extra_service_info'] = self::cart_extra_service_info($product_id, $date, $ex_service_types, $ex_service_qty);
						$cart_item_data['mpwpb_tp'] = $total_price;
						$cart_item_data['line_total'] = $total_price;
						$cart_item_data['line_subtotal'] = $total_price;
						$cart_item_data = apply_filters('mpwpb_add_cart_item', $cart_item_data, $product_id);
					}
					$cart_item_data['mpwpb_id'] = $product_id;
				}
				//echo '<pre>'; print_r( $cart_item_data ); echo '</pre>'; die();
				return $cart_item_data;
			}
			public function before_calculate_totals($cart_object): void {
				foreach ($cart_object->cart_contents as $value) {
					$post_id = array_key_exists('mpwpb_id', $value) ? $value['mpwpb_id'] : 0;
					// mpwpb_tp is only set by add_cart_item_data() for booking items added
					// through the normal flow; skip the price override entirely if it's
					// missing rather than guessing a price for this line item.
					if (get_post_type($post_id) == MPWPB_Function::get_cpt() && isset($value['mpwpb_tp'])) {
						$total_price = $value['mpwpb_tp'];
						$value['data']->set_price($total_price);
						$value['data']->set_regular_price($total_price);
						$value['data']->set_sale_price($total_price);
						$value['data']->set_sold_individually('yes');
						$value['data']->get_price();
					}
				}
			}
			public function cart_item_thumbnail($thumbnail, $cart_item) {
				$post_id = array_key_exists('mpwpb_id', $cart_item) ? $cart_item['mpwpb_id'] : 0;
				if (get_post_type($post_id) == MPWPB_Function::get_cpt()) {
					$thumbnail = '<div class="bg_image_area" data-href="' . get_the_permalink($post_id) . '"><div data-bg-image="' . MPWPB_Global_Function::get_image_url($post_id) . '"></div></div>';
				}
				return $thumbnail;
			}
			/**
			 * FIX: Only show "Booking Details" for service booking items, not regular products
			 * AUTHOR: Kiro
			 * ISSUE: #6 "Booking Details:" text shows on all cart items including non-booking products
			 * SOLVED: 2025-01-20
			 * CONTEXT: $item_data[] was outside the post type check, causing it to append empty "Booking Details" to all products
			 */
			public function get_item_data($item_data, $cart_item) {
				$post_id = array_key_exists('mpwpb_id', $cart_item) ? $cart_item['mpwpb_id'] : 0;
				if (get_post_type($post_id) == MPWPB_Function::get_cpt()) {
					ob_start();
					$this->show_cart_item($cart_item, $post_id);
					do_action('mpwpb_show_cart_item', $cart_item, $post_id);
					$booking_html = ob_get_clean();
					if (!empty($booking_html)) {
						$item_data[] = array('key' => esc_html__('Booking Details ', 'service-booking-manager'), 'value' => $booking_html);
					}
				}
				return $item_data;
			}
			//**************//
			public function after_checkout_validation() {
				global $woocommerce;
				$items = $woocommerce->cart->get_cart();
				foreach ($items as $values) {
					$post_id = array_key_exists('mpwpb_id', $values) ? $values['mpwpb_id'] : 0;
					if (get_post_type($post_id) == MPWPB_Function::get_cpt()) {
						//wc_add_notice( __( "custom_notice", 'fake_error' ), 'error');
						do_action('mpwpb_validate_cart_item', $values, $post_id);
					}
				}
			}
			public function checkout_create_order_line_item($item, $cart_item_key, $values) {

				$post_id = array_key_exists('mpwpb_id', $values) ? $values['mpwpb_id'] : 0;
				if (get_post_type($post_id) == MPWPB_Function::get_cpt()) {

					$category = $values['mpwpb_category'] ?: '';
                    $mpwpb_staff_name = $values['mpwpb_staff_name'] ?: '';
					$staff_member = $values['mpwpb_staff_member_id'] ?: '';
					$sub_category = $values['mpwpb_sub_category'] ?: '';
					$services = $values['mpwpb_service'] ?: [];
					$date = $values['mpwpb_date'] ?: '';
                    $date_array = [];
                    if( !empty( $date ) ){
                        $date_array = explode( ',', $date );
                    }
					$total_price = $values['mpwpb_tp'] ?? '';
					$extra_service = $values['mpwpb_extra_service_info'] ?: [];
					if ($category) {
						$item->add_meta_data(MPWPB_Function::get_category_text($post_id), $category);
						if ($sub_category) {
							$item->add_meta_data(MPWPB_Function::get_sub_category_text($post_id), $sub_category);
						}
					}
					if (is_array($services) && sizeof($services)) {
						foreach ($services as $service) {
							$item->add_meta_data(MPWPB_Function::get_service_text($post_id), $service['name']);
							$item->add_meta_data(esc_html__('Price ', 'service-booking-manager'), MPWPB_Global_Function::wc_price($post_id, $service['price']));
						}
					}
                    if( is_array($date_array) && sizeof($date_array) > 0 ) {
                        foreach ($date_array as $days) {
                            $item->add_meta_data(esc_html__('Date ', 'service-booking-manager'), esc_html(MPWPB_Global_Function::date_format($days)));
                            $item->add_meta_data(esc_html__('Time ', 'service-booking-manager'), esc_html(MPWPB_Global_Function::date_format($date, 'time')));
                        }
                    }
					if (sizeof($extra_service) > 0) {
						foreach ($extra_service as $ex_service) {
							$item->add_meta_data(esc_html__('Services Name ', 'service-booking-manager'), $ex_service['ex_name']);
							$item->add_meta_data(esc_html__('Quantity ', 'service-booking-manager'), $ex_service['ex_qty']);
							$item->add_meta_data(esc_html__('Price ', 'service-booking-manager'), ' ( ' . MPWPB_Global_Function::wc_price($post_id, $ex_service['ex_price']) . ' x ' . $ex_service['ex_qty'] . ') = ' . MPWPB_Global_Function::wc_price($post_id, ($ex_service['ex_price'] * $ex_service['ex_qty'])));
						}
					}
                    if( $mpwpb_staff_name ){
                        $item->add_meta_data(esc_html__('Staff Name ', 'service-booking-manager'), $mpwpb_staff_name );
                    }
					$item->add_meta_data('_mpwpb_id', $post_id);
					$item->add_meta_data('_mpwpb_staff_term_id', $staff_member);
					$item->add_meta_data('_mpwpb_date', $date);
					if ($category) {
						$item->add_meta_data('_mpwpb_category', $category);
						if ($sub_category) {
							$item->add_meta_data('_mpwpb_sub_category', $sub_category);
						}
					}
					$item->add_meta_data('_mpwpb_service', $services);
					$item->add_meta_data('_mpwpb_tp', $total_price);
					$item->add_meta_data('_mpwpb_extra_service_info', $extra_service);
					do_action('mpwpb_checkout_create_order_line_item', $item, $values);
				}
			}
			public function checkout_order_processed($order_id) {
				if (is_object($order_id)) {
					$order_id = $order_id->get_id();
				}
				if (!$order_id) {
					return;
				}
				$order = wc_get_order($order_id);
				$this->maybe_create_bookings_for_order($order_id, $order);
			}
			/**
			 * Reads the mpwpb-tagged line items and billing details off a WC order.
			 */
			private function get_order_context($order): array {
				$line_items = [];
				foreach ($order->get_items() as $item_id => $item) {
					$post_id = wc_get_order_item_meta($item_id, '_mpwpb_id');
					if (get_post_type($post_id) != MPWPB_Function::get_cpt()) {
						continue;
					}
					$date = wc_get_order_item_meta($item_id, '_mpwpb_date');
					$category = wc_get_order_item_meta($item_id, '_mpwpb_category');
					$sub_category = wc_get_order_item_meta($item_id, '_mpwpb_sub_category');
					$total_price = wc_get_order_item_meta($item_id, '_mpwpb_tp');
					$line_items[] = [
						'post_id' => $post_id,
						'date' => $date ? sanitize_text_field(wp_unslash($date)) : '',
						'staff_term_id' => wc_get_order_item_meta($item_id, '_mpwpb_staff_term_id'),
						'category' => $category ? sanitize_text_field(wp_unslash($category)) : '',
						'sub_category' => $sub_category ? sanitize_text_field(wp_unslash($sub_category)) : '',
						'service' => wc_get_order_item_meta($item_id, '_mpwpb_service'),
						'total_price' => $total_price ? sanitize_text_field(wp_unslash($total_price)) : '',
						'extra_service_info' => wc_get_order_item_meta($item_id, '_mpwpb_extra_service_info') ?: [],
					];
				}
				$billing = [
					'first_name' => $order->get_billing_first_name(),
					'last_name' => $order->get_billing_last_name(),
					'email' => $order->get_billing_email(),
					'phone' => $order->get_billing_phone(),
					'address_1' => $order->get_billing_address_1(),
					'address_2' => $order->get_billing_address_2(),
				];
				return [$line_items, $billing];
			}
			/**
			 * Creates bookings for a WC order the first time it reaches one of the
			 * admin-configured "Confirm Booking Based on Payment Status" statuses
			 * (Settings > Payment Method > Additional Settings). Idempotent — safe
			 * to call again on later status transitions (e.g. on-hold -> completed)
			 * without creating duplicate bookings.
			 */
			public function maybe_create_bookings_for_order($order_id, $order): void {
				if (!$order_id || !$order) {
					return;
				}
				$order_status = $order->get_status();
				$confirm_statuses = MPWPB_Global_Function::get_payment_setting('wc_confirm_statuses', ['pending', 'processing', 'on-hold', 'completed']);
				$confirm_statuses = is_array($confirm_statuses) && !empty($confirm_statuses) ? $confirm_statuses : ['pending', 'processing', 'on-hold', 'completed'];
				if (!in_array($order_status, $confirm_statuses, true)) {
					return;
				}
				$existing = get_posts([
					'post_type' => 'mpwpb_booking',
					'posts_per_page' => 1,
					'fields' => 'ids',
					'meta_query' => [['key' => 'mpwpb_order_id', 'value' => $order_id]],
				]);
				if (!empty($existing)) {
					return;
				}
				[$line_items, $billing] = $this->get_order_context($order);
				if (empty($line_items)) {
					return;
				}
				self::create_bookings_from_data($order_id, $order_status, $order->get_payment_method(), $order->get_user_id() ?: '', $billing, $line_items);
			}
			/**
			 * Creates the mpwpb_booking / mpwpb_extra_service_booking posts for a completed order.
			 * Source-agnostic: fed from a WC order (checkout_order_processed above) or from a
			 * native (non-WooCommerce) order (see MPWPB_Native_Order::process_order()).
			 *
			 * @param array $line_items Each item: post_id, date, staff_term_id, category, sub_category, service, total_price, extra_service_info
			 * @param array $billing Keys: first_name, last_name, email, phone, address_1, address_2
			 */
			public static function create_bookings_from_data($order_id, $order_status, $payment_method, $user_id, $billing, $line_items): void {
				if ($order_status == 'failed') {
					return;
				}
				foreach ($line_items as $line_item) {
					$post_id = $line_item['post_id'] ?? 0;
					if (get_post_type($post_id) != MPWPB_Function::get_cpt()) {
						continue;
					}
					$ex_service_infos = $line_item['extra_service_info'] ?: [];

					// A comma-joined date string means a recurring booking with more
					// than one occurrence (see mpwpb_registration.js dateTimeString) --
					// create one mpwpb_booking per date instead of squashing every
					// occurrence into a single booking's mpwpb_date meta. Previously
					// only one booking was ever created per line item regardless of
					// how many dates were selected, so recurring bookings past the
					// first date never showed up as real, individually-manageable
					// bookings (staff schedules, admin lists, cancel/reschedule, etc.).
					$raw_date = $line_item['date'] ?? '';
					$occurrence_dates = is_string($raw_date) ? array_values(array_filter(array_map('trim', explode(',', $raw_date)))) : [];
					if (empty($occurrence_dates)) {
						$occurrence_dates = [''];
					}
					$occurrence_count = count($occurrence_dates);

					foreach ($occurrence_dates as $occurrence_index => $occurrence_date) {
						$data = [];
						$data['mpwpb_id'] = $post_id;
						$data['mpwpb_date'] = $occurrence_date;
						if ($occurrence_count > 1) {
							$data['mpwpb_recurring_index'] = $occurrence_index + 1;
							$data['mpwpb_recurring_total'] = $occurrence_count;
						}
						if (!empty($line_item['category'])) {
							$data['mpwpb_category'] = $line_item['category'];
							if (!empty($line_item['sub_category'])) {
								$data['mpwpb_sub_category'] = $line_item['sub_category'];
							}
						}
						$data['mpwpb_service'] = $line_item['service'] ?? [];
						$data['mpwpb_staff_term_id'] = $line_item['staff_term_id'] ?? '';
						$data['mpwpb_tp'] = $line_item['total_price'] ?? '';
						$data['mpwpb_service_info'] = $ex_service_infos;
						$data['mpwpb_order_id'] = $order_id;
						$data['mpwpb_order_status'] = $order_status;
						$data['mpwpb_payment_method'] = $payment_method;
						$data['mpwpb_user_id'] = $user_id ?: '';
						$data['mpwpb_extra_service_info'] = $ex_service_infos;
						$data['mpwpb_billing_name'] = trim(($billing['first_name'] ?? '') . ' ' . ($billing['last_name'] ?? ''));
						$data['mpwpb_billing_email'] = $billing['email'] ?? '';
						$data['mpwpb_billing_phone'] = $billing['phone'] ?? '';
						$data['mpwpb_billing_address'] = trim(($billing['address_1'] ?? '') . ' ' . ($billing['address_2'] ?? ''));
						$booking_data = apply_filters('add_mpwpb_booking_data', $data, $post_id);
						self::add_cpt_data('mpwpb_booking', $booking_data['mpwpb_billing_name'], $booking_data);
						if (is_array($ex_service_infos) && sizeof($ex_service_infos) > 0) {
							foreach ($ex_service_infos as $ex_service_info) {
								$ex_data = [];
								$ex_data['mpwpb_id'] = $post_id;
								$ex_data['mpwpb_date'] = $occurrence_date;
								$ex_data['mpwpb_order_id'] = $order_id;
								$ex_data['mpwpb_order_status'] = $order_status;
								$ex_data['mpwpb_ex_name'] = $ex_service_info['ex_name'];
								$ex_data['mpwpb_ex_price'] = $ex_service_info['ex_price'];
								$ex_data['mpwpb_ex_qty'] = $ex_service_info['ex_qty'];
								$ex_data['mpwpb_payment_method'] = $payment_method;
								$ex_data['mpwpb_user_id'] = $user_id ?: '';
								$ex_title = '#' . $order_id . $ex_data['mpwpb_ex_name'] . ($occurrence_count > 1 ? '-' . ($occurrence_index + 1) : '');
								self::add_cpt_data('mpwpb_extra_service_booking', $ex_title, $ex_data);
							}
						}
					}
				}
			}
			public function order_status_changed($order_id) {
				$order = wc_get_order($order_id);
				if (!$order) {
					return;
				}
				// Confirms the booking retroactively if this transition just
				// reached one of the configured "confirm on" statuses and it
				// wasn't already created on an earlier status.
				$this->maybe_create_bookings_for_order($order_id, $order);
				$order_status = $order->get_status();
				foreach ($order->get_items() as $item_id => $item_values) {
					$post_id = wc_get_order_item_meta($item_id, '_mpwpb_id');
					if (get_post_type($post_id) == MPWPB_Function::get_cpt()) {
						$this->wc_order_status_change($order_status, $post_id, $order_id);
					}
				}
			}
			//**************************//
			public function show_cart_item($cart_item, $post_id) {
				$extra_service = $cart_item['mpwpb_extra_service_info'] ?: [];
				?>
                <div class="mpwpb_style">
					<?php do_action('mpwpb_before_cart_item_display', $cart_item, $post_id); ?>
                    <div class="dLayout_xs">
                        <ul class="cart_list">
							<?php if ($cart_item['mpwpb_category']) { ?>
                                <li>
                                    <h6><?php echo esc_html(MPWPB_Function::get_category_text($post_id)); ?>&nbsp;:&nbsp;</h6>
                                    <span><?php echo esc_html($cart_item['mpwpb_category']); ?></span>
                                </li>
							<?php } ?>
							<?php if ($cart_item['mpwpb_sub_category']) { ?>
                                <li>
                                    <h6><?php echo esc_html(MPWPB_Function::get_sub_category_text($post_id)); ?>&nbsp;:&nbsp;</h6>
                                    <span><?php echo esc_html($cart_item['mpwpb_sub_category']); ?></span>
                                </li>
							<?php } ?>
							<?php
								$services = $cart_item['mpwpb_service'];
								if (is_array($services) && sizeof($services)) {
									foreach ($services as $service) {
										?>
                                        <li>
                                            <h6><?php echo esc_html(MPWPB_Function::get_service_text($post_id)); ?>&nbsp;:&nbsp;</h6>
                                            <span><?php echo esc_html($service['name']); ?></span>
                                        </li>
                                        <li>
                                            <h6><?php esc_html_e('Price', 'service-booking-manager'); ?>&nbsp;:&nbsp;</h6>
                                            <span><?php echo wp_kses_post(' ( ' . MPWPB_Global_Function::wc_price($post_id, $service['price']) . ' x '.$service['qty'].' ) = ' . MPWPB_Global_Function::wc_price($post_id, ($service['price'] * $service['qty']))); ?></span>
                                        </li>
										<?php
									}
								}

                            $str = strpos( $cart_item['mpwpb_date'], ',');


                            $all_date_array = explode(',', $cart_item['mpwpb_date']);
                            if( is_array( $all_date_array ) && sizeof( $all_date_array ) > 0 ) {
                                foreach ($all_date_array as $days) {

                                    ?>
                                        <li>
                                            <span class="far fa-calendar-alt"></span>
                                            <h6><?php esc_html_e('Date', 'service-booking-manager'); ?>&nbsp;:&nbsp;</h6>
                                            <span><?php echo esc_html(MPWPB_Global_Function::date_format( $days ) ); ?></span>
                                        </li>
                                        <li>
                                            <span class="far fa-clock"></span>
                                            <h6><?php esc_html_e('Time', 'service-booking-manager'); ?>&nbsp;:&nbsp;</h6>
                                            <span><?php echo esc_html(MPWPB_Global_Function::date_format( $days, 'time')); ?></span>
                                        </li>
                                    </ul>
                                </div>
                        <?php
                            }
                        }
                        if (sizeof($extra_service) > 0) { ?>
                        <div class="dLayout_xs">
                            <h5 class="mB_xs"><?php esc_html_e('Extra Services', 'service-booking-manager'); ?></h5>
							<?php foreach ($extra_service as $service) { ?>
                                <div class="divider"></div>
                                <div class="dFlex">
                                    <h6><?php esc_html_e('Services Name', 'service-booking-manager'); ?>&nbsp;:&nbsp;</h6>
                                    <span><?php echo esc_html($service['ex_name']); ?>
									</span>
                                </div>
                                <div class="dFlex">
                                    <h6><?php esc_html_e('Price', 'service-booking-manager'); ?>&nbsp;:&nbsp;</h6>
                                    <span><?php echo wp_kses_post(' ( ' . MPWPB_Global_Function::wc_price($post_id, $service['ex_price']) . ' x ' . $service['ex_qty'] . ' ) = ' . MPWPB_Global_Function::wc_price($post_id, ($service['ex_price'] * $service['ex_qty']))); ?></span>
                                </div>
							<?php } ?>
                        </div>
					<?php }
                        $staff_name = $cart_item['mpwpb_staff_name'];
                        if( $staff_name ){
                        ?>
                        <div class="dLayout_xs">
                            <h5 class="mB_xs"><?php esc_html_e('Selected Staff', 'service-booking-manager'); ?></h5>
                            <span><?php echo esc_html( $staff_name ); ?>
                        </div>
					<?php }
                        do_action('mpwpb_after_cart_item_display', $cart_item, $post_id);
                    ?>
                </div>
				<?php
			}
			public function wc_order_status_change($order_status, $post_id, $order_id) {
				$args = array(
					'post_type' => 'mpwpb_booking',
					'posts_per_page' => -1,
					'meta_query' => array(
						'relation' => 'AND',
						array(
							array(
								'key' => 'mpwpb_id',
								'value' => $post_id,
								'compare' => '='
							),
							array(
								'key' => 'mpwpb_order_id',
								'value' => $order_id,
								'compare' => '='
							)
						)
					)
				);
				$loop = new WP_Query($args);
				foreach ($loop->posts as $user) {
					$user_id = $user->ID;
					//echo '<pre>';print_r($user_id);echo '</pre>';
					update_post_meta($user_id, 'mpwpb_order_status', $order_status);
				}
				$args = array(
					'post_type' => 'mpwpb_extra_service_booking',
					'posts_per_page' => -1,
					'meta_query' => array(
						'relation' => 'AND',
						array(
							array(
								'key' => 'mpwpb_id',
								'value' => $post_id,
								'compare' => '='
							),
							array(
								'key' => 'mpwpb_order_id',
								'value' => $order_id,
								'compare' => '='
							)
						)
					)
				);
				$loop = new WP_Query($args);
				foreach ($loop->posts as $user) {
					$user_id = $user->ID;
					update_post_meta($user_id, 'mpwpb_order_status', $order_status);
				}
			}
			//**********************//
			public static function cart_extra_service_info($post_id, $date, $ex_service_types, $ex_service_qty): array {
				$extra_service = array();
				$service_count = sizeof($ex_service_types);
				if ($service_count > 0) {
					$count = 0;
					for ($i = 0; $i < $service_count; $i++) {
						if ($ex_service_types[$i]) {
							$ex_price = MPWPB_Function::get_extra_price($post_id, $ex_service_types[$i]);
							$extra_service[$count]['ex_name'] = $ex_service_types[$i];
							$extra_service[$count]['ex_price'] = $ex_price;
							$extra_service[$count]['ex_qty'] = $ex_service_qty[$i];
							$extra_service[$count]['mpwpb_date'] = $date ?? '';
							$count++;
						}
					}
				}
				return $extra_service;
			}
			public static function get_cart_total_price($post_id, $all_service, $ex_service_types, $ex_service_qty, $ex_service_group) {
				$price = 0;
				if (is_array($all_service) && sizeof($all_service)) {
					foreach ($all_service as $service) {
						$price = $price + $service['price'] * $service['qty'];
					}
				}
				$ex_price = 0;
				$service_count = sizeof($ex_service_types);
				if ($service_count > 0) {
					for ($i = 0; $i < $service_count; $i++) {
						if ($ex_service_types[$i]) {
							$group_name = array_key_exists($i, $ex_service_group) ? $ex_service_group[$i] : '';
							$ex_price = $ex_price + MPWPB_Function::get_extra_price($post_id, $ex_service_types[$i], $group_name) * $ex_service_qty[$i];
						}
					}
				}
				$total_price = $price + $ex_price;
				return max(0, $total_price);
			}
			public static function add_cpt_data($cpt_name, $title, $meta_data = array(), $status = 'publish', $cat = array()) {
				$new_post = array(
					'post_title' => $title,
					'post_content' => '',
					'post_category' => $cat,
					'tags_input' => array(),
					'post_status' => $status,
					'post_type' => $cpt_name
				);
				wp_reset_postdata();
				$post_id = wp_insert_post($new_post);
				if (sizeof($meta_data) > 0) {
					foreach ($meta_data as $key => $value) {
						update_post_meta($post_id, $key, $value);
					}
				}
				if ($cpt_name == 'mpwpb_booking') {
					$pin = $meta_data['mpwpb_user_id'] . $meta_data['mpwpb_order_id'] . $meta_data['mpwpb_id'] . $post_id;
					update_post_meta($post_id, 'mpwpb_pin', $pin);
					// Mark frontend orders as not backend orders
					update_post_meta($post_id, 'mpwpb_backend_order', 'no');
				}
				wp_reset_postdata();
			}
			/****************************/
			public function mpwpb_add_to_cart() {
				if (isset($_POST['nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'mpwpb_nonce')) {
					$link_id = isset($_POST['link_id']) ? sanitize_text_field(wp_unslash($_POST['link_id'])) : '';
					if (!MPWPB_Global_Function::is_wc_payment_mode()) {
						echo esc_url(MPWPB_Native_Checkout::add_to_cart($link_id));
						die();
					}
					if (MPWPB_Global_Function::get_payment_setting('wc_require_login') === 'on' && !is_user_logged_in()) {
						echo esc_url(wp_login_url(wp_get_referer() ?: home_url('/')));
						die();
					}
					// $link_id is normally already the linked hidden WC product id
					// (next_date_time.php reads the 'link_wc_product' meta), but
					// that meta is only ever written while WC mode was active when
					// the service was last saved (Admin/MPWPB_Hidden_Product.php) --
					// a service created/edited while Payment Method was set to
					// Custom instead falls back to its own raw service post id
					// there, which WC()->cart->add_to_cart() can't add (wrong post
					// type). Resolve/create the real hidden product now rather than
					// silently failing to add to cart.
					if (get_post_type($link_id) === MPWPB_Function::get_cpt() && class_exists('MPWPB_Hidden_Product')) {
						$link_id = MPWPB_Hidden_Product::ensure_hidden_product($link_id);
					}
					$product_id = apply_filters('woocommerce_add_to_cart_product_id', $link_id);
					$quantity = 1;
					$passed_validation = apply_filters('woocommerce_add_to_cart_validation', true, $product_id, $quantity);
					$product_status = get_post_status($product_id);
					WC()->cart->empty_cart();
					if ($passed_validation && WC()->cart->add_to_cart($product_id, 1) && 'publish' === $product_status) {
						$redirect_mode = MPWPB_Global_Function::get_payment_setting('wc_add_to_cart_redirect', 'checkout');
						echo esc_url($redirect_mode === 'cart' ? wc_get_cart_url() : wc_get_checkout_url());
					}
				}
				die();
			}

            public static function calculate_discounted_total( $price, $recurringCount, $discountPercent ) {
                $total = $price * $recurringCount;
                $discountAmount = ($total * $discountPercent) / 100;
                $finalTotal = $total - $discountAmount;
                return round($finalTotal, 2);
            }
		}
		new MPWPB_Woocommerce();
	}
