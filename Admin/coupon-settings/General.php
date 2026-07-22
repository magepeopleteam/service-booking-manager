<?php
	if (!defined('ABSPATH')) {
		die;
	}
	if (!class_exists('MPWPB_Coupon_General_Settings')) {
		class MPWPB_Coupon_General_Settings {
			public function __construct() {
				add_action('add_mpwpb_coupon_tab_content', [$this, 'render'], 10, 1);
			}
			public function render($post_id) {
				$code = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_coupon_code', '');
				$description = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_coupon_description', '');
				$start_date = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_coupon_start_date', '');
				$expiry_date = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_coupon_expiry_date', '');
				?>
				<div class="tabsItem" data-tabs="#mpwpb_coupon_general">
					<header>
						<h2><?php esc_html_e('General', 'service-booking-manager'); ?></h2>
						<span><?php esc_html_e('Coupon code and validity window. The coupon name (post title) is set at the top of this screen.', 'service-booking-manager'); ?></span>
					</header>
					<section class="section">
						<label class="label">
							<p><?php esc_html_e('Coupon Code', 'service-booking-manager'); ?> <b class="mpwpb-coupon-required">*</b></p>
							<input type="text" name="mpwpb_coupon_code" value="<?php echo esc_attr($code); ?>" placeholder="<?php esc_attr_e('e.g. SUMMER25', 'service-booking-manager'); ?>" maxlength="64" pattern="[A-Za-z0-9][A-Za-z0-9_-]*" style="text-transform:uppercase;" required/>
						</label>
					</section>
					<section class="section">
						<label class="label">
							<p><?php esc_html_e('Description', 'service-booking-manager'); ?></p>
							<textarea rows="3" name="mpwpb_coupon_description"><?php echo esc_textarea($description); ?></textarea>
						</label>
					</section>
					<section class="section">
						<label class="label">
							<p><?php esc_html_e('Start Date', 'service-booking-manager'); ?></p>
							<input type="date" name="mpwpb_coupon_start_date" value="<?php echo esc_attr($start_date); ?>"/>
						</label>
						<label class="label">
							<p><?php esc_html_e('Expiry Date', 'service-booking-manager'); ?></p>
							<input type="date" name="mpwpb_coupon_expiry_date" value="<?php echo esc_attr($expiry_date); ?>"/>
						</label>
					</section>
					<section class="section">
						<p><?php esc_html_e('Status is controlled by the Publish/Draft state of this post (top-right panel). Published = active, Draft = inactive.', 'service-booking-manager'); ?></p>
					</section>
				</div>
				<?php
			}
		}
		new MPWPB_Coupon_General_Settings();
	}
