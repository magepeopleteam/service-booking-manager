<?php
	/*
   * Per-service Happy Hours Pricing -- off by default. One time-of-day
   * window per service (e.g. 14:00-16:00), with a percentage or fixed
   * discount applied to that service's price whenever the customer's
   * SELECTED APPOINTMENT TIME (not the time they happen to be checking
   * out) falls inside the window.
   * Hooked entirely through the existing 'mpwpb_price_filter' filter
   * (MPWPB_Function::get_price()/get_extra_price()) -- the single place
   * a booking's price is computed for the cart, already shared by both
   * the WooCommerce and Custom Payment paths (MPWPB_Woocommerce::
   * build_booking_item_from_request()), so no new plumbing is needed in
   * either checkout path.
   */
	if (!defined('ABSPATH')) {
		die;
	} // Cannot access pages directly.

	if (!class_exists('MPWPB_Happy_Hours_Helper')) {
		class MPWPB_Happy_Hours_Helper {
			public function __construct() {
				add_filter('mpwpb_price_filter', [$this, 'apply_happy_hours'], 10, 4);
			}

			/**
			 * Happy Hours is a Pro feature. Gated here (not just in the
			 * settings-screen UI) so it can never actually apply a discount
			 * just because 'on' happens to be stored -- e.g. if Pro was
			 * active when this was enabled but has since been deactivated.
			 */
			public static function is_enabled_for_service($service_post_id): bool {
				if (!MPWPB_Global_Function::is_pro_active()) {
					return false;
				}
				return get_post_meta($service_post_id, 'mpwpb_happy_hours_enabled', true) === 'on';
			}

			public static function get_rule($service_post_id): array {
				return [
					'start_time' => (string) get_post_meta($service_post_id, 'mpwpb_happy_hours_start_time', true) ?: '00:00',
					'end_time' => (string) get_post_meta($service_post_id, 'mpwpb_happy_hours_end_time', true) ?: '23:59',
					'discount_type' => get_post_meta($service_post_id, 'mpwpb_happy_hours_discount_type', true) === 'fixed' ? 'fixed' : 'percent',
					'discount_value' => (float) get_post_meta($service_post_id, 'mpwpb_happy_hours_discount_value', true),
				];
			}

			/**
			 * @param string $datetime Anything strtotime() can parse, e.g.
			 *                         "2026-07-15 14:00". Recurring bookings can
			 *                         pass a comma-joined list of several
			 *                         dates -- only the FIRST one's time-of-day
			 *                         is checked (the base per-instance price is
			 *                         already computed once and multiplied by
			 *                         occurrence count elsewhere for recurring
			 *                         bookings, so this matches that existing
			 *                         one-price-for-all-instances behavior
			 *                         rather than introducing new per-date
			 *                         granularity recurring pricing doesn't
			 *                         otherwise have).
			 * @return bool Whether $datetime's time-of-day falls inside the
			 *              rule's window. Does not handle windows that cross
			 *              midnight (start_time must be < end_time) -- a
			 *              known v1 simplification.
			 */
			public static function time_in_window($datetime, array $rule): bool {
				if ($datetime === '') {
					return false;
				}
				$first = trim(explode(',', $datetime)[0]);
				$ts = strtotime($first);
				if (!$ts) {
					return false;
				}
				$time = date('H:i', $ts);
				return $time >= $rule['start_time'] && $time < $rule['end_time'];
			}

			public static function get_adjusted_price($service_post_id, $price, $datetime) {
				$price = (float) $price;
				if ($price <= 0 || !self::is_enabled_for_service($service_post_id)) {
					return $price;
				}
				$rule = self::get_rule($service_post_id);
				if (!self::time_in_window($datetime, $rule)) {
					return $price;
				}
				if ($rule['discount_type'] === 'fixed') {
					return max(0.0, round($price - $rule['discount_value'], 2));
				}
				return max(0.0, round($price - ($price * ($rule['discount_value'] / 100)), 2));
			}

			/**
			 * Short plain-text label for the time-slot picker (e.g. "20% off"
			 * or "$15 off"), so the discount is visible at the moment the
			 * customer is choosing a slot rather than only appearing on the
			 * final price. Empty string when Happy Hours isn't enabled/has no
			 * value set, so callers can just skip rendering the badge.
			 */
			public static function get_badge_label($service_post_id): string {
				if (!self::is_enabled_for_service($service_post_id)) {
					return '';
				}
				$rule = self::get_rule($service_post_id);
				if ($rule['discount_value'] <= 0) {
					return '';
				}
				if ($rule['discount_type'] === 'fixed') {
					$amount = wp_strip_all_tags(MPWPB_Global_Function::wc_price($service_post_id, $rule['discount_value']));
					/* translators: %s: formatted discount amount, e.g. "$15" */
					return sprintf(__('%s off', 'service-booking-manager'), $amount);
				}
				$value = rtrim(rtrim(number_format($rule['discount_value'], 2), '0'), '.');
				/* translators: %s: discount percentage, e.g. "20" */
				return sprintf(__('%s%% off', 'service-booking-manager'), $value);
			}

			/** mpwpb_price_filter callback -- signature matches both call sites
			 * (get_price's $service_id, $date) and get_extra_price's
			 * ($ex_service_category, $ex_service_types), but only the base
			 * service price ($post_id itself carrying the rule, $date a real
			 * datetime string) is ever adjusted; extra services keep their
			 * own flat price regardless of the time slot. */
			public function apply_happy_hours($price, $post_id, $arg3, $arg4) {
				// get_extra_price() passes a service name string as $arg4, not a
				// datetime -- time_in_window()'s strtotime() would silently
				// misparse that, so only apply when $arg4 actually looks like a
				// real date (get_price()'s call site).
				if (!is_string($arg4) || $arg4 === '' || !strtotime(trim(explode(',', $arg4)[0]))) {
					return $price;
				}
				return self::get_adjusted_price($post_id, $price, $arg4);
			}
		}
		new MPWPB_Happy_Hours_Helper();
	}
