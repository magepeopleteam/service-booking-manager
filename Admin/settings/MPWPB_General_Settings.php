<?php
	/*
   * @Author 		engr.sumonazma@gmail.com
   * Copyright: 	mage-people.com
   */
	if (!defined('ABSPATH')) {
		die;
	} // Cannot access pages directly.
	if (!class_exists('MPWPB_General_Settings')) {
		class MPWPB_General_Settings {
			public function __construct() {
				add_action('add_mpwpb_settings_tab_content', [$this, 'general_settings'], 10, 1);
				add_action('mpwpb_settings_save', [$this, 'save_general_settings'], 10, 1);
			}
			public function general_settings($post_id) {
				$title = MP_Global_Function::get_post_info($post_id, 'mpwpb_shortcode_title');
				$sub_title = MP_Global_Function::get_post_info($post_id, 'mpwpb_shortcode_sub_title');
				?>
				<div class="tabsItem" data-tabs="#mpwpb_general_info">
					<h2 class="h4 text-primary px-0"><?php esc_html_e('General Information Settings', 'service-booking-manager'); ?></h2>
					
					<section class="component d-flex justify-content-between align-items-center mb-2">
                        <div class="w-50 d-flex justify-content-between align-items-center">
                            <label class=""><?php esc_html_e('Add To Cart Form Shortcode : ', 'service-booking-manage'); ?> <i class="fas fa-question-circle tool-tips"><span><?php MPWPB_Settings::info_text('mpwpb_short_code'); ?></span></i></label>
                        </div>
                        <div class="w-50 d-flex justify-content-end align-items-center">
							<code> [service-booking post_id="<?php echo esc_html($post_id); ?>"]</code>
                        </div>
                    </section>
					
					<section class="component d-flex justify-content-between align-items-center mb-2">
                        <div class="w-50 d-flex justify-content-between align-items-center">
                            <label for=""><?php esc_html_e('Service Title', 'service-booking-manager'); ?> <i class="fas fa-question-circle tool-tips"></i></label>
                            <div class=" d-flex justify-content-between">
								<input type="text" name="mpwpb_shortcode_title" class="formControl" value="<?php echo esc_attr($title); ?>" placeholder="<?php esc_attr_e('Service Title', 'service-booking-manager'); ?>"/>
                            </div>
                        </div>
                        <div class="w-50 ms-5 d-flex justify-content-between align-items-center">
                            <label for=""><?php esc_html_e('Service sub title', 'service-booking-manager'); ?> <i class="fas fa-question-circle tool-tips"></i></label>
                            <div class=" d-flex justify-content-between align-items-center">
								<input type="text" name="mpwpb_shortcode_sub_title" class="formControl" value="<?php echo esc_attr($sub_title); ?>" placeholder="<?php esc_attr_e('Service Sub Title', 'service-booking-manager'); ?>"/>
                            </div>
                        </div>
                    </section>
				</div>
				<?php
			}
			public function save_general_settings($post_id) {
				if (get_post_type($post_id) == MPWPB_Function::get_cpt()) {
					$title = MP_Global_Function::get_submit_info('mpwpb_shortcode_title');
					update_post_meta($post_id, 'mpwpb_shortcode_title', $title);
					$sub_title = MP_Global_Function::get_submit_info('mpwpb_shortcode_sub_title');
					update_post_meta($post_id, 'mpwpb_shortcode_sub_title', $sub_title);
				}
			}
		}
		new MPWPB_General_Settings();
	}