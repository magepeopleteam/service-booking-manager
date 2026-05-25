<?php
	/*
   * @Author 		engr.sumonazma@gmail.com
   * Copyright: 	mage-people.com
   */
	if (!defined('ABSPATH')) {
		die;
	} // Cannot access pages directly.
	if (!class_exists('MPWPB_Gallery_Settings')) {
		class MPWPB_Gallery_Settings {
			public function __construct() {
				add_action('add_mpwpb_settings_tab_content', [$this, 'gallery_settings'], 10, 1);
			}
			public function gallery_settings($post_id) {
				$display = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_display_slider', 'off');
				$active = $display == 'off' ? '' : 'mActive';
				$checked = $display == 'off' ? '' : 'checked';
				$image_ids = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_slider_images', array());
				?>
				<div class="tabsItem" data-tabs="#mpwpb_settings_gallery">
					<h5 class="dFlex">
						<span class="mR"><?php esc_html_e('On/Off Slider', 'service-booking-manager'); ?></span>
						<?php MPWPB_Custom_Layout::switch_button('mpwpb_display_slider', $checked); ?>
					</h5>
					<?php MPWPB_Settings::info_text('mpwpb_display_slider'); ?>
					<div class="divider"></div>
					<div data-collapse="#mpwpb_display_slider" class="<?php echo esc_attr($active); ?>">
						<table>
							<tbody>
							<tr>
								<th><?php esc_html_e('Gallery Images ', 'service-booking-manager'); ?></th>
								<td colspan="3"><?php do_action('mpwpb_add_multi_image', 'mpwpb_slider_images', $image_ids); ?></td>
							</tr>
							<tr>
								<td colspan="4"><?php MPWPB_Settings::info_text('mpwpb_slider_images'); ?></td>
							</tr>
							</tbody>
						</table>
					</div>
				</div>
				<?php
			}
		}
		new MPWPB_Gallery_Settings();
	}