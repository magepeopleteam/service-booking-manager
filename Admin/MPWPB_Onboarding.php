<?php
	/*
	 * First-run onboarding wizard.
	 *
	 * After the plugin is activated for the first time the admin is sent to a
	 * full-screen setup page (hidden from the menu): a welcome card, then
	 * business type -> first service (template, demo import or from scratch)
	 * -> payment method -> done. Finishing or skipping stores
	 * mpwpb_onboarding_status so it never shows again; deleting that option
	 * (e.g. a full data reset) brings it back.
	 *
	 * The heavy lifting reuses existing importers: business templates go
	 * through MPWPB_Business_Templates_Import's AJAX action and the demo
	 * services through MPWPB_Dummy_Import's stepped AJAX import.
	 */
	if (!defined('ABSPATH')) {
		die;
	} // Cannot access pages directly.

	if (!class_exists('MPWPB_Onboarding')) {
		class MPWPB_Onboarding {
			const SLUG = 'mpwpb-onboarding';
			const OPTION = 'mpwpb_onboarding_status';
			const NONCE = 'mpwpb_onboarding';

			public function __construct() {
				add_action('admin_menu', [$this, 'register_page']);
				add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
				add_filter('admin_body_class', [$this, 'body_class']);
				add_action('wp_ajax_mpwpb_onboarding_create_service', [$this, 'ajax_create_service']);
				add_action('wp_ajax_mpwpb_onboarding_payment', [$this, 'ajax_payment']);
				add_action('wp_ajax_mpwpb_onboarding_summary', [$this, 'ajax_summary']);
				add_action('wp_ajax_mpwpb_onboarding_finish', [$this, 'ajax_finish']);
			}

			/**
			 * True only until the wizard has been shown once (like Eventin's
			 * etn_wizard flag): 'shown' is stored at the activation redirect, then
			 * 'done' / 'skipped' when the admin finishes or skips it.
			 */
			public static function is_pending(): bool {
				return !get_option(self::OPTION);
			}

			public static function mark_shown(): void {
				if (!get_option(self::OPTION)) {
					update_option(self::OPTION, 'shown', false);
				}
			}

			public static function url(): string {
				return admin_url('admin.php?page=' . self::SLUG);
			}

			private static function services_url(): string {
				return admin_url('edit.php?post_type=mpwpb_item&page=mpwpb_service_list');
			}

			private function is_page(): bool {
				return isset($_GET['page']) && sanitize_key(wp_unslash($_GET['page'])) === self::SLUG; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- screen check only.
			}

			/** Registered under a non-menu parent so it is reachable but not listed. */
			public function register_page() {
				add_submenu_page('options.php', __('Set up Service Booking', 'service-booking-manager'), __('Set up Service Booking', 'service-booking-manager'), 'manage_options', self::SLUG, [$this, 'render']);
			}

			public function body_class($classes) {
				return $this->is_page() ? $classes . ' mpwpb-ob-screen' : $classes;
			}

			public function enqueue_assets() {
				if (!$this->is_page()) {
					return;
				}
				$css = '/assets/admin/mpwpb-onboarding.css';
				$js = '/assets/admin/mpwpb-onboarding.js';
				wp_enqueue_style('dashicons');
				wp_enqueue_style('mpwpb-onboarding', MPWPB_PLUGIN_URL . $css, [], (string) filemtime(MPWPB_PLUGIN_DIR . $css));
				wp_enqueue_script('mpwpb-onboarding', MPWPB_PLUGIN_URL . $js, ['jquery'], (string) filemtime(MPWPB_PLUGIN_DIR . $js), true);
				wp_localize_script('mpwpb-onboarding', 'mpwpbOb', [
					'ajaxUrl' => admin_url('admin-ajax.php'),
					'nonce' => wp_create_nonce(self::NONCE),
					'templateNonce' => wp_create_nonce('mpwpb_admin_nonce'),
					'dummyNonce' => wp_create_nonce('mpwpb_import_dummy'),
					'servicesUrl' => self::services_url(),
					'templates' => $this->templates(),
					'i18n' => [
						'required' => __('Please fill in this field.', 'service-booking-manager'),
						'pickDay' => __('Choose at least one open day.', 'service-booking-manager'),
						'working' => __('Setting things up…', 'service-booking-manager'),
						'importing' => __('Importing demo services…', 'service-booking-manager'),
						'readySite' => __('Building your ready site…', 'service-booking-manager'),
						'error' => __('Something went wrong. Please try again.', 'service-booking-manager'),
						/* translators: %d: number of services */
						'services' => __('%d services', 'service-booking-manager'),
						'noTemplates' => __('No ready-made templates for this type yet. Start from scratch below.', 'service-booking-manager'),
						'use' => __('Use this template', 'service-booking-manager'),
						'liveTitle' => __('Your first service is live!', 'service-booking-manager'),
						'liveLead' => __('Customers can book it right now. Here is what you set up.', 'service-booking-manager'),
						'demoTitle' => __('Demo services imported', 'service-booking-manager'),
						/* translators: %d: number of services */
						'demoBody' => __('%d sample services are ready to explore.', 'service-booking-manager'),
					],
				]);
			}

			/** Business templates in the shape the wizard's cards need. */
			private function templates(): array {
				if (!class_exists('MPWPB_Business_Templates_Import') || !MPWPB_Business_Templates_Import::$instance) {
					return [];
				}
				$out = [];
				foreach (MPWPB_Business_Templates_Import::$instance->picker_payload() as $tpl) {
					$out[] = [
						'key' => $tpl['key'],
						'group' => $tpl['group'],
						'title' => $tpl['label'],
						'subtitle' => $tpl['subtitle'],
						'icon' => $tpl['icon'],
						'color' => $tpl['color'],
						'count' => $tpl['serviceCount'],
					];
				}
				return $out;
			}

			/** Business types offered on step 1, keyed by template group name. */
			private function business_types(): array {
				return [
					[__('Beauty & wellness', 'service-booking-manager'), 'dashicons-admin-customizer', __('Salons, spas, nails', 'service-booking-manager')],
					[__('Health', 'service-booking-manager'), 'dashicons-heart', __('Clinics, dental, therapy', 'service-booking-manager')],
					[__('Fitness & lessons', 'service-booking-manager'), 'dashicons-universal-access-alt', __('Trainers, yoga, tutors', 'service-booking-manager')],
					[__('Automotive', 'service-booking-manager'), 'dashicons-car', __('Car wash, repair, rental', 'service-booking-manager')],
					[__('Home services', 'service-booking-manager'), 'dashicons-admin-home', __('Cleaning, repairs, moving', 'service-booking-manager')],
					[__('Media & events', 'service-booking-manager'), 'dashicons-camera', __('Photo, video, decor', 'service-booking-manager')],
					[__('Pets', 'service-booking-manager'), 'dashicons-pets', __('Grooming and care', 'service-booking-manager')],
					['other', 'dashicons-screenoptions', __('Something else', 'service-booking-manager')],
				];
			}

			private function verify() {
				if (!current_user_can('manage_options') || !check_ajax_referer(self::NONCE, 'nonce', false)) {
					wp_send_json_error(['message' => __('Permission denied.', 'service-booking-manager')], 403);
				}
			}

			/** Step 2, "Start from scratch": one published service with one bookable option. */
			public function ajax_create_service() {
				$this->verify();
				$business = isset($_POST['business']) ? sanitize_text_field(wp_unslash($_POST['business'])) : '';
				$service = isset($_POST['service']) ? sanitize_text_field(wp_unslash($_POST['service'])) : '';
				$price = isset($_POST['price']) ? (float) wp_unslash($_POST['price']) : 0;
				$duration = isset($_POST['duration']) ? absint($_POST['duration']) : 60;
				$start = isset($_POST['start']) ? (float) wp_unslash($_POST['start']) : 9;
				$end = isset($_POST['end']) ? (float) wp_unslash($_POST['end']) : 17;
				$days = isset($_POST['days']) ? array_map('sanitize_key', (array) wp_unslash($_POST['days'])) : [];
				if ($business === '' || $service === '') {
					wp_send_json_error(['message' => __('Please add a business and a service name.', 'service-booking-manager')]);
				}
				if ($end <= $start) {
					wp_send_json_error(['message' => __('Closing time must be after opening time.', 'service-booking-manager')]);
				}
				$week = array_keys(MPWPB_Global_Function::week_day());
				$open = array_values(array_intersect($week, $days));
				if (!$open) {
					wp_send_json_error(['message' => __('Choose at least one open day.', 'service-booking-manager')]);
				}
				$duration = max(15, min(480, $duration));
				$post_id = wp_insert_post([
					'post_title' => $business,
					'post_status' => 'publish',
					'post_type' => 'mpwpb_item',
				], true);
				if (is_wp_error($post_id)) {
					wp_send_json_error(['message' => $post_id->get_error_message()]);
				}
				$meta = array_merge(
					MPWPB_Dummy_Import::dummy_schedule(min(60, $duration), 1, [$start, $end], implode(',', array_diff($week, $open))),
					[
						'mpwpb_template' => 'static.php',
						'mpwpb_shortcode_title' => $business,
						'mpwpb_shortcode_sub_title' => __('Book your appointment online', 'service-booking-manager'),
						'mpwpb_service_details_active' => 'on',
						'mpwpb_service_duration_active' => 'on',
						'mpwpb_service_multiple_category_check' => 'on',
						'mpwpb_multiple_service_select' => 'on',
						'mpwpb_category_service' => [],
						'mpwpb_sub_category_service' => [],
						'mpwpb_service' => [MPWPB_Dummy_Import::dummy_service($service, $price, $duration . 'min', '', 'fas fa-check')],
						'mpwpb_extra_service_active' => 'off',
						'mpwpb_extra_service' => [],
						'mpwpb_display_slider' => 'off',
						'mpwpb_slider_images' => [],
					]
				);
				foreach ($meta as $key => $value) {
					update_post_meta($post_id, $key, $value);
				}
				wp_send_json_success($this->summary((int) $post_id));
			}

			/** Step 3: WooCommerce checkout or the built-in checkout (offline payment on). */
			public function ajax_payment() {
				$this->verify();
				$type = isset($_POST['type']) ? sanitize_key(wp_unslash($_POST['type'])) : '';
				if (!in_array($type, ['woocommerce', 'custom'], true)) {
					wp_send_json_error(['message' => __('Choose a payment method.', 'service-booking-manager')]);
				}
				if ($type === 'woocommerce' && MPWPB_Global_Function::check_woocommerce() != 1) {
					wp_send_json_error(['message' => __('Install and activate WooCommerce first.', 'service-booking-manager')]);
				}
				$settings = get_option('mpwpb_payment_method_settings');
				$settings = is_array($settings) ? $settings : [];
				$settings['payment_method_type'] = $type;
				if ($type === 'custom' && empty($settings['offline_enabled'])) {
					$settings['offline_enabled'] = 'on';
				}
				update_option('mpwpb_payment_method_settings', $settings);
				wp_send_json_success(['label' => $this->payment_label($type)]);
			}

			public function ajax_summary() {
				$this->verify();
				$post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
				if (!$post_id || get_post_type($post_id) !== 'mpwpb_item') {
					wp_send_json_error(['message' => __('Service not found.', 'service-booking-manager')]);
				}
				wp_send_json_success($this->summary($post_id));
			}

			public function ajax_finish() {
				$this->verify();
				$status = isset($_POST['status']) && $_POST['status'] === 'skipped' ? 'skipped' : 'done';
				update_option(self::OPTION, $status, false);
				wp_send_json_success(['redirect' => self::services_url()]);
			}

			private function payment_label(string $type): string {
				return $type === 'woocommerce'
					? __('WooCommerce checkout', 'service-booking-manager')
					: __('Built-in checkout', 'service-booking-manager');
			}

			private function hour_text($hour): string {
				return date_i18n(get_option('time_format'), (int) round((float) $hour * 3600), true);
			}

			/** What the "done" screen shows about the service just created. */
			private function summary(int $post_id): array {
				$services = (array) get_post_meta($post_id, 'mpwpb_service', true);
				$prices = array_filter(array_map(function ($s) { return isset($s['price']) ? (float) $s['price'] : null; }, $services), function ($p) { return $p !== null; });
				$start = get_post_meta($post_id, 'mpwpb_default_start_time', true);
				$end = get_post_meta($post_id, 'mpwpb_default_end_time', true);
				$off = array_filter(explode(',', (string) get_post_meta($post_id, 'mpwpb_off_days', true)));
				$days = [];
				foreach (MPWPB_Global_Function::week_day() as $key => $label) {
					if (!in_array($key, $off, true)) {
						$days[] = function_exists('mb_substr') ? mb_substr($label, 0, 3) : substr($label, 0, 3);
					}
				}
				return [
					'post_id' => $post_id,
					'title' => html_entity_decode(get_the_title($post_id), ENT_QUOTES, 'UTF-8'),
					'services' => count($services),
					'from' => $prices ? html_entity_decode(wp_strip_all_tags(MPWPB_Global_Function::format_price(min($prices))), ENT_QUOTES, 'UTF-8') : '',
					'hours' => ($start !== '' && $end !== '') ? $this->hour_text($start) . ' – ' . $this->hour_text($end) : '',
					'days' => implode(', ', $days),
					'view_url' => get_permalink($post_id),
					'edit_url' => get_edit_post_link($post_id, 'raw'),
				];
			}

			private function hour_options(float $selected): void {
				for ($h = 0; $h <= 23.5; $h += 0.5) {
					printf('<option value="%s"%s>%s</option>', esc_attr($h), selected($h, $selected, false), esc_html($this->hour_text($h)));
				}
			}

			public function render() {
				if (!current_user_can('manage_options')) {
					return;
				}
				$wc_active = MPWPB_Global_Function::check_woocommerce() == 1;
				$payment_type = MPWPB_Global_Function::get_payment_method_type();
				if ($payment_type === '' || $payment_type === 'none' || ($payment_type === 'woocommerce' && !$wc_active)) {
					$payment_type = $wc_active ? 'woocommerce' : 'custom';
				}
				$pro = MPWPB_Global_Function::is_pro_active();
				$days = MPWPB_Global_Function::week_day();
				?>
				<div class="mpwpb-ob" data-mpwpb-ob>
					<div class="mpwpb-ob__card">

						<?php /* ---------- Welcome ---------- */ ?>
						<section class="mpwpb-ob__step is-active" data-ob-step="welcome">
							<div class="mpwpb-ob__brand">
								<span class="mpwpb-ob__mark" aria-hidden="true"><span class="dashicons dashicons-calendar-alt"></span></span>
								<span class="mpwpb-ob__brand-name"><?php esc_html_e('Service Booking Manager', 'service-booking-manager'); ?></span>
							</div>
							<h1 class="mpwpb-ob__title"><?php esc_html_e('Take bookings online in minutes', 'service-booking-manager'); ?></h1>
							<p class="mpwpb-ob__sub"><?php esc_html_e('Everything you need to run appointments, payments and staff in one place', 'service-booking-manager'); ?></p>
							<div class="mpwpb-ob__features">
								<div class="mpwpb-ob__feature">
									<span class="mpwpb-ob__feature-icon" aria-hidden="true"><span class="dashicons dashicons-performance"></span></span>
									<strong><?php esc_html_e('Quick Service Setup', 'service-booking-manager'); ?></strong>
									<span><?php esc_html_e('Go live in minutes', 'service-booking-manager'); ?></span>
								</div>
								<div class="mpwpb-ob__feature">
									<span class="mpwpb-ob__feature-icon" aria-hidden="true"><span class="dashicons dashicons-clock"></span></span>
									<strong><?php esc_html_e('Smart Scheduling', 'service-booking-manager'); ?></strong>
									<span><?php esc_html_e('Hours, breaks and time slots', 'service-booking-manager'); ?></span>
								</div>
								<div class="mpwpb-ob__feature">
									<span class="mpwpb-ob__feature-icon" aria-hidden="true"><span class="dashicons dashicons-money-alt"></span></span>
									<strong><?php esc_html_e('Get Paid Online', 'service-booking-manager'); ?></strong>
									<span><?php esc_html_e('WooCommerce or built-in checkout', 'service-booking-manager'); ?></span>
								</div>
							</div>
							<ul class="mpwpb-ob__trust">
								<li><span class="dashicons dashicons-yes-alt" aria-hidden="true"></span><?php esc_html_e('No coding needed', 'service-booking-manager'); ?></li>
								<li><span class="dashicons dashicons-cart" aria-hidden="true"></span><?php esc_html_e('Works with WooCommerce', 'service-booking-manager'); ?></li>
								<li><span class="dashicons dashicons-smartphone" aria-hidden="true"></span><?php esc_html_e('Mobile-friendly booking', 'service-booking-manager'); ?></li>
							</ul>
							<p class="mpwpb-ob__error" data-ob-error role="alert" hidden></p>
							<div class="mpwpb-ob__actions mpwpb-ob__actions--center">
								<button type="button" class="mpwpb-ob__btn mpwpb-ob__btn--ghost" data-ob-ready-site><span class="dashicons dashicons-download" aria-hidden="true"></span><?php esc_html_e('One-Click Dummy Import (Ready Site)', 'service-booking-manager'); ?></button>
								<button type="button" class="mpwpb-ob__btn mpwpb-ob__btn--primary" data-ob-go="type"><?php esc_html_e("Let's set up your business", 'service-booking-manager'); ?></button>
							</div>
						</section>

						<?php /* ---------- Steps 1-3 share a header ---------- */ ?>
						<div class="mpwpb-ob__steps-head" data-ob-head hidden>
							<ol class="mpwpb-ob__stepper" aria-label="<?php esc_attr_e('Setup progress', 'service-booking-manager'); ?>">
								<li data-ob-dot="type"><span>1</span><?php esc_html_e('Business', 'service-booking-manager'); ?></li>
								<li data-ob-dot="service"><span>2</span><?php esc_html_e('First service', 'service-booking-manager'); ?></li>
								<li data-ob-dot="payment"><span>3</span><?php esc_html_e('Payments', 'service-booking-manager'); ?></li>
							</ol>
							<button type="button" class="mpwpb-ob__exit" data-ob-skip><?php esc_html_e('Exit setup', 'service-booking-manager'); ?></button>
						</div>

						<?php /* ---------- 1. Business type ---------- */ ?>
						<section class="mpwpb-ob__step" data-ob-step="type" hidden>
							<h2 class="mpwpb-ob__h2"><?php esc_html_e('What kind of business do you run?', 'service-booking-manager'); ?></h2>
							<p class="mpwpb-ob__lead"><?php esc_html_e("We'll suggest ready-made services for your business.", 'service-booking-manager'); ?></p>
							<div class="mpwpb-ob__types" role="radiogroup" aria-label="<?php esc_attr_e('Business type', 'service-booking-manager'); ?>">
								<?php foreach ($this->business_types() as $t) :
									$label = $t[0] === 'other' ? __('Other', 'service-booking-manager') : $t[0]; ?>
									<button type="button" class="mpwpb-ob__type" role="radio" aria-checked="false" data-ob-type="<?php echo esc_attr($t[0]); ?>">
										<span class="dashicons <?php echo esc_attr($t[1]); ?>" aria-hidden="true"></span>
										<strong><?php echo esc_html($label); ?></strong>
										<span><?php echo esc_html($t[2]); ?></span>
									</button>
								<?php endforeach; ?>
							</div>
							<div class="mpwpb-ob__actions">
								<button type="button" class="mpwpb-ob__btn mpwpb-ob__btn--link" data-ob-go="welcome"><?php esc_html_e('Back', 'service-booking-manager'); ?></button>
								<button type="button" class="mpwpb-ob__btn mpwpb-ob__btn--primary" data-ob-go="service" data-ob-need-type disabled><?php esc_html_e('Next Step', 'service-booking-manager'); ?></button>
							</div>
						</section>

						<?php /* ---------- 2. First service ---------- */ ?>
						<section class="mpwpb-ob__step" data-ob-step="service" hidden>
							<h2 class="mpwpb-ob__h2"><?php esc_html_e("Let's create your first service", 'service-booking-manager'); ?></h2>
							<p class="mpwpb-ob__lead"><?php esc_html_e('Start from a ready-made business, or add your own. You can edit everything later.', 'service-booking-manager'); ?></p>
							<div class="mpwpb-ob__templates" data-ob-templates></div>
							<div class="mpwpb-ob__alt">
								<button type="button" class="mpwpb-ob__alt-btn" data-ob-scratch-toggle aria-expanded="false">
									<span class="dashicons dashicons-edit" aria-hidden="true"></span>
									<span><strong><?php esc_html_e('Start from scratch', 'service-booking-manager'); ?></strong><?php esc_html_e('Add your business and one service', 'service-booking-manager'); ?></span>
								</button>
								<button type="button" class="mpwpb-ob__alt-btn" data-ob-demo>
									<span class="dashicons dashicons-download" aria-hidden="true"></span>
									<span><strong><?php esc_html_e('Import demo services', 'service-booking-manager'); ?></strong><?php esc_html_e('7 sample businesses to explore', 'service-booking-manager'); ?></span>
								</button>
							</div>
							<form class="mpwpb-ob__form" data-ob-scratch hidden novalidate>
								<div class="mpwpb-ob__grid">
									<label class="mpwpb-ob__field mpwpb-ob__field--wide"><span><?php esc_html_e('Business name', 'service-booking-manager'); ?> <em aria-hidden="true">*</em></span>
										<input type="text" name="business" required placeholder="<?php esc_attr_e('e.g. Bright Smile Dental', 'service-booking-manager'); ?>"></label>
									<label class="mpwpb-ob__field mpwpb-ob__field--wide"><span><?php esc_html_e('First service', 'service-booking-manager'); ?> <em aria-hidden="true">*</em></span>
										<input type="text" name="service" required placeholder="<?php esc_attr_e('e.g. Check-up & clean', 'service-booking-manager'); ?>"></label>
									<label class="mpwpb-ob__field"><span><?php esc_html_e('Price', 'service-booking-manager'); ?></span>
										<input type="number" name="price" min="0" step="0.01" value="0"></label>
									<label class="mpwpb-ob__field"><span><?php esc_html_e('Duration', 'service-booking-manager'); ?></span>
										<select name="duration">
											<?php foreach ([15, 30, 45, 60, 90, 120] as $m) : ?>
												<option value="<?php echo esc_attr($m); ?>"<?php selected($m, 60); ?>><?php echo esc_html(MPWPB_Function::format_slot_duration($m)); ?></option>
											<?php endforeach; ?>
										</select></label>
									<label class="mpwpb-ob__field"><span><?php esc_html_e('Opens at', 'service-booking-manager'); ?></span>
										<select name="start"><?php $this->hour_options(9); ?></select></label>
									<label class="mpwpb-ob__field"><span><?php esc_html_e('Closes at', 'service-booking-manager'); ?></span>
										<select name="end"><?php $this->hour_options(17); ?></select></label>
									<fieldset class="mpwpb-ob__field mpwpb-ob__field--wide"><legend><?php esc_html_e('Open days', 'service-booking-manager'); ?></legend>
										<div class="mpwpb-ob__days">
											<?php foreach ($days as $key => $label) : ?>
												<label class="mpwpb-ob__day"><input type="checkbox" name="days[]" value="<?php echo esc_attr($key); ?>"<?php checked(!in_array($key, ['sunday'], true)); ?>><span><?php echo esc_html(function_exists('mb_substr') ? mb_substr($label, 0, 3) : substr($label, 0, 3)); ?></span></label>
											<?php endforeach; ?>
										</div>
									</fieldset>
								</div>
								<p class="mpwpb-ob__note"><?php esc_html_e('Your service is published right away so you can try the booking page.', 'service-booking-manager'); ?></p>
							</form>
							<p class="mpwpb-ob__error" data-ob-error role="alert" hidden></p>
							<div class="mpwpb-ob__actions">
								<button type="button" class="mpwpb-ob__btn mpwpb-ob__btn--link" data-ob-go="type"><?php esc_html_e('Back', 'service-booking-manager'); ?></button>
								<span class="mpwpb-ob__spacer"></span>
								<button type="button" class="mpwpb-ob__btn mpwpb-ob__btn--ghost" data-ob-go="payment"><?php esc_html_e('Skip step', 'service-booking-manager'); ?></button>
								<button type="button" class="mpwpb-ob__btn mpwpb-ob__btn--primary" data-ob-create disabled><?php esc_html_e('Create service', 'service-booking-manager'); ?></button>
							</div>
						</section>

						<?php /* ---------- 3. Payments ---------- */ ?>
						<section class="mpwpb-ob__step" data-ob-step="payment" hidden>
							<h2 class="mpwpb-ob__h2"><?php esc_html_e('How will customers pay?', 'service-booking-manager'); ?></h2>
							<p class="mpwpb-ob__lead"><?php esc_html_e('You can switch at any time in Settings → Payment.', 'service-booking-manager'); ?></p>
							<div class="mpwpb-ob__pay" role="radiogroup" aria-label="<?php esc_attr_e('Payment method', 'service-booking-manager'); ?>">
								<button type="button" class="mpwpb-ob__payopt<?php echo $payment_type === 'custom' ? ' is-selected' : ''; ?>" role="radio" aria-checked="<?php echo $payment_type === 'custom' ? 'true' : 'false'; ?>" data-ob-pay="custom">
									<span class="mpwpb-ob__payopt-icon" aria-hidden="true"><span class="dashicons dashicons-money-alt"></span></span>
									<span class="mpwpb-ob__payopt-body">
										<strong><?php esc_html_e('Built-in checkout', 'service-booking-manager'); ?></strong>
										<span><?php esc_html_e('No extra plugins. Customers pay on arrival or by bank transfer.', 'service-booking-manager'); ?></span>
										<span class="mpwpb-ob__tag"><?php echo $pro ? esc_html__('Stripe & PayPal available', 'service-booking-manager') : esc_html__('Stripe & PayPal with Pro', 'service-booking-manager'); ?></span>
									</span>
								</button>
								<button type="button" class="mpwpb-ob__payopt<?php echo $payment_type === 'woocommerce' ? ' is-selected' : ''; ?>" role="radio" aria-checked="<?php echo $payment_type === 'woocommerce' ? 'true' : 'false'; ?>" data-ob-pay="woocommerce"<?php disabled(!$wc_active); ?>>
									<span class="mpwpb-ob__payopt-icon" aria-hidden="true"><span class="dashicons dashicons-cart"></span></span>
									<span class="mpwpb-ob__payopt-body">
										<strong><?php esc_html_e('WooCommerce checkout', 'service-booking-manager'); ?></strong>
										<span><?php esc_html_e('Use your WooCommerce cart, payment gateways and order emails.', 'service-booking-manager'); ?></span>
										<span class="mpwpb-ob__tag<?php echo $wc_active ? ' is-ok' : ''; ?>"><?php echo $wc_active ? esc_html__('WooCommerce is active', 'service-booking-manager') : esc_html__('Install WooCommerce to use this', 'service-booking-manager'); ?></span>
									</span>
								</button>
							</div>
							<p class="mpwpb-ob__error" data-ob-error role="alert" hidden></p>
							<div class="mpwpb-ob__actions">
								<button type="button" class="mpwpb-ob__btn mpwpb-ob__btn--link" data-ob-go="service"><?php esc_html_e('Back', 'service-booking-manager'); ?></button>
								<span class="mpwpb-ob__spacer"></span>
								<button type="button" class="mpwpb-ob__btn mpwpb-ob__btn--ghost" data-ob-go="done"><?php esc_html_e('Skip step', 'service-booking-manager'); ?></button>
								<button type="button" class="mpwpb-ob__btn mpwpb-ob__btn--primary" data-ob-save-pay data-current="<?php echo esc_attr($payment_type); ?>"><?php esc_html_e('Finish setup', 'service-booking-manager'); ?></button>
							</div>
						</section>

						<?php /* ---------- Done ---------- */ ?>
						<section class="mpwpb-ob__step mpwpb-ob__done" data-ob-step="done" hidden>
							<span class="mpwpb-ob__done-icon" aria-hidden="true"><span class="dashicons dashicons-yes"></span></span>
							<h2 class="mpwpb-ob__h2" data-ob-done-title><?php esc_html_e("You're all set!", 'service-booking-manager'); ?></h2>
							<p class="mpwpb-ob__lead" data-ob-done-lead><?php esc_html_e('Your booking system is ready. Add services whenever you like.', 'service-booking-manager'); ?></p>
							<dl class="mpwpb-ob__summary" data-ob-summary hidden>
								<div><dt><?php esc_html_e('Business', 'service-booking-manager'); ?></dt><dd data-ob-sum="title"></dd></div>
								<div><dt><?php esc_html_e('Services', 'service-booking-manager'); ?></dt><dd data-ob-sum="services"></dd></div>
								<div data-ob-sum-row="from"><dt><?php esc_html_e('Prices from', 'service-booking-manager'); ?></dt><dd data-ob-sum="from"></dd></div>
								<div data-ob-sum-row="hours"><dt><?php esc_html_e('Opening hours', 'service-booking-manager'); ?></dt><dd data-ob-sum="hours"></dd></div>
								<div data-ob-sum-row="pay"><dt><?php esc_html_e('Payments', 'service-booking-manager'); ?></dt><dd data-ob-sum="pay"></dd></div>
							</dl>
							<div class="mpwpb-ob__actions mpwpb-ob__actions--center">
								<a class="mpwpb-ob__btn mpwpb-ob__btn--ghost" data-ob-edit href="#" hidden><?php esc_html_e('Edit service', 'service-booking-manager'); ?></a>
								<a class="mpwpb-ob__btn mpwpb-ob__btn--ghost" data-ob-view href="#" target="_blank" rel="noopener" hidden><?php esc_html_e('View booking page', 'service-booking-manager'); ?></a>
								<a class="mpwpb-ob__btn mpwpb-ob__btn--primary" href="<?php echo esc_url(self::services_url()); ?>"><?php esc_html_e('Go to Dashboard', 'service-booking-manager'); ?></a>
							</div>
						</section>

						<div class="mpwpb-ob__busy" data-ob-busy hidden><span class="mpwpb-ob__spin" aria-hidden="true"></span><span data-ob-busy-text></span></div>
					</div>
				</div>
				<?php
			}
		}
		new MPWPB_Onboarding();
	}
