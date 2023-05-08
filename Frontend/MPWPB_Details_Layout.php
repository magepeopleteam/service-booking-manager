<?php
	if ( ! defined( 'ABSPATH' ) ) {
		die;
	} // Cannot access pages directly.
	if ( ! class_exists( 'MPWPB_Details_Layout' ) ) {
		class MPWPB_Details_Layout {
			public function __construct() {
				/**************/
			}
		}
		new MPWPB_Details_Layout();
	}