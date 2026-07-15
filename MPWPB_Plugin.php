<?php
	/**
	 * Plugin Name: Appointment Booking Plugin for WooCommerce – WpBookingly | All-in-One Service Manager
	 * Plugin URI: http://mage-people.com
	 * Description: A complete solution for Any kind of service booking.
	 * Version: 1.3.1
	 * Author: MagePeople Team
	 * Author URI: http://www.mage-people.com/
	 * Text Domain: service-booking-manager
	 * Domain Path: /languages/
	 * License: GPLv2 or later
 	 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
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
				if (!defined('MPWPB_VERSION')) {
					define('MPWPB_VERSION', '1.3.1');
				}
				require_once MPWPB_PLUGIN_DIR . '/mp_global/MPWPB_Global_File_Load.php';
				add_action('activated_plugin', array($this, 'activation_redirect'), 90, 1);
				require_once MPWPB_PLUGIN_DIR . '/inc/MPWPB_Dependencies.php';
				$this->appsero_init_tracker_service_booking_manager();
			}
			public function appsero_init_tracker_service_booking_manager() {
				if ( ! class_exists( 'Appsero\Client' ) ) {
					require_once __DIR__ . '/lib/appsero/src/Client.php';
				}			
				$client = new Appsero\Client( '969083cc-730a-49a5-ad81-e24ace3fbacf', 'Service Booking &amp; Scheduling Solution | All-in-one Booking Systems', __FILE__ );			
				// Active insights
				$client->insights()->init();
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
