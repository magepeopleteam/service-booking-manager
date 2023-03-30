
	<?php
	if ( ! defined( 'ABSPATH' ) ) {
		die;
	} // Cannot access pages directly.
	if ( ! class_exists( 'MPWPB_FAQ_Settings' ) ) {
		class MPWPB_FAQ_Settings {
			public function __construct() {
				//add_action( 'add_mpwpb_settings_tab_content', [ $this, 'faq_settings' ],10,1 );
				//add_action( 'mpwpb_settings_save', [ $this, 'save_faq_settings' ], 10, 1 );
			}
			public function faq_settings( $post_id ) {

				$display   = MPWPB_Function::get_post_info( $post_id, 'mpwpb_display_slider', 'off' );
				$active    = $display == 'off' ? '' : 'mActive';
				$checked   = $display == 'off' ? '' : 'checked';
				$image_ids = MPWPB_Function::get_post_info( $post_id, 'mpwpb_slider_images', array() );
				?>
				<div class="tabsItem" data-tabs="#mpwpb_settings_gallery">
					<h5 class="dFlex">
						<span class="mR"><?php esc_html_e( 'On/Off Slider', 'bookingmaster' ); ?></span>
						<?php MPWPB_Layout::switch_button( 'mpwpb_display_slider', $checked ); ?>
					</h5>
					<?php MPWPB_Settings::info_text( 'mpwpb_display_slider' ); ?>
					<div class="divider"></div>
					<div data-collapse="#mpwpb_display_slider" class="<?php echo esc_attr( $active ); ?>">
						<table>
							<tbody>
							<tr>
								<th><?php esc_html_e( 'Gallery Images ', 'bookingmaster' ); ?></th>
								<td colspan="3"><?php do_action( 'mp_add_multi_image', 'mpwpb_slider_images', $image_ids ); ?></td>
							</tr>
							<tr>
								<td colspan="4"><?php MPWPB_Settings::info_text( 'mpwpb_slider_images' ); ?></td>
							</tr>
							</tbody>
						</table>
					</div>
				</div>
				<?php
			}
			public function save_faq_settings( $post_id ) {
				if ( get_post_type( $post_id ) == MPWPB_Function::get_cpt_name() ) {
					$slider = MPWPB_Function::get_submit_info( 'mpwpb_display_slider' ) ? 'on' : 'off';
					update_post_meta( $post_id, 'mpwpb_display_slider', $slider );
					$images     = MPWPB_Function::get_submit_info( 'mpwpb_slider_images');
					$all_images = explode( ',', $images );
					update_post_meta( $post_id, 'mpwpb_slider_images', $all_images );
				}
			}
		}
		new MPWPB_FAQ_Settings();
	}