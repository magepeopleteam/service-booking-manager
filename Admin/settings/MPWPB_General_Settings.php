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
				$title = MP_Global_Function::get_post_info( $post_id, 'mpwpb_shortcode_title' );
				$sub_title  = MP_Global_Function::get_post_info( $post_id, 'mpwpb_shortcode_sub_title');
				?>
				<div class="tabsItem" data-tabs="#mpwpb_general_info">
					<h5><?php esc_html_e( 'General Information Settings', 'service-booking-manager' ); ?></h5>
					<div class="divider"></div>
					<label>
						<span class="max_200"><?php esc_html_e( 'Service Title', 'service-booking-manager' ); ?></span>
						<input type="text"  name="mpwpb_shortcode_title" class="formControl" value="<?php echo esc_attr( $title ); ?>" placeholder="<?php esc_attr_e( 'Service Title', 'service-booking-manager' ); ?>"/>
					</label>
					<div class="divider"></div>
					<label>
						<span class="max_200"><?php esc_html_e( 'Service sub title', 'service-booking-manager' ); ?></span>
						<input type="text"  name="mpwpb_shortcode_sub_title" class="formControl" value="<?php echo esc_attr( $sub_title ); ?>" placeholder="<?php esc_attr_e( 'Service Sub Title', 'service-booking-manager' ); ?>"/>
					</label>
				</div>
				<?php
			}
			public function save_general_settings( $post_id ) {
				if ( get_post_type( $post_id ) == MPWPB_Function::get_cpt() ) {
					$title =MP_Global_Function::get_submit_info( 'mpwpb_shortcode_title' );
					update_post_meta( $post_id, 'mpwpb_shortcode_title', $title );
					$sub_title = MP_Global_Function::get_submit_info( 'mpwpb_shortcode_sub_title' );
					update_post_meta( $post_id, 'mpwpb_shortcode_sub_title', $sub_title );
				}
			}
		}
		new MPWPB_General_Settings();
	}