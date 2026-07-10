<?php
	/*
   * @Author 		engr.sumonazma@gmail.com
   * Copyright: 	mage-people.com
   */
	if (!defined('ABSPATH')) {
		die;
	} // Cannot access pages directly.

	if (!class_exists('MPWPB_Wc_Account_Order_Actions')) {
		/**
		 * Surfaces Cancel/Reschedule for WooCommerce orders inside WooCommerce's
		 * own My Account > Orders list and View Order page, reusing the exact
		 * same buttons/modal/AJAX actions the customer dashboard
		 * (Frontend/MPWPB_User_Dashboard.php) already uses -- no new AJAX
		 * actions or ownership logic needed here. This works because that
		 * class's assets (JS/CSS/nonce) are already enqueued unconditionally
		 * on every front-end page, and WC-order bookings already carry
		 * mpwpb_user_id = the WC customer ID (Frontend/MPWPB_Woocommerce.php),
		 * so the dashboard's existing ownership check already authorizes
		 * correctly here with zero new code.
		 */
		class MPWPB_Wc_Account_Order_Actions {
			public function __construct() {
				add_filter('woocommerce_my_account_my_orders_actions', array($this, 'add_order_actions'), 10, 2);
				add_action('woocommerce_order_details_after_order_table', array($this, 'render_booking_actions'));
			}

			public function add_order_actions($actions, $order) {
				if (!MPWPB_Global_Function::is_wc_payment_mode()) {
					return $actions;
				}
				$booking_id = MPWPB_User_Dashboard::get_booking_for_order($order->get_id());
				if (!$booking_id) {
					return $actions;
				}
				$service_id = get_post_meta($booking_id, 'mpwpb_id', true);
				$status = get_post_meta($booking_id, 'mpwpb_order_status', true);

				// Unlike Cancel/Reschedule, Reorder creates an unrelated NEW
				// booking rather than modifying this one -- available regardless
				// of this booking's status or the lead-time cutoff.
				$actions['mpwpb_reorder'] = array(
					'url' => MPWPB_User_Dashboard::get_reorder_url($booking_id, $service_id),
					'name' => __('Reorder', 'service-booking-manager'),
				);

				if ($status === 'cancelled') {
					return $actions;
				}
				// Only for a booking that's actually still payable (not cancelled --
				// checked above), so a deposit taken before a later cancellation
				// doesn't leave a stale "Pay Balance" button behind.
				if (class_exists('MPWPB_Partial_Payment') && MPWPB_Partial_Payment::get_amount_due($booking_id) > 0) {
					$actions['mpwpb_pay_balance'] = array(
						'url' => add_query_arg('mpwpb_pay_balance', $booking_id, home_url('/')),
						'name' => __('Pay Balance', 'service-booking-manager'),
					);
				}
				$date = get_post_meta($booking_id, 'mpwpb_date', true);
				$view_url = $order->get_view_order_url();

				if (MPWPB_Booking_History::is_within_lead_time($date, 'cancel')) {
					$actions['mpwpb_cancel'] = array(
						'url' => $view_url,
						'name' => __('Cancel Booking', 'service-booking-manager'),
					);
				}
				if (MPWPB_Booking_History::is_within_lead_time($date, 'reschedule')) {
					$actions['mpwpb_reschedule'] = array(
						'url' => $view_url,
						'name' => __('Reschedule Booking', 'service-booking-manager'),
					);
				}
				return $actions;
			}

			public function render_booking_actions($order) {
				if (!MPWPB_Global_Function::is_wc_payment_mode() || !is_user_logged_in()) {
					return;
				}
				if ((int) $order->get_customer_id() !== get_current_user_id()) {
					return;
				}
				$booking_id = MPWPB_User_Dashboard::get_booking_for_order($order->get_id());
				if (!$booking_id) {
					return;
				}
				$service_id = get_post_meta($booking_id, 'mpwpb_id', true);
				?>
				<h2><?php esc_html_e('Manage Your Booking', 'service-booking-manager'); ?></h2>
				<p class="mpwpb-wc-account-actions">
					<?php MPWPB_User_Dashboard::render_booking_actions($booking_id, $service_id, true); ?>
					<a href="<?php echo esc_url(MPWPB_User_Dashboard::get_reorder_url($booking_id, $service_id)); ?>" class="mpwpb-btn mpwpb-reorder-btn">
						<?php esc_html_e('Reorder', 'service-booking-manager'); ?>
					</a>
				</p>
				<?php
			}
		}
	}
	new MPWPB_Wc_Account_Order_Actions();
