<?php
	/*
   * @Author 		engr.sumonazma@gmail.com
   * Copyright: 	mage-people.com
   */
	if (!defined('ABSPATH')) {
		die;
	} // Cannot access pages directly.
	if (!class_exists('MPWPB_Coupon_Frontend')) {
		class MPWPB_Coupon_Frontend {
			public function __construct() {
				add_action('wp_ajax_mpwpb_apply_coupon', [$this, 'apply_coupon']);
				add_action('wp_ajax_nopriv_mpwpb_apply_coupon', [$this, 'apply_coupon']);
				add_action('wp_ajax_mpwpb_remove_coupon', [$this, 'remove_coupon']);
				add_action('wp_ajax_nopriv_mpwpb_remove_coupon', [$this, 'remove_coupon']);
				// This action/hook only exists in this plugin's own checkout
				// template override and only ever fires from real WooCommerce
				// checkout rendering -- self-gating, no is_wc_payment_mode()
				// guard needed at registration time (checking that here, at
				// construct time, would be the same load-order bug documented
				// for MPWPB_Marketing/MPWPB_Coupon_Fields in the previous
				// coupon feature: WooCommerce may not be loaded yet).
				add_action('woocommerce_checkout_before_order_review_heading', [$this, 'render_wc_coupon_box']);
				// Hardening: re-validate a still-applied coupon right before a WC
				// order is actually placed (mirrors the equivalent re-check added
				// to MPWPB_Native_Checkout::handle_checkout_submit()).
				add_action('mpwpb_validate_cart_item', [$this, 'revalidate_at_wc_checkout'], 10, 2);
				add_action('woocommerce_blocks_loaded', [$this, 'register_store_api_extension']);
				add_action('wp_enqueue_scripts', [$this, 'enqueue_checkout_block_assets']);
			}

			public function apply_coupon(): void {
				if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'mpwpb_nonce')) {
					wp_send_json_error(['message' => esc_html__('Security check failed.', 'service-booking-manager')]);
				}
				$code = isset($_POST['code']) ? sanitize_text_field(wp_unslash($_POST['code'])) : '';
				$email = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
				if (!$code) {
					wp_send_json_error(['message' => esc_html__('Please enter a coupon code.', 'service-booking-manager')]);
				}
				if (MPWPB_Global_Function::is_wc_payment_mode()) {
					$this->apply_coupon_wc($code, $email);
				} else {
					$this->apply_coupon_native($code, $email);
				}
			}

			public function remove_coupon(): void {
				if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'mpwpb_nonce')) {
					wp_send_json_error(['message' => esc_html__('Security check failed.', 'service-booking-manager')]);
				}
				if (MPWPB_Global_Function::is_wc_payment_mode()) {
					if (function_exists('WC') && WC()->cart) {
						$cart_key = self::find_wc_booking_cart_key();
						if ($cart_key) {
							unset(WC()->cart->cart_contents[$cart_key]['mpwpb_coupon_code']);
							unset(WC()->cart->cart_contents[$cart_key]['mpwpb_discount_amount']);
							WC()->cart->set_session();
						}
					}
					wp_send_json_success(['message' => esc_html__('Coupon removed.', 'service-booking-manager')]);
				}
				$item = MPWPB_Native_Cart::get_item();
				if (!empty($item)) {
					unset($item['mpwpb_coupon_code']);
					unset($item['mpwpb_discount_amount']);
					MPWPB_Native_Cart::set_item($item);
				}
				wp_send_json_success([
					'message' => esc_html__('Coupon removed.', 'service-booking-manager'),
					'html' => $this->render_native_recap($item),
				]);
			}

			private function apply_coupon_native($code, $email): void {
				$item = MPWPB_Native_Cart::get_item();
				if (empty($item)) {
					wp_send_json_error(['message' => esc_html__('Your booking cart is empty.', 'service-booking-manager')]);
				}
				$context = MPWPB_Coupon_Validator::build_context($item, $email, get_current_user_id());
				$result = MPWPB_Coupon_Validator::validate($code, $context);
				if (!$result['valid']) {
					wp_send_json_error(['message' => $result['message']]);
				}
				$discount = MPWPB_Coupon_Validator::calculate_discount($result['coupon_id'], $context);
				$item['mpwpb_coupon_code'] = MPWPB_Coupon_Function::normalize_code($code);
				$item['mpwpb_discount_amount'] = $discount;
				MPWPB_Native_Cart::set_item($item);
				wp_send_json_success([
					'message' => esc_html__('Coupon applied.', 'service-booking-manager'),
					'html' => $this->render_native_recap($item),
				]);
			}

			private function apply_coupon_wc($code, $email): void {
				if (!function_exists('WC') || !WC()->cart) {
					wp_send_json_error(['message' => esc_html__('Cart unavailable.', 'service-booking-manager')]);
				}
				$cart_key = self::find_wc_booking_cart_key();
				if (!$cart_key) {
					wp_send_json_error(['message' => esc_html__('Your cart has no bookable service.', 'service-booking-manager')]);
				}
				$item = WC()->cart->cart_contents[$cart_key];
				if (!$email && is_user_logged_in()) {
					$email = wp_get_current_user()->user_email;
				}
				$context = MPWPB_Coupon_Validator::build_context($item, $email, get_current_user_id());
				$result = MPWPB_Coupon_Validator::validate($code, $context);
				if (!$result['valid']) {
					wp_send_json_error(['message' => $result['message']]);
				}
				$discount = MPWPB_Coupon_Validator::calculate_discount($result['coupon_id'], $context);
				WC()->cart->cart_contents[$cart_key]['mpwpb_coupon_code'] = MPWPB_Coupon_Function::normalize_code($code);
				WC()->cart->cart_contents[$cart_key]['mpwpb_discount_amount'] = $discount;
				WC()->cart->set_session();
				wp_send_json_success([
					'message' => esc_html__('Coupon applied.', 'service-booking-manager'),
				]);
			}

			/** WooCommerce Checkout Blocks cart extension. */
			public function register_store_api_extension(): void {
				if (!function_exists('woocommerce_store_api_register_endpoint_data') || !function_exists('woocommerce_store_api_register_update_callback')) {
					return;
				}
				woocommerce_store_api_register_endpoint_data([
					'endpoint' => 'cart',
					'namespace' => 'mpwpb_coupon',
					'data_callback' => [$this, 'get_store_api_data'],
					'schema_callback' => [$this, 'get_store_api_schema'],
					'schema_type' => ARRAY_A,
				]);
				woocommerce_store_api_register_update_callback([
					'namespace' => 'mpwpb_coupon',
					'callback' => [$this, 'handle_store_api_update'],
				]);
			}

			public function get_store_api_data(): array {
				if (!function_exists('WC') || !WC()->cart) {
					return ['enabled' => false, 'code' => '', 'discount' => ''];
				}
				$cart_key = self::find_wc_booking_cart_key();
				if (!$cart_key) {
					return ['enabled' => false, 'code' => '', 'discount' => ''];
				}
				$item = WC()->cart->cart_contents[$cart_key];
				$discount = (float) ($item['mpwpb_discount_amount'] ?? 0);
				return [
					'enabled' => true,
					'code' => (string) ($item['mpwpb_coupon_code'] ?? ''),
					'discount' => $discount > 0 ? html_entity_decode(wp_strip_all_tags(MPWPB_Global_Function::wc_price(0, $discount)), ENT_QUOTES, 'UTF-8') : '',
				];
			}

			public function get_store_api_schema(): array {
				return [
					'enabled' => ['description' => 'Whether booking coupons are available.', 'type' => 'boolean', 'readonly' => true],
					'code' => ['description' => 'Applied booking coupon code.', 'type' => 'string', 'readonly' => true],
					'discount' => ['description' => 'Formatted booking discount.', 'type' => 'string', 'readonly' => true],
				];
			}

			public function handle_store_api_update($data): void {
				if (!function_exists('WC') || !WC()->cart) {
					throw new WC_REST_Exception('mpwpb_coupon_cart_unavailable', esc_html__('Cart unavailable.', 'service-booking-manager'), 400);
				}
				$cart_key = self::find_wc_booking_cart_key();
				if (!$cart_key) {
					throw new WC_REST_Exception('mpwpb_coupon_no_booking', esc_html__('Your cart has no bookable service.', 'service-booking-manager'), 400);
				}
				$action = isset($data['action']) ? sanitize_key($data['action']) : 'apply';
				if ($action === 'remove') {
					unset(WC()->cart->cart_contents[$cart_key]['mpwpb_coupon_code'], WC()->cart->cart_contents[$cart_key]['mpwpb_discount_amount']);
					WC()->cart->set_session();
					return;
				}
				$code = isset($data['code']) ? sanitize_text_field($data['code']) : '';
				if ($code === '') {
					throw new WC_REST_Exception('mpwpb_coupon_empty', esc_html__('Please enter a coupon code.', 'service-booking-manager'), 400);
				}
				$item = WC()->cart->cart_contents[$cart_key];
				$email = $this->get_wc_customer_email();
				$context = MPWPB_Coupon_Validator::build_context($item, $email, get_current_user_id());
				$result = MPWPB_Coupon_Validator::validate($code, $context);
				if (!$result['valid']) {
					throw new WC_REST_Exception('mpwpb_coupon_invalid', wp_strip_all_tags($result['message']), 400);
				}
				WC()->cart->cart_contents[$cart_key]['mpwpb_coupon_code'] = MPWPB_Coupon_Function::normalize_code($code);
				WC()->cart->cart_contents[$cart_key]['mpwpb_discount_amount'] = MPWPB_Coupon_Validator::calculate_discount($result['coupon_id'], $context);
				WC()->cart->set_session();
			}

			private function get_wc_customer_email(): string {
				if (function_exists('WC') && WC()->customer) {
					$email = sanitize_email(WC()->customer->get_billing_email());
					if ($email) {
						return $email;
					}
				}
				return is_user_logged_in() ? sanitize_email(wp_get_current_user()->user_email) : '';
			}

			public function enqueue_checkout_block_assets(): void {
				if (!class_exists('WooCommerce') || (function_exists('is_checkout') && !is_checkout())) {
					return;
				}
				$js = '/assets/frontend/mpwpb-coupon-block.js';
				$css = '/assets/frontend/mpwpb-coupon-block.css';
				wp_enqueue_style('mpwpb_coupon_block', MPWPB_PLUGIN_URL . $css, [], file_exists(MPWPB_PLUGIN_DIR . $css) ? filemtime(MPWPB_PLUGIN_DIR . $css) : '1.0.0');
				wp_enqueue_script('mpwpb_coupon_block', MPWPB_PLUGIN_URL . $js, ['wp-element', 'wp-plugins', 'wp-data', 'wc-blocks-checkout', 'wc-blocks-components'], file_exists(MPWPB_PLUGIN_DIR . $js) ? filemtime(MPWPB_PLUGIN_DIR . $js) : '1.0.0', true);
				wp_localize_script('mpwpb_coupon_block', 'mpwpbCouponBlockI18n', [
					'title' => esc_html__('Booking coupon', 'service-booking-manager'),
					'placeholder' => esc_html__('Coupon code', 'service-booking-manager'),
					'apply' => esc_html__('Apply', 'service-booking-manager'),
					'applying' => esc_html__('Applying…', 'service-booking-manager'),
					'applied' => esc_html__('Applied', 'service-booking-manager'),
					'remove' => esc_html__('Remove', 'service-booking-manager'),
					'error' => esc_html__('Unable to update the coupon. Please try again.', 'service-booking-manager'),
				]);
			}

			/**
			 * Shared with MPWPB_Partial_Payment's checkout-page payment-choice
			 * toggle -- both need the same "which cart item is the booking"
			 * lookup, so it's public/static rather than duplicated.
			 */
			public static function find_wc_booking_cart_key() {
				foreach (WC()->cart->get_cart() as $key => $value) {
					if (!empty($value['mpwpb_id'])) {
						return $key;
					}
				}
				return '';
			}

			private function render_native_recap($item): string {
				$item = is_array($item) ? $item : [];
				$post_id = $item['mpwpb_id'] ?? 0;
				if (!$post_id) {
					return '';
				}
				ob_start();
				?>
				<div class="mpwpb-checkout-recap-root" data-mpwpb-recap-root>
					<?php MPWPB_Native_Checkout::render_booking_recap($item, $post_id, true, true); ?>
				</div>
				<?php
				return ob_get_clean();
			}

			/**
			 * Coupon box on the real WooCommerce checkout page. Hooked to this
			 * plugin's own (otherwise-unused) action in its
			 * woocommerce/checkout/form-checkout.php override, right before the
			 * "Your order" heading.
			 */
			public function render_wc_coupon_box(): void {
				if (!function_exists('WC') || !WC()->cart) {
					return;
				}
				$cart_key = self::find_wc_booking_cart_key();
				if (!$cart_key) {
					return; // nothing bookable in the cart for a booking coupon to apply to
				}
				$item = WC()->cart->cart_contents[$cart_key];
				$code = (string) ($item['mpwpb_coupon_code'] ?? '');
				?>
				<div class="mpwpb-coupon-box mpwpb-wc-coupon-box" data-mpwpb-coupon-box>
					<?php if ($code !== '') : ?>
						<div class="mpwpb-coupon-applied">
							<span class="mpwpb-coupon-applied-code"><i class="fas fa-tag"></i> <?php echo esc_html($code); ?></span>
							<button type="button" class="mpwpb-coupon-remove" data-mpwpb-remove-coupon><?php esc_html_e('Remove', 'service-booking-manager'); ?></button>
						</div>
					<?php else : ?>
						<div class="mpwpb-coupon-form">
							<input type="text" class="mpwpb-coupon-input" placeholder="<?php esc_attr_e('Coupon code', 'service-booking-manager'); ?>" data-mpwpb-coupon-input/>
							<button type="button" class="mpwpb-coupon-apply" data-mpwpb-apply-coupon><?php esc_html_e('Apply Coupon', 'service-booking-manager'); ?></button>
						</div>
					<?php endif; ?>
					<p class="mpwpb-coupon-message" data-mpwpb-coupon-message style="display:none;"></p>
				</div>
				<?php
			}

			/**
			 * Blocks WC checkout submission if a still-applied coupon has since
			 * become invalid (expired, hit its usage limit, etc. between being
			 * applied and the order actually being placed).
			 */
			public function revalidate_at_wc_checkout($values, $post_id): void {
				$coupon_code = $values['mpwpb_coupon_code'] ?? '';
				if (!$coupon_code) {
					return;
				}
				$email = isset($_POST['billing_email']) ? sanitize_email(wp_unslash($_POST['billing_email'])) : $this->get_wc_customer_email();
				$context = MPWPB_Coupon_Validator::build_context($values, $email, get_current_user_id());
				$result = MPWPB_Coupon_Validator::validate($coupon_code, $context);
				if (!$result['valid']) {
					wc_add_notice(sprintf(
						/* translators: %s: reason the coupon is no longer valid */
						esc_html__('Your coupon is no longer valid: %s', 'service-booking-manager'),
						$result['message']
					), 'error');
				}
			}
		}
		new MPWPB_Coupon_Frontend();
	}
