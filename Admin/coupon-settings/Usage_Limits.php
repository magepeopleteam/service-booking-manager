<?php
	if (!defined('ABSPATH')) {
		die;
	}
	if (!class_exists('MPWPB_Coupon_Usage_Limits_Settings')) {
		class MPWPB_Coupon_Usage_Limits_Settings {
			public function __construct() {
				add_action('add_mpwpb_coupon_tab_content', [$this, 'render'], 10, 1);
			}
			public function render($post_id) {
				$limit_total = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_coupon_usage_limit_total', '');
				$limit_per_customer = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_coupon_usage_limit_per_customer', '');
				$used = (int) MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_coupon_usage_count', 0);
				?>
				<div class="tabsItem" data-tabs="#mpwpb_coupon_usage_limits">
					<header>
						<h2><?php esc_html_e('Usage Limits', 'service-booking-manager'); ?></h2>
						<span><?php esc_html_e('Cap how many times this coupon can be redeemed.', 'service-booking-manager'); ?></span>
					</header>
					<section class="section">
						<label class="label">
							<p><?php esc_html_e('Total Usage Limit', 'service-booking-manager'); ?></p>
							<input type="number" step="1" min="0" name="mpwpb_coupon_usage_limit_total" value="<?php echo esc_attr($limit_total); ?>" placeholder="<?php esc_attr_e('Unlimited', 'service-booking-manager'); ?>"/>
						</label>
						<label class="label">
							<p><?php esc_html_e('Usage Limit Per Customer', 'service-booking-manager'); ?></p>
							<input type="number" step="1" min="0" name="mpwpb_coupon_usage_limit_per_customer" value="<?php echo esc_attr($limit_per_customer); ?>" placeholder="<?php esc_attr_e('Unlimited', 'service-booking-manager'); ?>"/>
						</label>
					</section>
					<section class="section">
						<p><?php
							printf(
								/* translators: %d: number of times this coupon has been used so far */
								esc_html__('Used %d time(s) so far.', 'service-booking-manager'),
								(int) $used
							);
						?></p>
					</section>
				</div>
				<?php
			}
		}
		new MPWPB_Coupon_Usage_Limits_Settings();
	}
