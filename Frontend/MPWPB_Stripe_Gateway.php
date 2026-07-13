<?php
	/*
	 * Stripe Checkout (hosted redirect) integration for the native/custom
	 * payment flow. No Stripe PHP SDK — plain REST calls via wp_remote_*,
	 * matching the rest of this plugin's style. The customer is sent to a
	 * Stripe-hosted payment page and redirected back; MPWPB_Native_Checkout
	 * verifies the session server-side (both inline on return and via
	 * webhook) before ever marking a booking as paid.
	 */
	if (!defined('ABSPATH')) {
		die;
	} // Cannot access pages directly.
	if (!class_exists('MPWPB_Stripe_Gateway')) {
		class MPWPB_Stripe_Gateway {
			const API_BASE = 'https://api.stripe.com/v1';

			public static function is_configured(): bool {
				return (bool) self::get_secret_key();
			}
			public static function get_secret_key(): string {
				return (string) MPWPB_Global_Function::get_payment_setting('stripe_secret_key');
			}
			public static function get_webhook_secret(): string {
				return (string) MPWPB_Global_Function::get_payment_setting('stripe_webhook_secret');
			}

			/**
			 * @param int   $order_id  MPWPB_Native_Order post ID (already created, status pending).
			 * @param array $item      Same shape as MPWPB_Native_Cart::get_item().
			 * @param string $currency_code ISO currency code, e.g. 'USD'.
			 * @param string $success_url
			 * @param string $cancel_url
			 * @param float|null $charge_amount When given (a deposit, not the full
			 *        booking), Stripe charges exactly this instead of itemizing
			 *        real per-service prices -- those would sum to the FULL
			 *        price regardless of what's actually owed right now, so
			 *        itemizing doesn't make sense once only part of the total
			 *        is being collected.
			 * @return array{ok:bool,url?:string,session_id?:string,error?:string}
			 */
			public static function create_checkout_session($order_id, array $item, string $currency_code, string $success_url, string $cancel_url, ?float $charge_amount = null): array {
				if (!self::is_configured()) {
					return ['ok' => false, 'error' => esc_html__('Stripe is not configured. Please contact the site administrator.', 'service-booking-manager')];
				}
				$decimals = (int) MPWPB_Global_Function::native_currency_setting('decimals', 2);
				$multiplier = 10 ** max(0, $decimals);
				$currency = strtolower($currency_code);

				$body = [
					'mode' => 'payment',
					'success_url' => $success_url,
					'cancel_url' => $cancel_url,
					'client_reference_id' => (string) $order_id,
					'metadata' => ['mpwpb_order_id' => (string) $order_id],
				];

				if ($charge_amount !== null) {
					$body['line_items'][0]['quantity'] = 1;
					$body['line_items'][0]['price_data']['currency'] = $currency;
					$body['line_items'][0]['price_data']['unit_amount'] = (int) round($charge_amount * $multiplier);
					$body['line_items'][0]['price_data']['product_data']['name'] = esc_html__('Deposit Payment for Booking', 'service-booking-manager');

					$response = self::request('POST', '/checkout/sessions', $body);
					if (!$response['ok']) {
						return $response;
					}
					$session = $response['data'];
					if (empty($session['url']) || empty($session['id'])) {
						return ['ok' => false, 'error' => esc_html__('Stripe did not return a checkout URL.', 'service-booking-manager')];
					}
					return ['ok' => true, 'url' => $session['url'], 'session_id' => $session['id']];
				}

				$line_index = 0;
				foreach ((array) ($item['mpwpb_service'] ?? []) as $service) {
					$qty = max(1, (int) ($service['qty'] ?? 1));
					$unit_amount = (int) round(((float) ($service['price'] ?? 0)) * $multiplier);
					if ($unit_amount <= 0) {
						continue;
					}
					$body['line_items'][$line_index]['quantity'] = $qty;
					$body['line_items'][$line_index]['price_data']['currency'] = $currency;
					$body['line_items'][$line_index]['price_data']['unit_amount'] = $unit_amount;
					$body['line_items'][$line_index]['price_data']['product_data']['name'] = wp_strip_all_tags((string) ($service['name'] ?? esc_html__('Service', 'service-booking-manager')));
					$line_index++;
				}
				foreach ((array) ($item['mpwpb_extra_service_info'] ?? []) as $extra) {
					$qty = max(1, (int) ($extra['ex_qty'] ?? 1));
					$unit_amount = (int) round(((float) ($extra['ex_price'] ?? 0)) * $multiplier);
					if ($unit_amount <= 0) {
						continue;
					}
					$body['line_items'][$line_index]['quantity'] = $qty;
					$body['line_items'][$line_index]['price_data']['currency'] = $currency;
					$body['line_items'][$line_index]['price_data']['unit_amount'] = $unit_amount;
					$body['line_items'][$line_index]['price_data']['product_data']['name'] = wp_strip_all_tags((string) ($extra['ex_name'] ?? esc_html__('Extra Service', 'service-booking-manager')));
					$line_index++;
				}
				if ($line_index < 1) {
					// Nothing priced above zero (shouldn't normally happen) -- fall
					// back to a single line item for the order's stored total so
					// Stripe always has something to charge.
					$total = (float) ($item['mpwpb_tp'] ?? 0);
					$body['line_items'][0]['quantity'] = 1;
					$body['line_items'][0]['price_data']['currency'] = $currency;
					$body['line_items'][0]['price_data']['unit_amount'] = (int) round($total * $multiplier);
					$body['line_items'][0]['price_data']['product_data']['name'] = esc_html__('Booking', 'service-booking-manager');
				}

				$response = self::request('POST', '/checkout/sessions', $body);
				if (!$response['ok']) {
					return $response;
				}
				$session = $response['data'];
				if (empty($session['url']) || empty($session['id'])) {
					return ['ok' => false, 'error' => esc_html__('Stripe did not return a checkout URL.', 'service-booking-manager')];
				}
				return ['ok' => true, 'url' => $session['url'], 'session_id' => $session['id']];
			}

			/**
			 * @return array{ok:bool,paid?:bool,order_id?:string,error?:string}
			 */
			public static function retrieve_session($session_id): array {
				if (!self::is_configured() || !$session_id) {
					return ['ok' => false, 'error' => esc_html__('Stripe is not configured.', 'service-booking-manager')];
				}
				$response = self::request('GET', '/checkout/sessions/' . rawurlencode($session_id));
				if (!$response['ok']) {
					return $response;
				}
				$session = $response['data'];
				return [
					'ok' => true,
					'paid' => ($session['payment_status'] ?? '') === 'paid',
					'order_id' => $session['metadata']['mpwpb_order_id'] ?? ($session['client_reference_id'] ?? ''),
				];
			}

			/**
			 * Stripe's documented webhook signature scheme: the header is
			 * "t=<timestamp>,v1=<signature>[,v1=<older_signature>...]"; the
			 * expected signature is HMAC-SHA256("{$timestamp}.{$payload}", secret).
			 * A 5 minute tolerance guards against replay of an intercepted request.
			 */
			public static function verify_webhook_signature(string $payload, string $sig_header, string $secret): bool {
				if (!$secret || !$sig_header) {
					return false;
				}
				$parts = [];
				foreach (explode(',', $sig_header) as $pair) {
					$pair = explode('=', $pair, 2);
					if (count($pair) === 2) {
						$parts[trim($pair[0])][] = trim($pair[1]);
					}
				}
				$timestamp = $parts['t'][0] ?? '';
				$signatures = $parts['v1'] ?? [];
				if (!$timestamp || empty($signatures)) {
					return false;
				}
				if (abs(time() - (int) $timestamp) > 300) {
					return false;
				}
				$expected = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);
				foreach ($signatures as $signature) {
					if (hash_equals($expected, $signature)) {
						return true;
					}
				}
				return false;
			}

			/**
			 * @return array{ok:bool,data?:array,error?:string}
			 */
			private static function request(string $method, string $path, array $body = []): array {
				$args = [
					'method' => $method,
					'timeout' => 20,
					'headers' => [
						'Authorization' => 'Bearer ' . self::get_secret_key(),
						'Content-Type' => 'application/x-www-form-urlencoded',
					],
				];
				if ($method === 'POST') {
					$args['body'] = self::to_form_params($body);
				}
				$response = wp_remote_request(self::API_BASE . $path, $args);
				if (is_wp_error($response)) {
					return ['ok' => false, 'error' => $response->get_error_message()];
				}
				$code = wp_remote_retrieve_response_code($response);
				$data = json_decode(wp_remote_retrieve_body($response), true);
				if ($code < 200 || $code >= 300) {
					$message = is_array($data) ? ($data['error']['message'] ?? '') : '';
					return ['ok' => false, 'error' => $message ?: esc_html__('Stripe request failed.', 'service-booking-manager')];
				}
				return ['ok' => true, 'data' => is_array($data) ? $data : []];
			}

			/**
			 * Stripe's API expects PHP-style bracketed array keys in its
			 * form-encoded body (e.g. line_items[0][price_data][currency]) --
			 * http_build_query() already produces exactly that for nested
			 * PHP arrays.
			 */
			private static function to_form_params(array $body): string {
				return http_build_query($body, '', '&', PHP_QUERY_RFC3986);
			}
		}
	}
