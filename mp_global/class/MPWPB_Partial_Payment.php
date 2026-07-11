<?php
	/*
   * @Author 		engr.sumonazma@gmail.com
   * Copyright: 	mage-people.com
   */
	if (!defined('ABSPATH')) {
		die;
	} // Cannot access pages directly.

	if (!class_exists('MPWPB_Partial_Payment')) {
		/**
		 * Deposit/partial-payment policy + amount-tracking helpers, shared by
		 * the WooCommerce order path (MPWPB_Woocommerce), the native/Custom
		 * Payment order path (MPWPB_Native_Order/MPWPB_Native_Checkout), and
		 * the admin "Record Payment" action -- so the deposit math and the
		 * paid/due bookkeeping only exist in one place for both systems.
		 *
		 * 'mpwpb_order_status' on a mpwpb_booking post is a DERIVED display
		 * value once partial payment is involved: 'mpwpb_real_order_status'
		 * holds the actual underlying WC/native order status, and
		 * compute_display_status() turns that + the current amount due into
		 * what should actually be shown/filtered on ('partially-paid' while
		 * money is still owed, otherwise the real status unchanged). Every
		 * site that used to write 'mpwpb_order_status' directly must instead
		 * go through sync_display_status() so a later status change (e.g.
		 * on-hold -> processing) can't silently clobber 'partially-paid'
		 * back to the raw status while a balance is still due.
		 */
		class MPWPB_Partial_Payment {

			const TERMINAL_NON_PAYABLE_STATUSES = ['cancelled', 'failed', 'refunded'];

			public function __construct() {
				add_filter('query_vars', [$this, 'add_query_var']);
				add_action('template_redirect', [$this, 'maybe_handle_pay_balance_redirect']);
				// A WC balance order (create_wc_balance_order() below) is a plain
				// WC_Order with a WC_Order_Item_Fee line item, not one of this
				// plugin's own mpwpb-tagged line items -- MPWPB_Woocommerce::
				// order_status_changed() only ever looks at orders that have
				// those, so it never sees a balance order reach a paid status.
				// This is the one place that does.
				add_action('woocommerce_order_status_changed', [$this, 'maybe_apply_wc_balance_payment'], 20, 4);
				// Pay in Full / Pay Deposit Now choice: rendered on the real
				// checkout screens right before the customer places the order
				// (see render_choice_radio()) instead of earlier in the booking
				// wizard. Hooked inside the same #order_review fragment WC
				// rebuilds on 'update_checkout', so re-rendering here always
				// reflects whatever the AJAX handler below just saved.
				add_action('woocommerce_review_order_before_submit', [$this, 'render_wc_payment_choice_box']);
				add_action('wp_ajax_mpwpb_set_payment_choice', [$this, 'ajax_set_payment_choice']);
				add_action('wp_ajax_nopriv_mpwpb_set_payment_choice', [$this, 'ajax_set_payment_choice']);
				// This site's WooCommerce checkout page uses the Cart & Checkout
				// Blocks (React/Store API), which never fires any of the classic
				// woocommerce_checkout_* template hooks above -- render_wc_payment_choice_box()
				// silently never runs there. These two hooks are the Blocks-native
				// equivalent: a Store API cart extension (namespace 'mpwpb') that
				// the Checkout block's Order Summary can read/write live, wired to
				// the exact same before_calculate_totals() price logic afterward.
				add_action('woocommerce_blocks_loaded', [$this, 'register_store_api_extension']);
				// woocommerce_blocks_enqueue_checkout_block_scripts_after exists
				// in WC core but never actually fires in this WooCommerce
				// version's real render path (confirmed live: did_action() stayed
				// 0 on an actual checkout page load) -- wp_enqueue_scripts is the
				// same reliable, always-fires hook every other frontend asset in
				// this plugin already uses (mpwpb_coupon, mpwpb_registration, etc.),
				// and WP resolves script dependencies lazily at print time, so
				// declaring wc-blocks-checkout/wc-blocks-components as deps here
				// still works even though those get registered by a separate class.
				add_action('wp_enqueue_scripts', [$this, 'enqueue_checkout_block_script']);
				// 'init' (not admin_init) -- is_enabled()/get_deposit_amount() are
				// read from front-end checkout code too, so the migration must have
				// already run before the very first front-end request after this
				// tab split ships, not just whenever an admin next visits wp-admin.
				add_action('init', [$this, 'maybe_migrate_settings']);
			}

			/**
			 * Partial Payment used to live inside the Payment Method tab's
			 * settings (mpwpb_payment_method_settings option) -- now it has
			 * its own Settings tab/option (mpwpb_partial_payment_settings, see
			 * MPWPB_Native_Checkout_Settings::render_partial_payment_panel()).
			 * One-time copy of whatever the site already had configured there,
			 * so switching tabs doesn't silently reset an already-configured
			 * deposit percentage/amount back to defaults. Safe to run on every
			 * request: only acts once, the first time the new option has no
			 * 'partial_payment_enabled' key yet; never touches/deletes the old
			 * option afterward.
			 */
			public function maybe_migrate_settings(): void {
				$new_settings = get_option('mpwpb_partial_payment_settings');
				if (is_array($new_settings) && isset($new_settings['partial_payment_enabled'])) {
					return;
				}
				$legacy = get_option('mpwpb_payment_method_settings');
				if (!is_array($legacy) || !isset($legacy['partial_payment_enabled'])) {
					return;
				}
				update_option('mpwpb_partial_payment_settings', [
					'partial_payment_enabled' => $legacy['partial_payment_enabled'] ?? 'off',
					'partial_payment_type' => $legacy['partial_payment_type'] ?? 'percentage',
					'partial_payment_percentage' => $legacy['partial_payment_percentage'] ?? 50,
					'partial_payment_fixed_amount' => $legacy['partial_payment_fixed_amount'] ?? 0,
				]);
			}

			public function maybe_apply_wc_balance_payment($order_id, $old_status, $new_status, $order): void {
				if (!$order || !$order->get_meta('_mpwpb_balance_of_order_id')) {
					return;
				}
				if (in_array($new_status, ['processing', 'completed'], true)) {
					self::apply_balance_payment($order_id, true);
				}
			}

			public function add_query_var($vars) {
				$vars[] = 'mpwpb_pay_balance';
				return $vars;
			}

			/**
			 * The one "Pay Balance" entry point for both payment systems --
			 * MPWPB_User_Dashboard::render_booking_actions() links here
			 * (?mpwpb_pay_balance={booking_id}) regardless of which system the
			 * booking was placed under; create_balance_order() itself branches
			 * WC-vs-native and returns the right URL to send the customer to.
			 */
			public function maybe_handle_pay_balance_redirect(): void {
				$booking_id = (int) get_query_var('mpwpb_pay_balance');
				if (!$booking_id || get_post_type($booking_id) !== 'mpwpb_booking') {
					return;
				}
				if (!is_user_logged_in()) {
					wp_safe_redirect(wp_login_url(add_query_arg([])));
					exit;
				}
				// Ownership check -- only the customer this booking belongs to
				// (or an admin) can trigger a balance payment for it.
				$owner_id = (int) get_post_meta($booking_id, 'mpwpb_user_id', true);
				if ($owner_id !== get_current_user_id() && !current_user_can('manage_options')) {
					wp_die(esc_html__('You do not have permission to pay this balance.', 'service-booking-manager'), '', ['response' => 403]);
				}
				$result = self::create_balance_order($booking_id);
				if (is_wp_error($result)) {
					wp_die(esc_html($result->get_error_message()), '', ['response' => 400]);
				}
				wp_safe_redirect($result);
				exit;
			}

			public static function is_enabled(): bool {
				return MPWPB_Global_Function::get_partial_payment_setting('partial_payment_enabled') === 'on';
			}

			/**
			 * Shared Pay in Full / Pay Deposit Now radio markup -- used both on
			 * the real WooCommerce checkout (render_wc_payment_choice_box()
			 * below) and the native checkout's embedded form
			 * (MPWPB_Native_Checkout::render_embedded_form()). A "Pay Balance"
			 * item (create_balance_order() above) always carries payment_choice
			 * 'full' and is never offered the choice again.
			 */
			public static function render_choice_radio(array $item): void {
				if (!self::is_enabled() || !empty($item['mpwpb_is_balance_payment'])) {
					return;
				}
				$net_total = max(0, ((float) ($item['mpwpb_tp'] ?? 0)) - ((float) ($item['mpwpb_discount_amount'] ?? 0)));
				$deposit = self::get_deposit_amount($net_total);
				$choice = ($item['mpwpb_payment_choice'] ?? 'full') === 'partial' ? 'partial' : 'full';
				?>
				<div class="mpwpb-payment-choice-row" data-mpwpb-payment-choice-wrap>
					<label class="mpwpb-payment-choice-option">
						<input type="radio" name="mpwpb_payment_choice" value="full" data-mpwpb-payment-choice-radio <?php checked($choice, 'full'); ?>/>
						<?php esc_html_e('Pay in Full', 'service-booking-manager'); ?>
					</label>
					<label class="mpwpb-payment-choice-option">
						<input type="radio" name="mpwpb_payment_choice" value="partial" data-mpwpb-payment-choice-radio <?php checked($choice, 'partial'); ?>/>
						<?php esc_html_e('Pay Deposit Now', 'service-booking-manager'); ?>
						<span class="mpwpb-payment-choice-due">(<?php esc_html_e('Due Now:', 'service-booking-manager'); ?> <?php echo wp_kses_post(MPWPB_Global_Function::wc_price(0, $deposit)); ?>)</span>
					</label>
				</div>
				<?php
			}

			/**
			 * Renders the choice on the real WooCommerce checkout, right before
			 * the Place Order button -- inside the same #order_review markup WC
			 * regenerates on every 'update_checkout' AJAX call, so it always
			 * shows the currently-saved cart-item choice.
			 */
			public function render_wc_payment_choice_box(): void {
				if (!self::is_enabled() || !function_exists('WC') || !WC()->cart || !class_exists('MPWPB_Coupon_Frontend')) {
					return;
				}
				$cart_key = MPWPB_Coupon_Frontend::find_wc_booking_cart_key();
				if (!$cart_key) {
					return;
				}
				self::render_choice_radio(WC()->cart->cart_contents[$cart_key]);
			}

			/**
			 * Single AJAX entry point for both checkout systems -- mirrors
			 * MPWPB_Coupon_Frontend::apply_coupon()'s is_wc_payment_mode()
			 * branch so the client only ever needs to know one action name.
			 */
			public function ajax_set_payment_choice(): void {
				if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'mpwpb_nonce')) {
					wp_send_json_error(['message' => esc_html__('Security check failed.', 'service-booking-manager')]);
				}
				if (!self::is_enabled()) {
					wp_send_json_error(['message' => esc_html__('Partial payment is not enabled.', 'service-booking-manager')]);
				}
				$choice = (isset($_POST['choice']) && sanitize_text_field(wp_unslash($_POST['choice'])) === 'partial') ? 'partial' : 'full';
				if (MPWPB_Global_Function::is_wc_payment_mode()) {
					$this->ajax_set_wc_payment_choice($choice);
				} else {
					$this->ajax_set_native_payment_choice($choice);
				}
			}

			private function ajax_set_wc_payment_choice($choice): void {
				if (!function_exists('WC') || !WC()->cart || !class_exists('MPWPB_Coupon_Frontend')) {
					wp_send_json_error(['message' => esc_html__('Cart unavailable.', 'service-booking-manager')]);
				}
				$cart_key = MPWPB_Coupon_Frontend::find_wc_booking_cart_key();
				if (!$cart_key) {
					wp_send_json_error(['message' => esc_html__('Your cart has no bookable service.', 'service-booking-manager')]);
				}
				if (!empty(WC()->cart->cart_contents[$cart_key]['mpwpb_is_balance_payment'])) {
					wp_send_json_error(['message' => esc_html__('This item does not support a deposit.', 'service-booking-manager')]);
				}
				WC()->cart->cart_contents[$cart_key]['mpwpb_payment_choice'] = $choice;
				WC()->cart->set_session();
				wp_send_json_success();
			}

			private function ajax_set_native_payment_choice($choice): void {
				if (!class_exists('MPWPB_Native_Cart')) {
					wp_send_json_error(['message' => esc_html__('Cart unavailable.', 'service-booking-manager')]);
				}
				$item = MPWPB_Native_Cart::get_item();
				if (empty($item)) {
					wp_send_json_error(['message' => esc_html__('Your booking cart is empty.', 'service-booking-manager')]);
				}
				if (!empty($item['mpwpb_is_balance_payment'])) {
					wp_send_json_error(['message' => esc_html__('This item does not support a deposit.', 'service-booking-manager')]);
				}
				$item['mpwpb_payment_choice'] = $choice;
				MPWPB_Native_Cart::set_item($item);
				$html = '';
				$post_id = $item['mpwpb_id'] ?? 0;
				if ($post_id && class_exists('MPWPB_Native_Checkout')) {
					ob_start();
					?>
					<div class="mpwpb-checkout-recap-root" data-mpwpb-recap-root>
						<?php MPWPB_Native_Checkout::render_booking_recap($item, $post_id, true, true); ?>
					</div>
					<?php
					$html = ob_get_clean();
				}
				wp_send_json_success(['html' => $html]);
			}

			/**
			 * Registers the 'mpwpb' Store API cart extension -- the Blocks
			 * Checkout-native equivalent of render_wc_payment_choice_box()/
			 * ajax_set_payment_choice() above, for sites whose WooCommerce
			 * checkout page uses the Cart & Checkout Blocks (which never fire
			 * classic woocommerce_checkout_* hooks). Confirmed against this
			 * site's actual installed WooCommerce version that
			 * woocommerce_store_api_register_endpoint_data()/
			 * _register_update_callback() exist and behave as documented
			 * before relying on them here.
			 */
			public function register_store_api_extension(): void {
				if (!function_exists('woocommerce_store_api_register_endpoint_data') || !function_exists('woocommerce_store_api_register_update_callback')) {
					return;
				}
				woocommerce_store_api_register_endpoint_data([
					'endpoint' => 'cart',
					'namespace' => 'mpwpb',
					'data_callback' => [$this, 'get_cart_extension_data'],
					'schema_callback' => [$this, 'get_cart_extension_schema'],
					'schema_type' => ARRAY_A,
				]);
				woocommerce_store_api_register_update_callback([
					'namespace' => 'mpwpb',
					'callback' => [$this, 'handle_cart_extension_update'],
				]);
			}

			/**
			 * Data exposed at cart.extensions.mpwpb in every Store API cart/
			 * checkout response -- read by the Checkout block's JS Fill
			 * (mpwpb-payment-choice-block.js) to render/reflect the current
			 * choice without a separate AJAX round trip.
			 */
			public function get_cart_extension_data(): array {
				if (!self::is_enabled() || !function_exists('WC') || !WC()->cart || !class_exists('MPWPB_Coupon_Frontend')) {
					return ['enabled' => false];
				}
				$cart_key = MPWPB_Coupon_Frontend::find_wc_booking_cart_key();
				if (!$cart_key) {
					return ['enabled' => false];
				}
				$item = WC()->cart->cart_contents[$cart_key];
				if (!empty($item['mpwpb_is_balance_payment'])) {
					return ['enabled' => false];
				}
				$net_total = max(0, ((float) ($item['mpwpb_tp'] ?? 0)) - ((float) ($item['mpwpb_discount_amount'] ?? 0)));
				$deposit = self::get_deposit_amount($net_total);
				$choice = ($item['mpwpb_payment_choice'] ?? 'full') === 'partial' ? 'partial' : 'full';
				return [
					'enabled' => true,
					'choice' => $choice,
					'full_amount_formatted' => self::format_price_plain($net_total),
					'deposit_amount_formatted' => self::format_price_plain($deposit),
					'due_later_amount_formatted' => self::format_price_plain(max(0, round($net_total - $deposit, 2))),
				];
			}

			public function get_cart_extension_schema(): array {
				return [
					'enabled' => ['description' => 'Whether partial payment is available for the current cart.', 'type' => 'boolean', 'readonly' => true],
					'choice' => ['description' => 'The currently selected payment choice.', 'type' => 'string', 'readonly' => true],
					'full_amount_formatted' => ['description' => 'Formatted full payment amount.', 'type' => 'string', 'readonly' => true],
					'deposit_amount_formatted' => ['description' => 'Formatted deposit amount due now.', 'type' => 'string', 'readonly' => true],
					'due_later_amount_formatted' => ['description' => 'Formatted remaining balance due later.', 'type' => 'string', 'readonly' => true],
				];
			}

			/**
			 * The write side -- invoked by the Checkout block's
			 * extensionCartUpdate({ namespace: 'mpwpb', data: {...} }) call to
			 * POST /wc/store/v1/cart/extensions. The route itself calls
			 * CartController::calculate_totals() immediately after this
			 * returns, which fires the existing, unchanged
			 * MPWPB_Woocommerce::before_calculate_totals() -- same price logic
			 * as the classic-checkout path, just triggered from a different
			 * entry point.
			 *
			 * @param array $data Posted extension data, e.g. ['choice' => 'partial'].
			 */
			public function handle_cart_extension_update($data): void {
				if (!self::is_enabled() || !function_exists('WC') || !WC()->cart || !class_exists('MPWPB_Coupon_Frontend')) {
					return;
				}
				$cart_key = MPWPB_Coupon_Frontend::find_wc_booking_cart_key();
				if (!$cart_key || !empty(WC()->cart->cart_contents[$cart_key]['mpwpb_is_balance_payment'])) {
					return;
				}
				$choice = (isset($data['choice']) && $data['choice'] === 'partial') ? 'partial' : 'full';
				WC()->cart->cart_contents[$cart_key]['mpwpb_payment_choice'] = $choice;
				WC()->cart->set_session();
			}

			/**
			 * Hooked to wp_enqueue_scripts (fires on every frontend request,
			 * same as this plugin's other frontend assets) rather than a
			 * Blocks-specific enqueue hook -- WP resolves script dependencies
			 * lazily at print time, so declaring wc-blocks-checkout/
			 * wc-blocks-components/wp-element/wp-plugins/wp-data as deps here
			 * still resolves correctly even though those handles are
			 * registered by a separate WooCommerce Blocks class. The registerPlugin()
			 * call inside the script itself only ever renders on the actual
			 * Checkout block (scope: 'woocommerce-checkout'), so loading this
			 * unconditionally site-wide is harmless -- same pattern already
			 * used by mpwpb_coupon/mpwpb_registration in MPWPB_Dependencies.php.
			 */
			public function enqueue_checkout_block_script(): void {
				if (!self::is_enabled()) {
					return;
				}
				wp_enqueue_style(
					'mpwpb_payment_choice_block',
					MPWPB_PLUGIN_URL . '/assets/frontend/mpwpb-payment-choice-block.css',
					[],
					time()
				);
				wp_enqueue_script(
					'mpwpb_payment_choice_block',
					MPWPB_PLUGIN_URL . '/assets/frontend/mpwpb-payment-choice-block.js',
					['wp-element', 'wp-plugins', 'wp-data', 'wc-blocks-checkout', 'wc-blocks-components'],
					time(),
					true
				);
				wp_localize_script('mpwpb_payment_choice_block', 'mpwpbPaymentChoiceI18n', [
					'fullEyebrow' => esc_html__('Standard', 'service-booking-manager'),
					'fullLabel' => esc_html__('Pay in Full', 'service-booking-manager'),
					'depositEyebrow' => esc_html__('Flexible', 'service-booking-manager'),
					'depositLabel' => esc_html__('Pay Deposit', 'service-booking-manager'),
					'todaySuffix' => esc_html__('today', 'service-booking-manager'),
				]);
			}

			/**
			 * Plain-text currency string for contexts that escape their input
			 * (MPWPB_Booking_History's old_date/new_date/note columns, rendered
			 * via esc_html() in MPWPB_Layout_Pro::history_info()) -- wc_price()
			 * returns HTML with the currency symbol as an entity (e.g. &#36;);
			 * wp_strip_all_tags() alone removes the <span> tags but leaves that
			 * entity un-decoded, so a later esc_html() would double-encode it
			 * into a literal "&#36;" on screen instead of "$". Decoding after
			 * stripping avoids that.
			 */
			public static function format_price_plain($amount): string {
				return html_entity_decode(wp_strip_all_tags(MPWPB_Global_Function::wc_price(0, $amount)), ENT_QUOTES, 'UTF-8');
			}

			/**
			 * @param float $net_total Already-discounted total (post-coupon), matching
			 *                         the $net_total pattern used at the WC/native checkout sites.
			 */
			public static function get_deposit_amount($net_total): float {
				$net_total = (float) $net_total;
				if ($net_total <= 0) {
					return 0.0;
				}
				$type = MPWPB_Global_Function::get_partial_payment_setting('partial_payment_type', 'percentage');
				if ($type === 'fixed') {
					$amount = (float) MPWPB_Global_Function::get_partial_payment_setting('partial_payment_fixed_amount', 0);
				} else {
					$percentage = (float) MPWPB_Global_Function::get_partial_payment_setting('partial_payment_percentage', 50);
					$amount = round($net_total * ($percentage / 100), 2);
				}
				return max(0.0, min($amount, $net_total));
			}

			/**
			 * @param string $payment_choice 'full' | 'partial'
			 * @return array{deposit: float, due: float} Both derived from $net_total;
			 *         'full' (or partial payment not enabled) always yields due = 0.
			 */
			public static function split_total($net_total, $payment_choice): array {
				$net_total = (float) $net_total;
				if ($payment_choice === 'partial' && self::is_enabled()) {
					$deposit = self::get_deposit_amount($net_total);
					return ['deposit' => $deposit, 'due' => max(0.0, round($net_total - $deposit, 2))];
				}
				return ['deposit' => $net_total, 'due' => 0.0];
			}

			public static function compute_display_status($real_status, $amount_due): string {
				$amount_due = (float) $amount_due;
				if ($amount_due > 0.005 && !in_array($real_status, self::TERMINAL_NON_PAYABLE_STATUSES, true)) {
					return 'partially-paid';
				}
				return (string) $real_status;
			}

			public static function get_amount_paid($booking_id): float {
				return (float) get_post_meta($booking_id, 'mpwpb_amount_paid', true);
			}

			public static function get_amount_due($booking_id): float {
				return (float) get_post_meta($booking_id, 'mpwpb_amount_due', true);
			}

			/**
			 * Recomputes and writes the booking's DISPLAYED 'mpwpb_order_status'
			 * from its real status + current amount due. Pass $real_status when
			 * the underlying status just changed; omit it to just re-derive off
			 * the amount due changing instead (e.g. a payment was just recorded).
			 */
			public static function sync_display_status($booking_id, $real_status = null): string {
				if ($real_status !== null) {
					update_post_meta($booking_id, 'mpwpb_real_order_status', $real_status);
				} else {
					$real_status = get_post_meta($booking_id, 'mpwpb_real_order_status', true);
				}
				$display_status = self::compute_display_status($real_status, self::get_amount_due($booking_id));
				update_post_meta($booking_id, 'mpwpb_order_status', $display_status);
				return $display_status;
			}

			/**
			 * Records a payment against a booking's outstanding balance --
			 * shared by the admin "Record Payment" action and
			 * apply_balance_payment() below (a paid-online balance order is
			 * just a payment of the full remaining due, recorded the same way).
			 *
			 * @return true|WP_Error
			 */
			public static function record_payment($booking_id, $amount, $note = '', $action_type = null) {
				$amount = round((float) $amount, 2);
				if ($amount <= 0) {
					return new WP_Error('mpwpb_invalid_amount', __('Enter an amount greater than zero.', 'service-booking-manager'));
				}
				$amount_due = self::get_amount_due($booking_id);
				if ($amount_due <= 0) {
					return new WP_Error('mpwpb_no_balance_due', __('This booking has no outstanding balance.', 'service-booking-manager'));
				}
				if ($amount > $amount_due + 0.005) {
					return new WP_Error('mpwpb_amount_exceeds_due', __('That amount is more than the remaining balance due.', 'service-booking-manager'));
				}

				$amount_paid = self::get_amount_paid($booking_id);
				$new_amount_paid = round($amount_paid + $amount, 2);
				$new_amount_due = max(0.0, round($amount_due - $amount, 2));

				update_post_meta($booking_id, 'mpwpb_amount_paid', $new_amount_paid);
				update_post_meta($booking_id, 'mpwpb_amount_due', $new_amount_due);
				$display_status = self::sync_display_status($booking_id);

				if (class_exists('MPWPB_Booking_History')) {
					MPWPB_Booking_History::log(
						$booking_id,
						$action_type ?: MPWPB_Booking_History::ACTION_PAYMENT_MARKED_RECEIVED,
						self::format_price_plain($amount_due),
						self::format_price_plain($new_amount_due),
						$note
					);
				}

				return true;
			}

			/**
			 * Admin manually recording a payment received (cash, bank transfer,
			 * etc.) against a booking's balance -- supports partial/installment
			 * entries, not just settling the full remaining due at once.
			 *
			 * @return true|WP_Error
			 */
			public static function admin_record_payment($booking_id, $amount, $note = '') {
				$note = $note !== '' ? $note : __('Payment recorded by admin.', 'service-booking-manager');
				return self::record_payment($booking_id, $amount, $note, MPWPB_Booking_History::ACTION_PAYMENT_MARKED_RECEIVED);
			}

			/**
			 * Resolves the booking tied to an order id, working for both a real
			 * WC order and a native mpwpb_order post -- both use the same
			 * mpwpb_order_id meta key on the booking, so no branching needed here.
			 */
			public static function get_booking_for_order($order_id): int {
				if (class_exists('MPWPB_User_Dashboard')) {
					return (int) MPWPB_User_Dashboard::get_booking_for_order($order_id);
				}
				return 0;
			}

			/**
			 * Creates (or reuses an already-pending) linked order for exactly a
			 * booking's remaining balance, and returns the URL the customer
			 * should be sent to in order to pay it -- a WooCommerce "Pay for
			 * order" URL, or the native checkout URL, depending on which
			 * payment system the ORIGINAL order was placed under (read off the
			 * booking's own mpwpb_payment_method, not the site's current
			 * setting, so a balance payment still routes correctly even if the
			 * site's payment method was switched after the deposit was taken).
			 *
			 * @return string|WP_Error
			 */
			public static function create_balance_order($booking_id) {
				$amount_due = self::get_amount_due($booking_id);
				if ($amount_due <= 0) {
					return new WP_Error('mpwpb_no_balance_due', __('This booking has no outstanding balance.', 'service-booking-manager'));
				}
				$parent_order_id = (int) get_post_meta($booking_id, 'mpwpb_order_id', true);
				if (!$parent_order_id) {
					return new WP_Error('mpwpb_no_parent_order', __('No order is associated with this booking.', 'service-booking-manager'));
				}

				// Which system the ORIGINAL order actually belongs to, not the
				// site's current Payment Method setting -- that can differ if the
				// admin switches WC/Custom after the deposit was taken, and this
				// must still route to wherever the parent order really lives.
				// mpwpb_order CPT posts are never stored as WC HPOS orders, so a
				// post-type check reliably distinguishes the two.
				if (get_post_type($parent_order_id) === 'mpwpb_order') {
					return self::create_native_balance_order($parent_order_id, $booking_id, $amount_due);
				}
				return self::create_wc_balance_order($parent_order_id, $booking_id, $amount_due);
			}

			private static function create_wc_balance_order($parent_order_id, $booking_id, $amount_due) {
				$parent_order = wc_get_order($parent_order_id);
				if (!$parent_order) {
					return new WP_Error('mpwpb_parent_order_missing', __('The original order could not be found.', 'service-booking-manager'));
				}

				// Reuse an existing unpaid balance order for this parent rather
				// than spawning a new one every time the customer clicks "Pay
				// Balance" (e.g. they abandoned the payment page once already).
				$existing_ids = $parent_order->get_meta('_mpwpb_balance_order_ids');
				$existing_ids = is_array($existing_ids) ? $existing_ids : [];
				foreach ($existing_ids as $existing_id) {
					$existing = wc_get_order($existing_id);
					if ($existing && $existing->needs_payment()) {
						return $existing->get_checkout_payment_url();
					}
				}

				$balance_order = wc_create_order(['status' => 'pending']);
				$balance_order->set_billing_first_name($parent_order->get_billing_first_name());
				$balance_order->set_billing_last_name($parent_order->get_billing_last_name());
				$balance_order->set_billing_email($parent_order->get_billing_email());
				$balance_order->set_billing_phone($parent_order->get_billing_phone());
				$balance_order->set_customer_id($parent_order->get_customer_id());
				$item = new WC_Order_Item_Fee();
				$item->set_name(sprintf(
					/* translators: %s: original order number */
					__('Balance Payment — Order #%s', 'service-booking-manager'),
					$parent_order->get_order_number()
				));
				$item->set_amount($amount_due);
				$item->set_total($amount_due);
				$balance_order->add_item($item);
				$balance_order->calculate_totals(false);
				$balance_order->update_meta_data('_mpwpb_balance_of_order_id', $parent_order_id);
				$balance_order->update_meta_data('_mpwpb_balance_of_booking_id', $booking_id);
				$balance_order->save();

				$existing_ids[] = $balance_order->get_id();
				$parent_order->update_meta_data('_mpwpb_balance_order_ids', $existing_ids);
				$parent_order->save();

				return $balance_order->get_checkout_payment_url();
			}

			private static function create_native_balance_order($parent_order_id, $booking_id, $amount_due) {
				if (!class_exists('MPWPB_Native_Order') || !class_exists('MPWPB_Native_Cart') || !class_exists('MPWPB_Native_Checkout')) {
					return new WP_Error('mpwpb_native_unavailable', __('Custom Payment checkout is not available.', 'service-booking-manager'));
				}
				$parent_status = MPWPB_Native_Order::get_status($parent_order_id);
				if (!$parent_status) {
					return new WP_Error('mpwpb_parent_order_missing', __('The original order could not be found.', 'service-booking-manager'));
				}

				MPWPB_Native_Cart::set_item([
					'mpwpb_is_balance_payment' => true,
					'mpwpb_balance_of_order_id' => $parent_order_id,
					'mpwpb_balance_of_booking_id' => $booking_id,
					'mpwpb_tp' => $amount_due,
					'mpwpb_discount_amount' => 0,
					'mpwpb_payment_choice' => 'full',
					'mpwpb_id' => get_post_meta($booking_id, 'mpwpb_id', true),
					'mpwpb_date' => get_post_meta($booking_id, 'mpwpb_date', true),
					'mpwpb_service' => [],
					'mpwpb_extra_service_info' => [],
					'mpwpb_category' => '',
					'mpwpb_sub_category' => '',
				]);

				return MPWPB_Native_Checkout::get_checkout_url();
			}

			/**
			 * Called once a balance order/payment completes (WC "pay for order"
			 * reaching a paid status, or the native balance-payment branch in
			 * MPWPB_Native_Checkout::handle_checkout_submit()) -- updates the
			 * ORIGINAL booking/order's paid/due and logs history. Safe to call
			 * more than once for the same balance order (no-ops if already applied).
			 */
			public static function apply_balance_payment($balance_order_id, $is_wc = null): void {
				$is_wc = $is_wc ?? MPWPB_Global_Function::is_wc_payment_mode();

				if ($is_wc) {
					$balance_order = wc_get_order($balance_order_id);
					if (!$balance_order) {
						return;
					}
					if ($balance_order->get_meta('_mpwpb_balance_applied') === 'yes') {
						return; // already processed -- avoid double-crediting on repeated webhook/return calls
					}
					$booking_id = (int) $balance_order->get_meta('_mpwpb_balance_of_booking_id');
					$parent_order_id = (int) $balance_order->get_meta('_mpwpb_balance_of_order_id');
					if (!$booking_id) {
						return;
					}
					$amount = (float) $balance_order->get_total();
					self::record_payment($booking_id, $amount, sprintf(
						/* translators: %s: balance order number */
						__('Balance paid online — Order #%s.', 'service-booking-manager'),
						$balance_order->get_order_number()
					), MPWPB_Booking_History::ACTION_BALANCE_PAID);
					$balance_order->update_meta_data('_mpwpb_balance_applied', 'yes');
					$balance_order->save();
					return;
				}

				if (!class_exists('MPWPB_Native_Order')) {
					return;
				}
				$booking_id = (int) get_post_meta($balance_order_id, 'mpwpb_balance_of_booking_id', true);
				if (!$booking_id) {
					return;
				}
				if (get_post_meta($balance_order_id, 'mpwpb_balance_applied', true) === 'yes') {
					return;
				}
				$amount = (float) MPWPB_Native_Order::get_total($balance_order_id);
				self::record_payment($booking_id, $amount, __('Balance paid online.', 'service-booking-manager'), MPWPB_Booking_History::ACTION_BALANCE_PAID);
				update_post_meta($balance_order_id, 'mpwpb_balance_applied', 'yes');
			}
		}
		new MPWPB_Partial_Payment();
	}
