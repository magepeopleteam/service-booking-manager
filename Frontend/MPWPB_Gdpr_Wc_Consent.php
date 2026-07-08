<?php
	/*
	 * Mirrors the two optional GDPR consent checkboxes rendered on the native
	 * checkout form (Frontend/MPWPB_Native_Checkout.php::render_embedded_form())
	 * onto the standard WooCommerce checkout page, for sites running in
	 * WooCommerce payment mode instead. Entirely inert unless both
	 * WooCommerce mode and the GDPR feature are active.
	 */
	if (!defined('ABSPATH')) {
		die;
	} // Cannot access pages directly.
	if (!class_exists('MPWPB_Gdpr_Wc_Consent')) {
		class MPWPB_Gdpr_Wc_Consent {
			public function __construct() {
				if (!MPWPB_Global_Function::is_wc_payment_mode() || !MPWPB_Global_Function::is_gdpr_enabled()) {
					return;
				}
				add_action('woocommerce_review_order_before_submit', array($this, 'render_consent_fields'));
				add_action('woocommerce_checkout_update_order_meta', array($this, 'save_consent_fields'), 10, 2);
			}
			public function render_consent_fields(): void {
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
				<?php
			}
			/**
			 * Both checkboxes are optional (render_consent_fields() never
			 * marks them required) -- just record whatever the customer
			 * chose, same meta keys as the native checkout so either payment
			 * mode leaves the same audit trail shape.
			 */
			public function save_consent_fields($order_id, $data): void {
				update_post_meta($order_id, 'mpwpb_privacy_policy_consent', isset($_POST['mpwpb_privacy_consent']) ? 'yes' : 'no');
				update_post_meta($order_id, 'mpwpb_data_processing_consent', isset($_POST['mpwpb_data_consent']) ? 'yes' : 'no');
			}
		}
		new MPWPB_Gdpr_Wc_Consent();
	}
