<?php
	/**
	 * Plugin Name: WordPress Service Booking & Scheduling Plugin | All-in-one Booking Systems -WpBookingly
	 * Plugin URI: http://mage-people.com
	 * Description: A complete solution for Any kind of service booking.
	 * Version: 1.1.3
	 * Author: MagePeople Team
	 * Author URI: http://www.mage-people.com/
	 * Text Domain: service-booking-manager
	 * Domain Path: /languages/
	 */
	if (!defined('ABSPATH'))
		die;
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
				} else {
					require_once MPWPB_PLUGIN_DIR . '/Admin/MPWPB_Quick_Setup.php';
					add_action('admin_notices', [$this, 'woocommerce_not_active']);
					add_action('activated_plugin', array($this, 'activation_redirect_setup'), 90, 1);
				}
			}
			public function activation_redirect($plugin) {
				if ($plugin == plugin_basename(__FILE__)) {
					if (!wp_roles()->is_role('mpwpb_staff')) {
						add_role('mpwpb_staff', esc_html__('Service Staffs', 'service-booking-manager'), array(
							'read' => true, // True allows that capability
							'edit_posts' => true,
							'create_posts' => false,
							'delete_posts' => false, // Use false to explicitly deny
						));
					}
					flush_rewrite_rules();
					exit(esc_url_raw(wp_redirect(admin_url('edit.php?post_type=mpwpb_item&page=mpwpb_quick_setup'))));
				}
			}
			public function activation_redirect_setup($plugin) {
				if ($plugin == plugin_basename(__FILE__)) {
					exit(esc_url_raw(wp_redirect(admin_url('admin.php?post_type=mpwpb_item&page=mpwpb_quick_setup'))));
				}
			}
			public function woocommerce_not_active() {
				$wc_install_url = get_admin_url() . 'plugin-install.php?s=woocommerce&tab=search&type=term';
				$text = esc_html__('You Must Install WooCommerce Plugin before activating Service Booking Manager, Because It is dependent on Woocommerce Plugin.', 'service-booking-manager') . '<a class="btn button" href="' . esc_html($wc_install_url) . '">' . esc_html__('Click Here to Install', 'service-booking-manager') . '</a>';
				printf('<div class="error" style="background:red; color:#fff;"><p>%s</p></div>', wp_kses_post($text));
			}
			public static function activate() {
				update_option('rewrite_rules', '');
			}
		}
	}
	if (class_exists('MPWPB_Plugin')) {
		register_activation_hook(__FILE__, array('MPWPB_Plugin', 'activate'));
		new MPWPB_Plugin();
	}