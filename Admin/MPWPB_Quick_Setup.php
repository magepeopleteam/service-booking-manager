<?php
	/*
   * @Author 		engr.sumonazma@gmail.com
   * Copyright: 	mage-people.com
   */
	if (!defined('ABSPATH')) {
		die;
	} // Cannot access pages directly.
	if (!class_exists('MPWPB_Quick_Setup')) {
		class MPWPB_Quick_Setup {
			public function __construct() {
				add_action('admin_menu', array($this, 'quick_setup_menu'));
			}
			public function quick_setup_menu() {
				add_submenu_page('edit.php?post_type=mpwpb_item', __('Quick Setup', 'service-booking-manager'), '<span style="color:#10dd10">' . esc_html__('Quick Setup', 'service-booking-manager') . '</span>', 'manage_options', 'mpwpb_quick_setup', array($this, 'quick_setup'));
			}
			public function quick_setup() {
				if (isset($_POST['mpwpb_quick_setup_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['mpwpb_quick_setup_nonce'])), 'mpwpb_quick_setup_nonce')) {
					if (isset($_POST['finish_quick_setup'])) {
						$label = isset($_POST['mpwpb_label']) ? sanitize_text_field(wp_unslash($_POST['mpwpb_label'])) : 'service-booking-manager';
						$slug = isset($_POST['mpwpb_slug']) ? sanitize_text_field(wp_unslash($_POST['mpwpb_slug'])) : 'service-booking-manager';
						$general_settings_data = get_option('mpwpb_general_settings');
						$update_general_settings_arr = [
							'label' => $label,
							'slug' => $slug
						];
						$new_general_settings_data = is_array($general_settings_data) ? array_replace($general_settings_data, $update_general_settings_arr) : $update_general_settings_arr;
						update_option('mpwpb_general_settings', $new_general_settings_data);
						$payment_type = isset($_POST['mpwpb_payment_method_type']) ? sanitize_text_field(wp_unslash($_POST['mpwpb_payment_method_type'])) : '';
						if (in_array($payment_type, ['woocommerce', 'custom'], true)) {
							$payment_settings = get_option('mpwpb_payment_method_settings');
							$payment_settings = is_array($payment_settings) ? $payment_settings : [];
							$payment_settings['payment_method_type'] = $payment_type;
							update_option('mpwpb_payment_method_settings', $payment_settings);
						}
						flush_rewrite_rules();
						wp_redirect(admin_url('edit.php?post_type=mpwpb_item&page=mpwpb_service_list'));
					}
				}
				?>
                <div class="mpStyle mpwpb_style mep-quick-setup">
                    <div class="_dShadow_6_adminLayout">
                        <form method="post" action="">
							<?php wp_nonce_field('mpwpb_quick_setup_nonce', 'mpwpb_quick_setup_nonce'); ?>
                            <div class="mpwpb_tabs_next">
                                <div class="tabListsNext _max_700_mAuto">
                                    <div data-tabs-target-next="#mpwpb_qs_welcome" class="tabItemNext" data-open-text="1" data-close-text=" " data-open-icon="" data-close-icon="fas fa-check" data-add-class="success">
                                        <h4 class="circleIcon" data-class>
                                            <span class="mp_zero" data-icon></span>
                                            <span class="mp_zero" data-text>1</span>
                                        </h4>
                                        <h6 class="circleTitle" data-class><?php esc_html_e('Welcome', 'service-booking-manager'); ?></h6>
                                    </div>
                                    <div data-tabs-target-next="#mpwpb_qs_general" class="tabItemNext" data-open-text="2" data-close-text="" data-open-icon="" data-close-icon="fas fa-check" data-add-class="success">
                                        <h4 class="circleIcon" data-class>
                                            <span class="mp_zero" data-icon></span>
                                            <span class="mp_zero" data-text>2</span>
                                        </h4>
                                        <h6 class="circleTitle" data-class><?php esc_html_e('General', 'service-booking-manager'); ?></h6>
                                    </div>
                                    <div data-tabs-target-next="#mpwpb_qs_done" class="tabItemNext" data-open-text="3" data-close-text="" data-open-icon="" data-close-icon="fas fa-check" data-add-class="success">
                                        <h4 class="circleIcon" data-class>
                                            <span class="mp_zero" data-icon></span>
                                            <span class="mp_zero" data-text>3</span>
                                        </h4>
                                        <h6 class="circleTitle" data-class><?php esc_html_e('Done', 'service-booking-manager'); ?></h6>
                                    </div>
                                </div>
                                <div class="tabsContentNext _infoLayout_mT">
									<?php
										$this->setup_welcome_content();
										$this->setup_general_content();
										$this->setup_content_done();
									?>
                                </div>
								<div class="justifyBetween">
                                    <button type="button" class="mpBtn nextTab_prev">
                                        <span>&longleftarrow;<?php esc_html_e('Previous', 'service-booking-manager'); ?></span>
                                    </button>
                                    <div></div>
                                    <button type="button" class="themeButton nextTab_next">
                                        <span><?php esc_html_e('Next', 'service-booking-manager'); ?>&longrightarrow;</span>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
				<?php
			}
			public function setup_welcome_content() {
				$payment_type = MPWPB_Global_Function::get_payment_method_type();
				$wc_available = MPWPB_Global_Function::check_woocommerce() == 1;
				?>
                <div data-tabs-next="#mpwpb_qs_welcome">
                    <h2><?php esc_html_e('Service Booking Manager', 'service-booking-manager'); ?></h2>
                    <p class="mTB_xs"><?php esc_html_e('Welcome! Choose how you want to accept payments for bookings, then continue.', 'service-booking-manager'); ?></p>
                    <div class="_mT">
						<?php if ($wc_available) { ?>
                            <label class="_fullWidth">
                                <input type="radio" name="mpwpb_payment_method_type" value="woocommerce" <?php checked($payment_type, 'woocommerce'); ?> />
								<?php esc_html_e('WooCommerce', 'service-booking-manager'); ?>
                            </label>
						<?php } ?>
                        <label class="_fullWidth">
                            <input type="radio" name="mpwpb_payment_method_type" value="custom" <?php checked($payment_type, 'custom'); ?> />
							<?php esc_html_e('Custom Payment Method (Stripe / PayPal / Offline)', 'service-booking-manager'); ?>
                        </label>
                        <i class="info_text">
                            <span class="fas fa-info-circle"></span>
							<?php esc_html_e('You can configure Stripe, PayPal, and Offline payment, and change this at any time from Settings > Payment Method.', 'service-booking-manager'); ?>
                        </i>
                    </div>
                </div>
				<?php
			}
			public function setup_general_content() {
				$label = MPWPB_Global_Function::get_settings('mpwpb_general_settings', 'label', 'Service Booking');
				$slug = MPWPB_Global_Function::get_settings('mpwpb_general_settings', 'slug', 'service-booking');
				?>
                <div data-tabs-next="#mpwpb_qs_general">
                    <div class="section">
                        <h2><?php esc_html_e('General settings', 'service-booking-manager'); ?></h2>
                        <p class="mTB_xs"><?php esc_html_e('Choose some general option.', 'service-booking-manager'); ?></p>
                        <div class="_mT">
                            <label class="_fullWidth">
                                <span class="min_300"><?php esc_html_e('Service Booking Manager Label:', 'service-booking-manager'); ?></span>
                                <input type="text" class="formControl" name="mpwpb_label" value='<?php echo esc_attr($label); ?>'/>
                            </label>
                            <i class="info_text">
                                <span class="fas fa-info-circle"></span>
								<?php esc_html_e('It will change the Service Booking Manager post type label on the entire plugin.', 'service-booking-manager'); ?>
                            </i>
                            <label class="_fullWidth">
                            <span
                                class="min_300"><?php esc_html_e('Service Booking Manager Slug:', 'service-booking-manager'); ?></span>
                                <input type="text" class="formControl" name="mpwpb_slug" value='<?php echo esc_attr($slug); ?>'/>
                            </label>
                            <i class="info_text">
                                <span class="fas fa-info-circle"></span>
								<?php esc_html_e('It will change the Service Booking Manager slug on the entire plugin. Remember after changing this slug you need to flush permalinks. Just go to Settings->Permalinks hit the Save Settings button', 'service-booking-manager'); ?>
                            </i>
                        </div>
                    </div>
                </div>
				<?php
			}
			public function setup_content_done() {
				?>
                <div data-tabs-next="#mpwpb_qs_done">
                    <h2><?php esc_html_e('Finalize Setup', 'service-booking-manager'); ?></h2>
                    <p class="mTB_xs"><?php esc_html_e('You are about to Finish & Save service-booking-manager For Woocommerce Plugin setup process', 'service-booking-manager'); ?></p>
                    <div class="mT allCenter">
                        <button type="submit" name="finish_quick_setup"
                                class="themeButton"><?php esc_html_e('Finish & Save', 'service-booking-manager'); ?></button>
                    </div>
                </div>
				<?php
			}
		}
		new MPWPB_Quick_Setup();
	}