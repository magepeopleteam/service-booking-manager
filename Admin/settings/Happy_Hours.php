<?php
	/*
   * Per-service Happy Hours Pricing -- off by default. One time-of-day
   * discount window per service (e.g. 20% off between 14:00-16:00),
   * applied automatically to the price whenever the customer's selected
   * appointment time falls inside it -- see MPWPB_Happy_Hours_Helper,
   * which hooks the existing 'mpwpb_price_filter' so both WooCommerce
   * and Custom Payment checkouts pick it up with no further wiring.
   */
	if (!defined('ABSPATH')) {
		die;
	}
	if (!class_exists('MPWPB_Happy_Hours_Settings')) {
		class MPWPB_Happy_Hours_Settings {
			public function __construct() {
				add_action('add_mpwpb_settings_tab_content', [$this, 'happy_hours_settings']);
				add_action('mpwpb_settings_save', [$this, 'save_happy_hours_meta'], 10, 1);
			}

			public function save_happy_hours_meta($post_id) {
				if (!isset($_POST['mpwpb_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['mpwpb_nonce'])), 'mpwpb_nonce')) {
					return;
				}
				$enabled = isset($_POST['mpwpb_happy_hours_enabled']) ? 'on' : 'off';
				$start_time = isset($_POST['mpwpb_happy_hours_start_time']) ? sanitize_text_field(wp_unslash($_POST['mpwpb_happy_hours_start_time'])) : '00:00';
				$end_time = isset($_POST['mpwpb_happy_hours_end_time']) ? sanitize_text_field(wp_unslash($_POST['mpwpb_happy_hours_end_time'])) : '23:59';
				$discount_type = (isset($_POST['mpwpb_happy_hours_discount_type']) && sanitize_text_field(wp_unslash($_POST['mpwpb_happy_hours_discount_type'])) === 'fixed') ? 'fixed' : 'percent';
				$discount_value = isset($_POST['mpwpb_happy_hours_discount_value']) ? (float) wp_unslash($_POST['mpwpb_happy_hours_discount_value']) : 0.0;
				$discount_value = max(0.0, round($discount_value, 2));
				update_post_meta($post_id, 'mpwpb_happy_hours_enabled', $enabled);
				update_post_meta($post_id, 'mpwpb_happy_hours_start_time', $start_time);
				update_post_meta($post_id, 'mpwpb_happy_hours_end_time', $end_time);
				update_post_meta($post_id, 'mpwpb_happy_hours_discount_type', $discount_type);
				update_post_meta($post_id, 'mpwpb_happy_hours_discount_value', $discount_value);
			}

			public function happy_hours_settings($post_id) {
				$enabled = get_post_meta($post_id, 'mpwpb_happy_hours_enabled', true) === 'on';
				$start_time = get_post_meta($post_id, 'mpwpb_happy_hours_start_time', true) ?: '14:00';
				$end_time = get_post_meta($post_id, 'mpwpb_happy_hours_end_time', true) ?: '16:00';
				$discount_type = get_post_meta($post_id, 'mpwpb_happy_hours_discount_type', true) === 'fixed' ? 'fixed' : 'percent';
				$discount_value = get_post_meta($post_id, 'mpwpb_happy_hours_discount_value', true);
				?>
				<div class="tabsItem" data-tabs="#mpwpb_happy_hours_settings">
					<header>
						<h2><?php esc_html_e('Happy Hours Pricing', 'service-booking-manager'); ?></h2>
						<span><?php esc_html_e('Give a discount when the customer books an appointment time inside a set window.', 'service-booking-manager'); ?></span>
					</header>

					<section>
						<label class="label">
							<div>
								<p><?php esc_html_e('Enable Happy Hours', 'service-booking-manager'); ?></p>
								<span><?php esc_html_e('Off by default. The discount is based on the appointment time the customer picks, not the time they check out.', 'service-booking-manager'); ?></span>
							</div>
							<div>
								<label class="roundSwitchLabel">
									<input type="checkbox" class="mpwpb_happy_hours_enabled" name="mpwpb_happy_hours_enabled" <?php checked($enabled); ?>>
									<span class="roundSwitch" data-collapse-target="#mpwpb_happy_hours_row"></span>
								</label>
							</div>
						</label>
					</section>

					<section id="mpwpb_happy_hours_row" data-collapse="#mpwpb_happy_hours_row" class="<?php echo $enabled ? 'mActive' : ''; ?>" style="display: <?php echo $enabled ? 'block' : 'none'; ?>">
						<label class="label">
							<div>
								<p><?php esc_html_e('Start Time', 'service-booking-manager'); ?></p>
								<span><?php esc_html_e('Appointment times at or after this time qualify.', 'service-booking-manager'); ?></span>
							</div>
							<div>
								<input type="time" name="mpwpb_happy_hours_start_time" class="formControl" style="max-width:150px;" value="<?php echo esc_attr($start_time); ?>"/>
							</div>
						</label>
						<label class="label">
							<div>
								<p><?php esc_html_e('End Time', 'service-booking-manager'); ?></p>
								<span><?php esc_html_e('Appointment times before this time qualify.', 'service-booking-manager'); ?></span>
							</div>
							<div>
								<input type="time" name="mpwpb_happy_hours_end_time" class="formControl" style="max-width:150px;" value="<?php echo esc_attr($end_time); ?>"/>
							</div>
						</label>
						<label class="label">
							<div>
								<p><?php esc_html_e('Discount Type', 'service-booking-manager'); ?></p>
								<span><?php esc_html_e('Percentage off, or a fixed amount off.', 'service-booking-manager'); ?></span>
							</div>
							<div>
								<select name="mpwpb_happy_hours_discount_type" class="formControl" style="max-width:150px;">
									<option value="percent" <?php selected($discount_type, 'percent'); ?>><?php esc_html_e('Percentage (%)', 'service-booking-manager'); ?></option>
									<option value="fixed" <?php selected($discount_type, 'fixed'); ?>><?php esc_html_e('Fixed Amount', 'service-booking-manager'); ?></option>
								</select>
							</div>
						</label>
						<label class="label">
							<div>
								<p><?php esc_html_e('Discount Value', 'service-booking-manager'); ?></p>
								<span><?php esc_html_e('e.g. 20 for 20% off, or 20 for $20 off with Fixed Amount.', 'service-booking-manager'); ?></span>
							</div>
							<div>
								<input type="number" min="0" step="0.01" name="mpwpb_happy_hours_discount_value" class="formControl" style="max-width:120px;" value="<?php echo esc_attr($discount_value !== '' ? $discount_value : ''); ?>" placeholder="<?php esc_attr_e('e.g. 20', 'service-booking-manager'); ?>"/>
							</div>
						</label>
					</section>
				</div>
				<?php
			}
		}
		new MPWPB_Happy_Hours_Settings();
	}
