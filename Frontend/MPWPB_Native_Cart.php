<?php
	/*
	 * Native (non-WooCommerce) single-item cart.
	 * Mirrors WC()->cart usage in MPWPB_Woocommerce (which always calls
	 * empty_cart() before add_to_cart()) — holds exactly one pending
	 * booking selection between the wizard and the native checkout page.
	 */
	if (!defined('ABSPATH')) {
		die;
	} // Cannot access pages directly.
	if (!class_exists('MPWPB_Native_Cart')) {
		class MPWPB_Native_Cart {
			const COOKIE_NAME = 'mpwpb_cart_token';
			const TTL = 7200; // 2 hours

			public static function get_token($create = false) {
				if (!empty($_COOKIE[self::COOKIE_NAME])) {
					return preg_replace('/[^a-zA-Z0-9]/', '', wp_unslash($_COOKIE[self::COOKIE_NAME]));
				}
				if (!$create) {
					return '';
				}
				$token = wp_generate_password(32, false, false);
				if (!headers_sent()) {
					setcookie(self::COOKIE_NAME, $token, [
						'expires' => time() + self::TTL,
						'path' => defined('COOKIEPATH') && COOKIEPATH ? COOKIEPATH : '/',
						'domain' => defined('COOKIE_DOMAIN') ? COOKIE_DOMAIN : '',
						'secure' => is_ssl(),
						'httponly' => true,
						'samesite' => 'Lax',
					]);
				}
				$_COOKIE[self::COOKIE_NAME] = $token;
				return $token;
			}
			private static function transient_key($token): string {
				return 'mpwpb_cart_' . $token;
			}
			public static function set_item(array $item): string {
				$token = self::get_token(true);
				set_transient(self::transient_key($token), $item, self::TTL);
				return $token;
			}
			public static function get_item(): array {
				$token = self::get_token(false);
				if (!$token) {
					return [];
				}
				$item = get_transient(self::transient_key($token));
				return is_array($item) ? $item : [];
			}
			public static function clear(): void {
				$token = self::get_token(false);
				if ($token) {
					delete_transient(self::transient_key($token));
				}
			}
		}
	}
