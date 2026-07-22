<?php
	/*
   * @Author 		engr.sumonazma@gmail.com
   * Copyright: 	mage-people.com
   */
	if (!defined('ABSPATH')) {
		die;
	} // Cannot access pages directly.
	if (!class_exists('MPWPB_Shortcodes')) {
		class MPWPB_Shortcodes {
			public function __construct() {
				add_shortcode('service-booking', array($this, 'service_booking'));
			}
			public function service_booking($attribute) {
                $shortcode = 'yes';
				ob_start();
				$defaults = array(
					'post_id' => '',
				);
				$params = shortcode_atts($defaults, $attribute);
				$post_id = $params['post_id'];
				if ($post_id) {
					// Check if the current theme is a block theme
					if (function_exists('wp_is_block_theme') && wp_is_block_theme()) {
						?>
                        <div class="wp-block-group has-global-padding is-layout-constrained wp-block-group-is-layout-constrained">
                        <div class="entry-content wp-block-post-content has-global-padding is-layout-constrained wp-block-post-content-is-layout-constrained">
						<?php
					}
					?>
                    <div class="mpwpb_style mpwpb_registration_short_code mpwpb_registration mpwpb-static-template">
                        <div class="sidebar">
							<?php include(MPWPB_Function::template_path('registration/static_registration.php')); ?>
                        </div>
                    </div>
					<?php
					if (function_exists('wp_is_block_theme') && wp_is_block_theme()) {
						?>
                        </div>
                        </div>
						<?php
					}
				}
				return ob_get_clean();
			}
		}
		new MPWPB_Shortcodes();
	}