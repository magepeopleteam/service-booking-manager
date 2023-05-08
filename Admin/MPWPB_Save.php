<?php
	if ( ! defined( 'ABSPATH' ) ) {
		die;
	} // Cannot access pages directly.
	if ( ! class_exists( 'MPWPB_Save' ) ) {
		class MPWPB_Save {
			public function __construct() {
				add_action( 'save_post', array( $this, 'save_settings' ), 99, 1 );
			}
			public function save_settings( $post_id ) {
				if ( ! isset( $_POST['mpwpb_nonce'] ) || ! wp_verify_nonce( $_POST['mpwpb_nonce'], 'mpwpb_nonce' ) && defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE && ! current_user_can( 'edit_post', $post_id ) ) {
					return;
				}
				do_action( 'mpwpb_settings_save', $post_id );
			}
		}
		new MPWPB_Save();
	}