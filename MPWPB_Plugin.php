<?php
	/**
	 * Plugin Name: WordPress Service Booking & Scheduling Plugin | All-in-one Booking Systems -WpBookingly
	 * Plugin URI: http://mage-people.com
	 * Description: A complete solution for Any kind of service booking.
	 * Version: 1.0.7
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
				require_once MPWPB_PLUGIN_DIR . '/mp_global/MP_Global_File_Load.php';
				if (MP_Global_Function::check_woocommerce() == 1) {
					add_action('activated_plugin', array($this, 'activation_redirect'), 90, 1);
					require_once MPWPB_PLUGIN_DIR . '/inc/MPWPB_Dependencies.php';
				}
				else {
					require_once MPWPB_PLUGIN_DIR . '/Admin/MPWPB_Quick_Setup.php';
					add_action('admin_notices', [$this, 'woocommerce_not_active']);
					add_action('activated_plugin', array($this, 'activation_redirect_setup'), 90, 1);
				}
			}
			public function activation_redirect($plugin) {
				if ($plugin == plugin_basename(__FILE__)) {
					flush_rewrite_rules();
					if(!MP_Global_Function::user_role_exists('mpwpb_staff')){
						add_role('mpwpb_staff', esc_html__('Service Staffs', 'service-booking-manager'), array(
							'read' => true, // True allows that capability
							'edit_posts' => true,
							'create_posts' => false,
							'delete_posts' => false, // Use false to explicitly deny
						));
					}
					exit(wp_redirect(admin_url('edit.php?post_type=mpwpb_item&page=mpwpb_quick_setup')));
				}
			}
			public function activation_redirect_setup($plugin) {
				if ($plugin == plugin_basename(__FILE__)) {
					exit(wp_redirect(admin_url('admin.php?post_type=mpwpb_item&page=mpwpb_quick_setup')));
				}
			}
			public function woocommerce_not_active() {
				$wc_install_url = get_admin_url() . 'plugin-install.php?s=woocommerce&tab=search&type=term';
				printf('<div class="error" style="background:red; color:#fff;"><p>%s</p></div>', __('You Must Install WooCommerce Plugin before activating Service Booking Manager, Because It is dependent on Woocommerce Plugin. <a class="btn button" href=' . $wc_install_url . '>Click Here to Install</a>'));
			}
		}
		new MPWPB_Plugin();
	}
