<?php
	/*
* @Author 		engr.sumonazma@gmail.com
* Copyright: 	mage-people.com
*/
	if (!defined('ABSPATH')) {
		die;
	} // Cannot access pages directly.
	/**
	 * "Disable WooCommerce Customer Emails" -- Settings > Payment Method >
	 * WooCommerce > Additional Settings.
	 *
	 * A booking is sold through a hidden WooCommerce product, so WooCommerce
	 * treats it exactly like a shippable item and mails the customer its stock
	 * order notifications ("Your order is now processing", "Your order is
	 * complete"). Those read as shipment confirmations and confuse booking
	 * customers, who expect a booking confirmation instead.
	 *
	 * With this setting on, the automatic CUSTOMER order emails are suppressed
	 * for orders that consist solely of bookings, and the plugin's own booking
	 * confirmation / PDF-ticket email (Settings > Email, PRO) takes their place.
	 *
	 * Deliberately NOT suppressed:
	 *  - Admin/shop-manager notifications (new_order, cancelled_order,
	 *    failed_order) -- orders must still "come in" to the shop owner.
	 *  - Admin-triggered emails (customer_invoice, customer_note) -- a human
	 *    pressed a button expecting mail to go out; silently dropping those
	 *    would be a bug, not a feature.
	 *  - Any order that also contains a normal shop product; that customer
	 *    still needs WooCommerce's shipping/processing mail.
	 *
	 * Default is 'off', so existing sites keep their current behaviour until an
	 * admin opts in.
	 */
	if (!class_exists('MPWPB_Wc_Email_Control')) {
		class MPWPB_Wc_Email_Control {
			/**
			 * Automatic, order-status driven customer emails.
			 */
			const CUSTOMER_EMAIL_IDS = [
				'customer_on_hold_order',
				'customer_processing_order',
				'customer_completed_order',
				'customer_refunded_order',
				'customer_partially_refunded_order',
			];
			public function __construct() {
				foreach (self::CUSTOMER_EMAIL_IDS as $email_id) {
					add_filter('woocommerce_email_enabled_' . $email_id, [$this, 'maybe_disable_customer_email'], 99, 2);
				}
			}
			public static function is_enabled(): bool {
				return MPWPB_Global_Function::get_payment_setting('wc_disable_customer_emails', 'off') === 'on';
			}
			/**
			 * WC_Email::is_enabled() runs this filter both when an email is about
			 * to be sent (with the order as $object) and on the WooCommerce email
			 * settings screen (where $object is null). Only the first case may be
			 * overridden -- otherwise the settings screen would show every booking
			 * email as disabled.
			 *
			 * @param bool  $enabled Whether WooCommerce would send this email.
			 * @param mixed $object  The order being mailed, or null.
			 * @return bool
			 */
			public function maybe_disable_customer_email($enabled, $object = null) {
				if (!$enabled || !self::is_enabled()) {
					return $enabled;
				}
				return self::is_booking_only_order($object) ? false : $enabled;
			}
			/**
			 * True only when every line item on the order is a booking service.
			 * A mixed cart (booking + a real product that does ship) keeps
			 * WooCommerce's emails, because that customer still needs them.
			 */
			public static function is_booking_only_order($order): bool {
				if (!class_exists('WC_Order') || !$order instanceof WC_Order) {
					return false;
				}
				$items = $order->get_items();
				if (empty($items)) {
					return false;
				}
				foreach ($items as $item) {
					if (get_post_type(absint($item->get_meta('_mpwpb_id', true))) !== MPWPB_Function::get_cpt()) {
						return false;
					}
				}
				return true;
			}
		}
		new MPWPB_Wc_Email_Control();
	}
