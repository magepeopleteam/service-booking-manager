<?php
	/**
	 * Plugin Name: Service Booking Manager
	 * Plugin URI: http://mage-people.com
	 * Description: A complete solution for Any kind of service booking.
	 * Version: 1.0.2
	 * Author: MagePeople Team
	 * Author URI: http://www.mage-people.com/
	 * Text Domain: service-booking-manager
	 * Domain Path: /languages/
	 * WC requires at least: 3.0.9
	 * WC tested up to: 5.0
	 */
	if (!defined('ABSPATH')) {
		die;
	} // Cannot access pages directly.
	if (!class_exists('MPWPB_Plugin')) {
		class MPWPB_Plugin {
			public function __construct() {
				$this->load_plugin();
			}
			private function load_plugin() {
				include_once(ABSPATH . 'wp-admin/includes/plugin.php');
				if (!defined('MPWPB_PLUGIN_DIR')) {
					define('MPWPB_PLUGIN_DIR', dirname(__FILE__));
				}
				if (!defined('MPWPB_PLUGIN_URL')) {
					define('MPWPB_PLUGIN_URL', plugins_url() . '/' . plugin_basename(dirname(__FILE__)));
				}
				$this->load_global_file();
				if (MP_Global_Function::check_woocommerce() == 1) {
					add_action('activated_plugin', array($this, 'activation_redirect'), 90, 1);
					self::on_activation_page_create();
					require_once MPWPB_PLUGIN_DIR . '/inc/MPWPB_Dependencies.php';
				}
				else {
					require_once MPWPB_PLUGIN_DIR . '/Admin/MPWPB_Quick_Setup.php';
					add_action('admin_notices', [$this, 'woocommerce_not_active']);
					add_action('activated_plugin', array($this, 'activation_redirect_setup'), 90, 1);
				}
			}
			public function load_global_file() {
				require_once MPWPB_PLUGIN_DIR . '/inc/global/MP_Global_Function.php';
				require_once MPWPB_PLUGIN_DIR . '/inc/global/MP_Global_Style.php';
				require_once MPWPB_PLUGIN_DIR . '/inc/global/MP_Custom_Layout.php';
				require_once MPWPB_PLUGIN_DIR . '/inc/global/MP_Custom_Slider.php';
				require_once MPWPB_PLUGIN_DIR . '/inc/global/MP_Select_Icon_image.php';
			}
			public function activation_redirect($plugin) {
				if ($plugin == plugin_basename(__FILE__)) {
					flush_rewrite_rules();
					exit(wp_redirect(admin_url('edit.php?post_type=mpwpb_item&page=mpwpb_quick_setup')));
				}
			}
			public function activation_redirect_setup($plugin) {
				if ($plugin == plugin_basename(__FILE__)) {
					exit(wp_redirect(admin_url('admin.php?post_type=mpwpb_item&page=mpwpb_quick_setup')));
				}
			}
			public static function on_activation_page_create() {
				if (!MP_Global_Function::get_page_by_slug('mpwpb-order-details')) {
					$add_page = array(
						'post_type' => 'page',
						'post_name' => 'mpwpb-order-details',
						'post_title' => esc_html__('Order Details', 'service-booking-manager'),
						'post_content' => '[mpwpb-order-details]',
						'post_status' => 'publish',
					);
					wp_insert_post($add_page);
				}
			}
			public function woocommerce_not_active() {
				$wc_install_url = get_admin_url() . 'plugin-install.php?s=woocommerce&tab=search&type=term';
				printf('<div class="error" style="background:red; color:#fff;"><p>%s</p></div>', __('You Must Install WooCommerce Plugin before activating Service Booking Manager, Because It is dependent on Woocommerce Plugin. <a class="btn button" href=' . $wc_install_url . '>Click Here to Install</a>'));
			}
		}
		new MPWPB_Plugin();
	}