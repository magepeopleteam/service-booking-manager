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
				$tabs = array(
					'dashboard' => esc_html__('Dashboard', 'service-booking-manager'),
					'orders' => esc_html__('Orders', 'service-booking-manager'),
					'account-details' => esc_html__('Account Details', 'service-booking-manager'),
				);
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
				$orders = $this->get_custom_payment_orders($user_id, 50);
				echo '<h3>' . esc_html__('My Custom Payment Orders', 'service-booking-manager') . '</h3>';
				if (empty($orders)) {
					echo '<div class="mpwpb-no-bookings">' . esc_html__('You have no custom payment orders yet.', 'service-booking-manager') . '</div>';
					return;
				}
				$this->orders_table($orders);
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
						</tr>
					</thead>
					<tbody>
						<?php foreach ($orders as $order) :
							$status = (string) get_post_meta($order->ID, 'mpwpb_order_status', true);
							$total = (float) get_post_meta($order->ID, 'mpwpb_total', true);
							$gateway = (string) get_post_meta($order->ID, 'mpwpb_payment_method', true);
							?>
							<tr>
								<td>#<?php echo esc_html($order->ID); ?></td>
								<td><?php echo esc_html(get_the_date('', $order)); ?></td>
								<td><span class="mpwpb-status mpwpb-status-<?php echo esc_attr(strtolower($status)); ?>"><?php echo esc_html($status ? ucfirst($status) : '—'); ?></span></td>
								<td><?php echo wp_kses_post(MPWPB_Global_Function::wc_price(0, $total)); ?></td>
								<td><?php echo esc_html($gateway ? ucfirst($gateway) : '—'); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<?php
			}
		}
		new MPWPB_Custom_Payment_My_Account();
	}
