<?php
	/*
	 * Custom Payment "My Account" page -- a WooCommerce-My-Account-style
	 * shell ([custom_payment_my_account] shortcode) for customers who
	 * booked through this plugin's own Custom Payment checkout
	 * (Frontend/MPWPB_Native_Checkout.php) rather than WooCommerce. Auto-
	 * created as a real published page (maybe_create_page(), called from
	 * MPWPB_Plugin::plugin_activate() on install/activation, same
	 * convention WooCommerce itself uses for its own "My Account" page) and
	 * again the moment Custom Payment mode is actually turned on via
	 * settings, in case the plugin was activated before that toggle
	 * existed on this site.
	 *
	 * Reuses Frontend/MPWPB_User_Dashboard.php's existing account-details
	 * form and GDPR "Privacy & Data" section (both now public+static)
	 * instead of re-implementing them, so there's one editable profile /
	 * one GDPR request flow shared by both dashboards, not two.
	 */
	if (!defined('ABSPATH')) {
		die;
	} // Cannot access pages directly.
	if (!class_exists('MPWPB_Custom_Payment_My_Account')) {
		class MPWPB_Custom_Payment_My_Account {
			const OPTION_PAGE_ID = 'mpwpb_custom_payment_my_account_page_id';
			const PAGE_SLUG = 'custom-payment-my-account';

			public function __construct() {
				add_shortcode('custom_payment_my_account', array($this, 'render'));
				// Retroactive creation the moment Custom Payment mode is
				// actually turned on -- covers sites where the plugin was
				// already active before this feature/toggle existed.
				add_action('update_option_mpwpb_payment_method_settings', array($this, 'maybe_create_page_on_settings_change'), 10, 2);
			}

			public function maybe_create_page_on_settings_change($old_value, $new_value): void {
				if (is_array($new_value) && ($new_value['payment_method_type'] ?? '') === 'custom') {
					self::maybe_create_page();
				}
			}

			/** Idempotent -- safe to call on every activation, or repeatedly from the settings hook above. */
			public static function maybe_create_page(): void {
				$page_id = (int) get_option(self::OPTION_PAGE_ID);
				if ($page_id && get_post_status($page_id)) {
					return;
				}
				$existing = get_page_by_path(self::PAGE_SLUG);
				if ($existing) {
					update_option(self::OPTION_PAGE_ID, $existing->ID);
					return;
				}
				$page_id = wp_insert_post(array(
					'post_title' => esc_html__('My Dashboard', 'service-booking-manager'),
					'post_name' => self::PAGE_SLUG,
					'post_content' => '[custom_payment_my_account]',
					'post_status' => 'publish',
					'post_type' => 'page',
				));
				if ($page_id && !is_wp_error($page_id)) {
					update_option(self::OPTION_PAGE_ID, $page_id);
				}
			}

			public function render($atts) {
				ob_start();
				if (!MPWPB_Global_Function::is_custom_payment_mode()) {
					echo '<p>' . esc_html__('This page is only available when Custom Payment is enabled.', 'service-booking-manager') . '</p>';
					return ob_get_clean();
				}
				if (!is_user_logged_in()) {
					?>
					<div class="mpStyle mpwpb-login-form">
						<h3><?php esc_html_e('Please login to view your account', 'service-booking-manager'); ?></h3>
						<?php wp_login_form(array('redirect' => get_permalink())); ?>
						<p>
							<?php esc_html_e('Don\'t have an account?', 'service-booking-manager'); ?>
							<a href="<?php echo esc_url(wp_registration_url()); ?>"><?php esc_html_e('Register here', 'service-booking-manager'); ?></a>
						</p>
					</div>
					<?php
					return ob_get_clean();
				}

				$user_id = get_current_user_id();
				$tab = isset($_GET['cp_tab']) ? sanitize_key(wp_unslash($_GET['cp_tab'])) : 'dashboard';
				$is_staff = MPWPB_Staff_DashBoard::is_staff($user_id);
				$tabs = array(
					'dashboard' => esc_html__('Dashboard', 'service-booking-manager'),
					'orders' => esc_html__('Orders', 'service-booking-manager'),
				);
				if ($is_staff) {
					$tabs['my-service'] = esc_html__('My Service', 'service-booking-manager');
					$tabs['my-appointment'] = esc_html__('My Appointment', 'service-booking-manager');
					if (MPWPB_Staff_DashBoard::can_modify_own_schedule($user_id)) {
						$tabs['my-schedule'] = esc_html__('My Schedule', 'service-booking-manager');
					}
				}
				$tabs['account-details'] = esc_html__('Account Details', 'service-booking-manager');
				if (MPWPB_Global_Function::is_gdpr_enabled()) {
					$tabs['privacy'] = esc_html__('Privacy & Data', 'service-booking-manager');
				}
				?>
				<div class="mpStyle mpwpb-user-dashboard mpwpb-cp-my-account">
					<div class="mpwpb-dashboard-tabs">
						<ul class="mpwpb-tabs-nav">
							<?php foreach ($tabs as $tab_key => $tab_label) : ?>
								<li class="<?php echo $tab === $tab_key ? 'active' : ''; ?>">
									<a href="<?php echo esc_url(add_query_arg('cp_tab', $tab_key)); ?>"><?php echo esc_html($tab_label); ?></a>
								</li>
							<?php endforeach; ?>
							<li><a href="<?php echo esc_url(wp_logout_url(get_permalink())); ?>"><?php esc_html_e('Logout', 'service-booking-manager'); ?></a></li>
						</ul>
					</div>
					<div class="mpwpb-dashboard-content">
						<?php
						switch ($tab) {
							case 'orders':
								$this->orders_tab($user_id);
								break;
							case 'my-service':
								if ($is_staff) {
									MPWPB_Staff_DashBoard::render_my_service_tab($user_id);
								}
								break;
							case 'my-appointment':
								if ($is_staff) {
									MPWPB_Staff_DashBoard::render_my_appointment_tab($user_id);
								}
								break;
							case 'my-schedule':
								if ($is_staff && MPWPB_Staff_DashBoard::can_modify_own_schedule($user_id)) {
									MPWPB_Staff_DashBoard::render_my_schedule_tab($user_id);
								}
								break;
							case 'account-details':
								MPWPB_User_Dashboard::user_profile($user_id);
								break;
							case 'privacy':
								if (MPWPB_Global_Function::is_gdpr_enabled()) {
									MPWPB_User_Dashboard::privacy_data_tab($user_id);
								}
								break;
							default:
								$this->dashboard_tab($user_id);
								break;
						}
						?>
					</div>
				</div>
				<?php
				return ob_get_clean();
			}

			private function dashboard_tab(int $user_id): void {
				$user = wp_get_current_user();
				$orders = $this->get_custom_payment_orders($user_id, 5);
				?>
				<div class="mpwpb-cp-dashboard">
					<p>
						<?php
						printf(
							/* translators: 1: customer display name (bold), 2: opening <a> tag for the logout link, 3: closing </a> tag */
							esc_html__('Hello %1$s (not you? %2$sLog out%3$s)', 'service-booking-manager'),
							'<strong>' . esc_html($user->display_name) . '</strong>',
							'<a href="' . esc_url(wp_logout_url(get_permalink())) . '">',
							'</a>'
						);
						?>
					</p>
					<p><?php esc_html_e('From your account dashboard you can view your recent custom payment orders, manage your account details, and request your data.', 'service-booking-manager'); ?></p>
					<?php if ($orders) : ?>
						<h3><?php esc_html_e('Recent Orders', 'service-booking-manager'); ?></h3>
						<?php $this->orders_table($orders); ?>
					<?php endif; ?>
					<?php if (MPWPB_Global_Function::is_gdpr_enabled()) : ?>
						<div class="mpwpb-message info" style="margin-top:20px;">
							<?php
							printf(
								/* translators: %s: "Privacy & Data" tab link */
								esc_html__('Want to manage your privacy consent or request your data be deleted? Visit the %s tab.', 'service-booking-manager'),
								'<a href="' . esc_url(add_query_arg('cp_tab', 'privacy')) . '">' . esc_html__('Privacy & Data', 'service-booking-manager') . '</a>'
							);
							?>
						</div>
					<?php endif; ?>
				</div>
				<?php
			}

			private function orders_tab(int $user_id): void {
				$order_id = isset($_GET['order_id']) ? absint($_GET['order_id']) : 0;
				if ($order_id) {
					// This account view always requires logged-in ownership. Guest
					// confirmation links use the separate random native order key.
					$order = get_post($order_id);
					$owner_id = (int) get_post_meta($order_id, 'mpwpb_user_id', true);
					if ($order && $order->post_type === MPWPB_Native_Order::CPT && $owner_id === $user_id) {
						$this->render_order_details($order);
						return;
					}
					echo '<div class="mpwpb-message error">' . esc_html__('That order could not be found.', 'service-booking-manager') . '</div>';
				}
				$orders = $this->get_custom_payment_orders($user_id, 50);
				echo '<h3>' . esc_html__('My Custom Payment Orders', 'service-booking-manager') . '</h3>';
				if (empty($orders)) {
					echo '<div class="mpwpb-no-bookings">' . esc_html__('You have no custom payment orders yet.', 'service-booking-manager') . '</div>';
					return;
				}
				$this->orders_table($orders);
			}
			private function render_order_details(WP_Post $order): void {
				$order_id = $order->ID;
				$status = (string) get_post_meta($order_id, 'mpwpb_order_status', true);
				$total = (float) get_post_meta($order_id, 'mpwpb_total', true);
				$gateway = (string) get_post_meta($order_id, 'mpwpb_payment_method', true);
				$item = get_post_meta($order_id, 'mpwpb_line_items', true);
				$item = is_array($item) ? $item : [];
				$post_id = $item['mpwpb_id'] ?? 0;
				if (!isset($item['mpwpb_tp'])) {
					$item['mpwpb_tp'] = $total;
				}
				?>
				<p><a href="<?php echo esc_url(remove_query_arg('order_id')); ?>">&laquo; <?php esc_html_e('Back to Orders', 'service-booking-manager'); ?></a></p>
				<h3>
					<?php
					printf(
						/* translators: %d: order ID */
						esc_html__('Order #%d', 'service-booking-manager'),
						$order_id
					);
					?>
				</h3>
				<table class="mpwpb-bookings-table">
					<tbody>
						<tr>
							<td><?php esc_html_e('Date', 'service-booking-manager'); ?></td>
							<td><?php echo esc_html(get_the_date('', $order)); ?></td>
						</tr>
						<tr>
							<td><?php esc_html_e('Status', 'service-booking-manager'); ?></td>
							<td><span class="mpwpb-status mpwpb-status-<?php echo esc_attr(strtolower($status)); ?>"><?php echo esc_html($status ? ucfirst($status) : '—'); ?></span></td>
						</tr>
						<tr>
							<td><?php esc_html_e('Payment Method', 'service-booking-manager'); ?></td>
							<td><?php echo esc_html($gateway ? ucfirst($gateway) : '—'); ?></td>
						</tr>
						<tr>
							<td><?php esc_html_e('Total', 'service-booking-manager'); ?></td>
							<td><?php echo wp_kses_post(MPWPB_Global_Function::wc_price(0, $total)); ?></td>
						</tr>
					</tbody>
				</table>
				<?php if (!empty($item)) : ?>
					<?php MPWPB_Native_Checkout::render_booking_recap($item, $post_id, false); ?>
				<?php endif; ?>
				<?php MPWPB_Native_Checkout::render_customer_info_card($order_id); ?>
				<?php $this->render_manage_booking_section($order_id); ?>
				<?php
			}

			/**
			 * "Manage Your Booking" block on the order details view -- the
			 * real interactive Cancel/Reschedule buttons + modal, plus a
			 * Reorder link. Mirrors Frontend/MPWPB_Wc_Account_Order_Actions
			 * ::render_booking_actions() so Custom Payment orders behave the
			 * same way WooCommerce orders already do on the View Order page.
			 */
			private function render_manage_booking_section(int $order_id): void {
				$booking_id = MPWPB_User_Dashboard::get_booking_for_order($order_id);
				if (!$booking_id) {
					return;
				}
				$service_id = get_post_meta($booking_id, 'mpwpb_id', true);
				?>
				<h3><?php esc_html_e('Manage Your Booking', 'service-booking-manager'); ?></h3>
				<p class="mpwpb-wc-account-actions">
					<?php MPWPB_User_Dashboard::render_booking_actions($booking_id, $service_id, true); ?>
					<a href="<?php echo esc_url(MPWPB_User_Dashboard::get_reorder_url($booking_id, $service_id)); ?>" class="mpwpb-btn mpwpb-reorder-btn">
						<?php esc_html_e('Reorder', 'service-booking-manager'); ?>
					</a>
				</p>
				<?php
			}

			/**
			 * Reorder + the real interactive Cancel/Reschedule buttons for
			 * one order row in the orders list. Mirrors
			 * Frontend/MPWPB_User_Dashboard.php::booking_history()'s own
			 * pattern exactly: render_booking_actions($id, $service_id, false)
			 * per row (render_modal=false -- N rows would otherwise render N
			 * copies of the fixed-ID #mpwpb-reschedule-modal), then
			 * orders_table() calls render_reschedule_modal() once after the
			 * loop. This makes Cancel/Reschedule fully interactive directly
			 * in the list (immediate AJAX cancel / popup for reschedule),
			 * not just a link through to the order details page.
			 */
			private function render_booking_action_links(int $order_id): void {
				$booking_id = MPWPB_User_Dashboard::get_booking_for_order($order_id);
				if (!$booking_id) {
					return;
				}
				$service_id = get_post_meta($booking_id, 'mpwpb_id', true);
				?>
				<a href="<?php echo esc_url(MPWPB_User_Dashboard::get_reorder_url($booking_id, $service_id)); ?>" class="mpwpb-btn mpwpb-reorder-btn">
					<?php esc_html_e('Reorder', 'service-booking-manager'); ?>
				</a>
				<?php MPWPB_User_Dashboard::render_booking_actions($booking_id, $service_id, false); ?>
				<?php
			}

			/** @return WP_Post[] mpwpb_order posts -- these are only ever created by the Custom Payment checkout, never WooCommerce, so no extra payment-method filtering is needed. */
			private function get_custom_payment_orders(int $user_id, int $limit): array {
				return get_posts(array(
					'post_type' => MPWPB_Native_Order::CPT,
					'post_status' => 'publish',
					'posts_per_page' => $limit,
					'meta_key' => 'mpwpb_user_id',
					'meta_value' => $user_id,
					'orderby' => 'date',
					'order' => 'DESC',
				));
			}

			private function orders_table(array $orders): void {
				?>
				<table class="mpwpb-bookings-table">
					<thead>
						<tr>
							<th><?php esc_html_e('Order', 'service-booking-manager'); ?></th>
							<th><?php esc_html_e('Date', 'service-booking-manager'); ?></th>
							<th><?php esc_html_e('Status', 'service-booking-manager'); ?></th>
							<th><?php esc_html_e('Total', 'service-booking-manager'); ?></th>
							<th><?php esc_html_e('Payment Method', 'service-booking-manager'); ?></th>
							<th><?php esc_html_e('Actions', 'service-booking-manager'); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($orders as $order) :
							$status = (string) get_post_meta($order->ID, 'mpwpb_order_status', true);
							$total = (float) get_post_meta($order->ID, 'mpwpb_total', true);
							$gateway = (string) get_post_meta($order->ID, 'mpwpb_payment_method', true);
							$view_url = add_query_arg(['cp_tab' => 'orders', 'order_id' => $order->ID]);
							?>
							<tr>
								<td>#<?php echo esc_html($order->ID); ?></td>
								<td><?php echo esc_html(get_the_date('', $order)); ?></td>
								<td><span class="mpwpb-status mpwpb-status-<?php echo esc_attr(strtolower($status)); ?>"><?php echo esc_html($status ? ucfirst($status) : '—'); ?></span></td>
								<td><?php echo wp_kses_post(MPWPB_Global_Function::wc_price(0, $total)); ?></td>
								<td><?php echo esc_html($gateway ? ucfirst($gateway) : '—'); ?></td>
								<td>
									<a class="mpwpb-btn mpwpb-view-btn" href="<?php echo esc_url($view_url); ?>"><?php esc_html_e('View Details', 'service-booking-manager'); ?></a>
									<?php $this->render_booking_action_links($order->ID); ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<?php MPWPB_User_Dashboard::render_action_modals(); ?>
				<?php
			}
		}
		new MPWPB_Custom_Payment_My_Account();
	}
