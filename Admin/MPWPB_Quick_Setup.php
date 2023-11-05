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
				$status = MP_Global_Function::check_woocommerce();
				if ($status == 1) {
					add_submenu_page('edit.php?post_type=mpwpb_item', __('Quick Setup', 'service-booking-manager'), '<span style="color:#10dd10">' . esc_html__('Quick Setup', 'service-booking-manager') . '</span>', 'manage_options', 'mpwpb_quick_setup', array($this, 'quick_setup'));
					add_submenu_page('mpwpb_item', esc_html__('Quick Setup', 'service-booking-manager'), '<span style="color:#10dd10">' . esc_html__('Quick Setup', 'service-booking-manager') . '</span>', 'manage_options', 'mpwpb_quick_setup', array($this, 'quick_setup'));
				}
				else {
					add_menu_page(esc_html__('Service Booking', 'service-booking-manager'), esc_html__('Service Booking', 'service-booking-manager'), 'manage_options', 'mpwpb_item', array($this, 'quick_setup'), 'dashicons-admin-site-alt2', 6);
					add_submenu_page('mpwpb_item', esc_html__('Quick Setup', 'service-booking-manager'), '<span style="color:#10dd17">' . esc_html__('Quick Setup', 'service-booking-manager') . '</span>', 'manage_options', 'mpwpb_quick_setup', array($this, 'quick_setup'));
				}
			}
			public function quick_setup() {
				$status = MP_Global_Function::check_woocommerce();
				if (isset($_POST['active_woo_btn'])) {
					?>
					<script>
						dLoaderBody();
					</script>
					<?php
					activate_plugin('woocommerce/woocommerce.php');
					?>
					<script>
						(function ($) {
							"use strict";
							$(document).ready(function () {
								let mpwpb_admin_location = window.location.href;
								mpwpb_admin_location = mpwpb_admin_location.replace('admin.php?post_type=mpwpb_item&page=mpwpb_quick_setup', 'edit.php?post_type=mpwpb_item&page=mpwpb_quick_setup');
								mpwpb_admin_location = mpwpb_admin_location.replace('admin.php?page=mpwpb_item', 'edit.php?post_type=mpwpb_item&page=mpwpb_quick_setup');
								mpwpb_admin_location = mpwpb_admin_location.replace('admin.php?page=mpwpb_quick_setup', 'edit.php?post_type=mpwpb_item&page=mpwpb_quick_setup');
								window.location.href = mpwpb_admin_location;
							});
						}(jQuery));
					</script>
					<?php
				}
				if (isset($_POST['install_and_active_woo_btn'])) {
					echo '<div style="display:none">';
					include_once(ABSPATH . 'wp-admin/includes/plugin-install.php');
					include_once(ABSPATH . 'wp-admin/includes/file.php');
					include_once(ABSPATH . 'wp-admin/includes/misc.php');
					include_once(ABSPATH . 'wp-admin/includes/class-wp-upgrader.php');
					$plugin = 'woocommerce';
					$api = plugins_api('plugin_information', array(
						'slug' => $plugin,
						'fields' => array(
							'short_description' => false,
							'sections' => false,
							'requires' => false,
							'rating' => false,
							'ratings' => false,
							'downloaded' => false,
							'last_updated' => false,
							'added' => false,
							'tags' => false,
							'compatibility' => false,
							'homepage' => false,
							'donate_link' => false,
						),
					));
					$title = 'title';
					$url = 'url';
					$nonce = 'nonce';
					$woocommerce_plugin = new Plugin_Upgrader(new Plugin_Installer_Skin(compact('title', 'url', 'nonce', 'plugin', 'api')));
					$woocommerce_plugin->install($api->download_link);
					activate_plugin('woocommerce/woocommerce.php');
					echo '</div>';
					?>
					<script>
						(function ($) {
							"use strict";
							$(document).ready(function () {
								let mpwpb_admin_location = window.location.href;
								mpwpb_admin_location = mpwpb_admin_location.replace('admin.php?post_type=mpwpb_item&page=mpwpb_quick_setup', 'edit.php?post_type=mpwpb_item&page=mpwpb_quick_setup');
								mpwpb_admin_location = mpwpb_admin_location.replace('admin.php?page=mpwpb_item', 'edit.php?post_type=mpwpb_item&page=mpwpb_quick_setup');
								mpwpb_admin_location = mpwpb_admin_location.replace('admin.php?page=mpwpb_quick_setup', 'edit.php?post_type=mpwpb_item&page=mpwpb_quick_setup');
								window.location.href = mpwpb_admin_location;
							});
						}(jQuery));
					</script>
					<?php
				}
				if (isset($_POST['finish_quick_setup'])) {
					$label = isset($_POST['mpwpb_label']) ? sanitize_text_field($_POST['mpwpb_label']) : 'service-booking-manager';
					$slug = isset($_POST['mpwpb_slug']) ? sanitize_text_field($_POST['mpwpb_slug']) : 'service-booking-manager';
					$general_settings_data = get_option('mpwpb_general_settings');
					$update_general_settings_arr = [
						'label' => $label,
						'slug' => $slug
					];
					$new_general_settings_data = is_array($general_settings_data) ? array_replace($general_settings_data, $update_general_settings_arr) : $update_general_settings_arr;
					update_option('mpwpb_general_settings', $new_general_settings_data);
					flush_rewrite_rules();
					wp_redirect(admin_url('edit.php?post_type=mpwpb_item'));
				}
				?>
				<div class="mpStyle">
					<div class=_dShadow_6_adminLayout">
						<form method="post" action="">
							<div class="mpTabsNext">
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
								<?php if ($status == 1) { ?>
									<div class="justifyBetween">
										<button type="button" class="mpBtn nextTab_prev">
											<span>&longleftarrow;<?php esc_html_e('Previous', 'service-booking-manager'); ?></span>
										</button>
										<div></div>
										<button type="button" class="themeButton nextTab_next">
											<span><?php esc_html_e('Next', 'service-booking-manager'); ?>&longrightarrow;</span>
										</button>
									</div>
								<?php } ?>
							</div>
						</form>
					</div>
				</div>
				<?php
			}
			public function setup_welcome_content() {
				$status = MP_Global_Function::check_woocommerce();
				?>
				<div data-tabs-next="#mpwpb_qs_welcome">
					<h2><?php esc_html_e('Service Booking Manager For Woocommerce Plugin', 'service-booking-manager'); ?></h2>
					<p class="mTB_xs"><?php esc_html_e('Service Booking Manager Plugin for WooCommerce for your site, Please go step by step and choose some options to get started.', 'service-booking-manager'); ?></p>
					<div class="_dLayout_mT_alignCenter justifyBetween">
						<h5>
							<?php if ($status == 1) {
								esc_html_e('Woocommerce already installed and activated', 'service-booking-manager');
							}
							elseif ($status == 0) {
								esc_html_e('Woocommerce need to install and active', 'service-booking-manager');
							}
							else {
								esc_html_e('Woocommerce already install , please activate it', 'service-booking-manager');
							} ?>
						</h5>
						<?php if ($status == 1) { ?>
							<h5>
								<span class="fas fa-check-circle textSuccess"></span>
							</h5>
						<?php } elseif ($status == 0) { ?>
							<button class="warningButton" type="submit"
								name="install_and_active_woo_btn"><?php esc_html_e('Install & Active Now', 'service-booking-manager'); ?></button>
						<?php } else { ?>
							<button class="themeButton" type="submit"
								name="active_woo_btn"><?php esc_html_e('Active Now', 'service-booking-manager'); ?></button>
						<?php } ?>
					</div>
				</div>
				<?php
			}
			public function setup_general_content() {
				$label = MP_Global_Function::get_settings('mpwpb_general_settings', 'label', 'Service Booking');
				$slug = MP_Global_Function::get_settings('mpwpb_general_settings', 'slug', 'service-booking');
				?>
				<div data-tabs-next="#mpwpb_qs_general">
					<div class="section">
						<h2><?php esc_html_e('General settings', 'service-booking-manager'); ?></h2>
						<p class="mTB_xs"><?php esc_html_e('Choose some general option.', 'service-booking-manager'); ?></p>
						<div class="_dLayout_mT">
							<label class="fullWidth">
								<span class="min_300"><?php esc_html_e('Service Booking Manager Label:', 'service-booking-manager'); ?></span>
								<input type="text" class="formControl" name="mpwpb_label" value='<?php echo esc_attr($label); ?>'/>
							</label>
							<i class="info_text">
								<span class="fas fa-info-circle"></span>
								<?php esc_html_e('It will change the Service Booking Manager post type label on the entire plugin.', 'service-booking-manager'); ?>
							</i>
							<div class="divider"></div>
							<label class="fullWidth">
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