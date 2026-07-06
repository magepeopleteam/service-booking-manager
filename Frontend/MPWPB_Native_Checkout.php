<?php
	/*
	 * Native (non-WooCommerce) checkout: renders a billing form for the
	 * single item held in MPWPB_Native_Cart, lets the customer pick from
	 * whichever gateways are enabled under Payment Method > Custom
	 * (Offline / Stripe / PayPal), creates a MPWPB_Native_Order, and (for
	 * now, until real gateway processing is built) marks it paid
	 * immediately regardless of which gateway was chosen. Entirely inert
	 * when Payment Method is set to WooCommerce.
	 */
	if (!defined('ABSPATH')) {
		die;
	} // Cannot access pages directly.
	if (!class_exists('MPWPB_Native_Checkout')) {
		class MPWPB_Native_Checkout {
			public function __construct() {
				if (MPWPB_Global_Function::is_wc_payment_mode()) {
					return;
				}
				add_filter('query_vars', [$this, 'add_query_var']);
				add_action('template_redirect', [$this, 'maybe_render_checkout']);
				add_action('wp_ajax_mpwpb_native_checkout_submit', [$this, 'handle_checkout_submit']);
				add_action('wp_ajax_nopriv_mpwpb_native_checkout_submit', [$this, 'handle_checkout_submit']);
				add_action('wp_ajax_mpwpb_native_checkout_form', [$this, 'ajax_render_embedded_form']);
				add_action('wp_ajax_nopriv_mpwpb_native_checkout_form', [$this, 'ajax_render_embedded_form']);
				add_shortcode('mpwpb_booking_confirmation', [$this, 'render_confirmation_shortcode']);
			}
			public function add_query_var($vars) {
				$vars[] = 'mpwpb_checkout';
				return $vars;
			}
			public static function get_checkout_url(): string {
				return add_query_arg('mpwpb_checkout', '1', home_url('/'));
			}
			/**
			 * Where to send the customer after a successful booking: the
			 * admin-configured "Booking Confirmation Page" (rendered via the
			 * [mpwpb_booking_confirmation] shortcode) if one is set, else the
			 * built-in thank-you view on the checkout URL itself.
			 */
			public static function get_confirmation_url($order_id): string {
				$page_id = (int) MPWPB_Global_Function::get_payment_setting('confirmation_page_id');
				if ($page_id && get_post_status($page_id) === 'publish') {
					return add_query_arg(['mpwpb_order' => $order_id, 'status' => 'success'], get_permalink($page_id));
				}
				return add_query_arg(['mpwpb_checkout' => '1', 'mpwpb_order' => $order_id, 'status' => 'success'], home_url('/'));
			}
			public function render_confirmation_shortcode(): string {
				$order_id = isset($_GET['mpwpb_order']) ? absint($_GET['mpwpb_order']) : 0;
				$status = isset($_GET['status']) ? sanitize_text_field(wp_unslash($_GET['status'])) : '';
				if (!$order_id || $status !== 'success' || get_post_type($order_id) !== MPWPB_Native_Order::CPT) {
					return '<p>' . esc_html__('No booking information found.', 'service-booking-manager') . '</p>';
				}
				ob_start();
				$this->render_thank_you($order_id);
				return ob_get_clean();
			}
			/**
			 * Gateways the admin has enabled under Payment Method > Custom.
			 * Note: Stripe/PayPal charge processing isn't built yet — selecting
			 * either one currently confirms the booking the same way Offline
			 * does. Real gateway integration is the next piece of work.
			 */
			public static function get_enabled_gateways(): array {
				$gateways = [];
				if (MPWPB_Global_Function::get_payment_setting('offline_enabled') === 'on') {
					$gateways['offline'] = esc_html__('Offline Payment', 'service-booking-manager');
				}
				if (MPWPB_Global_Function::get_payment_setting('stripe_enabled') === 'on') {
					$gateways['stripe'] = esc_html__('Credit/Debit Card (Stripe)', 'service-booking-manager');
				}
				if (MPWPB_Global_Function::get_payment_setting('paypal_enabled') === 'on') {
					$gateways['paypal'] = esc_html__('PayPal', 'service-booking-manager');
				}
				return $gateways;
			}
			/**
			 * Builds a booking item from the current $_POST (same contract as the
			 * WooCommerce mpwpb_add_to_cart AJAX action) and stores it in the
			 * native cart. Returns the checkout URL, or '' if the item is invalid.
			 */
			public static function add_to_cart($link_id): string {
				$item = MPWPB_Woocommerce::build_booking_item_from_request($link_id);
				$post_id = $item['mpwpb_id'] ?? 0;
				if (!$post_id || get_post_type($post_id) != MPWPB_Function::get_cpt() || get_post_status($post_id) !== 'publish') {
					return '';
				}
				MPWPB_Native_Cart::set_item($item);
				return self::get_checkout_url();
			}
			/**
			 * AJAX-only: returns the same billing form as the standalone
			 * checkout page, but as a bare HTML fragment (no get_header()/
			 * get_footer()) styled to sit inside the booking popup's own
			 * "Checkout" step (templates/registration/static_registration.php
			 * .mpwpb_order_proceed_area) instead of navigating the browser
			 * away to a separate page.
			 */
			public function ajax_render_embedded_form(): void {
				if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'mpwpb_nonce')) {
					wp_send_json_error(['message' => esc_html__('Security check failed.', 'service-booking-manager')]);
				}
				ob_start();
				$this->render_embedded_form();
				wp_send_json_success(['html' => ob_get_clean()]);
			}
			private function render_embedded_form(): void {
				$item = MPWPB_Native_Cart::get_item();
				if (empty($item)) {
					?>
					<p class="mpwpb-checkout-empty"><?php esc_html_e('Your booking cart is empty.', 'service-booking-manager'); ?></p>
					<?php
					return;
				}
				$gateways = self::get_enabled_gateways();
				if (empty($gateways)) {
					?>
					<p class="mpwpb-checkout-empty"><?php esc_html_e('No payment method is currently available. Please contact the site administrator.', 'service-booking-manager'); ?></p>
					<?php
					return;
				}
				$post_id = $item['mpwpb_id'];
				$total = $item['mpwpb_tp'] ?? 0;
				?>
				<div class="mpwpb-checkout-embed">
					<div class="mpwpb-checkout-summary">
						<?php if (!empty($item['mpwpb_service']) && is_array($item['mpwpb_service'])) : ?>
							<ul class="mpwpb-checkout-summary-list">
								<?php foreach ($item['mpwpb_service'] as $service) : ?>
									<li>
										<span class="fas fa-check-circle"></span>
										<?php echo esc_html($service['name'] ?? ''); ?>
									</li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>
						<div class="mpwpb-checkout-total">
							<span><?php esc_html_e('Total', 'service-booking-manager'); ?></span>
							<strong><?php echo wp_kses_post(MPWPB_Global_Function::wc_price($post_id, $total)); ?></strong>
						</div>
					</div>
					<p class="mpwpb-checkout-error" style="display:none;"></p>
					<form class="mpwpb-checkout-form" method="post" action="<?php echo esc_url(admin_url('admin-ajax.php')); ?>">
						<input type="hidden" name="action" value="mpwpb_native_checkout_submit"/>
						<input type="hidden" name="nonce" value="<?php echo esc_attr(wp_create_nonce('mpwpb_nonce')); ?>"/>
						<input type="hidden" name="mpwpb_ajax_submit" value="1"/>
						<div class="mpwpb-checkout-row">
							<label class="mpwpb-checkout-field">
								<span><?php esc_html_e('First Name', 'service-booking-manager'); ?></span>
								<input type="text" name="mpwpb_billing_first_name" required/>
							</label>
							<label class="mpwpb-checkout-field">
								<span><?php esc_html_e('Last Name', 'service-booking-manager'); ?></span>
								<input type="text" name="mpwpb_billing_last_name" required/>
							</label>
						</div>
						<div class="mpwpb-checkout-row">
							<label class="mpwpb-checkout-field">
								<span><?php esc_html_e('Email', 'service-booking-manager'); ?></span>
								<input type="email" name="mpwpb_billing_email" required/>
							</label>
							<label class="mpwpb-checkout-field">
								<span><?php esc_html_e('Phone', 'service-booking-manager'); ?></span>
								<input type="text" name="mpwpb_billing_phone"/>
							</label>
						</div>
						<label class="mpwpb-checkout-field">
							<span><?php esc_html_e('Address', 'service-booking-manager'); ?></span>
							<input type="text" name="mpwpb_billing_address_1"/>
						</label>
						<?php if (count($gateways) > 1) : ?>
							<div class="mpwpb-checkout-gateways">
								<span class="mpwpb-checkout-gateways-label"><?php esc_html_e('Payment Method', 'service-booking-manager'); ?></span>
								<?php foreach ($gateways as $key => $label) : ?>
									<label class="mpwpb-checkout-gateway">
										<input type="radio" name="mpwpb_payment_gateway" value="<?php echo esc_attr($key); ?>" <?php checked($key, array_key_first($gateways)); ?> />
										<?php echo esc_html($label); ?>
									</label>
								<?php endforeach; ?>
							</div>
						<?php else : ?>
							<input type="hidden" name="mpwpb_payment_gateway" value="<?php echo esc_attr(array_key_first($gateways)); ?>"/>
						<?php endif; ?>
						<button type="submit" class="mpwpb-checkout-submit"><?php esc_html_e('Confirm Booking', 'service-booking-manager'); ?></button>
					</form>
				</div>
				<?php
			}
			public function maybe_render_checkout(): void {
				if (!get_query_var('mpwpb_checkout')) {
					return;
				}
				$this->render_checkout_page();
				exit;
			}
			private function render_checkout_page(): void {
				get_header();
				$order_id = isset($_GET['mpwpb_order']) ? absint($_GET['mpwpb_order']) : 0;
				$status = isset($_GET['status']) ? sanitize_text_field(wp_unslash($_GET['status'])) : '';
				echo '<div class="mpwpb_style mpwpb_native_checkout" style="max-width:640px;margin:40px auto;">';
				if ($order_id && $status === 'success' && get_post_type($order_id) === MPWPB_Native_Order::CPT) {
					$this->render_thank_you($order_id);
				} else {
					$this->render_cart_and_form();
				}
				echo '</div>';
				get_footer();
			}
			private function render_thank_you($order_id): void {
				?>
				<h2><?php esc_html_e('Thank you, your booking is confirmed!', 'service-booking-manager'); ?></h2>
				<p>
					<?php
					echo esc_html(
						sprintf(
							/* translators: %s: order reference number */
							__('Order reference: #%s', 'service-booking-manager'),
							$order_id
						)
					);
					?>
				</p>
				<?php
			}
			private function render_cart_and_form(): void {
				$item = MPWPB_Native_Cart::get_item();
				if (empty($item)) {
					?>
					<p><?php esc_html_e('Your booking cart is empty.', 'service-booking-manager'); ?></p>
					<?php
					return;
				}
				$gateways = self::get_enabled_gateways();
				if (empty($gateways)) {
					?>
					<p><?php esc_html_e('No payment method is currently available. Please contact the site administrator.', 'service-booking-manager'); ?></p>
					<?php
					return;
				}
				$post_id = $item['mpwpb_id'];
				$total = $item['mpwpb_tp'] ?? 0;
				?>
				<h2><?php esc_html_e('Checkout', 'service-booking-manager'); ?></h2>
				<div class="mpwpb_native_checkout_summary">
					<?php if (!empty($item['mpwpb_service']) && is_array($item['mpwpb_service'])) : ?>
						<ul>
							<?php foreach ($item['mpwpb_service'] as $service) : ?>
								<li><?php echo esc_html($service['name'] ?? ''); ?></li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
					<p>
						<strong><?php esc_html_e('Total:', 'service-booking-manager'); ?></strong>
						<?php echo wp_kses_post(MPWPB_Global_Function::wc_price($post_id, $total)); ?>
					</p>
				</div>
				<?php if (isset($_GET['mpwpb_error'])) : ?>
					<p style="color:#b32d2e;"><?php esc_html_e('Please fill in all required fields.', 'service-booking-manager'); ?></p>
				<?php endif; ?>
				<form method="post" action="<?php echo esc_url(admin_url('admin-ajax.php')); ?>">
					<input type="hidden" name="action" value="mpwpb_native_checkout_submit"/>
					<input type="hidden" name="nonce" value="<?php echo esc_attr(wp_create_nonce('mpwpb_nonce')); ?>"/>
					<p>
						<label><?php esc_html_e('First Name', 'service-booking-manager'); ?><br/>
							<input type="text" name="mpwpb_billing_first_name" required/>
						</label>
					</p>
					<p>
						<label><?php esc_html_e('Last Name', 'service-booking-manager'); ?><br/>
							<input type="text" name="mpwpb_billing_last_name" required/>
						</label>
					</p>
					<p>
						<label><?php esc_html_e('Email', 'service-booking-manager'); ?><br/>
							<input type="email" name="mpwpb_billing_email" required/>
						</label>
					</p>
					<p>
						<label><?php esc_html_e('Phone', 'service-booking-manager'); ?><br/>
							<input type="text" name="mpwpb_billing_phone"/>
						</label>
					</p>
					<p>
						<label><?php esc_html_e('Address', 'service-booking-manager'); ?><br/>
							<input type="text" name="mpwpb_billing_address_1"/>
						</label>
					</p>
					<?php if (count($gateways) > 1) : ?>
						<p>
							<strong><?php esc_html_e('Payment Method', 'service-booking-manager'); ?></strong><br/>
							<?php foreach ($gateways as $key => $label) : ?>
								<label style="display:block;">
									<input type="radio" name="mpwpb_payment_gateway" value="<?php echo esc_attr($key); ?>" <?php checked($key, array_key_first($gateways)); ?> />
									<?php echo esc_html($label); ?>
								</label>
							<?php endforeach; ?>
						</p>
					<?php else : ?>
						<input type="hidden" name="mpwpb_payment_gateway" value="<?php echo esc_attr(array_key_first($gateways)); ?>"/>
					<?php endif; ?>
					<p>
						<button type="submit"><?php esc_html_e('Confirm Booking', 'service-booking-manager'); ?></button>
					</p>
				</form>
				<?php
			}
			public function handle_checkout_submit(): void {
				// The embedded popup form (render_embedded_form()) carries this
				// flag and is consumed via $.post/AJAX, where a redirect response
				// can't navigate the browser -- it needs a JSON success/error
				// reply instead. The standalone fallback page's form (no JS/
				// direct link) omits it and keeps the classic redirect flow.
				$is_ajax_submit = isset($_POST['mpwpb_ajax_submit']);
				if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'mpwpb_nonce')) {
					if ($is_ajax_submit) {
						wp_send_json_error(['message' => esc_html__('Security check failed.', 'service-booking-manager')]);
					}
					wp_die(esc_html__('Security check failed.', 'service-booking-manager'));
				}
				$item = MPWPB_Native_Cart::get_item();
				$first_name = isset($_POST['mpwpb_billing_first_name']) ? sanitize_text_field(wp_unslash($_POST['mpwpb_billing_first_name'])) : '';
				$last_name = isset($_POST['mpwpb_billing_last_name']) ? sanitize_text_field(wp_unslash($_POST['mpwpb_billing_last_name'])) : '';
				$email = isset($_POST['mpwpb_billing_email']) ? sanitize_email(wp_unslash($_POST['mpwpb_billing_email'])) : '';
				$gateway = isset($_POST['mpwpb_payment_gateway']) ? sanitize_text_field(wp_unslash($_POST['mpwpb_payment_gateway'])) : '';
				$enabled_gateways = self::get_enabled_gateways();
				if (empty($item) || !$first_name || !$last_name || !$email || !array_key_exists($gateway, $enabled_gateways)) {
					if ($is_ajax_submit) {
						wp_send_json_error(['message' => esc_html__('Please fill in all required fields.', 'service-booking-manager')]);
					}
					wp_safe_redirect(add_query_arg('mpwpb_error', '1', self::get_checkout_url()));
					exit;
				}
				$billing = [
					'first_name' => $first_name,
					'last_name' => $last_name,
					'email' => $email,
					'phone' => isset($_POST['mpwpb_billing_phone']) ? sanitize_text_field(wp_unslash($_POST['mpwpb_billing_phone'])) : '',
					'address_1' => isset($_POST['mpwpb_billing_address_1']) ? sanitize_text_field(wp_unslash($_POST['mpwpb_billing_address_1'])) : '',
					'address_2' => '',
				];
				$order_id = MPWPB_Native_Order::create([
					'billing' => $billing,
					'line_items' => $item,
					'total' => $item['mpwpb_tp'] ?? 0,
					'currency' => MPWPB_Global_Function::native_currency_setting('symbol', '$'),
				]);
				if (!$order_id) {
					if ($is_ajax_submit) {
						wp_send_json_error(['message' => esc_html__('Something went wrong creating your booking. Please try again.', 'service-booking-manager')]);
					}
					wp_safe_redirect(add_query_arg('mpwpb_error', '1', self::get_checkout_url()));
					exit;
				}
				// Stripe/PayPal charge processing isn't built yet, so every
				// gateway currently confirms immediately like Offline does.
				// Real gateways will replace this with a redirect to the
				// gateway and defer process_order() to its webhook.
				MPWPB_Native_Order::mark_paid($order_id, $gateway, '');
				MPWPB_Native_Order::process_order($order_id);
				MPWPB_Native_Cart::clear();
				if ($is_ajax_submit) {
					wp_send_json_success(['redirect' => self::get_confirmation_url($order_id)]);
				}
				wp_safe_redirect(self::get_confirmation_url($order_id));
				exit;
			}
		}
		new MPWPB_Native_Checkout();
	}
