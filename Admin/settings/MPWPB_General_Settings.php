<?php
	if ( ! defined( 'ABSPATH' ) ) {
		die;
	} // Cannot access pages directly.
	if ( ! class_exists( 'MPWPB_General_Settings' ) ) {
		class MPWPB_General_Settings {
			public function __construct() {
				add_action( 'add_mpwpb_settings_tab_content', [ $this, 'general_settings' ], 10, 1 );
				add_action( 'mpwpb_settings_save', [ $this, 'save_general_settings' ], 10, 1 );
			}
			public function general_settings( $post_id ) {
				?>
				<div class="tabsItem" data-tabs="#mpwpb_general_info">
					<h5><?php esc_html_e( 'General Information Settings', 'mpwpb_plugin' ); ?></h5>
					<div class="divider"></div>

				</div>
				<?php
			}
			public function save_general_settings( $post_id ) {
				if ( get_post_type( $post_id ) == MPWPB_Function::get_cpt_name() ) {
				}
			}
		}
		new MPWPB_General_Settings();
	}