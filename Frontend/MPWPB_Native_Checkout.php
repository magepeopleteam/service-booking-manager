<?php
	/*
	 * Native (non-WooCommerce) checkout: renders a billing form for the
	 * single item held in MPWPB_Native_Cart, lets the customer pick from
	 * whichever gateways are enabled under Payment Method > Custom
	 * (Offline / Stripe / PayPal), and creates a MPWPB_Native_Order.
	 * Offline confirms immediately (nothing to charge). Stripe and PayPal
	 * (MPWPB_Stripe_Gateway / MPWPB_Paypal_Gateway) redirect to a real
	 * hosted payment page and only mark the order paid once the payment is
	 * independently verified with the gateway on return (plus a Stripe
	 * webhook as a backstop) — never based on the return URL alone.
	 * Entirely inert when Payment Method is set to WooCommerce.
	 */
	if (!defined('ABSPATH')) {
		die;
	} // Cannot access pages directly.
	if (!class_exists('MPWPB_Native_Checkout')) {
		class MPWPB_Native_Checkout {
			private $checkout_content_rendered = false;
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
				add_action('wp_ajax_mpwpb_stripe_webhook', [$this, 'handle_stripe_webhook']);
				add_action('wp_ajax_nopriv_mpwpb_stripe_webhook', [$this, 'handle_stripe_webhook']);
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
				// Same rule as filter_checkout_content(): status=success only marks
				// this as a return-from-checkout URL, never proof of payment by
				// itself -- re-verify with the gateway before trusting it.
				$this->maybe_finalize_gateway_return($order_id);
				ob_start();
				if (MPWPB_Native_Order::get_status($order_id) === 'processing') {
					$this->render_thank_you($order_id);
				} else {
					$this->render_payment_incomplete($order_id);
				}
				return ob_get_clean();
			}
			/**
			 * Gateways the admin has enabled under Payment Method > Custom.
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
			/**
			 * Appointment-slot card + itemized booking summary, shared between
			 * the pre-payment checkout form (render_embedded_form()) and the
			 * post-payment thank-you page (render_thank_you()) -- same item
			 * shape either way (MPWPB_Native_Cart::get_item() before payment,
			 * the order's stored 'mpwpb_line_items' snapshot after).
			 */
			/** Public/static so other order-detail views (e.g. Frontend/MPWPB_Custom_Payment_My_Account.php's dashboard order view) can reuse it instead of reimplementing the services/total recap. */
			public static function render_booking_recap(array $item, $post_id, bool $show_change_button): void {
				$total = $item['mpwpb_tp'] ?? 0;

				// A comma-joined value here means a recurring booking with more
				// than one occurrence (see mpwpb_registration.js dateTimeString) --
				// show every occurrence, not just the first, so the recap actually
				// reflects the full itinerary the customer is being charged for.
				$date_raw = is_string($item['mpwpb_date'] ?? '') ? $item['mpwpb_date'] : '';
				$date_segments = $date_raw !== '' ? array_filter(array_map('trim', explode(',', $date_raw))) : [];
				$use_24hour = MPWPB_Global_Function::get_settings('mpwpb_global_settings', 'time_format_24hour', 'no');
				$time_format = $use_24hour === 'yes' ? 'H:i' : 'g:i A';
				/* translators: %s: formatted time, e.g. "10:30 AM" */
				$slot_format = sprintf(__('l, M j \@ %s', 'service-booking-manager'), $time_format);
				$slot_displays = [];
				foreach ($date_segments as $segment) {
					$segment_ts = strtotime($segment);
					if ($segment_ts) {
						$slot_displays[] = date_i18n($slot_format, $segment_ts);
					}
				}

				$slot_location = trim(
					implode(
						' - ',
						array_filter([
							(string) ($item['mpwpb_category'] ?? ''),
							(string) ($item['mpwpb_sub_category'] ?? ''),
						])
					)
				);
				if ($slot_location === '') {
					$slot_location = get_bloginfo('name');
				}
				?>
				<?php if (!empty($slot_displays)) : ?>
					<div class="mpwpb-checkout-slot">
						<div class="mpwpb-checkout-slot-main">
							<span class="mpwpb-checkout-slot-eyebrow">
								<?php echo esc_html(count($slot_displays) > 1 ? __('Appointment Slots', 'service-booking-manager') : __('Appointment Slot', 'service-booking-manager')); ?>
							</span>
							<?php if (count($slot_displays) > 1) : ?>
								<ol class="mpwpb-checkout-slot-dates">
									<?php foreach ($slot_displays as $slot_display) : ?>
										<li><?php echo esc_html($slot_display); ?></li>
									<?php endforeach; ?>
								</ol>
							<?php else : ?>
								<div class="mpwpb-checkout-slot-date"><?php echo esc_html($slot_displays[0]); ?></div>
							<?php endif; ?>
							<div class="mpwpb-checkout-slot-location"><?php echo esc_html($slot_location); ?></div>
						</div>
						<?php if ($show_change_button) : ?>
							<button type="button" class="mpwpb-checkout-slot-change" data-checkout-back-to-date>
								<i class="fas fa-pen"></i> <?php esc_html_e('Change', 'service-booking-manager'); ?>
							</button>
						<?php endif; ?>
						<div class="mpwpb-checkout-slot-icon"><i class="fas fa-calendar-alt"></i></div>
					</div>
				<?php endif; ?>
				<?php
				// Tracks the per-visit rate (services + extras for ONE
				// occurrence) while the loops below render each line, so a
				// recurring booking's total can be broken down as
				// "rate × occurrences − discount = payable" afterward,
				// instead of only showing the final payable figure with no
				// indication of how it was derived.
				$per_occurrence_rate = 0;
				?>
				<div class="mpwpb-checkout-summary">
					<div class="mpwpb-checkout-summary-label"><?php esc_html_e('Booking Summary', 'service-booking-manager'); ?></div>
					<?php if (!empty($item['mpwpb_service']) && is_array($item['mpwpb_service'])) : ?>
						<div class="mpwpb-checkout-summary-subhead"><?php esc_html_e('Services', 'service-booking-manager'); ?></div>
						<ul class="mpwpb-checkout-summary-list">
							<?php foreach ($item['mpwpb_service'] as $service) :
								$qty = max(1, (int) ($service['qty'] ?? 1));
								$unit_price = (float) ($service['price'] ?? 0);
								$line_total = $unit_price * $qty;
								$per_occurrence_rate += $line_total;
								?>
								<li>
									<span class="fas fa-check-circle"></span>
									<span class="mpwpb-checkout-summary-name"><?php echo esc_html($service['name'] ?? ''); ?></span>
									<span class="mpwpb-checkout-summary-qty"><?php echo esc_html('x' . $qty); ?></span>
									<span class="mpwpb-checkout-summary-price"><?php echo wp_kses_post(MPWPB_Global_Function::wc_price($post_id, $line_total)); ?></span>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
					<?php if (!empty($item['mpwpb_extra_service_info']) && is_array($item['mpwpb_extra_service_info'])) : ?>
						<div class="mpwpb-checkout-summary-subhead"><?php esc_html_e('Extra Service Addons', 'service-booking-manager'); ?></div>
						<ul class="mpwpb-checkout-summary-list">
							<?php foreach ($item['mpwpb_extra_service_info'] as $extra) :
								$ex_qty = max(1, (int) ($extra['ex_qty'] ?? 1));
								$ex_unit_price = (float) ($extra['ex_price'] ?? 0);
								$ex_line_total = $ex_unit_price * $ex_qty;
								$per_occurrence_rate += $ex_line_total;
								?>
								<li>
									<span class="fas fa-plus-circle"></span>
									<span class="mpwpb-checkout-summary-name"><?php echo esc_html($extra['ex_name'] ?? ''); ?></span>
									<span class="mpwpb-checkout-summary-qty"><?php echo esc_html('x' . $ex_qty); ?></span>
									<span class="mpwpb-checkout-summary-price"><?php echo wp_kses_post(MPWPB_Global_Function::wc_price($post_id, $ex_line_total)); ?></span>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
					<?php
					// $total (mpwpb_tp) is already the final, recurring-discounted
					// payable amount (see MPWPB_Woocommerce::calculate_discounted_total()) --
					// the per-visit rate × occurrence count reconstructs the
					// pre-discount subtotal, and the discount is just the
					// difference between that and the payable total, so no
					// discount percentage needs to be re-fetched here.
					$occurrence_count = max(1, count($date_segments));
					$subtotal = round($per_occurrence_rate * $occurrence_count, 2);
					$discount_amount = ($occurrence_count > 1) ? max(0, round($subtotal - $total, 2)) : 0;
					?>
					<?php if ($occurrence_count > 1 && $per_occurrence_rate > 0) : ?>
						<div class="mpwpb-checkout-recurring-breakdown">
							<div class="mpwpb-checkout-recurring-row">
								<span><?php esc_html_e('Rate per visit', 'service-booking-manager'); ?></span>
								<span><?php echo wp_kses_post(MPWPB_Global_Function::wc_price($post_id, $per_occurrence_rate)); ?></span>
							</div>
							<div class="mpwpb-checkout-recurring-row">
								<span>
									<?php
									/* translators: %d: number of recurring occurrences */
									printf(esc_html(_n('%d visit', '%d visits', $occurrence_count, 'service-booking-manager')), (int) $occurrence_count);
									?>
									&times; <?php echo wp_kses_post(MPWPB_Global_Function::wc_price($post_id, $per_occurrence_rate)); ?>
								</span>
								<span><?php echo wp_kses_post(MPWPB_Global_Function::wc_price($post_id, $subtotal)); ?></span>
							</div>
							<?php if ($discount_amount > 0) : ?>
								<div class="mpwpb-checkout-recurring-row mpwpb-checkout-recurring-discount">
									<span><?php esc_html_e('Recurring Discount', 'service-booking-manager'); ?></span>
									<span>&minus;<?php echo wp_kses_post(MPWPB_Global_Function::wc_price($post_id, $discount_amount)); ?></span>
								</div>
							<?php endif; ?>
						</div>
					<?php endif; ?>
					<div class="mpwpb-checkout-total">
						<span><?php esc_html_e('Total', 'service-booking-manager'); ?></span>
						<strong><?php echo wp_kses_post(MPWPB_Global_Function::wc_price($post_id, $total)); ?></strong>
					</div>
				</div>
				<?php
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
					<p class="mpwpb-checkout-empty"><?php esc_html_e('No payment method is currently configured. Please configure a payment method to accept bookings.', 'service-booking-manager'); ?></p>
					<?php
					return;
				}
				$post_id = $item['mpwpb_id'];
				?>
				<div class="mpwpb-checkout-embed">
					<?php self::render_booking_recap($item, $post_id, true); ?>
					<p class="mpwpb-checkout-error" style="display:none;"></p>
					<form class="mpwpb-checkout-form" method="post" action="<?php echo esc_url(admin_url('admin-ajax.php')); ?>">
						<input type="hidden" name="action" value="mpwpb_native_checkout_submit"/>
						<input type="hidden" name="nonce" value="<?php echo esc_attr(wp_create_nonce('mpwpb_nonce')); ?>"/>
						<input type="hidden" name="mpwpb_ajax_submit" value="1"/>
						<div class="mpwpb-checkout-card">
							<div class="mpwpb-checkout-card-header">
								<span class="mpwpb-checkout-card-icon"><i class="fas fa-user"></i></span>
								<h3 class="mpwpb-checkout-card-title"><?php esc_html_e('Customer Information', 'service-booking-manager'); ?></h3>
							</div>
							<div class="mpwpb-checkout-card-body">
								<div class="mpwpb-checkout-row">
									<label class="mpwpb-checkout-field">
										<span><?php esc_html_e('First Name', 'service-booking-manager'); ?></span>
										<input type="text" name="mpwpb_billing_first_name" placeholder="<?php esc_attr_e('Enter first name', 'service-booking-manager'); ?>" required/>
									</label>
									<label class="mpwpb-checkout-field">
										<span><?php esc_html_e('Last Name', 'service-booking-manager'); ?></span>
										<input type="text" name="mpwpb_billing_last_name" placeholder="<?php esc_attr_e('Enter last name', 'service-booking-manager'); ?>" required/>
									</label>
								</div>
								<div class="mpwpb-checkout-row">
									<label class="mpwpb-checkout-field">
										<span><?php esc_html_e('Email Address', 'service-booking-manager'); ?></span>
										<input type="email" name="mpwpb_billing_email" placeholder="name@example.com" required/>
									</label>
									<label class="mpwpb-checkout-field">
										<span><?php esc_html_e('Phone Number', 'service-booking-manager'); ?></span>
										<input type="text" name="mpwpb_billing_phone" placeholder="(555) 000-0000"/>
									</label>
								</div>
								<label class="mpwpb-checkout-field">
									<span><?php esc_html_e('Address', 'service-booking-manager'); ?></span>
									<input type="text" name="mpwpb_billing_address_1" placeholder="<?php esc_attr_e('123 Main St, City', 'service-booking-manager'); ?>"/>
								</label>
							</div>
						</div>
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
						<?php if (MPWPB_Global_Function::is_gdpr_enabled()) : ?>
							<?php
							$privacy_policy_page_id = (int) MPWPB_Global_Function::get_gdpr_setting('privacy_policy_page_id');
							$privacy_consent_text = MPWPB_Global_Function::get_gdpr_setting('privacy_consent_text', esc_html__('I agree to the Privacy Policy.', 'service-booking-manager'));
							$data_consent_text = MPWPB_Global_Function::get_gdpr_setting('data_consent_text', esc_html__('I consent to my personal data being processed for this booking.', 'service-booking-manager'));
							?>
							<div class="mpwpb-checkout-gdpr-consent">
								<label class="mpwpb-checkout-consent-row">
									<input type="checkbox" name="mpwpb_privacy_consent" value="1"/>
									<span>
										<?php if ($privacy_policy_page_id) : ?>
											<a href="<?php echo esc_url(get_permalink($privacy_policy_page_id)); ?>" target="_blank" rel="noopener"><?php echo esc_html($privacy_consent_text); ?></a>
										<?php else : ?>
											<?php echo esc_html($privacy_consent_text); ?>
										<?php endif; ?>
									</span>
								</label>
								<label class="mpwpb-checkout-consent-row">
									<input type="checkbox" name="mpwpb_data_consent" value="1"/>
									<span><?php echo esc_html($data_consent_text); ?></span>
								</label>
							</div>
						<?php endif; ?>
						<button type="submit" class="mpwpb-checkout-submit"><?php esc_html_e('Confirm Booking', 'service-booking-manager'); ?></button>
					</form>
				</div>
				<?php
			}
			/**
			 * The no-Confirmation-Page-configured fallback used to call
			 * get_header()/get_footer() directly (classic-theme template
			 * functions) and exit -- on a block theme with no header.php/
			 * footer.php (e.g. Twenty Twenty-Five and every other current
			 * default theme), those are simply no-ops, so the page rendered
			 * with no header/footer at all instead of matching every other
			 * page. Filtering the_content() instead lets WordPress run its
			 * completely normal front-end render for whatever home_url('/')
			 * resolves to (classic or block theme, static front page or
			 * posts index) -- the real header/footer for that theme comes
			 * along for free, since we're no longer bypassing it.
			 */
			public function maybe_render_checkout(): void {
				if (!get_query_var('mpwpb_checkout')) {
					return;
				}
				add_filter('the_content', [$this, 'filter_checkout_content'], 999);
			}
			public function filter_checkout_content($content) {
				if (!in_the_loop() || !is_main_query() || $this->checkout_content_rendered) {
					return $content;
				}
				$this->checkout_content_rendered = true;
				$order_id = isset($_GET['mpwpb_order']) ? absint($_GET['mpwpb_order']) : 0;
				$status = isset($_GET['status']) ? sanitize_text_field(wp_unslash($_GET['status'])) : '';
				$valid_order = $order_id && get_post_type($order_id) === MPWPB_Native_Order::CPT;
				ob_start();
				echo '<div class="mpwpb_style mpwpb_native_checkout">';
				if ($valid_order && $status === 'success') {
					// status=success only means "this URL is a return from checkout,
					// not the cart" -- it is never itself treated as proof of
					// payment. For Stripe/PayPal, maybe_finalize_gateway_return()
					// re-verifies the real payment status with the gateway first;
					// only then does the order's own stored status decide what
					// renders. Offline orders are already marked paid by this
					// point (done synchronously in handle_checkout_submit()), so
					// this is a no-op for them.
					$this->maybe_finalize_gateway_return($order_id);
					if (MPWPB_Native_Order::get_status($order_id) === 'processing') {
						$this->render_thank_you($order_id);
					} else {
						$this->render_payment_incomplete($order_id);
					}
				} else {
					$this->render_cart_and_form();
				}
				echo '</div>';
				return ob_get_clean();
			}
			/**
			 * Post-payment confirmation: reuses the exact same recap markup/
			 * CSS as the pre-payment checkout form (render_booking_recap()),
			 * built from the order's stored 'mpwpb_line_items' snapshot
			 * instead of the (by-now-cleared) MPWPB_Native_Cart. Everything
			 * here is under .mpwpb-confirmation-wrap, which carries its own
			 * self-contained box-sizing reset and a max-width, so this drops
			 * cleanly into the [mpwpb_booking_confirmation] shortcode on any
			 * theme/page without depending on -- or altering -- surrounding
			 * page styles.
			 */
			private function render_thank_you($order_id): void {
				$item = get_post_meta($order_id, 'mpwpb_line_items', true);
				$item = is_array($item) ? $item : [];
				$post_id = $item['mpwpb_id'] ?? 0;
				if (!isset($item['mpwpb_tp'])) {
					$item['mpwpb_tp'] = MPWPB_Native_Order::get_total($order_id);
				}
				?>
				<div class="mpwpb-confirmation-wrap">
					<div class="mpwpb-checkout-embed">
						<div class="mpwpb-confirmation-banner">
							<span class="mpwpb-confirmation-icon"><i class="fas fa-check-circle"></i></span>
							<h2 class="mpwpb-confirmation-title"><?php esc_html_e('Thank you, your booking is confirmed!', 'service-booking-manager'); ?></h2>
							<p class="mpwpb-confirmation-ref">
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
						</div>
						<?php if (!empty($item)) : ?>
							<?php self::render_booking_recap($item, $post_id, false); ?>
						<?php endif; ?>
						<?php self::render_customer_info_card($order_id); ?>
					</div>
				</div>
				<?php
			}
			/**
			 * "Customer Information" card (name/email/phone/address) shared by
			 * the post-checkout thank-you view above and the dashboard order
			 * details view (Frontend/MPWPB_Custom_Payment_My_Account.php) so
			 * both read the same billing meta the same way.
			 */
			public static function render_customer_info_card($order_id): void {
				$first_name = get_post_meta($order_id, 'mpwpb_billing_first_name', true);
				$last_name = get_post_meta($order_id, 'mpwpb_billing_last_name', true);
				$email = get_post_meta($order_id, 'mpwpb_billing_email', true);
				$phone = get_post_meta($order_id, 'mpwpb_billing_phone', true);
				$address = get_post_meta($order_id, 'mpwpb_billing_address_1', true);
				if (!$first_name && !$last_name && !$email && !$phone && !$address) {
					return;
				}
				?>
				<div class="mpwpb-checkout-card">
					<div class="mpwpb-checkout-card-header">
						<span class="mpwpb-checkout-card-icon"><i class="fas fa-user"></i></span>
						<h3 class="mpwpb-checkout-card-title"><?php esc_html_e('Customer Information', 'service-booking-manager'); ?></h3>
					</div>
					<div class="mpwpb-checkout-card-body">
						<div class="mpwpb-checkout-row">
							<div class="mpwpb-checkout-info">
								<span><?php esc_html_e('Full Name', 'service-booking-manager'); ?></span>
								<strong><?php echo esc_html(trim($first_name . ' ' . $last_name)); ?></strong>
							</div>
							<div class="mpwpb-checkout-info">
								<span><?php esc_html_e('Email Address', 'service-booking-manager'); ?></span>
								<strong><?php echo esc_html($email); ?></strong>
							</div>
						</div>
						<?php if ($phone || $address) : ?>
							<div class="mpwpb-checkout-row">
								<div class="mpwpb-checkout-info">
									<span><?php esc_html_e('Phone Number', 'service-booking-manager'); ?></span>
									<strong><?php echo esc_html($phone ?: '—'); ?></strong>
								</div>
								<div class="mpwpb-checkout-info">
									<span><?php esc_html_e('Address', 'service-booking-manager'); ?></span>
									<strong><?php echo esc_html($address ?: '—'); ?></strong>
								</div>
							</div>
						<?php endif; ?>
					</div>
				</div>
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
					<p><?php esc_html_e('No payment method is currently configured. Please configure a payment method to accept bookings.', 'service-booking-manager'); ?></p>
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
				$currency_code = MPWPB_Global_Function::native_currency_setting('currency_code', 'USD');
				$order_id = MPWPB_Native_Order::create([
					'billing' => $billing,
					'line_items' => $item,
					'total' => $item['mpwpb_tp'] ?? 0,
					'currency' => $currency_code,
				]);
				if (!$order_id) {
					if ($is_ajax_submit) {
						wp_send_json_error(['message' => esc_html__('Something went wrong creating your booking. Please try again.', 'service-booking-manager')]);
					}
					wp_safe_redirect(add_query_arg('mpwpb_error', '1', self::get_checkout_url()));
					exit;
				}
				// Both consent checkboxes are optional (render_embedded_form()
				// never marks them required) -- just record whatever the
				// customer chose, only when GDPR is on.
				if (MPWPB_Global_Function::is_gdpr_enabled()) {
					update_post_meta($order_id, 'mpwpb_privacy_policy_consent', isset($_POST['mpwpb_privacy_consent']) ? 'yes' : 'no');
					update_post_meta($order_id, 'mpwpb_data_processing_consent', isset($_POST['mpwpb_data_consent']) ? 'yes' : 'no');
				}
				// Offline still confirms immediately (there's nothing to charge
				// through a gateway). Stripe/PayPal instead send the customer to
				// a hosted payment page and defer mark_paid()/process_order() to
				// filter_checkout_content()'s verification of the real payment
				// status once they return (plus a Stripe webhook as a backstop) --
				// never trust the return purely because the browser came back.
				if ($gateway === 'stripe') {
					$redirect = $this->start_stripe_payment($order_id, $item, $currency_code);
				} elseif ($gateway === 'paypal') {
					$redirect = $this->start_paypal_payment($order_id, $item, $currency_code);
				} else {
					MPWPB_Native_Order::mark_paid($order_id, $gateway, '');
					MPWPB_Native_Order::process_order($order_id);
					MPWPB_Native_Cart::clear();
					$redirect = ['ok' => true, 'url' => self::get_confirmation_url($order_id)];
				}
				if (!$redirect['ok']) {
					if ($is_ajax_submit) {
						wp_send_json_error(['message' => $redirect['error']]);
					}
					wp_safe_redirect(add_query_arg('mpwpb_error', '1', self::get_checkout_url()));
					exit;
				}
				if ($is_ajax_submit) {
					wp_send_json_success(['redirect' => $redirect['url']]);
				}
				wp_safe_redirect($redirect['url']);
				exit;
			}
			/**
			 * @return array{ok:bool,url?:string,error?:string}
			 */
			private function start_stripe_payment($order_id, array $item, string $currency_code): array {
				$success_url = add_query_arg('mpwpb_gateway_return', 'stripe', self::get_confirmation_url($order_id));
				// {CHECKOUT_SESSION_ID} is a literal Stripe template placeholder --
				// appended after add_query_arg() so it's never URL-encoded.
				$success_url .= '&session_id={CHECKOUT_SESSION_ID}';
				$cancel_url = self::get_checkout_url();
				$result = MPWPB_Stripe_Gateway::create_checkout_session($order_id, $item, $currency_code, $success_url, $cancel_url);
				if (!$result['ok']) {
					return ['ok' => false, 'error' => $result['error']];
				}
				update_post_meta($order_id, 'mpwpb_stripe_session_id', $result['session_id']);
				return ['ok' => true, 'url' => $result['url']];
			}
			/**
			 * @return array{ok:bool,url?:string,error?:string}
			 */
			private function start_paypal_payment($order_id, array $item, string $currency_code): array {
				$return_url = add_query_arg('mpwpb_gateway_return', 'paypal', self::get_confirmation_url($order_id));
				$cancel_url = self::get_checkout_url();
				$result = MPWPB_Paypal_Gateway::create_order($order_id, $item, $currency_code, $return_url, $cancel_url);
				if (!$result['ok']) {
					return ['ok' => false, 'error' => $result['error']];
				}
				update_post_meta($order_id, 'mpwpb_paypal_order_id', $result['paypal_order_id']);
				return ['ok' => true, 'url' => $result['approve_url']];
			}
			/**
			 * Verifies the real payment status with the gateway before ever
			 * marking an order paid -- the mpwpb_gateway_return/session_id/token
			 * query args only say which gateway to check with, they are never
			 * trusted as proof of payment by themselves.
			 */
			private function maybe_finalize_gateway_return($order_id): void {
				if (MPWPB_Native_Order::get_status($order_id) === 'processing') {
					return;
				}
				$gateway_return = isset($_GET['mpwpb_gateway_return']) ? sanitize_text_field(wp_unslash($_GET['mpwpb_gateway_return'])) : '';
				if ($gateway_return === 'stripe') {
					$session_id = isset($_GET['session_id']) ? sanitize_text_field(wp_unslash($_GET['session_id'])) : '';
					$result = MPWPB_Stripe_Gateway::retrieve_session($session_id);
					if ($result['ok'] && !empty($result['paid']) && (int) ($result['order_id'] ?? 0) === (int) $order_id) {
						$this->finalize_paid_order($order_id, 'stripe', $session_id);
					}
				} elseif ($gateway_return === 'paypal') {
					$paypal_order_id = isset($_GET['token']) ? sanitize_text_field(wp_unslash($_GET['token'])) : '';
					$result = MPWPB_Paypal_Gateway::capture_order($paypal_order_id);
					if ($result['ok'] && !empty($result['captured']) && (int) ($result['order_id'] ?? 0) === (int) $order_id) {
						$this->finalize_paid_order($order_id, 'paypal', $paypal_order_id);
					}
				}
			}
			private function finalize_paid_order($order_id, $gateway, $txn_id): void {
				if (MPWPB_Native_Order::get_status($order_id) === 'processing') {
					return;
				}
				MPWPB_Native_Order::mark_paid($order_id, $gateway, $txn_id);
				MPWPB_Native_Order::process_order($order_id);
				// Only the customer's own browser/cookie can reach this; a webhook
				// request (Stripe, server-to-server) has no such cookie, so this
				// is a no-op there and the cart transient just expires on its own.
				MPWPB_Native_Cart::clear();
			}
			private function render_payment_incomplete($order_id): void {
				?>
				<div class="mpwpb-confirmation-wrap">
					<div class="mpwpb-checkout-embed">
						<div class="mpwpb-confirmation-banner">
							<span class="mpwpb-confirmation-icon mpwpb-confirmation-icon--warn"><i class="fas fa-triangle-exclamation"></i></span>
							<h2 class="mpwpb-confirmation-title"><?php esc_html_e("We couldn't confirm your payment", 'service-booking-manager'); ?></h2>
							<p class="mpwpb-confirmation-ref"><?php esc_html_e('If you completed payment, this can take a moment to confirm — please check back shortly, or contact us with your reference below.', 'service-booking-manager'); ?></p>
							<p class="mpwpb-confirmation-ref">
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
						</div>
						<p style="text-align:center;">
							<a class="mpwpb-checkout-submit mpwpb-checkout-retry-link" href="<?php echo esc_url(self::get_checkout_url()); ?>"><?php esc_html_e('Try again', 'service-booking-manager'); ?></a>
						</p>
					</div>
				</div>
				<?php
			}
			/**
			 * Stripe webhook — a backstop alongside the inline verification in
			 * filter_checkout_content(): covers a customer closing the tab before
			 * the redirect back completes, or Stripe's own async payment methods.
			 * finalize_paid_order() is idempotent, so it's harmless if the inline
			 * check already handled it first.
			 */
			public function handle_stripe_webhook(): void {
				$payload = file_get_contents('php://input');
				$sig_header = isset($_SERVER['HTTP_STRIPE_SIGNATURE']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_STRIPE_SIGNATURE'])) : '';
				$secret = MPWPB_Stripe_Gateway::get_webhook_secret();
				if (!$secret || !MPWPB_Stripe_Gateway::verify_webhook_signature($payload, $sig_header, $secret)) {
					status_header(400);
					exit;
				}
				$event = json_decode($payload, true);
				if (($event['type'] ?? '') === 'checkout.session.completed') {
					$session = $event['data']['object'] ?? [];
					$order_id = (int) ($session['metadata']['mpwpb_order_id'] ?? ($session['client_reference_id'] ?? 0));
					if ($order_id && get_post_type($order_id) === MPWPB_Native_Order::CPT && ($session['payment_status'] ?? '') === 'paid') {
						$this->finalize_paid_order($order_id, 'stripe', $session['id'] ?? '');
					}
				}
				status_header(200);
				exit;
			}
		}
		new MPWPB_Native_Checkout();
	}
