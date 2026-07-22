<?php
	/*
   * Per-service Tax Settings -- off by default. Works with or without
   * WooCommerce: the admin picks a tax class and types a rate right here
   * (no trip to WooCommerce's own Settings > Tax screen needed) --
   *  - WooCommerce active: sync_wc_tax_rate() (MPWPB_Tax_Helper) pushes that
   *    rate into a real WooCommerce tax rate and turns woocommerce_calc_taxes
   *    on automatically; sync_hidden_product_tax() below syncs the class
   *    onto the hidden WC product so WooCommerce's own cart/checkout engine
   *    calculates it automatically, same as any real product.
   *  - WooCommerce inactive/not installed: MPWPB_Native_Checkout/
   *    MPWPB_Native_Order still work -- MPWPB_Tax_Helper::calculate() falls
   *    back to a plain percentage of the price using this same stored rate.
   */
	if (!defined('ABSPATH')) {
		die;
	}
	if (!class_exists('Tax_Settings')) {
		class Tax_Settings {
			public function __construct() {
				add_action('add_mpwpb_settings_tab_content', [$this, 'tax_settings']);
				add_action('mpwpb_settings_save', [$this, 'save_tax_settings_meta'], 10, 1);
				// Priority 100: after MPWPB_Hidden_Product::run_link_product_on_save()
				// (priority 99 on the same save_post hook), which unconditionally
				// writes the hidden product's _tax_status/_tax_class from
				// $_POST['_tax_status']/['_tax_class'] -- fields nothing currently
				// submits, so it always writes 'none'/''. Running after it means
				// this sync always has the final say instead of being silently
				// clobbered back to "no tax" on every save.
				add_action('save_post', [$this, 'sync_hidden_product_tax'], 100, 1);
			}

			public function save_tax_settings_meta($post_id) {
				if (!isset($_POST['mpwpb_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['mpwpb_nonce'])), 'mpwpb_nonce')) {
					return;
				}
				$enabled = isset($_POST['mpwpb_tax_enabled']) ? 'on' : 'off';
				$tax_class = isset($_POST['mpwpb_tax_class']) ? sanitize_text_field(wp_unslash($_POST['mpwpb_tax_class'])) : '';
				$tax_rate = isset($_POST['mpwpb_tax_rate']) ? (float) wp_unslash($_POST['mpwpb_tax_rate']) : 0.0;
				$tax_rate = max(0.0, round($tax_rate, 4));
				update_post_meta($post_id, 'mpwpb_tax_enabled', $enabled);
				update_post_meta($post_id, 'mpwpb_tax_class', $tax_class);
				update_post_meta($post_id, 'mpwpb_tax_rate', $tax_rate);
				// No-ops entirely when WooCommerce isn't active -- see
				// MPWPB_Tax_Helper::sync_wc_tax_rate(). Only pushed when tax is
				// actually on for this service; a disabled service doesn't touch
				// the shared class rate other services might still be using.
				if ($enabled === 'on' && class_exists('MPWPB_Tax_Helper')) {
					MPWPB_Tax_Helper::sync_wc_tax_rate($tax_class, $tax_rate);
				}
			}

			public function sync_hidden_product_tax($post_id) {
				if (get_post_type($post_id) !== MPWPB_Function::get_cpt()) {
					return;
				}
				if (!isset($_POST['mpwpb_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['mpwpb_nonce'])), 'mpwpb_nonce')) {
					return;
				}
				$product_id = (int) MPWPB_Global_Function::get_post_info($post_id, 'link_wc_product');
				if (!$product_id || get_post_type($product_id) !== 'product') {
					return;
				}
				$enabled = get_post_meta($post_id, 'mpwpb_tax_enabled', true) === 'on';
				$tax_class = (string) get_post_meta($post_id, 'mpwpb_tax_class', true);
				update_post_meta($product_id, '_tax_status', $enabled ? 'taxable' : 'none');
				update_post_meta($product_id, '_tax_class', $enabled ? $tax_class : '');
				if (function_exists('wc_delete_product_transients')) {
					wc_delete_product_transients($product_id);
				}
			}

			public function tax_settings($post_id) {
				$enabled = get_post_meta($post_id, 'mpwpb_tax_enabled', true) === 'on';
				$tax_class = get_post_meta($post_id, 'mpwpb_tax_class', true);
				$tax_rate = get_post_meta($post_id, 'mpwpb_tax_rate', true);
				$tax_class_options = class_exists('MPWPB_Tax_Helper')
					? MPWPB_Tax_Helper::get_tax_class_options()
					: ['' => __('Standard', 'service-booking-manager')];
				?>
				<div class="tabsItem" data-tabs="#mpwpb_tax_settings">
					<header>
						<h2><?php esc_html_e('Tax Settings', 'service-booking-manager'); ?></h2>
						<span><?php esc_html_e('Charge tax on this service -- works with or without WooCommerce.', 'service-booking-manager'); ?></span>
					</header>

					<section>
						<label class="label">
							<div>
								<p><?php esc_html_e('Enable Tax', 'service-booking-manager'); ?></p>
								<span><?php esc_html_e('Off by default. Pick a tax class and rate below -- if WooCommerce is active it is synced there automatically, no need to open WooCommerce\'s own tax settings.', 'service-booking-manager'); ?></span>
							</div>
							<div>
								<label class="roundSwitchLabel">
									<input type="checkbox" class="mpwpb_tax_enabled" name="mpwpb_tax_enabled" <?php checked($enabled); ?>>
									<span class="roundSwitch" data-collapse-target="#mpwpb_tax_class_row"></span>
								</label>
							</div>
						</label>
					</section>

					<section id="mpwpb_tax_class_row" data-collapse="#mpwpb_tax_class_row" class="<?php echo $enabled ? 'mActive' : ''; ?>" style="display: <?php echo $enabled ? 'block' : 'none'; ?>">
						<label class="label">
							<div>
								<p><?php esc_html_e('Tax Class', 'service-booking-manager'); ?></p>
								<span><?php esc_html_e('A rate is shared by every service using the same class -- pick a different class if this service needs a different rate.', 'service-booking-manager'); ?></span>
							</div>
							<div>
								<select name="mpwpb_tax_class" class="formControl" id="mpwpb_tax_class_select">
									<?php foreach ($tax_class_options as $slug => $label) : ?>
										<option value="<?php echo esc_attr($slug); ?>" <?php selected($tax_class, $slug); ?>><?php echo esc_html($label); ?></option>
									<?php endforeach; ?>
								</select>
							</div>
						</label>
						<label class="label" id="mpwpb_tax_rate_row">
							<div>
								<p><?php esc_html_e('Tax Rate (%)', 'service-booking-manager'); ?></p>
								<span><?php esc_html_e('The percentage charged for the tax class selected above.', 'service-booking-manager'); ?></span>
							</div>
							<div>
								<input type="number" min="0" max="100" step="0.0001" name="mpwpb_tax_rate" class="formControl" style="max-width:120px;" value="<?php echo esc_attr($tax_rate !== '' ? $tax_rate : ''); ?>" placeholder="<?php esc_attr_e('e.g. 8.5', 'service-booking-manager'); ?>"/>
							</div>
						</label>
					</section>
				</div>
				<?php
			}
		}
		new Tax_Settings();
	}
