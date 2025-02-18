<?php
	/**
	 * Plugin Name: Service Booking & Scheduling Solution | All-in-one Booking Systems
	 * Plugin URI: http://mage-people.com
	 * Description: A complete solution for Any kind of service booking.
	 * Version: 1.1.3
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
				require_once MPWPB_PLUGIN_DIR . '/mp_global/MP_Global_File_Load.php';
				if (MP_Global_Function::check_woocommerce() == 1) {
					add_action('activated_plugin', array($this, 'activation_redirect'), 90, 1);
					require_once MPWPB_PLUGIN_DIR . '/inc/MPWPB_Dependencies.php';
				} else {
					require_once MPWPB_PLUGIN_DIR . '/Admin/MPWPB_Quick_Setup.php';
					 //add_action('admin_notices', [$this, 'woocommerce_not_active']);
					add_action('activated_plugin', array($this, 'activation_redirect_setup'), 90, 1);
				}
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
				?>
				<div class="error" style="background-color: #ffe0e0;">
					<p>
						<?php esc_html_e('You Must Install WooCommerce Plugin before activating Service Booking Manager, Because It is dependent on Woocommerce Plugin.', 'service-booking-manager');?>
						<a class="btn button" href="<?php esc_html($wc_install_url); ?>"><?php esc_html_e('Click Here to Install', 'service-booking-manager'); ?></a>
					</p>
				</div>
				<?php
			}
			public static function plugin_activate() {
				error_log('MPWPB_Plugin activated.', 0);
				flush_rewrite_rules();
			}
		}
		register_activation_hook(__FILE__, ['MPWPB_Plugin', 'plugin_activate']);
		new MPWPB_Plugin();
	}