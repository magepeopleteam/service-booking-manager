<?php
	/*
	 * Native (non-WooCommerce) order entity.
	 * A lightweight WC_Order replacement used only when WooCommerce is
	 * inactive: stores billing info + a snapshot of the booked line item(s)
	 * for the native checkout flow, and is the trigger that creates the
	 * real mpwpb_booking / mpwpb_extra_service_booking posts once paid.
	 */
	if (!defined('ABSPATH')) {
		die;
	} // Cannot access pages directly.
	if (!class_exists('MPWPB_Native_Order')) {
		class MPWPB_Native_Order {
			const CPT = 'mpwpb_order';

			public function __construct() {
				add_action('init', [$this, 'register_cpt']);
			}
			public function register_cpt(): void {
				register_post_type(self::CPT, [
					'label' => esc_html__('Native Bookings Orders', 'service-booking-manager'),
					'public' => false,
					'show_ui' => true,
					'show_in_menu' => false,
					'capability_type' => 'post',
					'supports' => ['title'],
					'rewrite' => false,
					'query_var' => false,
				]);
			}
			/**
			 * @param array $args {
			 *     @type array  $billing     first_name,last_name,email,phone,address_1,address_2
			 *     @type array  $line_items  Same shape MPWPB_Native_Cart holds (mpwpb_id, mpwpb_date, mpwpb_service, mpwpb_tp, mpwpb_extra_service_info, mpwpb_staff_member_id, ...)
			 *     @type float  $total
			 *     @type string $currency
			 * }
			 */
			public static function create(array $args): int {
				$billing = $args['billing'] ?? [];
				$title = trim(($billing['first_name'] ?? '') . ' ' . ($billing['last_name'] ?? '')) ?: esc_html__('Native Booking Order', 'service-booking-manager');
				$order_id = wp_insert_post([
					'post_title' => $title,
					'post_type' => self::CPT,
					'post_status' => 'publish',
				]);
				if (is_wp_error($order_id) || !$order_id) {
					return 0;
				}
				update_post_meta($order_id, 'mpwpb_order_status', 'pending');
				update_post_meta($order_id, 'mpwpb_payment_method', $args['payment_method'] ?? '');
				update_post_meta($order_id, 'mpwpb_gateway_txn_id', $args['txn_id'] ?? '');
				update_post_meta($order_id, 'mpwpb_currency', $args['currency'] ?? '');
				update_post_meta($order_id, 'mpwpb_total', $args['total'] ?? 0);
				update_post_meta($order_id, 'mpwpb_user_id', get_current_user_id());
				update_post_meta($order_id, 'mpwpb_billing_first_name', $billing['first_name'] ?? '');
				update_post_meta($order_id, 'mpwpb_billing_last_name', $billing['last_name'] ?? '');
				update_post_meta($order_id, 'mpwpb_billing_email', $billing['email'] ?? '');
				update_post_meta($order_id, 'mpwpb_billing_phone', $billing['phone'] ?? '');
				update_post_meta($order_id, 'mpwpb_billing_address_1', $billing['address_1'] ?? '');
				update_post_meta($order_id, 'mpwpb_billing_address_2', $billing['address_2'] ?? '');
				update_post_meta($order_id, 'mpwpb_line_items', $args['line_items'] ?? []);
				return (int) $order_id;
			}
			public static function set_status($order_id, $status): void {
				update_post_meta($order_id, 'mpwpb_order_status', $status);
			}
			public static function mark_paid($order_id, $payment_method, $txn_id): void {
				update_post_meta($order_id, 'mpwpb_order_status', 'processing');
				update_post_meta($order_id, 'mpwpb_payment_method', $payment_method);
				update_post_meta($order_id, 'mpwpb_gateway_txn_id', $txn_id);
			}
			/**
			 * Turns a paid native order into real mpwpb_booking post(s).
			 * Idempotent: safe to call more than once for the same order
			 * (e.g. a retried gateway webhook) — only creates bookings once.
			 */
			public static function process_order($order_id): bool {
				$order_id = (int) $order_id;
				if (!$order_id || get_post_type($order_id) != self::CPT) {
					return false;
				}
				if (get_post_meta($order_id, 'mpwpb_booking_created', true) === 'yes') {
					return true;
				}
				$lock_key = 'mpwpb_order_lock_' . $order_id;
				if (get_transient($lock_key)) {
					return false;
				}
				set_transient($lock_key, 1, 30);
				update_post_meta($order_id, 'mpwpb_booking_created', 'yes');

				$cart_item = get_post_meta($order_id, 'mpwpb_line_items', true);
				$cart_item = is_array($cart_item) ? $cart_item : [];
				$line_items = [];
				if (!empty($cart_item)) {
					$line_items[] = [
						'post_id' => $cart_item['mpwpb_id'] ?? 0,
						'date' => $cart_item['mpwpb_date'] ?? '',
						'staff_term_id' => $cart_item['mpwpb_staff_member_id'] ?? '',
						'category' => $cart_item['mpwpb_category'] ?? '',
						'sub_category' => $cart_item['mpwpb_sub_category'] ?? '',
						'service' => $cart_item['mpwpb_service'] ?? [],
						'total_price' => $cart_item['mpwpb_tp'] ?? '',
						'extra_service_info' => $cart_item['mpwpb_extra_service_info'] ?? [],
					];
				}
				$order_status = get_post_meta($order_id, 'mpwpb_order_status', true) ?: 'processing';
				$payment_method = get_post_meta($order_id, 'mpwpb_payment_method', true);
				$user_id = get_post_meta($order_id, 'mpwpb_user_id', true);
				$billing = [
					'first_name' => get_post_meta($order_id, 'mpwpb_billing_first_name', true),
					'last_name' => get_post_meta($order_id, 'mpwpb_billing_last_name', true),
					'email' => get_post_meta($order_id, 'mpwpb_billing_email', true),
					'phone' => get_post_meta($order_id, 'mpwpb_billing_phone', true),
					'address_1' => get_post_meta($order_id, 'mpwpb_billing_address_1', true),
					'address_2' => get_post_meta($order_id, 'mpwpb_billing_address_2', true),
				];
				MPWPB_Woocommerce::create_bookings_from_data($order_id, $order_status, $payment_method, $user_id, $billing, $line_items);
				delete_transient($lock_key);
				return true;
			}
			//***** Read-only accessors, mirroring the subset of WC_Order used elsewhere in the plugin *****//
			public static function get_status($order_id) {
				return get_post_meta($order_id, 'mpwpb_order_status', true);
			}
			public static function get_total($order_id) {
				return get_post_meta($order_id, 'mpwpb_total', true);
			}
			public static function get_payment_method($order_id) {
				return get_post_meta($order_id, 'mpwpb_payment_method', true);
			}
			public static function get_billing_email($order_id) {
				return get_post_meta($order_id, 'mpwpb_billing_email', true);
			}
		}
		new MPWPB_Native_Order();
	}
