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
				ob_start();
				$defaults = array(
					'post_id' => '',
				);
				$params = shortcode_atts($defaults, $attribute);
				$post_id = $params['post_id'];
				if ($post_id) {
					?>
                    <div class="mpStyle mpwpb_registration_short_code  mpwpb_registration mpwpb-static-template">
                        <div class="sidebar">
							<?php include(MPWPB_Function::template_path('registration/static_registration.php')); ?>
                        </div>
                    </div>
					<?php
				}
				return ob_get_clean();
			}
		}
		new MPWPB_Shortcodes();
	}