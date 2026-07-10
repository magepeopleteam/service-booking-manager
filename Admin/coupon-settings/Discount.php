<?php
	if (!defined('ABSPATH')) {
		die;
	}
	if (!class_exists('MPWPB_Coupon_Discount_Settings')) {
		class MPWPB_Coupon_Discount_Settings {
			public function __construct() {
				add_action('add_mpwpb_coupon_tab_content', [$this, 'render'], 10, 1);
			}
			public function render($post_id) {
				$type = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_coupon_discount_type', 'fixed');
				$value = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_coupon_discount_value', 0);
				?>
				<div class="tabsItem" data-tabs="#mpwpb_coupon_discount">
					<header>
						<h2><?php esc_html_e('Discount', 'service-booking-manager'); ?></h2>
						<span><?php esc_html_e('Choose how this coupon discounts a booking.', 'service-booking-manager'); ?></span>
					</header>
					<section class="section">
						<label class="label">
							<p><?php esc_html_e('Discount Type', 'service-booking-manager'); ?></p>
							<select name="mpwpb_coupon_discount_type" id="mpwpb_coupon_discount_type">
								<option value="fixed" <?php selected($type, 'fixed'); ?>><?php esc_html_e('Fixed Discount ($ off)', 'service-booking-manager'); ?></option>
								<option value="percentage" <?php selected($type, 'percentage'); ?>><?php esc_html_e('Percentage Discount (% off)', 'service-booking-manager'); ?></option>
								<option value="fixed_price" <?php selected($type, 'fixed_price'); ?>><?php esc_html_e('Fixed Booking Price (matched service becomes a flat price)', 'service-booking-manager'); ?></option>
								<option value="free" <?php selected($type, 'free'); ?>><?php esc_html_e('Free Booking (100% off)', 'service-booking-manager'); ?></option>
							</select>
						</label>
					</section>
					<section class="section" id="mpwpb_coupon_discount_value_wrap" style="display: <?php echo esc_attr($type === 'free' ? 'none' : 'block'); ?>;">
						<label class="label">
							<p id="mpwpb_coupon_discount_value_label">
								<?php
								if ($type === 'percentage') {
									esc_html_e('Percentage Off (%)', 'service-booking-manager');
								} elseif ($type === 'fixed_price') {
									esc_html_e('Flat Price', 'service-booking-manager');
								} else {
									esc_html_e('Amount Off', 'service-booking-manager');
								}
								?>
							</p>
							<input type="number" step="0.01" min="0" name="mpwpb_coupon_discount_value" value="<?php echo esc_attr($value); ?>"/>
						</label>
					</section>
				</div>
				<script>
					jQuery(function ($) {
						$('#mpwpb_coupon_discount_type').on('change', function () {
							var isFree = $(this).val() === 'free';
							$('#mpwpb_coupon_discount_value_wrap').toggle(!isFree);
						});
					});
				</script>
				<?php
			}
		}
		new MPWPB_Coupon_Discount_Settings();
	}
