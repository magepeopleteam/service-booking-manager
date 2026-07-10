<?php
	if (!defined('ABSPATH')) {
		die;
	}
	if (!class_exists('MPWPB_Coupon_Scheduling_Staff_Settings')) {
		class MPWPB_Coupon_Scheduling_Staff_Settings {
			public function __construct() {
				add_action('add_mpwpb_coupon_tab_content', [$this, 'render'], 10, 1);
			}
			public function render($post_id) {
				$day_restriction = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_coupon_booking_day_restriction', 'none');
				$date_mode = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_coupon_booking_date_mode', 'none');
				$booking_dates = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_coupon_booking_dates', []);
				$booking_dates_str = is_array($booking_dates) ? implode(', ', $booking_dates) : '';
				$time_mode = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_coupon_time_mode', 'none');
				$time_bucket = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_coupon_time_bucket', 'morning');
				$time_start = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_coupon_time_range_start', '');
				$time_end = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_coupon_time_range_end', '');
				?>
				<div class="tabsItem" data-tabs="#mpwpb_coupon_scheduling_staff">
					<header>
						<h2><?php esc_html_e('Scheduling & Staff', 'service-booking-manager'); ?></h2>
						<span><?php esc_html_e('Restrict this coupon to certain booking dates/times, and (Pro) certain staff.', 'service-booking-manager'); ?></span>
					</header>
					<section class="section">
						<h3><?php esc_html_e('Day Restriction', 'service-booking-manager'); ?></h3>
						<label class="label">
							<p><?php esc_html_e('Only Valid For', 'service-booking-manager'); ?></p>
							<select name="mpwpb_coupon_booking_day_restriction">
								<option value="none" <?php selected($day_restriction, 'none'); ?>><?php esc_html_e('Any Day', 'service-booking-manager'); ?></option>
								<option value="weekdays" <?php selected($day_restriction, 'weekdays'); ?>><?php esc_html_e('Weekdays Only', 'service-booking-manager'); ?></option>
								<option value="weekends" <?php selected($day_restriction, 'weekends'); ?>><?php esc_html_e('Weekends Only', 'service-booking-manager'); ?></option>
							</select>
						</label>
					</section>
					<section class="section">
						<h3><?php esc_html_e('Specific Dates', 'service-booking-manager'); ?></h3>
						<label class="label">
							<p><?php esc_html_e('Mode', 'service-booking-manager'); ?></p>
							<select name="mpwpb_coupon_booking_date_mode" id="mpwpb_coupon_booking_date_mode">
								<option value="none" <?php selected($date_mode, 'none'); ?>><?php esc_html_e('No Date Restriction', 'service-booking-manager'); ?></option>
								<option value="allowlist" <?php selected($date_mode, 'allowlist'); ?>><?php esc_html_e('Only These Dates', 'service-booking-manager'); ?></option>
								<option value="blacklist" <?php selected($date_mode, 'blacklist'); ?>><?php esc_html_e('Blackout These Dates', 'service-booking-manager'); ?></option>
							</select>
						</label>
						<label class="label" id="mpwpb_coupon_booking_dates_wrap" style="display: <?php echo esc_attr($date_mode === 'none' ? 'none' : 'block'); ?>;">
							<p><?php esc_html_e('Dates (comma-separated, YYYY-MM-DD)', 'service-booking-manager'); ?></p>
							<input type="text" name="mpwpb_coupon_booking_dates" value="<?php echo esc_attr($booking_dates_str); ?>" placeholder="2026-12-25, 2026-12-31"/>
						</label>
					</section>
					<section class="section">
						<h3><?php esc_html_e('Time Restriction', 'service-booking-manager'); ?></h3>
						<label class="label">
							<p><?php esc_html_e('Mode', 'service-booking-manager'); ?></p>
							<select name="mpwpb_coupon_time_mode" id="mpwpb_coupon_time_mode">
								<option value="none" <?php selected($time_mode, 'none'); ?>><?php esc_html_e('Any Time', 'service-booking-manager'); ?></option>
								<option value="bucket" <?php selected($time_mode, 'bucket'); ?>><?php esc_html_e('Time of Day', 'service-booking-manager'); ?></option>
								<option value="range" <?php selected($time_mode, 'range'); ?>><?php esc_html_e('Specific Time Range', 'service-booking-manager'); ?></option>
							</select>
						</label>
						<label class="label mpwpb_coupon_time_bucket_wrap" style="display: <?php echo esc_attr($time_mode === 'bucket' ? 'block' : 'none'); ?>;">
							<p><?php esc_html_e('Bucket', 'service-booking-manager'); ?></p>
							<select name="mpwpb_coupon_time_bucket">
								<option value="morning" <?php selected($time_bucket, 'morning'); ?>><?php esc_html_e('Morning (12:00am–11:59am)', 'service-booking-manager'); ?></option>
								<option value="afternoon" <?php selected($time_bucket, 'afternoon'); ?>><?php esc_html_e('Afternoon (12:00pm–4:59pm)', 'service-booking-manager'); ?></option>
								<option value="evening" <?php selected($time_bucket, 'evening'); ?>><?php esc_html_e('Evening (5:00pm–11:59pm)', 'service-booking-manager'); ?></option>
							</select>
						</label>
						<label class="label mpwpb_coupon_time_range_wrap" style="display: <?php echo esc_attr($time_mode === 'range' ? 'block' : 'none'); ?>;">
							<p><?php esc_html_e('From', 'service-booking-manager'); ?></p>
							<input type="time" name="mpwpb_coupon_time_range_start" value="<?php echo esc_attr($time_start); ?>"/>
						</label>
						<label class="label mpwpb_coupon_time_range_wrap" style="display: <?php echo esc_attr($time_mode === 'range' ? 'block' : 'none'); ?>;">
							<p><?php esc_html_e('To', 'service-booking-manager'); ?></p>
							<input type="time" name="mpwpb_coupon_time_range_end" value="<?php echo esc_attr($time_end); ?>"/>
						</label>
					</section>
					<?php $this->render_staff_section($post_id); ?>
				</div>
				<script>
					jQuery(function ($) {
						$('#mpwpb_coupon_booking_date_mode').on('change', function () {
							$('#mpwpb_coupon_booking_dates_wrap').toggle($(this).val() !== 'none');
						});
						$('#mpwpb_coupon_time_mode').on('change', function () {
							var mode = $(this).val();
							$('.mpwpb_coupon_time_bucket_wrap').toggle(mode === 'bucket');
							$('.mpwpb_coupon_time_range_wrap').toggle(mode === 'range');
						});
					});
				</script>
				<?php
			}
			private function render_staff_section($post_id) {
				if (!MPWPB_Global_Function::is_pro_active()) {
					?>
					<section class="section">
						<h3><?php esc_html_e('Staff Restriction', 'service-booking-manager'); ?></h3>
						<span><?php esc_html_e('Restricting a coupon to specific staff requires the Pro version.', 'service-booking-manager'); ?></span>
					</section>
					<?php
					return;
				}
				$staff_scope = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_coupon_staff_scope', 'all');
				$staff_ids = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_coupon_staff_ids', []);
				if (!is_array($staff_ids)) {
					$staff_ids = [];
				}
				$users = get_users(['role__in' => ['mpwpb_staff']]);
				?>
				<section class="section">
					<h3><?php esc_html_e('Staff Restriction', 'service-booking-manager'); ?></h3>
					<label class="label">
						<p><?php esc_html_e('Applies To', 'service-booking-manager'); ?></p>
						<select name="mpwpb_coupon_staff_scope" id="mpwpb_coupon_staff_scope">
							<option value="all" <?php selected($staff_scope, 'all'); ?>><?php esc_html_e('All Staff', 'service-booking-manager'); ?></option>
							<option value="include" <?php selected($staff_scope, 'include'); ?>><?php esc_html_e('Only Selected Staff', 'service-booking-manager'); ?></option>
							<option value="exclude" <?php selected($staff_scope, 'exclude'); ?>><?php esc_html_e('Exclude Selected Staff', 'service-booking-manager'); ?></option>
						</select>
					</label>
					<label class="label" id="mpwpb_coupon_staff_ids_wrap" style="display: <?php echo esc_attr($staff_scope === 'all' ? 'none' : 'block'); ?>;">
						<p><?php esc_html_e('Select Staff', 'service-booking-manager'); ?></p>
						<select name="mpwpb_coupon_staff_ids[]" multiple size="6" style="min-width:280px;">
							<?php foreach ($users as $user): ?>
								<option value="<?php echo esc_attr($user->ID); ?>" <?php selected(in_array((int) $user->ID, array_map('intval', $staff_ids), true), true); ?>><?php echo esc_html($user->display_name); ?></option>
							<?php endforeach; ?>
						</select>
					</label>
				</section>
				<script>
					jQuery(function ($) {
						$('#mpwpb_coupon_staff_scope').on('change', function () {
							$('#mpwpb_coupon_staff_ids_wrap').toggle($(this).val() !== 'all');
						});
					});
				</script>
				<?php
			}
		}
		new MPWPB_Coupon_Scheduling_Staff_Settings();
	}
