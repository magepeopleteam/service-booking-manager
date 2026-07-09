<?php
	/*
   * @Author 		engr.sumonazma@gmail.com
   * Copyright: 	mage-people.com
   */
	if (!defined('ABSPATH')) {
		die;
	} // Cannot access pages directly.

	if (!class_exists('MPWPB_Business_Templates_Import')) {
		class MPWPB_Business_Templates_Import {

			const AJAX_ACTION = 'mpwpb_import_business_template';
			const NONCE_ACTION = 'mpwpb_admin_nonce';

			public function __construct() {
				add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
				add_action('wp_ajax_' . self::AJAX_ACTION, [$this, 'ajax_import_business_template']);
			}

			/**
			 * Only load on the Service List admin page.
			 */
			public function enqueue_assets($hook) {
				if ($hook !== 'mpwpb_item_page_mpwpb_service_list') {
					return;
				}
				wp_enqueue_style(
					'mpwpb-business-templates',
					MPWPB_PLUGIN_URL . '/assets/admin/mpwpb-business-templates.css',
					[],
					filemtime(MPWPB_PLUGIN_DIR . '/assets/admin/mpwpb-business-templates.css')
				);
				wp_enqueue_script(
					'mpwpb-business-templates',
					MPWPB_PLUGIN_URL . '/assets/admin/mpwpb-business-templates.js',
					['jquery'],
					filemtime(MPWPB_PLUGIN_DIR . '/assets/admin/mpwpb-business-templates.js'),
					true
				);
				wp_localize_script('mpwpb-business-templates', 'mpwpbBt', [
					'ajaxUrl' => admin_url('admin-ajax.php'),
					'nonce' => wp_create_nonce(self::NONCE_ACTION),
					'action' => self::AJAX_ACTION,
					'templates' => $this->picker_payload(),
					'i18n' => [
						'title' => __('One-Click Business Templates', 'service-booking-manager'),
						'intro' => __('Choose a business type to create a fully configured, ready-to-book service business in seconds. Everything imported stays fully editable afterward.', 'service-booking-manager'),
						'select' => __('Use This Template', 'service-booking-manager'),
						'servicesIncluded' => __('Services Included', 'service-booking-manager'),
						'importing' => __('Creating your business…', 'service-booking-manager'),
						'error' => __('Something went wrong. Please try again.', 'service-booking-manager'),
					],
				]);
			}

			/**
			 * Data sent to the browser for the picker cards -- label/icon/color
			 * only, never the full template content.
			 */
			private function picker_payload(): array {
				$registry = mpwpb_business_templates_registry();
				$payload = [];
				foreach ($registry as $key => $tpl) {
					$payload[] = [
						'key' => $key,
						'label' => $tpl['label'],
						'icon' => $tpl['picker_icon'],
						'color' => $tpl['color'],
						'serviceCount' => count($tpl['post']['services'] ?? []),
					];
				}
				return $payload;
			}

			public function ajax_import_business_template() {
				if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), self::NONCE_ACTION)) {
					wp_send_json_error(['message' => __('Invalid nonce!', 'service-booking-manager')]);
				}
				if (!current_user_can('manage_options')) {
					wp_send_json_error(['message' => __('Permission denied.', 'service-booking-manager')]);
				}
				$template_key = isset($_POST['template_key']) ? sanitize_key(wp_unslash($_POST['template_key'])) : '';
				$registry = mpwpb_business_templates_registry();
				if (!isset($registry[$template_key])) {
					wp_send_json_error(['message' => __('Unknown template.', 'service-booking-manager')]);
				}

				$post_id = $this->import_template($registry[$template_key]['post']);
				if (is_wp_error($post_id)) {
					wp_send_json_error(['message' => $post_id->get_error_message()]);
				}

				wp_send_json_success([
					'post_id' => $post_id,
					'edit_url' => get_edit_post_link($post_id, 'raw'),
				]);
			}

			/**
			 * @return int|WP_Error
			 */
			private function import_template(array $tpl) {
				$post_id = $this->create_business_post($tpl);
				if (is_wp_error($post_id)) {
					return $post_id;
				}

				$this->populate_pricing($post_id, $tpl);
				$this->populate_schedule($post_id, $tpl['schedule']);
				$this->populate_faq($post_id, $tpl['faqs']);
				$this->populate_recurring($post_id, $tpl['recurring']);

				$staff_ids = $this->create_staff($tpl['staff']);
				$this->assign_staff($post_id, $staff_ids);

				$this->seed_reviews($post_id, $tpl['reviews']);
				$this->recompute_rating($post_id);

				return $post_id;
			}

			private function create_business_post(array $tpl) {
				$post_id = wp_insert_post([
					'post_title' => $tpl['title'],
					'post_status' => 'publish',
					'post_type' => MPWPB_Function::get_cpt(),
				], true);
				if (is_wp_error($post_id)) {
					return $post_id;
				}

				update_post_meta($post_id, 'mpwpb_shortcode_title', $tpl['shortcode_title']);
				update_post_meta($post_id, 'mpwpb_shortcode_sub_title', $tpl['shortcode_sub_title']);
				update_post_meta($post_id, 'mpwpb_template', MPWPB_Function::sanitize_details_template_name($tpl['template']));

				return $post_id;
			}

			private function populate_pricing(int $post_id, array $tpl): void {
				$categories = [];
				foreach ($tpl['categories'] as $cat) {
					$categories[] = ['name' => $cat['name'], 'icon' => $cat['icon'], 'image' => $cat['image']];
				}
				update_post_meta($post_id, 'mpwpb_category_service', $categories);
				update_post_meta($post_id, 'mpwpb_sub_category_service', $tpl['sub_categories']);

				$services = [];
				foreach ($tpl['services'] as $svc) {
					$services[] = [
						'name' => $svc['name'],
						'price' => $svc['price'],
						'service_unit' => $svc['service_unit'],
						'duration' => $svc['duration'],
						'details' => $svc['details'],
						'icon' => $svc['icon'],
						'image' => $svc['image'],
						'show_cat_status' => 'on',
						'parent_cat' => (string) $svc['parent_cat'],
						'sub_cat' => '',
					];
				}
				update_post_meta($post_id, 'mpwpb_service', $services);

				update_post_meta($post_id, 'mpwpb_extra_service_active', $tpl['extra_service_active']);
				$extras = [];
				foreach ($tpl['extra_services'] as $extra) {
					$extras[] = [
						'name' => $extra['name'],
						'price' => $extra['price'],
						'qty' => $extra['qty'],
						'details' => $extra['details'],
						'icon' => $extra['icon'],
						'image' => $extra['image'],
					];
				}
				update_post_meta($post_id, 'mpwpb_extra_service', $extras);

				update_post_meta($post_id, 'mpwpb_service_multiple_category_check', 'on');
				update_post_meta($post_id, 'mpwpb_multiple_service_select', 'on');

				update_post_meta($post_id, 'mpwpb_features_status', 'on');
				update_post_meta($post_id, 'mpwpb_features', $tpl['features']);

				update_post_meta($post_id, 'mpwpb_service_overview_status', 'on');
				update_post_meta($post_id, 'mpwpb_service_overview_content', $tpl['overview']);
				update_post_meta($post_id, 'mpwpb_service_details_status', 'on');
				update_post_meta($post_id, 'mpwpb_service_details_content', $tpl['details']);

				// No photos in this template pack -- keep the gallery off and the
				// post thumbnail unset; the Service List table already falls back
				// to a placeholder icon when there's no thumbnail.
				update_post_meta($post_id, 'mpwpb_display_slider', 'off');
				update_post_meta($post_id, 'mpwpb_slider_images', []);
			}

			private function populate_faq(int $post_id, array $faqs): void {
				update_post_meta($post_id, 'mpwpb_faq_active', 'on');
				update_post_meta($post_id, 'mpwpb_faq', $faqs);
			}

			private function populate_recurring(int $post_id, array $recurring): void {
				update_post_meta($post_id, 'mpwpb_enable_recurring', !empty($recurring['enabled']) ? 'yes' : 'no');
				update_post_meta($post_id, 'mpwpb_recurring_types', $recurring['types']);
				update_post_meta($post_id, 'mpwpb_max_recurring_count', absint($recurring['max_count']));
				update_post_meta($post_id, 'mpwpb_recurring_discount', absint($recurring['discount']));
			}

			private function populate_schedule(int $post_id, array $schedule): void {
				update_post_meta($post_id, 'mpwpb_date_type', 'repeated');
				update_post_meta($post_id, 'mpwpb_particular_dates', []);
				update_post_meta($post_id, 'mpwpb_repeated_start_date', current_time('Y-m-d'));
				update_post_meta($post_id, 'mpwpb_repeated_after', 1);
				update_post_meta($post_id, 'mpwpb_active_days', absint($schedule['active_days']));
				update_post_meta($post_id, 'mpwpb_time_slot_length', absint($schedule['time_slot_length']));
				update_post_meta($post_id, 'mpwpb_capacity_per_session', absint($schedule['capacity_per_session']));

				$this->write_day_schedule_meta(
					$schedule,
					function ($key, $value) use ($post_id) {
						update_post_meta($post_id, $key, $value);
					}
				);

				update_post_meta($post_id, 'mpwpb_off_days', implode(',', $schedule['off_days']));
				update_post_meta($post_id, 'mpwpb_off_dates', []);
			}

			/**
			 * Shared by both the business post schedule (post meta) and staff
			 * schedules (user meta) -- same 'mpwpb_{day}_start_time' etc. shape
			 * in both places, only the meta-write callback differs. Days that
			 * have no explicit day_overrides entry are left blank so they fall
			 * back to the 'default' row at runtime (MPWPB_Function::get_time_slot),
			 * matching how the plugin's own manual-save and dummy-import already
			 * populate this data.
			 */
			private function write_day_schedule_meta(array $schedule, callable $write): void {
				$write('mpwpb_default_start_time', $schedule['default']['start']);
				$write('mpwpb_default_end_time', $schedule['default']['end']);
				$write('mpwpb_default_start_break_time', $schedule['default']['break_start']);
				$write('mpwpb_default_end_break_time', $schedule['default']['break_end']);

				$days = MPWPB_Global_Function::week_day();
				foreach ($days as $key => $label) {
					$override = $schedule['day_overrides'][$key] ?? null;
					$write('mpwpb_' . $key . '_start_time', $override['start'] ?? '');
					$write('mpwpb_' . $key . '_end_time', $override['end'] ?? '');
					$write('mpwpb_' . $key . '_start_break_time', $override['break_start'] ?? '');
					$write('mpwpb_' . $key . '_end_break_time', $override['break_end'] ?? '');
				}
			}

			/**
			 * @return int[] newly created mpwpb_staff user IDs
			 */
			private function create_staff(array $staff_defs): array {
				$staff_ids = [];
				foreach ($staff_defs as $staff) {
					$username = $this->unique_username($staff['username_base']);
					$email = $this->unique_email($staff['email_local']);
					$password = wp_generate_password(20, true);

					$user_id = wp_create_user($username, $password, $email);
					if (is_wp_error($user_id)) {
						continue;
					}

					wp_update_user([
						'ID' => $user_id,
						'first_name' => $staff['first_name'],
						'last_name' => $staff['last_name'],
						'display_name' => $staff['first_name'],
						'role' => 'mpwpb_staff',
					]);

					update_user_meta($user_id, 'date_type', 'repeated');
					update_user_meta($user_id, 'mpwpb_staff_modify_holiday', '');
					update_user_meta($user_id, 'mpwpb_particular_dates', []);
					update_user_meta($user_id, 'mpwpb_repeated_start_date', current_time('Y-m-d'));
					update_user_meta($user_id, 'mpwpb_repeated_after', 1);

					$this->write_day_schedule_meta(
						$staff['schedule'],
						function ($key, $value) use ($user_id) {
							update_user_meta($user_id, $key, $value);
						}
					);

					update_user_meta($user_id, 'mpwpb_off_days', implode(',', $staff['schedule']['off_days']));
					update_user_meta($user_id, 'mpwpb_off_dates', []);
					update_user_meta($user_id, 'mpwpb_custom_profile_image', '');

					$staff_ids[] = $user_id;
				}
				return $staff_ids;
			}

			private function assign_staff(int $post_id, array $staff_ids): void {
				if (empty($staff_ids)) {
					return;
				}
				update_post_meta($post_id, 'mpwpb_staff_member_add', 'on');
				update_post_meta($post_id, 'mpwpb_selected_staff_ids', $staff_ids);
			}

			private function unique_username(string $base): string {
				$username = $base . '_demo';
				$suffix = 2;
				while (username_exists($username)) {
					$username = $base . '_demo_' . $suffix;
					$suffix++;
				}
				return $username;
			}

			private function unique_email(string $local): string {
				$domain = wp_parse_url(home_url(), PHP_URL_HOST) ?: 'example.com';
				$email = $local . '.demo@' . $domain;
				$suffix = 2;
				while (email_exists($email)) {
					$email = $local . '.demo' . $suffix . '@' . $domain;
					$suffix++;
				}
				return $email;
			}

			private function seed_reviews(int $post_id, array $reviews): void {
				global $wpdb;
				$this->ensure_reviews_table();
				$table_name = $wpdb->prefix . 'mpwpb_reviews';

				foreach ($reviews as $review) {
					$date_created = gmdate('Y-m-d H:i:s', strtotime('-' . absint($review['days_ago']) . ' days', current_time('timestamp')));
					$wpdb->insert(
						$table_name,
						[
							'service_id' => $post_id,
							'user_id' => 0,
							'user_name' => $review['name'],
							'rating' => absint($review['rating']),
							'title' => $review['title'],
							'content' => $review['content'],
							'status' => 'approved',
							'date_created' => $date_created,
						],
						['%d', '%d', '%s', '%d', '%s', '%s', '%s', '%s']
					);
				}
			}

			/**
			 * Mirrors MPWPB_Reviews_Admin::create_reviews_table() -- that method
			 * is private, and its constructor already runs this on every admin
			 * load, so in practice the table already exists by the time this
			 * fires. Kept as a cheap, idempotent safety net (SHOW TABLES LIKE
			 * guard, same as the original) rather than a hard dependency.
			 */
			private function ensure_reviews_table(): void {
				global $wpdb;
				$table_name = $wpdb->prefix . 'mpwpb_reviews';
				if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
					return;
				}
				$charset_collate = $wpdb->get_charset_collate();
				$sql = "CREATE TABLE $table_name (
					id bigint(20) NOT NULL AUTO_INCREMENT,
					service_id bigint(20) NOT NULL,
					user_id bigint(20) NOT NULL,
					user_name varchar(100) NOT NULL,
					rating int(1) NOT NULL,
					title varchar(255) NOT NULL,
					content text NOT NULL,
					status varchar(20) NOT NULL DEFAULT 'pending',
					date_created datetime NOT NULL,
					PRIMARY KEY  (id)
				) $charset_collate;";
				require_once ABSPATH . 'wp-admin/includes/upgrade.php';
				dbDelta($sql);
			}

			/**
			 * Mirrors MPWPB_Reviews_Admin::update_service_rating() (private) --
			 * recomputes the 3 summary meta fields from the real 'approved' rows
			 * this class just inserted, so they stay in sync.
			 */
			private function recompute_rating(int $post_id): void {
				global $wpdb;
				$table_name = $wpdb->prefix . 'mpwpb_reviews';
				$reviews = $wpdb->get_results($wpdb->prepare(
					"SELECT rating FROM $table_name WHERE service_id = %d AND status = 'approved'",
					$post_id
				));

				$count = count($reviews);
				$total = 0;
				foreach ($reviews as $review) {
					$total += (int) $review->rating;
				}
				$average = $count > 0 ? $total / $count : 0;

				update_post_meta($post_id, 'mpwpb_service_review_ratings', number_format($average, 1));
				update_post_meta($post_id, 'mpwpb_service_rating_scale', esc_html__('Out of 5', 'service-booking-manager')); // phpcs:ignore -- mirrors MPWPB_Reviews_Admin::update_service_rating()'s exact save pattern
				update_post_meta($post_id, 'mpwpb_service_rating_text', sprintf(
					/* translators: %d: number of ratings */
					esc_html__('(%d ratings)', 'service-booking-manager'), // phpcs:ignore -- same
					$count
				));
			}
		}
	}
	new MPWPB_Business_Templates_Import();
