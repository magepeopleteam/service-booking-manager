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
			}
			private function load_file(): void {
				require_once MPWPB_PLUGIN_DIR . '/Frontend/MPWPB_Shortcodes.php';
				require_once MPWPB_PLUGIN_DIR . '/Frontend/MPWPB_Details_Layout.php';
				require_once MPWPB_PLUGIN_DIR . '/Frontend/MPWPB_Woocommerce.php';
				require_once MPWPB_PLUGIN_DIR . '/Frontend/MPWPB_Wc_Checkout_Fields_Helper.php';
				require_once MPWPB_PLUGIN_DIR . '/Frontend/MPWPB_Static_Template.php';
				require_once MPWPB_PLUGIN_DIR . '/Frontend/MPWPB_User_Dashboard.php';
				require_once MPWPB_PLUGIN_DIR . '/Frontend/MPWPB_Waiting_List.php';
				require_once MPWPB_PLUGIN_DIR . '/Frontend/MPWPB_Recurring_Booking.php';
				require_once MPWPB_PLUGIN_DIR . '/Frontend/MPWPB_Staff_Booking.php';

			}
			public function load_single_template( $template ): string {
				global $post;
				if ( $post->post_type && $post->post_type == MPWPB_Function::get_cpt()) {
					$template = MPWPB_Function::template_path( 'single_page/mpwpb_details.php' );
				}
				return $template;
			}
		}
		new MPWPB_Frontend();
	}