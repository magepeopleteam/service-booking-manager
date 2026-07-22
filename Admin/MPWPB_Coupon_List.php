<?php
	/*
   * @Author 		engr.sumonazma@gmail.com
   * Copyright: 	mage-people.com
   */
	if (!defined('ABSPATH')) {
		die;
	} // Cannot access pages directly.
	if (!class_exists('MPWPB_Coupon_List')) {
		class MPWPB_Coupon_List {
			public function __construct() {
				add_action('admin_menu', [$this, 'coupon_list_menu']);
				add_action('admin_action_mpwpb_coupon_duplicate', [$this, 'duplicate_coupon']);
				add_action('admin_enqueue_scripts', [$this, 'enqueue_assets'], 999);
				add_action('wp_ajax_mpwpb_coupon_modal_form', [$this, 'ajax_modal_form']);
				add_action('wp_ajax_mpwpb_coupon_modal_save', [$this, 'ajax_modal_save']);
				add_action('wp_ajax_mpwpb_coupon_modal_duplicate', [$this, 'ajax_modal_duplicate']);
				add_action('wp_ajax_mpwpb_coupon_modal_trash', [$this, 'ajax_modal_trash']);
			}
			public function coupon_list_menu() {
				add_submenu_page('edit.php?post_type=' . MPWPB_Function::get_cpt(), esc_html__('Coupons', 'service-booking-manager'), esc_html__('Coupons', 'service-booking-manager'), 'manage_options', 'mpwpb_coupon_list', [$this, 'coupon_list'], 20);
			}
			public function coupon_list() {
				?>
				<div class="wrap">
					<div class="mpwpb_style mpwpb_order_filter_area">
						<div id="mpwpb_coupon_list_result">
							<?php $this->coupon_list_result(); ?>
						</div>
					</div>
					<div id="mpwpb-coupon-modal-root"></div>
				</div>
				<style>
					#update-nag, .update-nag {display: none;}
				</style>
				<?php
			}
			public function coupon_list_result() {
				include(MPWPB_Function::template_path('layout/coupon_list.php'));
			}

			private function is_coupon_list_screen(): bool {
				if (!function_exists('get_current_screen')) {
					return false;
				}
				$screen = get_current_screen();
				return $screen && $screen->id === MPWPB_Function::get_cpt() . '_page_mpwpb_coupon_list';
			}

			public function enqueue_assets(): void {
				if (!$this->is_coupon_list_screen()) {
					return;
				}
				$css = '/assets/admin/mpwpb-coupon-list-modern.css';
				$js = '/assets/admin/mpwpb-coupon-list-modern.js';
				wp_enqueue_style('mpwpb-coupon-edit-modern', MPWPB_PLUGIN_URL . '/assets/admin/mpwpb-coupon-edit-modern.css', [], filemtime(MPWPB_PLUGIN_DIR . '/assets/admin/mpwpb-coupon-edit-modern.css'));
				wp_enqueue_style('mpwpb-coupon-list-modern', MPWPB_PLUGIN_URL . $css, ['mpwpb-coupon-edit-modern'], file_exists(MPWPB_PLUGIN_DIR . $css) ? filemtime(MPWPB_PLUGIN_DIR . $css) : '1.0.0');
				wp_enqueue_script('mpwpb-coupon-list-modern', MPWPB_PLUGIN_URL . $js, ['jquery'], file_exists(MPWPB_PLUGIN_DIR . $js) ? filemtime(MPWPB_PLUGIN_DIR . $js) : '1.0.0', true);
				wp_localize_script('mpwpb-coupon-list-modern', 'mpwpbCouponModal', [
					'ajaxUrl' => admin_url('admin-ajax.php'),
					'nonce' => wp_create_nonce('mpwpb_coupon_modal'),
					'loading' => esc_html__('Loading coupon…', 'service-booking-manager'),
					'saving' => esc_html__('Saving…', 'service-booking-manager'),
					'error' => esc_html__('The coupon could not be saved. Please try again.', 'service-booking-manager'),
					'trashConfirm' => esc_html__('Move this coupon to Trash?', 'service-booking-manager'),
				]);
			}

			private function verify_ajax_request(): void {
				if (!check_ajax_referer('mpwpb_coupon_modal', 'nonce', false)) {
					wp_send_json_error(['message' => esc_html__('Security check failed. Refresh the page and try again.', 'service-booking-manager')], 403);
				}
				if (!current_user_can('manage_options')) {
					wp_send_json_error(['message' => esc_html__('You do not have permission to manage coupons.', 'service-booking-manager')], 403);
				}
			}

			public function ajax_modal_form(): void {
				$this->verify_ajax_request();
				$post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
				if ($post_id && (get_post_type($post_id) !== 'mpwpb_coupon' || !current_user_can('edit_post', $post_id))) {
					wp_send_json_error(['message' => esc_html__('Coupon not found or not editable.', 'service-booking-manager')], 404);
				}
				ob_start();
				$this->render_modal_form($post_id);
				wp_send_json_success(['html' => ob_get_clean()]);
			}

			private function render_modal_form($post_id): void {
				$title = $post_id ? get_the_title($post_id) : '';
				$status = $post_id && get_post_status($post_id) === 'publish' ? 'publish' : 'draft';
				?>
				<div class="mpwpb-coupon-modal" role="dialog" aria-modal="true" aria-labelledby="mpwpb-coupon-modal-title">
					<div class="mpwpb-coupon-modal__backdrop" data-coupon-modal-close></div>
					<form class="mpwpb-coupon-modal__dialog mpwpb-cem mpwpb_style" data-coupon-modal-form>
						<input type="hidden" name="post_id" value="<?php echo esc_attr($post_id); ?>"/>
						<header class="mpwpb-coupon-modal__header">
							<div class="mpwpb-coupon-modal__heading">
								<span class="mpwpb-coupon-modal__header-icon"><span class="dashicons dashicons-tickets-alt"></span></span>
								<div>
									<span class="mpwpb-coupon-modal__eyebrow"><?php esc_html_e('Booking promotion', 'service-booking-manager'); ?></span>
									<h2 id="mpwpb-coupon-modal-title"><?php echo esc_html($post_id ? __('Edit Coupon', 'service-booking-manager') : __('Create Coupon', 'service-booking-manager')); ?></h2>
									<p><?php esc_html_e('Configure the offer, eligibility and usage rules in one place.', 'service-booking-manager'); ?></p>
								</div>
							</div>
							<button type="button" class="mpwpb-coupon-modal__close" data-coupon-modal-close aria-label="<?php esc_attr_e('Close', 'service-booking-manager'); ?>"><span class="dashicons dashicons-no-alt"></span></button>
						</header>
						<div class="mpwpb-coupon-modal__identity">
							<label><span><?php esc_html_e('Coupon name', 'service-booking-manager'); ?> <b class="mpwpb-coupon-required">*</b></span><input type="text" name="post_title" value="<?php echo esc_attr($title); ?>" placeholder="<?php esc_attr_e('Summer campaign', 'service-booking-manager'); ?>" maxlength="160" required/></label>
							<label><span><?php esc_html_e('Status', 'service-booking-manager'); ?></span><select name="post_status"><option value="publish" <?php selected($status, 'publish'); ?>><?php esc_html_e('Active', 'service-booking-manager'); ?></option><option value="draft" <?php selected($status, 'draft'); ?>><?php esc_html_e('Inactive / Draft', 'service-booking-manager'); ?></option></select></label>
						</div>
						<div class="mpwpb-coupon-modal__content mpwpb-cem__body mpwpb_tabs">
							<nav class="mpwpb-cem__rail"><ul class="tabLists">
								<li data-tabs-target="#mpwpb_coupon_general"><span class="dashicons dashicons-admin-generic"></span><?php esc_html_e('General', 'service-booking-manager'); ?></li>
								<li data-tabs-target="#mpwpb_coupon_discount"><span class="dashicons dashicons-tag"></span><?php esc_html_e('Discount', 'service-booking-manager'); ?></li>
								<li data-tabs-target="#mpwpb_coupon_services"><span class="dashicons dashicons-list-view"></span><?php esc_html_e('Services', 'service-booking-manager'); ?></li>
								<li data-tabs-target="#mpwpb_coupon_restrictions"><span class="dashicons dashicons-filter"></span><?php esc_html_e('Restrictions', 'service-booking-manager'); ?></li>
								<li data-tabs-target="#mpwpb_coupon_scheduling_staff"><span class="dashicons dashicons-calendar-alt"></span><?php esc_html_e('Schedule & Staff', 'service-booking-manager'); ?></li>
								<li data-tabs-target="#mpwpb_coupon_usage_limits"><span class="dashicons dashicons-groups"></span><?php esc_html_e('Usage', 'service-booking-manager'); ?></li>
							</ul></nav>
							<div class="mpwpb-cem__card tabsContent"><?php do_action('add_mpwpb_coupon_tab_content', $post_id); ?></div>
						</div>
						<footer class="mpwpb-coupon-modal__footer">
							<p data-coupon-modal-message aria-live="polite"></p>
							<div><button type="button" class="button" data-coupon-modal-close><?php esc_html_e('Cancel', 'service-booking-manager'); ?></button><button type="submit" class="button button-primary" data-coupon-modal-save><?php echo esc_html($post_id ? __('Update Coupon', 'service-booking-manager') : __('Create Coupon', 'service-booking-manager')); ?></button></div>
						</footer>
					</form>
				</div>
				<?php
			}

			private function coupon_validation_error($code, $message, $field, $tab) {
				return new WP_Error($code, $message, ['field' => $field, 'tab' => $tab]);
			}

			/** Validate every cross-field rule before creating or updating a coupon. */
			private function validate_coupon_request(array $data, $post_id) {
				$text = static function ($key) use ($data): string {
					return isset($data[$key]) ? sanitize_text_field(wp_unslash($data[$key])) : '';
				};
				$valid_date = static function ($value): bool {
					if ($value === '') {
						return true;
					}
					$date = DateTime::createFromFormat('!Y-m-d', $value);
					return $date && $date->format('Y-m-d') === $value;
				};
				$optional_number = static function ($key) use ($data) {
					if (!isset($data[$key]) || $data[$key] === '') {
						return null;
					}
					$value = wp_unslash($data[$key]);
					return is_numeric($value) ? (float) $value : false;
				};

				$title = $text('post_title');
				if ($title === '') {
					return $this->coupon_validation_error('coupon_name_required', esc_html__('Enter a coupon name.', 'service-booking-manager'), 'post_title', 'identity');
				}
				$title_length = function_exists('mb_strlen') ? mb_strlen($title) : strlen($title);
				if ($title_length > 160) {
					return $this->coupon_validation_error('coupon_name_too_long', esc_html__('Coupon name must be 160 characters or fewer.', 'service-booking-manager'), 'post_title', 'identity');
				}

				$code = MPWPB_Coupon_Function::normalize_code($text('mpwpb_coupon_code'));
				if ($code === '') {
					return $this->coupon_validation_error('coupon_code_required', esc_html__('Enter a coupon code.', 'service-booking-manager'), 'mpwpb_coupon_code', '#mpwpb_coupon_general');
				}
				if (strlen($code) > 64 || !preg_match('/^[A-Z0-9][A-Z0-9_-]*$/', $code)) {
					return $this->coupon_validation_error('coupon_code_invalid', esc_html__('Use up to 64 letters, numbers, hyphens or underscores for the coupon code.', 'service-booking-manager'), 'mpwpb_coupon_code', '#mpwpb_coupon_general');
				}
				$existing_id = MPWPB_Coupon_Function::find_any_by_code($code);
				if ($existing_id && $existing_id !== (int) $post_id) {
					return $this->coupon_validation_error('duplicate_coupon_code', esc_html__('That coupon code is already in use.', 'service-booking-manager'), 'mpwpb_coupon_code', '#mpwpb_coupon_general');
				}

				$start = $text('mpwpb_coupon_start_date');
				$expiry = $text('mpwpb_coupon_expiry_date');
				if (!$valid_date($start)) {
					return $this->coupon_validation_error('coupon_start_date_invalid', esc_html__('Enter a valid start date.', 'service-booking-manager'), 'mpwpb_coupon_start_date', '#mpwpb_coupon_general');
				}
				if (!$valid_date($expiry)) {
					return $this->coupon_validation_error('coupon_expiry_date_invalid', esc_html__('Enter a valid expiry date.', 'service-booking-manager'), 'mpwpb_coupon_expiry_date', '#mpwpb_coupon_general');
				}
				if ($start && $expiry && $expiry < $start) {
					return $this->coupon_validation_error('coupon_date_order', esc_html__('Expiry date must be on or after the start date.', 'service-booking-manager'), 'mpwpb_coupon_expiry_date', '#mpwpb_coupon_general');
				}

				$discount_type = $text('mpwpb_coupon_discount_type');
				if (!in_array($discount_type, ['fixed', 'percentage', 'fixed_price', 'free'], true)) {
					return $this->coupon_validation_error('coupon_discount_type_invalid', esc_html__('Select a valid discount type.', 'service-booking-manager'), 'mpwpb_coupon_discount_type', '#mpwpb_coupon_discount');
				}
				if ($discount_type !== 'free') {
					$discount = $optional_number('mpwpb_coupon_discount_value');
					if ($discount === false || $discount === null || $discount <= 0) {
						return $this->coupon_validation_error('coupon_discount_required', esc_html__('Enter a discount value greater than zero.', 'service-booking-manager'), 'mpwpb_coupon_discount_value', '#mpwpb_coupon_discount');
					}
					if ($discount_type === 'percentage' && $discount > 100) {
						return $this->coupon_validation_error('coupon_percentage_invalid', esc_html__('Percentage discount cannot exceed 100%.', 'service-booking-manager'), 'mpwpb_coupon_discount_value', '#mpwpb_coupon_discount');
					}
				}

				$service_scope = $text('mpwpb_coupon_service_scope');
				if (!in_array($service_scope, ['all', 'specific'], true)) {
					return $this->coupon_validation_error('coupon_service_scope_invalid', esc_html__('Select a valid service scope.', 'service-booking-manager'), 'mpwpb_coupon_service_scope', '#mpwpb_coupon_services');
				}
				if ($service_scope === 'specific') {
					$services = isset($data['mpwpb_coupon_services']) ? array_values(array_filter(array_map('sanitize_text_field', wp_unslash((array) $data['mpwpb_coupon_services'])))) : [];
					$valid_services = array_column(MPWPB_Coupon_Function::get_all_services_flat(), 'value');
					if (!$services) {
						return $this->coupon_validation_error('coupon_services_required', esc_html__('Select at least one service.', 'service-booking-manager'), 'mpwpb_coupon_services[]', '#mpwpb_coupon_services');
					}
					if (array_diff($services, $valid_services)) {
						return $this->coupon_validation_error('coupon_services_invalid', esc_html__('One or more selected services are invalid.', 'service-booking-manager'), 'mpwpb_coupon_services[]', '#mpwpb_coupon_services');
					}
				}

				$min_total = $optional_number('mpwpb_coupon_min_total');
				$max_total = $optional_number('mpwpb_coupon_max_total');
				if ($min_total === false || ($min_total !== null && $min_total < 0)) {
					return $this->coupon_validation_error('coupon_min_total_invalid', esc_html__('Minimum booking total cannot be negative.', 'service-booking-manager'), 'mpwpb_coupon_min_total', '#mpwpb_coupon_restrictions');
				}
				if ($max_total === false || ($max_total !== null && $max_total < 0)) {
					return $this->coupon_validation_error('coupon_max_total_invalid', esc_html__('Maximum booking total cannot be negative.', 'service-booking-manager'), 'mpwpb_coupon_max_total', '#mpwpb_coupon_restrictions');
				}
				if ($min_total !== null && $max_total !== null && $max_total < $min_total) {
					return $this->coupon_validation_error('coupon_total_range_invalid', esc_html__('Maximum booking total must be at least the minimum total.', 'service-booking-manager'), 'mpwpb_coupon_max_total', '#mpwpb_coupon_restrictions');
				}

				$min_qty = $optional_number('mpwpb_coupon_min_qty');
				$max_qty = $optional_number('mpwpb_coupon_max_qty');
				if ($min_qty === false || ($min_qty !== null && ($min_qty < 0 || floor($min_qty) !== $min_qty))) {
					return $this->coupon_validation_error('coupon_min_qty_invalid', esc_html__('Minimum quantity must be a whole number of zero or more.', 'service-booking-manager'), 'mpwpb_coupon_min_qty', '#mpwpb_coupon_restrictions');
				}
				if ($max_qty === false || ($max_qty !== null && ($max_qty < 0 || floor($max_qty) !== $max_qty))) {
					return $this->coupon_validation_error('coupon_max_qty_invalid', esc_html__('Maximum quantity must be a whole number of zero or more.', 'service-booking-manager'), 'mpwpb_coupon_max_qty', '#mpwpb_coupon_restrictions');
				}
				if ($min_qty !== null && $max_qty !== null && $max_qty < $min_qty) {
					return $this->coupon_validation_error('coupon_qty_range_invalid', esc_html__('Maximum quantity must be at least the minimum quantity.', 'service-booking-manager'), 'mpwpb_coupon_max_qty', '#mpwpb_coupon_restrictions');
				}

				$history_restriction = $text('mpwpb_coupon_history_restriction');
				$account_restriction = $text('mpwpb_coupon_account_restriction');
				if (!in_array($history_restriction, ['none', 'first_booking', 'returning'], true)) {
					return $this->coupon_validation_error('coupon_history_invalid', esc_html__('Select a valid booking-history restriction.', 'service-booking-manager'), 'mpwpb_coupon_history_restriction', '#mpwpb_coupon_restrictions');
				}
				if (!in_array($account_restriction, ['none', 'guest_only', 'logged_in_only'], true)) {
					return $this->coupon_validation_error('coupon_account_invalid', esc_html__('Select a valid account restriction.', 'service-booking-manager'), 'mpwpb_coupon_account_restriction', '#mpwpb_coupon_restrictions');
				}

				$day_restriction = $text('mpwpb_coupon_booking_day_restriction');
				$date_mode = $text('mpwpb_coupon_booking_date_mode');
				$time_mode = $text('mpwpb_coupon_time_mode');
				if (!in_array($day_restriction, ['none', 'weekdays', 'weekends'], true)) {
					return $this->coupon_validation_error('coupon_day_invalid', esc_html__('Select a valid day restriction.', 'service-booking-manager'), 'mpwpb_coupon_booking_day_restriction', '#mpwpb_coupon_scheduling_staff');
				}
				if (!in_array($date_mode, ['none', 'allowlist', 'blacklist'], true)) {
					return $this->coupon_validation_error('coupon_date_mode_invalid', esc_html__('Select a valid booking-date mode.', 'service-booking-manager'), 'mpwpb_coupon_booking_date_mode', '#mpwpb_coupon_scheduling_staff');
				}
				if (!in_array($time_mode, ['none', 'bucket', 'range'], true)) {
					return $this->coupon_validation_error('coupon_time_mode_invalid', esc_html__('Select a valid time restriction.', 'service-booking-manager'), 'mpwpb_coupon_time_mode', '#mpwpb_coupon_scheduling_staff');
				}
				if (in_array($date_mode, ['allowlist', 'blacklist'], true)) {
					$dates_raw = $text('mpwpb_coupon_booking_dates');
					$dates = array_values(array_filter(array_map('trim', explode(',', $dates_raw))));
					if (!$dates) {
						return $this->coupon_validation_error('coupon_booking_dates_required', esc_html__('Enter at least one booking date.', 'service-booking-manager'), 'mpwpb_coupon_booking_dates', '#mpwpb_coupon_scheduling_staff');
					}
					foreach ($dates as $date) {
						if (!$valid_date($date)) {
							return $this->coupon_validation_error('coupon_booking_date_invalid', esc_html__('Booking dates must use the YYYY-MM-DD format.', 'service-booking-manager'), 'mpwpb_coupon_booking_dates', '#mpwpb_coupon_scheduling_staff');
						}
					}
				}

				if ($time_mode === 'bucket' && !in_array($text('mpwpb_coupon_time_bucket'), ['morning', 'afternoon', 'evening'], true)) {
					return $this->coupon_validation_error('coupon_time_bucket_invalid', esc_html__('Select a valid time-of-day option.', 'service-booking-manager'), 'mpwpb_coupon_time_bucket', '#mpwpb_coupon_scheduling_staff');
				}
				if ($time_mode === 'range') {
					$time_start = $text('mpwpb_coupon_time_range_start');
					$time_end = $text('mpwpb_coupon_time_range_end');
					if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $time_start)) {
						return $this->coupon_validation_error('coupon_time_start_invalid', esc_html__('Select a valid start time.', 'service-booking-manager'), 'mpwpb_coupon_time_range_start', '#mpwpb_coupon_scheduling_staff');
					}
					if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $time_end) || $time_end === $time_start) {
						return $this->coupon_validation_error('coupon_time_end_invalid', esc_html__('Select an end time different from the start time.', 'service-booking-manager'), 'mpwpb_coupon_time_range_end', '#mpwpb_coupon_scheduling_staff');
					}
				}

				$staff_scope = $text('mpwpb_coupon_staff_scope');
				if (!in_array($staff_scope, ['all', 'include', 'exclude'], true)) {
					return $this->coupon_validation_error('coupon_staff_scope_invalid', esc_html__('Select a valid staff restriction.', 'service-booking-manager'), 'mpwpb_coupon_staff_scope', '#mpwpb_coupon_scheduling_staff');
				}
				if (in_array($staff_scope, ['include', 'exclude'], true)) {
					$staff_ids = isset($data['mpwpb_coupon_staff_ids']) ? array_values(array_filter(array_map('absint', (array) wp_unslash($data['mpwpb_coupon_staff_ids'])))) : [];
					if (!$staff_ids) {
						return $this->coupon_validation_error('coupon_staff_required', esc_html__('Select at least one staff member.', 'service-booking-manager'), 'mpwpb_coupon_staff_ids[]', '#mpwpb_coupon_scheduling_staff');
					}
					foreach ($staff_ids as $staff_id) {
						$user = get_userdata($staff_id);
						if (!$user || !in_array('mpwpb_staff', (array) $user->roles, true)) {
							return $this->coupon_validation_error('coupon_staff_invalid', esc_html__('One or more selected staff members are invalid.', 'service-booking-manager'), 'mpwpb_coupon_staff_ids[]', '#mpwpb_coupon_scheduling_staff');
						}
					}
				}

				$total_limit = $optional_number('mpwpb_coupon_usage_limit_total');
				$customer_limit = $optional_number('mpwpb_coupon_usage_limit_per_customer');
				if ($total_limit !== null && ($total_limit === false || $total_limit < 1 || floor($total_limit) !== $total_limit)) {
					return $this->coupon_validation_error('coupon_total_limit_invalid', esc_html__('Total usage limit must be a whole number greater than zero.', 'service-booking-manager'), 'mpwpb_coupon_usage_limit_total', '#mpwpb_coupon_usage_limits');
				}
				if ($customer_limit !== null && ($customer_limit === false || $customer_limit < 1 || floor($customer_limit) !== $customer_limit)) {
					return $this->coupon_validation_error('coupon_customer_limit_invalid', esc_html__('Per-customer limit must be a whole number greater than zero.', 'service-booking-manager'), 'mpwpb_coupon_usage_limit_per_customer', '#mpwpb_coupon_usage_limits');
				}
				if ($total_limit !== null && $customer_limit !== null && $customer_limit > $total_limit) {
					return $this->coupon_validation_error('coupon_usage_limits_invalid', esc_html__('Per-customer limit cannot exceed the total usage limit.', 'service-booking-manager'), 'mpwpb_coupon_usage_limit_per_customer', '#mpwpb_coupon_usage_limits');
				}

				return true;
			}

			public function ajax_modal_save(): void {
				$this->verify_ajax_request();
				$post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
				if ($post_id && (get_post_type($post_id) !== 'mpwpb_coupon' || !current_user_can('edit_post', $post_id))) {
					wp_send_json_error(['message' => esc_html__('Coupon not found or not editable.', 'service-booking-manager')], 404);
				}

				$validation = $this->validate_coupon_request($_POST, $post_id);
				if (is_wp_error($validation)) {
					$error_data = $validation->get_error_data();
					wp_send_json_error([
						'message' => $validation->get_error_message(),
						'field' => $error_data['field'] ?? '',
						'tab' => $error_data['tab'] ?? '',
					], 422);
				}

				$title = sanitize_text_field(wp_unslash($_POST['post_title']));
				$status = isset($_POST['post_status']) && $_POST['post_status'] === 'publish' ? 'publish' : 'draft';

				$post_data = ['post_type' => 'mpwpb_coupon', 'post_title' => $title, 'post_status' => $status];
				if ($post_id) {
					$post_data['ID'] = $post_id;
					$result = wp_update_post(wp_slash($post_data), true);
				} else {
					$post_data['post_author'] = get_current_user_id();
					$result = wp_insert_post(wp_slash($post_data), true);
				}
				if (is_wp_error($result) || !$result) {
					wp_send_json_error(['message' => is_wp_error($result) ? $result->get_error_message() : esc_html__('The coupon could not be saved.', 'service-booking-manager')], 500);
				}
				$post_id = (int) $result;
				$saved = MPWPB_Coupon_Settings::save_coupon_fields($post_id, $_POST);
				if (is_wp_error($saved)) {
					wp_send_json_error(['message' => $saved->get_error_message()], 422);
				}

				ob_start();
				$this->coupon_list_result();
				wp_send_json_success([
					'message' => esc_html__('Coupon saved instantly.', 'service-booking-manager'),
					'listHtml' => ob_get_clean(),
					'postId' => $post_id,
				]);
			}

			private function get_list_html(): string {
				ob_start();
				$this->coupon_list_result();
				return ob_get_clean();
			}

			public function ajax_modal_duplicate(): void {
				$this->verify_ajax_request();
				$post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
				if (!$post_id || get_post_type($post_id) !== 'mpwpb_coupon' || !current_user_can('edit_post', $post_id)) {
					wp_send_json_error(['message' => esc_html__('Coupon not found or not editable.', 'service-booking-manager')], 404);
				}
				$new_post_id = $this->create_duplicate($post_id);
				if (is_wp_error($new_post_id)) {
					wp_send_json_error(['message' => $new_post_id->get_error_message()], 500);
				}
				wp_send_json_success(['postId' => $new_post_id, 'listHtml' => $this->get_list_html()]);
			}

			public function ajax_modal_trash(): void {
				$this->verify_ajax_request();
				$post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
				if (!$post_id || get_post_type($post_id) !== 'mpwpb_coupon' || !current_user_can('delete_post', $post_id)) {
					wp_send_json_error(['message' => esc_html__('Coupon not found or not removable.', 'service-booking-manager')], 404);
				}
				if (!wp_trash_post($post_id)) {
					wp_send_json_error(['message' => esc_html__('The coupon could not be moved to Trash.', 'service-booking-manager')], 500);
				}
				wp_send_json_success(['listHtml' => $this->get_list_html()]);
			}

			private function create_duplicate($post_id) {
				$post = get_post($post_id);
				if (!$post || $post->post_type !== 'mpwpb_coupon') {
					return new WP_Error('invalid_coupon', esc_html__('Invalid coupon.', 'service-booking-manager'));
				}
				$new_post_id = wp_insert_post([
					'post_title' => $post->post_title . ' ' . __('(Copy)', 'service-booking-manager'),
					'post_status' => 'draft',
					'post_type' => 'mpwpb_coupon',
					'post_author' => get_current_user_id(),
				], true);
				if (is_wp_error($new_post_id) || !$new_post_id) {
					return is_wp_error($new_post_id) ? $new_post_id : new WP_Error('duplicate_failed', esc_html__('Failed to duplicate coupon.', 'service-booking-manager'));
				}
				foreach (get_post_meta($post_id) as $key => $values) {
					if ($key === 'mpwpb_coupon_code' || $key === 'mpwpb_coupon_usage_count') {
						continue;
					}
					foreach ($values as $value) {
						add_post_meta($new_post_id, $key, maybe_unserialize($value));
					}
				}
				update_post_meta($new_post_id, 'mpwpb_coupon_usage_count', 0);
				return (int) $new_post_id;
			}

			public function duplicate_coupon() {
				if (!isset($_GET['post_id']) || !isset($_GET['_wpnonce']) ||
					!wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'mpwpb_coupon_duplicate_' . sanitize_text_field($_GET['post_id']))
				) {
					wp_die('Invalid request (missing or invalid nonce).');
				}
				if (!current_user_can('manage_options')) {
					wp_die('You are not allowed to do this.');
				}
				$post_id = (int) sanitize_text_field(wp_unslash($_GET['post_id']));
				$new_post_id = $this->create_duplicate($post_id);
				if (is_wp_error($new_post_id)) {
					wp_die(esc_html($new_post_id->get_error_message()));
				}
				wp_redirect(admin_url('post.php?action=edit&post=' . $new_post_id));
				exit;
			}
		}
		new MPWPB_Coupon_List();
	}
