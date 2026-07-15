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
				add_action('woocommerce_cart_calculate_fees', array($this, 'add_booking_coupon_discount'), 90, 1);
				add_filter('woocommerce_cart_item_thumbnail', array($this, 'cart_item_thumbnail'), 90, 3);
				add_filter('woocommerce_get_item_data', array($this, 'get_item_data'), 90, 2);
				//************//
				//add_filter('woocommerce_add_to_cart_redirect', [$this, 'add_to_cart_redirect'], 10, 2);
				//************//
				add_action('woocommerce_after_checkout_validation', array($this, 'after_checkout_validation'));
				add_action('woocommerce_store_api_checkout_update_order_meta', array($this, 'validate_store_api_checkout'));
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
			private static function order_contains_booking($order): bool {
				if (!$order instanceof WC_Order) {
					return false;
				}
				foreach ($order->get_items() as $item) {
					if (get_post_type(absint($item->get_meta('_mpwpb_id', true))) === MPWPB_Function::get_cpt()) {
						return true;
					}
				}
				return false;
			}
			public function filter_return_url($return_url, $order) {
				if (!self::order_contains_booking($order) || MPWPB_Global_Function::get_payment_setting('wc_order_confirm_redirect', 'default') !== 'plugin_thank_you') {
					return $return_url;
				}
				return add_query_arg([
					'mpwpb_wc_thankyou' => '1',
					'mpwpb_order' => $order->get_id(),
					'mpwpb_order_key' => $order->get_order_key(),
				], home_url('/'));
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
				$order_key = isset($_GET['mpwpb_order_key']) ? wc_clean(wp_unslash($_GET['mpwpb_order_key'])) : '';
				$order = $order_id ? wc_get_order($order_id) : null;
				$valid_order = $order
					&& self::order_contains_booking($order)
					&& $order_key !== ''
					&& hash_equals((string) $order->get_order_key(), (string) $order_key)
					&& (!is_user_logged_in() || !$order->get_customer_id() || (int) $order->get_customer_id() === get_current_user_id() || current_user_can('manage_woocommerce'));
				get_header();
				echo '<div class="mpwpb_style" style="max-width:640px;margin:40px auto;">';
				if ($valid_order) {
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
			 * Resolve either a service post or its linked hidden WooCommerce
			 * product to the real service post. AJAX input must never be allowed
			 * to turn this endpoint into a generic add-any-product endpoint.
			 */
			public static function resolve_service_id($candidate): int {
				$candidate = absint($candidate);
				if ($candidate && get_post_type($candidate) === MPWPB_Function::get_cpt()) {
					return $candidate;
				}
				$service_id = $candidate ? absint(get_post_meta($candidate, 'link_mpwpb_id', true)) : 0;
				return $service_id && get_post_type($service_id) === MPWPB_Function::get_cpt() ? $service_id : 0;
			}

			private static function request_array($key): array {
				if (!isset($_POST[$key]) || !is_array($_POST[$key])) {
					return array();
				}
				return array_map('sanitize_text_field', wp_unslash($_POST[$key]));
			}

			private static function normalize_datetime($value): string {
				$value = trim((string) $value);
				if (!preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}(?::\d{2})?$/', $value)) {
					return '';
				}
				$date = DateTime::createFromFormat('!Y-m-d H:i:s', strlen($value) === 19 ? $value : $value . ':00');
				$errors = DateTime::getLastErrors();
				if (!$date || (is_array($errors) && ($errors['warning_count'] || $errors['error_count']))) {
					return '';
				}
				return $date->format('Y-m-d H:i');
			}

			private static function is_open_date($post_id, $date, $allow_recurring_future = false): bool {
				if (in_array($date, MPWPB_Function::get_date($post_id), true)) {
					return true;
				}
				if (!$allow_recurring_future || MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_date_type', 'repeated') !== 'repeated') {
					return false;
				}

				$start_date = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_repeated_start_date', current_time('Y-m-d'));
				$repeat_after = max(1, absint(MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_repeated_after', 1)));
				$start = strtotime($start_date . ' 00:00:00');
				$check = strtotime($date . ' 00:00:00');
				if (!$start || !$check || $check < $start || ((int) floor(($check - $start) / DAY_IN_SECONDS)) % $repeat_after !== 0) {
					return false;
				}

				$off_days = array_filter(array_map('strtolower', array_map('trim', explode(',', (string) MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_off_days', '')))));
				$off_dates = array_map(
					static function ($off_date) {
						return date_i18n('Y-m-d', strtotime($off_date));
					},
					(array) MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_off_dates', array())
				);
				return !in_array(strtolower(date('l', $check)), $off_days, true) && !in_array($date, $off_dates, true);
			}

			private static function validate_datetime($post_id, $datetime, $allow_recurring_future = false): bool {
				$normalized = self::normalize_datetime($datetime);
				if (!$normalized) {
					return false;
				}
				$date = substr($normalized, 0, 10);
				if (!self::is_open_date($post_id, $date, $allow_recurring_future)) {
					return false;
				}
				$slots = array_map(array(__CLASS__, 'normalize_datetime'), MPWPB_Function::get_time_slot($post_id, $date));
				return in_array($normalized, $slots, true) && MPWPB_Function::get_total_available($post_id, $normalized) > 0;
			}

			private static function validate_staff($post_id, $staff_id, array $dates): bool {
				$staff_id = absint($staff_id);
				if (!$staff_id) {
					return true;
				}
				$user = get_userdata($staff_id);
				if (!$user || !in_array('mpwpb_staff', (array) $user->roles, true)) {
					return false;
				}
				if (get_post_meta($post_id, 'mpwpb_staff_member_add', true) !== 'on') {
					return false;
				}
				$selected = get_post_meta($post_id, 'mpwpb_selected_staff_ids', true);
				$selected_ids = array();
				if (is_array($selected)) {
					array_walk_recursive($selected, static function ($selected_id) use (&$selected_ids) {
						$selected_ids[] = absint($selected_id);
					});
				}
				$selected = array_values(array_unique(array_filter($selected_ids)));
				if (!in_array($staff_id, $selected, true)) {
					return false;
				}
				foreach ($dates as $datetime) {
					$date = substr($datetime, 0, 10);
					$time = (int) substr($datetime, 11, 2) + ((int) substr($datetime, 14, 2) / 60);
					if (!MPWPB_Staff_Booking::get_date_check($staff_id, $date, (string) $time)
						|| !MPWPB_Staff_Booking::mpwpb_is_staff_available_time($staff_id, (string) $time, $date)) {
						return false;
					}
				}
				return true;
			}

			/** Validate and normalize every customer-controlled booking choice. */
			public static function validate_booking_request($candidate) {
				$post_id = self::resolve_service_id($candidate);
				if (!$post_id || get_post_status($post_id) !== 'publish') {
					return new WP_Error('mpwpb_invalid_service', esc_html__('This booking service is not available.', 'service-booking-manager'));
				}

				$services = self::request_array('mpwpb_service');
				$service_qty = self::request_array('mpwpb_service_qty');
				$configured_services = (array) MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_service', array());
				$max_service_qty = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_multiple_service_select', 'off') === 'on' ? 10 : 1;
				if (!$services) {
					return new WP_Error('mpwpb_missing_selection', esc_html__('Please select at least one service.', 'service-booking-manager'));
				}
				if (count($services) !== count(array_unique(array_map('absint', $services)))) {
					return new WP_Error('mpwpb_duplicate_selection', esc_html__('The same service cannot be selected more than once.', 'service-booking-manager'));
				}
				foreach ($services as $service_id) {
					$service_id = absint($service_id);
					$quantity = isset($service_qty[$service_id]) ? absint($service_qty[$service_id]) : 1;
					if (!$service_id || !isset($configured_services[$service_id - 1]) || empty($configured_services[$service_id - 1]['name']) || $quantity < 1 || $quantity > $max_service_qty) {
						return new WP_Error('mpwpb_invalid_selection', esc_html__('The selected service or quantity is invalid.', 'service-booking-manager'));
					}
				}
				$category = isset($_POST['mpwpb_category']) ? absint($_POST['mpwpb_category']) : 0;
				$sub_category = isset($_POST['mpwpb_sub_category']) ? absint($_POST['mpwpb_sub_category']) : 0;
				if (($category && MPWPB_Function::get_category_name($post_id, $category) === '')
					|| ($sub_category && MPWPB_Function::get_sub_category_name($post_id, $sub_category) === '')) {
					return new WP_Error('mpwpb_invalid_category', esc_html__('The selected service category is invalid.', 'service-booking-manager'));
				}

				$date_value = isset($_POST['mpwpb_date']) ? sanitize_text_field(wp_unslash($_POST['mpwpb_date'])) : '';
				$dates = array_values(array_filter(array_map(array(__CLASS__, 'normalize_datetime'), explode(',', $date_value))));
				$is_recurring = isset($_POST['is_recurring_on']) && sanitize_text_field(wp_unslash($_POST['is_recurring_on'])) === 'on';
				$recurring_count = isset($_POST['recurringCount']) ? absint($_POST['recurringCount']) : 1;
				$max_recurring = max(2, absint(MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_max_recurring_count', 26)));
				if (!$dates || count($dates) !== count(explode(',', $date_value)) || count($dates) !== count(array_unique($dates))) {
					return new WP_Error('mpwpb_invalid_date', esc_html__('The selected booking date or time is invalid.', 'service-booking-manager'));
				}
				if ($is_recurring) {
					if (MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_enable_recurring', 'no') !== 'yes' || $recurring_count < 2 || $recurring_count > $max_recurring || count($dates) !== $recurring_count) {
						return new WP_Error('mpwpb_invalid_recurring', esc_html__('The recurring booking selection is invalid.', 'service-booking-manager'));
					}
				} elseif (count($dates) !== 1) {
					return new WP_Error('mpwpb_invalid_date_count', esc_html__('Please select one booking date and time.', 'service-booking-manager'));
				}
				foreach ($dates as $index => $datetime) {
					if (!self::validate_datetime($post_id, $datetime, $is_recurring && $index > 0)) {
						return new WP_Error('mpwpb_unavailable_date', esc_html__('One of the selected booking times is no longer available.', 'service-booking-manager'));
					}
				}

				$extra_names = array_values(self::request_array('mpwpb_extra_service_type'));
				$extra_qty = array_values(self::request_array('mpwpb_extra_service_qty'));
				$configured_extras = (array) MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_extra_service', array());
				$extras_by_name = array();
				foreach ($configured_extras as $extra) {
					if (!empty($extra['name'])) {
						$extras_by_name[(string) $extra['name']] = max(1, absint($extra['qty'] ?? 1));
					}
				}
				if ($extra_names && MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_extra_service_active', 'off') !== 'on') {
					return new WP_Error('mpwpb_invalid_extra', esc_html__('Extra services are not available for this booking.', 'service-booking-manager'));
				}
				foreach ($extra_names as $key => $name) {
					$quantity = isset($extra_qty[$key]) ? absint($extra_qty[$key]) : 0;
					if (!isset($extras_by_name[$name]) || $quantity < 1 || $quantity > $extras_by_name[$name]) {
						return new WP_Error('mpwpb_invalid_extra', esc_html__('An extra service or quantity is invalid.', 'service-booking-manager'));
					}
				}

				$staff_id = isset($_POST['mpwpb_staff_member']) ? absint($_POST['mpwpb_staff_member']) : 0;
				if (!self::validate_staff($post_id, $staff_id, $dates)) {
					return new WP_Error('mpwpb_invalid_staff', esc_html__('The selected staff member is not available.', 'service-booking-manager'));
				}
				return true;
			}

			/** Recheck a stored cart item immediately before an order is created. */
			public static function validate_stored_booking_item($item) {
				if (!is_array($item)) {
					return new WP_Error('mpwpb_invalid_cart_item', esc_html__('The booking cart item is invalid.', 'service-booking-manager'));
				}
				$post_id = absint($item['mpwpb_id'] ?? 0);
				$date_segments = explode(',', (string) ($item['mpwpb_date'] ?? ''));
				$dates = array_values(array_filter(array_map(array(__CLASS__, 'normalize_datetime'), $date_segments)));
				if (!$post_id || get_post_status($post_id) !== 'publish' || !$dates || count($dates) !== count($date_segments) || count($dates) !== count(array_unique($dates))) {
					return new WP_Error('mpwpb_invalid_cart_item', esc_html__('The booking cart item is invalid.', 'service-booking-manager'));
				}
				foreach ($dates as $index => $datetime) {
					if (!self::validate_datetime($post_id, $datetime, $index > 0)) {
						return new WP_Error('mpwpb_unavailable_cart_item', esc_html__('A booking time in your cart is no longer available. Please choose another time.', 'service-booking-manager'));
					}
				}
				if (!self::validate_staff($post_id, $item['mpwpb_staff_member_id'] ?? 0, $dates)) {
					return new WP_Error('mpwpb_unavailable_staff', esc_html__('The selected staff member is no longer available.', 'service-booking-manager'));
				}
				return true;
			}
			/**
			 * Builds the booking selection array from the current $_POST request.
			 * Shared by the WooCommerce cart bridge (add_cart_item_data) and the
			 * native (non-WooCommerce) add-to-cart path so both stay in sync.
			 */
			public static function build_booking_item_from_request($product_id): array {
				$cart_item_data = array();
				$product_id = self::resolve_service_id($product_id);
				if (!$product_id || is_wp_error(self::validate_booking_request($product_id))) {
					return array();
				}
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
						$services = self::request_array('mpwpb_service');
						$services_qty = self::request_array('mpwpb_service_qty');
						$date = isset($_POST['mpwpb_date']) ? sanitize_text_field(wp_unslash($_POST['mpwpb_date'])) : '';
						$all_service = [];
						if (is_array($services) && sizeof($services)) {
							foreach ($services as $key => $service) {
								$all_service[$key]['service_id'] = $service; // 1-based index, needed for coupon service matching
								$all_service[$key]['name'] = MPWPB_Function::get_service_name($product_id, $service);
								$all_service[$key]['price'] = MPWPB_Function::get_price($product_id, $service, $date);
								$all_service[$key]['qty'] = isset($services_qty[$service]) ? max(1, absint($services_qty[$service])) : 1;
							}
						}

						$ex_service_types = array_values(self::request_array('mpwpb_extra_service_type'));
						$ex_service_qty = array_values(self::request_array('mpwpb_extra_service_qty'));
						$ex_service_group = array_fill(0, count($ex_service_types), '');
						$is_recurring_on = isset($_POST['is_recurring_on']) ? sanitize_text_field( wp_unslash( $_POST['is_recurring_on'] ) ) : 'off';
						$total_price = self::get_cart_total_price($product_id, $all_service, $ex_service_types, $ex_service_qty, $ex_service_group);
                        if( $is_recurring_on === 'on' ){
                            $recurringCount = isset($_POST['recurringCount']) ? sanitize_text_field( wp_unslash( $_POST['recurringCount'] ) ) : 1;
                            $total_price = self::calculate_discounted_total( $total_price, $recurringCount, $discountPercent );
                        }

                        $mpwpb_staff_member_id = isset($_POST['mpwpb_staff_member']) ? sanitize_text_field(wp_unslash($_POST['mpwpb_staff_member'])) : '';
                        if( $mpwpb_staff_member_id ){
							$mpwpb_staff_data = get_userdata($mpwpb_staff_member_id);
							$mpwpb_staff_member = $mpwpb_staff_data ? $mpwpb_staff_data->display_name : '';
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
						// Full/Partial choice made once on the wizard's "Proceed to
						// Checkout" step (see templates/registration/next_date_time.php) --
						// only trusted as 'partial' when the feature is actually
						// enabled, so a stale/tampered request can't discount a
						// checkout that shouldn't have this option at all.
						$payment_choice = isset($_POST['mpwpb_payment_choice']) ? sanitize_text_field(wp_unslash($_POST['mpwpb_payment_choice'])) : 'full';
						if ($payment_choice !== 'partial' || !class_exists('MPWPB_Partial_Payment') || !MPWPB_Partial_Payment::is_enabled()) {
							$payment_choice = 'full';
						}
						$cart_item_data['mpwpb_payment_choice'] = $payment_choice;
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
						$total_price = max(0, (float) $value['mpwpb_tp']);
						// Only the DEPOSIT is actually charged through WooCommerce's own
						// checkout when Partial was chosen -- the remaining balance is
						// collected later via a separate linked order (see
						// MPWPB_Partial_Payment::create_balance_order()). The coupon is
						// added separately as a negative fee so Checkout can show the
						// original subtotal, discount and final total as distinct values.
						if (class_exists('MPWPB_Partial_Payment') && ($value['mpwpb_payment_choice'] ?? 'full') === 'partial') {
							$total_price = MPWPB_Partial_Payment::get_deposit_amount($total_price);
						}
						$value['data']->set_price($total_price);
						$value['data']->set_regular_price($total_price);
						$value['data']->set_sale_price($total_price);
						$value['data']->set_sold_individually('yes');
						$value['data']->get_price();
					}
				}
			}

			/**
			 * Adds the booking-native coupon to WooCommerce's totals as a real
			 * negative adjustment. Previously the product price was silently reduced
			 * and the coupon UI displayed the same amount again, making Checkout
			 * Blocks appear to ignore the coupon.
			 */
			public function add_booking_coupon_discount($cart_object): void {
				if (!$cart_object || (is_admin() && !wp_doing_ajax())) {
					return;
				}
				$adjustment = 0.0;
				$coupon_codes = [];
				foreach ($cart_object->get_cart() as $value) {
					$post_id = $value['mpwpb_id'] ?? 0;
					$discount = max(0, (float) ($value['mpwpb_discount_amount'] ?? 0));
					if (get_post_type($post_id) !== MPWPB_Function::get_cpt() || !isset($value['mpwpb_tp']) || $discount <= 0) {
						continue;
					}
					$gross_total = max(0, (float) $value['mpwpb_tp']);
					$net_total = max(0, $gross_total - $discount);
					if (class_exists('MPWPB_Partial_Payment') && ($value['mpwpb_payment_choice'] ?? 'full') === 'partial') {
						$gross_total = MPWPB_Partial_Payment::get_deposit_amount($gross_total);
						$net_total = MPWPB_Partial_Payment::get_deposit_amount($net_total);
					}
					$adjustment += max(0, $gross_total - $net_total);
					if (!empty($value['mpwpb_coupon_code'])) {
						$coupon_codes[] = sanitize_text_field($value['mpwpb_coupon_code']);
					}
				}
				$adjustment = round($adjustment, wc_get_price_decimals());
				if ($adjustment <= 0) {
					return;
				}
				$coupon_codes = array_values(array_unique(array_filter($coupon_codes)));
				$label = !empty($coupon_codes)
					? sprintf(
						/* translators: %s: booking coupon code. */
						esc_html__('Booking coupon (%s)', 'service-booking-manager'),
						implode(', ', $coupon_codes)
					)
					: esc_html__('Booking coupon', 'service-booking-manager');
				$cart_object->add_fee($label, -$adjustment, false);
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
						$validation = self::validate_stored_booking_item($values);
						if (is_wp_error($validation)) {
							wc_add_notice($validation->get_error_message(), 'error');
						}
						if (!empty($values['mpwpb_coupon_code']) && class_exists('MPWPB_Coupon_Validator')) {
							$email = isset($_POST['billing_email']) ? sanitize_email(wp_unslash($_POST['billing_email'])) : '';
							$context = MPWPB_Coupon_Validator::build_context($values, $email, get_current_user_id());
							$coupon_validation = MPWPB_Coupon_Validator::validate($values['mpwpb_coupon_code'], $context);
							if (!$coupon_validation['valid']) {
								wc_add_notice(sprintf(
									/* translators: %s: coupon validation failure reason */
									esc_html__('Your coupon is no longer valid: %s', 'service-booking-manager'),
									esc_html($coupon_validation['message'])
								), 'error');
							}
						}
						//wc_add_notice( __( "custom_notice", 'fake_error' ), 'error');
						do_action('mpwpb_validate_cart_item', $values, $post_id);
					}
				}
			}
			/** Blocks/Store API equivalent of the classic checkout validation hook. */
			public function validate_store_api_checkout(): void {
				if (!function_exists('WC') || !WC()->cart) {
					return;
				}
				foreach (WC()->cart->get_cart() as $values) {
					$post_id = absint($values['mpwpb_id'] ?? 0);
					if (get_post_type($post_id) !== MPWPB_Function::get_cpt()) {
						continue;
					}
					$validation = self::validate_stored_booking_item($values);
					if (is_wp_error($validation)) {
						throw new Exception($validation->get_error_message());
					}
					if (!empty($values['mpwpb_coupon_code']) && class_exists('MPWPB_Coupon_Validator')) {
						$email = WC()->customer ? sanitize_email(WC()->customer->get_billing_email()) : '';
						$context = MPWPB_Coupon_Validator::build_context($values, $email, get_current_user_id());
						$coupon_validation = MPWPB_Coupon_Validator::validate($values['mpwpb_coupon_code'], $context);
						if (!$coupon_validation['valid']) {
							throw new Exception(sprintf(
								/* translators: %s: coupon validation failure reason */
								esc_html__('Your coupon is no longer valid: %s', 'service-booking-manager'),
								esc_html($coupon_validation['message'])
							));
						}
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
					$coupon_code = $values['mpwpb_coupon_code'] ?? '';
					$discount_amount = (float) ($values['mpwpb_discount_amount'] ?? 0);
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
                            $item->add_meta_data(esc_html__('Time ', 'service-booking-manager'), esc_html(MPWPB_Global_Function::date_format($days, 'time')));
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
					if ($coupon_code) {
						$item->add_meta_data(esc_html__('Coupon Applied ', 'service-booking-manager'), $coupon_code);
						$item->add_meta_data(esc_html__('Discount ', 'service-booking-manager'), MPWPB_Global_Function::wc_price($post_id, $discount_amount));
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
					$item->add_meta_data('_mpwpb_coupon_code', $coupon_code);
					$item->add_meta_data('_mpwpb_discount_amount', $discount_amount);

					$payment_choice = $values['mpwpb_payment_choice'] ?? 'full';
					$net_total = max(0, ((float) $total_price) - $discount_amount);
					$split = class_exists('MPWPB_Partial_Payment') ? MPWPB_Partial_Payment::split_total($net_total, $payment_choice) : ['deposit' => $net_total, 'due' => 0];
					$item->add_meta_data('_mpwpb_payment_choice', $payment_choice);
					$item->add_meta_data('_mpwpb_deposit_amount', $split['deposit']);
					$item->add_meta_data('_mpwpb_amount_due', $split['due']);

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
						'coupon_code' => wc_get_order_item_meta($item_id, '_mpwpb_coupon_code') ?: '',
						'discount_amount' => (float) wc_get_order_item_meta($item_id, '_mpwpb_discount_amount'),
						'payment_choice' => wc_get_order_item_meta($item_id, '_mpwpb_payment_choice') ?: 'full',
						'amount_due' => (float) wc_get_order_item_meta($item_id, '_mpwpb_amount_due'),
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
				// There's no real WC_Coupon/coupon line item behind this discount (the
				// coupon engine is booking-native, see MPWPB_Coupon_Validator) -- an
				// order note is the one place an admin can see which coupon was used.
				foreach ($line_items as $line_item) {
					if (!empty($line_item['coupon_code'])) {
						$order->add_order_note(sprintf(
							/* translators: 1: coupon code, 2: formatted discount amount */
							esc_html__('Coupon "%1$s" applied — %2$s discount.', 'service-booking-manager'),
							$line_item['coupon_code'],
							wp_strip_all_tags(MPWPB_Global_Function::format_price($line_item['discount_amount']))
						));
					}
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

					$coupon_code = $line_item['coupon_code'] ?? '';
					$discount_amount = (float) ($line_item['discount_amount'] ?? 0);
					// One usage per completed order, not once per recurring occurrence --
					// counted here (this function only ever runs once per order, guarded
					// by its two callers) rather than inside the per-occurrence loop below.
					if ($coupon_code) {
						MPWPB_Coupon_Usage::record_usage(MPWPB_Coupon_Function::find_by_code($coupon_code));
					}

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
						$data['mpwpb_coupon_code'] = $coupon_code;
						$data['mpwpb_discount_amount'] = $discount_amount;
						$data['mpwpb_service_info'] = $ex_service_infos;
						$data['mpwpb_order_id'] = $order_id;
						$data['mpwpb_payment_method'] = $payment_method;
						// mpwpb_order_status stays a DERIVED display value once partial
						// payment is involved -- mpwpb_real_order_status is the ground
						// truth updated by wc_order_status_change()/native status syncs,
						// recomputed through compute_display_status() every time either
						// it or the amount due changes, so a later status transition
						// can't silently clobber 'partially-paid' back to the raw status
						// while a balance is still owed.
						$amount_due = (float) ($line_item['amount_due'] ?? 0);
						$total_price_num = (float) ($line_item['total_price'] ?? 0);
						$net_total_num = max(0, $total_price_num - $discount_amount);
						$data['mpwpb_payment_choice'] = $line_item['payment_choice'] ?? 'full';
						$data['mpwpb_amount_due'] = $amount_due;
						$data['mpwpb_amount_paid'] = max(0, round($net_total_num - $amount_due, 2));
						$data['mpwpb_real_order_status'] = $order_status;
						$data['mpwpb_order_status'] = class_exists('MPWPB_Partial_Payment') ? MPWPB_Partial_Payment::compute_display_status($order_status, $amount_due) : $order_status;
						$data['mpwpb_user_id'] = $user_id ?: '';
						$data['mpwpb_extra_service_info'] = $ex_service_infos;
						$data['mpwpb_billing_name'] = trim(($billing['first_name'] ?? '') . ' ' . ($billing['last_name'] ?? ''));
						$data['mpwpb_billing_email'] = $billing['email'] ?? '';
						$data['mpwpb_billing_phone'] = $billing['phone'] ?? '';
						$data['mpwpb_billing_address'] = trim(($billing['address_1'] ?? '') . ' ' . ($billing['address_2'] ?? ''));
						$booking_data = apply_filters('add_mpwpb_booking_data', $data, $post_id);
						$new_booking_id = self::add_cpt_data('mpwpb_booking', $booking_data['mpwpb_billing_name'], $booking_data);
						if ($new_booking_id && $amount_due > 0 && class_exists('MPWPB_Booking_History') && class_exists('MPWPB_Partial_Payment')) {
							MPWPB_Booking_History::log(
								$new_booking_id,
								MPWPB_Booking_History::ACTION_DEPOSIT_RECEIVED,
								MPWPB_Partial_Payment::format_price_plain($total_price_num),
								MPWPB_Partial_Payment::format_price_plain($amount_due),
								sprintf(
									/* translators: %s: amount charged as a deposit */
									esc_html__('Deposit of %s paid at checkout.', 'service-booking-manager'),
									MPWPB_Partial_Payment::format_price_plain($data['mpwpb_amount_paid'])
								)
							);
						}
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
					if (class_exists('MPWPB_Partial_Payment')) {
						MPWPB_Partial_Payment::sync_display_status($user_id, $order_status);
					} else {
						update_post_meta($user_id, 'mpwpb_order_status', $order_status);
					}
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
				return $post_id;
			}
			/****************************/
			public function mpwpb_add_to_cart() {
				if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'mpwpb_nonce')) {
					wp_send_json_error(array('message' => esc_html__('Security check failed. Please refresh the page and try again.', 'service-booking-manager')), 403);
				}
				$link_id = isset($_POST['link_id']) ? absint($_POST['link_id']) : 0;
				$service_id = self::resolve_service_id($link_id);
				$validation = self::validate_booking_request($service_id);
				if (is_wp_error($validation)) {
					wp_send_json_error(array('message' => $validation->get_error_message()), 400);
				}
					if (!MPWPB_Global_Function::is_wc_payment_mode()) {
						$url = MPWPB_Native_Checkout::add_to_cart($service_id);
						if (!$url) {
							wp_send_json_error(array('message' => esc_html__('The booking could not be added. Please try again.', 'service-booking-manager')), 400);
						}
						echo esc_url($url);
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
						$link_id = MPWPB_Hidden_Product::ensure_hidden_product($service_id);
					}
					$product_id = apply_filters('woocommerce_add_to_cart_product_id', $link_id);
					$quantity = 1;
					$passed_validation = apply_filters('woocommerce_add_to_cart_validation', true, $product_id, $quantity);
					$product_status = get_post_status($product_id);
					WC()->cart->empty_cart();
					if ($passed_validation && WC()->cart->add_to_cart($product_id, 1) && 'publish' === $product_status) {
						$redirect_mode = MPWPB_Global_Function::get_payment_setting('wc_add_to_cart_redirect', 'checkout');
						echo esc_url($redirect_mode === 'cart' ? wc_get_cart_url() : wc_get_checkout_url());
						die();
					}
					wp_send_json_error(array('message' => esc_html__('The booking could not be added to the cart. Please try again.', 'service-booking-manager')), 400);
			}

            public static function calculate_discounted_total( $price, $recurringCount, $discountPercent ) {
				$price = max(0, (float) $price);
				$recurringCount = max(1, absint($recurringCount));
				$discountPercent = max(0, min(100, (float) $discountPercent));
				$total = $price * $recurringCount;
                $discountAmount = ($total * $discountPercent) / 100;
                $finalTotal = $total - $discountAmount;
                return round($finalTotal, 2);
            }
		}
		new MPWPB_Woocommerce();
	}
