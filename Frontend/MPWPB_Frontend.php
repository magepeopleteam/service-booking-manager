<?php
	/*
   * @Author 		engr.sumonazma@gmail.com
   * Copyright: 	mage-people.com
   */
	if ( ! defined( 'ABSPATH' ) ) {
		die;
	} // Cannot access pages directly.
	if ( ! class_exists( 'MPWPB_Frontend' ) ) {
		class MPWPB_Frontend {
			public function __construct() {
				$this->load_file();
				add_filter( 'single_template', array( $this, 'load_single_template' ) );
				add_filter( 'body_class', array( $this, 'add_plugin_body_class' ) );
			}
			private function load_file(): void {
				require_once MPWPB_PLUGIN_DIR . '/Frontend/MPWPB_Shortcodes.php';
				require_once MPWPB_PLUGIN_DIR . '/Frontend/MPWPB_Details_Layout.php';
				require_once MPWPB_PLUGIN_DIR . '/Frontend/MPWPB_Woocommerce.php';
				require_once MPWPB_PLUGIN_DIR . '/Frontend/MPWPB_Wc_Checkout_Fields_Helper.php';
				require_once MPWPB_PLUGIN_DIR . '/Frontend/MPWPB_Native_Cart.php';
				require_once MPWPB_PLUGIN_DIR . '/Frontend/MPWPB_Stripe_Gateway.php';
				require_once MPWPB_PLUGIN_DIR . '/Frontend/MPWPB_Paypal_Gateway.php';
				require_once MPWPB_PLUGIN_DIR . '/Frontend/MPWPB_Native_Checkout.php';
				require_once MPWPB_PLUGIN_DIR . '/Frontend/MPWPB_Static_Template.php';
				require_once MPWPB_PLUGIN_DIR . '/Frontend/MPWPB_User_Dashboard.php';
				require_once MPWPB_PLUGIN_DIR . '/Frontend/MPWPB_Wc_Account_Order_Actions.php';
				require_once MPWPB_PLUGIN_DIR . '/Frontend/MPWPB_Waiting_List.php';
				require_once MPWPB_PLUGIN_DIR . '/Frontend/MPWPB_Recurring_Booking.php';
				require_once MPWPB_PLUGIN_DIR . '/Frontend/MPWPB_Staff_Booking.php';
				require_once MPWPB_PLUGIN_DIR . '/Frontend/MPWPB_Gdpr_Cookie_Banner.php';
				require_once MPWPB_PLUGIN_DIR . '/Frontend/MPWPB_Gdpr_Wc_Consent.php';
				require_once MPWPB_PLUGIN_DIR . '/Frontend/MPWPB_Custom_Payment_My_Account.php';

			}
			public function load_single_template( $template ): string {
				global $post;
				if ( $post->post_type && $post->post_type == MPWPB_Function::get_cpt()) {
					$template = MPWPB_Function::template_path( 'single_page/mpwpb_details.php' );
				}
				return $template;
			}
			/**
			 * Adds a "mpwpb-active" body class on any front-end page where
			 * this plugin actually renders something -- its own service CPT
			 * single page, the native (non-WooCommerce) checkout URL, or a
			 * post/page containing one of its shortcodes -- so themes/CSS
			 * can target "any page with our plugin's content" without
			 * needing to enumerate every possible page individually.
			 */
			public function add_plugin_body_class( $classes ): array {
				if ( is_singular( MPWPB_Function::get_cpt() ) || get_query_var( 'mpwpb_checkout' ) ) {
					$classes[] = 'mpwpb-active';
					return $classes;
				}
				$post = get_post();
				if ( $post instanceof WP_Post ) {
					$shortcodes = array( 'service-booking', 'mpwpb-user-dashboard', 'custom_payment_my_account', 'mpwpb_booking_confirmation' );
					foreach ( $shortcodes as $shortcode ) {
						if ( has_shortcode( $post->post_content, $shortcode ) ) {
							$classes[] = 'mpwpb-active';
							break;
						}
					}
				}
				return $classes;
			}
		}
		new MPWPB_Frontend();
	}