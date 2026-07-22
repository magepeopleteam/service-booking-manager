<?php
	/*
	 * GDPR tab on the shared Settings screen (same mpwpb_settings_sec_reg /
	 * mpwpb_settings_sec_fields filters used by every other tab there, e.g.
	 * MPWPB_Settings_Global::global_sec_reg()). Always registered/visible --
	 * this is deliberately the ONE place the master "Enable GDPR Feature"
	 * toggle lives. The customer data-request review/approve table
	 * (Admin/MPWPB_Gdpr_Requests.php::render_requests_table()) is rendered
	 * at the bottom of the Pro plugin's "GDPR Compliance Tools" page
	 * (service-booking-manager-pro/admin/MPWPB_GDPR_Tools.php::render_gdpr_page())
	 * instead of here, so there's one place admins go for GDPR action
	 * (Tools), separate from where the feature is configured (this tab).
	 */
	if (!defined('ABSPATH')) {
		die;
	} // Cannot access pages directly.
	if (!class_exists('MPWPB_Gdpr_Settings')) {
		class MPWPB_Gdpr_Settings {
			public function __construct() {
				add_filter('mpwpb_settings_sec_reg', array($this, 'sec_reg'), 95);
				add_filter('mpwpb_settings_sec_fields', array($this, 'sec_fields'), 15);
				add_action('wsa_form_top_mpwpb_gdpr_settings', array($this, 'output_styles'));
			}
			/**
			 * do_settings_fields() (WordPress core) applies each field's
			 * 'class' arg -- defaulted to its field name in
			 * MPWPB_Setting_API::admin_init() -- to that field's <tr>, so
			 * .privacy_consent_text targets this specific row.
			 */
			public function output_styles(): void {
				?>
				<style>.privacy_consent_text input.formControl{min-width:460px !important; }</style>
				<?php
			}
			public function sec_reg($default_sec): array {
				$default_sec[] = array(
					'id' => 'mpwpb_gdpr_settings',
					'icon' => 'mi mi-shield-check',
					'title' => esc_html__('GDPR', 'service-booking-manager'),
				);
				return $default_sec;
			}
			public function sec_fields($default_fields): array {
				$default_fields['mpwpb_gdpr_settings'] = apply_filters('filter_mpwpb_gdpr_settings', array(
					array(
						'name' => 'enable_gdpr',
						'label' => esc_html__('Enable GDPR Feature', 'service-booking-manager'),
						'desc' => esc_html__('Turns on the cookie consent banner and adds privacy consent checkboxes to the booking form. Off by default.', 'service-booking-manager'),
						'type' => 'checkbox',
						'default' => 'off',
					),
					array(
						'name' => 'cookie_banner_message',
						'label' => esc_html__('Cookie Banner Message', 'service-booking-manager'),
						'desc' => esc_html__('Shown in the cookie consent banner on the frontend.', 'service-booking-manager'),
						'type' => 'textarea',
						'default' => esc_html__('We use cookies to remember your booking details and improve your experience. Do you accept?', 'service-booking-manager'),
					),
					array(
						'name' => 'cookie_accept_text',
						'label' => esc_html__('Accept Button Text', 'service-booking-manager'),
						'type' => 'text',
						'default' => esc_html__('Accept', 'service-booking-manager'),
					),
					array(
						'name' => 'cookie_reject_text',
						'label' => esc_html__('Reject Button Text', 'service-booking-manager'),
						'type' => 'text',
						'default' => esc_html__('Reject', 'service-booking-manager'),
					),
					array(
						'name' => 'privacy_policy_page_id',
						'label' => esc_html__('Privacy Policy Page', 'service-booking-manager'),
						'desc' => esc_html__('Linked from the Privacy Policy checkbox on the booking form.', 'service-booking-manager'),
						'type' => 'pages',
						'default' => (int) get_option('wp_page_for_privacy_policy'),
					),
					array(
						'name' => 'privacy_consent_text',
						'label' => esc_html__('Privacy Policy Checkbox Text', 'service-booking-manager'),
						'type' => 'text',
						'default' => esc_html__('I agree to the Privacy Policy.', 'service-booking-manager'),
					),
					array(
						'name' => 'data_consent_text',
						'label' => esc_html__('Data Processing Checkbox Text', 'service-booking-manager'),
						'type' => 'text',
						'default' => esc_html__('I consent to my personal data being processed for this booking.', 'service-booking-manager'),
					),
				));
				return $default_fields;
			}
		}
		new MPWPB_Gdpr_Settings();
	}
