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
				// Two-paragraph copy for the "switch payment method" confirm modal,
				// one set per direction. Built server-side (not just a plain string)
				// so the method names can render as <strong> without re-escaping HTML
				// that isn't there — every piece going into sprintf() is already run
				// through esc_html__() first.
				$wc_name = '<strong>' . esc_html__('WooCommerce', 'service-booking-manager') . '</strong>';
				$custom_name = '<strong>' . esc_html__('Custom Payment', 'service-booking-manager') . '</strong>';
				$pm_confirm_copy = [
					'woocommerce' => [
						'intro' => sprintf(
							/* translators: %s: the payment method currently active */
							esc_html__('You currently have %s active. Adding another provider is not supported in your current configuration.', 'service-booking-manager'),
							$custom_name
						),
						'question' => sprintf(
							/* translators: 1: method to disable, 2: method to activate */
							esc_html__('Do you want to disable %1$s and activate %2$s instead?', 'service-booking-manager'),
							$custom_name,
							$wc_name
						),
					],
					'custom' => [
						'intro' => sprintf(
							esc_html__('You currently have %s active. Adding another provider is not supported in your current configuration.', 'service-booking-manager'),
							$wc_name
						),
						'question' => sprintf(
							esc_html__('Do you want to disable %1$s and activate %2$s instead?', 'service-booking-manager'),
							$wc_name,
							$custom_name
						),
					],
				];
				?>
				<style>
					.mpwpb-pm-toggle { display: inline-flex; border: 1px solid #dcdcde; border-radius: 6px; overflow: hidden; margin-bottom: 20px; }
					.mpwpb-pm-toggle-btn { border: none; background: #fff; padding: 10px 22px; font-weight: 600; cursor: pointer; color: #1d2327; }
					.mpwpb-pm-toggle-btn.is-active { background: #6366f1; color: #fff; }
					.mpwpb-pm-panel { border: 1px solid #dcdcde; border-radius: 8px; padding: 20px; background: #fff; }
					.mpwpb-custom-dependent.mpwpb-locked { opacity: .5; pointer-events: none; user-select: none; }
					.mpwpb-pro-badge { display: inline-block; background: linear-gradient(135deg,#f7b733,#fc4a1a); color: #fff; font-size: 10px; font-weight: 700; letter-spacing: .5px; border-radius: 999px; padding: 2px 8px; margin-left: 6px; vertical-align: middle; }
					.mpwpb-toggle-switch input:disabled + .mpwpb-toggle-slider { cursor: not-allowed; opacity: .5; }
					.mpwpb-pm-notice { border-radius: 6px; padding: 14px 16px; }
					.mpwpb-pm-notice-warning { background: #fff6dd; border: 1px solid #f0dfa6; }
					.mpwpb-pm-notice-success { background: #eaf7ee; border: 1px solid #b7e3c4; }
					.mpwpb-pm-notice p { margin: 6px 0 12px; }
					.mpwpb-pm-btn-primary { background: #6366f1; color: #fff; border: none; border-radius: 5px; padding: 9px 18px; font-weight: 600; cursor: pointer; }
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
					.mpwpb-gw-panel label.mpwpb-gw-field { display: flex; align-items: flex-start; gap: 14px; padding-top: 6px; }
					.mpwpb-gw-panel .mpwpb-gw-field-label { flex: 0 0 160px; }
					.mpwpb-gw-panel .mpwpb-gw-field-control { flex: 1 1 auto; }
					.mpwpb-gw-panel .mpwpb-gw-field-control input[type=text], .mpwpb-gw-panel .mpwpb-gw-field-control input[type=password], .mpwpb-gw-panel .mpwpb-gw-field-control select, .mpwpb-gw-panel .mpwpb-gw-field-control textarea { margin-top: 0; }
					.mpwpb-toggle-switch { position: relative; display: inline-block; width: 40px; height: 22px; vertical-align: middle; }
					.mpwpb-toggle-switch input { opacity: 0; width: 0; height: 0; }
					.mpwpb-toggle-slider { position: absolute; inset: 0; background: rgba(255,255,255,.35); border-radius: 999px; transition: .15s; cursor: pointer; }
					.mpwpb-toggle-slider:before { content: ""; position: absolute; height: 16px; width: 16px; left: 3px; top: 3px; background: #fff; border-radius: 50%; transition: .15s; }
					.mpwpb-toggle-switch input:checked + .mpwpb-toggle-slider { background: #1c9a5b; }
					.mpwpb-toggle-switch input:checked + .mpwpb-toggle-slider:before { transform: translateX(18px); }
					.mpwpb-confirmation-row { display: flex; align-items: center; justify-content: space-between; gap: 20px; margin-top: 18px; padding-top: 18px; border-top: 1px solid #dcdcde; }
					.mpwpb-confirmation-row .description { color: #646970; margin: 4px 0 0; }
					.mpwpb-toggle-row { display: flex; align-items: center; justify-content: space-between; gap: 14px; padding-bottom: 16px; margin-bottom: 16px; border-bottom: 1px solid #dcdcde; }
					.mpwpb-toggle-row .description { color: #646970; }
					.mpwpb-toggle-switch-lg .mpwpb-toggle-slider { background: rgba(0,0,0,.15); }
					.mpwpb-toggle-switch-lg input:checked + .mpwpb-toggle-slider { background: #6366f1; }
					.mpwpb-accordion { border: 1px solid #dcdcde; border-radius: 6px; margin-bottom: 14px; overflow: hidden; }
					.mpwpb-accordion-header { display: flex; align-items: center; justify-content: space-between; width: 100%; text-align: left; background: #f6f7f7; border: none; padding: 12px 16px; font-weight: 600; cursor: pointer; }
					.mpwpb-accordion-header.is-open { background: #eaf1ff; color: #6366f1; }
					button.mpwpb-accordion-header { justify-content: left; }
					.mpwpb-accordion mpwpb span.fas.fa-chevron-down { margin-left: 20px; }
					span.fas.fa-chevron-down { margin-left: 10px; }
					.mpwpb-accordion-body { padding: 16px; border-top: 1px solid #dcdcde; }
					.mpwpb-accordion-body p { margin-bottom: 5px !important; }
					.mpwpb-pm-btn-outline { display: inline-block; border: 1px solid #6366f1; color: #6366f1; background: #fff; border-radius: 5px; padding: 6px 14px; text-decoration: none; font-size: 13px; font-weight: 600; }
					.mpwpb-wc-gateway-card { border: 1px solid #dcdcde; border-radius: 6px; padding: 14px 16px; margin-bottom: 10px; display: grid; grid-template-columns: auto auto 1fr auto; align-items: center; gap: 14px; }
					.mpwpb-wc-gateway-name { font-weight: 600; }
					.mpwpb-wc-gateway-status { border-radius: 999px; padding: 2px 10px; font-size: 11px; font-weight: 700; background: #fdecea; color: #b32d2e; justify-self: end; }
					.mpwpb-wc-gateway-status.is-enabled { background: #eaf7ee; color: #1c9a5b; }
					.mpwpb-wc-gateway-desc { grid-column: 1 / -1; color: #646970; font-size: 13px; }
					.mpwpb-wc-gw-panel { border: 1px solid #dcdcde; border-top: none; border-radius: 0 0 8px 8px; padding: 16px 18px; margin: -10px 0 10px; background: #fafafa; }
					.mpwpb-wc-gw-panel .form-table th { width: 220px; }
					.mpwpb-wc-gw-panel .form-table input[type=text], .mpwpb-wc-gw-panel .form-table input[type=password], .mpwpb-wc-gw-panel .form-table select, .mpwpb-wc-gw-panel .form-table textarea { width: 100%; max-width: 420px; }
					.mpwpb-settings-row { display: flex; align-items: center; justify-content: space-between; gap: 20px; padding: 14px 0; border-top: 1px solid #eee; }
					.mpwpb-settings-row:first-child { border-top: none; padding-top: 0; }
					.mpwpb-settings-row .description { color: #646970; margin: 4px 0 0; }
					.mpwpb-settings-row.mpwpb-settings-row-top { align-items: flex-start; }
					.mpwpb-settings-row.mpwpb-settings-row-top > div:first-child { padding-top: 2px; }
					.mpwpb-settings-row label { display: flex; align-items: center; gap: 6px; font-weight: 400; }
					.mpwpb-pm-confirm-overlay { position: fixed; inset: 0; background: rgba(15,23,42,.55); z-index: 100000; display: none; align-items: center; justify-content: center; padding: 20px; }
					.mpwpb-pm-confirm-box { position: relative; background: #fff; border-radius: 12px; padding: 26px 26px 22px; max-width: 440px; width: 100%; box-shadow: 0 24px 60px rgba(15,23,42,.35); overflow: hidden; }
					.mpwpb-pm-confirm-box::before { content: ""; position: absolute; top: 0; left: 0; right: 0; height: 4px; background: linear-gradient(90deg,#6366f1,#8b8ff5); }
					.mpwpb-pm-confirm-head { display: flex; align-items: flex-start; gap: 14px; margin-bottom: 14px; }
					.mpwpb-pm-confirm-icon { flex: 0 0 auto !important; width: 38px !important; height: 38px !important; border-radius: 50% !important; background: #fff6dd !important; display: flex !important; align-items: center !important; justify-content: center !important; color: #dba617 !important; font-size: 16px !important; }
					.mpwpb-pm-confirm-title { margin: 0 !important; padding-top: 6px !important; font-size: 18px !important; font-weight: 700 !important; color: #1d2327 !important; line-height: 1.35 !important; }
					.mpwpb-pm-confirm-body p { margin: 0 0 10px; font-size: 13.5px; color: #50575e; line-height: 1.6; }
					.mpwpb-pm-confirm-body p:last-child { margin-bottom: 0; }
					.mpwpb-pm-confirm-body strong { color: #1d2327; font-weight: 700; }
					.mpwpb-pm-confirm-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 22px; }
					.mpwpb-pm-confirm-actions button { border-radius: 7px; padding: 9px 20px; font-size: 13.5px; font-weight: 600; cursor: pointer; border: none; }
					.mpwpb-pm-confirm-actions .mpwpb-pm-btn-outline { background: #f1f5f9; color: #334155; border: 1px solid #e2e8f0; }
					.mpwpb-pm-confirm-actions .mpwpb-pm-btn-outline:hover { background: #e2e8f0; }
					.mpwpb-pm-confirm-actions .mpwpb-pm-btn-primary { background: #6366f1; color: #fff; }
					.mpwpb-pm-confirm-actions .mpwpb-pm-btn-primary:hover { background: #4f46e5; }
					.mpwpb-settings-row label[style*="display:block"] { display: flex !important; margin-bottom: 8px; }
					.mpwpb-settings-row label[style*="display:block"]:last-child { margin-bottom: 0; }
				</style>
				<div class="mpwpb-pm-toggle">
					<button type="button" class="mpwpb-pm-toggle-btn <?php echo $payment_type !== 'custom' ? 'is-active' : ''; ?>" data-value="woocommerce"><?php esc_html_e('WooCommerce', 'service-booking-manager'); ?></button>
					<button type="button" class="mpwpb-pm-toggle-btn <?php echo $payment_type === 'custom' ? 'is-active' : ''; ?>" data-value="custom"><?php esc_html_e('Custom Payment', 'service-booking-manager'); ?></button>
				</div>
				<input type="hidden" name="<?php echo esc_attr($option); ?>[payment_method_type]" id="mpwpb_payment_method_type_input" value="<?php echo esc_attr($payment_type ?: 'custom'); ?>"/>

				<div id="mpwpb-pm-confirm-modal" class="mpwpb-pm-confirm-overlay">
					<div class="mpwpb-pm-confirm-box" role="alertdialog" aria-modal="true" aria-labelledby="mpwpb-pm-confirm-title">
						<div class="mpwpb-pm-confirm-head">
							<span class="mpwpb-pm-confirm-icon"><span class="fas fa-triangle-exclamation"></span></span>
							<h3 class="mpwpb-pm-confirm-title" id="mpwpb-pm-confirm-title"><?php esc_html_e('Only One Payment Method Allowed', 'service-booking-manager'); ?></h3>
						</div>
						<div class="mpwpb-pm-confirm-body" id="mpwpb-pm-confirm-text"></div>
						<div class="mpwpb-pm-confirm-actions">
							<button type="button" class="mpwpb-pm-btn-outline" data-mpwpb-confirm-cancel><?php esc_html_e('Cancel', 'service-booking-manager'); ?></button>
							<button type="button" class="mpwpb-pm-btn-primary" data-mpwpb-confirm-ok><?php esc_html_e('Yes, Switch', 'service-booking-manager'); ?></button>
						</div>
					</div>
				</div>

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
										<button type="button" class="mpwpb-pm-btn-outline mpwpb-gateway-configure" data-target="<?php echo esc_attr($config_panel_id); ?>"><?php esc_html_e('Configure', 'service-booking-manager'); ?></button>
										<div class="mpwpb-wc-gateway-desc"><?php echo wp_kses_post($wc_gateway->get_method_description()); ?></div>
									</div>
									<div class="mpwpb-wc-gw-panel" id="<?php echo esc_attr($config_panel_id); ?>" style="display:none;">
										<table class="form-table">
											<?php echo $wc_gateway->generate_settings_html($config_fields, false); ?>
										</table>
										<p>
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

				<div class="mpwpb-pm-panel" data-panel="custom" style="<?php echo $payment_type === 'custom' ? '' : 'display:none;'; ?>">
					<div class="mpwpb-toggle-row">
						<div>
							<strong>
								<?php esc_html_e('Enable Custom Payment Method', 'service-booking-manager'); ?>
								<?php if (!$pro_active) : ?>
									<span class="mpwpb-pro-badge"><?php esc_html_e('PRO', 'service-booking-manager'); ?></span>
								<?php endif; ?>
							</strong>
							<p class="description">
								<?php if ($pro_active) : ?>
									<?php esc_html_e('If enabled, the custom payment gateways below (PayPal, Stripe, Offline) will be used for checkout.', 'service-booking-manager'); ?>
								<?php else : ?>
									<?php esc_html_e('Requires the service-booking-manager-pro plugin to be installed and activated.', 'service-booking-manager'); ?>
								<?php endif; ?>
							</p>
						</div>
						<label class="mpwpb-toggle-switch mpwpb-toggle-switch-lg">
							<input type="checkbox" id="mpwpb_custom_enable_toggle" <?php disabled(!$pro_active); ?> <?php checked($payment_type === 'custom'); ?>/>
							<span class="mpwpb-toggle-slider"></span>
						</label>
					</div>

					<div class="mpwpb-custom-dependent <?php echo ($payment_type === 'custom' && $pro_active) ? '' : 'mpwpb-locked'; ?>">
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
								<span class="mpwpb-toggle-switch mpwpb-toggle-switch-lg">
									<input type="checkbox" class="mpwpb-gw-enable-toggle" data-status-for="<?php echo esc_attr($key); ?>" name="<?php echo esc_attr($option); ?>[<?php echo esc_attr($key); ?>_enabled]" value="on" <?php checked($enabled); ?>/>
									<span class="mpwpb-toggle-slider"></span>
								</span>
								&nbsp;<?php esc_html_e('Enable this payment method', 'service-booking-manager'); ?>
							</label>
							<?php if ($key === 'offline') : ?>
								<label class="mpwpb-gw-field">
									<span class="mpwpb-gw-field-label"><?php esc_html_e('Instructions shown to the customer at checkout', 'service-booking-manager'); ?></span>
									<span class="mpwpb-gw-field-control">
										<textarea rows="3" name="<?php echo esc_attr($option); ?>[offline_instructions]"><?php echo esc_textarea(MPWPB_Global_Function::get_payment_setting('offline_instructions', esc_html__('Please pay via bank transfer. We will confirm your booking once payment is received.', 'service-booking-manager'))); ?></textarea>
									</span>
								</label>
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
								<p class="description">
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
								<p class="description"><?php esc_html_e('Customers approve payment on a PayPal-hosted page and are redirected back once done. Use your PayPal app\'s Client ID/Secret for the mode selected above (sandbox app credentials while testing, live app credentials when ready to accept real payments).', 'service-booking-manager'); ?></p>
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
				<div class="justifyBetween _mT">
					<div></div>
					<?php submit_button(); ?>
				</div>
				<script>
					(function ($) {
						"use strict";
						$(document).ready(function () {
							var mpwpbProActive = <?php echo $pro_active ? 'true' : 'false'; ?>;
							// Single source of truth for both "which gateway is enabled" toggles
							// (WooCommerce tab's and Custom tab's) plus their dependent sections.
							// Value is one of 'woocommerce', 'custom', or 'none' — both switches can
							// be off at once, but turning one on always turns the other off, so at
							// most one is ever active. Custom is a Pro feature: silently refuses to
							// activate it without Pro (mirrored by a real server-side gate in
							// MPWPB_Global_Function::is_custom_payment_mode(), this is just so the
							// UI doesn't show a state it won't actually run in).
							function mpwpbSyncPaymentToggles(value) {
								if (value === 'custom' && !mpwpbProActive) {
									value = 'none';
								}
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
							var $mpwpbConfirmModal = $('#mpwpb-pm-confirm-modal');
							function mpwpbOpenConfirmModal(copy, onConfirm) {
								// copy.intro/copy.question are built server-side from esc_html__()
								// pieces only (see render_payment_method_panel()'s $pm_confirm_copy) --
								// safe to inject as HTML so the method names can render as <strong>.
								$mpwpbConfirmModal.find('#mpwpb-pm-confirm-text').html(
									'<p>' + copy.intro + '</p><p>' + copy.question + '</p>'
								);
								$mpwpbConfirmModal.css('display', 'flex');
								// Rebound on every open (not delegated once at ready) so each call's
								// onConfirm closure is the only one wired up -- avoids stacking
								// duplicate handlers from earlier opens.
								$mpwpbConfirmModal.find('[data-mpwpb-confirm-ok]').off('click').on('click', function () {
									mpwpbCloseConfirmModal();
									onConfirm();
								});
								$mpwpbConfirmModal.find('[data-mpwpb-confirm-cancel]').off('click').on('click', mpwpbCloseConfirmModal);
							}
							function mpwpbCloseConfirmModal() {
								$mpwpbConfirmModal.hide();
							}
							$mpwpbConfirmModal.on('click', function (e) {
								if (e.target === this) {
									mpwpbCloseConfirmModal();
								}
							});
							$(document).on('keydown', function (e) {
								if (e.key === 'Escape' && $mpwpbConfirmModal.is(':visible')) {
									mpwpbCloseConfirmModal();
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
								$('#' + $(this).data('target')).slideToggle(150);
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
