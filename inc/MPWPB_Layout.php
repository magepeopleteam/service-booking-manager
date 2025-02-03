<?php
	/*
   * @Author 		engr.sumonazma@gmail.com
   * Copyright: 	mage-people.com
   */
	if (!defined('ABSPATH')) {
		die;
	} // Cannot access pages directly.
	if (!class_exists('MPWPB_Layout')) {
		class MPWPB_Layout {
			public function __construct() { }
			public static function post_select() {
				$label = MPWPB_Function::get_name();
				?>
                <label class="min_400 mpwpb_id">
                    <select name="mpwpb_id" class="formControl mp_select2" id="mpwpb_id" required>
                        <option value="0"><?php esc_html_e('Select', 'service-booking-manager') . ' ' . esc_html($label); ?></option>
						<?php
							$loop = MP_Global_Function::query_post_type(MPWPB_Function::get_cpt());
							$posts = $loop->posts;
							foreach ($posts as $post) {
								?>
                                <option value="<?php echo esc_attr($post->ID); ?>">
									<?php echo esc_html(get_the_title($post->ID)); ?>
                                </option>
								<?php
							}
							wp_reset_postdata();
						?>
                    </select>
                </label>
				<?php
			}
			/*****************************/
		}
		new MPWPB_Layout();
	}