<?php
	/*
   * @Author 		engr.sumonazma@gmail.com
   * Copyright: 	mage-people.com
   */
	if (!defined('ABSPATH')) {
		die;
	} // Cannot access pages directly.

	if (!class_exists('MPWPB_Partial_Payment')) {
		/**
		 * Deposit/partial-payment policy + amount-tracking helpers, shared by
		 * the WooCommerce order path (MPWPB_Woocommerce), the native/Custom
		 * Payment order path (MPWPB_Native_Order/MPWPB_Native_Checkout), and
		 * the admin "Record Payment" action -- so the deposit math and the
		 * paid/due bookkeeping only exist in one place for both systems.
		 *
		 * 'mpwpb_order_status' on a mpwpb_booking post is a DERIVED display
		 * value once partial payment is involved: 'mpwpb_real_order_status'
		 * holds the actual underlying WC/native order status, and
		 * compute_display_status() turns that + the current amount due into
		 * what should actually be shown/filtered on ('partially-paid' while
		 * money is still owed, otherwise the real status unchanged). Every
		 * site that used to write 'mpwpb_order_status' directly must instead
		 * go through sync_display_status() so a later status change (e.g.
		 * on-hold -> processing) can't silently clobber 'partially-paid'
		 * back to the raw status while a balance is still due.
		 */
		class MPWPB_Partial_Payment {

			const TERMINAL_NON_PAYABLE_STATUSES = ['cancelled', 'failed', 'refunded'];

			public function __construct() {
				add_filter('query_vars', [$this, 'add_query_var']);
				add_action('template_redirect', [$this, 'maybe_handle_pay_balance_redirect']);
				// A WC balance order (create_wc_balance_order() below) is a plain
				// WC_Order with a WC_Order_Item_Fee line item, not one of this
				// plugin's own mpwpb-tagged line items -- MPWPB_Woocommerce::
				// order_status_changed() only ever looks at orders that have
				// those, so it never sees a balance order reach a paid status.
				// This is the one place that does.
				add_action('woocommerce_order_status_changed', [$this, 'maybe_apply_wc_balance_payment'], 20, 4);
			}

			public function maybe_apply_wc_balance_payment($order_id, $old_status, $new_status, $order): void {
				if (!$order || !$order->get_meta('_mpwpb_balance_of_order_id')) {
					return;
				}
				if (in_array($new_status, ['processing', 'completed'], true)) {
					self::apply_balance_payment($order_id, true);
				}
			}

			public function add_query_var($vars) {
				$vars[] = 'mpwpb_pay_balance';
				return $vars;
			}

			/**
			 * The one "Pay Balance" entry point for both payment systems --
			 * MPWPB_User_Dashboard::render_booking_actions() links here
			 * (?mpwpb_pay_balance={booking_id}) regardless of which system the
			 * booking was placed under; create_balance_order() itself branches
			 * WC-vs-native and returns the right URL to send the customer to.
			 */
			public function maybe_handle_pay_balance_redirect(): void {
				$booking_id = (int) get_query_var('mpwpb_pay_balance');
				if (!$booking_id || get_post_type($booking_id) !== 'mpwpb_booking') {
					return;
				}
				if (!is_user_logged_in()) {
					wp_safe_redirect(wp_login_url(add_query_arg([])));
					exit;
				}
				// Ownership check -- only the customer this booking belongs to
				// (or an admin) can trigger a balance payment for it.
				$owner_id = (int) get_post_meta($booking_id, 'mpwpb_user_id', true);
				if ($owner_id !== get_current_user_id() && !current_user_can('manage_options')) {
					wp_die(esc_html__('You do not have permission to pay this balance.', 'service-booking-manager'), '', ['response' => 403]);
				}
				$result = self::create_balance_order($booking_id);
				if (is_wp_error($result)) {
					wp_die(esc_html($result->get_error_message()), '', ['response' => 400]);
				}
				wp_safe_redirect($result);
				exit;
			}

			public static function is_enabled(): bool {
				return MPWPB_Global_Function::get_payment_setting('partial_payment_enabled') === 'on';
			}

			/**
			 * Plain-text currency string for contexts that escape their input
			 * (MPWPB_Booking_History's old_date/new_date/note columns, rendered
			 * via esc_html() in MPWPB_Layout_Pro::history_info()) -- wc_price()
			 * returns HTML with the currency symbol as an entity (e.g. &#36;);
			 * wp_strip_all_tags() alone removes the <span> tags but leaves that
			 * entity un-decoded, so a later esc_html() would double-encode it
			 * into a literal "&#36;" on screen instead of "$". Decoding after
			 * stripping avoids that.
			 */
			public static function format_price_plain($amount): string {
				return html_entity_decode(wp_strip_all_tags(MPWPB_Global_Function::wc_price(0, $amount)), ENT_QUOTES, 'UTF-8');
			}

			/**
			 * @param float $net_total Already-discounted total (post-coupon), matching
			 *                         the $net_total pattern used at the WC/native checkout sites.
			 */
			public static function get_deposit_amount($net_total): float {
				$net_total = (float) $net_total;
				if ($net_total <= 0) {
					return 0.0;
				}
				$type = MPWPB_Global_Function::get_payment_setting('partial_payment_type', 'percentage');
				if ($type === 'fixed') {
					$amount = (float) MPWPB_Global_Function::get_payment_setting('partial_payment_fixed_amount', 0);
				} else {
					$percentage = (float) MPWPB_Global_Function::get_payment_setting('partial_payment_percentage', 50);
					$amount = round($net_total * ($percentage / 100), 2);
				}
				return max(0.0, min($amount, $net_total));
			}

			/**
			 * @param string $payment_choice 'full' | 'partial'
			 * @return array{deposit: float, due: float} Both derived from $net_total;
			 *         'full' (or partial payment not enabled) always yields due = 0.
			 */
			public static function split_total($net_total, $payment_choice): array {
				$net_total = (float) $net_total;
				if ($payment_choice === 'partial' && self::is_enabled()) {
					$deposit = self::get_deposit_amount($net_total);
					return ['deposit' => $deposit, 'due' => max(0.0, round($net_total - $deposit, 2))];
				}
				return ['deposit' => $net_total, 'due' => 0.0];
			}

			public static function compute_display_status($real_status, $amount_due): string {
				$amount_due = (float) $amount_due;
				if ($amount_due > 0.005 && !in_array($real_status, self::TERMINAL_NON_PAYABLE_STATUSES, true)) {
					return 'partially-paid';
				}
				return (string) $real_status;
			}

			public static function get_amount_paid($booking_id): float {
				return (float) get_post_meta($booking_id, 'mpwpb_amount_paid', true);
			}

			public static function get_amount_due($booking_id): float {
				return (float) get_post_meta($booking_id, 'mpwpb_amount_due', true);
			}

			/**
			 * Recomputes and writes the booking's DISPLAYED 'mpwpb_order_status'
			 * from its real status + current amount due. Pass $real_status when
			 * the underlying status just changed; omit it to just re-derive off
			 * the amount due changing instead (e.g. a payment was just recorded).
			 */
			public static function sync_display_status($booking_id, $real_status = null): string {
				if ($real_status !== null) {
					update_post_meta($booking_id, 'mpwpb_real_order_status', $real_status);
				} else {
					$real_status = get_post_meta($booking_id, 'mpwpb_real_order_status', true);
				}
				$display_status = self::compute_display_status($real_status, self::get_amount_due($booking_id));
				update_post_meta($booking_id, 'mpwpb_order_status', $display_status);
				return $display_status;
			}

			/**
			 * Records a payment against a booking's outstanding balance --
			 * shared by the admin "Record Payment" action and
			 * apply_balance_payment() below (a paid-online balance order is
			 * just a payment of the full remaining due, recorded the same way).
			 *
			 * @return true|WP_Error
			 */
			public static function record_payment($booking_id, $amount, $note = '', $action_type = null) {
				$amount = round((float) $amount, 2);
				if ($amount <= 0) {
					return new WP_Error('mpwpb_invalid_amount', __('Enter an amount greater than zero.', 'service-booking-manager'));
				}
				$amount_due = self::get_amount_due($booking_id);
				if ($amount_due <= 0) {
					return new WP_Error('mpwpb_no_balance_due', __('This booking has no outstanding balance.', 'service-booking-manager'));
				}
				if ($amount > $amount_due + 0.005) {
					return new WP_Error('mpwpb_amount_exceeds_due', __('That amount is more than the remaining balance due.', 'service-booking-manager'));
				}

				$amount_paid = self::get_amount_paid($booking_id);
				$new_amount_paid = round($amount_paid + $amount, 2);
				$new_amount_due = max(0.0, round($amount_due - $amount, 2));

				update_post_meta($booking_id, 'mpwpb_amount_paid', $new_amount_paid);
				update_post_meta($booking_id, 'mpwpb_amount_due', $new_amount_due);
				$display_status = self::sync_display_status($booking_id);

				if (class_exists('MPWPB_Booking_History')) {
					MPWPB_Booking_History::log(
						$booking_id,
						$action_type ?: MPWPB_Booking_History::ACTION_PAYMENT_MARKED_RECEIVED,
						self::format_price_plain($amount_due),
						self::format_price_plain($new_amount_due),
						$note
					);
				}

				return true;
			}

			/**
			 * Admin manually recording a payment received (cash, bank transfer,
			 * etc.) against a booking's balance -- supports partial/installment
			 * entries, not just settling the full remaining due at once.
			 *
			 * @return true|WP_Error
			 */
			public static function admin_record_payment($booking_id, $amount, $note = '') {
				$note = $note !== '' ? $note : __('Payment recorded by admin.', 'service-booking-manager');
				return self::record_payment($booking_id, $amount, $note, MPWPB_Booking_History::ACTION_PAYMENT_MARKED_RECEIVED);
			}

			/**
			 * Resolves the booking tied to an order id, working for both a real
			 * WC order and a native mpwpb_order post -- both use the same
			 * mpwpb_order_id meta key on the booking, so no branching needed here.
			 */
			public static function get_booking_for_order($order_id): int {
				if (class_exists('MPWPB_User_Dashboard')) {
					return (int) MPWPB_User_Dashboard::get_booking_for_order($order_id);
				}
				return 0;
			}

			/**
			 * Creates (or reuses an already-pending) linked order for exactly a
			 * booking's remaining balance, and returns the URL the customer
			 * should be sent to in order to pay it -- a WooCommerce "Pay for
			 * order" URL, or the native checkout URL, depending on which
			 * payment system the ORIGINAL order was placed under (read off the
			 * booking's own mpwpb_payment_method, not the site's current
			 * setting, so a balance payment still routes correctly even if the
			 * site's payment method was switched after the deposit was taken).
			 *
			 * @return string|WP_Error
			 */
			public static function create_balance_order($booking_id) {
				$amount_due = self::get_amount_due($booking_id);
				if ($amount_due <= 0) {
					return new WP_Error('mpwpb_no_balance_due', __('This booking has no outstanding balance.', 'service-booking-manager'));
				}
				$parent_order_id = (int) get_post_meta($booking_id, 'mpwpb_order_id', true);
				if (!$parent_order_id) {
					return new WP_Error('mpwpb_no_parent_order', __('No order is associated with this booking.', 'service-booking-manager'));
				}

				// Which system the ORIGINAL order actually belongs to, not the
				// site's current Payment Method setting -- that can differ if the
				// admin switches WC/Custom after the deposit was taken, and this
				// must still route to wherever the parent order really lives.
				// mpwpb_order CPT posts are never stored as WC HPOS orders, so a
				// post-type check reliably distinguishes the two.
				if (get_post_type($parent_order_id) === 'mpwpb_order') {
					return self::create_native_balance_order($parent_order_id, $booking_id, $amount_due);
				}
				return self::create_wc_balance_order($parent_order_id, $booking_id, $amount_due);
			}

			private static function create_wc_balance_order($parent_order_id, $booking_id, $amount_due) {
				$parent_order = wc_get_order($parent_order_id);
				if (!$parent_order) {
					return new WP_Error('mpwpb_parent_order_missing', __('The original order could not be found.', 'service-booking-manager'));
				}

				// Reuse an existing unpaid balance order for this parent rather
				// than spawning a new one every time the customer clicks "Pay
				// Balance" (e.g. they abandoned the payment page once already).
				$existing_ids = $parent_order->get_meta('_mpwpb_balance_order_ids');
				$existing_ids = is_array($existing_ids) ? $existing_ids : [];
				foreach ($existing_ids as $existing_id) {
					$existing = wc_get_order($existing_id);
					if ($existing && $existing->needs_payment()) {
						return $existing->get_checkout_payment_url();
					}
				}

				$balance_order = wc_create_order(['status' => 'pending']);
				$balance_order->set_billing_first_name($parent_order->get_billing_first_name());
				$balance_order->set_billing_last_name($parent_order->get_billing_last_name());
				$balance_order->set_billing_email($parent_order->get_billing_email());
				$balance_order->set_billing_phone($parent_order->get_billing_phone());
				$balance_order->set_customer_id($parent_order->get_customer_id());
				$item = new WC_Order_Item_Fee();
				$item->set_name(sprintf(
					/* translators: %s: original order number */
					__('Balance Payment — Order #%s', 'service-booking-manager'),
					$parent_order->get_order_number()
				));
				$item->set_amount($amount_due);
				$item->set_total($amount_due);
				$balance_order->add_item($item);
				$balance_order->calculate_totals(false);
				$balance_order->update_meta_data('_mpwpb_balance_of_order_id', $parent_order_id);
				$balance_order->update_meta_data('_mpwpb_balance_of_booking_id', $booking_id);
				$balance_order->save();

				$existing_ids[] = $balance_order->get_id();
				$parent_order->update_meta_data('_mpwpb_balance_order_ids', $existing_ids);
				$parent_order->save();

				return $balance_order->get_checkout_payment_url();
			}

			private static function create_native_balance_order($parent_order_id, $booking_id, $amount_due) {
				if (!class_exists('MPWPB_Native_Order') || !class_exists('MPWPB_Native_Cart') || !class_exists('MPWPB_Native_Checkout')) {
					return new WP_Error('mpwpb_native_unavailable', __('Custom Payment checkout is not available.', 'service-booking-manager'));
				}
				$parent_status = MPWPB_Native_Order::get_status($parent_order_id);
				if (!$parent_status) {
					return new WP_Error('mpwpb_parent_order_missing', __('The original order could not be found.', 'service-booking-manager'));
				}

				MPWPB_Native_Cart::set_item([
					'mpwpb_is_balance_payment' => true,
					'mpwpb_balance_of_order_id' => $parent_order_id,
					'mpwpb_balance_of_booking_id' => $booking_id,
					'mpwpb_tp' => $amount_due,
					'mpwpb_discount_amount' => 0,
					'mpwpb_payment_choice' => 'full',
					'mpwpb_id' => get_post_meta($booking_id, 'mpwpb_id', true),
					'mpwpb_date' => get_post_meta($booking_id, 'mpwpb_date', true),
					'mpwpb_service' => [],
					'mpwpb_extra_service_info' => [],
					'mpwpb_category' => '',
					'mpwpb_sub_category' => '',
				]);

				return MPWPB_Native_Checkout::get_checkout_url();
			}

			/**
			 * Called once a balance order/payment completes (WC "pay for order"
			 * reaching a paid status, or the native balance-payment branch in
			 * MPWPB_Native_Checkout::handle_checkout_submit()) -- updates the
			 * ORIGINAL booking/order's paid/due and logs history. Safe to call
			 * more than once for the same balance order (no-ops if already applied).
			 */
			public static function apply_balance_payment($balance_order_id, $is_wc = null): void {
				$is_wc = $is_wc ?? MPWPB_Global_Function::is_wc_payment_mode();

				if ($is_wc) {
					$balance_order = wc_get_order($balance_order_id);
					if (!$balance_order) {
						return;
					}
					if ($balance_order->get_meta('_mpwpb_balance_applied') === 'yes') {
						return; // already processed -- avoid double-crediting on repeated webhook/return calls
					}
					$booking_id = (int) $balance_order->get_meta('_mpwpb_balance_of_booking_id');
					$parent_order_id = (int) $balance_order->get_meta('_mpwpb_balance_of_order_id');
					if (!$booking_id) {
						return;
					}
					$amount = (float) $balance_order->get_total();
					self::record_payment($booking_id, $amount, sprintf(
						/* translators: %s: balance order number */
						__('Balance paid online — Order #%s.', 'service-booking-manager'),
						$balance_order->get_order_number()
					), MPWPB_Booking_History::ACTION_BALANCE_PAID);
					$balance_order->update_meta_data('_mpwpb_balance_applied', 'yes');
					$balance_order->save();
					return;
				}

				if (!class_exists('MPWPB_Native_Order')) {
					return;
				}
				$booking_id = (int) get_post_meta($balance_order_id, 'mpwpb_balance_of_booking_id', true);
				if (!$booking_id) {
					return;
				}
				if (get_post_meta($balance_order_id, 'mpwpb_balance_applied', true) === 'yes') {
					return;
				}
				$amount = (float) MPWPB_Native_Order::get_total($balance_order_id);
				self::record_payment($booking_id, $amount, __('Balance paid online.', 'service-booking-manager'), MPWPB_Booking_History::ACTION_BALANCE_PAID);
				update_post_meta($balance_order_id, 'mpwpb_balance_applied', 'yes');
			}
		}
		new MPWPB_Partial_Payment();
	}
