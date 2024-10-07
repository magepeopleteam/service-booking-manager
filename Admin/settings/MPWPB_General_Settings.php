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
				$mpwpb_template = MP_Global_Function::get_post_info($post_id, 'mpwpb_theme_file','default.php');
                // echo $mpwpb_template;
				?>
				<div class="tabsItem" data-tabs="#mpwpb_general_info">
					<header>
							<h2><?php esc_html_e('General Information Settings', 'service-booking-manager'); ?></h2>
							<span><?php MPWPB_Settings::info_text('mpwpb_short_code'); ?></span>
                    </header>
					<section class="section">
							<h2><?php esc_html_e('General Information Settings', 'service-booking-manager'); ?></h2>
							<span><?php MPWPB_Settings::info_text('mpwpb_short_code'); ?></span>
                    </section>
					<section>
                        <label class="label">
                            <div>
								<p><?php esc_html_e('Add To Cart Form Shortcode', 'service-booking-manage'); ?></p>
								<span><?php MPWPB_Settings::info_text('mpwpb_short_code'); ?></span>
							</div>
							<code> [service-booking post_id="<?php echo esc_html($post_id); ?>"]</code>
						</label>
                    </section>
					<section>
                        <label class="label">
							<div>
								<p><?php esc_html_e('Service Title', 'service-booking-manager'); ?></p>
								<span><?php esc_html_e('Service Title', 'service-booking-manager'); ?></span>
							</div>
                            <div class=" d-flex justify-content-between">
								<input type="text" name="mpwpb_shortcode_title" class="" value="<?php echo esc_attr($title); ?>" placeholder="<?php esc_attr_e('Service Title', 'service-booking-manager'); ?>"/>
                            </div>
                        </label>
                    </section>
					<section>
						<label class="label">
							<div>
								<p><?php esc_html_e('Service sub title', 'service-booking-manager'); ?></p>
								<span><?php esc_html_e('Service sub title', 'service-booking-manager'); ?></span>
							</div>
							<textarea rows="3" cols="50" name="mpwpb_shortcode_sub_title"><?php echo esc_attr($sub_title); ?></textarea>
							
						</label>
					</section>
                    <section>
                        <label class="label">
                            <div>
                                <p><?php esc_html_e('Service template', 'service-booking-manager'); ?></p>
                            </div>
                            <select class="" name="mpwpb_theme_file" >
                                <option disabled selected><?php esc_html_e('Please select ...', 'service-booking-manager'); ?></option>
                                <option value="default.php"  <?php echo esc_attr($mpwpb_template == 'default.php' ? 'selected' : ''); ?>><?php esc_html_e('Regular', 'service-booking-manager'); ?></option>
                                <option value="static.php" <?php echo esc_attr($mpwpb_template == 'static.php' ? 'selected' : ''); ?>><?php esc_html_e('Static', 'service-booking-manager'); ?></option>
                            </select>

                        </label>
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
					$mpwpb_template = MP_Global_Function::get_submit_info('mpwpb_theme_file','default.php');
					update_post_meta($post_id, 'mpwpb_theme_file', $mpwpb_template);
				}
			}
		}
		new MPWPB_General_Settings();
	}