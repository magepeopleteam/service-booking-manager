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
				add_action('wp_ajax_mpwpb_save_wc_gateway_settings', [$this, 'save_wc_gateway_settings']);
				add_action('wp_ajax_mpwpb_save_payment_method_settings', [$this, 'save_payment_method_settings']);
			}
			/**
			 * Saves the mpwpb_payment_method_settings option when the
			 * Payment Method panel is submitted from outside the real
			 * Settings page (e.g. the modal on the service Add/Edit
			 * screen) -- the panel's fields aren't wrapped in a real
			 * <form action="options.php"> there, so this mirrors what
			 * MPWPB_Setting_API::sanitize_options() does for this same
			 * option on the real Settings page.
			 */
			public function save_payment_method_settings(): void {
				if (!current_user_can('manage_options')) {
					wp_send_json_error(['message' => esc_html__('You do not have permission to do this.', 'service-booking-manager')]);
				}
				if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'mpwpb_save_payment_method_settings')) {
					wp_send_json_error(['message' => esc_html__('Security check failed.', 'service-booking-manager')]);
				}
				$posted = isset($_POST['mpwpb_payment_method_settings']) && is_array($_POST['mpwpb_payment_method_settings'])
					? wp_unslash($_POST['mpwpb_payment_method_settings'])
					: [];
				$sanitized = [];
				foreach ($posted as $key => $value) {
					$sanitized[sanitize_key($key)] = is_array($value) ? array_map('sanitize_text_field', $value) : sanitize_text_field($value);
				}
				update_option('mpwpb_payment_method_settings', $sanitized);
				wp_send_json_success(['message' => esc_html__('Payment settings saved.', 'service-booking-manager')]);
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
			/**
			 * Saves a WooCommerce gateway's own settings fields from our inline
			 * panel. Delegates to the gateway's own process_admin_options(),
			 * which reads $_POST using the same field keys as WooCommerce's
			 * native settings screen and writes to the same option — so this
			 * is not a separate copy of the settings, it IS the WooCommerce
			 * gateway settings.
			 */
			public function save_wc_gateway_settings(): void {
				if (!current_user_can('manage_woocommerce') && !current_user_can('manage_options')) {
					wp_send_json_error(['message' => esc_html__('You do not have permission to do this.', 'service-booking-manager')]);
				}
				$gateway_id = isset($_POST['gateway']) ? sanitize_key(wp_unslash($_POST['gateway'])) : '';
				if (!$gateway_id || !isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'mpwpb_save_wc_gateway_' . $gateway_id)) {
					wp_send_json_error(['message' => esc_html__('Security check failed.', 'service-booking-manager')]);
				}
				if (!MPWPB_Global_Function::check_woocommerce()) {
					wp_send_json_error(['message' => esc_html__('WooCommerce is not active.', 'service-booking-manager')]);
				}
				$gateways = WC()->payment_gateways()->payment_gateways();
				if (!isset($gateways[$gateway_id])) {
					wp_send_json_error(['message' => esc_html__('Unknown payment gateway.', 'service-booking-manager')]);
				}
				$gateway = $gateways[$gateway_id];
				$gateway->process_admin_options();
				$errors = $gateway->get_errors();
				if (!empty($errors)) {
					wp_send_json_error(['message' => implode(' ', array_map('wp_strip_all_tags', $errors))]);
				}
				wp_send_json_success(['message' => esc_html__('Settings saved.', 'service-booking-manager')]);
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
						'id' => 'mpwpb_partial_payment_settings',
						'icon' => 'mi mi-percentage',
						'title' => esc_html__('Partial Payment', 'service-booking-manager'),
						'callback' => [$this, 'render_partial_payment_panel'],
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
						'name' => 'currency_code',
						'label' => esc_html__('Currency Code', 'service-booking-manager'),
						'desc' => esc_html__('ISO currency code charged through Stripe/PayPal. This is separate from the display symbol below — Stripe/PayPal require a real currency code (a "$" alone is ambiguous between USD/CAD/AUD/etc.).', 'service-booking-manager'),
						'type' => 'select',
						'default' => 'USD',
						'options' => [
							'USD' => 'USD - US Dollar',
							'EUR' => 'EUR - Euro',
							'GBP' => 'GBP - British Pound',
							'CAD' => 'CAD - Canadian Dollar',
							'AUD' => 'AUD - Australian Dollar',
							'NZD' => 'NZD - New Zealand Dollar',
							'JPY' => 'JPY - Japanese Yen',
							'CHF' => 'CHF - Swiss Franc',
							'SEK' => 'SEK - Swedish Krona',
							'NOK' => 'NOK - Norwegian Krone',
							'DKK' => 'DKK - Danish Krone',
							'PLN' => 'PLN - Polish Zloty',
							'CZK' => 'CZK - Czech Koruna',
							'HUF' => 'HUF - Hungarian Forint',
							'RON' => 'RON - Romanian Leu',
							'BGN' => 'BGN - Bulgarian Lev',
							'INR' => 'INR - Indian Rupee',
							'SGD' => 'SGD - Singapore Dollar',
							'HKD' => 'HKD - Hong Kong Dollar',
							'MYR' => 'MYR - Malaysian Ringgit',
							'PHP' => 'PHP - Philippine Peso',
							'THB' => 'THB - Thai Baht',
							'AED' => 'AED - UAE Dirham',
							'SAR' => 'SAR - Saudi Riyal',
							'ZAR' => 'ZAR - South African Rand',
							'MXN' => 'MXN - Mexican Peso',
							'BRL' => 'BRL - Brazilian Real',
						],
					],
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
			 * Settings keys owned by a Pro-only gateway, so a free site can
			 * round-trip them untouched instead of dropping them (the gateway's
			 * config panel isn't rendered there -- see render_payment_method_panel()).
			 */
			private static function pro_gateway_setting_keys(string $gateway): array {
				$map = [
					'stripe' => ['stripe_mode', 'stripe_publishable_key', 'stripe_secret_key', 'stripe_webhook_secret'],
					'paypal' => ['paypal_mode', 'paypal_client_id', 'paypal_client_secret'],
				];
				return $map[$gateway] ?? [];
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
				$pro_active = MPWPB_Global_Function::is_pro_active();
				$option = 'mpwpb_payment_method_settings';
				$upgrade_url = MPWPB_Global_Function::pro_upgrade_url();
				// 'pro' marks the online gateways: they stay locked without Pro.
				// Offline carries no flag -- it is the free standalone payment
				// method, so a free site can take real bookings in Custom mode.
				$gateways = [
					'paypal' => [
						'label' => esc_html__('PayPal', 'service-booking-manager'),
						'icon' => 'fab fa-paypal',
						'gradient' => 'linear-gradient(135deg,#003b7a,#0073c4)',
						'pro' => true,
					],
					'stripe' => [
						'label' => esc_html__('Credit/Debit Card (Stripe)', 'service-booking-manager'),
						'icon' => 'fab fa-stripe-s',
						'gradient' => 'linear-gradient(135deg,#4338ca,#6d5bf0)',
						'pro' => true,
					],
					'offline' => [
						'label' => esc_html__('Offline Payment', 'service-booking-manager'),
						'icon' => 'fas fa-money-check-dollar',
						'gradient' => 'linear-gradient(135deg,#0f5f52,#1f9c82)',
					],
				];
				// Plain-text copy and explicit from/to labels let the modal render a
				// predictable visual transition without inherited admin typography
				// splitting an inline sentence into an unreadable order.
				$wc_name = esc_html__('WooCommerce', 'service-booking-manager');
				$custom_name = esc_html__('Custom Payment', 'service-booking-manager');
				$pm_confirm_copy = [
					'woocommerce' => [
						'intro' => esc_html__('Only one checkout payment method can be active at a time.', 'service-booking-manager'),
						'from' => $custom_name,
						'to' => $wc_name,
						'detail' => sprintf(
							/* translators: 1: current payment method, 2: new payment method */
							esc_html__('%1$s will be turned off, and %2$s will become active.', 'service-booking-manager'),
							$custom_name,
							$wc_name
						),
					],
					'custom' => [
						'intro' => esc_html__('Only one checkout payment method can be active at a time.', 'service-booking-manager'),
						'from' => $wc_name,
						'to' => $custom_name,
						'detail' => sprintf(
							/* translators: 1: current payment method, 2: new payment method */
							esc_html__('%1$s will be turned off, and %2$s will become active.', 'service-booking-manager'),
							$wc_name,
							$custom_name
						),
					],
				];
				$pm_confirm_labels = [
					'change' => esc_html__('Payment method change', 'service-booking-manager'),
					'current' => esc_html__('Current method', 'service-booking-manager'),
					'next' => esc_html__('New method', 'service-booking-manager'),
				];
				?>
				<style>
					.mpwpb-pm-toggle { display: inline-flex; gap: 4px; margin: 0 0 20px; padding: 4px; overflow: hidden; border: 1px solid #dce3ee; border-radius: 12px; background: #eef2f7; box-shadow: inset 0 1px 2px rgba(15,23,42,.04); }
					.mpwpb-pm-toggle-btn { min-height: 38px; border: none; border-radius: 9px; background: transparent; padding: 9px 18px; font-size: 13px; font-weight: 700; cursor: pointer; color: #536177; transition: background .15s ease, color .15s ease, box-shadow .15s ease; }
					.mpwpb-pm-toggle-btn:hover { background: rgba(255,255,255,.72); color: #172033; }
					.mpwpb-pm-toggle-btn.is-active { background: linear-gradient(135deg,#4565e8,#6967ed); color: #fff; box-shadow: 0 6px 14px rgba(69,101,232,.22); }
					.mpwpb-pm-panel { border: 1px solid #dce3ee; border-radius: 16px; padding: 20px; background: #f8fafc; box-shadow: 0 8px 24px rgba(15,23,42,.05); }
					.mpwpb-custom-dependent.mpwpb-locked { opacity: .5; pointer-events: none; user-select: none; }
					.mpwpb-pro-badge { display: inline-block; background: linear-gradient(135deg,#f7b733,#fc4a1a); color: #fff; font-size: 10px; font-weight: 700; letter-spacing: .5px; border-radius: 999px; padding: 2px 8px; margin-left: 6px; vertical-align: middle; }
					.mpwpb-toggle-switch input:disabled + .mpwpb-toggle-slider { cursor: not-allowed; opacity: .5; }
					.mpwpb-pm-notice { border-radius: 6px; padding: 14px 16px; }
					.mpwpb-pm-notice-warning { background: #fff6dd; border: 1px solid #f0dfa6; }
					.mpwpb-pm-notice-success { background: #eaf7ee; border: 1px solid #b7e3c4; }
					.mpwpb-pm-notice p { margin: 6px 0 12px; }
					.mpwpb-pm-btn-primary { background: #6366f1; color: #fff; border: none; border-radius: 5px; padding: 9px 18px; font-weight: 600; cursor: pointer; }
					.mpwpb-pm-btn-primary:disabled { opacity: .6; cursor: default; }
					.mpwpb-gateway-card { position: relative; z-index: 1; overflow: hidden; border: 1px solid rgba(255,255,255,.18); border-radius: 14px; margin-bottom: 14px; color: #fff; background: #444; box-shadow: 0 8px 18px rgba(15,23,42,.11); transition: transform .15s ease, box-shadow .15s ease; }
					.mpwpb-gateway-card::after { content: ""; position: absolute; inset: 0; pointer-events: none; background: linear-gradient(110deg,rgba(255,255,255,.08),transparent 45%); }
					.mpwpb-gateway-card.is-config-open { border-radius: 14px 14px 0 0; margin-bottom: 0; }
					.mpwpb-gateway-row { position: relative; z-index: 1; display: flex; align-items: center; gap: 13px; min-height: 62px; padding: 12px 17px; box-sizing: border-box; }
					div.mpwpb_style .mpwpb-gateway-icon { display: inline-flex !important; align-items: center !important; justify-content: center !important; width: 36px; height: 36px; flex: 0 0 36px; margin: 0 !important; padding: 0 !important; border-radius: 10px; background: rgba(255,255,255,.15); font-size: 18px; line-height: 1; text-align: center; box-sizing: border-box; }
					div.mpwpb_style .mpwpb-gateway-icon > i { display: inline-flex !important; align-items: center !important; justify-content: center !important; width: 20px; height: 20px; flex: 0 0 20px; margin: 0 !important; padding: 0 !important; font-size: 18px; line-height: 20px !important; text-align: center; vertical-align: middle; }
					div.mpwpb_style .mpwpb-gateway-icon > i::before { display: block; width: 20px; line-height: 20px; text-align: center; }
					.mpwpb-gateway-name { font-size: 13px; font-weight: 750; flex: 1; }
					.mpwpb-gateway-status { border: 1px solid rgba(255,255,255,.16); border-radius: 999px; padding: 4px 10px; font-size: 10px; font-weight: 750; background: rgba(255,255,255,.2); }
					.mpwpb-gateway-status.is-enabled { background: #1c9a5b; }
					.mpwpb-gateway-configure { display: inline-flex; align-items: center; gap: 7px; min-height: 34px; border: 1px solid rgba(255,255,255,.42); background: rgba(255,255,255,.14); color: #fff; border-radius: 9px; padding: 7px 12px; font-size: 11.5px; font-weight: 700; cursor: pointer; transition: background .15s ease, transform .15s ease; }
					.mpwpb-gateway-configure::after { content: "\f347"; font-family: dashicons; font-size: 14px; transition: transform .15s ease; }
					.mpwpb-gateway-configure[aria-expanded="true"]::after { transform: rotate(180deg); }
					.mpwpb-gateway-configure:hover { background: rgba(255,255,255,.24); transform: translateY(-1px); }
					.mpwpb-gateway-card--locked { filter: saturate(.55); }
					.mpwpb-gateway-card--locked .mpwpb-gateway-name { display: inline-flex; align-items: center; }
					a.mpwpb-gateway-configure.mpwpb-gateway-upgrade { text-decoration: none; color: #fff; }
					a.mpwpb-gateway-configure.mpwpb-gateway-upgrade::after { content: none; }
					a.mpwpb-gateway-configure.mpwpb-gateway-upgrade .dashicons { width: 14px; height: 14px; font-size: 14px; line-height: 14px; }
					.mpwpb-gw-panel { position: relative; border: 1px solid #dce3ee; border-top: none; border-radius: 0 0 14px 14px; padding: 20px; margin: 0 0 16px; background: #fff; box-shadow: 0 12px 24px rgba(15,23,42,.08); }
					.mpwpb-gw-panel .mpwpb-gw-enable-row { display: flex !important; align-items: center !important; justify-content: space-between !important; gap: 14px !important; margin: 0 0 6px !important; padding: 0 0 16px !important; border-bottom: 1px solid #e8edf4; color: #26334a; font-size: 12.5px; font-weight: 750 !important; }
					.mpwpb-gw-panel .mpwpb-gw-enable-label { flex: 1; min-width: 0; }
					.mpwpb-gw-panel label.mpwpb-gw-field { display: grid !important; grid-template-columns: 150px minmax(0,1fr); align-items: center !important; gap: 16px !important; margin: 0 !important; padding: 14px 0 !important; border-bottom: 1px solid #edf1f6; }
					.mpwpb-gw-panel label.mpwpb-gw-field:last-of-type { border-bottom: none; }
					.mpwpb-gw-panel .mpwpb-gw-field-label { min-width: 0; color: #354258; font-size: 12px; font-weight: 700; line-height: 1.4; }
					.mpwpb-gw-panel .mpwpb-gw-field-control { display: block !important; min-width: 0; }
					.mpwpb-gw-panel input[type=text], .mpwpb-gw-panel input[type=password], .mpwpb-gw-panel select, .mpwpb-gw-panel textarea { width: 100% !important; max-width: none !important; min-height: 42px !important; margin: 0 !important; padding: 9px 12px !important; border: 1px solid #d7dfeb !important; border-radius: 10px !important; background: #f9fbfd !important; color: #172033 !important; font-size: 12.5px !important; font-weight: 500 !important; box-shadow: none !important; box-sizing: border-box !important; transition: border-color .15s ease, box-shadow .15s ease, background .15s ease; }
					.mpwpb-gw-panel textarea { min-height: 92px !important; resize: vertical; }
					.mpwpb-gw-panel input:focus, .mpwpb-gw-panel select:focus, .mpwpb-gw-panel textarea:focus { outline: none !important; border-color: #6276e8 !important; background: #fff !important; box-shadow: 0 0 0 3px rgba(83,104,221,.13) !important; }
					.mpwpb-gw-panel .mpwpb-gw-help { position: relative; margin: 16px 0 0 !important; padding: 13px 14px 13px 42px !important; border: 1px solid #dbe5ff; border-radius: 11px; background: #f6f8ff; color: #59677c !important; font-size: 11.5px !important; line-height: 1.55 !important; }
					.mpwpb-gw-panel .mpwpb-gw-help::before { content: "\f348"; position: absolute; top: 14px; left: 14px; display: grid; place-items: center; width: 18px; height: 18px; color: #5368dd; font-family: dashicons; font-size: 18px; }
					.mpwpb-gw-panel .mpwpb-gw-help code { display: inline-block; max-width: 100%; padding: 1px 4px; border-radius: 4px; background: #e9edff; color: #3449bd; font-size: 10.5px; overflow-wrap: anywhere; }
					.mpwpb-toggle-switch { position: relative; display: inline-block; width: 40px; height: 22px; vertical-align: middle; }
					.mpwpb-toggle-switch input { opacity: 0; width: 0; height: 0; }
					.mpwpb-toggle-slider { position: absolute; inset: 0; background: rgba(255,255,255,.35); border-radius: 999px; transition: .15s; cursor: pointer; }
					.mpwpb-toggle-slider:before { content: ""; position: absolute; height: 16px; width: 16px; left: 3px; top: 3px; background: #fff; border-radius: 50%; transition: .15s; }
					.mpwpb-toggle-switch input:checked + .mpwpb-toggle-slider { background: #1c9a5b; }
					.mpwpb-toggle-switch input:checked + .mpwpb-toggle-slider:before { transform: translateX(18px); }
					.mpwpb-confirmation-row { display: grid; grid-template-columns: minmax(0,1fr) minmax(180px,240px); align-items: center; gap: 20px; margin-top: 18px; padding: 18px; border: 1px solid #dce3ee; border-radius: 13px; background: #fff; }
					.mpwpb-confirmation-row .description { color: #646970; margin: 4px 0 0; }
					.mpwpb-confirmation-row select { width: 100%; min-height: 42px; border: 1px solid #d7dfeb; border-radius: 10px; background-color: #f9fbfd; }
					.mpwpb-toggle-row { display: flex; align-items: center; justify-content: space-between; gap: 18px; padding: 16px 17px; margin-bottom: 18px; border: 1px solid #dce3ee; border-radius: 13px; background: #fff; }
					.mpwpb-toggle-row strong { display: inline-flex !important; align-items: center; color: #26334a; font-size: 13px; font-weight: 750; }
					.mpwpb-toggle-row .description { color: #646970; }
					.mpwpb-toggle-switch-lg .mpwpb-toggle-slider { background: rgba(0,0,0,.15); }
					.mpwpb-toggle-switch-lg input:checked + .mpwpb-toggle-slider { background: #6366f1; }
					.mpwpb-accordion { border: 1px solid #dce3ee; border-radius: 13px; margin-bottom: 14px; overflow: hidden; background: #fff; }
					.mpwpb-accordion-header { display: flex; align-items: center; justify-content: space-between !important; gap: 12px; width: 100%; text-align: left; background: #fff; border: none; padding: 14px 16px; color: #354258; font-size: 12.5px; font-weight: 750; cursor: pointer; transition: background .15s ease, color .15s ease; }
					.mpwpb-accordion-header:hover { background: #f8faff; }
					.mpwpb-accordion-header.is-open { background: #eef2ff; color: #4056ce; }
					.mpwpb-accordion-header .fa-chevron-down { margin-left: auto !important; transition: transform .15s ease; }
					.mpwpb-accordion-header.is-open .fa-chevron-down { transform: rotate(180deg); }
					.mpwpb-accordion mpwpb span.fas.fa-chevron-down { margin-left: 20px; }
					span.fas.fa-chevron-down { margin-left: 10px; }
					.mpwpb-accordion-body { padding: 16px; border-top: 1px solid #e7ecf3; background: #f8fafc; }
					.mpwpb-accordion-body p { margin-bottom: 5px !important; }
					.mpwpb-pm-btn-outline { display: inline-block; border: 1px solid #6366f1; color: #6366f1; background: #fff; border-radius: 5px; padding: 6px 14px; text-decoration: none; font-size: 13px; font-weight: 600; }
					.mpwpb-wc-gateway-card { border: 1px solid #dce3ee; border-radius: 12px; padding: 14px 15px; margin-bottom: 10px; display: grid; grid-template-columns: auto minmax(120px,auto) 1fr auto; align-items: center; gap: 13px; background: #fff; box-shadow: 0 4px 12px rgba(15,23,42,.04); }
					.mpwpb-wc-gateway-card.is-config-open { border-radius: 12px 12px 0 0; margin-bottom: 0; }
					.mpwpb-wc-gateway-name { color: #26334a; font-size: 12.5px; font-weight: 750; }
					.mpwpb-wc-gateway-status { border-radius: 999px; padding: 2px 10px; font-size: 11px; font-weight: 700; background: #fdecea; color: #b32d2e; justify-self: end; }
					.mpwpb-wc-gateway-status.is-enabled { background: #eaf7ee; color: #1c9a5b; }
					.mpwpb-wc-gateway-desc { grid-column: 1 / -1; padding-top: 10px; border-top: 1px solid #edf1f6; color: #69768a; font-size: 11.5px; line-height: 1.5; }
					.mpwpb-wc-gateway-card .mpwpb-gateway-configure { display: inline-flex; align-items: center; justify-content: center; gap: 7px; border-color: #cfd7ff; background: #eef2ff; color: #455bd4; border-radius: 9px; }
					.mpwpb-wc-gateway-card .mpwpb-gateway-configure:hover { border-color: #aebaff; background: #e4e9ff; color: #3449bd; }
					.mpwpb-wc-gw-panel { border: 1px solid #dce3ee; border-top: 3px solid #6276e8; border-radius: 0 0 12px 12px; padding: 18px; margin: 0 0 12px; background: #fff; box-shadow: 0 10px 22px rgba(15,23,42,.07); }
					.mpwpb-wc-gw-panel .form-table { width: 100%; margin: 0; border-collapse: separate; border-spacing: 0; }
					.mpwpb-wc-gw-panel .form-table tr + tr th, .mpwpb-wc-gw-panel .form-table tr + tr td { border-top: 1px solid #edf1f6; }
					.mpwpb-wc-gw-panel .form-table th { width: 190px; padding: 14px 14px 14px 0; color: #354258; font-size: 12px; font-weight: 700; vertical-align: middle; }
					.mpwpb-wc-gw-panel .form-table td { padding: 14px 0; vertical-align: middle; }
					.mpwpb-wc-gw-panel .form-table input[type=text], .mpwpb-wc-gw-panel .form-table input[type=password], .mpwpb-wc-gw-panel .form-table input[type=email], .mpwpb-wc-gw-panel .form-table input[type=number], .mpwpb-wc-gw-panel .form-table select, .mpwpb-wc-gw-panel .form-table textarea { width: 100% !important; max-width: none !important; min-height: 42px; margin: 0; padding: 9px 12px; border: 1px solid #d7dfeb; border-radius: 10px; background: #f9fbfd; box-shadow: none; box-sizing: border-box; }
					.mpwpb-wc-gw-panel .form-table input:focus, .mpwpb-wc-gw-panel .form-table select:focus, .mpwpb-wc-gw-panel .form-table textarea:focus { outline: none; border-color: #6276e8; background: #fff; box-shadow: 0 0 0 3px rgba(83,104,221,.13); }
					.mpwpb-wc-gw-panel .description { color: #7a879a; font-size: 10.5px; line-height: 1.5; }
					.mpwpb-wc-gw-save-row { display: flex; align-items: center; gap: 10px; margin: 16px 0 0 !important; padding-top: 16px; border-top: 1px solid #edf1f6; }
					.mpwpb-settings-row { display: flex; align-items: center; justify-content: space-between; gap: 20px; margin: 0; padding: 15px 0; border-top: 1px solid #e7ecf3; }
					.mpwpb-settings-row:first-child { border-top: none; padding-top: 0; }
					.mpwpb-settings-row > div:first-child strong { display: block !important; color: #354258; font-size: 12.5px; font-weight: 750; }
					.mpwpb-settings-row .description { color: #758196; margin: 4px 0 0; font-size: 11px; line-height: 1.5; }
					.mpwpb-settings-row select { min-width: 190px; min-height: 40px; border: 1px solid #d7dfeb; border-radius: 9px; background-color: #fff; }
					.mpwpb-settings-row.mpwpb-settings-row-top { align-items: flex-start; }
					.mpwpb-settings-row.mpwpb-settings-row-top > div:first-child { padding-top: 2px; }
					.mpwpb-settings-row label { display: flex; align-items: center; gap: 6px; font-weight: 400; }
					.mpwpb-pm-panel .mpwpb-pm-btn-primary, .mpwpb-pm-panel input[type=submit].button-primary { display: inline-flex; align-items: center; justify-content: center; min-height: 40px; margin: 0; padding: 9px 17px; border: 1px solid transparent; border-radius: 10px; background: linear-gradient(135deg,#4565e8,#6967ed); color: #fff; font-size: 12.5px; font-weight: 700; line-height: 1.2; text-shadow: none; box-shadow: 0 7px 16px rgba(69,101,232,.22); cursor: pointer; }
					.mpwpb-pm-panel .mpwpb-pm-btn-primary:hover, .mpwpb-pm-panel input[type=submit].button-primary:hover { background: linear-gradient(135deg,#3656d6,#5758db); color: #fff; }
					.mpwpb-payment-save-footer { display: flex !important; justify-content: flex-end !important; align-items: center !important; margin-top: 18px !important; padding-top: 18px; border-top: 1px solid #e4e9f1; }
					.mpwpb-payment-save-footer .submit { margin: 0 !important; padding: 0 !important; }
					.mpwpb-payment-save-footer input#submit { min-height: 42px; margin: 0; padding: 9px 22px; border: 0; border-radius: 10px; background: linear-gradient(135deg,#4565e8,#6967ed); color: #fff; font-size: 12.5px; font-weight: 700; line-height: 1.2; text-shadow: none; box-shadow: 0 7px 16px rgba(69,101,232,.22); }
					.mpwpb-payment-save-footer input#submit:hover { background: linear-gradient(135deg,#3656d6,#5758db); color: #fff; }
					@media (max-width: 720px) { .mpwpb-gateway-row { flex-wrap: wrap; } .mpwpb-gateway-name { min-width: 120px; } .mpwpb-gw-panel label.mpwpb-gw-field { grid-template-columns: 1fr; gap: 7px !important; } .mpwpb-confirmation-row { grid-template-columns: 1fr; } .mpwpb-wc-gateway-card { grid-template-columns: auto 1fr auto; } .mpwpb-wc-gateway-card .mpwpb-gateway-configure { grid-column: 1 / -1; justify-content: center; } .mpwpb-wc-gw-panel .form-table, .mpwpb-wc-gw-panel .form-table tbody, .mpwpb-wc-gw-panel .form-table tr, .mpwpb-wc-gw-panel .form-table th, .mpwpb-wc-gw-panel .form-table td { display: block; width: 100%; } .mpwpb-wc-gw-panel .form-table th { padding: 13px 0 5px; border-top: 1px solid #edf1f6; } .mpwpb-wc-gw-panel .form-table td { padding: 0 0 13px; } .mpwpb-settings-row { align-items: stretch; flex-direction: column; gap: 9px; } .mpwpb-settings-row select { width: 100%; } }
					body.mpwpb-pm-confirm-open { overflow: hidden; }
					#mpwpb-pm-confirm-modal.mpwpb-pm-confirm-overlay { position: fixed !important; inset: 0 !important; z-index: 100020 !important; display: none; align-items: center !important; justify-content: center !important; padding: 24px !important; box-sizing: border-box !important; background: rgba(15,23,42,.68) !important; -webkit-backdrop-filter: blur(5px); backdrop-filter: blur(5px); opacity: 0; transition: opacity .18s ease; }
					#mpwpb-pm-confirm-modal.mpwpb-pm-confirm-overlay.is-open { opacity: 1; }
					#mpwpb-pm-confirm-modal .mpwpb-pm-confirm-box { position: relative !important; width: min(100%, 520px) !important; max-width: 520px !important; margin: 0 !important; padding: 0 !important; overflow: hidden !important; border: 1px solid rgba(255,255,255,.72) !important; border-radius: 20px !important; background: #fff !important; color: #172033 !important; box-shadow: 0 30px 80px rgba(15,23,42,.32), 0 8px 24px rgba(15,23,42,.16) !important; transform: translateY(14px) scale(.975); transition: transform .22s cubic-bezier(.2,.8,.2,1); }
					#mpwpb-pm-confirm-modal.is-open .mpwpb-pm-confirm-box { transform: translateY(0) scale(1); }
					#mpwpb-pm-confirm-modal .mpwpb-pm-confirm-box::before { content: ""; position: absolute; inset: 0 0 auto; height: 5px; background: linear-gradient(90deg,#315bdc 0%,#6366f1 52%,#8b5cf6 100%); }
					#mpwpb-pm-confirm-modal .mpwpb-pm-confirm-close { position: absolute; top: 16px; right: 16px; z-index: 2; display: grid; place-items: center; width: 34px; height: 34px; min-height: 0; margin: 0; padding: 0; border: 1px solid #e2e8f0; border-radius: 10px; background: #fff; color: #64748b; cursor: pointer; transition: background .15s ease, color .15s ease, border-color .15s ease, transform .15s ease; }
					#mpwpb-pm-confirm-modal .mpwpb-pm-confirm-close:hover { border-color: #cbd5e1; background: #f8fafc; color: #172033; transform: rotate(4deg); }
					#mpwpb-pm-confirm-modal .mpwpb-pm-confirm-close .dashicons { width: 18px; height: 18px; font-size: 18px; line-height: 18px; }
					#mpwpb-pm-confirm-modal .mpwpb-pm-confirm-head { display: grid !important; grid-template-columns: 56px minmax(0,1fr); align-items: center !important; gap: 16px !important; margin: 0 !important; padding: 30px 60px 22px 28px !important; background: linear-gradient(145deg,#f8faff 0%,#fff 68%) !important; border-bottom: 1px solid #eef2f7 !important; }
					#mpwpb-pm-confirm-modal .mpwpb-pm-confirm-icon { display: grid !important; place-items: center !important; width: 54px !important; height: 54px !important; min-width: 54px !important; margin: 0 !important; padding: 0 !important; border: 1px solid #fde2a6 !important; border-radius: 16px !important; background: linear-gradient(145deg,#fffaf0,#fff2cf) !important; color: #c77908 !important; box-shadow: 0 8px 20px rgba(199,121,8,.13) !important; }
					#mpwpb-pm-confirm-modal .mpwpb-pm-confirm-icon .dashicons { width: 25px !important; height: 25px !important; font-size: 25px !important; line-height: 25px !important; }
					#mpwpb-pm-confirm-modal .mpwpb-pm-confirm-heading { min-width: 0; }
					#mpwpb-pm-confirm-modal .mpwpb-pm-confirm-eyebrow { display: block !important; margin: 0 0 5px !important; color: #5368dd !important; font-size: 10.5px !important; font-weight: 800 !important; line-height: 1.2 !important; letter-spacing: .1em !important; text-transform: uppercase !important; }
					#mpwpb-pm-confirm-modal .mpwpb-pm-confirm-title { margin: 0 !important; padding: 0 !important; color: #172033 !important; font-size: 19px !important; font-weight: 750 !important; line-height: 1.35 !important; letter-spacing: -.015em !important; }
					#mpwpb-pm-confirm-modal .mpwpb-pm-confirm-body { padding: 22px 28px 24px !important; background: #fff !important; }
					#mpwpb-pm-confirm-modal .mpwpb-pm-confirm-intro { margin: 0 0 14px !important; color: #58667b !important; font-size: 13.5px !important; line-height: 1.65 !important; }
					#mpwpb-pm-confirm-modal .mpwpb-pm-confirm-change { margin: 0 !important; padding: 14px 15px !important; border: 1px solid #dce4ff !important; border-radius: 13px !important; background: #f6f8ff !important; }
					#mpwpb-pm-confirm-modal .mpwpb-pm-confirm-change-label { display: block !important; margin: 0 0 10px !important; color: #5368dd !important; font-size: 10px !important; font-weight: 800 !important; line-height: 1.2 !important; letter-spacing: .08em !important; text-transform: uppercase !important; }
					#mpwpb-pm-confirm-modal .mpwpb-pm-confirm-route { display: grid !important; grid-template-columns: minmax(0,1fr) 30px minmax(0,1fr) !important; align-items: center !important; gap: 9px !important; }
					#mpwpb-pm-confirm-modal .mpwpb-pm-confirm-method { display: flex !important; flex-direction: column !important; gap: 3px !important; min-width: 0 !important; padding: 9px 10px !important; border: 1px solid #e1e7f0 !important; border-radius: 10px !important; background: #fff !important; }
					#mpwpb-pm-confirm-modal .mpwpb-pm-confirm-method small { display: block !important; margin: 0 !important; color: #8a96a8 !important; font-size: 8.5px !important; font-weight: 800 !important; line-height: 1.2 !important; letter-spacing: .07em !important; text-transform: uppercase !important; }
					#mpwpb-pm-confirm-modal .mpwpb-pm-confirm-method strong { display: block !important; min-width: 0 !important; margin: 0 !important; color: #26334a !important; font-size: 12px !important; font-weight: 750 !important; line-height: 1.3 !important; overflow-wrap: anywhere !important; }
					#mpwpb-pm-confirm-modal .mpwpb-pm-confirm-method.is-next { border-color: #bdcaff !important; background: #eef2ff !important; }
					#mpwpb-pm-confirm-modal .mpwpb-pm-confirm-method.is-next strong { color: #354fc7 !important; }
					#mpwpb-pm-confirm-modal .mpwpb-pm-confirm-route-arrow { display: grid !important; place-items: center !important; width: 30px !important; height: 30px !important; border-radius: 50% !important; background: #5368dd !important; color: #fff !important; }
					#mpwpb-pm-confirm-modal .mpwpb-pm-confirm-route-arrow .dashicons { width: 16px !important; height: 16px !important; font-size: 16px !important; line-height: 16px !important; }
					#mpwpb-pm-confirm-modal .mpwpb-pm-confirm-detail { margin: 11px 0 0 !important; color: #536177 !important; font-size: 12px !important; font-weight: 550 !important; line-height: 1.5 !important; }
					#mpwpb-pm-confirm-modal .mpwpb-pm-confirm-footer { display: flex !important; align-items: center !important; justify-content: space-between !important; gap: 16px !important; padding: 17px 28px !important; border-top: 1px solid #e8edf4 !important; background: #f8fafc !important; }
					#mpwpb-pm-confirm-modal .mpwpb-pm-confirm-hint { display: inline-flex; align-items: center; gap: 6px; max-width: 138px; color: #7a879a; font-size: 10px; font-weight: 600; line-height: 1.35; }
					#mpwpb-pm-confirm-modal .mpwpb-pm-confirm-hint .dashicons { flex: 0 0 15px; width: 15px; height: 15px; color: #22a06b; font-size: 15px; line-height: 15px; }
					#mpwpb-pm-confirm-modal .mpwpb-pm-confirm-actions { display: flex !important; flex-wrap: nowrap !important; align-items: center !important; justify-content: flex-end !important; gap: 9px !important; margin: 0 !important; }
					#mpwpb-pm-confirm-modal .mpwpb-pm-confirm-actions button { display: inline-flex !important; flex: 0 0 auto !important; align-items: center !important; justify-content: center !important; gap: 7px !important; min-width: 104px !important; min-height: 40px !important; margin: 0 !important; padding: 9px 13px !important; border-radius: 10px !important; font-family: inherit !important; font-size: 12px !important; font-weight: 700 !important; line-height: 1.2 !important; white-space: nowrap !important; cursor: pointer !important; transition: transform .15s ease, box-shadow .15s ease, background .15s ease !important; }
					#mpwpb-pm-confirm-modal .mpwpb-pm-confirm-actions .mpwpb-pm-btn-primary { min-width: 126px !important; }
					#mpwpb-pm-confirm-modal .mpwpb-pm-confirm-actions .mpwpb-pm-btn-outline { border: 1px solid #d9e0ea !important; background: #fff !important; color: #475569 !important; box-shadow: none !important; }
					#mpwpb-pm-confirm-modal .mpwpb-pm-confirm-actions .mpwpb-pm-btn-outline:hover { border-color: #c4cedb !important; background: #f3f6fa !important; color: #172033 !important; }
					#mpwpb-pm-confirm-modal .mpwpb-pm-confirm-actions .mpwpb-pm-btn-primary { border: 1px solid transparent !important; background: linear-gradient(135deg,#315bdc,#5f63e8) !important; color: #fff !important; box-shadow: 0 7px 16px rgba(49,91,220,.24) !important; }
					#mpwpb-pm-confirm-modal .mpwpb-pm-confirm-actions .mpwpb-pm-btn-primary:hover { background: linear-gradient(135deg,#244bc4,#5054d6) !important; box-shadow: 0 9px 20px rgba(49,91,220,.3) !important; transform: translateY(-1px); }
					#mpwpb-pm-confirm-modal button:focus-visible { outline: 3px solid rgba(49,91,220,.22) !important; outline-offset: 2px !important; }
					@media (max-width: 560px) { #mpwpb-pm-confirm-modal.mpwpb-pm-confirm-overlay { padding: 14px !important; } #mpwpb-pm-confirm-modal .mpwpb-pm-confirm-head { grid-template-columns: 46px minmax(0,1fr); padding: 26px 48px 18px 20px !important; } #mpwpb-pm-confirm-modal .mpwpb-pm-confirm-icon { width: 44px !important; height: 44px !important; min-width: 44px !important; border-radius: 13px !important; } #mpwpb-pm-confirm-modal .mpwpb-pm-confirm-title { font-size: 17px !important; } #mpwpb-pm-confirm-modal .mpwpb-pm-confirm-body { padding: 18px 20px 20px !important; } #mpwpb-pm-confirm-modal .mpwpb-pm-confirm-route { grid-template-columns: 1fr !important; } #mpwpb-pm-confirm-modal .mpwpb-pm-confirm-route-arrow { transform: rotate(90deg); justify-self: center; } #mpwpb-pm-confirm-modal .mpwpb-pm-confirm-footer { align-items: stretch !important; flex-direction: column !important; padding: 15px 20px 20px !important; } #mpwpb-pm-confirm-modal .mpwpb-pm-confirm-hint { max-width: none; } #mpwpb-pm-confirm-modal .mpwpb-pm-confirm-actions { width: 100%; } #mpwpb-pm-confirm-modal .mpwpb-pm-confirm-actions button { flex: 1 1 0 !important; min-width: 0 !important; } }
					@media (prefers-reduced-motion: reduce) { #mpwpb-pm-confirm-modal.mpwpb-pm-confirm-overlay, #mpwpb-pm-confirm-modal .mpwpb-pm-confirm-box { transition: none !important; } }
					.mpwpb-settings-row label[style*="display:block"] { display: flex !important; margin-bottom: 8px; }
					.mpwpb-settings-row label[style*="display:block"]:last-child { margin-bottom: 0; }
				</style>
				<div class="mpwpb-pm-toggle">
					<button type="button" class="mpwpb-pm-toggle-btn <?php echo $payment_type !== 'custom' ? 'is-active' : ''; ?>" data-value="woocommerce"><?php esc_html_e('WooCommerce', 'service-booking-manager'); ?></button>
					<button type="button" class="mpwpb-pm-toggle-btn <?php echo $payment_type === 'custom' ? 'is-active' : ''; ?>" data-value="custom"><?php esc_html_e('Custom Payment', 'service-booking-manager'); ?></button>
				</div>
				<input type="hidden" name="<?php echo esc_attr($option); ?>[payment_method_type]" id="mpwpb_payment_method_type_input" value="<?php echo esc_attr($payment_type ?: 'custom'); ?>"/>

				<div id="mpwpb-pm-confirm-modal" class="mpwpb-pm-confirm-overlay" aria-hidden="true">
					<div class="mpwpb-pm-confirm-box" role="alertdialog" aria-modal="true" aria-labelledby="mpwpb-pm-confirm-title" aria-describedby="mpwpb-pm-confirm-text" tabindex="-1">
						<button type="button" class="mpwpb-pm-confirm-close" data-mpwpb-confirm-close aria-label="<?php esc_attr_e('Close dialog', 'service-booking-manager'); ?>"><span class="dashicons dashicons-no-alt" aria-hidden="true"></span></button>
						<div class="mpwpb-pm-confirm-head">
							<span class="mpwpb-pm-confirm-icon"><span class="dashicons dashicons-shield-alt" aria-hidden="true"></span></span>
							<div class="mpwpb-pm-confirm-heading">
								<span class="mpwpb-pm-confirm-eyebrow"><?php esc_html_e('Payment configuration', 'service-booking-manager'); ?></span>
								<h3 class="mpwpb-pm-confirm-title" id="mpwpb-pm-confirm-title"><?php esc_html_e('Only One Payment Method Allowed', 'service-booking-manager'); ?></h3>
							</div>
						</div>
						<div class="mpwpb-pm-confirm-body" id="mpwpb-pm-confirm-text"></div>
						<div class="mpwpb-pm-confirm-footer">
							<span class="mpwpb-pm-confirm-hint"><span class="dashicons dashicons-lock" aria-hidden="true"></span><?php esc_html_e('Your gateway settings remain saved.', 'service-booking-manager'); ?></span>
							<div class="mpwpb-pm-confirm-actions">
								<button type="button" class="mpwpb-pm-btn-outline" data-mpwpb-confirm-cancel><?php esc_html_e('Keep Current', 'service-booking-manager'); ?></button>
								<button type="button" class="mpwpb-pm-btn-primary" data-mpwpb-confirm-ok><span class="dashicons dashicons-update" aria-hidden="true"></span><?php esc_html_e('Switch Method', 'service-booking-manager'); ?></button>
							</div>
						</div>
					</div>
				</div>

				<div class="mpwpb-pm-panel mpwpb-pm-panel--woocommerce" data-panel="woocommerce" style="<?php echo $payment_type === 'custom' ? 'display:none;' : ''; ?>">
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
								<p class="description"><?php esc_html_e('If enabled, WooCommerce payment gateway will be used for checkout.', 'service-booking-manager'); ?></p>
							</div>
							<label class="mpwpb-toggle-switch mpwpb-toggle-switch-lg">
								<input type="checkbox" id="mpwpb_wc_enable_toggle" <?php checked($payment_type === 'woocommerce'); ?>/>
								<span class="mpwpb-toggle-slider"></span>
							</label>
						</div>

						<div class="mpwpb-accordion mpwpb-wc-dependent" <?php echo $payment_type === 'woocommerce' ? '' : 'style="display:none;"'; ?>>
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
								<?php foreach (WC()->payment_gateways()->payment_gateways() as $wc_gateway) :
									$config_panel_id = 'mpwpb-wc-gw-config-' . $wc_gateway->id;
									$config_fields = $wc_gateway->get_form_fields();
									unset($config_fields['enabled']);
									?>
									<div class="mpwpb-wc-gateway-card">
										<label class="mpwpb-toggle-switch mpwpb-toggle-switch-lg">
											<input type="checkbox" class="mpwpb-wc-gateway-toggle" data-gateway="<?php echo esc_attr($wc_gateway->id); ?>" data-nonce="<?php echo esc_attr(wp_create_nonce('mpwpb_toggle_wc_gateway_' . $wc_gateway->id)); ?>" data-enabled-field="<?php echo esc_attr($wc_gateway->get_field_key('enabled')); ?>" <?php checked($wc_gateway->enabled === 'yes'); ?>/>
											<span class="mpwpb-toggle-slider"></span>
										</label>
										<span class="mpwpb-wc-gateway-name"><?php echo esc_html($wc_gateway->get_method_title()); ?></span>
										<span class="mpwpb-wc-gateway-status <?php echo $wc_gateway->enabled === 'yes' ? 'is-enabled' : ''; ?>" data-status-for="<?php echo esc_attr($wc_gateway->id); ?>">
											<?php echo $wc_gateway->enabled === 'yes' ? esc_html__('ENABLED', 'service-booking-manager') : esc_html__('DISABLED', 'service-booking-manager'); ?>
										</span>
										<button type="button" class="mpwpb-pm-btn-outline mpwpb-gateway-configure" data-target="<?php echo esc_attr($config_panel_id); ?>" aria-expanded="false"><?php esc_html_e('Configure', 'service-booking-manager'); ?></button>
										<div class="mpwpb-wc-gateway-desc"><?php echo wp_kses_post($wc_gateway->get_method_description()); ?></div>
									</div>
									<div class="mpwpb-wc-gw-panel" id="<?php echo esc_attr($config_panel_id); ?>" style="display:none;" aria-hidden="true">
										<table class="form-table">
											<?php echo $wc_gateway->generate_settings_html($config_fields, false); ?>
										</table>
										<p class="mpwpb-wc-gw-save-row">
											<button type="button" class="mpwpb-pm-btn-primary mpwpb-wc-gw-save" data-gateway="<?php echo esc_attr($wc_gateway->id); ?>" data-nonce="<?php echo esc_attr(wp_create_nonce('mpwpb_save_wc_gateway_' . $wc_gateway->id)); ?>"><?php esc_html_e('Save Changes', 'service-booking-manager'); ?></button>
											<span class="mpwpb-wc-gw-save-status" style="margin-left:10px;"></span>
										</p>
									</div>
								<?php endforeach; ?>
							</div>
						</div>

						<div class="mpwpb-accordion mpwpb-wc-dependent" <?php echo $payment_type === 'woocommerce' ? '' : 'style="display:none;"'; ?>>
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
								<div class="mpwpb-settings-row mpwpb-settings-row-top">
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

				<div class="mpwpb-pm-panel mpwpb-pm-panel--custom" data-panel="custom" style="<?php echo $payment_type === 'custom' ? '' : 'display:none;'; ?>">
					<div class="mpwpb-toggle-row">
						<div>
							<strong>
								<?php esc_html_e('Enable Custom Payment Method', 'service-booking-manager'); ?>
							</strong>
							<p class="description">
								<?php if ($pro_active) : ?>
									<?php esc_html_e('If enabled, the custom payment gateways below (PayPal, Stripe, Offline) will be used for checkout.', 'service-booking-manager'); ?>
								<?php else : ?>
									<?php esc_html_e('If enabled, bookings are taken through the built-in checkout instead of WooCommerce. Offline Payment is included free; PayPal and Stripe require Pro.', 'service-booking-manager'); ?>
								<?php endif; ?>
							</p>
						</div>
						<label class="mpwpb-toggle-switch mpwpb-toggle-switch-lg">
							<input type="checkbox" id="mpwpb_custom_enable_toggle" <?php checked($payment_type === 'custom'); ?>/>
							<span class="mpwpb-toggle-slider"></span>
						</label>
					</div>

					<div class="mpwpb-custom-dependent <?php echo $payment_type === 'custom' ? '' : 'mpwpb-locked'; ?>">
					<?php foreach ($gateways as $key => $gw) :
						$enabled = MPWPB_Global_Function::get_payment_setting($key . '_enabled') === 'on';
						// Pro-only gateway on a free site: show the card, but with no
						// switch and no config panel. The stored value is preserved in
						// a hidden field so saving this page from a free site never
						// wipes credentials/state configured while Pro was active.
						$locked = !empty($gw['pro']) && !$pro_active;
						if ($locked) :
							?>
							<div class="mpwpb-gateway-card mpwpb-gateway-card--locked" style="background:<?php echo esc_attr($gw['gradient']); ?>">
								<div class="mpwpb-gateway-row">
									<span class="mpwpb-gateway-icon"><i class="<?php echo esc_attr($gw['icon']); ?>"></i></span>
									<span class="mpwpb-gateway-name">
										<?php echo esc_html($gw['label']); ?>
										<span class="mpwpb-pro-badge"><?php esc_html_e('PRO', 'service-booking-manager'); ?></span>
									</span>
									<?php
									// This panel's save rewrites the whole option from what
									// was submitted, so every key of a locked gateway has to
									// ride along or a save from a downgraded site would wipe
									// credentials that are still valid once Pro comes back.
									$preserved_keys = array_merge([$key . '_enabled'], self::pro_gateway_setting_keys($key));
									foreach ($preserved_keys as $preserved_key) :
										$preserved_value = MPWPB_Global_Function::get_payment_setting($preserved_key);
										if ($preserved_value === '' || is_array($preserved_value)) {
											continue;
										}
										?>
										<input type="hidden" name="<?php echo esc_attr($option); ?>[<?php echo esc_attr($preserved_key); ?>]" value="<?php echo esc_attr($preserved_value); ?>"/>
									<?php endforeach; ?>
									<a class="mpwpb-gateway-configure mpwpb-gateway-upgrade" href="<?php echo esc_url($upgrade_url); ?>" target="_blank" rel="noopener">
										<span class="dashicons dashicons-lock" aria-hidden="true"></span><?php esc_html_e('Unlock with Pro', 'service-booking-manager'); ?>
									</a>
								</div>
							</div>
							<?php
							continue;
						endif;
						?>
						<div class="mpwpb-gateway-card" style="background:<?php echo esc_attr($gw['gradient']); ?>">
							<div class="mpwpb-gateway-row">
								<span class="mpwpb-gateway-icon"><i class="<?php echo esc_attr($gw['icon']); ?>"></i></span>
								<span class="mpwpb-gateway-name"><?php echo esc_html($gw['label']); ?></span>
								<span class="mpwpb-gateway-status <?php echo $enabled ? 'is-enabled' : ''; ?>" data-status-for="<?php echo esc_attr($key); ?>">
									<?php echo $enabled ? esc_html__('Enabled', 'service-booking-manager') : esc_html__('Disabled', 'service-booking-manager'); ?>
								</span>
								<button type="button" class="mpwpb-gateway-configure" data-target="mpwpb-gw-panel-<?php echo esc_attr($key); ?>" aria-expanded="false"><?php esc_html_e('Configure', 'service-booking-manager'); ?></button>
							</div>
						</div>
						<div class="mpwpb-gw-panel" id="mpwpb-gw-panel-<?php echo esc_attr($key); ?>" style="display:none;" aria-hidden="true" data-gateway="<?php echo esc_attr($key); ?>">
							<label class="mpwpb-gw-enable-row">
								<input type="hidden" name="<?php echo esc_attr($option); ?>[<?php echo esc_attr($key); ?>_enabled]" value="off"/>
								<span class="mpwpb-gw-enable-label"><?php esc_html_e('Enable this payment method', 'service-booking-manager'); ?></span>
								<span class="mpwpb-toggle-switch mpwpb-toggle-switch-lg">
									<input type="checkbox" class="mpwpb-gw-enable-toggle" data-status-for="<?php echo esc_attr($key); ?>" name="<?php echo esc_attr($option); ?>[<?php echo esc_attr($key); ?>_enabled]" value="on" <?php checked($enabled); ?>/>
									<span class="mpwpb-toggle-slider"></span>
								</span>
							</label>
							<?php if ($key === 'offline') : ?>
								<label class="mpwpb-gw-field">
									<span class="mpwpb-gw-field-label"><?php esc_html_e('Instructions shown to the customer at checkout', 'service-booking-manager'); ?></span>
									<span class="mpwpb-gw-field-control">
										<textarea rows="3" name="<?php echo esc_attr($option); ?>[offline_instructions]"><?php echo esc_textarea(MPWPB_Global_Function::get_payment_setting('offline_instructions', esc_html__('Please pay via bank transfer. We will confirm your booking once payment is received.', 'service-booking-manager'))); ?></textarea>
									</span>
								</label>
								<p class="description mpwpb-gw-help"><?php esc_html_e('These instructions are shown during checkout and included with the booking payment details.', 'service-booking-manager'); ?></p>
							<?php elseif ($key === 'stripe') : ?>
								<label class="mpwpb-gw-field">
									<span class="mpwpb-gw-field-label"><?php esc_html_e('Mode', 'service-booking-manager'); ?></span>
									<span class="mpwpb-gw-field-control">
										<select name="<?php echo esc_attr($option); ?>[stripe_mode]">
											<option value="test" <?php selected(MPWPB_Global_Function::get_payment_setting('stripe_mode', 'test'), 'test'); ?>><?php esc_html_e('Test', 'service-booking-manager'); ?></option>
											<option value="live" <?php selected(MPWPB_Global_Function::get_payment_setting('stripe_mode'), 'live'); ?>><?php esc_html_e('Live', 'service-booking-manager'); ?></option>
										</select>
									</span>
								</label>
								<label class="mpwpb-gw-field">
									<span class="mpwpb-gw-field-label"><?php esc_html_e('Publishable Key', 'service-booking-manager'); ?></span>
									<span class="mpwpb-gw-field-control">
										<input type="text" name="<?php echo esc_attr($option); ?>[stripe_publishable_key]" value="<?php echo esc_attr(MPWPB_Global_Function::get_payment_setting('stripe_publishable_key')); ?>"/>
									</span>
								</label>
								<label class="mpwpb-gw-field">
									<span class="mpwpb-gw-field-label"><?php esc_html_e('Secret Key', 'service-booking-manager'); ?></span>
									<span class="mpwpb-gw-field-control">
										<input type="password" name="<?php echo esc_attr($option); ?>[stripe_secret_key]" value="<?php echo esc_attr(MPWPB_Global_Function::get_payment_setting('stripe_secret_key')); ?>"/>
									</span>
								</label>
								<label class="mpwpb-gw-field">
									<span class="mpwpb-gw-field-label"><?php esc_html_e('Webhook Secret', 'service-booking-manager'); ?></span>
									<span class="mpwpb-gw-field-control">
										<input type="password" name="<?php echo esc_attr($option); ?>[stripe_webhook_secret]" value="<?php echo esc_attr(MPWPB_Global_Function::get_payment_setting('stripe_webhook_secret')); ?>"/>
									</span>
								</label>
								<p class="description mpwpb-gw-help">
									<?php
									echo wp_kses_post(
										sprintf(
											/* translators: %s: Stripe webhook endpoint URL */
											__('Customers pay on a Stripe-hosted page and are redirected back once done. Also add this URL as a webhook endpoint in your Stripe Dashboard (Developers → Webhooks) listening for the <code>checkout.session.completed</code> event — it confirms payment even if the customer closes the tab before returning: <code>%s</code>', 'service-booking-manager'),
											esc_url(admin_url('admin-ajax.php?action=mpwpb_stripe_webhook'))
										)
									);
									?>
								</p>
							<?php elseif ($key === 'paypal') : ?>
								<label class="mpwpb-gw-field">
									<span class="mpwpb-gw-field-label"><?php esc_html_e('Mode', 'service-booking-manager'); ?></span>
									<span class="mpwpb-gw-field-control">
										<select name="<?php echo esc_attr($option); ?>[paypal_mode]">
											<option value="sandbox" <?php selected(MPWPB_Global_Function::get_payment_setting('paypal_mode', 'sandbox'), 'sandbox'); ?>><?php esc_html_e('Sandbox', 'service-booking-manager'); ?></option>
											<option value="live" <?php selected(MPWPB_Global_Function::get_payment_setting('paypal_mode'), 'live'); ?>><?php esc_html_e('Live', 'service-booking-manager'); ?></option>
										</select>
									</span>
								</label>
								<label class="mpwpb-gw-field">
									<span class="mpwpb-gw-field-label"><?php esc_html_e('Client ID', 'service-booking-manager'); ?></span>
									<span class="mpwpb-gw-field-control">
										<input type="text" name="<?php echo esc_attr($option); ?>[paypal_client_id]" value="<?php echo esc_attr(MPWPB_Global_Function::get_payment_setting('paypal_client_id')); ?>"/>
									</span>
								</label>
								<label class="mpwpb-gw-field">
									<span class="mpwpb-gw-field-label"><?php esc_html_e('Client Secret', 'service-booking-manager'); ?></span>
									<span class="mpwpb-gw-field-control">
										<input type="password" name="<?php echo esc_attr($option); ?>[paypal_client_secret]" value="<?php echo esc_attr(MPWPB_Global_Function::get_payment_setting('paypal_client_secret')); ?>"/>
									</span>
								</label>
								<p class="description mpwpb-gw-help"><?php esc_html_e('Customers approve payment on a PayPal-hosted page and are redirected back once done. Use your PayPal app\'s Client ID/Secret for the mode selected above (sandbox app credentials while testing, live app credentials when ready to accept real payments).', 'service-booking-manager'); ?></p>
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
				</div>
				<div class="justifyBetween _mT mpwpb-payment-save-footer">
					<?php submit_button(); ?>
				</div>
				<script>
					(function ($) {
						"use strict";
						$(document).ready(function () {
							// Single source of truth for both "which gateway is enabled" toggles
							// (WooCommerce tab's and Custom tab's) plus their dependent sections.
							// Value is one of 'woocommerce', 'custom', or 'none' — both switches can
							// be off at once, but turning one on always turns the other off, so at
							// most one is ever active. Custom Payment itself is free (its Offline
							// gateway ships in this plugin); only the individual Stripe/PayPal cards
							// are Pro-locked, server-side in
							// MPWPB_Global_Function::is_gateway_available().
							function mpwpbSyncPaymentToggles(value) {
								$('#mpwpb_payment_method_type_input').val(value);
								$('#mpwpb_wc_enable_toggle').prop('checked', value === 'woocommerce');
								$('#mpwpb_custom_enable_toggle').prop('checked', value === 'custom');
								$('.mpwpb-wc-dependent').toggle(value === 'woocommerce');
								$('.mpwpb-custom-dependent').toggleClass('mpwpb-locked', value !== 'custom');
							}
							function mpwpbSetPaymentMode(value, keepPanel) {
								$('.mpwpb-pm-toggle-btn').removeClass('is-active');
								$('.mpwpb-pm-toggle-btn[data-value="' + value + '"]').addClass('is-active');
								mpwpbSyncPaymentToggles(value);
								if (!keepPanel) {
									$('.mpwpb-pm-panel[data-panel="woocommerce"]').toggle(value === 'woocommerce');
									$('.mpwpb-pm-panel[data-panel="custom"]').toggle(value === 'custom');
								}
							}
							// Only WooCommerce and Custom Payment are mutually exclusive (both being
							// off at once is fine) -- gate the one real cross-over with a confirm
							// dialog (not the browser's native window.confirm(), which reads as a
							// jarring "this site says" popup) so switching isn't accidental, since
							// it silently disables whichever gateway was previously active.
							var mpwpbSwitchConfirmCopy = <?php echo wp_json_encode($pm_confirm_copy); ?>;
							var mpwpbSwitchConfirmLabels = <?php echo wp_json_encode($pm_confirm_labels); ?>;
							var $mpwpbConfirmModal = $('#mpwpb-pm-confirm-modal');
							var mpwpbConfirmCloseTimer = null;
							var mpwpbConfirmPreviousFocus = null;
							function mpwpbOpenConfirmModal(copy, onConfirm) {
								// Build the explanation as DOM text rather than one long HTML
								// sentence, so global admin typography cannot reorder its meaning.
								var $change = $('<div class="mpwpb-pm-confirm-change"></div>');
								var $route = $('<div class="mpwpb-pm-confirm-route"></div>');
								var $current = $('<div class="mpwpb-pm-confirm-method is-current"><small></small><strong></strong></div>');
								var $next = $('<div class="mpwpb-pm-confirm-method is-next"><small></small><strong></strong></div>');
								$current.find('small').text(mpwpbSwitchConfirmLabels.current);
								$current.find('strong').text(copy.from);
								$next.find('small').text(mpwpbSwitchConfirmLabels.next);
								$next.find('strong').text(copy.to);
								$route.append($current, '<span class="mpwpb-pm-confirm-route-arrow" aria-hidden="true"><span class="dashicons dashicons-arrow-right-alt"></span></span>', $next);
								$change.append(
									$('<span class="mpwpb-pm-confirm-change-label"></span>').text(mpwpbSwitchConfirmLabels.change),
									$route,
									$('<p class="mpwpb-pm-confirm-detail"></p>').text(copy.detail)
								);
								$mpwpbConfirmModal.find('#mpwpb-pm-confirm-text').empty().append(
									$('<p class="mpwpb-pm-confirm-intro"></p>').text(copy.intro),
									$change
								);
								clearTimeout(mpwpbConfirmCloseTimer);
								mpwpbConfirmPreviousFocus = document.activeElement;
								$('body').addClass('mpwpb-pm-confirm-open');
								$mpwpbConfirmModal.css('display', 'flex').attr('aria-hidden', 'false');
								(window.requestAnimationFrame || function (callback) { setTimeout(callback, 0); })(function () {
									$mpwpbConfirmModal.addClass('is-open');
									$mpwpbConfirmModal.find('[data-mpwpb-confirm-cancel]').trigger('focus');
								});
								// Rebound on every open (not delegated once at ready) so each call's
								// onConfirm closure is the only one wired up -- avoids stacking
								// duplicate handlers from earlier opens.
								$mpwpbConfirmModal.find('[data-mpwpb-confirm-ok]').off('click').on('click', function () {
									mpwpbCloseConfirmModal();
									onConfirm();
								});
								$mpwpbConfirmModal.find('[data-mpwpb-confirm-cancel], [data-mpwpb-confirm-close]').off('click').on('click', mpwpbCloseConfirmModal);
							}
							function mpwpbCloseConfirmModal() {
								if (mpwpbConfirmPreviousFocus && document.contains(mpwpbConfirmPreviousFocus)) {
									mpwpbConfirmPreviousFocus.focus();
								}
								$mpwpbConfirmModal.removeClass('is-open').attr('aria-hidden', 'true');
								$('body').removeClass('mpwpb-pm-confirm-open');
								clearTimeout(mpwpbConfirmCloseTimer);
								mpwpbConfirmCloseTimer = setTimeout(function () {
									$mpwpbConfirmModal.hide();
								}, 180);
							}
							$mpwpbConfirmModal.on('click', function (e) {
								if (e.target === this) {
									mpwpbCloseConfirmModal();
								}
							});
							$(document).off('keydown.mpwpbPmConfirm').on('keydown.mpwpbPmConfirm', function (e) {
								if (e.key === 'Escape' && $mpwpbConfirmModal.is(':visible')) {
									e.preventDefault();
									mpwpbCloseConfirmModal();
								}
								if (e.key === 'Tab' && $mpwpbConfirmModal.hasClass('is-open')) {
									var $focusable = $mpwpbConfirmModal.find('button:visible');
									var first = $focusable.get(0);
									var last = $focusable.get($focusable.length - 1);
									if (e.shiftKey && document.activeElement === first) {
										e.preventDefault();
										last.focus();
									} else if (!e.shiftKey && document.activeElement === last) {
										e.preventDefault();
										first.focus();
									}
								}
							});
							/**
							 * Runs `apply` immediately if switching to `newValue` doesn't cross
							 * over from the other active method; otherwise shows the confirm
							 * modal first and only runs `apply` if the admin accepts. `onCancel`
							 * (optional) runs if they dismiss it instead -- callers use it to
							 * restore a checkbox's UI state, since the browser already flipped it
							 * before the 'change' event fired.
							 */
							function mpwpbGuardPaymentSwitch(newValue, apply, onCancel) {
								var current = $('#mpwpb_payment_method_type_input').val() || 'none';
								var isCrossOver = (newValue === 'woocommerce' && current === 'custom')
									|| (newValue === 'custom' && current === 'woocommerce');
								if (!isCrossOver) {
									apply();
									return;
								}
								if (onCancel) {
									onCancel();
								}
								mpwpbOpenConfirmModal(mpwpbSwitchConfirmCopy[newValue], apply);
							}
							$('.mpwpb-pm-toggle-btn').on('click', function () {
								var value = $(this).data('value');
								mpwpbGuardPaymentSwitch(value, function () {
									mpwpbSetPaymentMode(value);
								});
							});
							$('#mpwpb_wc_enable_toggle').on('change', function () {
								// Independent of the top tab selector: only flips which gateway is functionally
								// active. Deliberately does not touch the top buttons or panel visibility.
								// Unchecking disables WooCommerce payment outright — it does not hand control
								// to Custom Payment.
								var $toggle = $(this);
								if (!this.checked) {
									mpwpbSyncPaymentToggles('none');
									return;
								}
								mpwpbGuardPaymentSwitch('woocommerce', function () {
									$toggle.prop('checked', true);
									mpwpbSyncPaymentToggles('woocommerce');
								}, function () {
									$toggle.prop('checked', false);
								});
							});
							$('#mpwpb_custom_enable_toggle').on('change', function () {
								// Same: unchecking disables Custom Payment outright, it does not re-enable
								// WooCommerce.
								var $toggle = $(this);
								if (!this.checked) {
									mpwpbSyncPaymentToggles('none');
									return;
								}
								mpwpbGuardPaymentSwitch('custom', function () {
									$toggle.prop('checked', true);
									mpwpbSyncPaymentToggles('custom');
								}, function () {
									$toggle.prop('checked', false);
								});
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
								var $button = $(this);
								var $panel = $('#' + $button.data('target'));
								var willOpen = !$panel.is(':visible');
								$button.attr('aria-expanded', willOpen).toggleClass('is-open', willOpen);
								$panel.attr('aria-hidden', !willOpen).stop(true, true).slideToggle(150);
								if ($panel.hasClass('mpwpb-gw-panel')) {
									$panel.prev('.mpwpb-gateway-card').toggleClass('is-config-open', willOpen);
								}
								if ($panel.hasClass('mpwpb-wc-gw-panel')) {
									$panel.prev('.mpwpb-wc-gateway-card').toggleClass('is-config-open', willOpen);
								}
							});
							$('.mpwpb-wc-gw-save').on('click', function () {
								var $btn = $(this);
								var $panel = $btn.closest('.mpwpb-wc-gw-panel');
								var $status = $panel.find('.mpwpb-wc-gw-save-status');
								var gatewayId = $btn.data('gateway');
								var $enableToggle = $('.mpwpb-wc-gateway-toggle[data-gateway="' + gatewayId + '"]');
								var data = $panel.find('table.form-table :input').serializeArray();
								// The panel intentionally omits the gateway's own "enabled" field (we have a
								// dedicated switch for that), but process_admin_options() still expects it —
								// a HTML checkbox is only present in submitted data when checked, so mirror
								// that: include it only if the dedicated switch is currently on, otherwise
								// omit it entirely so the gateway isn't silently disabled on save.
								if ($enableToggle.is(':checked')) {
									data.push({name: $enableToggle.data('enabledField'), value: 'yes'});
								}
								data.push({name: 'action', value: 'mpwpb_save_wc_gateway_settings'});
								data.push({name: 'gateway', value: gatewayId});
								data.push({name: 'nonce', value: $btn.data('nonce')});
								$btn.prop('disabled', true);
								$status.css('color', '').text(<?php echo wp_json_encode(esc_html__('Saving…', 'service-booking-manager')); ?>);
								$.post(ajaxurl, data).done(function (resp) {
									if (resp && resp.success) {
										$status.css('color', '#1c9a5b').text(<?php echo wp_json_encode(esc_html__('Saved.', 'service-booking-manager')); ?>);
									} else {
										$status.css('color', '#b32d2e').text((resp && resp.data && resp.data.message) ? resp.data.message : <?php echo wp_json_encode(esc_html__('Something went wrong.', 'service-booking-manager')); ?>);
									}
								}).fail(function () {
									$status.css('color', '#b32d2e').text(<?php echo wp_json_encode(esc_html__('Request failed.', 'service-booking-manager')); ?>);
								}).always(function () {
									$btn.prop('disabled', false);
								});
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
			/**
			 * Its own Settings tab/option (mpwpb_partial_payment_settings) --
			 * previously an accordion tucked inside the Payment Method tab
			 * (shared mpwpb_payment_method_settings option); moved out to a
			 * dedicated tab. Applies regardless of WooCommerce vs Custom
			 * Payment -- a deposit/balance split is the same policy either way
			 * (MPWPB_Partial_Payment handles both). Existing values are copied
			 * over automatically the first time this loads (see
			 * MPWPB_Partial_Payment::maybe_migrate_settings()).
			 */
			public function render_partial_payment_panel(): void {
				$option = 'mpwpb_partial_payment_settings';
				$partial_enabled = MPWPB_Global_Function::get_partial_payment_setting('partial_payment_enabled');
				$partial_type = MPWPB_Global_Function::get_partial_payment_setting('partial_payment_type', 'percentage');
				?>
				<div class="mpwpb-settings-panel">
					<div class="mpwpb-toggle-row">
						<div>
							<strong><?php esc_html_e('Enable Partial Payment', 'service-booking-manager'); ?></strong>
							<p class="description"><?php esc_html_e('Let customers pay a deposit at checkout and the remaining balance later from My Account.', 'service-booking-manager'); ?></p>
						</div>
						<label class="mpwpb-toggle-switch mpwpb-toggle-switch-lg">
							<input type="hidden" name="<?php echo esc_attr($option); ?>[partial_payment_enabled]" value="off"/>
							<input type="checkbox" name="<?php echo esc_attr($option); ?>[partial_payment_enabled]" value="on" <?php checked($partial_enabled, 'on'); ?>/>
							<span class="mpwpb-toggle-slider"></span>
						</label>
					</div>
					<div class="mpwpb-settings-row">
						<div>
							<strong><?php esc_html_e('Deposit Type', 'service-booking-manager'); ?></strong>
							<p class="description"><?php esc_html_e('Charge a fixed amount, or a percentage of the booking total, as the deposit.', 'service-booking-manager'); ?></p>
						</div>
						<select name="<?php echo esc_attr($option); ?>[partial_payment_type]" id="mpwpb_partial_payment_type">
							<option value="percentage" <?php selected($partial_type, 'percentage'); ?>><?php esc_html_e('Percentage', 'service-booking-manager'); ?></option>
							<option value="fixed" <?php selected($partial_type, 'fixed'); ?>><?php esc_html_e('Fixed Amount', 'service-booking-manager'); ?></option>
						</select>
					</div>
					<div class="mpwpb-settings-row" id="mpwpb_partial_payment_percentage_row" style="<?php echo $partial_type === 'fixed' ? 'display:none;' : ''; ?>">
						<div>
							<strong><?php esc_html_e('Deposit Percentage', 'service-booking-manager'); ?></strong>
						</div>
						<input type="number" min="0" max="100" step="0.01" name="<?php echo esc_attr($option); ?>[partial_payment_percentage]" value="<?php echo esc_attr(MPWPB_Global_Function::get_partial_payment_setting('partial_payment_percentage', 50)); ?>"/>
					</div>
					<div class="mpwpb-settings-row" id="mpwpb_partial_payment_fixed_row" style="<?php echo $partial_type === 'fixed' ? '' : 'display:none;'; ?>">
						<div>
							<strong><?php esc_html_e('Deposit Fixed Amount', 'service-booking-manager'); ?></strong>
						</div>
						<input type="number" min="0" step="0.01" name="<?php echo esc_attr($option); ?>[partial_payment_fixed_amount]" value="<?php echo esc_attr(MPWPB_Global_Function::get_partial_payment_setting('partial_payment_fixed_amount', 0)); ?>"/>
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
							$('#mpwpb_partial_payment_type').on('change', function () {
								var isFixed = $(this).val() === 'fixed';
								$('#mpwpb_partial_payment_fixed_row').toggle(isFixed);
								$('#mpwpb_partial_payment_percentage_row').toggle(!isFixed);
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
			/**
			 * Shown only on this plugin's own admin screens (the service
			 * CPT list/edit screens and every edit.php?post_type=mpwpb_item
			 * submenu page -- Settings, Status, GDPR Tools, etc. -- all of
			 * which share that same post_type on their current screen), not
			 * site-wide on every wp-admin page.
			 */
			public function maybe_show_payment_notice(): void {
				if (!current_user_can('manage_options') || MPWPB_Global_Function::has_functional_payment_method()) {
					return;
				}
				if (!$this->is_our_admin_screen()) {
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
								__('<strong>Service Booking Manager:</strong> No payment method is currently configured. <a href="%s">Configure a payment method</a> to accept bookings.', 'service-booking-manager'),
								esc_url($url)
							)
						);
						?>
					</p>
				</div>
				<?php
			}
			/**
			 * True on the service CPT list/edit screens and every
			 * edit.php?post_type=mpwpb_item submenu page (Settings, Status,
			 * GDPR Tools, etc). Checked multiple ways -- $screen->post_type
			 * isn't reliably populated by WordPress core for every one of
			 * our custom submenu pages (only the real list/edit screens),
			 * so this also falls back to the screen id and the raw
			 * post_type query var, either of which is present on all of
			 * them regardless of what WP_Screen itself infers.
			 */
			private function is_our_admin_screen(): bool {
				$cpt = MPWPB_Function::get_cpt();
				$screen = function_exists('get_current_screen') ? get_current_screen() : null;
				if ($screen && ($screen->post_type === $cpt || strpos((string) $screen->id, $cpt) !== false)) {
					return true;
				}
				$requested_post_type = isset($_GET['post_type']) ? sanitize_key(wp_unslash($_GET['post_type'])) : '';
				return $requested_post_type === $cpt;
			}
		}
		new MPWPB_Native_Checkout_Settings();
	}
