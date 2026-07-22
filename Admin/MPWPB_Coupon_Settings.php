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
				add_filter('admin_body_class', [$this, 'body_class']);
				add_action('admin_enqueue_scripts', [$this, 'enqueue']);
			}
			public function settings_meta() {
				add_meta_box('mpwpb_coupon_meta_box_panel', esc_html__('Coupon Settings', 'service-booking-manager'), [$this, 'settings'], 'mpwpb_coupon', 'normal', 'high');
			}

			/** True only on the add/edit screen of the coupon CPT -- gates the shell CSS/JS and the chrome-hiding body class. */
			private function is_coupon_edit_screen() {
				if (!function_exists('get_current_screen')) {
					return false;
				}
				$screen = get_current_screen();
				return $screen && $screen->base === 'post' && $screen->post_type === 'mpwpb_coupon';
			}

			public function body_class($classes) {
				if ($this->is_coupon_edit_screen()) {
					$classes .= ' mpwpb-cem-active';
				}
				return $classes;
			}

			/** Cache-bust on file change so edits show without a manual hard-refresh. */
			private function asset_ver($rel_path) {
				$file = MPWPB_PLUGIN_DIR . $rel_path;
				return file_exists($file) ? (string) filemtime($file) : '1.0.0';
			}

			public function enqueue() {
				if (!$this->is_coupon_edit_screen()) {
					return;
				}
				wp_enqueue_style('mpwpb-coupon-edit-modern', MPWPB_PLUGIN_URL . '/assets/admin/mpwpb-coupon-edit-modern.css', [], $this->asset_ver('/assets/admin/mpwpb-coupon-edit-modern.css'));
				wp_enqueue_script('mpwpb-coupon-edit-modern', MPWPB_PLUGIN_URL . '/assets/admin/mpwpb-coupon-edit-modern.js', ['jquery'], $this->asset_ver('/assets/admin/mpwpb-coupon-edit-modern.js'), true);
			}

			/**
			 * Modern SaaS-style shell: sticky topbar (name field mirroring the
			 * real #title, status pill, Publish/Update + Save Draft/Trash) and
			 * a left nav rail + single content card in place of the old
			 * horizontal tab strip. WordPress's own Publish box / Screen
			 * Options bar are hidden via CSS (see mpwpb-coupon-edit-modern.css)
			 * -- their real controls stay in the DOM and are proxy-clicked by
			 * mpwpb-coupon-edit-modern.js, so save/publish/draft/trash all keep
			 * working exactly as before. The tab strip below keeps the exact
			 * same .mpwpb_style/.mpwpb_tabs/.tabLists/[data-tabs-target]/
			 * .tabsContent/[data-tabs] structure the shared tab-switching JS
			 * (mp_global/assets/mp_style/mpwpb_plugin_global.js) already
			 * delegates on, so no JS changes were needed for tab switching.
			 */
			public function settings($post) {
				$post_id = (int) $post->ID;
				wp_nonce_field('mpwpb_coupon_nonce', 'mpwpb_coupon_nonce');

				$coupon_name = get_the_title($post_id);
				$list_url = admin_url('edit.php?post_type=' . MPWPB_Function::get_cpt() . '&page=mpwpb_coupon_list');
				$status = get_post_status($post_id);
				$is_published = in_array($status, ['publish', 'private', 'future'], true);
				$is_real_post = $status && $status !== 'auto-draft';
				$primary_label = $is_published ? __('Update', 'service-booking-manager') : __('Publish', 'service-booking-manager');
				$secondary_label = $is_published ? __('Switch to Draft', 'service-booking-manager') : __('Save Draft', 'service-booking-manager');
				$status_label = $is_published ? __('Active', 'service-booking-manager') : __('Draft', 'service-booking-manager');
				?>
				<div class="mpwpb-cem mpwpb_style" id="mpwpb-cem">
					<header class="mpwpb-cem__topbar">
						<a class="mpwpb-cem__back" href="<?php echo esc_url($list_url); ?>">
							<span class="dashicons dashicons-arrow-left-alt2"></span>
							<?php esc_html_e('Back to Coupons', 'service-booking-manager'); ?>
						</a>
						<input type="text" class="mpwpb-cem__ttl-input" id="mpwpb-cem-title" value="<?php echo esc_attr($coupon_name); ?>" placeholder="<?php esc_attr_e('Coupon name', 'service-booking-manager'); ?>" aria-label="<?php esc_attr_e('Coupon name', 'service-booking-manager'); ?>"/>
						<div class="mpwpb-cem__acts">
							<span class="mpwpb-cem__status-pill<?php echo $is_published ? ' is-active' : ' is-draft'; ?>"><?php echo esc_html($status_label); ?></span>
							<div class="mpwpb-cem__split" data-cem-split>
								<button type="button" class="mpwpb-cem__btn mpwpb-cem__btn--primary" data-cem-save><?php echo esc_html($primary_label); ?></button>
								<button type="button" class="mpwpb-cem__btn mpwpb-cem__btn--primary mpwpb-cem__split-caret" data-cem-split-toggle aria-haspopup="true" aria-expanded="false">
									<span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span>
									<span class="screen-reader-text"><?php esc_html_e('More save options', 'service-booking-manager'); ?></span>
								</button>
								<div class="mpwpb-cem__split-menu" data-cem-split-menu hidden>
									<button type="button" class="mpwpb-cem__split-menu-item" data-cem-save-as="draft"><?php echo esc_html($secondary_label); ?></button>
									<?php if ($is_real_post && current_user_can('delete_post', $post_id)) : ?>
										<a class="mpwpb-cem__split-menu-item mpwpb-cem__split-menu-item--danger" href="<?php echo esc_url(get_delete_post_link($post_id)); ?>">
											<?php esc_html_e('Move to Trash', 'service-booking-manager'); ?>
										</a>
									<?php endif; ?>
								</div>
							</div>
						</div>
					</header>

					<div class="mpwpb-cem__body mpwpb_tabs">
						<nav class="mpwpb-cem__rail">
							<ul class="tabLists">
								<li data-tabs-target="#mpwpb_coupon_general">
									<span class="dashicons dashicons-admin-generic"></span><?php esc_html_e('General', 'service-booking-manager'); ?>
								</li>
								<li data-tabs-target="#mpwpb_coupon_discount">
									<span class="dashicons dashicons-tag"></span><?php esc_html_e('Discount', 'service-booking-manager'); ?>
								</li>
								<li data-tabs-target="#mpwpb_coupon_services">
									<span class="dashicons dashicons-list-view"></span><?php esc_html_e('Services', 'service-booking-manager'); ?>
								</li>
								<li data-tabs-target="#mpwpb_coupon_restrictions">
									<span class="dashicons dashicons-filter"></span><?php esc_html_e('Restrictions', 'service-booking-manager'); ?>
								</li>
								<li data-tabs-target="#mpwpb_coupon_scheduling_staff">
									<span class="dashicons dashicons-calendar-alt"></span><?php esc_html_e('Scheduling & Staff', 'service-booking-manager'); ?>
								</li>
								<li data-tabs-target="#mpwpb_coupon_usage_limits">
									<span class="dashicons dashicons-groups"></span><?php esc_html_e('Usage Limits', 'service-booking-manager'); ?>
								</li>
							</ul>
						</nav>
						<div class="mpwpb-cem__card tabsContent">
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

				self::save_coupon_fields($post_id, $_POST);
			}

			/**
			 * Persist the shared coupon fields for both the normal post editor and
			 * the fast modal on the coupon list screen.
			 */
			public static function save_coupon_fields($post_id, $data) {
				$post_id = absint($post_id);
				$data = is_array($data) ? $data : [];

				// General
				$code = isset($data['mpwpb_coupon_code']) ? MPWPB_Coupon_Function::normalize_code(wp_unslash($data['mpwpb_coupon_code'])) : '';
				if ($code !== '') {
					$existing_id = MPWPB_Coupon_Function::find_any_by_code($code);
					if ($existing_id && $existing_id !== $post_id) {
						return new WP_Error('duplicate_coupon_code', esc_html__('That coupon code is already in use.', 'service-booking-manager'));
					}
				}
				update_post_meta($post_id, 'mpwpb_coupon_code', $code);
				update_post_meta($post_id, 'mpwpb_coupon_description', isset($data['mpwpb_coupon_description']) ? sanitize_textarea_field(wp_unslash($data['mpwpb_coupon_description'])) : '');
				update_post_meta($post_id, 'mpwpb_coupon_start_date', isset($data['mpwpb_coupon_start_date']) ? sanitize_text_field(wp_unslash($data['mpwpb_coupon_start_date'])) : '');
				update_post_meta($post_id, 'mpwpb_coupon_expiry_date', isset($data['mpwpb_coupon_expiry_date']) ? sanitize_text_field(wp_unslash($data['mpwpb_coupon_expiry_date'])) : '');

				// Discount
				$discount_type = isset($data['mpwpb_coupon_discount_type']) ? sanitize_text_field(wp_unslash($data['mpwpb_coupon_discount_type'])) : 'fixed';
				if (!in_array($discount_type, ['fixed', 'percentage', 'fixed_price', 'free'], true)) {
					$discount_type = 'fixed';
				}
				update_post_meta($post_id, 'mpwpb_coupon_discount_type', $discount_type);
				update_post_meta($post_id, 'mpwpb_coupon_discount_value', isset($data['mpwpb_coupon_discount_value']) ? max(0, (float) sanitize_text_field(wp_unslash($data['mpwpb_coupon_discount_value']))) : 0);

				// Services
				$service_scope = isset($data['mpwpb_coupon_service_scope']) ? sanitize_text_field(wp_unslash($data['mpwpb_coupon_service_scope'])) : 'all';
				update_post_meta($post_id, 'mpwpb_coupon_service_scope', $service_scope === 'specific' ? 'specific' : 'all');
				$services = isset($data['mpwpb_coupon_services']) ? array_map('sanitize_text_field', wp_unslash((array) $data['mpwpb_coupon_services'])) : [];
				update_post_meta($post_id, 'mpwpb_coupon_services', array_values(array_filter($services)));

				// Restrictions
				update_post_meta($post_id, 'mpwpb_coupon_min_total', isset($data['mpwpb_coupon_min_total']) && $data['mpwpb_coupon_min_total'] !== '' ? max(0, (float) sanitize_text_field(wp_unslash($data['mpwpb_coupon_min_total']))) : '');
				update_post_meta($post_id, 'mpwpb_coupon_max_total', isset($data['mpwpb_coupon_max_total']) && $data['mpwpb_coupon_max_total'] !== '' ? max(0, (float) sanitize_text_field(wp_unslash($data['mpwpb_coupon_max_total']))) : '');
				update_post_meta($post_id, 'mpwpb_coupon_min_qty', isset($data['mpwpb_coupon_min_qty']) && $data['mpwpb_coupon_min_qty'] !== '' ? max(0, (int) sanitize_text_field(wp_unslash($data['mpwpb_coupon_min_qty']))) : '');
				update_post_meta($post_id, 'mpwpb_coupon_max_qty', isset($data['mpwpb_coupon_max_qty']) && $data['mpwpb_coupon_max_qty'] !== '' ? max(0, (int) sanitize_text_field(wp_unslash($data['mpwpb_coupon_max_qty']))) : '');

				$history_restriction = isset($data['mpwpb_coupon_history_restriction']) ? sanitize_text_field(wp_unslash($data['mpwpb_coupon_history_restriction'])) : 'none';
				update_post_meta($post_id, 'mpwpb_coupon_history_restriction', in_array($history_restriction, ['first_booking', 'returning'], true) ? $history_restriction : 'none');
				$account_restriction = isset($data['mpwpb_coupon_account_restriction']) ? sanitize_text_field(wp_unslash($data['mpwpb_coupon_account_restriction'])) : 'none';
				update_post_meta($post_id, 'mpwpb_coupon_account_restriction', in_array($account_restriction, ['guest_only', 'logged_in_only'], true) ? $account_restriction : 'none');

				// Scheduling
				$day_restriction = isset($data['mpwpb_coupon_booking_day_restriction']) ? sanitize_text_field(wp_unslash($data['mpwpb_coupon_booking_day_restriction'])) : 'none';
				update_post_meta($post_id, 'mpwpb_coupon_booking_day_restriction', in_array($day_restriction, ['weekdays', 'weekends'], true) ? $day_restriction : 'none');

				$date_mode = isset($data['mpwpb_coupon_booking_date_mode']) ? sanitize_text_field(wp_unslash($data['mpwpb_coupon_booking_date_mode'])) : 'none';
				update_post_meta($post_id, 'mpwpb_coupon_booking_date_mode', in_array($date_mode, ['allowlist', 'blacklist'], true) ? $date_mode : 'none');
				// Rendered as one comma-separated text field, not a real multi-input, so parse it here.
				$booking_dates_raw = isset($data['mpwpb_coupon_booking_dates']) ? sanitize_text_field(wp_unslash($data['mpwpb_coupon_booking_dates'])) : '';
				$booking_dates = array_filter(array_map('trim', explode(',', $booking_dates_raw)));
				update_post_meta($post_id, 'mpwpb_coupon_booking_dates', array_values($booking_dates));

				$time_mode = isset($data['mpwpb_coupon_time_mode']) ? sanitize_text_field(wp_unslash($data['mpwpb_coupon_time_mode'])) : 'none';
				update_post_meta($post_id, 'mpwpb_coupon_time_mode', in_array($time_mode, ['bucket', 'range'], true) ? $time_mode : 'none');
				$time_bucket = isset($data['mpwpb_coupon_time_bucket']) ? sanitize_text_field(wp_unslash($data['mpwpb_coupon_time_bucket'])) : 'morning';
				update_post_meta($post_id, 'mpwpb_coupon_time_bucket', in_array($time_bucket, ['morning', 'afternoon', 'evening'], true) ? $time_bucket : 'morning');
				update_post_meta($post_id, 'mpwpb_coupon_time_range_start', isset($data['mpwpb_coupon_time_range_start']) ? sanitize_text_field(wp_unslash($data['mpwpb_coupon_time_range_start'])) : '');
				update_post_meta($post_id, 'mpwpb_coupon_time_range_end', isset($data['mpwpb_coupon_time_range_end']) ? sanitize_text_field(wp_unslash($data['mpwpb_coupon_time_range_end'])) : '');

				// Staff (Pro)
				$staff_scope = isset($data['mpwpb_coupon_staff_scope']) ? sanitize_text_field(wp_unslash($data['mpwpb_coupon_staff_scope'])) : 'all';
				update_post_meta($post_id, 'mpwpb_coupon_staff_scope', in_array($staff_scope, ['include', 'exclude'], true) ? $staff_scope : 'all');
				$staff_ids = isset($data['mpwpb_coupon_staff_ids']) ? array_map('absint', (array) wp_unslash($data['mpwpb_coupon_staff_ids'])) : [];
				update_post_meta($post_id, 'mpwpb_coupon_staff_ids', array_values(array_filter($staff_ids)));

				// Usage limits
				update_post_meta($post_id, 'mpwpb_coupon_usage_limit_total', isset($data['mpwpb_coupon_usage_limit_total']) && $data['mpwpb_coupon_usage_limit_total'] !== '' ? max(0, (int) sanitize_text_field(wp_unslash($data['mpwpb_coupon_usage_limit_total']))) : '');
				update_post_meta($post_id, 'mpwpb_coupon_usage_limit_per_customer', isset($data['mpwpb_coupon_usage_limit_per_customer']) && $data['mpwpb_coupon_usage_limit_per_customer'] !== '' ? max(0, (int) sanitize_text_field(wp_unslash($data['mpwpb_coupon_usage_limit_per_customer']))) : '');
				if (get_post_meta($post_id, 'mpwpb_coupon_usage_count', true) === '') {
					update_post_meta($post_id, 'mpwpb_coupon_usage_count', 0);
				}

				do_action('mpwpb_coupon_settings_save', $post_id);
				return true;
			}
		}
		new MPWPB_Coupon_Settings();
	}
