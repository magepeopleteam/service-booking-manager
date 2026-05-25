<?php
	if (!defined('ABSPATH')) {
		die;
	} // Cannot access pages directly.
	/**
	 * Class MPWPB_Wc_Checkout_Default
	 *
	 * @since 1.0
	 *
	 * */
	if (!class_exists('MPWPB_Wc_Checkout_Default')) {
		class MPWPB_Wc_Checkout_Default {
			private $error;
			public function __construct() {
				$this->error = new WP_Error();
				add_action('mpwpb_wc_checkout_tab', array($this, 'tab_item'));
				add_action('mpwpb_wc_checkout_tab_content', array($this, 'tab_content'), 10, 1);
				add_action('mpwpb_save_checkout_fields_settings', [$this, 'mpwpb_save_checkout_fields_settings']);
				//add_action('wp_loaded', array( $this,'apply' ), 7  );
				add_action('admin_notices', array($this, 'mp_admin_notice'));
			}
			public function tab_item() {
				?>
                <li class="tab-item" data-tabs-target="#mpwpb_wc_checkout_settings"><i class="dashicons dashicons-admin-generic text-primary"></i> Checkout Settings <i class="i i-chevron-right dashicons dashicons-arrow-right-alt2"></i></li>
				<?php
			}
			public function tab_content($contents) {
				$check_order_additional_information_section = '';
				if (MPWPB_Wc_Checkout_Fields_Helper::hide_checkout_order_additional_information_section()) {
					$check_order_additional_information_section = 'checked';
				}
				$check_order_review_section = '';
				if (MPWPB_Wc_Checkout_Fields_Helper::hide_checkout_order_review_section()) {
					$check_order_review_section = 'checked';
				}
				?>
                <div class="tab-content" id="mpwpb_wc_checkout_settings">
                    <h2>Checkout Settings</h2>
                    <!-- <table class="wc_gateways wp-list-table widefat striped"> -->
                    <div>
                        <form method="POST">
                            <input type="hidden" name="action" value="mpwpb_wc_checkout_settings"/>
                            <table class="wc_gateways wp-list-table widefat striped">
                                <tbody>
                                <tr>
                                    <td><label for="hide_checkout_order_additional_information"><span class="span-checkout-setting"><?php esc_html_e('Hide Order Additional Information Section', 'service-booking-manager') ?></span></label></td>
                                    <td><?php MPWPB_Wc_Checkout_Fields::switch_button('hide_checkout_order_additional_information', 'checkoutSettingsSwitchButton', 'hide_checkout_order_additional_information', $check_order_additional_information_section, null); ?></td>
                                </tr>
                                <tr>
                                    <td><label for="hide_checkout_order_review"><span class="span-checkout-setting"><?php esc_html_e('Hide Order Review Section', 'service-booking-manager') ?></span></label></td>
                                    <td><?php MPWPB_Wc_Checkout_Fields::switch_button('hide_checkout_order_review', 'checkoutSettingsSwitchButton', 'hide_checkout_order_review', $check_order_review_section, null); ?></td>
                                </tr>
                                </tbody>
                            </table>
                            <div class="action-button">
                                <p class="submit">
									<?php wp_nonce_field('mpwpb_wc_checkout_settings', 'mpwpb_wc_checkout_settings_nonce'); ?>
                                    <input type="submit" name="submit" class="button-primary" value="Submit">
                                </p>
                            </div>
                        </form>
                    </div>
                </div>
				<?php
			}
			public function mpwpb_save_checkout_fields_settings() {
				if (!current_user_can('administrator')) {
					wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'service-booking-manager'));
				}
				$action = isset($_POST['action']) ? sanitize_text_field(wp_unslash($_POST['action'])) : null;
				if (isset($action) && $action == 'mpwpb_wc_checkout_settings') {
					if (check_admin_referer('mpwpb_wc_checkout_settings', 'mpwpb_wc_checkout_settings_nonce')) {
						$hide_checkout_order_additional_information = isset($_POST['hide_checkout_order_additional_information']) ? sanitize_text_field(wp_unslash($_POST['hide_checkout_order_additional_information'])) : null;
						$hide_checkout_order_review = isset($_POST['hide_checkout_order_review']) ? sanitize_text_field(wp_unslash($_POST['hide_checkout_order_review'])) : null;
						$options = get_option('mpwpb_custom_checkout_fields');
						if (!is_array($options)) {
							$options = array();
						}
						$options['hide_checkout_order_additional_information'] = $hide_checkout_order_additional_information;
						$options['hide_checkout_order_review'] = $hide_checkout_order_review;
						update_option('mpwpb_custom_checkout_fields', $options);
					}
					wp_redirect(admin_url('edit.php?post_type=' . MPWPB_Function::get_cpt() . '&page=mpwpb_wc_checkout_fields'));
				}
			}
			public function mp_admin_notice() {
				MPWPB_Wc_Checkout_Fields::mp_error_notice($this->error);
			}
		}
		new MPWPB_Wc_Checkout_Default();
	}