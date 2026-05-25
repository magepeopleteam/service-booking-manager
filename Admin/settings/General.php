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
			}
			public function general_settings($post_id) {
				$title = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_shortcode_title');
				$sub_title = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_shortcode_sub_title');
				$mpwpb_template = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_template', 'default.php');
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
                    <section class="shortcode">
                        <label class="label">
                            <div>
                                <p><?php esc_html_e('Add To Cart Form Shortcode', 'service-booking-manager'); ?></p>
                                
                            </div>
                            <code> [service-booking post_id="<?php echo esc_html($post_id); ?>"]</code>
                        </label>
                    </section>
                    <section class="service-title">
                        <label class="label">
                            <p><?php esc_html_e('Service Title', 'service-booking-manager'); ?></p>
                            <input type="text" name="mpwpb_shortcode_title" class="" value="<?php echo esc_attr($title); ?>" placeholder="<?php esc_attr_e('Service Title', 'service-booking-manager'); ?>"/>
                        </label>
                    </section>
                    <section class="service-sub-title">
                        <label class="label">
                            <p><?php esc_html_e('Service sub title', 'service-booking-manager'); ?></p>
                            
                            <textarea rows="3" cols="50" name="mpwpb_shortcode_sub_title"><?php echo esc_attr($sub_title); ?></textarea>
                        </label>
                    </section>
                    <section class="service-template">
                        <label class="label">
                            <p><?php esc_html_e('Service template', 'service-booking-manager'); ?></p>
                            
                            <select class="" name="mpwpb_template">
                                <option disabled selected><?php esc_html_e('Please select ...', 'service-booking-manager'); ?></option>
                                <!-- <option value="default.php" <?php echo esc_attr($mpwpb_template == 'default.php' ? 'selected' : ''); ?>><?php esc_html_e('Regular', 'service-booking-manager'); ?></option> -->
                                <option value="static.php" <?php echo esc_attr($mpwpb_template == 'static.php' ? 'selected' : ''); ?>><?php esc_html_e('Static', 'service-booking-manager'); ?></option>
                            </select>
                        </label>
                    </section>
                </div>
				<?php
			}
		}
		new MPWPB_General_Settings();
	}