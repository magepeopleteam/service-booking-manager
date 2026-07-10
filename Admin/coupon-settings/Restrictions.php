<?php
	if (!defined('ABSPATH')) {
		die;
	}
	if (!class_exists('MPWPB_Coupon_Restrictions_Settings')) {
		class MPWPB_Coupon_Restrictions_Settings {
			public function __construct() {
				add_action('add_mpwpb_coupon_tab_content', [$this, 'render'], 10, 1);
			}
			public function render($post_id) {
				$min_total = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_coupon_min_total', '');
				$max_total = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_coupon_max_total', '');
				$min_qty = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_coupon_min_qty', '');
				$max_qty = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_coupon_max_qty', '');
				$history_restriction = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_coupon_history_restriction', 'none');
				$account_restriction = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_coupon_account_restriction', 'none');
				?>
				<div class="tabsItem" data-tabs="#mpwpb_coupon_restrictions">
					<header>
						<h2><?php esc_html_e('Restrictions', 'service-booking-manager'); ?></h2>
						<span><?php esc_html_e('Price, quantity, and customer rules a booking must meet for this coupon to apply.', 'service-booking-manager'); ?></span>
					</header>
					<section class="section">
						<h3><?php esc_html_e('Price Restriction', 'service-booking-manager'); ?></h3>
						<label class="label">
							<p><?php esc_html_e('Minimum Booking Total', 'service-booking-manager'); ?></p>
							<input type="number" step="0.01" min="0" name="mpwpb_coupon_min_total" value="<?php echo esc_attr($min_total); ?>" placeholder="<?php esc_attr_e('No minimum', 'service-booking-manager'); ?>"/>
						</label>
						<label class="label">
							<p><?php esc_html_e('Maximum Booking Total', 'service-booking-manager'); ?></p>
							<input type="number" step="0.01" min="0" name="mpwpb_coupon_max_total" value="<?php echo esc_attr($max_total); ?>" placeholder="<?php esc_attr_e('No maximum', 'service-booking-manager'); ?>"/>
						</label>
					</section>
					<section class="section">
						<h3><?php esc_html_e('Quantity Restriction', 'service-booking-manager'); ?></h3>
						<label class="label">
							<p><?php esc_html_e('Minimum Quantity', 'service-booking-manager'); ?></p>
							<input type="number" step="1" min="0" name="mpwpb_coupon_min_qty" value="<?php echo esc_attr($min_qty); ?>" placeholder="<?php esc_attr_e('No minimum', 'service-booking-manager'); ?>"/>
						</label>
						<label class="label">
							<p><?php esc_html_e('Maximum Quantity', 'service-booking-manager'); ?></p>
							<input type="number" step="1" min="0" name="mpwpb_coupon_max_qty" value="<?php echo esc_attr($max_qty); ?>" placeholder="<?php esc_attr_e('No maximum', 'service-booking-manager'); ?>"/>
						</label>
					</section>
					<section class="section">
						<h3><?php esc_html_e('Customer Restriction', 'service-booking-manager'); ?></h3>
						<label class="label">
							<p><?php esc_html_e('Booking History', 'service-booking-manager'); ?></p>
							<select name="mpwpb_coupon_history_restriction">
								<option value="none" <?php selected($history_restriction, 'none'); ?>><?php esc_html_e('No restriction', 'service-booking-manager'); ?></option>
								<option value="first_booking" <?php selected($history_restriction, 'first_booking'); ?>><?php esc_html_e('First Booking Only', 'service-booking-manager'); ?></option>
								<option value="returning" <?php selected($history_restriction, 'returning'); ?>><?php esc_html_e('Returning Customer Only', 'service-booking-manager'); ?></option>
							</select>
						</label>
						<label class="label">
							<p><?php esc_html_e('Account Status', 'service-booking-manager'); ?></p>
							<select name="mpwpb_coupon_account_restriction">
								<option value="none" <?php selected($account_restriction, 'none'); ?>><?php esc_html_e('No restriction', 'service-booking-manager'); ?></option>
								<option value="guest_only" <?php selected($account_restriction, 'guest_only'); ?>><?php esc_html_e('Guest Checkout Only', 'service-booking-manager'); ?></option>
								<option value="logged_in_only" <?php selected($account_restriction, 'logged_in_only'); ?>><?php esc_html_e('Logged-in Customers Only', 'service-booking-manager'); ?></option>
							</select>
						</label>
						<span><?php esc_html_e('Both rules apply together when set — e.g. "First Booking Only" + "Logged-in Customers Only" requires both.', 'service-booking-manager'); ?></span>
					</section>
				</div>
				<?php
			}
		}
		new MPWPB_Coupon_Restrictions_Settings();
	}
