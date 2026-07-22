<?php
	/*
	 * Adds "My Service" / "My Appointment" / "My Schedule" entries to
	 * WooCommerce's native My Account left-side menu for staff members
	 * (mpwpb_staff role), shown only when WooCommerce is the active
	 * payment method (My Schedule additionally requires the admin-set
	 * mpwpb_staff_modify_holiday flag). Content is rendered by
	 * Admin/MPWPB_Staff_DashBoard.php's shared static methods -- see
	 * Frontend/MPWPB_Custom_Payment_My_Account.php for the equivalent tabs
	 * on the Custom Payment "My Dashboard" page, both built on the same
	 * underlying MPWPB_Staff_DashBoard methods.
	 */
	if (!defined('ABSPATH')) {
		die;
	} // Cannot access pages directly.
	if (!class_exists('MPWPB_Wc_Staff_Account')) {
		class MPWPB_Wc_Staff_Account {
			const EP_SERVICES = 'mpwpb-my-service';
			const EP_APPOINTMENTS = 'mpwpb-my-appointment';
			const EP_SCHEDULE = 'mpwpb-my-schedule';

			public function __construct() {
				add_action('init', array($this, 'add_endpoints'));
				add_filter('woocommerce_get_query_vars', array($this, 'add_query_vars'));
				add_filter('woocommerce_account_menu_items', array($this, 'add_menu_items'));
				add_action('woocommerce_account_' . self::EP_SERVICES . '_endpoint', array($this, 'render_services_endpoint'));
				add_action('woocommerce_account_' . self::EP_APPOINTMENTS . '_endpoint', array($this, 'render_appointments_endpoint'));
				add_action('woocommerce_account_' . self::EP_SCHEDULE . '_endpoint', array($this, 'render_schedule_endpoint'));
			}

			/**
			 * Guarded by class_exists('WooCommerce') -- this plugin runs
			 * fine with WooCommerce absent entirely (see the WC-optional
			 * native checkout work), and EP_ROOT/EP_PAGES are WooCommerce's
			 * own constants, undefined if WC isn't installed. This hook body
			 * only runs on 'init', so the guard is enough to make the whole
			 * file safe to load unconditionally.
			 */
			public function add_endpoints(): void {
				if (!class_exists('WooCommerce')) {
					return;
				}
				add_rewrite_endpoint(self::EP_SERVICES, EP_ROOT | EP_PAGES);
				add_rewrite_endpoint(self::EP_APPOINTMENTS, EP_ROOT | EP_PAGES);
				add_rewrite_endpoint(self::EP_SCHEDULE, EP_ROOT | EP_PAGES);
				// One-time flush so the new endpoints resolve instead of 404ing --
				// add_rewrite_endpoint() only takes effect after rewrite rules are
				// regenerated, which normally only happens on plugin (re)activation.
				// Bump the stored value (not just a boolean) whenever a new
				// endpoint is added later, so it flushes again instead of
				// staying stuck on an earlier "already flushed" state.
				if (get_option('mpwpb_staff_account_endpoints_flushed') !== 'v2') {
					flush_rewrite_rules();
					update_option('mpwpb_staff_account_endpoints_flushed', 'v2');
				}
			}

			public function add_query_vars($vars) {
				$vars[self::EP_SERVICES] = self::EP_SERVICES;
				$vars[self::EP_APPOINTMENTS] = self::EP_APPOINTMENTS;
				$vars[self::EP_SCHEDULE] = self::EP_SCHEDULE;
				return $vars;
			}

			private function should_show(): bool {
				return MPWPB_Global_Function::is_wc_payment_mode() && MPWPB_Staff_DashBoard::is_staff();
			}

			public function add_menu_items($items) {
				if (!$this->should_show()) {
					return $items;
				}
				// Insert before "Logout" rather than appending after it.
				$logout = null;
				if (isset($items['customer-logout'])) {
					$logout = $items['customer-logout'];
					unset($items['customer-logout']);
				}
				$items[self::EP_SERVICES] = esc_html__('My Service', 'service-booking-manager');
				$items[self::EP_APPOINTMENTS] = esc_html__('My Appointment', 'service-booking-manager');
				if (MPWPB_Staff_DashBoard::can_modify_own_schedule(get_current_user_id())) {
					$items[self::EP_SCHEDULE] = esc_html__('My Schedule', 'service-booking-manager');
				}
				if ($logout !== null) {
					$items['customer-logout'] = $logout;
				}
				return $items;
			}

			public function render_services_endpoint(): void {
				if (!$this->should_show()) {
					echo '<p>' . esc_html__('This page is only available to staff members.', 'service-booking-manager') . '</p>';
					return;
				}
				MPWPB_Staff_DashBoard::render_my_service_tab(get_current_user_id());
			}

			public function render_appointments_endpoint(): void {
				if (!$this->should_show()) {
					echo '<p>' . esc_html__('This page is only available to staff members.', 'service-booking-manager') . '</p>';
					return;
				}
				MPWPB_Staff_DashBoard::render_my_appointment_tab(get_current_user_id());
			}

			public function render_schedule_endpoint(): void {
				$user_id = get_current_user_id();
				if (!$this->should_show() || !MPWPB_Staff_DashBoard::can_modify_own_schedule($user_id)) {
					echo '<p>' . esc_html__('This page is only available to staff members.', 'service-booking-manager') . '</p>';
					return;
				}
				MPWPB_Staff_DashBoard::render_my_schedule_tab($user_id);
			}
		}
		new MPWPB_Wc_Staff_Account();
	}
