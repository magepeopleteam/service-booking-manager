<?php
	/*
	 * Customer cancellation-request workflow shared by the frontend and admin.
	 */
	if (!defined('ABSPATH')) {
		die;
	}

	if (!class_exists('MPWPB_Cancellation')) {
		class MPWPB_Cancellation {
			const STATUS_PENDING = 'pending';
			const STATUS_APPROVED = 'approved';
			const STATUS_REJECTED = 'rejected';

			public static function get_status($booking_id): string {
				return sanitize_key((string) get_post_meta($booking_id, 'mpwpb_cancellation_status', true));
			}

			/**
			 * Store a customer request without releasing the booked slot. The
			 * booking/order is cancelled only after an administrator approves it.
			 *
			 * @return true|WP_Error
			 */
			public static function request($booking_id, $user_id, $reason) {
				$booking_id = absint($booking_id);
				$user_id = absint($user_id);
				$reason = trim(sanitize_textarea_field($reason));

				if (!$booking_id || get_post_type($booking_id) !== 'mpwpb_booking') {
					return new WP_Error('mpwpb_invalid_booking', __('Invalid booking.', 'service-booking-manager'));
				}
				if (!$user_id || (int) get_post_meta($booking_id, 'mpwpb_user_id', true) !== $user_id) {
					return new WP_Error('mpwpb_not_owner', __('You do not have permission to cancel this booking.', 'service-booking-manager'));
				}
				if (get_post_meta($booking_id, 'mpwpb_order_status', true) === 'cancelled') {
					return new WP_Error('mpwpb_already_cancelled', __('This booking is already cancelled.', 'service-booking-manager'));
				}
				if (self::get_status($booking_id) === self::STATUS_PENDING) {
					return new WP_Error('mpwpb_request_pending', __('A cancellation request is already awaiting review.', 'service-booking-manager'));
				}
				if (!MPWPB_Booking_History::is_within_lead_time(get_post_meta($booking_id, 'mpwpb_date', true), 'cancel')) {
					return new WP_Error('mpwpb_lead_time_passed', __('This booking can no longer be cancelled online because the cancellation window has passed.', 'service-booking-manager'));
				}
				if (strlen($reason) < 5) {
					return new WP_Error('mpwpb_reason_required', __('Please provide a short reason for your cancellation request.', 'service-booking-manager'));
				}

				update_post_meta($booking_id, 'mpwpb_cancellation_status', self::STATUS_PENDING);
				update_post_meta($booking_id, 'mpwpb_cancellation_reason', $reason);
				update_post_meta($booking_id, 'mpwpb_cancellation_requested_at', current_time('mysql'));
				update_post_meta($booking_id, 'mpwpb_cancellation_requested_by', $user_id);
				delete_post_meta($booking_id, 'mpwpb_cancellation_review_note');
				delete_post_meta($booking_id, 'mpwpb_cancellation_reviewed_at');
				delete_post_meta($booking_id, 'mpwpb_cancellation_reviewed_by');

				MPWPB_Booking_History::log(
					$booking_id,
					MPWPB_Booking_History::ACTION_CANCELLATION_REQUESTED,
					get_post_meta($booking_id, 'mpwpb_date', true),
					null,
					$reason
				);
				self::add_order_note($booking_id, sprintf(__('Customer requested cancellation. Reason: %s', 'service-booking-manager'), $reason));
				update_post_meta($booking_id, 'mpwpb_cancellation_admin_email_sent', self::send_admin_request_email($booking_id) ? 'yes' : 'no');
				do_action('mpwpb_cancellation_requested', $booking_id, $reason, $user_id);

				return true;
			}

			/** @return true|WP_Error */
			public static function approve($booking_id, $admin_note = '') {
				$booking_id = absint($booking_id);
				$admin_note = trim(sanitize_textarea_field($admin_note));
				if (self::get_status($booking_id) !== self::STATUS_PENDING) {
					return new WP_Error('mpwpb_not_pending', __('This cancellation request has already been reviewed.', 'service-booking-manager'));
				}

				$order_id = absint(get_post_meta($booking_id, 'mpwpb_order_id', true));
				$series_ids = self::booking_ids_for_order($order_id);
				if (!$series_ids) {
					$series_ids = array($booking_id);
				}
				$reason = (string) get_post_meta($booking_id, 'mpwpb_cancellation_reason', true);
				$note = __('Cancellation request approved by administrator.', 'service-booking-manager');
				if ($reason) {
					$note .= ' ' . sprintf(__('Customer reason: %s', 'service-booking-manager'), $reason);
				}
				if ($admin_note) {
					$note .= ' ' . sprintf(__('Administrator note: %s', 'service-booking-manager'), $admin_note);
				}

				// The request was inside the cutoff when submitted. Approval may be
				// processed later, so do not reject it merely because admin review
				// crossed the cutoff in the meantime.
				$result = MPWPB_Booking_History::cancel($booking_id, $note, false);
				if (is_wp_error($result)) {
					return $result;
				}
				foreach ($series_ids as $series_id) {
					if ((int) $series_id === $booking_id || get_post_meta($series_id, 'mpwpb_order_status', true) === 'cancelled') {
						continue;
					}
					$series_result = MPWPB_Booking_History::cancel($series_id, $note, false, false);
					if (is_wp_error($series_result)) {
						return $series_result;
					}
				}

				self::mark_reviewed($booking_id, self::STATUS_APPROVED, $admin_note);
				MPWPB_Booking_History::log($booking_id, MPWPB_Booking_History::ACTION_CANCELLATION_APPROVED, '', '', $admin_note);
				update_post_meta($booking_id, 'mpwpb_cancellation_email_sent', self::send_customer_decision_email($booking_id, true, $admin_note) ? 'yes' : 'no');
				do_action('mpwpb_cancellation_approved', $booking_id, $admin_note);
				return true;
			}

			/** @return true|WP_Error */
			public static function reject($booking_id, $admin_note = '') {
				$booking_id = absint($booking_id);
				$admin_note = trim(sanitize_textarea_field($admin_note));
				if (self::get_status($booking_id) !== self::STATUS_PENDING) {
					return new WP_Error('mpwpb_not_pending', __('This cancellation request has already been reviewed.', 'service-booking-manager'));
				}

				self::mark_reviewed($booking_id, self::STATUS_REJECTED, $admin_note);
				self::add_order_note($booking_id, __('Customer cancellation request rejected by administrator.', 'service-booking-manager') . ($admin_note ? ' ' . $admin_note : ''));
				MPWPB_Booking_History::log($booking_id, MPWPB_Booking_History::ACTION_CANCELLATION_REJECTED, '', '', $admin_note);
				update_post_meta($booking_id, 'mpwpb_cancellation_email_sent', self::send_customer_decision_email($booking_id, false, $admin_note) ? 'yes' : 'no');
				do_action('mpwpb_cancellation_rejected', $booking_id, $admin_note);
				return true;
			}

			private static function mark_reviewed($booking_id, $status, $note): void {
				update_post_meta($booking_id, 'mpwpb_cancellation_status', $status);
				update_post_meta($booking_id, 'mpwpb_cancellation_review_note', $note);
				update_post_meta($booking_id, 'mpwpb_cancellation_reviewed_at', current_time('mysql'));
				update_post_meta($booking_id, 'mpwpb_cancellation_reviewed_by', get_current_user_id());
			}

			private static function booking_ids_for_order($order_id): array {
				if (!$order_id) {
					return array();
				}
				return array_values(array_unique(array_map('intval', get_posts(array(
					'post_type' => 'mpwpb_booking',
					'post_status' => 'any',
					'posts_per_page' => -1,
					'fields' => 'ids',
					'meta_key' => 'mpwpb_order_id',
					'meta_value' => $order_id,
				)))));
			}

			private static function customer_email($booking_id): string {
				$email = sanitize_email((string) get_post_meta($booking_id, 'mpwpb_billing_email', true));
				if ($email) {
					return $email;
				}
				$user = get_user_by('id', (int) get_post_meta($booking_id, 'mpwpb_user_id', true));
				return $user ? sanitize_email($user->user_email) : '';
			}

			private static function customer_name($booking_id): string {
				$name = trim((string) get_post_meta($booking_id, 'mpwpb_billing_name', true));
				if ($name) {
					return $name;
				}
				$user = get_user_by('id', (int) get_post_meta($booking_id, 'mpwpb_user_id', true));
				return $user ? $user->display_name : __('Customer', 'service-booking-manager');
			}

			private static function send_admin_request_email($booking_id): bool {
				$to = sanitize_email((string) get_option('admin_email'));
				if (!$to) {
					return false;
				}
				$service = get_the_title((int) get_post_meta($booking_id, 'mpwpb_id', true));
				$order_id = (int) get_post_meta($booking_id, 'mpwpb_order_id', true);
				$reason = (string) get_post_meta($booking_id, 'mpwpb_cancellation_reason', true);
				$url = admin_url('edit.php?post_type=' . MPWPB_Function::get_cpt() . '&page=mpwpb_cancellation_requests');
				$body = sprintf(
					'<p>%s</p><table cellspacing="0" cellpadding="8" style="border-collapse:collapse"><tr><th align="left">%s</th><td>#%d</td></tr><tr><th align="left">%s</th><td>#%d</td></tr><tr><th align="left">%s</th><td>%s</td></tr><tr><th align="left">%s</th><td>%s</td></tr><tr><th align="left">%s</th><td>%s</td></tr></table><p><a href="%s">%s</a></p>',
					esc_html__('A customer submitted a booking cancellation request.', 'service-booking-manager'),
					esc_html__('Booking', 'service-booking-manager'),
					$booking_id,
					esc_html__('Order', 'service-booking-manager'),
					$order_id,
					esc_html__('Customer', 'service-booking-manager'),
					esc_html(self::customer_name($booking_id)),
					esc_html__('Service', 'service-booking-manager'),
					esc_html($service),
					esc_html__('Reason', 'service-booking-manager'),
					esc_html($reason),
					esc_url($url),
					esc_html__('Review cancellation request', 'service-booking-manager')
				);
				return self::send_email($to, sprintf(__('Cancellation request for booking #%d', 'service-booking-manager'), $booking_id), __('New cancellation request', 'service-booking-manager'), $body);
			}

			private static function send_customer_decision_email($booking_id, $approved, $admin_note): bool {
				$to = self::customer_email($booking_id);
				if (!$to) {
					return false;
				}
				$service = get_the_title((int) get_post_meta($booking_id, 'mpwpb_id', true));
				$status_text = $approved ? __('approved', 'service-booking-manager') : __('not approved', 'service-booking-manager');
				$body = sprintf(
					'<p>%s</p><p><strong>%s:</strong> #%d<br><strong>%s:</strong> %s<br><strong>%s:</strong> %s</p>',
					esc_html(sprintf(__('Hello %s, your cancellation request has been %s.', 'service-booking-manager'), self::customer_name($booking_id), $status_text)),
					esc_html__('Booking', 'service-booking-manager'),
					$booking_id,
					esc_html__('Service', 'service-booking-manager'),
					esc_html($service),
					esc_html__('Decision', 'service-booking-manager'),
					esc_html(ucfirst($status_text))
				);
				if ($admin_note) {
					$body .= '<p><strong>' . esc_html__('Administrator note:', 'service-booking-manager') . '</strong><br>' . nl2br(esc_html($admin_note)) . '</p>';
				}
				$body .= '<p>' . esc_html($approved ? __('The booking and its associated order are now cancelled.', 'service-booking-manager') : __('Your booking remains active. Please contact us if you need assistance.', 'service-booking-manager')) . '</p>';
				$subject = $approved
					? sprintf(__('Booking #%d cancellation approved', 'service-booking-manager'), $booking_id)
					: sprintf(__('Booking #%d cancellation request update', 'service-booking-manager'), $booking_id);
				return self::send_email($to, $subject, __('Cancellation request update', 'service-booking-manager'), $body);
			}

			private static function send_email($to, $subject, $heading, $body): bool {
				if (function_exists('WC') && WC() && method_exists(WC(), 'mailer')) {
					$mailer = WC()->mailer();
					return (bool) $mailer->send($to, $subject, $mailer->wrap_message($heading, $body));
				}
				return (bool) wp_mail($to, $subject, $body, array('Content-Type: text/html; charset=UTF-8'));
			}

			private static function add_order_note($booking_id, $note): void {
				$order_id = (int) get_post_meta($booking_id, 'mpwpb_order_id', true);
				if (!$order_id || !$note) {
					return;
				}
				if (function_exists('wc_get_order')) {
					$order = wc_get_order($order_id);
					if ($order) {
						$order->add_order_note($note);
						return;
					}
				}
				if (class_exists('MPWPB_Native_Order') && method_exists('MPWPB_Native_Order', 'add_note')) {
					MPWPB_Native_Order::add_note($order_id, $note);
				}
			}
		}
	}
