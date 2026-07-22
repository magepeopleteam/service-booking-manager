<?php
	/*
   * @Author 		engr.sumonazma@gmail.com
   * Copyright: 	mage-people.com
   */
	if (!defined('ABSPATH')) {
		die;
	} // Cannot access pages directly.

	if (!class_exists('MPWPB_Tax_Helper')) {
		/**
		 * Per-service tax calculation -- works whether or not WooCommerce is
		 * even installed:
		 *  - WooCommerce active: the rate the admin types into the Tax
		 *    Settings card (Admin/settings/Tax_Settings.php) is pushed into a
		 *    real WooCommerce tax rate (sync_wc_tax_rate() below) and
		 *    woocommerce_calc_taxes is turned on automatically -- the admin
		 *    never has to visit WooCommerce > Settings > Tax. WooCommerce-mode
		 *    checkout then needs nothing further from this class at all: its
		 *    own cart/checkout tax engine already applies tax automatically to
		 *    any product with the right _tax_status/_tax_class (synced onto
		 *    the hidden product by Tax_Settings::sync_hidden_product_tax()).
		 *  - WooCommerce inactive/not installed: calculate() falls back to a
		 *    plain percentage of the amount using the same stored rate --
		 *    no WC_Tax dependency at all.
		 * Custom Payment mode always calls calculate() directly (there's no WC
		 * cart to lean on either way); WooCommerce mode never needs to.
		 */
		class MPWPB_Tax_Helper {
			const RATE_ID_OPTION = 'mpwpb_tax_class_rate_ids';

			public static function is_enabled_for_service($service_post_id): bool {
				return get_post_meta($service_post_id, 'mpwpb_tax_enabled', true) === 'on';
			}

			public static function get_tax_class($service_post_id): string {
				return (string) get_post_meta($service_post_id, 'mpwpb_tax_class', true);
			}

			public static function get_tax_rate($service_post_id): float {
				return (float) get_post_meta($service_post_id, 'mpwpb_tax_rate', true);
			}

			/**
			 * The built-in tax class choices, available with or without
			 * WooCommerce -- when WC is active this also picks up any
			 * additional classes the admin has defined in WooCommerce's own
			 * Settings > Tax > "Additional tax classes".
			 */
			public static function get_tax_class_options(): array {
				if (function_exists('wc_get_product_tax_class_options')) {
					return wc_get_product_tax_class_options();
				}
				return [
					'' => __('Standard', 'service-booking-manager'),
					'reduced-rate' => __('Reduced Rate', 'service-booking-manager'),
					'zero-rate' => __('Zero Rate', 'service-booking-manager'),
				];
			}

			/**
			 * Pushes the admin's typed rate into a real WooCommerce tax rate
			 * and turns woocommerce_calc_taxes on -- so enabling tax here is
			 * enough by itself, no trip to WooCommerce's own settings screen.
			 * A single global rate per tax class (no country/state) is
			 * created/updated, tracked in the RATE_ID_OPTION map so saving
			 * again updates that same row instead of creating duplicates.
			 * Every service sharing a tax class shares its one rate, same as
			 * WooCommerce itself works for real products -- pick a different
			 * class for services that need a different percentage.
			 * No-ops entirely when WooCommerce isn't active.
			 */
			public static function sync_wc_tax_rate(string $tax_class, float $rate): void {
				if (!class_exists('WC_Tax')) {
					return;
				}
				if (get_option('woocommerce_calc_taxes') !== 'yes') {
					update_option('woocommerce_calc_taxes', 'yes');
				}
				$rate_ids = get_option(self::RATE_ID_OPTION, []);
				$rate_ids = is_array($rate_ids) ? $rate_ids : [];
				$rate_row = [
					'tax_rate_country' => '',
					'tax_rate_state' => '',
					'tax_rate' => number_format($rate, 4, '.', ''),
					'tax_rate_name' => $tax_class !== '' ? $tax_class : __('Tax', 'service-booking-manager'),
					'tax_rate_priority' => 1,
					'tax_rate_compound' => 0,
					'tax_rate_shipping' => 1,
					'tax_rate_order' => 1,
					'tax_rate_class' => $tax_class,
				];
				$existing_id = $rate_ids[$tax_class] ?? 0;
				if ($existing_id && WC_Tax::_get_tax_rate($existing_id)) {
					WC_Tax::_update_tax_rate($existing_id, $rate_row);
				} else {
					$rate_ids[$tax_class] = WC_Tax::_insert_tax_rate($rate_row);
					update_option(self::RATE_ID_OPTION, $rate_ids);
				}
			}

			/**
			 * @param float  $amount   The service's own price, entered the way
			 *                         WooCommerce's "Prices entered with tax"
			 *                         setting says prices are entered on this
			 *                         site (checked dynamically below via
			 *                         wc_prices_include_tax() -- not assumed;
			 *                         irrelevant in the no-WooCommerce fallback,
			 *                         which always treats the price as exclusive).
			 * @param string $country  ISO country code (e.g. "US"). Empty falls
			 *                         back to the shop's own base location, same
			 *                         as WooCommerce does when it doesn't know a
			 *                         customer's address yet. Ignored entirely by
			 *                         the no-WooCommerce flat-rate fallback.
			 * @param string $state    State/province code.
			 * @param string $postcode Postal/ZIP code.
			 * @return float Tax amount, 0 if tax isn't enabled/no matching rate.
			 */
			public static function calculate($service_post_id, $amount, $country = '', $state = '', $postcode = ''): float {
				$amount = (float) $amount;
				if ($amount <= 0 || !self::is_enabled_for_service($service_post_id)) {
					return 0.0;
				}
				if (!class_exists('WC_Tax') || !function_exists('wc_tax_enabled') || !wc_tax_enabled()) {
					// No WooCommerce (or its tax engine isn't actually usable) --
					// plain percentage, no location matching needed.
					return round($amount * (self::get_tax_rate($service_post_id) / 100), 2);
				}
				if ($country === '') {
					// No address known yet (e.g. live recap preview before the
					// customer has typed anything) -- fall back to the shop's own
					// base location, the same default WC_Customer itself uses.
					$country = WC()->countries->get_base_country();
					$state = WC()->countries->get_base_state();
					$postcode = WC()->countries->get_base_postcode();
				}
				$rates = WC_Tax::find_rates([
					'country' => $country,
					'state' => $state,
					'postcode' => $postcode,
					'city' => '',
					'tax_class' => self::get_tax_class($service_post_id),
				]);
				if (empty($rates)) {
					// Tax class has no matching WC rate (e.g. sync_wc_tax_rate()
					// never ran for it) -- fall back to the flat percentage rather
					// than silently charging no tax at all.
					return round($amount * (self::get_tax_rate($service_post_id) / 100), 2);
				}
				$price_includes_tax = function_exists('wc_prices_include_tax') && wc_prices_include_tax();
				$taxes = WC_Tax::calc_tax($amount, $rates, $price_includes_tax);
				return round(array_sum($taxes), 2);
			}
		}
	}
