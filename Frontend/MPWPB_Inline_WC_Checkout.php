<?php
	/**
	 * Hosts WooCommerce's classic checkout inside the service-booking drawer.
	 */
	if (!defined('ABSPATH')) {
		die;
	}
	if (!class_exists('MPWPB_Inline_WC_Checkout')) {
		class MPWPB_Inline_WC_Checkout {
			public function __construct() {
				add_filter('woocommerce_is_checkout', [$this, 'is_inline_checkout_context']);
				add_filter('woocommerce_get_return_url', [$this, 'filter_return_url'], 100, 2);
				add_action('wp_ajax_mpwpb_inline_wc_checkout', [$this, 'render_checkout']);
				add_action('wp_ajax_nopriv_mpwpb_inline_wc_checkout', [$this, 'render_checkout']);
				add_action('wp_ajax_mpwpb_inline_wc_confirmation', [$this, 'render_confirmation']);
				add_action('wp_ajax_nopriv_mpwpb_inline_wc_confirmation', [$this, 'render_confirmation']);
			}

			/** Make WC and gateway scripts treat service pages as checkout contexts. */
			public function is_inline_checkout_context($is_checkout): bool {
				if ($is_checkout || is_admin() || !MPWPB_Global_Function::is_wc_payment_mode()) {
					return (bool) $is_checkout;
				}
				return is_singular(MPWPB_Function::get_cpt());
			}

			private static function verify_request(): void {
				if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'mpwpb_nonce')) {
					wp_send_json_error(['message' => esc_html__('Security check failed. Please refresh the page and try again.', 'service-booking-manager')], 403);
				}
			}

			private static function cart_has_booking(): bool {
				if (!function_exists('WC') || !WC()->cart) {
					return false;
				}
				foreach (WC()->cart->get_cart() as $item) {
					if (get_post_type(absint($item['mpwpb_id'] ?? 0)) === MPWPB_Function::get_cpt()) {
						return true;
					}
				}
				return false;
			}

			private static function cart_service_id(): int {
				if (!function_exists('WC') || !WC()->cart) {
					return 0;
				}
				foreach (WC()->cart->get_cart() as $item) {
					$service_id = absint($item['mpwpb_id'] ?? 0);
					if (get_post_type($service_id) === MPWPB_Function::get_cpt()) {
						return $service_id;
					}
				}
				return 0;
			}

			private static function order_has_booking($order): bool {
				if (!$order instanceof WC_Order) {
					return false;
				}
				foreach ($order->get_items() as $item) {
					if (get_post_type(absint($item->get_meta('_mpwpb_id', true))) === MPWPB_Function::get_cpt()) {
						return true;
					}
				}
				return false;
			}

			/** Return the inner checkout markup; the persistent form wrapper lives in the drawer. */
			public function render_checkout(): void {
				self::verify_request();
				if (!MPWPB_Global_Function::is_wc_payment_mode() || !self::cart_has_booking()) {
					wp_send_json_error(['message' => esc_html__('Your booking cart is empty.', 'service-booking-manager')], 400);
				}

				$source_url = '';
				$source_id = isset($_POST['service_id']) ? absint($_POST['service_id']) : 0;
				$cart_service_id = self::cart_service_id();
				if ($source_id && $source_id === $cart_service_id) {
					$source_url = get_permalink($source_id);
				}
				if ($source_url && wp_parse_url($source_url, PHP_URL_HOST) === wp_parse_url(home_url('/'), PHP_URL_HOST)) {
					WC()->session->set('mpwpb_inline_checkout_source', $source_url);
				}

				WC()->cart->calculate_totals();
				$checkout = WC()->checkout();
				ob_start();
				?>
				<div class="mpwpb-inline-checkout-notices" aria-live="polite"></div>
				<div class="mpwpb-inline-checkout-stage" data-inline-stage="billing">
					<div class="mpwpb-inline-checkout-heading">
						<h3><?php esc_html_e('Billing & Customer Information', 'service-booking-manager'); ?></h3>
						<p><?php esc_html_e('Enter the details required to confirm your booking.', 'service-booking-manager'); ?></p>
					</div>
					<?php do_action('woocommerce_checkout_before_customer_details'); ?>
					<div id="customer_details" class="col2-set">
						<div class="col-1"><?php do_action('woocommerce_checkout_billing'); ?></div>
						<div class="col-2"><?php do_action('woocommerce_checkout_shipping'); ?></div>
					</div>
					<?php do_action('woocommerce_checkout_after_customer_details'); ?>
					<div class="mpwpb-inline-stage-actions">
						<button type="button" class="button mpwpb-inline-back-to-booking"><?php esc_html_e('Back', 'service-booking-manager'); ?></button>
						<button type="button" class="button alt mpwpb-inline-continue-payment"><?php esc_html_e('Continue to Payment', 'service-booking-manager'); ?></button>
					</div>
				</div>
				<div class="mpwpb-inline-checkout-stage" data-inline-stage="payment" hidden>
					<div class="mpwpb-inline-checkout-heading">
						<h3><?php esc_html_e('Payment & Booking Summary', 'service-booking-manager'); ?></h3>
						<p><?php esc_html_e('Review your booking and choose a payment method.', 'service-booking-manager'); ?></p>
					</div>
					<div class="mpwpb-inline-coupon">
						<label for="mpwpb_inline_coupon_code"><?php esc_html_e('Coupon code', 'woocommerce'); ?></label>
						<div>
							<input type="text" id="mpwpb_inline_coupon_code" autocomplete="off" placeholder="<?php esc_attr_e('Coupon code', 'woocommerce'); ?>"/>
							<button type="button" class="button mpwpb-inline-apply-coupon"><?php esc_html_e('Apply coupon', 'woocommerce'); ?></button>
						</div>
						<span class="mpwpb-inline-coupon-message" aria-live="polite"></span>
					</div>
					<h3 id="order_review_heading"><?php esc_html_e('Your order', 'woocommerce'); ?></h3>
					<div id="order_review" class="woocommerce-checkout-review-order"><?php do_action('woocommerce_checkout_order_review'); ?></div>
					<button type="button" class="button mpwpb-inline-back-to-billing"><?php esc_html_e('Back to Billing', 'service-booking-manager'); ?></button>
				</div>
				<?php
				$html = ob_get_clean();
				wp_send_json_success(['html' => $html]);
			}

			/** Keep gateway returns tied to the originating service page. */
			public function filter_return_url($return_url, $order) {
				if (!self::order_has_booking($order) || !function_exists('WC') || !WC()->session) {
					return $return_url;
				}
				$source_url = esc_url_raw((string) WC()->session->get('mpwpb_inline_checkout_source'));
				if (!$source_url || wp_parse_url($source_url, PHP_URL_HOST) !== wp_parse_url(home_url('/'), PHP_URL_HOST)) {
					return $return_url;
				}
				return add_query_arg([
					'mpwpb_inline_order' => $order->get_id(),
					'key' => $order->get_order_key(),
				], $source_url);
			}

			public function render_confirmation(): void {
				self::verify_request();
				$order_id = isset($_POST['order_id']) ? absint($_POST['order_id']) : 0;
				$order_key = isset($_POST['order_key']) ? wc_clean(wp_unslash($_POST['order_key'])) : '';
				$order = $order_id ? wc_get_order($order_id) : false;
				if (!$order || !hash_equals((string) $order->get_order_key(), (string) $order_key) || !self::order_has_booking($order)) {
					wp_send_json_error(['message' => esc_html__('The order confirmation could not be verified.', 'service-booking-manager')], 403);
				}
				if (is_user_logged_in() && $order->get_customer_id() && (int) $order->get_customer_id() !== get_current_user_id() && !current_user_can('manage_woocommerce')) {
					wp_send_json_error(['message' => esc_html__('You are not allowed to view this order.', 'service-booking-manager')], 403);
				}

				ob_start();
				echo '<div class="mpwpb-inline-confirmation">';
				echo '<div class="mpwpb-inline-confirmation-summary">';
				echo '<h2>' . esc_html__('Thank you. Your booking has been received.', 'service-booking-manager') . '</h2>';
				echo '<p><strong>' . esc_html__('Order status:', 'service-booking-manager') . '</strong> ' . esc_html(wc_get_order_status_name($order->get_status())) . '</p>';
				if ($order->get_billing_email()) {
					echo '<p><strong>' . esc_html__('Customer email:', 'service-booking-manager') . '</strong> ' . esc_html($order->get_billing_email()) . '</p>';
				}
				echo '</div>';
				wc_get_template('checkout/thankyou.php', ['order' => $order]);
				echo '</div>';
				if (function_exists('WC') && WC()->session) {
					WC()->session->__unset('mpwpb_inline_checkout_source');
				}
				wp_send_json_success(['html' => ob_get_clean()]);
			}
		}
		new MPWPB_Inline_WC_Checkout();
	}
