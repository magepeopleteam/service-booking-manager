<?php
	/*
	 * PayPal Orders v2 (hosted redirect / "PayPal Checkout") integration for
	 * the native/custom payment flow. No PayPal SDK — plain REST calls via
	 * wp_remote_*. The customer approves the payment on paypal.com and is
	 * redirected back; MPWPB_Native_Checkout captures the order server-side
	 * on return before marking a booking as paid.
	 */
	if (!defined('ABSPATH')) {
		die;
	} // Cannot access pages directly.
	if (!class_exists('MPWPB_Paypal_Gateway')) {
		class MPWPB_Paypal_Gateway {
			public static function is_configured(): bool {
				return (bool) MPWPB_Global_Function::get_payment_setting('paypal_client_id')
					&& (bool) MPWPB_Global_Function::get_payment_setting('paypal_client_secret');
			}
			private static function get_api_base(): string {
				$mode = MPWPB_Global_Function::get_payment_setting('paypal_mode', 'sandbox');
				return $mode === 'live' ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com';
			}
			/**
			 * OAuth2 client-credentials token, cached for its own lifetime
			 * (minus a minute of safety margin) so repeat calls in the same
			 * checkout don't each spend a round trip getting a fresh one.
			 */
			private static function get_access_token(): array {
				$mode = MPWPB_Global_Function::get_payment_setting('paypal_mode', 'sandbox');
				$transient_key = 'mpwpb_paypal_token_' . $mode;
				$cached = get_transient($transient_key);
				if ($cached) {
					return ['ok' => true, 'token' => $cached];
				}
				$client_id = MPWPB_Global_Function::get_payment_setting('paypal_client_id');
				$client_secret = MPWPB_Global_Function::get_payment_setting('paypal_client_secret');
				if (!$client_id || !$client_secret) {
					return ['ok' => false, 'error' => esc_html__('PayPal is not configured. Please contact the site administrator.', 'service-booking-manager')];
				}
				$response = wp_remote_post(self::get_api_base() . '/v1/oauth2/token', [
					'timeout' => 20,
					'headers' => [
						'Authorization' => 'Basic ' . base64_encode($client_id . ':' . $client_secret),
						'Content-Type' => 'application/x-www-form-urlencoded',
					],
					'body' => ['grant_type' => 'client_credentials'],
				]);
				if (is_wp_error($response)) {
					return ['ok' => false, 'error' => $response->get_error_message()];
				}
				$code = wp_remote_retrieve_response_code($response);
				$data = json_decode(wp_remote_retrieve_body($response), true);
				if ($code < 200 || $code >= 300 || empty($data['access_token'])) {
					return ['ok' => false, 'error' => esc_html__('Could not authenticate with PayPal.', 'service-booking-manager')];
				}
				$ttl = max(60, ((int) ($data['expires_in'] ?? 300)) - 60);
				set_transient($transient_key, $data['access_token'], $ttl);
				return ['ok' => true, 'token' => $data['access_token']];
			}

			/**
			 * @param int    $order_id      MPWPB_Native_Order post ID (already created, status pending).
			 * @param array  $item          Same shape as MPWPB_Native_Cart::get_item().
			 * @param string $currency_code ISO currency code, e.g. 'USD'.
			 * @param float|null $charge_amount When given (a deposit, not the full
			 *        booking total), PayPal charges exactly this amount instead.
			 * @return array{ok:bool,approve_url?:string,paypal_order_id?:string,error?:string}
			 */
			public static function create_order($order_id, array $item, string $currency_code, string $return_url, string $cancel_url, ?float $charge_amount = null): array {
				$auth = self::get_access_token();
				if (!$auth['ok']) {
					return $auth;
				}
				$decimals = (int) MPWPB_Global_Function::native_currency_setting('decimals', 2);
				$total = $charge_amount ?? (float) ($item['mpwpb_tp'] ?? 0);
				$body = [
					'intent' => 'CAPTURE',
					'purchase_units' => [
						[
							'custom_id' => (string) $order_id,
							'invoice_id' => 'mpwpb-' . $order_id . '-' . time(),
							'amount' => [
								'currency_code' => strtoupper($currency_code),
								'value' => number_format($total, $decimals, '.', ''),
							],
						],
					],
					'application_context' => [
						'brand_name' => wp_strip_all_tags(get_bloginfo('name')),
						'user_action' => 'PAY_NOW',
						'return_url' => $return_url,
						'cancel_url' => $cancel_url,
					],
				];
				$response = wp_remote_post(self::get_api_base() . '/v2/checkout/orders', [
					'timeout' => 20,
					'headers' => [
						'Authorization' => 'Bearer ' . $auth['token'],
						'Content-Type' => 'application/json',
					],
					'body' => wp_json_encode($body),
				]);
				if (is_wp_error($response)) {
					return ['ok' => false, 'error' => $response->get_error_message()];
				}
				$code = wp_remote_retrieve_response_code($response);
				$data = json_decode(wp_remote_retrieve_body($response), true);
				if ($code < 200 || $code >= 300 || empty($data['id'])) {
					$message = is_array($data) ? ($data['message'] ?? '') : '';
					return ['ok' => false, 'error' => $message ?: esc_html__('PayPal order creation failed.', 'service-booking-manager')];
				}
				$approve_url = '';
				foreach ((array) ($data['links'] ?? []) as $link) {
					if (($link['rel'] ?? '') === 'approve') {
						$approve_url = $link['href'];
						break;
					}
				}
				if (!$approve_url) {
					return ['ok' => false, 'error' => esc_html__('PayPal did not return an approval link.', 'service-booking-manager')];
				}
				return ['ok' => true, 'approve_url' => $approve_url, 'paypal_order_id' => $data['id']];
			}

			/**
			 * @return array{ok:bool,captured?:bool,order_id?:string,error?:string}
			 */
			public static function capture_order($paypal_order_id): array {
				if (!$paypal_order_id) {
					return ['ok' => false, 'error' => esc_html__('Missing PayPal order reference.', 'service-booking-manager')];
				}
				$auth = self::get_access_token();
				if (!$auth['ok']) {
					return $auth;
				}
				$response = wp_remote_post(self::get_api_base() . '/v2/checkout/orders/' . rawurlencode($paypal_order_id) . '/capture', [
					'timeout' => 20,
					'headers' => [
						'Authorization' => 'Bearer ' . $auth['token'],
						'Content-Type' => 'application/json',
					],
					'body' => '{}',
				]);
				if (is_wp_error($response)) {
					return ['ok' => false, 'error' => $response->get_error_message()];
				}
				$code = wp_remote_retrieve_response_code($response);
				$data = json_decode(wp_remote_retrieve_body($response), true);
				// 422 with ORDER_ALREADY_CAPTURED means a previous attempt (e.g. the
				// customer refreshing the return page) already completed this --
				// treat as success rather than an error, same end state either way.
				// That error response has no purchase_units payload, so re-fetch
				// the order itself to confirm status/custom_id instead of trusting
				// the error body's shape.
				$already_captured = $code === 422 && is_array($data)
					&& (($data['details'][0]['issue'] ?? '') === 'ORDER_ALREADY_CAPTURED');
				if ($already_captured) {
					return self::get_order($paypal_order_id, $auth['token']);
				}
				if ($code < 200 || $code >= 300) {
					$message = is_array($data) ? ($data['message'] ?? '') : '';
					return ['ok' => false, 'error' => $message ?: esc_html__('PayPal capture failed.', 'service-booking-manager')];
				}
				$status = $data['status'] ?? '';
				$purchase_unit = $data['purchase_units'][0] ?? [];
				$custom_id = $purchase_unit['payments']['captures'][0]['custom_id'] ?? ($purchase_unit['custom_id'] ?? '');
				return [
					'ok' => true,
					'captured' => $status === 'COMPLETED',
					'order_id' => $custom_id,
				];
			}
			/**
			 * @return array{ok:bool,captured?:bool,order_id?:string,error?:string}
			 */
			private static function get_order($paypal_order_id, string $token): array {
				$response = wp_remote_get(self::get_api_base() . '/v2/checkout/orders/' . rawurlencode($paypal_order_id), [
					'timeout' => 20,
					'headers' => ['Authorization' => 'Bearer ' . $token],
				]);
				if (is_wp_error($response)) {
					return ['ok' => false, 'error' => $response->get_error_message()];
				}
				$code = wp_remote_retrieve_response_code($response);
				$data = json_decode(wp_remote_retrieve_body($response), true);
				if ($code < 200 || $code >= 300) {
					return ['ok' => false, 'error' => esc_html__('Could not confirm PayPal order status.', 'service-booking-manager')];
				}
				$status = $data['status'] ?? '';
				$purchase_unit = $data['purchase_units'][0] ?? [];
				$custom_id = $purchase_unit['payments']['captures'][0]['custom_id'] ?? ($purchase_unit['custom_id'] ?? '');
				return ['ok' => true, 'captured' => $status === 'COMPLETED', 'order_id' => $custom_id];
			}
		}
	}
