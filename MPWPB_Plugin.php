<?php
	/**
	 * Plugin Name: Appointment Booking Plugin for WooCommerce – All-in-One Service Manager
	 * Plugin URI: http://mage-people.com
	 * Description: A complete solution for Any kind of service booking.
	 * Version: 1.4.1
	 * Author: MagePeople Team
	 * Author URI: http://www.mage-people.com/
	 * Text Domain: service-booking-manager
	 * Domain Path: /languages/
	 * License: GPLv2 or later
 	 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
	 */
	if (!defined('ABSPATH'))
		die;

	require_once __DIR__ . '/vendor/appneck/wordpress-sdk/appneck-wordpress-sdk/appneck-sdk.php';
	appneck_sdk_load_latest();

	$GLOBALS['my_plugin_sdk'] = \Appneck\Sdk\Sdk::bootstrap(
		'pk_YEXYRSDXmlkmpDxXzjs9XsoHwtyEgAnn',  // your API key
		'sk_rvKJ3tyofYzZsCOz7zqLYQ4JZSSQD61lAvPcM4YCqrXLb9lR', // your product secret
		'https://appneck.com',                  // the Appneck server URL
		__FILE__                                // so the SDK can hook activation/deactivation
	);

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
				if (!defined('MPWPB_VERSION')) {
					define('MPWPB_VERSION', '1.4.1');
				}
				require_once MPWPB_PLUGIN_DIR . '/mp_global/MPWPB_Global_File_Load.php';
				add_action('activated_plugin', array($this, 'activation_redirect'), 90, 1);
				require_once MPWPB_PLUGIN_DIR . '/inc/MPWPB_Dependencies.php';
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
					wp_safe_redirect(admin_url('edit.php?post_type=mpwpb_item&page=mpwpb_service_list'));
					exit;
				}
			}
			public static function plugin_activate() {
				set_transient('mpwpb_plugin_activated', true, 30);
				// Activation runs after the current request's init hook. Register the
				// service post type now so its permalink rules exist before flushing.
				if (class_exists('MPWPB_CPT')) {
					MPWPB_CPT::register_service_post_type();
				}
				// Auto-create the Custom Payment "My Account" page, same
				// convention WooCommerce uses for its own "My Account" page
				// on activation -- MPWPB_Custom_Payment_My_Account is
				// already required/instantiated by this point (loaded via
				// inc/MPWPB_Dependencies.php, above, before WordPress fires
				// this activation callback).
				if (class_exists('MPWPB_Custom_Payment_My_Account')) {
					MPWPB_Custom_Payment_My_Account::maybe_create_page();
				}
				flush_rewrite_rules();
			}
		}
		register_activation_hook(__FILE__, ['MPWPB_Plugin', 'plugin_activate']);
		new MPWPB_Plugin();
	}
