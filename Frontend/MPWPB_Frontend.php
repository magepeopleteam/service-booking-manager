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
				add_action( 'template_redirect', array( $this, 'init_post_data' ) );
			}
			private function load_file(): void {
				require_once MPWPB_PLUGIN_DIR . '/Frontend/MPWPB_Shortcodes.php';
				require_once MPWPB_PLUGIN_DIR . '/Frontend/MPWPB_Details_Layout.php';
				require_once MPWPB_PLUGIN_DIR . '/Frontend/MPWPB_Woocommerce.php';
				require_once MPWPB_PLUGIN_DIR . '/Frontend/MPWPB_Wc_Checkout_Fields_Helper.php';
				require_once MPWPB_PLUGIN_DIR . '/Frontend/MPWPB_Static_Template.php';
			}

			public function init_post_data() {
				global $post;
				if ( is_singular( MPWPB_Function::get_cpt() ) ) {
					if ( !isset($post) || !is_object($post) ) {
						$post = get_post( get_the_ID() );
					}
					setup_postdata( $post );
				}
			}

			public function load_single_template( $template ): string {
				global $post;
				if ( $post && is_object($post) && $post->post_type == MPWPB_Function::get_cpt() ) {
					$template_path = MPWPB_Function::template_path( 'single_page/mpwpb_details.php' );
					if ( file_exists( $template_path ) ) {
						return $template_path;
					}
				}
				return $template;
			}
		}
		new MPWPB_Frontend();
	}