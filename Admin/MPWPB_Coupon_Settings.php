<?php
	/*
   * @Author 		engr.sumonazma@gmail.com
   * Copyright: 	mage-people.com
   */
	if (!defined('ABSPATH')) {
		die;
	} // Cannot access pages directly.
	if (!class_exists('MPWPB_Coupon_Settings')) {
		class MPWPB_Coupon_Settings {
			public function __construct() {
				add_action('add_meta_boxes', [$this, 'settings_meta']);
				add_action('save_post', [$this, 'save_settings'], 99, 1);
			}
			public function settings_meta() {
				add_meta_box('mpwpb_coupon_meta_box_panel', esc_html__('Coupon Settings', 'service-booking-manager'), [$this, 'settings'], 'mpwpb_coupon', 'normal', 'high');
			}
			public function settings() {
				$post_id = get_the_ID();
				wp_nonce_field('mpwpb_coupon_nonce', 'mpwpb_coupon_nonce');
				?>
				<div class="mpwpb_style">
					<div class="mpwpb_tabs metabox">
						<div class="tabLists">
							<ul>
								<li data-tabs-target="#mpwpb_coupon_general">
									<i class="mi mi-settings"></i><?php esc_html_e('General', 'service-booking-manager'); ?>
								</li>
								<li data-tabs-target="#mpwpb_coupon_discount">
									<i class="mi mi-coins"></i><?php esc_html_e('Discount', 'service-booking-manager'); ?>
								</li>
								<li data-tabs-target="#mpwpb_coupon_services">
									<i class="mi mi-rectangle-list"></i><?php esc_html_e('Services', 'service-booking-manager'); ?>
								</li>
								<li data-tabs-target="#mpwpb_coupon_restrictions">
									<i class="mi mi-workflow-setting-alt"></i><?php esc_html_e('Restrictions', 'service-booking-manager'); ?>
								</li>
								<li data-tabs-target="#mpwpb_coupon_scheduling_staff">
									<i class="mi mi-calendar-clock"></i><?php esc_html_e('Scheduling & Staff', 'service-booking-manager'); ?>
								</li>
								<li data-tabs-target="#mpwpb_coupon_usage_limits">
									<i class="mi mi-users-alt"></i><?php esc_html_e('Usage Limits', 'service-booking-manager'); ?>
								</li>
							</ul>
						</div>
						<div class="tabsContent">
							<?php do_action('add_mpwpb_coupon_tab_content', $post_id); ?>
						</div>
					</div>
				</div>
				<?php
			}
			public function save_settings($post_id) {
				if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
					return;
				}
				if (get_post_type($post_id) !== 'mpwpb_coupon') {
					return;
				}
				if (!current_user_can('edit_post', $post_id)) {
					return;
				}
				if (!isset($_POST['mpwpb_coupon_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['mpwpb_coupon_nonce'])), 'mpwpb_coupon_nonce')) {
					return;
				}

				// General
				$code = isset($_POST['mpwpb_coupon_code']) ? MPWPB_Coupon_Function::normalize_code(wp_unslash($_POST['mpwpb_coupon_code'])) : '';
				if ($code !== '') {
					$existing_id = MPWPB_Coupon_Function::find_by_code($code);
					if ($existing_id && $existing_id !== $post_id) {
						$code = ''; // duplicate code: refuse to save it silently overwriting another coupon's code
					}
				}
				update_post_meta($post_id, 'mpwpb_coupon_code', $code);
				update_post_meta($post_id, 'mpwpb_coupon_description', isset($_POST['mpwpb_coupon_description']) ? sanitize_textarea_field(wp_unslash($_POST['mpwpb_coupon_description'])) : '');
				update_post_meta($post_id, 'mpwpb_coupon_start_date', isset($_POST['mpwpb_coupon_start_date']) ? sanitize_text_field(wp_unslash($_POST['mpwpb_coupon_start_date'])) : '');
				update_post_meta($post_id, 'mpwpb_coupon_expiry_date', isset($_POST['mpwpb_coupon_expiry_date']) ? sanitize_text_field(wp_unslash($_POST['mpwpb_coupon_expiry_date'])) : '');

				// Discount
				$discount_type = isset($_POST['mpwpb_coupon_discount_type']) ? sanitize_text_field(wp_unslash($_POST['mpwpb_coupon_discount_type'])) : 'fixed';
				if (!in_array($discount_type, ['fixed', 'percentage', 'fixed_price', 'free'], true)) {
					$discount_type = 'fixed';
				}
				update_post_meta($post_id, 'mpwpb_coupon_discount_type', $discount_type);
				update_post_meta($post_id, 'mpwpb_coupon_discount_value', isset($_POST['mpwpb_coupon_discount_value']) ? (float) sanitize_text_field(wp_unslash($_POST['mpwpb_coupon_discount_value'])) : 0);

				// Services
				$service_scope = isset($_POST['mpwpb_coupon_service_scope']) ? sanitize_text_field(wp_unslash($_POST['mpwpb_coupon_service_scope'])) : 'all';
				update_post_meta($post_id, 'mpwpb_coupon_service_scope', $service_scope === 'specific' ? 'specific' : 'all');
				$services = isset($_POST['mpwpb_coupon_services']) ? array_map('sanitize_text_field', wp_unslash((array) $_POST['mpwpb_coupon_services'])) : [];
				update_post_meta($post_id, 'mpwpb_coupon_services', array_values(array_filter($services)));

				// Restrictions
				update_post_meta($post_id, 'mpwpb_coupon_min_total', isset($_POST['mpwpb_coupon_min_total']) && $_POST['mpwpb_coupon_min_total'] !== '' ? (float) sanitize_text_field(wp_unslash($_POST['mpwpb_coupon_min_total'])) : '');
				update_post_meta($post_id, 'mpwpb_coupon_max_total', isset($_POST['mpwpb_coupon_max_total']) && $_POST['mpwpb_coupon_max_total'] !== '' ? (float) sanitize_text_field(wp_unslash($_POST['mpwpb_coupon_max_total'])) : '');
				update_post_meta($post_id, 'mpwpb_coupon_min_qty', isset($_POST['mpwpb_coupon_min_qty']) && $_POST['mpwpb_coupon_min_qty'] !== '' ? (int) sanitize_text_field(wp_unslash($_POST['mpwpb_coupon_min_qty'])) : '');
				update_post_meta($post_id, 'mpwpb_coupon_max_qty', isset($_POST['mpwpb_coupon_max_qty']) && $_POST['mpwpb_coupon_max_qty'] !== '' ? (int) sanitize_text_field(wp_unslash($_POST['mpwpb_coupon_max_qty'])) : '');

				$history_restriction = isset($_POST['mpwpb_coupon_history_restriction']) ? sanitize_text_field(wp_unslash($_POST['mpwpb_coupon_history_restriction'])) : 'none';
				update_post_meta($post_id, 'mpwpb_coupon_history_restriction', in_array($history_restriction, ['first_booking', 'returning'], true) ? $history_restriction : 'none');
				$account_restriction = isset($_POST['mpwpb_coupon_account_restriction']) ? sanitize_text_field(wp_unslash($_POST['mpwpb_coupon_account_restriction'])) : 'none';
				update_post_meta($post_id, 'mpwpb_coupon_account_restriction', in_array($account_restriction, ['guest_only', 'logged_in_only'], true) ? $account_restriction : 'none');

				// Scheduling
				$day_restriction = isset($_POST['mpwpb_coupon_booking_day_restriction']) ? sanitize_text_field(wp_unslash($_POST['mpwpb_coupon_booking_day_restriction'])) : 'none';
				update_post_meta($post_id, 'mpwpb_coupon_booking_day_restriction', in_array($day_restriction, ['weekdays', 'weekends'], true) ? $day_restriction : 'none');

				$date_mode = isset($_POST['mpwpb_coupon_booking_date_mode']) ? sanitize_text_field(wp_unslash($_POST['mpwpb_coupon_booking_date_mode'])) : 'none';
				update_post_meta($post_id, 'mpwpb_coupon_booking_date_mode', in_array($date_mode, ['allowlist', 'blacklist'], true) ? $date_mode : 'none');
				// Rendered as one comma-separated text field, not a real multi-input, so parse it here.
				$booking_dates_raw = isset($_POST['mpwpb_coupon_booking_dates']) ? sanitize_text_field(wp_unslash($_POST['mpwpb_coupon_booking_dates'])) : '';
				$booking_dates = array_filter(array_map('trim', explode(',', $booking_dates_raw)));
				update_post_meta($post_id, 'mpwpb_coupon_booking_dates', array_values($booking_dates));

				$time_mode = isset($_POST['mpwpb_coupon_time_mode']) ? sanitize_text_field(wp_unslash($_POST['mpwpb_coupon_time_mode'])) : 'none';
				update_post_meta($post_id, 'mpwpb_coupon_time_mode', in_array($time_mode, ['bucket', 'range'], true) ? $time_mode : 'none');
				$time_bucket = isset($_POST['mpwpb_coupon_time_bucket']) ? sanitize_text_field(wp_unslash($_POST['mpwpb_coupon_time_bucket'])) : 'morning';
				update_post_meta($post_id, 'mpwpb_coupon_time_bucket', in_array($time_bucket, ['morning', 'afternoon', 'evening'], true) ? $time_bucket : 'morning');
				update_post_meta($post_id, 'mpwpb_coupon_time_range_start', isset($_POST['mpwpb_coupon_time_range_start']) ? sanitize_text_field(wp_unslash($_POST['mpwpb_coupon_time_range_start'])) : '');
				update_post_meta($post_id, 'mpwpb_coupon_time_range_end', isset($_POST['mpwpb_coupon_time_range_end']) ? sanitize_text_field(wp_unslash($_POST['mpwpb_coupon_time_range_end'])) : '');

				// Staff (Pro)
				$staff_scope = isset($_POST['mpwpb_coupon_staff_scope']) ? sanitize_text_field(wp_unslash($_POST['mpwpb_coupon_staff_scope'])) : 'all';
				update_post_meta($post_id, 'mpwpb_coupon_staff_scope', in_array($staff_scope, ['include', 'exclude'], true) ? $staff_scope : 'all');
				$staff_ids = isset($_POST['mpwpb_coupon_staff_ids']) ? array_map('absint', (array) wp_unslash($_POST['mpwpb_coupon_staff_ids'])) : [];
				update_post_meta($post_id, 'mpwpb_coupon_staff_ids', array_values(array_filter($staff_ids)));

				// Usage limits
				update_post_meta($post_id, 'mpwpb_coupon_usage_limit_total', isset($_POST['mpwpb_coupon_usage_limit_total']) && $_POST['mpwpb_coupon_usage_limit_total'] !== '' ? (int) sanitize_text_field(wp_unslash($_POST['mpwpb_coupon_usage_limit_total'])) : '');
				update_post_meta($post_id, 'mpwpb_coupon_usage_limit_per_customer', isset($_POST['mpwpb_coupon_usage_limit_per_customer']) && $_POST['mpwpb_coupon_usage_limit_per_customer'] !== '' ? (int) sanitize_text_field(wp_unslash($_POST['mpwpb_coupon_usage_limit_per_customer'])) : '');
				if (get_post_meta($post_id, 'mpwpb_coupon_usage_count', true) === '') {
					update_post_meta($post_id, 'mpwpb_coupon_usage_count', 0);
				}

				do_action('mpwpb_coupon_settings_save', $post_id);
			}
		}
		new MPWPB_Coupon_Settings();
	}
