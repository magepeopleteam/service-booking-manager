<?php
	/*
	 * Cookie consent banner: shown site-wide in wp_footer whenever GDPR is
	 * enabled (Admin/MPWPB_Gdpr_Settings.php) and the visitor hasn't already
	 * accepted/rejected in this browser. Accept/Reject is a pure client-side
	 * choice (assets/frontend/mpwpb-gdpr-cookie-banner.js writes the
	 * mpwpb_cookie_consent cookie) -- it only gates whether
	 * assets/frontend/mpwpb_registration.js is allowed to save/read the
	 * "remember my info" mpwpb_customer_info cookie on the booking form, so
	 * there's no server round-trip or nonce needed here.
	 */
	if (!defined('ABSPATH')) {
		die;
	} // Cannot access pages directly.
	if (!class_exists('MPWPB_Gdpr_Cookie_Banner')) {
		class MPWPB_Gdpr_Cookie_Banner {
			public function __construct() {
				if (!MPWPB_Global_Function::is_gdpr_enabled()) {
					return;
				}
				add_action('wp_enqueue_scripts', array($this, 'enqueue'));
				add_action('wp_footer', array($this, 'render_banner'));
			}
			public function enqueue(): void {
				wp_enqueue_style('mpwpb_gdpr_cookie_banner', MPWPB_PLUGIN_URL . '/assets/frontend/mpwpb-gdpr-cookie-banner.css', array(), time());
				wp_enqueue_script('mpwpb_gdpr_cookie_banner', MPWPB_PLUGIN_URL . '/assets/frontend/mpwpb-gdpr-cookie-banner.js', array('jquery'), time(), true);
				wp_localize_script('mpwpb_gdpr_cookie_banner', 'mpwpb_gdpr', array(
					'message' => MPWPB_Global_Function::get_gdpr_setting('cookie_banner_message', esc_html__('We use cookies to remember your booking details and improve your experience. Do you accept?', 'service-booking-manager')),
					'accept_text' => MPWPB_Global_Function::get_gdpr_setting('cookie_accept_text', esc_html__('Accept', 'service-booking-manager')),
					'reject_text' => MPWPB_Global_Function::get_gdpr_setting('cookie_reject_text', esc_html__('Reject', 'service-booking-manager')),
				));
			}
			public function render_banner(): void {
				?>
				<div class="mpwpb-gdpr-banner" id="mpwpb_gdpr_banner" style="display:none;">
					<div class="mpwpb-gdpr-banner-inner">
						<p class="mpwpb-gdpr-banner-msg" id="mpwpb_gdpr_banner_msg"></p>
						<div class="mpwpb-gdpr-banner-actions">
							<button type="button" class="mpwpb-gdpr-banner-btn mpwpb-gdpr-banner-reject" id="mpwpb_gdpr_reject"></button>
							<button type="button" class="mpwpb-gdpr-banner-btn mpwpb-gdpr-banner-accept" id="mpwpb_gdpr_accept"></button>
						</div>
					</div>
				</div>
				<?php
			}
		}
		new MPWPB_Gdpr_Cookie_Banner();
	}
