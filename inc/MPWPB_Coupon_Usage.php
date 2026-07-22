<?php
	/*
   * @Author 		engr.sumonazma@gmail.com
   * Copyright: 	mage-people.com
   */
	if (!defined('ABSPATH')) {
		die;
	} // Cannot access pages directly.
	if (!class_exists('MPWPB_Coupon_Usage')) {
		class MPWPB_Coupon_Usage {
			/**
			 * Live enforcement source of truth (not the cached display
			 * counter). Counts DISTINCT mpwpb_order_id among matching
			 * mpwpb_booking posts, since one recurring booking creates
			 * several booking posts sharing a single order id -- counting
			 * raw posts would over-count usage for recurring bookings.
			 */
			public static function count_total_usage($coupon_id): int {
				$code = MPWPB_Global_Function::get_post_info($coupon_id, 'mpwpb_coupon_code', '');
				if (!$code) {
					return 0;
				}
				return self::distinct_order_count([
					['key' => 'mpwpb_coupon_code', 'value' => $code],
				]);
			}

			public static function count_customer_usage($coupon_id, $email, $user_id): int {
				$code = MPWPB_Global_Function::get_post_info($coupon_id, 'mpwpb_coupon_code', '');
				$identity = self::identity_meta_query($email, $user_id);
				if (!$code || !$identity) {
					return 0;
				}
				return self::distinct_order_count([
					['key' => 'mpwpb_coupon_code', 'value' => $code],
					$identity,
				]);
			}

			/**
			 * Total prior (non-cancelled) orders for this customer, coupon-
			 * agnostic -- used for the first-booking/returning-customer
			 * restriction.
			 */
			public static function count_customer_orders($email, $user_id): int {
				$identity = self::identity_meta_query($email, $user_id);
				if (!$identity) {
					return 0;
				}
				return self::distinct_order_count([$identity]);
			}

			private static function identity_meta_query($email, $user_id) {
				$user_id = (int) $user_id;
				$email = trim((string) $email);
				if ($user_id > 0) {
					return ['key' => 'mpwpb_user_id', 'value' => $user_id];
				}
				if ($email !== '') {
					return ['key' => 'mpwpb_billing_email', 'value' => $email];
				}
				return null;
			}

			private static function distinct_order_count(array $meta_filters): int {
				$meta_query = array_merge(['relation' => 'AND'], $meta_filters, [
					[
						'key' => 'mpwpb_order_status',
						'value' => 'cancelled',
						'compare' => '!=',
					],
				]);
				$query = new WP_Query([
					'post_type' => 'mpwpb_booking',
					'post_status' => 'any',
					'posts_per_page' => -1,
					'no_found_rows' => true,
					'fields' => 'ids',
					'meta_query' => $meta_query,
				]);
				$order_ids = [];
				foreach ($query->posts as $post_id) {
					$order_id = get_post_meta($post_id, 'mpwpb_order_id', true);
					if ($order_id !== '') {
						$order_ids[(string) $order_id] = true;
					}
				}
				return count($order_ids);
			}

			/**
			 * Bumps the coupon's cached display counter only. Call exactly
			 * once per completed order (before create_bookings_from_data()'s
			 * per-occurrence loop), never once per recurring occurrence.
			 */
			public static function record_usage($coupon_id): void {
				if (!$coupon_id) {
					return;
				}
				$count = (int) get_post_meta($coupon_id, 'mpwpb_coupon_usage_count', true);
				update_post_meta($coupon_id, 'mpwpb_coupon_usage_count', $count + 1);
			}
		}
	}
