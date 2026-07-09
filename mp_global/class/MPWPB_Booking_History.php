<?php
	/*
   * @Author 		engr.sumonazma@gmail.com
   * Copyright: 	mage-people.com
   */
	if (!defined('ABSPATH')) {
		die;
	} // Cannot access pages directly.

	if (!class_exists('MPWPB_Booking_History')) {
		/**
		 * Single source of truth for cancel/reschedule cutoff rules, order
		 * syncing, and audit history -- shared by the customer dashboard, the
		 * staff dashboard, and the WooCommerce My Account integration so the
		 * three call sites never re-implement (and re-diverge on) the same
		 * lead-time math or order-sync branching.
		 */
		class MPWPB_Booking_History {

			const ACTION_CANCELLED = 'cancelled';
			const ACTION_RESCHEDULED = 'rescheduled';

			private static function maybe_create_table(): void {
				global $wpdb;
				$table_name = $wpdb->prefix . 'mpwpb_booking_history';

				if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
					$charset_collate = $wpdb->get_charset_collate();

					$sql = "CREATE TABLE $table_name (
						id bigint(20) NOT NULL AUTO_INCREMENT,
						booking_id bigint(20) NOT NULL,
						action_type varchar(20) NOT NULL,
						old_date varchar(20) DEFAULT NULL,
						new_date varchar(20) DEFAULT NULL,
						performed_by_user_id bigint(20) NOT NULL,
						performed_by_role varchar(50) NOT NULL,
						note text DEFAULT NULL,
						date_created datetime NOT NULL,
						PRIMARY KEY  (id),
						KEY booking_id (booking_id)
					) $charset_collate;";

					require_once ABSPATH . 'wp-admin/includes/upgrade.php';
					dbDelta($sql);
				}
			}

			public static function log($booking_id, $action_type, $old_date, $new_date, $note = ''): void {
				global $wpdb;
				self::maybe_create_table();

				$user = wp_get_current_user();
				$role = ($user && !empty($user->roles)) ? $user->roles[0] : 'guest';

				$wpdb->insert(
					$wpdb->prefix . 'mpwpb_booking_history',
					[
						'booking_id' => (int) $booking_id,
						'action_type' => $action_type,
						'old_date' => $old_date,
						'new_date' => $new_date,
						'performed_by_user_id' => $user ? (int) $user->ID : 0,
						'performed_by_role' => $role,
						'note' => (string) $note,
						'date_created' => current_time('mysql'),
					],
					['%d', '%s', '%s', '%s', '%d', '%s', '%s', '%s']
				);
			}

			public static function get_for_booking($booking_id): array {
				global $wpdb;
				self::maybe_create_table();

				return $wpdb->get_results($wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}mpwpb_booking_history WHERE booking_id = %d ORDER BY date_created ASC",
					(int) $booking_id
				));
			}

			/**
			 * @param string $booking_date 'Y-m-d H:i' -- mpwpb_date's own stored format.
			 * @param string $action 'cancel' | 'reschedule'
			 */
			public static function is_within_lead_time($booking_date, $action = 'cancel'): bool {
				$hours = $action === 'reschedule'
					? (int) MPWPB_Global_Function::get_settings('mpwpb_general_settings', 'reschedule_lead_time', 48)
					: (int) MPWPB_Global_Function::get_settings('mpwpb_general_settings', 'cancellation_lead_time', 24);

				$booking_time = strtotime((string) $booking_date);
				if (!$booking_time) {
					return false;
				}

				return $booking_time > (current_time('timestamp') + $hours * HOUR_IN_SECONDS);
			}

			/**
			 * Callers own their own nonce + ownership checks (those legitimately
			 * differ per caller -- mpwpb_user_id for customers, mpwpb_staff_term_id
			 * for staff). This method is deliberately agnostic of "who's asking."
			 *
			 * @return true|WP_Error
			 */
			public static function cancel($booking_id, $note = '') {
				$current_status = get_post_meta($booking_id, 'mpwpb_order_status', true);
				if ($current_status === 'cancelled') {
					return new WP_Error('mpwpb_already_cancelled', __('This booking is already cancelled.', 'service-booking-manager'));
				}

				$current_date = get_post_meta($booking_id, 'mpwpb_date', true);
				if (!self::is_within_lead_time($current_date, 'cancel')) {
					return new WP_Error('mpwpb_lead_time_passed', __('This booking can no longer be cancelled online -- the cancellation window has passed.', 'service-booking-manager'));
				}

				update_post_meta($booking_id, 'mpwpb_order_status', 'cancelled');

				$order_id = get_post_meta($booking_id, 'mpwpb_order_id', true);
				if ($order_id) {
					self::sync_order_status($order_id, 'cancelled', $note);
				}

				self::log($booking_id, self::ACTION_CANCELLED, $current_date, null, $note);
				do_action('mpwpb_booking_cancelled', $booking_id, $note);

				return true;
			}

			/**
			 * @param string $new_date 'Y-m-d H:i'
			 * @return true|WP_Error
			 */
			public static function reschedule($booking_id, $new_date, $note = '') {
				$current_status = get_post_meta($booking_id, 'mpwpb_order_status', true);
				if ($current_status === 'cancelled') {
					return new WP_Error('mpwpb_already_cancelled', __('A cancelled booking cannot be rescheduled.', 'service-booking-manager'));
				}

				$current_date = get_post_meta($booking_id, 'mpwpb_date', true);
				if (!self::is_within_lead_time($current_date, 'reschedule')) {
					return new WP_Error('mpwpb_lead_time_passed', __('This booking can no longer be rescheduled online -- the reschedule window has passed.', 'service-booking-manager'));
				}

				update_post_meta($booking_id, 'mpwpb_date', $new_date);

				$order_id = get_post_meta($booking_id, 'mpwpb_order_id', true);
				if ($order_id) {
					self::sync_order_note($order_id, $note);
				}

				self::log($booking_id, self::ACTION_RESCHEDULED, $current_date, $new_date, $note);
				do_action('mpwpb_booking_rescheduled', $booking_id, $current_date, $new_date);

				return true;
			}

			/**
			 * MPWPB_Global_Function::get_order() returns false for native
			 * (non-WooCommerce) orders, so branch here instead of relying on it --
			 * that's what previously left native mpwpb_order posts out of sync.
			 */
			private static function sync_order_status($order_id, $status, $note): void {
				if (MPWPB_Global_Function::is_wc_payment_mode()) {
					$order = wc_get_order($order_id);
					if ($order) {
						$order->update_status($status, (string) $note);
					}
					return;
				}
				if (class_exists('MPWPB_Native_Order')) {
					MPWPB_Native_Order::set_status($order_id, $status);
					if ($note && method_exists('MPWPB_Native_Order', 'add_note')) {
						MPWPB_Native_Order::add_note($order_id, $note);
					}
				}
			}

			private static function sync_order_note($order_id, $note): void {
				if (!$note) {
					return;
				}
				if (MPWPB_Global_Function::is_wc_payment_mode()) {
					$order = wc_get_order($order_id);
					if ($order) {
						$order->add_order_note((string) $note);
					}
					return;
				}
				if (class_exists('MPWPB_Native_Order') && method_exists('MPWPB_Native_Order', 'add_note')) {
					MPWPB_Native_Order::add_note($order_id, $note);
				}
			}
		}
	}
