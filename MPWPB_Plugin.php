<?php
	/**
	 * Plugin Name: WP Bookingly
	 * Plugin URI: http://mage-people.com
	 * Description: A complete solution for car wash service.
	 * Version: 1.0.0
	 * Author: MagePeople Team
	 * Author URI: http://www.mage-people.com/
	 * Text Domain: mpwpb_plugin
	 * Domain Path: /languages/
	 * WC requires at least: 3.0.9
	 * WC tested up to: 5.0
	 */
	if ( ! defined( 'ABSPATH' ) ) {
		die;
	} // Cannot access pages directly.
	if ( ! class_exists( 'MPWPB_Plugin' ) ) {
		class MPWPB_Plugin {
			public function __construct() {
				$this->load_plugin();
			}
			private function load_plugin() {
				include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
				if ( ! defined( 'MPWPB_PLUGIN_DIR' ) ) {
					define( 'MPWPB_PLUGIN_DIR', dirname( __FILE__ ) );
				}
				if ( ! defined( 'MPWPB_PLUGIN_URL' ) ) {
					define( 'MPWPB_PLUGIN_URL', plugins_url() . '/' . plugin_basename( dirname( __FILE__ ) ) );
				}
				if ( self::check_woocommerce()==1 ) {
					add_action( 'activated_plugin', array( $this, 'activation_redirect' ), 90, 1 );
					//register_activation_hook( __FILE__, array( $this, 'on_activation_page_create' ) );
					require_once MPWPB_PLUGIN_DIR . '/inc/MPWPB_Dependencies.php';

				}
				else {
					require_once MPWPB_PLUGIN_DIR . '/Admin/MPWPB_Quick_Setup.php';
					add_action( 'admin_notices', [$this,'woocommerce_not_active'] );
					add_action( 'activated_plugin', array( $this, 'activation_redirect_setup' ), 90, 1 );
				}
				flush_rewrite_rules();
			}
			public function activation_redirect( $plugin ) {
				if ( $plugin == plugin_basename( __FILE__ ) ) {
					exit( wp_redirect( admin_url( 'edit.php?post_type=mpwpb_item&page=mpwpb_quick_setup' ) ) );
				}
			}
			public function activation_redirect_setup( $plugin ) {
				if ( $plugin == plugin_basename( __FILE__ ) ) {
					exit( wp_redirect( admin_url( 'admin.php?post_type=mpwpb_item&page=mpwpb_quick_setup' ) ) );
				}
			}
			public static function check_woocommerce(): int {
				include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
				$plugin_dir = ABSPATH . 'wp-content/plugins/woocommerce';
				if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
					return 1;
				} elseif ( is_dir( $plugin_dir ) ) {
					return 2;
				} else {
					return 0;
				}
			}
			public function woocommerce_not_active(){
				$wc_install_url = get_admin_url() . 'plugin-install.php?s=woocommerce&tab=search&type=term';
				printf( '<div class="error" style="background:red; color:#fff;"><p>%s</p></div>', __( 'You Must Install WooCommerce Plugin before activating WP Bookingly, Because It is dependent on Woocommerce Plugin. <a class="btn button" href=' . $wc_install_url . '>Click Here to Install</a>' ) );
			}
		}
		new MPWPB_Plugin();
	}