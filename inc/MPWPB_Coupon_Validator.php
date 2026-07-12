<?php
	/*
   * @Author 		engr.sumonazma@gmail.com
   * Copyright: 	mage-people.com
   */
	if (!defined('ABSPATH')) {
		die;
	} // Cannot access pages directly.
	if (!class_exists('MPWPB_Coupon_Validator')) {
		class MPWPB_Coupon_Validator {
			/**
			 * Builds the validator/discount context from a booking cart item --
			 * the same array shape produced by
			 * MPWPB_Woocommerce::build_booking_item_from_request(), used
			 * identically by both Native Checkout and WooCommerce so a coupon
			 * behaves the same regardless of checkout path.
			 */
			public static function build_context(array $item, string $email = '', int $user_id = 0): array {
				$services = isset($item['mpwpb_service']) && is_array($item['mpwpb_service']) ? $item['mpwpb_service'] : [];
				$quantity = 0;
				$services_subtotal = 0.0;
				$normalized_services = [];
				foreach ($services as $service) {
					$qty = isset($service['qty']) ? (int) $service['qty'] : 0;
					$price = isset($service['price']) ? (float) $service['price'] : 0.0;
					$quantity += $qty;
					$services_subtotal += $price * $qty;
					$normalized_services[] = [
						'service_id' => isset($service['service_id']) && $service['service_id'] !== '' ? (int) $service['service_id'] : null,
						'name' => $service['name'] ?? '',
						'price' => $price,
						'qty' => $qty,
					];
				}
				return [
					'item_post_id' => isset($item['mpwpb_id']) ? (int) $item['mpwpb_id'] : 0,
					'services' => $normalized_services,
					'quantity' => $quantity,
					'services_subtotal' => $services_subtotal,
					'subtotal' => isset($item['mpwpb_tp']) && $item['mpwpb_tp'] !== '' ? (float) $item['mpwpb_tp'] : $services_subtotal,
					'date' => isset($item['mpwpb_date']) ? (string) $item['mpwpb_date'] : '',
					'staff_id' => isset($item['mpwpb_staff_member_id']) ? $item['mpwpb_staff_member_id'] : '',
					'user_id' => $user_id,
					'email' => $email,
				];
			}

			/**
			 * @return array{valid: bool, message: string, coupon_id?: int}
			 */
			public static function validate($code, array $context): array {
				$coupon_id = MPWPB_Coupon_Function::find_by_code($code);
				if (!$coupon_id) {
					return ['valid' => false, 'message' => esc_html__('Invalid coupon code.', 'service-booking-manager')];
				}

				$today = current_time('Y-m-d');
				$start = MPWPB_Global_Function::get_post_info($coupon_id, 'mpwpb_coupon_start_date', '');
				$expiry = MPWPB_Global_Function::get_post_info($coupon_id, 'mpwpb_coupon_expiry_date', '');
				if ($start && $today < $start) {
					return ['valid' => false, 'message' => esc_html__('This coupon is not yet valid.', 'service-booking-manager')];
				}
				if ($expiry && $today > $expiry) {
					return ['valid' => false, 'message' => esc_html__('This coupon has expired.', 'service-booking-manager')];
				}

				// Service scope
				$scope = MPWPB_Global_Function::get_post_info($coupon_id, 'mpwpb_coupon_service_scope', 'all');
				if ($scope === 'specific' && !self::matches_service_scope($coupon_id, $context)) {
					return ['valid' => false, 'message' => esc_html__('This coupon is not valid for the selected service(s).', 'service-booking-manager')];
				}

				// Price restriction
				$min_total = MPWPB_Global_Function::get_post_info($coupon_id, 'mpwpb_coupon_min_total', '');
				$max_total = MPWPB_Global_Function::get_post_info($coupon_id, 'mpwpb_coupon_max_total', '');
				if ($min_total !== '' && $context['subtotal'] < (float) $min_total) {
					return ['valid' => false, 'message' => sprintf(
						/* translators: %s: minimum booking total required */
						esc_html__('A minimum booking total of %s is required for this coupon.', 'service-booking-manager'),
						wp_strip_all_tags(MPWPB_Global_Function::format_price($min_total))
					)];
				}
				if ($max_total !== '' && $context['subtotal'] > (float) $max_total) {
					return ['valid' => false, 'message' => sprintf(
						/* translators: %s: maximum booking total allowed */
						esc_html__('This coupon only applies to bookings up to %s.', 'service-booking-manager'),
						wp_strip_all_tags(MPWPB_Global_Function::format_price($max_total))
					)];
				}

				// Quantity restriction
				$min_qty = MPWPB_Global_Function::get_post_info($coupon_id, 'mpwpb_coupon_min_qty', '');
				$max_qty = MPWPB_Global_Function::get_post_info($coupon_id, 'mpwpb_coupon_max_qty', '');
				if ($min_qty !== '' && $context['quantity'] < (int) $min_qty) {
					return ['valid' => false, 'message' => sprintf(
						/* translators: %d: minimum quantity required */
						esc_html__('A minimum quantity of %d is required for this coupon.', 'service-booking-manager'),
						(int) $min_qty
					)];
				}
				if ($max_qty !== '' && $context['quantity'] > (int) $max_qty) {
					return ['valid' => false, 'message' => sprintf(
						/* translators: %d: maximum quantity allowed */
						esc_html__('This coupon only applies to a maximum quantity of %d.', 'service-booking-manager'),
						(int) $max_qty
					)];
				}

				$date_check = self::check_date_time_restrictions($coupon_id, $context);
				if (!$date_check['valid']) {
					return $date_check;
				}

				$customer_check = self::check_customer_restrictions($coupon_id, $context);
				if (!$customer_check['valid']) {
					return $customer_check;
				}

				$staff_check = self::check_staff_restriction($coupon_id, $context);
				if (!$staff_check['valid']) {
					return $staff_check;
				}

				$usage_check = self::check_usage_limits($coupon_id, $context);
				if (!$usage_check['valid']) {
					return $usage_check;
				}

				return ['valid' => true, 'message' => '', 'coupon_id' => $coupon_id];
			}

			private static function matches_service_scope($coupon_id, array $context): bool {
				$allowed = MPWPB_Global_Function::get_post_info($coupon_id, 'mpwpb_coupon_services', []);
				$allowed = is_array($allowed) ? $allowed : [];
				foreach ($context['services'] as $service) {
					if ($service['service_id'] === null) {
						continue;
					}
					$selector = $context['item_post_id'] . ':' . $service['service_id'];
					if (in_array($selector, $allowed, true)) {
						return true;
					}
				}
				return false;
			}

			private static function check_date_time_restrictions($coupon_id, array $context): array {
				$day_restriction = MPWPB_Global_Function::get_post_info($coupon_id, 'mpwpb_coupon_booking_day_restriction', 'none');
				$date_mode = MPWPB_Global_Function::get_post_info($coupon_id, 'mpwpb_coupon_booking_date_mode', 'none');
				$allowed_dates = MPWPB_Global_Function::get_post_info($coupon_id, 'mpwpb_coupon_booking_dates', []);
				$allowed_dates = is_array($allowed_dates) ? $allowed_dates : [];
				$time_mode = MPWPB_Global_Function::get_post_info($coupon_id, 'mpwpb_coupon_time_mode', 'none');

				if ($day_restriction === 'none' && $date_mode === 'none' && $time_mode === 'none') {
					return ['valid' => true, 'message' => ''];
				}

				$occurrences = self::split_occurrences($context['date']);
				if (empty($occurrences)) {
					return ['valid' => false, 'message' => esc_html__('This coupon requires a selected booking date/time.', 'service-booking-manager')];
				}

				foreach ($occurrences as $occurrence) {
					[$date_part, $time_part] = self::split_occurrence($occurrence);

					if ($day_restriction !== 'none' && $date_part) {
						$weekday = (int) date('w', strtotime($date_part));
						$is_weekend = ($weekday === 0 || $weekday === 6);
						if ($day_restriction === 'weekdays' && $is_weekend) {
							return ['valid' => false, 'message' => esc_html__('This coupon is only valid on weekdays.', 'service-booking-manager')];
						}
						if ($day_restriction === 'weekends' && !$is_weekend) {
							return ['valid' => false, 'message' => esc_html__('This coupon is only valid on weekends.', 'service-booking-manager')];
						}
					}
					if ($date_mode === 'allowlist' && $date_part && !in_array($date_part, $allowed_dates, true)) {
						return ['valid' => false, 'message' => esc_html__('This coupon is not valid for the selected date.', 'service-booking-manager')];
					}
					if ($date_mode === 'blacklist' && $date_part && in_array($date_part, $allowed_dates, true)) {
						return ['valid' => false, 'message' => esc_html__('This coupon cannot be used on the selected date.', 'service-booking-manager')];
					}
					if ($time_mode !== 'none' && $time_part) {
						if ($time_mode === 'bucket') {
							$bucket = MPWPB_Global_Function::get_post_info($coupon_id, 'mpwpb_coupon_time_bucket', 'morning');
							if (!self::time_in_bucket($time_part, $bucket)) {
								return ['valid' => false, 'message' => esc_html__('This coupon is only valid for a specific time of day.', 'service-booking-manager')];
							}
						} else {
							$range_start = MPWPB_Global_Function::get_post_info($coupon_id, 'mpwpb_coupon_time_range_start', '');
							$range_end = MPWPB_Global_Function::get_post_info($coupon_id, 'mpwpb_coupon_time_range_end', '');
							if ($range_start && $range_end && !self::time_in_range($time_part, $range_start, $range_end)) {
								return ['valid' => false, 'message' => esc_html__('This coupon is only valid within a specific time range.', 'service-booking-manager')];
							}
						}
					}
				}

				return ['valid' => true, 'message' => ''];
			}

			private static function check_customer_restrictions($coupon_id, array $context): array {
				$history_restriction = MPWPB_Global_Function::get_post_info($coupon_id, 'mpwpb_coupon_history_restriction', 'none');
				$account_restriction = MPWPB_Global_Function::get_post_info($coupon_id, 'mpwpb_coupon_account_restriction', 'none');
				$limit_per_customer = MPWPB_Global_Function::get_post_info($coupon_id, 'mpwpb_coupon_usage_limit_per_customer', '');

				$needs_identity = ($history_restriction !== 'none') || ($limit_per_customer !== '');
				if ($needs_identity && !$context['user_id'] && !$context['email']) {
					return ['valid' => false, 'message' => esc_html__('Please enter your email to apply this coupon.', 'service-booking-manager')];
				}

				if ($account_restriction === 'guest_only' && $context['user_id'] > 0) {
					return ['valid' => false, 'message' => esc_html__('This coupon is valid for guest checkout only.', 'service-booking-manager')];
				}
				if ($account_restriction === 'logged_in_only' && $context['user_id'] <= 0) {
					return ['valid' => false, 'message' => esc_html__('This coupon requires a logged-in account.', 'service-booking-manager')];
				}

				if ($history_restriction !== 'none') {
					$order_count = MPWPB_Coupon_Usage::count_customer_orders($context['email'], $context['user_id']);
					if ($history_restriction === 'first_booking' && $order_count > 0) {
						return ['valid' => false, 'message' => esc_html__('This coupon is valid for first-time customers only.', 'service-booking-manager')];
					}
					if ($history_restriction === 'returning' && $order_count === 0) {
						return ['valid' => false, 'message' => esc_html__('This coupon is valid for returning customers only.', 'service-booking-manager')];
					}
				}

				return ['valid' => true, 'message' => ''];
			}

			private static function check_staff_restriction($coupon_id, array $context): array {
				$staff_scope = MPWPB_Global_Function::get_post_info($coupon_id, 'mpwpb_coupon_staff_scope', 'all');
				if ($staff_scope === 'all') {
					return ['valid' => true, 'message' => ''];
				}
				$staff_ids = MPWPB_Global_Function::get_post_info($coupon_id, 'mpwpb_coupon_staff_ids', []);
				$staff_ids = array_map('intval', is_array($staff_ids) ? $staff_ids : []);
				$current_staff = (int) ($context['staff_id'] ?: 0);
				if (!$current_staff) {
					return ['valid' => false, 'message' => esc_html__('This coupon requires a specific staff member to be selected.', 'service-booking-manager')];
				}
				$in_list = in_array($current_staff, $staff_ids, true);
				if ($staff_scope === 'include' && !$in_list) {
					return ['valid' => false, 'message' => esc_html__('This coupon is not valid with the selected staff member.', 'service-booking-manager')];
				}
				if ($staff_scope === 'exclude' && $in_list) {
					return ['valid' => false, 'message' => esc_html__('This coupon is not valid with the selected staff member.', 'service-booking-manager')];
				}
				return ['valid' => true, 'message' => ''];
			}

			private static function check_usage_limits($coupon_id, array $context): array {
				$limit_total = MPWPB_Global_Function::get_post_info($coupon_id, 'mpwpb_coupon_usage_limit_total', '');
				if ($limit_total !== '') {
					$used_total = MPWPB_Coupon_Usage::count_total_usage($coupon_id);
					if ($used_total >= (int) $limit_total) {
						return ['valid' => false, 'message' => esc_html__('This coupon has reached its usage limit.', 'service-booking-manager')];
					}
				}
				$limit_per_customer = MPWPB_Global_Function::get_post_info($coupon_id, 'mpwpb_coupon_usage_limit_per_customer', '');
				if ($limit_per_customer !== '') {
					$used_by_customer = MPWPB_Coupon_Usage::count_customer_usage($coupon_id, $context['email'], $context['user_id']);
					if ($used_by_customer >= (int) $limit_per_customer) {
						return ['valid' => false, 'message' => esc_html__('You have already used this coupon the maximum number of times.', 'service-booking-manager')];
					}
				}
				return ['valid' => true, 'message' => ''];
			}

			/**
			 * fixed: flat amount off. percentage: % of the services subtotal.
			 * fixed_price: matched service line(s) become a flat unit price
			 * (qty preserved). free: 100% of the services subtotal.
			 * Extras are never discounted -- always added on top.
			 */
			public static function calculate_discount($coupon_id, array $context): float {
				$type = MPWPB_Global_Function::get_post_info($coupon_id, 'mpwpb_coupon_discount_type', 'fixed');
				$value = (float) MPWPB_Global_Function::get_post_info($coupon_id, 'mpwpb_coupon_discount_value', 0);
				$base = (float) $context['services_subtotal'];

				switch ($type) {
					case 'percentage':
						$discount = $base * $value / 100;
						break;
					case 'fixed_price':
						$discount = self::fixed_price_discount($coupon_id, $context, $value);
						break;
					case 'free':
						$discount = $base;
						break;
					default: // fixed
						$discount = $value;
						break;
				}

				return max(0, min($discount, $base));
			}

			private static function fixed_price_discount($coupon_id, array $context, float $flat_price): float {
				$scope = MPWPB_Global_Function::get_post_info($coupon_id, 'mpwpb_coupon_service_scope', 'all');
				$allowed = MPWPB_Global_Function::get_post_info($coupon_id, 'mpwpb_coupon_services', []);
				$allowed = is_array($allowed) ? $allowed : [];
				$discount = 0.0;
				foreach ($context['services'] as $service) {
					$selector = $service['service_id'] !== null ? $context['item_post_id'] . ':' . $service['service_id'] : null;
					$applies = ($scope !== 'specific') || ($selector && in_array($selector, $allowed, true));
					if (!$applies) {
						continue;
					}
					$qty = (int) $service['qty'];
					$original_line_total = (float) $service['price'] * $qty;
					$new_line_total = $flat_price * $qty;
					$discount += max(0, $original_line_total - $new_line_total);
				}
				return $discount;
			}

			private static function split_occurrences($raw_date): array {
				if (!is_string($raw_date) || $raw_date === '') {
					return [];
				}
				return array_values(array_filter(array_map('trim', explode(',', $raw_date))));
			}

			/**
			 * Booking date/time is stored as one 'Y-m-d H:i' string per
			 * occurrence -- split it into its date and time parts.
			 */
			private static function split_occurrence($occurrence): array {
				$parts = explode(' ', trim($occurrence));
				$date_part = $parts[0] ?? '';
				$time_part = $parts[1] ?? '';
				return [$date_part, $time_part];
			}

			private static function time_in_bucket($time_hi, $bucket): bool {
				$hour = (int) explode(':', $time_hi)[0];
				switch ($bucket) {
					case 'morning':
						return $hour >= 0 && $hour <= 11;
					case 'afternoon':
						return $hour >= 12 && $hour <= 16;
					case 'evening':
						return $hour >= 17 && $hour <= 23;
				}
				return true;
			}

			private static function time_in_range($time_hi, $start, $end): bool {
				return $time_hi >= $start && $time_hi <= $end;
			}
		}
	}
