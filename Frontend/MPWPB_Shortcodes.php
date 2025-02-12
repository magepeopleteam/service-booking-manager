<?php
	/*
   * @Author 		engr.sumonazma@gmail.com
   * Copyright: 	mage-people.com
   */
	if ( ! defined( 'ABSPATH' ) ) {
		die;
	} // Cannot access pages directly.
	if ( ! class_exists( 'MPWPB_Shortcodes' ) ) {
		class MPWPB_Shortcodes {
			public function __construct() {
				add_shortcode( 'service-booking', array( $this, 'service_booking' ) );
			}
			public function service_booking( $attribute ) {
				ob_start();
				$defaults = array(
					'post_id' => '',
				);
				$params   = shortcode_atts( $defaults, $attribute );
				$post_id  = $params['post_id'];
				if ( $post_id ) {
					include( MPWPB_Function::details_template_path( $post_id ) );
				}
				return ob_get_clean();
			}
		}
		new MPWPB_Shortcodes();
	}