<?php
	/*
	 * Payment Method settings: a two-button toggle (WooCommerce / Custom
	 * Payment) plus, in Custom mode, gateway cards for PayPal / Stripe /
	 * Offline Payment and a Booking Confirmation Page picker. Also renders
	 * the Currency tab. Always registered (even when WooCommerce is active)
	 * so a site can switch away from WooCommerce deliberately.
	 */
	if (!defined('ABSPATH')) {
		die;
	} // Cannot access pages directly.
	if (!class_exists('MPWPB_Native_Checkout_Settings')) {
		class MPWPB_Native_Checkout_Settings {
			public function __construct() {
				add_filter('mpwpb_settings_sec_reg', [$this, 'sec_reg'], 92);
				add_filter('mpwpb_settings_sec_fields', [$this, 'sec_fields'], 15);
				add_action('admin_notices', [$this, 'maybe_show_payment_notice']);
				add_action('wp_ajax_mpwpb_install_activate_woocommerce', [$this, 'install_activate_woocommerce']);
				add_action('wp_ajax_mpwpb_toggle_wc_gateway', [$this, 'toggle_wc_gateway']);
			}
			/**
			 * Enables/disables a real WooCommerce payment gateway from our
			 * dashboard view, so the admin doesn't have to leave this page.
			 */
			public function toggle_wc_gateway(): void {
				if (!current_user_can('manage_woocommerce') && !current_user_can('manage_options')) {
					wp_send_json_error(['message' => esc_html__('You do not have permission to do this.', 'service-booking-manager')]);
				}
				$gateway_id = isset($_POST['gateway']) ? sanitize_key(wp_unslash($_POST['gateway'])) : '';
				if (!$gateway_id || !isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'mpwpb_toggle_wc_gateway_' . $gateway_id)) {
					wp_send_json_error(['message' => esc_html__('Security check failed.', 'service-booking-manager')]);
				}
				if (!MPWPB_Global_Function::check_woocommerce()) {
					wp_send_json_error(['message' => esc_html__('WooCommerce is not active.', 'service-booking-manager')]);
				}
				$gateways = WC()->payment_gateways()->payment_gateways();
				if (!isset($gateways[$gateway_id])) {
					wp_send_json_error(['message' => esc_html__('Unknown payment gateway.', 'service-booking-manager')]);
				}
				$enabled = !empty($_POST['enabled']) && $_POST['enabled'] === '1';
				$gateways[$gateway_id]->update_option('enabled', $enabled ? 'yes' : 'no');
				wp_send_json_success(['enabled' => $enabled]);
			}
			public function sec_reg($default_sec): array {
				$sections = [
					[
						'id' => 'mpwpb_payment_method_settings',
						'icon' => 'mi mi-credit-card',
						'title' => esc_html__('Payment Method', 'service-booking-manager'),
						'callback' => [$this, 'render_payment_method_panel'],
					],
					[
						'id' => 'mpwpb_currency_settings',
						'icon' => 'mi mi-globe',
						'title' => esc_html__('Currency Settings', 'service-booking-manager'),
					],
				];
				return array_merge($default_sec, $sections);
			}
			public function sec_fields($default_fields): array {
				$default_fields['mpwpb_currency_settings'] = [
					[
						'name' => 'symbol',
						'label' => esc_html__('Currency Symbol', 'service-booking-manager'),
						'desc' => esc_html__('Used to format all booking prices when Payment Method is Custom.', 'service-booking-manager'),
						'type' => 'text',
						'default' => '$',
					],
					[
						'name' => 'position',
						'label' => esc_html__('Currency Position', 'service-booking-manager'),
						'type' => 'select',
						'options' => [
							'left' => esc_html__('Left ($99.00)', 'service-booking-manager'),
							'right' => esc_html__('Right (99.00$)', 'service-booking-manager'),
							'left_space' => esc_html__('Left with space ($ 99.00)', 'service-booking-manager'),
							'right_space' => esc_html__('Right with space (99.00 $)', 'service-booking-manager'),
						],
						'default' => 'left',
					],
					[
						'name' => 'decimals',
						'label' => esc_html__('Number of Decimals', 'service-booking-manager'),
						'type' => 'number',
						'min' => 0,
						'max' => 4,
						'default' => 2,
					],
					[
						'name' => 'decimal_separator',
						'label' => esc_html__('Decimal Separator', 'service-booking-manager'),
						'type' => 'text',
						'default' => '.',
					],
					[
						'name' => 'thousand_separator',
						'label' => esc_html__('Thousand Separator', 'service-booking-manager'),
						'type' => 'text',
						'default' => ',',
					],
				];
				return $default_fields;
			}
			/**
			 * Fully custom section renderer (registered as the section
			 * 'callback' in sec_reg() above, so no per-field table is used
			 * for this tab). Prints its own Save Changes button, since it
			 * intentionally registers no fields via sec_fields().
			 */
			public function render_payment_method_panel(): void {
				$payment_type = MPWPB_Global_Function::get_payment_method_type();
				$wc_status = MPWPB_Global_Function::check_woocommerce();
				$wc_active = $wc_status == 1;
				$option = 'mpwpb_payment_method_settings';
				$gateways = [
					'paypal' => [
						'label' => esc_html__('PayPal', 'service-booking-manager'),
						'icon' => 'fab fa-paypal',
						'gradient' => 'linear-gradient(135deg,#003b7a,#0073c4)',
					],
					'stripe' => [
						'label' => esc_html__('Credit/Debit Card (Stripe)', 'service-booking-manager'),
						'icon' => 'fab fa-stripe-s',
						'gradient' => 'linear-gradient(135deg,#4338ca,#6d5bf0)',
					],
					'offline' => [
						'label' => esc_html__('Offline Payment', 'service-booking-manager'),
						'icon' => 'fas fa-money-check-dollar',
						'gradient' => 'linear-gradient(135deg,#0f5f52,#1f9c82)',
					],
				];
				?>
				<style>
					.mpwpb-pm-toggle { display: inline-flex; border: 1px solid #dcdcde; border-radius: 6px; overflow: hidden; margin-bottom: 20px; }
					.mpwpb-pm-toggle-btn { border: none; background: #fff; padding: 10px 22px; font-weight: 600; cursor: pointer; color: #1d2327; }
					.mpwpb-pm-toggle-btn.is-active { background: #2451e0; color: #fff; }
					.mpwpb-pm-panel { border: 1px solid #dcdcde; border-radius: 8px; padding: 20px; background: #fff; }
					.mpwpb-pm-notice { border-radius: 6px; padding: 14px 16px; }
					.mpwpb-pm-notice-warning { background: #fff6dd; border: 1px solid #f0dfa6; }
					.mpwpb-pm-notice-success { background: #eaf7ee; border: 1px solid #b7e3c4; }
					.mpwpb-pm-notice p { margin: 6px 0 12px; }
					.mpwpb-pm-btn-primary { background: #2451e0; color: #fff; border: none; border-radius: 5px; padding: 9px 18px; font-weight: 600; cursor: pointer; }
					.mpwpb-pm-btn-primary:disabled { opacity: .6; cursor: default; }
					.mpwpb-gateway-card { border-radius: 8px; margin-bottom: 14px; color: #fff; background: #444; }
					.mpwpb-gateway-row { display: flex; align-items: center; gap: 12px; padding: 16px 18px; }
					.mpwpb-gateway-icon { font-size: 20px; width: 26px; text-align: center; }
					.mpwpb-gateway-name { font-weight: 600; flex: 1; }
					.mpwpb-gateway-status { border-radius: 999px; padding: 3px 12px; font-size: 12px; font-weight: 600; background: rgba(255,255,255,.25); }
					.mpwpb-gateway-status.is-enabled { background: #1c9a5b; }
					.mpwpb-gateway-configure { border: 1px solid rgba(255,255,255,.7); background: rgba(255,255,255,.1); color: #fff; border-radius: 5px; padding: 6px 14px; cursor: pointer; }
					.mpwpb-gw-panel { border: 1px solid #dcdcde; border-top: none; border-radius: 0 0 8px 8px; padding: 16px 18px; margin: -14px 0 14px; background: #fafafa; }
					.mpwpb-gw-panel label { display: block; margin-bottom: 12px; font-weight: 600; }
					.mpwpb-gw-panel input[type=text], .mpwpb-gw-panel input[type=password], .mpwpb-gw-panel select, .mpwpb-gw-panel textarea { width: 100%; max-width: 420px; margin-top: 4px; font-weight: normal; }
					.mpwpb-toggle-switch { position: relative; display: inline-block; width: 40px; height: 22px; vertical-align: middle; }
					.mpwpb-toggle-switch input { opacity: 0; width: 0; height: 0; }
					.mpwpb-toggle-slider { position: absolute; inset: 0; background: rgba(255,255,255,.35); border-radius: 999px; transition: .15s; cursor: pointer; }
					.mpwpb-toggle-slider:before { content: ""; position: absolute; height: 16px; width: 16px; left: 3px; top: 3px; background: #fff; border-radius: 50%; transition: .15s; }
					.mpwpb-toggle-switch input:checked + .mpwpb-toggle-slider { background: #1c9a5b; }
					.mpwpb-toggle-switch input:checked + .mpwpb-toggle-slider:before { transform: translateX(18px); }
					.mpwpb-confirmation-row { display: flex; align-items: center; justify-content: space-between; gap: 20px; margin-top: 18px; padding-top: 18px; border-top: 1px solid #dcdcde; }
					.mpwpb-confirmation-row .description { color: #646970; margin: 4px 0 0; }
					.mpwpb-toggle-row { display: flex; align-items: center; gap: 14px; padding-bottom: 16px; margin-bottom: 16px; border-bottom: 1px solid #dcdcde; }
					.mpwpb-toggle-row .description { color: #646970; }
					.mpwpb-toggle-switch-lg .mpwpb-toggle-slider { background: rgba(0,0,0,.15); }
					.mpwpb-toggle-switch-lg input:checked + .mpwpb-toggle-slider { background: #2451e0; }
					.mpwpb-accordion { border: 1px solid #dcdcde; border-radius: 6px; margin-bottom: 14px; overflow: hidden; }
					.mpwpb-accordion-header { display: flex; align-items: center; justify-content: space-between; width: 100%; text-align: left; background: #f6f7f7; border: none; padding: 12px 16px; font-weight: 600; cursor: pointer; }
					.mpwpb-accordion-header.is-open { background: #eaf1ff; color: #2451e0; }
					.mpwpb-accordion-body { padding: 16px; border-top: 1px solid #dcdcde; }
					.mpwpb-pm-btn-outline { display: inline-block; border: 1px solid #2451e0; color: #2451e0; background: #fff; border-radius: 5px; padding: 6px 14px; text-decoration: none; font-size: 13px; font-weight: 600; }
					.mpwpb-wc-gateway-card { border: 1px solid #dcdcde; border-radius: 6px; padding: 14px 16px; margin-bottom: 10px; display: grid; grid-template-columns: auto auto 1fr auto; align-items: center; gap: 14px; }
					.mpwpb-wc-gateway-name { font-weight: 600; }
					.mpwpb-wc-gateway-status { border-radius: 999px; padding: 2px 10px; font-size: 11px; font-weight: 700; background: #f0f0f1; color: #646970; justify-self: end; }
					.mpwpb-wc-gateway-status.is-enabled { background: #eaf7ee; color: #1c9a5b; }
					.mpwpb-wc-gateway-desc { grid-column: 1 / -1; color: #646970; font-size: 13px; }
					.mpwpb-settings-row { display: flex; align-items: center; justify-content: space-between; gap: 20px; padding: 14px 0; border-top: 1px solid #eee; }
					.mpwpb-settings-row:first-child { border-top: none; padding-top: 0; }
					.mpwpb-settings-row .description { color: #646970; margin: 4px 0 0; }
				</style>
				<div class="mpwpb-pm-toggle">
					<button type="button" class="mpwpb-pm-toggle-btn <?php echo $payment_type !== 'custom' ? 'is-active' : ''; ?>" data-value="woocommerce"><?php esc_html_e('WooCommerce', 'service-booking-manager'); ?></button>
					<button type="button" class="mpwpb-pm-toggle-btn <?php echo $payment_type === 'custom' ? 'is-active' : ''; ?>" data-value="custom"><?php esc_html_e('Custom Payment', 'service-booking-manager'); ?></button>
				</div>
				<input type="hidden" name="<?php echo esc_attr($option); ?>[payment_method_type]" id="mpwpb_payment_method_type_input" value="<?php echo esc_attr($payment_type ?: 'custom'); ?>"/>

				<div class="mpwpb-pm-panel" data-panel="woocommerce" style="<?php echo $payment_type === 'custom' ? 'display:none;' : ''; ?>">
					<?php if (!$wc_active) : ?>
						<div class="mpwpb-pm-notice mpwpb-pm-notice-warning">
							<strong><span class="fas fa-triangle-exclamation"></span> <?php esc_html_e('Notice: WooCommerce is Not Activated', 'service-booking-manager'); ?></strong>
							<p><?php esc_html_e('To use WooCommerce as your payment method, you must install and activate WooCommerce.', 'service-booking-manager'); ?></p>
							<button type="button" id="mpwpb_install_wc_btn" class="mpwpb-pm-btn-primary" data-nonce="<?php echo esc_attr(wp_create_nonce('mpwpb_install_wc_nonce')); ?>">
								<?php echo $wc_status == 2 ? esc_html__('Activate Now', 'service-booking-manager') : esc_html__('Install & Activate Now', 'service-booking-manager'); ?>
							</button>
							<span id="mpwpb_install_wc_status" style="margin-left:10px;"></span>
						</div>
					<?php else :
						$confirm_statuses = MPWPB_Global_Function::get_payment_setting('wc_confirm_statuses', ['pending', 'processing', 'on-hold', 'completed']);
						$confirm_statuses = is_array($confirm_statuses) ? $confirm_statuses : ['pending', 'processing', 'on-hold', 'completed'];
						?>
						<div class="mpwpb-toggle-row">
							<div>
								<strong><?php esc_html_e('Enable WooCommerce Payment', 'service-booking-manager'); ?></strong>
							</div>
							<label class="mpwpb-toggle-switch mpwpb-toggle-switch-lg">
								<input type="checkbox" id="mpwpb_wc_enable_toggle" <?php checked($payment_type !== 'custom'); ?>/>
								<span class="mpwpb-toggle-slider"></span>
							</label>
							<span class="description"><?php esc_html_e('If enabled, WooCommerce payment gateway will be used for checkout.', 'service-booking-manager'); ?></span>
						</div>

						<div class="mpwpb-accordion">
							<button type="button" class="mpwpb-accordion-header" data-target="mpwpb-acc-wc-gateways">
								<?php esc_html_e('WooCommerce Payment Methods', 'service-booking-manager'); ?>
								<span class="fas fa-chevron-down"></span>
							</button>
							<div class="mpwpb-accordion-body" id="mpwpb-acc-wc-gateways" style="display:none;">
								<p>
									<a class="mpwpb-pm-btn-outline" href="<?php echo esc_url(admin_url('admin.php?page=wc-settings&tab=checkout')); ?>" target="_blank" rel="noopener">
										<?php esc_html_e('Open in WooCommerce', 'service-booking-manager'); ?> <span class="fas fa-arrow-up-right-from-square"></span>
									</a>
								</p>
								<?php foreach (WC()->payment_gateways()->payment_gateways() as $wc_gateway) : ?>
									<div class="mpwpb-wc-gateway-card">
										<label class="mpwpb-toggle-switch">
											<input type="checkbox" class="mpwpb-wc-gateway-toggle" data-gateway="<?php echo esc_attr($wc_gateway->id); ?>" data-nonce="<?php echo esc_attr(wp_create_nonce('mpwpb_toggle_wc_gateway_' . $wc_gateway->id)); ?>" <?php checked($wc_gateway->enabled === 'yes'); ?>/>
											<span class="mpwpb-toggle-slider"></span>
										</label>
										<span class="mpwpb-wc-gateway-name"><?php echo esc_html($wc_gateway->get_method_title()); ?></span>
										<span class="mpwpb-wc-gateway-status <?php echo $wc_gateway->enabled === 'yes' ? 'is-enabled' : ''; ?>" data-status-for="<?php echo esc_attr($wc_gateway->id); ?>">
											<?php echo $wc_gateway->enabled === 'yes' ? esc_html__('ENABLED', 'service-booking-manager') : esc_html__('DISABLED', 'service-booking-manager'); ?>
										</span>
										<a class="mpwpb-pm-btn-outline" href="<?php echo esc_url(admin_url('admin.php?page=wc-settings&tab=checkout&section=' . $wc_gateway->id)); ?>" target="_blank" rel="noopener"><?php esc_html_e('Configure', 'service-booking-manager'); ?></a>
										<div class="mpwpb-wc-gateway-desc"><?php echo wp_kses_post($wc_gateway->get_method_description()); ?></div>
									</div>
								<?php endforeach; ?>
							</div>
						</div>

						<div class="mpwpb-accordion">
							<button type="button" class="mpwpb-accordion-header" data-target="mpwpb-acc-wc-additional">
								<?php esc_html_e('Additional Settings', 'service-booking-manager'); ?>
								<span class="fas fa-chevron-down"></span>
							</button>
							<div class="mpwpb-accordion-body" id="mpwpb-acc-wc-additional" style="display:none;">
								<div class="mpwpb-settings-row">
									<div>
										<strong><?php esc_html_e('After Adding to Cart, Redirect to', 'service-booking-manager'); ?></strong>
										<p class="description"><?php esc_html_e('Select where to redirect after adding a booking to cart.', 'service-booking-manager'); ?></p>
									</div>
									<select name="<?php echo esc_attr($option); ?>[wc_add_to_cart_redirect]">
										<option value="checkout" <?php selected(MPWPB_Global_Function::get_payment_setting('wc_add_to_cart_redirect', 'checkout'), 'checkout'); ?>><?php esc_html_e('Checkout', 'service-booking-manager'); ?></option>
										<option value="cart" <?php selected(MPWPB_Global_Function::get_payment_setting('wc_add_to_cart_redirect'), 'cart'); ?>><?php esc_html_e('Cart', 'service-booking-manager'); ?></option>
									</select>
								</div>
								<div class="mpwpb-settings-row">
									<div>
										<strong><?php esc_html_e('After Confirming the Order, Redirect To', 'service-booking-manager'); ?></strong>
										<p class="description"><?php esc_html_e('Select where to redirect after the order is confirmed.', 'service-booking-manager'); ?></p>
									</div>
									<select name="<?php echo esc_attr($option); ?>[wc_order_confirm_redirect]">
										<option value="default" <?php selected(MPWPB_Global_Function::get_payment_setting('wc_order_confirm_redirect', 'default'), 'default'); ?>><?php esc_html_e('WooCommerce Default', 'service-booking-manager'); ?></option>
										<option value="plugin_thank_you" <?php selected(MPWPB_Global_Function::get_payment_setting('wc_order_confirm_redirect'), 'plugin_thank_you'); ?>><?php esc_html_e('Plugin Thank You Page', 'service-booking-manager'); ?></option>
									</select>
								</div>
								<div class="mpwpb-settings-row">
									<div>
										<strong><?php esc_html_e('Require Account Login', 'service-booking-manager'); ?></strong>
									</div>
									<label>
										<input type="hidden" name="<?php echo esc_attr($option); ?>[wc_require_login]" value="off"/>
										<input type="checkbox" name="<?php echo esc_attr($option); ?>[wc_require_login]" value="on" <?php checked(MPWPB_Global_Function::get_payment_setting('wc_require_login'), 'on'); ?>/>
										<?php esc_html_e('Require login to book a service.', 'service-booking-manager'); ?>
									</label>
								</div>
								<div class="mpwpb-settings-row">
									<div>
										<strong><?php esc_html_e('Show Billing Info', 'service-booking-manager'); ?></strong>
									</div>
									<label>
										<input type="hidden" name="<?php echo esc_attr($option); ?>[wc_show_billing_info]" value="off"/>
										<input type="checkbox" name="<?php echo esc_attr($option); ?>[wc_show_billing_info]" value="on" <?php checked(MPWPB_Global_Function::get_payment_setting('wc_show_billing_info', 'on'), 'on'); ?>/>
										<?php esc_html_e('Show billing info on the WooCommerce checkout page.', 'service-booking-manager'); ?>
									</label>
								</div>
								<div class="mpwpb-settings-row">
									<div>
										<strong><?php esc_html_e('Confirm Booking Based on Payment Status', 'service-booking-manager'); ?></strong>
										<p class="description"><?php esc_html_e('Select the order statuses that will trigger booking confirmation.', 'service-booking-manager'); ?></p>
									</div>
									<div>
										<?php foreach (['pending' => __('Pending payment', 'service-booking-manager'), 'processing' => __('Processing', 'service-booking-manager'), 'on-hold' => __('On hold', 'service-booking-manager'), 'completed' => __('Completed', 'service-booking-manager')] as $status_key => $status_label) : ?>
											<label style="display:block;">
												<input type="checkbox" name="<?php echo esc_attr($option); ?>[wc_confirm_statuses][]" value="<?php echo esc_attr($status_key); ?>" <?php checked(in_array($status_key, $confirm_statuses, true)); ?>/>
												<?php echo esc_html($status_label); ?>
											</label>
										<?php endforeach; ?>
									</div>
								</div>
							</div>
						</div>
					<?php endif; ?>
				</div>

				<div class="mpwpb-pm-panel" data-panel="custom" style="<?php echo $payment_type === 'custom' ? '' : 'display:none;'; ?>">
					<?php foreach ($gateways as $key => $gw) :
						$enabled = MPWPB_Global_Function::get_payment_setting($key . '_enabled') === 'on';
						?>
						<div class="mpwpb-gateway-card" style="background:<?php echo esc_attr($gw['gradient']); ?>">
							<div class="mpwpb-gateway-row">
								<span class="mpwpb-gateway-icon"><i class="<?php echo esc_attr($gw['icon']); ?>"></i></span>
								<span class="mpwpb-gateway-name"><?php echo esc_html($gw['label']); ?></span>
								<span class="mpwpb-gateway-status <?php echo $enabled ? 'is-enabled' : ''; ?>" data-status-for="<?php echo esc_attr($key); ?>">
									<?php echo $enabled ? esc_html__('Enabled', 'service-booking-manager') : esc_html__('Disabled', 'service-booking-manager'); ?>
								</span>
								<button type="button" class="mpwpb-gateway-configure" data-target="mpwpb-gw-panel-<?php echo esc_attr($key); ?>"><?php esc_html_e('Configure', 'service-booking-manager'); ?></button>
							</div>
						</div>
						<div class="mpwpb-gw-panel" id="mpwpb-gw-panel-<?php echo esc_attr($key); ?>" style="display:none;">
							<label>
								<input type="hidden" name="<?php echo esc_attr($option); ?>[<?php echo esc_attr($key); ?>_enabled]" value="off"/>
								<span class="mpwpb-toggle-switch">
									<input type="checkbox" class="mpwpb-gw-enable-toggle" data-status-for="<?php echo esc_attr($key); ?>" name="<?php echo esc_attr($option); ?>[<?php echo esc_attr($key); ?>_enabled]" value="on" <?php checked($enabled); ?>/>
									<span class="mpwpb-toggle-slider"></span>
								</span>
								&nbsp;<?php esc_html_e('Enable this payment method', 'service-booking-manager'); ?>
							</label>
							<?php if ($key === 'offline') : ?>
								<label>
									<?php esc_html_e('Instructions shown to the customer at checkout', 'service-booking-manager'); ?>
									<textarea rows="3" name="<?php echo esc_attr($option); ?>[offline_instructions]"><?php echo esc_textarea(MPWPB_Global_Function::get_payment_setting('offline_instructions', esc_html__('Please pay via bank transfer. We will confirm your booking once payment is received.', 'service-booking-manager'))); ?></textarea>
								</label>
							<?php elseif ($key === 'stripe') : ?>
								<label>
									<?php esc_html_e('Mode', 'service-booking-manager'); ?>
									<select name="<?php echo esc_attr($option); ?>[stripe_mode]">
										<option value="test" <?php selected(MPWPB_Global_Function::get_payment_setting('stripe_mode', 'test'), 'test'); ?>><?php esc_html_e('Test', 'service-booking-manager'); ?></option>
										<option value="live" <?php selected(MPWPB_Global_Function::get_payment_setting('stripe_mode'), 'live'); ?>><?php esc_html_e('Live', 'service-booking-manager'); ?></option>
									</select>
								</label>
								<label>
									<?php esc_html_e('Publishable Key', 'service-booking-manager'); ?>
									<input type="text" name="<?php echo esc_attr($option); ?>[stripe_publishable_key]" value="<?php echo esc_attr(MPWPB_Global_Function::get_payment_setting('stripe_publishable_key')); ?>"/>
								</label>
								<label>
									<?php esc_html_e('Secret Key', 'service-booking-manager'); ?>
									<input type="password" name="<?php echo esc_attr($option); ?>[stripe_secret_key]" value="<?php echo esc_attr(MPWPB_Global_Function::get_payment_setting('stripe_secret_key')); ?>"/>
								</label>
								<label>
									<?php esc_html_e('Webhook Secret', 'service-booking-manager'); ?>
									<input type="password" name="<?php echo esc_attr($option); ?>[stripe_webhook_secret]" value="<?php echo esc_attr(MPWPB_Global_Function::get_payment_setting('stripe_webhook_secret')); ?>"/>
								</label>
								<p class="description"><?php esc_html_e('Note: real Stripe charge processing is not wired up yet — enabling Stripe currently confirms the booking immediately, the same as Offline.', 'service-booking-manager'); ?></p>
							<?php elseif ($key === 'paypal') : ?>
								<label>
									<?php esc_html_e('Mode', 'service-booking-manager'); ?>
									<select name="<?php echo esc_attr($option); ?>[paypal_mode]">
										<option value="sandbox" <?php selected(MPWPB_Global_Function::get_payment_setting('paypal_mode', 'sandbox'), 'sandbox'); ?>><?php esc_html_e('Sandbox', 'service-booking-manager'); ?></option>
										<option value="live" <?php selected(MPWPB_Global_Function::get_payment_setting('paypal_mode'), 'live'); ?>><?php esc_html_e('Live', 'service-booking-manager'); ?></option>
									</select>
								</label>
								<label>
									<?php esc_html_e('Client ID', 'service-booking-manager'); ?>
									<input type="text" name="<?php echo esc_attr($option); ?>[paypal_client_id]" value="<?php echo esc_attr(MPWPB_Global_Function::get_payment_setting('paypal_client_id')); ?>"/>
								</label>
								<label>
									<?php esc_html_e('Client Secret', 'service-booking-manager'); ?>
									<input type="password" name="<?php echo esc_attr($option); ?>[paypal_client_secret]" value="<?php echo esc_attr(MPWPB_Global_Function::get_payment_setting('paypal_client_secret')); ?>"/>
								</label>
								<p class="description"><?php esc_html_e('Note: real PayPal charge processing is not wired up yet — enabling PayPal currently confirms the booking immediately, the same as Offline.', 'service-booking-manager'); ?></p>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>

					<div class="mpwpb-confirmation-row">
						<div>
							<strong><?php esc_html_e('Booking Confirmation Page', 'service-booking-manager'); ?></strong>
							<p class="description"><?php esc_html_e('Select a page with the [mpwpb_booking_confirmation] shortcode. After booking, customers are redirected here instead of the default confirmation page.', 'service-booking-manager'); ?></p>
						</div>
						<?php
						wp_dropdown_pages([
							'name' => $option . '[confirmation_page_id]',
							'id' => 'mpwpb_confirmation_page_id',
							'selected' => (int) MPWPB_Global_Function::get_payment_setting('confirmation_page_id'),
							'show_option_none' => esc_html__('— Default —', 'service-booking-manager'),
							'option_none_value' => '0',
						]);
						?>
					</div>
				</div>
				<div class="justifyBetween _mT">
					<div></div>
					<?php submit_button(); ?>
				</div>
				<script>
					(function ($) {
						"use strict";
						$(document).ready(function () {
							function mpwpbSetPaymentMode(value) {
								$('.mpwpb-pm-toggle-btn').removeClass('is-active');
								$('.mpwpb-pm-toggle-btn[data-value="' + value + '"]').addClass('is-active');
								$('#mpwpb_payment_method_type_input').val(value);
								$('#mpwpb_wc_enable_toggle').prop('checked', value === 'woocommerce');
								$('.mpwpb-pm-panel[data-panel="woocommerce"]').toggle(value === 'woocommerce');
								$('.mpwpb-pm-panel[data-panel="custom"]').toggle(value === 'custom');
							}
							$('.mpwpb-pm-toggle-btn').on('click', function () {
								mpwpbSetPaymentMode($(this).data('value'));
							});
							$('#mpwpb_wc_enable_toggle').on('change', function () {
								mpwpbSetPaymentMode(this.checked ? 'woocommerce' : 'custom');
							});
							$('.mpwpb-accordion-header').on('click', function () {
								var $header = $(this);
								var $body = $('#' + $header.data('target'));
								$body.slideToggle(150);
								$header.toggleClass('is-open');
							});
							$('.mpwpb-wc-gateway-toggle').on('change', function () {
								var $toggle = $(this);
								var gateway = $toggle.data('gateway');
								var $status = $('.mpwpb-wc-gateway-status[data-status-for="' + gateway + '"]');
								var enabled = this.checked;
								$.post(ajaxurl, {
									action: 'mpwpb_toggle_wc_gateway',
									gateway: gateway,
									enabled: enabled ? '1' : '0',
									nonce: $toggle.data('nonce')
								}).done(function (resp) {
									if (resp && resp.success) {
										$status.toggleClass('is-enabled', enabled).text(enabled ? <?php echo wp_json_encode(esc_html__('ENABLED', 'service-booking-manager')); ?> : <?php echo wp_json_encode(esc_html__('DISABLED', 'service-booking-manager')); ?>);
									} else {
										$toggle.prop('checked', !enabled);
									}
								}).fail(function () {
									$toggle.prop('checked', !enabled);
								});
							});
							$('.mpwpb-gateway-configure').on('click', function () {
								$('#' + $(this).data('target')).slideToggle(150);
							});
							$('.mpwpb-gw-enable-toggle').on('change', function () {
								var key = $(this).data('status-for');
								var $status = $('.mpwpb-gateway-status[data-status-for="' + key + '"]');
								if (this.checked) {
									$status.addClass('is-enabled').text(<?php echo wp_json_encode(esc_html__('Enabled', 'service-booking-manager')); ?>);
								} else {
									$status.removeClass('is-enabled').text(<?php echo wp_json_encode(esc_html__('Disabled', 'service-booking-manager')); ?>);
								}
							});
							$('#mpwpb_install_wc_btn').on('click', function () {
								var $btn = $(this);
								var $status = $('#mpwpb_install_wc_status');
								$btn.prop('disabled', true);
								var originalText = $btn.text();
								$btn.text(<?php echo wp_json_encode(esc_html__('Please wait…', 'service-booking-manager')); ?>);
								$.post(ajaxurl, {
									action: 'mpwpb_install_activate_woocommerce',
									nonce: $btn.data('nonce')
								}).done(function (resp) {
									if (resp && resp.success) {
										window.location.reload();
									} else {
										$status.css('color', '#b32d2e').text((resp && resp.data && resp.data.message) ? resp.data.message : <?php echo wp_json_encode(esc_html__('Something went wrong.', 'service-booking-manager')); ?>);
										$btn.prop('disabled', false).text(originalText);
									}
								}).fail(function () {
									$status.css('color', '#b32d2e').text(<?php echo wp_json_encode(esc_html__('Request failed.', 'service-booking-manager')); ?>);
									$btn.prop('disabled', false).text(originalText);
								});
							});
						});
					}(jQuery));
				</script>
				<?php
			}
			public function install_activate_woocommerce(): void {
				if (!current_user_can('install_plugins') || !current_user_can('activate_plugins')) {
					wp_send_json_error(['message' => esc_html__('You do not have permission to do this.', 'service-booking-manager')]);
				}
				if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'mpwpb_install_wc_nonce')) {
					wp_send_json_error(['message' => esc_html__('Security check failed.', 'service-booking-manager')]);
				}
				$status = MPWPB_Global_Function::check_woocommerce();
				if ($status == 1) {
					wp_send_json_success(['message' => esc_html__('WooCommerce is already active.', 'service-booking-manager')]);
				}
				if ($status == 2) {
					$result = activate_plugin('woocommerce/woocommerce.php');
					if (is_wp_error($result)) {
						wp_send_json_error(['message' => $result->get_error_message()]);
					}
					wp_send_json_success(['message' => esc_html__('WooCommerce activated.', 'service-booking-manager')]);
				}
				include_once ABSPATH . 'wp-admin/includes/plugin-install.php';
				include_once ABSPATH . 'wp-admin/includes/file.php';
				include_once ABSPATH . 'wp-admin/includes/misc.php';
				include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
				$api = plugins_api('plugin_information', [
					'slug' => 'woocommerce',
					'fields' => ['sections' => false],
				]);
				if (is_wp_error($api)) {
					wp_send_json_error(['message' => $api->get_error_message()]);
				}
				$upgrader = new Plugin_Upgrader(new Automatic_Upgrader_Skin());
				$installed = $upgrader->install($api->download_link);
				if (is_wp_error($installed) || !$installed) {
					wp_send_json_error(['message' => esc_html__('Could not install WooCommerce.', 'service-booking-manager')]);
				}
				$result = activate_plugin('woocommerce/woocommerce.php');
				if (is_wp_error($result)) {
					wp_send_json_error(['message' => $result->get_error_message()]);
				}
				wp_send_json_success(['message' => esc_html__('WooCommerce installed and activated.', 'service-booking-manager')]);
			}
			public function maybe_show_payment_notice(): void {
				if (!current_user_can('manage_options') || MPWPB_Global_Function::has_functional_payment_method()) {
					return;
				}
				$cpt = MPWPB_Function::get_cpt();
				$url = admin_url('edit.php?post_type=' . $cpt . '&page=mpwpb_settings_page');
				?>
				<div class="notice notice-warning">
					<p>
						<?php
						echo wp_kses_post(
							sprintf(
								/* translators: %s: settings page URL */
								__('<strong>Service Booking Manager:</strong> no payment method is configured yet. <a href="%s">Configure a payment method</a> (WooCommerce, or Stripe / PayPal / Offline) so customers can complete bookings.', 'service-booking-manager'),
								esc_url($url)
							)
						);
						?>
					</p>
				</div>
				<?php
			}
		}
		new MPWPB_Native_Checkout_Settings();
	}
