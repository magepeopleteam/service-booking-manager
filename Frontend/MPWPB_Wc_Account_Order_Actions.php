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
			 * class's assets (JS/CSS/nonce) are enqueued on account pages, and
			 * WC-order bookings already carry
		 * mpwpb_user_id = the WC customer ID (Frontend/MPWPB_Woocommerce.php),
		 * so the dashboard's existing ownership check already authorizes
		 * correctly here with zero new code.
		 */
		class MPWPB_Wc_Account_Order_Actions {
			public function __construct() {
				add_filter('woocommerce_my_account_my_orders_actions', array($this, 'add_order_actions'), 10, 2);
				add_action('woocommerce_order_details_after_order_table', array($this, 'render_booking_actions'));
				add_action('woocommerce_after_account_orders', array($this, 'render_orders_modal'));
			}

			public function add_order_actions($actions, $order) {
				if (!MPWPB_Global_Function::is_wc_payment_mode()) {
					return $actions;
				}
				$booking_ids = MPWPB_User_Dashboard::get_bookings_for_order($order->get_id());
				if (!$booking_ids) {
					return $actions;
				}
				$booking_id = (int) $booking_ids[0];
				$is_recurring = count($booking_ids) > 1;
				$service_id = get_post_meta($booking_id, 'mpwpb_id', true);

				// Unlike Cancel/Reschedule, Reorder creates an unrelated NEW
				// booking rather than modifying this one -- available regardless
				// of this booking's status or the lead-time cutoff.
				$actions['mpwpb_reorder'] = array(
					'url' => MPWPB_User_Dashboard::get_reorder_url($booking_id, $service_id),
					'name' => __('Reorder', 'service-booking-manager'),
				);

				if ($is_recurring) {
					$actions['mpwpb_manage_recurring'] = array(
						'url' => $order->get_view_order_url() . '#mpwpb-recurring-bookings',
						'name' => __('Manage Recurring', 'service-booking-manager'),
					);
				}

				$active_booking_ids = array_values(array_filter($booking_ids, static function($id) {
					return get_post_meta($id, 'mpwpb_order_status', true) !== 'cancelled';
				}));
				if (!$active_booking_ids) {
					return $actions;
				}
				$cancellation_status = MPWPB_User_Dashboard::get_series_cancellation_status($booking_ids);
				if ($cancellation_status === MPWPB_Cancellation::STATUS_PENDING) {
					$actions['mpwpb_cancel_pending'] = array(
						'url' => '#',
						'name' => __('Cancellation Pending', 'service-booking-manager'),
					);
				}
				// Only for a booking that's actually still payable (not cancelled --
				// checked above), so a deposit taken before a later cancellation
				// doesn't leave a stale "Pay Balance" button behind.
				$balance_booking_id = 0;
				if (class_exists('MPWPB_Partial_Payment')) {
					foreach ($active_booking_ids as $active_booking_id) {
						if (MPWPB_Partial_Payment::get_amount_due($active_booking_id) > 0) {
							$balance_booking_id = (int) $active_booking_id;
							break;
						}
					}
				}
				if ($balance_booking_id) {
					$actions['mpwpb_pay_balance'] = array(
						'url' => add_query_arg('mpwpb_pay_balance', $balance_booking_id, home_url('/')),
						'name' => __('Pay Balance', 'service-booking-manager'),
					);
				}
				$cancellation_booking_id = MPWPB_User_Dashboard::get_series_cancellation_booking($booking_ids);
				$view_url = $order->get_view_order_url();

				if ($cancellation_status !== MPWPB_Cancellation::STATUS_PENDING && $cancellation_booking_id) {
					$actions['mpwpb_cancel'] = array(
						'url' => add_query_arg('mpwpb_cancel_booking', $cancellation_booking_id, wc_get_account_endpoint_url('orders')) . '#mpwpb-cancel-modal',
						'name' => $is_recurring ? __('Cancel Series', 'service-booking-manager') : __('Cancel Booking', 'service-booking-manager'),
					);
				}
				$date = get_post_meta($booking_id, 'mpwpb_date', true);
				if (!$is_recurring && $cancellation_status !== MPWPB_Cancellation::STATUS_PENDING && MPWPB_Booking_History::is_within_lead_time($date, 'reschedule')) {
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
				$booking_ids = MPWPB_User_Dashboard::get_bookings_for_order($order->get_id());
				if (!$booking_ids) {
					return;
				}
				$booking_id = (int) $booking_ids[0];
				$service_id = get_post_meta($booking_id, 'mpwpb_id', true);
				if (count($booking_ids) > 1) {
					$this->render_recurring_bookings($booking_ids, $service_id);
					return;
				}
				?>
				<h2><?php esc_html_e('Manage Your Booking', 'service-booking-manager'); ?></h2>
				<div class="mpwpb-wc-account-actions">
					<?php MPWPB_User_Dashboard::render_booking_actions($booking_id, $service_id, false); ?>
					<a href="<?php echo esc_url(MPWPB_User_Dashboard::get_reorder_url($booking_id, $service_id)); ?>" class="mpwpb-btn mpwpb-reorder-btn">
						<?php esc_html_e('Reorder', 'service-booking-manager'); ?>
					</a>
				</div>
				<?php MPWPB_User_Dashboard::render_action_modals(); ?>
				<?php
			}

			private function render_recurring_bookings(array $booking_ids, $service_id): void {
				$cancellation_status = MPWPB_User_Dashboard::get_series_cancellation_status($booking_ids);
				$cancellation_booking_id = MPWPB_User_Dashboard::get_series_cancellation_booking($booking_ids);
				$first_booking_id = (int) $booking_ids[0];
				$reschedule_lead = max(0, (int) MPWPB_Global_Function::get_settings('mpwpb_general_settings', 'reschedule_lead_time', 24));
				$locked_hint = $reschedule_lead > 0
					? sprintf(_n('Editing closes %d hour before the appointment.', 'Editing closes %d hours before the appointment.', $reschedule_lead, 'service-booking-manager'), $reschedule_lead)
					: __('This appointment can no longer be edited online.', 'service-booking-manager');
				$total_paid = 0;
				$total_due = 0;
				$first_due_booking = 0;
				foreach ($booking_ids as $payment_booking_id) {
					$total_paid += (float) get_post_meta($payment_booking_id, 'mpwpb_amount_paid', true);
					$due = (float) get_post_meta($payment_booking_id, 'mpwpb_amount_due', true);
					$total_due += $due;
					if (!$first_due_booking && $due > 0) {
						$first_due_booking = (int) $payment_booking_id;
					}
				}
				?>
				<section id="mpwpb-recurring-bookings" class="mpwpb-recurring-account">
					<header class="mpwpb-recurring-account__header">
						<div>
							<span class="mpwpb-recurring-account__eyebrow"><?php esc_html_e('Recurring booking', 'service-booking-manager'); ?></span>
							<h2><?php esc_html_e('Your appointment series', 'service-booking-manager'); ?></h2>
							<p><?php echo esc_html(sprintf(_n('%d appointment is included in this order.', '%d appointments are included in this order.', count($booking_ids), 'service-booking-manager'), count($booking_ids))); ?></p>
						</div>
						<span class="mpwpb-recurring-account__count"><?php echo esc_html(count($booking_ids)); ?></span>
					</header>
					<div class="mpwpb-recurring-account__list">
						<?php foreach ($booking_ids as $position => $booking_id) :
							$date = (string) get_post_meta($booking_id, 'mpwpb_date', true);
							$status = (string) get_post_meta($booking_id, 'mpwpb_order_status', true);
							$selection = MPWPB_Recurring_Account_Manager::selection($booking_id);
							$stored_services = (array) get_post_meta($booking_id, 'mpwpb_service', true);
							$service_names = array_values(array_filter(array_map(static function($item) { return is_array($item) ? ($item['name'] ?? '') : ''; }, $stored_services)));
							$balance_due = (float) get_post_meta($booking_id, 'mpwpb_amount_due', true);
							?>
							<article class="mpwpb-recurring-occurrence">
								<span class="mpwpb-recurring-occurrence__number"><?php echo esc_html($position + 1); ?></span>
								<div><small><?php echo esc_html(sprintf(__('Appointment #%d', 'service-booking-manager'), $position + 1)); ?></small><strong><?php echo esc_html(MPWPB_Global_Function::date_format($date)); ?></strong><span><?php echo esc_html(MPWPB_Global_Function::date_format($date, 'time')); ?></span><span class="mpwpb-recurring-occurrence__services"><i class="fas fa-concierge-bell" aria-hidden="true"></i><?php echo esc_html(implode(', ', $service_names)); ?></span></div>
								<span class="mpwpb-status mpwpb-status-<?php echo esc_attr(strtolower($status)); ?>"><?php echo esc_html(ucfirst($status)); ?></span>
								<div class="mpwpb-recurring-occurrence__actions">
									<?php if (MPWPB_Recurring_Account_Manager::can_edit($booking_id)) : ?>
										<button type="button" class="mpwpb-recurring-edit" data-booking-id="<?php echo esc_attr($booking_id); ?>" data-current-date="<?php echo esc_attr(substr($date, 0, 10)); ?>" data-selection="<?php echo esc_attr(wp_json_encode($selection)); ?>"><i class="fas fa-pen" aria-hidden="true"></i><?php esc_html_e('Edit', 'service-booking-manager'); ?></button>
									<?php else : ?>
										<span class="mpwpb-recurring-locked" title="<?php echo esc_attr($locked_hint); ?>"><i class="fas fa-lock" aria-hidden="true"></i><?php esc_html_e('Editing closed', 'service-booking-manager'); ?></span>
									<?php endif; ?>
									<?php if ($balance_due > 0) : ?><a href="<?php echo esc_url(add_query_arg('mpwpb_pay_balance', $booking_id, home_url('/'))); ?>" class="mpwpb-recurring-pay"><?php esc_html_e('Pay balance', 'service-booking-manager'); ?></a><?php endif; ?>
								</div>
							</article>
						<?php endforeach; ?>
					</div>
					<div class="mpwpb-recurring-account__payment">
						<div><small><?php esc_html_e('Paid across series', 'service-booking-manager'); ?></small><strong><?php echo wp_kses_post(MPWPB_Global_Function::wc_price(0, $total_paid)); ?></strong></div>
						<div><small><?php esc_html_e('Balance due', 'service-booking-manager'); ?></small><strong><?php echo wp_kses_post(MPWPB_Global_Function::wc_price(0, $total_due)); ?></strong></div>
						<?php if ($first_due_booking) : ?><a class="mpwpb-btn mpwpb-pay-balance-btn" href="<?php echo esc_url(add_query_arg('mpwpb_pay_balance', $first_due_booking, home_url('/'))); ?>"><?php esc_html_e('Pay Outstanding Balance', 'service-booking-manager'); ?></a><?php endif; ?>
					</div>
					<div class="mpwpb-recurring-account__actions">
						<?php if (MPWPB_Recurring_Account_Manager::can_add($booking_ids, $service_id)) : ?>
							<button type="button" class="mpwpb-btn mpwpb-recurring-add" data-booking-id="<?php echo esc_attr($first_booking_id); ?>" data-selection="<?php echo esc_attr(wp_json_encode(MPWPB_Recurring_Account_Manager::selection($first_booking_id))); ?>"><i class="fas fa-plus" aria-hidden="true"></i><?php esc_html_e('Add Appointment', 'service-booking-manager'); ?></button>
						<?php else : ?>
							<span class="mpwpb-recurring-limit"><i class="fas fa-info-circle" aria-hidden="true"></i><?php esc_html_e('This series has reached the configured appointment limit. You can still edit eligible appointments and add services.', 'service-booking-manager'); ?></span>
						<?php endif; ?>
						<?php if ($cancellation_status === MPWPB_Cancellation::STATUS_PENDING) : ?>
							<span class="mpwpb-cancellation-pending"><i class="fas fa-clock" aria-hidden="true"></i><?php esc_html_e('Series cancellation pending approval', 'service-booking-manager'); ?></span>
						<?php elseif ($cancellation_booking_id) : ?>
							<button type="button" class="mpwpb-btn mpwpb-cancel-btn" data-id="<?php echo esc_attr($cancellation_booking_id); ?>"><?php esc_html_e('Request Series Cancellation', 'service-booking-manager'); ?></button>
						<?php endif; ?>
						<a href="<?php echo esc_url(MPWPB_User_Dashboard::get_reorder_url($first_booking_id, $service_id)); ?>" class="mpwpb-btn mpwpb-reorder-btn"><?php esc_html_e('Book This Service Again', 'service-booking-manager'); ?></a>
					</div>
				</section>
				<?php MPWPB_Recurring_Account_Manager::render_modal((int) get_post_meta($first_booking_id, 'mpwpb_order_id', true), $booking_ids, $service_id); ?>
				<?php MPWPB_User_Dashboard::render_cancel_modal(); ?>
				<?php
			}

			public function render_orders_modal(): void {
				if (is_user_logged_in()) {
					MPWPB_User_Dashboard::render_cancel_modal();
				}
			}
		}
	}
	new MPWPB_Wc_Account_Order_Actions();
