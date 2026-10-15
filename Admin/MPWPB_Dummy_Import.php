<?php
	/*
   * @Author 		engr.sumonazma@gmail.com
   * Copyright: 	mage-people.com
   */
	if (!defined('ABSPATH')) {
		die;
	} // Cannot access pages directly.
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	if (!class_exists('MPWPB_Dummy_Import')) {
		class MPWPB_Dummy_Import {
			public function __construct() {
				add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
				add_action('admin_footer', array($this, 'render_popup'));
				add_action('wp_ajax_mpwpb_import_dummy_data', array($this, 'ajax_import_dummy_data'));
				add_action('wp_ajax_mpwpb_import_dummy_step', array($this, 'ajax_import_dummy_step'));
				add_action('wp_ajax_mpwpb_dismiss_dummy_import', array($this, 'ajax_dismiss_dummy_import'));
			}

			public static function is_already_imported() {
				return get_option('mpwpb_dummy_already_inserted') == 'yes';
			}

			/**
			 * The demo services are plain mpwpb_item posts + meta -- they never
			 * touch WooCommerce -- so the import is offered regardless of the
			 * payment mode. It only requires that the site has no services yet.
			 */
			public function is_eligible() {
				$count_posts = wp_count_posts('mpwpb_item');
				$count_existing = isset($count_posts->publish) ? $count_posts->publish : 0;
				return $count_existing === 0;
			}

			/** Screens the import widget is allowed to appear on. */
			private function is_import_screen(): bool {
				$screen = function_exists('get_current_screen') ? get_current_screen() : null;
				$screen_id = $screen ? $screen->id : '';
				return in_array($screen_id, array('mpwpb_item_page_mpwpb_service_list', 'mpwpb_item_page_mpwpb_status_page'), true);
			}

			private function should_auto_show_popup() {
				if (self::is_already_imported()) {
					return false;
				}
				$dismissed = get_option('mpwpb_dummy_import_dismissed');
				if ($dismissed == 'yes') {
					return false;
				}
				$count_posts = wp_count_posts('mpwpb_item');
				$count_existing = isset($count_posts->publish) ? $count_posts->publish : 0;
				if ($count_existing > 0) {
					return false;
				}
				return true;
			}

			public function enqueue_assets() {
				// Scoped to the two screens the widget actually renders on
				// (Service List / Status) rather than gated on WooCommerce --
				// the import works in standalone (Custom Payment) mode too.
				if (!$this->is_import_screen()) {
					return;
				}
				wp_enqueue_style(
					'mpwpb-dummy-installer',
					MPWPB_PLUGIN_URL . '/assets/admin/mpwpb_woo_installer.css',
					array(),
					filemtime(MPWPB_PLUGIN_DIR . '/assets/admin/mpwpb_woo_installer.css')
				);
			}

			public function render_popup() {
				// Show the corner widget only on the plugin's Service List and Status
				// screens — never scattered across every admin page. Its styles are
				// enqueued on exactly these same screens (see enqueue_assets), so the
				// markup is never left unstyled. Not gated on WooCommerce: the demo
				// services are plain posts and import fine in standalone mode.
				if (!$this->is_import_screen()) {
					return;
				}
				$screen    = function_exists('get_current_screen') ? get_current_screen() : null;
				$screen_id = $screen ? $screen->id : '';
				// Auto-run the import (no confirmation modal) on the Service List page
				// when the site has no services yet and it was not skipped before.
				$auto_start = ($screen_id === 'mpwpb_item_page_mpwpb_service_list') && $this->should_auto_show_popup();
				?>
				<!-- Floating circular progress widget (bottom-right) -->
				<div id="mpwpb-import-progress" class="mpwpb-ipw" role="status" aria-live="polite">
					<button type="button" class="mpwpb-ipw-close" aria-label="<?php esc_attr_e('Skip', 'service-booking-manager'); ?>" title="<?php esc_attr_e('Skip', 'service-booking-manager'); ?>">
						<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
					</button>
					<div class="mpwpb-ipw-ring">
						<svg viewBox="0 0 120 120" aria-hidden="true">
							<defs>
								<linearGradient id="mpwpbIpwGrad" x1="0%" y1="0%" x2="100%" y2="100%">
									<stop offset="0%" stop-color="#2563eb"/>
									<stop offset="100%" stop-color="#3b82f6"/>
								</linearGradient>
							</defs>
							<circle class="mpwpb-ipw-track" cx="60" cy="60" r="52"></circle>
							<circle class="mpwpb-ipw-fill" cx="60" cy="60" r="52"></circle>
						</svg>
						<div class="mpwpb-ipw-center"><span class="mpwpb-ipw-num">0</span><span class="mpwpb-ipw-pct">%</span></div>
						<div class="mpwpb-ipw-check" aria-hidden="true">
							<svg viewBox="0 0 24 24" fill="none"><path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
						</div>
					</div>
					<div class="mpwpb-ipw-body">
						<div class="mpwpb-ipw-title"><?php esc_html_e('Importing Services', 'service-booking-manager'); ?></div>
						<div class="mpwpb-ipw-status"><?php esc_html_e('Preparing…', 'service-booking-manager'); ?></div>
					</div>
				</div>

				<script>
				(function($) {
					$(document).ready(function() {
						var $widget  = $('#mpwpb-import-progress');
						if (!$widget.length) return;

						var $ring    = $widget.find('.mpwpb-ipw-fill');
						var $num     = $widget.find('.mpwpb-ipw-num');
						var $wtitle  = $widget.find('.mpwpb-ipw-title');
						var $wstatus = $widget.find('.mpwpb-ipw-status');

						var CIRC         = 2 * Math.PI * 52; // ring circumference (r = 52)
						var nonce        = '<?php echo wp_create_nonce("mpwpb_import_dummy"); ?>';
						var dismissNonce = '<?php echo wp_create_nonce("mpwpb_dismiss_dummy"); ?>';
						var autoStart    = <?php echo $auto_start ? 'true' : 'false'; ?>;
						var isWorking    = false;
						var cancelled    = false;
						var currentStep  = 0;
						var stepRetries  = 0;
						var shownPct     = 0;
						var rafId        = null;

						var i18n = {
							importing:  '<?php echo esc_js(__("Importing Services", "service-booking-manager")); ?>',
							preparing:  '<?php echo esc_js(__("Preparing demo data…", "service-booking-manager")); ?>',
							adding:     '<?php echo esc_js(__("Adding", "service-booking-manager")); ?>',
							complete:   '<?php echo esc_js(__("Import complete!", "service-booking-manager")); ?>',
							reloading:  '<?php echo esc_js(__("All set — reloading…", "service-booking-manager")); ?>',
							retry:      '<?php echo esc_js(__("Connection hiccup — retrying…", "service-booking-manager")); ?>',
							failed:     '<?php echo esc_js(__("Import failed. Please try again.", "service-booking-manager")); ?>',
							already:    '<?php echo esc_js(__("Demo data is already present.", "service-booking-manager")); ?>'
						};

						function setRing(pct) {
							pct = Math.max(0, Math.min(100, pct));
							$ring.css('stroke-dashoffset', CIRC * (1 - pct / 100));
						}

						// Smoothly count the number up to the latest real percentage so the
						// ring never looks frozen between AJAX round-trips.
						function animateTo(target) {
							target = Math.max(0, Math.min(100, Math.round(target)));
							setRing(target); // CSS transition handles the ring glide
							if (rafId) { cancelAnimationFrame(rafId); rafId = null; }
							var start = shownPct;
							if (target <= start) { shownPct = target; $num.text(target); return; }
							var t0 = null;
							function frame(ts) {
								if (t0 === null) { t0 = ts; }
								var p   = Math.min(1, (ts - t0) / 450);
								var val = Math.round(start + (target - start) * p);
								shownPct = val;
								$num.text(val);
								rafId = (p < 1) ? requestAnimationFrame(frame) : null;
							}
							rafId = requestAnimationFrame(frame);
						}

						function resetWidget() {
							$widget.removeClass('mpwpb-ipw-done mpwpb-ipw-error');
							shownPct = 0;
							setRing(0);
							$num.text('0');
							$wtitle.text(i18n.importing);
							$wstatus.text(i18n.preparing);
						}

						function startImport() {
							if (isWorking) return;
							isWorking   = true;
							cancelled   = false;
							currentStep = 0;
							stepRetries = 0;
							resetWidget();
							$widget.addClass('mpwpb-ipw-open');
							// small delay so the widget's entrance animation is seen first
							setTimeout(function() { if (!cancelled) { runStep(0); } }, 450);
						}

						function runStep(step) {
							if (cancelled) return;
							currentStep = step;
							$.ajax({
								url: ajaxurl,
								type: 'POST',
								data: { action: 'mpwpb_import_dummy_step', step: step, nonce: nonce },
								success: function(response) {
									if (cancelled) return;
									if (!response || !response.success || !response.data) { return handleError(); }
									stepRetries = 0;
									var d   = response.data;
									var pct = (typeof d.percent !== 'undefined') ? parseInt(d.percent, 10) : 0;
									animateTo(pct);
									if (d.label) { $wstatus.text(i18n.adding + ' ' + d.label); }
									if (d.done) { d.skipped ? alreadyDone() : finish(); }
									else { runStep(parseInt(d.step, 10)); }
								},
								error: function() { if (!cancelled) { handleError(); } }
							});
						}

						function finish() {
							animateTo(100);
							$widget.addClass('mpwpb-ipw-done');
							$wtitle.text(i18n.complete);
							$wstatus.text(i18n.reloading);
							setTimeout(function() { window.location.reload(); }, 1400);
						}

						// Backend reported nothing to import (data already exists): just
						// acknowledge and slide away without a disruptive reload.
						function alreadyDone() {
							isWorking = false;
							$widget.addClass('mpwpb-ipw-done');
							$wtitle.text(i18n.complete);
							$wstatus.text(i18n.already);
							setTimeout(function() { $widget.removeClass('mpwpb-ipw-open'); }, 2600);
						}

						function handleError() {
							if (stepRetries < 2) {
								stepRetries++;
								$wstatus.text(i18n.retry);
								setTimeout(function() { if (!cancelled) { runStep(currentStep); } }, 900 * stepRetries);
								return;
							}
							isWorking = false;
							$widget.addClass('mpwpb-ipw-error');
							$wtitle.text(i18n.failed);
							$wstatus.text('');
							setTimeout(function() { $widget.removeClass('mpwpb-ipw-open'); }, 3200);
						}

						// Skip / close — stop further steps and remember the choice so the
						// import does not auto-prompt again on the next page load.
						$widget.on('click', '.mpwpb-ipw-close', function(e) {
							e.preventDefault();
							cancelled = true;
							isWorking = false;
							if (rafId) { cancelAnimationFrame(rafId); rafId = null; }
							$widget.removeClass('mpwpb-ipw-open');
							$.ajax({
								url: ajaxurl,
								type: 'POST',
								data: { action: 'mpwpb_dismiss_dummy_import', nonce: dismissNonce }
							});
						});

						// Manual trigger (e.g. the Status page "Import Dummy Data" button).
						$(document).on('click', '#mpwpb-trigger-dummy-import-btn', function(e) {
							e.preventDefault();
							isWorking = false;
							startImport();
						});

						if (autoStart) {
							startImport();
						}
					});
				})(jQuery);
				</script>
				<?php
			}

			public function ajax_import_dummy_data() {
				check_ajax_referer('mpwpb_import_dummy', 'nonce');
				if (!current_user_can('manage_options')) {
					wp_send_json_error(array('message' => 'Permission denied.'));
				}
				delete_option('mpwpb_dummy_already_inserted');
				$this->do_dummy_import();
				wp_send_json_success();
			}

			public function ajax_dismiss_dummy_import() {
				check_ajax_referer('mpwpb_dismiss_dummy', 'nonce');
				if (!current_user_can('manage_options')) {
					wp_send_json_error(array('message' => 'Permission denied.'));
				}
				update_option('mpwpb_dummy_import_dismissed', 'yes');
				wp_send_json_success();
			}

			/**
			 * Stepped / batched dummy import — imports ONE service per AJAX request.
			 *
			 * The legacy single-request importer built all services (each with a
			 * full-size image + generated thumbnails) in one PHP request, which could
			 * exhaust memory or hit max_execution_time on constrained hosts and crash
			 * the import. Processing one service per round keeps every request light.
			 *
			 * Request : step  — 0-based index of the service to import this round.
			 * Response: done, step (completed count), total, percent, label.
			 */
			public function ajax_import_dummy_step() {
				check_ajax_referer('mpwpb_import_dummy', 'nonce');
				if (!current_user_can('manage_options')) {
					wp_send_json_error(array('message' => __('Permission denied.', 'service-booking-manager')));
				}
				$step  = isset($_POST['step']) ? absint($_POST['step']) : 0;
				$items = $this->get_dummy_items();
				$total = count($items);

				if ($total === 0) {
					update_option('mpwpb_dummy_already_inserted', 'yes');
					wp_send_json_success(array('done' => true, 'step' => 0, 'total' => 0, 'percent' => 100, 'skipped' => true));
				}

				// First round: run the eligibility guard once and seed taxonomies.
				// The guard only fires on step 0 because later steps intentionally
				// find the services they just inserted — re-running a "no services
				// yet" check would abort the import mid-way.
				if ($step === 0) {
					$existing = MPWPB_Global_Function::query_post_type('mpwpb_item');
					if ($existing->post_count > 0) {
						wp_send_json_success(array('done' => true, 'step' => $total, 'total' => $total, 'percent' => 100, 'skipped' => true));
					}
					delete_option('mpwpb_dummy_already_inserted');
					$this->import_taxonomies($this->dummy_data());
				}

				if ($step >= $total) {
					update_option('mpwpb_dummy_already_inserted', 'yes');
					wp_send_json_success(array('done' => true, 'step' => $total, 'total' => $total, 'percent' => 100));
				}

				$label = isset($items[$step]['name']) ? $items[$step]['name'] : '';
				$this->import_single_item($items[$step], $step);

				$next = $step + 1;
				$done = $next >= $total;
				if ($done) {
					update_option('mpwpb_dummy_already_inserted', 'yes');
				}
				wp_send_json_success(array(
					'done'    => $done,
					'step'    => $next,
					'total'   => $total,
					'percent' => (int) round(($next / $total) * 100),
					'label'   => $label,
				));
			}

			/**
			 * Flat, 0-indexed list of the dummy services to import.
			 */
			private function get_dummy_items(): array {
				$data = $this->dummy_data();
				return isset($data['custom_post']['mpwpb_item']) && is_array($data['custom_post']['mpwpb_item'])
					? array_values($data['custom_post']['mpwpb_item'])
					: array();
			}

			/**
			 * Seed any dummy taxonomy terms that do not already exist.
			 */
			private function import_taxonomies(array $dummy_data) {
				if (empty($dummy_data['taxonomy']) || !is_array($dummy_data['taxonomy'])) {
					return;
				}
				foreach ($dummy_data['taxonomy'] as $taxonomy => $dummy_taxonomy) {
					$check_taxonomy = MPWPB_Global_Function::get_taxonomy($taxonomy);
					if (is_string($check_taxonomy) || sizeof($check_taxonomy) == 0) {
						foreach ($dummy_taxonomy as $taxonomy_data) {
							wp_insert_term($taxonomy_data['name'], $taxonomy);
						}
					}
				}
			}

			/**
			 * Insert a single dummy service (post + meta + featured image).
			 *
			 * @param array $dummy_data Service definition from dummy_data().
			 * @param int   $key        Index used to locate its bundled image.
			 */
			private function import_single_item(array $dummy_data, $key) {
				$post_id = wp_insert_post(array(
					'post_title'  => isset($dummy_data['name']) ? $dummy_data['name'] : '',
					'post_status' => 'publish',
					'post_type'   => 'mpwpb_item',
				));
				if (is_wp_error($post_id) || !$post_id) {
					return;
				}
				if (array_key_exists('post_data', $dummy_data) && is_array($dummy_data['post_data'])) {
					foreach ($dummy_data['post_data'] as $meta_key => $data) {
						update_post_meta($post_id, $meta_key, $data);
					}
				}
				if (!empty($dummy_data['reviews']) && is_array($dummy_data['reviews'])) {
					self::import_dummy_reviews((int) $post_id, $dummy_data['reviews']);
				}
				$image = MPWPB_PLUGIN_DIR . '/assets/images/dummy-image-' . $key . '.png';
				if (!file_exists($image)) {
					$image = MPWPB_PLUGIN_DIR . '/assets/images/dummy-image-' . $key . '.jpg';
				}
				if (file_exists($image)) {
					$image_attached = self::insert_media($image);
					if (is_array($image_attached) && !empty($image_attached['id'])) {
						set_post_thumbnail($post_id, $image_attached['id']);
					}
				}
			}

			private function do_dummy_import() {
				$dummy_post = get_option('mpwpb_dummy_already_inserted');
				$all_post   = MPWPB_Global_Function::query_post_type('mpwpb_item');
				if ($all_post->post_count == 0 && $dummy_post != 'yes') {
					$dummy_data = $this->dummy_data();
					$this->import_taxonomies($dummy_data);
					foreach ($this->get_dummy_items() as $key => $item) {
						$this->import_single_item($item, $key);
					}
					update_option('mpwpb_dummy_already_inserted', 'yes');
				}
			}
			/**
			 * Shared booking-calendar meta for one dummy service: a rolling
			 * "repeated" calendar starting today, the default day plus any
			 * per-day overrides, and the off days.
			 *
			 * @param array $hours    ['start','end','break_start','break_end'] for the default day.
			 * @param array $per_day  day key => ['start','end'] overrides (e.g. a shorter Saturday).
			 */
			private static function dummy_schedule(int $slot, int $capacity, array $hours, string $off_days, array $per_day = array()): array {
				$meta = array(
					'mpwpb_date_type' => 'repeated',
					'mpwpb_repeated_start_date' => current_time('Y-m-d'),
					'mpwpb_repeated_after' => 1,
					'mpwpb_active_days' => 60,
					'mpwpb_particular_dates' => array(),
					'mpwpb_time_slot_length' => (string) $slot,
					'mpwpb_capacity_per_session' => (string) $capacity,
					'mpwpb_default_start_time' => (string) $hours[0],
					'mpwpb_default_end_time' => (string) $hours[1],
					'mpwpb_default_start_break_time' => isset($hours[2]) ? (string) $hours[2] : '',
					'mpwpb_default_end_break_time' => isset($hours[3]) ? (string) $hours[3] : '',
					'mpwpb_off_days' => $off_days,
					'mpwpb_off_dates' => array(),
				);
				foreach (array_keys(MPWPB_Global_Function::week_day()) as $day) {
					$own = isset($per_day[$day]) ? $per_day[$day] : array();
					$meta['mpwpb_' . $day . '_start_time'] = isset($own[0]) ? (string) $own[0] : '';
					$meta['mpwpb_' . $day . '_end_time'] = isset($own[1]) ? (string) $own[1] : '';
					$meta['mpwpb_' . $day . '_start_break_time'] = isset($own[2]) ? (string) $own[2] : '';
					$meta['mpwpb_' . $day . '_end_break_time'] = isset($own[3]) ? (string) $own[3] : '';
				}
				return $meta;
			}

			/**
			 * One bookable service row in the shape the Services & Pricing
			 * editor saves. $cat / $sub are indexes into the category and
			 * sub-category lists ('' = none).
			 */
			private static function dummy_service(string $name, $price, string $duration, string $details, string $icon, $cat = '', $sub = ''): array {
				return array(
					'name' => $name,
					'price' => $price,
					'service_unit' => '',
					'duration' => $duration,
					'details' => $details,
					'icon' => $icon,
					'image' => '',
					'show_cat_status' => $cat === '' ? 'off' : 'on',
					'parent_cat' => $cat === '' ? '' : (string) $cat,
					'sub_cat' => $sub === '' ? '' : (string) $sub,
				);
			}

			/** One optional add-on; qty is the most a customer can add to one booking. */
			private static function dummy_extra(string $name, $price, string $details, string $icon, int $qty = 1): array {
				return array('name' => $name, 'price' => $price, 'qty' => $qty, 'details' => $details, 'icon' => $icon, 'image' => '');
			}

			public function dummy_data(): array {
				$common = array(
					'mpwpb_template' => 'static.php',
					'mpwpb_service_details_active' => 'on',
					'mpwpb_service_duration_active' => 'on',
					'mpwpb_service_multiple_category_check' => 'on',
					'mpwpb_multiple_service_select' => 'on',
					'mpwpb_group_extra_service_active' => 'off',
					'mpwpb_extra_service_active' => 'on',
					// No gallery photos ship with the demo, so the slider stays off
					// rather than rendering an empty frame.
					'mpwpb_display_slider' => 'off',
					'mpwpb_slider_images' => array(),
					'mpwpb_faq_active' => 'on',
					'mpwpb_features_status' => 'on',
					'mpwpb_service_overview_status' => 'on',
					'mpwpb_service_details_status' => 'on',
				);
				return [
					'taxonomy' => [],
					'custom_post' => [
						'mpwpb_item' => [
							// Image: dummy-image-0.png (salon chair).
							0 => [
								'name' => 'Luxe Hair Studio',
								'post_data' => array_merge($common, self::dummy_schedule(30, 2, array(9, 19, 13, 14), 'monday', array('saturday' => array(9, 17), 'sunday' => array(10, 16))), [
									'mpwpb_shortcode_title' => 'Hair Salon',
									'mpwpb_shortcode_sub_title' => 'Cuts, colour and styling by experienced stylists',
									'mpwpb_category_text' => 'Category',
									'mpwpb_sub_category_text' => 'Sub-Category',
									'mpwpb_service_text' => 'Service',
									'mpwpb_category_service' => [
										['name' => 'Haircuts', 'icon' => 'fas fa-cut', 'image' => ''],
										['name' => 'Colour', 'icon' => 'fas fa-palette', 'image' => ''],
										['name' => 'Styling & Treatments', 'icon' => 'fas fa-magic', 'image' => ''],
									],
									'mpwpb_sub_category_service' => [],
									'mpwpb_service' => [
										self::dummy_service("Women's Cut & Blow-Dry", 55, '1h', 'Consultation, wash, precision cut and a smooth blow-dry finish.', 'fas fa-cut', 0),
										self::dummy_service("Men's Haircut", 30, '30min', 'Scissor or clipper cut, styled to finish. Includes a hot-towel neck shave.', 'fas fa-cut', 0),
										self::dummy_service("Kids' Haircut (under 12)", 20, '30min', 'A quick, friendly cut for children under 12.', 'fas fa-child', 0),
										self::dummy_service('Beard Trim & Shape', 15, '15min', 'Beard trimmed, shaped and lined up with a hot towel finish.', 'fas fa-user', 0),
										self::dummy_service('Root Touch-Up', 65, '1h 15m', 'Single-colour application to refresh up to 3 cm of regrowth.', 'fas fa-palette', 1),
										self::dummy_service('Full Highlights', 120, '2h', 'Foil highlights through the whole head, toner and blow-dry included.', 'fas fa-palette', 1),
										self::dummy_service('Balayage', 160, '2h 30m', 'Hand-painted, natural-looking colour with toner and style.', 'fas fa-paint-brush', 1),
										self::dummy_service('Blow-Dry & Style', 35, '45min', 'Wash and a smooth, bouncy or curled blow-dry for any occasion.', 'fas fa-wind', 2),
										self::dummy_service('Keratin Smoothing Treatment', 180, '2h 30m', 'Cuts frizz and drying time for up to 3 months.', 'fas fa-magic', 2),
									],
									'mpwpb_extra_service' => [
										self::dummy_extra('Deep Conditioning Mask', 15, 'Intensive moisture treatment for dry or coloured hair.', 'fas fa-tint'),
										self::dummy_extra('Scalp Massage', 10, 'Relaxing 10-minute scalp massage during your wash.', 'fas fa-spa'),
										self::dummy_extra('Gloss Toner', 25, 'Adds shine and refreshes the tone of coloured hair.', 'fas fa-star'),
									],
									// Regulars can rebook the same cut on a repeating schedule.
									'mpwpb_enable_recurring' => 'yes',
									'mpwpb_recurring_types' => ['weekly', 'bi-weekly', 'monthly'],
									'mpwpb_max_recurring_count' => 12,
									'mpwpb_recurring_discount' => 0,
									'mpwpb_features' => ['Senior & Junior Stylists', 'Professional Salon Colour Brands', 'Free Colour Consultation', 'Open Until 7 PM on Weekdays'],
									'mpwpb_faq' => [
										['title' => 'Do I need a patch test before colouring?', 'content' => '<p>Yes. If you are new to us or have not had colour with us in the last 6 months, please pop in for a free patch test at least 48 hours before your appointment.</p>'],
										['title' => 'Can I choose my stylist?', 'content' => '<p>Yes. Pick your preferred stylist when you book, or choose any available stylist for the earliest slot.</p>'],
										['title' => 'How early should I arrive?', 'content' => '<p>Please arrive 5 minutes before your appointment. If you are more than 15 minutes late we may need to shorten or reschedule your service.</p>'],
										['title' => 'What is your cancellation policy?', 'content' => '<p>You can cancel or reschedule free of charge up to 24 hours before your appointment.</p>'],
									],
									'mpwpb_service_overview_content' => '<p><strong>Luxe Hair Studio</strong> is a friendly neighbourhood salon for cuts, colour and styling, with experienced stylists for every hair type.</p><ol><li>Choose your service and stylist.</li><li>Pick a date and time that suits you.</li><li>Add a treatment if you would like one.</li><li>Confirm, and we will see you in the chair.</li></ol>',
									'mpwpb_service_details_content' => '<p>Every appointment starts with a short consultation so your stylist understands the look you want and how you style your hair at home.</p><p><strong>What is included</strong></p><ul><li>Consultation and wash with professional products</li><li>Cut or colour by a qualified stylist</li><li>Finishing style and aftercare advice</li></ul><p><strong>Before colour appointments</strong></p><p>New colour clients need a quick patch test at least 48 hours beforehand. It is free and takes five minutes.</p>',
									'mpwpb_service_rating_scale' => 'Out of 5',
								]),
								'reviews' => [
									['Olivia Bennett', 5, 'Stylist really listened', 'Showed a reference photo and got exactly the cut I wanted. Booking online saved me from the usual phone tag.'],
									['Marcus Johnson', 5, 'Quick, clean, professional', 'In and out in under 40 minutes with a great fade. The salon was clean and the stylist was easy to talk to.'],
									['Chloe Dubois', 4, 'Nice result, small wait', 'Appointment started about 15 minutes late but the haircut itself was worth it. Will book again.'],
									['Ethan Walsh', 5, "Best cut I've had locally", 'Found my new regular spot. The online booking calendar showed real availability, no back-and-forth needed.'],
								],
							],
							// Image: dummy-image-1.png (foam car wash).
							1 => [
								'name' => 'Sparkle Car Wash & Detailing',
								'post_data' => array_merge($common, self::dummy_schedule(30, 3, array(8, 18), '', array('sunday' => array(10, 16))), [
									'mpwpb_shortcode_title' => 'Car Wash & Detailing',
									'mpwpb_shortcode_sub_title' => 'Hand washes and full detailing, booked in minutes',
									'mpwpb_category_text' => 'Package',
									'mpwpb_sub_category_text' => 'Vehicle size',
									'mpwpb_service_text' => 'Service',
									'mpwpb_category_service' => [
										['name' => 'Wash Packages', 'icon' => 'fas fa-soap', 'image' => ''],
										['name' => 'Detailing', 'icon' => 'fas fa-spray-can', 'image' => ''],
									],
									'mpwpb_sub_category_service' => [
										['name' => 'Sedan / Hatchback', 'icon' => 'fas fa-car', 'image' => '', 'cat_id' => 0],
										['name' => 'SUV / Crossover', 'icon' => 'fas fa-car-side', 'image' => '', 'cat_id' => 0],
									],
									'mpwpb_service' => [
										self::dummy_service('Express Exterior Wash', 15, '30min', 'Pre-rinse, foam wash, hand dry and wheel clean.', 'fas fa-soap', 0, 0),
										self::dummy_service('Full Hand Wash (In & Out)', 35, '45min', 'Exterior hand wash plus vacuum, windows and dashboard wipe-down.', 'fas fa-hand-sparkles', 0, 0),
										self::dummy_service('Wash & Wax', 60, '1h 15m', 'Full hand wash finished with a protective carnauba wax.', 'fas fa-star', 0, 0),
										self::dummy_service('Express Exterior Wash', 20, '30min', 'Pre-rinse, foam wash, hand dry and wheel clean.', 'fas fa-soap', 0, 1),
										self::dummy_service('Full Hand Wash (In & Out)', 45, '1h', 'Exterior hand wash plus vacuum, windows and dashboard wipe-down.', 'fas fa-hand-sparkles', 0, 1),
										self::dummy_service('Wash & Wax', 75, '1h 30m', 'Full hand wash finished with a protective carnauba wax.', 'fas fa-star', 0, 1),
										self::dummy_service('Interior Deep Clean', 120, '2h', 'Shampoo seats and carpets, steam vents and condition leather.', 'fas fa-couch', 1),
										self::dummy_service('Full Detail (Inside & Out)', 220, '4h', 'Clay bar, machine polish, sealant and a complete interior deep clean.', 'fas fa-spray-can', 1),
										self::dummy_service('Headlight Restoration', 50, '45min', 'Sand, polish and UV-seal cloudy headlights.', 'fas fa-lightbulb', 1),
									],
									'mpwpb_extra_service' => [
										self::dummy_extra('Tyre Shine', 8, 'Long-lasting dressing for a deep black tyre finish.', 'fas fa-circle'),
										self::dummy_extra('Odour Removal', 25, 'Ozone treatment to remove smoke, pet and food odours.', 'fas fa-wind'),
										self::dummy_extra('Pet Hair Removal', 30, 'Extra time and tools to lift embedded pet hair.', 'fas fa-paw'),
										self::dummy_extra('Engine Bay Clean', 35, 'Degrease and dress the engine bay.', 'fas fa-cogs'),
									],
									'mpwpb_features' => ['Hand Wash Only, No Brushes', 'Eco-Friendly, pH-Neutral Products', 'Open 7 Days', 'Covered Waiting Lounge with Wi-Fi'],
									'mpwpb_faq' => [
										['title' => 'Which wash package should I choose?', 'content' => '<p>The Express Exterior Wash is ideal for a regular clean. Choose the Full Hand Wash if the inside needs attention too, and Wash &amp; Wax for extra shine and protection.</p>'],
										['title' => 'How long will my car take?', 'content' => '<p>Each service shows its time when you book. Washes take 30 to 90 minutes and full details take around 4 hours.</p>'],
										['title' => 'Can I wait while you wash my car?', 'content' => '<p>Yes. Our covered lounge has seating, Wi-Fi and coffee. For full details we recommend dropping the car off.</p>'],
										['title' => 'What if it rains after my wash?', 'content' => '<p>Book again within 48 hours and we will give the outside a free rinse and dry.</p>'],
									],
									'mpwpb_service_overview_content' => '<p><strong>Sparkle Car Wash &amp; Detailing</strong> gives your car a careful hand wash or a full showroom detail, with prices set by package and vehicle size.</p><ol><li>Choose a wash package or detailing service.</li><li>Pick your vehicle size.</li><li>Choose a time to drop off.</li><li>Add extras, confirm, and drive away sparkling.</li></ol>',
									'mpwpb_service_details_content' => '<p>Every car is washed by hand using the two-bucket method and soft microfibre mitts, so there are no automatic brushes to scratch your paint.</p><p><strong>Why drivers choose us</strong></p><ul><li><strong>Eco-friendly products:</strong> biodegradable, pH-neutral shampoos and water recycling.</li><li><strong>Trained team:</strong> every washer completes our paint-safe training.</li><li><strong>Clear pricing:</strong> the price you book is the price you pay.</li></ul>',
									'mpwpb_service_rating_scale' => 'Out of 5',
								]),
								'reviews' => [
									['Hannah Price', 5, 'Car looks brand new', 'Booked the full wash package online and the car came out spotless, inside and out. Staff were quick and courteous.'],
									['Carlos Mendes', 5, 'Easy booking, great value', 'Was able to pick a slot that fit around my work schedule. Price was fair for the quality of the job.'],
									['Natalie Brooks', 4, 'Good wash, busy on weekends', 'Quality of the wash was great, just had to wait a bit longer than expected on a Saturday. Would book on a weekday next time.'],
									['Samuel Afolabi', 5, 'Friendly team, thorough job', "They paid attention to detail I didn't expect from a quick wash, got the wheels and interior mats really clean."],
								],
							],
							// Image: dummy-image-2.png (mechanic under the bonnet).
							2 => [
								'name' => 'AutoCare Service & Repair Centre',
								'post_data' => array_merge($common, self::dummy_schedule(60, 3, array(8, 17, 12, 13), 'sunday', array('saturday' => array(9, 13))), [
									'mpwpb_shortcode_title' => 'Car Service & Repair',
									'mpwpb_shortcode_sub_title' => 'Servicing, repairs and diagnostics with upfront prices',
									'mpwpb_category_text' => 'Service type',
									'mpwpb_sub_category_text' => 'Sub-Category',
									'mpwpb_service_text' => 'Service',
									'mpwpb_category_service' => [
										['name' => 'Maintenance', 'icon' => 'fas fa-oil-can', 'image' => ''],
										['name' => 'Repairs', 'icon' => 'fas fa-wrench', 'image' => ''],
										['name' => 'Diagnostics', 'icon' => 'fas fa-laptop-medical', 'image' => ''],
									],
									'mpwpb_sub_category_service' => [],
									'mpwpb_service' => [
										self::dummy_service('Oil & Filter Change', 60, '45min', 'Up to 5 litres of manufacturer-grade oil and a new oil filter.', 'fas fa-oil-can', 0),
										self::dummy_service('Interim Service', 120, '1h 30m', 'Oil and filter change plus a 30-point safety check.', 'fas fa-clipboard-check', 0),
										self::dummy_service('Full Service', 220, '3h', 'Oil, air, fuel and cabin filters, plus a 60-point inspection.', 'fas fa-tools', 0),
										self::dummy_service('Tyre Rotation & Balance', 50, '45min', 'Rotate all four tyres and balance the wheels.', 'fas fa-circle-notch', 0),
										self::dummy_service('Front Brake Pads Replacement', 150, '1h 30m', 'New front pads fitted, discs checked and the brakes road-tested.', 'fas fa-compact-disc', 1),
										self::dummy_service('Battery Replacement', 140, '30min', 'Battery test, new battery fitted and old battery recycled.', 'fas fa-car-battery', 1),
										self::dummy_service('Air Conditioning Re-Gas', 90, '1h', 'Leak check, refrigerant top-up and cabin temperature test.', 'fas fa-snowflake', 1),
										self::dummy_service('Engine Diagnostic Scan', 70, '45min', 'Read and explain fault codes, with a written repair quote.', 'fas fa-laptop-medical', 2),
									],
									'mpwpb_extra_service' => [
										self::dummy_extra('Courtesy Car (per day)', 35, 'Keep moving while we work on your car.', 'fas fa-car'),
										self::dummy_extra('Collection & Delivery', 20, 'We collect your car and bring it back within 10 miles.', 'fas fa-truck'),
										self::dummy_extra('Wiper Blades (pair)', 25, 'New front wiper blades fitted.', 'fas fa-water'),
										self::dummy_extra('Cabin Air Filter', 30, 'Fresh pollen filter for cleaner air inside the car.', 'fas fa-filter'),
									],
									'mpwpb_features' => ['Certified Technicians', 'Manufacturer-Approved Parts', '12-Month Parts & Labour Warranty', 'Free Written Quote Before Any Work'],
									'mpwpb_faq' => [
										['title' => 'Will servicing here affect my manufacturer warranty?', 'content' => '<p>No. We follow the manufacturer service schedule and use approved parts, and we stamp your service book.</p>'],
										['title' => 'What if you find another problem?', 'content' => '<p>We never do extra work without asking. We call you with a written quote and only go ahead once you approve it.</p>'],
										['title' => 'Can I wait for my car?', 'content' => '<p>Yes, for jobs under 90 minutes. For longer work, add a courtesy car or our collection and delivery service.</p>'],
										['title' => 'Is the work guaranteed?', 'content' => '<p>Yes. All repairs come with a 12-month parts and labour warranty.</p>'],
									],
									'mpwpb_service_overview_content' => '<p><strong>AutoCare Service &amp; Repair Centre</strong> keeps your car safe and road-legal with fixed-price servicing, repairs and diagnostics.</p><ol><li>Choose the service your car needs.</li><li>Pick a drop-off time.</li><li>Add a courtesy car or collection if needed.</li><li>Confirm, and we will text you when it is ready.</li></ol>',
									'mpwpb_service_details_content' => '<p>Our workshop has three service bays and certified technicians who work on all major makes and models.</p><p><strong>How it works</strong></p><ul><li><strong>Fixed prices:</strong> the price you book covers parts and labour for the listed work.</li><li><strong>No surprises:</strong> if we spot anything else, we send photos and a quote first.</li><li><strong>Warranty:</strong> every repair is covered for 12 months.</li></ul>',
									'mpwpb_service_rating_scale' => 'Out of 5',
								]),
								'reviews' => [
									['Linda Martinez', 5, 'Ready the same afternoon', 'Dropped the car off in the morning for a full service and it was ready by 3 PM. They explained everything they had checked.'],
									['Robert Kim', 5, 'Transparent pricing, no surprises', "Quoted a price upfront before the work and that's exactly what I paid. Appreciated not getting any last-minute add-on charges."],
									['Grace Osei', 4, 'Good service, slight delay', 'The repair itself was done well, though the car was ready about an hour later than promised. They kept me updated by text throughout.'],
									['Daniel Novak', 5, 'Convenient booking, great result', 'The online calendar made it simple to find a next-day slot for my brakes. They have been perfect since.'],
								],
							],
							// Image: dummy-image-3.png (online guitar lesson).
							3 => [
								'name' => 'Harmony Online Music Lessons',
								'post_data' => array_merge($common, self::dummy_schedule(30, 3, array(14, 21), 'sunday', array('saturday' => array(10, 16))), [
									'mpwpb_shortcode_title' => 'Online Music Lessons',
									'mpwpb_shortcode_sub_title' => 'Live one-to-one lessons with professional teachers',
									'mpwpb_category_text' => 'Instrument',
									'mpwpb_sub_category_text' => 'Sub-Category',
									'mpwpb_service_text' => 'Lesson',
									'mpwpb_category_service' => [
										['name' => 'Guitar', 'icon' => 'fas fa-guitar', 'image' => ''],
										['name' => 'Piano & Keyboard', 'icon' => 'fas fa-music', 'image' => ''],
										['name' => 'Singing', 'icon' => 'fas fa-microphone', 'image' => ''],
									],
									'mpwpb_sub_category_service' => [],
									'mpwpb_service' => [
										self::dummy_service('Trial Lesson', 15, '20min', 'Meet your teacher, set goals and try a short lesson.', 'fas fa-play-circle', 0),
										self::dummy_service('Beginner Guitar Lesson', 30, '30min', 'Chords, strumming and your first songs on acoustic or electric.', 'fas fa-guitar', 0),
										self::dummy_service('Intermediate Guitar Lesson', 45, '45min', 'Scales, lead technique and music theory for improving players.', 'fas fa-guitar', 0),
										self::dummy_service('Beginner Piano Lesson', 30, '30min', 'Reading music, hand position and simple pieces.', 'fas fa-music', 1),
										self::dummy_service('Piano Grade Exam Preparation', 50, '45min', 'Pieces, scales and aural practice for graded exams.', 'fas fa-award', 1),
										self::dummy_service('Singing Lesson', 35, '30min', 'Breathing, pitch and confidence for all voice types.', 'fas fa-microphone', 2),
									],
									'mpwpb_extra_service' => [
										self::dummy_extra('Lesson Recording', 5, 'A video recording of your lesson to practise with.', 'fas fa-video'),
										self::dummy_extra('Personal Practice Plan', 10, 'A written weekly practice plan from your teacher.', 'fas fa-clipboard-list'),
										self::dummy_extra('Sheet Music Pack', 12, 'Printable sheet music and backing tracks for your level.', 'fas fa-file-audio'),
									],
									'mpwpb_features' => ['Qualified, Experienced Teachers', 'Live One-to-One Video Lessons', 'Lessons for Ages 7 to Adult', 'Exam & Performance Preparation'],
									'mpwpb_faq' => [
										['title' => 'What do I need for an online lesson?', 'content' => '<p>Your instrument, a laptop or tablet with a camera, and a stable internet connection. We send the video link by email after you book.</p>'],
										['title' => 'Do you teach children?', 'content' => '<p>Yes. We teach students from age 7. For children under 12, a parent should be nearby for the first lesson.</p>'],
										['title' => 'Can I keep the same teacher?', 'content' => '<p>Yes. Choose your teacher when you book, and keep booking with them every week.</p>'],
										['title' => 'What if I miss a lesson?', 'content' => '<p>Reschedule for free up to 12 hours before your lesson. Lessons missed without notice cannot be refunded.</p>'],
									],
									'mpwpb_service_overview_content' => '<p><strong>Harmony Online Music Lessons</strong> brings a professional teacher into your home for live, one-to-one lessons on guitar, piano and voice.</p><ol><li>Choose your instrument and lesson length.</li><li>Pick a time after school or work.</li><li>Add a recording or practice plan.</li><li>Join your lesson from the link we email you.</li></ol>',
									'mpwpb_service_details_content' => '<p>All of our teachers are professional musicians with teaching qualifications and at least five years of experience.</p><p><strong>What to expect</strong></p><ul><li>A lesson plan built around the music you want to play</li><li>Progress notes after every lesson</li><li>Optional preparation for graded exams and performances</li></ul>',
									'mpwpb_service_rating_scale' => 'Out of 5',
								]),
								'reviews' => [
									['Emily Chambers', 5, 'My daughter loves her lessons', 'The instructor is patient and really knows how to keep a 9-year-old engaged. Scheduling online sessions around school hours has been effortless.'],
									['Jonas Weber', 5, 'Flexible and genuinely effective', "I've tried other online music platforms before, but the structured progress tracking here actually kept me practicing consistently. Highly recommend."],
									['Aisha Rahman', 4, 'Solid instructor, minor tech hiccups', 'Lesson content and teaching quality are great. Had one session with audio lag, but support resolved it quickly for the next class.'],
									['Tom Richardson', 5, 'Worth every session', 'Booking a slot takes two minutes and the instructor always comes prepared. My guitar skills have improved more in two months than a year of self-teaching.'],
								],
							],
							// Image: dummy-image-4.png (clinician with patient).
							4 => [
								'name' => 'CityCare Medical & Dental Clinic',
								'post_data' => array_merge($common, self::dummy_schedule(15, 2, array(8, 18, 13, 14), 'sunday', array('saturday' => array(9, 13))), [
									'mpwpb_shortcode_title' => 'Medical & Dental Clinic',
									'mpwpb_shortcode_sub_title' => 'GP appointments and dental care under one roof',
									'mpwpb_category_text' => 'Department',
									'mpwpb_sub_category_text' => 'Sub-Category',
									'mpwpb_service_text' => 'Appointment',
									'mpwpb_category_service' => [
										['name' => 'General Medicine', 'icon' => 'fas fa-stethoscope', 'image' => ''],
										['name' => 'Dental', 'icon' => 'fas fa-tooth', 'image' => ''],
									],
									'mpwpb_sub_category_service' => [],
									'mpwpb_service' => [
										self::dummy_service('GP Consultation', 60, '15min', 'A same-week appointment with one of our family doctors.', 'fas fa-stethoscope', 0),
										self::dummy_service('Annual Health Check', 150, '1h', 'Blood pressure, BMI, blood tests and a full review with a doctor.', 'fas fa-heartbeat', 0),
										self::dummy_service('Vaccination Appointment', 35, '15min', 'Flu, travel and routine vaccinations given by a nurse.', 'fas fa-syringe', 0),
										self::dummy_service('Dental Check-Up & Clean', 90, '45min', 'Examination, scale and polish, and oral health advice.', 'fas fa-tooth', 1),
										self::dummy_service('Tooth-Coloured Filling', 120, '45min', 'Natural-looking composite filling for one tooth.', 'fas fa-tooth', 1),
										self::dummy_service('Teeth Whitening', 250, '1h', 'In-chair whitening for a brighter smile in one visit.', 'fas fa-smile', 1),
									],
									'mpwpb_extra_service' => [
										self::dummy_extra('Blood Test Panel', 45, 'Full blood count, cholesterol and blood sugar.', 'fas fa-vial'),
										self::dummy_extra('Dental X-Rays', 40, 'Two bitewing X-rays to check between the teeth.', 'fas fa-x-ray'),
										self::dummy_extra('Fluoride Varnish', 20, 'Strengthens enamel and helps prevent decay.', 'fas fa-shield-alt'),
									],
									// Booking form shown on the page itself, and repeat check-ups.
									'mpwpb_booking_display_mode' => 'inline',
									'mpwpb_enable_recurring' => 'yes',
									'mpwpb_recurring_types' => ['weekly', 'monthly'],
									'mpwpb_max_recurring_count' => 12,
									'mpwpb_recurring_discount' => 0,
									'mpwpb_features' => ['Same-Week Appointments', 'Family Doctors & Dentists', 'On-Site Blood Tests', 'Wheelchair-Accessible Clinic'],
									'mpwpb_faq' => [
										['title' => 'Do I need to register before booking?', 'content' => '<p>No. Book online as a new patient and complete a short health form when you arrive.</p>'],
										['title' => 'What should I bring?', 'content' => '<p>Please bring photo ID, a list of any medicines you take, and your insurance details if you have them.</p>'],
										['title' => 'Can I book medical and dental on the same day?', 'content' => '<p>Yes. Add both services to one booking and we will schedule them back to back.</p>'],
										['title' => 'What if I need to cancel?', 'content' => '<p>Please cancel at least 24 hours ahead so we can offer the slot to another patient.</p>'],
									],
									'mpwpb_service_overview_content' => '<p><strong>CityCare Medical &amp; Dental Clinic</strong> offers family doctor and dental appointments in one place, with same-week availability.</p><ol><li>Choose a medical or dental appointment.</li><li>Pick a date and time.</li><li>Add tests or X-rays if you need them.</li><li>Confirm, and we will send a reminder the day before.</li></ol>',
									'mpwpb_service_details_content' => '<p>Our team of doctors, nurses and dentists looks after patients of all ages, from routine check-ups to ongoing care.</p><p><strong>Before your visit</strong></p><ul><li>Arrive 10 minutes early to complete your health form.</li><li>For blood tests, please fast for 8 hours beforehand.</li><li>Let us know if you need step-free access or an interpreter.</li></ul>',
									'mpwpb_service_rating_scale' => 'Out of 5',
								]),
								'reviews' => [
									['Sarah Jenkins', 5, 'Fast, caring, and thorough', 'Booked a same-week appointment and the whole visit was smooth. The doctor took time to explain everything and the dental checkup was included without any extra hassle.'],
									['Michael Torres', 5, 'Great staff, no long wait', 'I was in and out faster than I expected. Staff were friendly and the online booking made it easy to pick a time that worked for me.'],
									['Priya Nair', 4, 'Good experience overall', 'Appointment ran a little behind schedule but the care itself was excellent. The doctor was knowledgeable and answered all my questions patiently.'],
									['David Okafor', 5, 'Dental and general checkup in one visit', "Didn't expect to get my dental checkup done the same day as my regular visit. Saved me a second trip. Will be booking here again."],
								],
							],
							// Image: dummy-image-5.png (SUV).
							5 => [
								'name' => 'DriveAway Car Rental',
								'post_data' => array_merge($common, self::dummy_schedule(60, 4, array(8, 18), '', array('sunday' => array(9, 13))), [
									'mpwpb_shortcode_title' => 'Car Rental',
									'mpwpb_shortcode_sub_title' => 'Clean, fully insured cars for a day trip or a weekend',
									'mpwpb_category_text' => 'Car class',
									'mpwpb_sub_category_text' => 'Sub-Category',
									'mpwpb_service_text' => 'Rental',
									'mpwpb_category_service' => [
										['name' => 'Economy', 'icon' => 'fas fa-car', 'image' => ''],
										['name' => 'Saloon', 'icon' => 'fas fa-car-side', 'image' => ''],
										['name' => 'SUV', 'icon' => 'fas fa-shuttle-van', 'image' => ''],
									],
									'mpwpb_sub_category_service' => [],
									'mpwpb_service' => [
										self::dummy_service('Economy, Half Day (6 hours)', 45, '6h', 'Compact 5-door car, ideal for city errands. 150 km included.', 'fas fa-car', 0),
										self::dummy_service('Economy, Full Day (24 hours)', 70, '24h', 'Compact 5-door car with 300 km included.', 'fas fa-car', 0),
										self::dummy_service('Saloon, Full Day (24 hours)', 95, '24h', 'Comfortable mid-size saloon with 300 km included.', 'fas fa-car-side', 1),
										self::dummy_service('SUV, Full Day (24 hours)', 130, '24h', '7-seat SUV with plenty of luggage space and 300 km included.', 'fas fa-shuttle-van', 2),
										self::dummy_service('SUV Day Tour with Driver', 220, '10h', 'A professional driver and SUV for up to 10 hours of sightseeing.', 'fas fa-route', 2),
									],
									'mpwpb_extra_service' => [
										self::dummy_extra('Child Seat', 8, 'Rear-facing or forward-facing seat, fitted for you.', 'fas fa-baby', 2),
										self::dummy_extra('Additional Driver', 15, 'Add a second named driver to the rental.', 'fas fa-user-plus'),
										self::dummy_extra('Zero-Excess Insurance', 25, 'Reduce your damage excess to zero.', 'fas fa-shield-alt'),
										self::dummy_extra('Sat Nav', 6, 'Portable GPS with up-to-date maps.', 'fas fa-map-marked-alt'),
									],
									'mpwpb_features' => ['Fully Insured, Late-Model Cars', 'Free Cancellation up to 48 Hours', 'Full-to-Full Fuel Policy', '24/7 Roadside Assistance'],
									'mpwpb_faq' => [
										['title' => 'What do I need to rent a car?', 'content' => '<p>A full driving licence held for at least one year, a credit or debit card in the main driver\'s name, and photo ID. Drivers must be 21 or older.</p>'],
										['title' => 'Is there a deposit?', 'content' => '<p>Yes. We hold a refundable deposit on your card at pick-up and release it when the car is returned undamaged.</p>'],
										['title' => 'What is the fuel policy?', 'content' => '<p>Full to full: you pick the car up with a full tank and return it full.</p>'],
										['title' => 'What if I drive more than the included distance?', 'content' => '<p>Extra distance is charged per kilometre, shown on your agreement at pick-up.</p>'],
									],
									'mpwpb_service_overview_content' => '<p><strong>DriveAway Car Rental</strong> has clean, fully insured economy cars, saloons and SUVs ready for a day out, a weekend away or a guided tour.</p><ol><li>Choose a car class and rental length.</li><li>Pick your pick-up date and time.</li><li>Add a child seat, extra driver or insurance.</li><li>Confirm and collect your keys.</li></ol>',
									'mpwpb_service_details_content' => '<p>Every car is under three years old, cleaned and safety-checked before each rental, and comes with roadside assistance.</p><p><strong>Included with every rental</strong></p><ul><li>Third-party insurance and breakdown cover</li><li>Included kilometres, shown on each rental</li><li>Free cancellation up to 48 hours before pick-up</li></ul>',
									'mpwpb_service_rating_scale' => 'Out of 5',
								]),
								'reviews' => [
									['Isabella Rossi', 5, 'Car was spotless and full tank', 'Picked up the car right on schedule, it was clean and ready to go. Made our day trip so much easier than public transport.'],
									['Benjamin Carter', 5, 'Smooth pickup and return', 'The booking process was clear about pricing and deposit upfront. No surprises at pickup, and return was quick too.'],
									['Fatima Zahra', 4, 'Great car, paperwork took a while', 'The car itself was excellent and well maintained. Just took a bit longer than expected to finish the rental paperwork at pickup.'],
									['Noah Campbell', 5, 'Perfect for our weekend trip', 'Comfortable ride, great mileage, and the online booking let us lock in the exact date we needed weeks in advance.'],
								],
							],
							// Image: dummy-image-6.jpg (chiropractic adjustment; Unsplash, free licence).
							6 => [
								'name' => 'Align Chiropractic Clinic',
								'post_data' => array_merge($common, self::dummy_schedule(30, 2, array(9, 18, 12, 13), 'sunday', array('saturday' => array(9, 13))), [
									'mpwpb_shortcode_title' => 'Chiropractic Clinic',
									'mpwpb_shortcode_sub_title' => 'Spinal health and pain relief, booked online',
									'mpwpb_category_text' => 'Category',
									'mpwpb_sub_category_text' => 'Sub-Category',
									'mpwpb_service_text' => 'Treatment',
									'mpwpb_category_service' => [
										['name' => 'Consultations', 'icon' => 'fas fa-comments', 'image' => ''],
										['name' => 'Adjustments & Therapy', 'icon' => 'fas fa-user-md', 'image' => ''],
									],
									'mpwpb_sub_category_service' => [],
									'mpwpb_service' => [
										self::dummy_service('New Patient Consultation', 95, '45min', 'Full history, posture assessment and a personal treatment plan.', 'fas fa-comments', 0),
										self::dummy_service('Follow-Up Adjustment', 55, '20min', 'Quick follow-up spinal adjustment for existing patients.', 'fas fa-user-md', 0),
										self::dummy_service('Spinal Adjustment', 65, '30min', 'Hands-on spinal adjustment to relieve pain and improve alignment.', 'fas fa-user-md', 1),
										self::dummy_service('Therapeutic Massage Add-On Session', 60, '30min', 'Muscle-relaxing massage paired with your adjustment.', 'fas fa-hand-sparkles', 1),
										self::dummy_service('Posture & Mobility Assessment', 80, '45min', 'Detailed posture and mobility screening with recommendations.', 'fas fa-walking', 1),
									],
									'mpwpb_extra_service' => [
										self::dummy_extra('Heat Therapy', 10, 'Heat pack applied before or after your adjustment.', 'fas fa-fire'),
										self::dummy_extra('Cold Laser Therapy', 30, 'Low-level laser therapy to reduce inflammation.', 'fas fa-first-aid'),
										self::dummy_extra('Take-Home Posture Brace', 35, 'Supportive brace to reinforce posture between visits.', 'fas fa-user-md'),
									],
									'mpwpb_features' => ['Licensed Chiropractors', 'Personalized Treatment Plans', 'Same-Week Appointments', 'Family-Friendly Clinic'],
									'mpwpb_faq' => [
										['title' => 'Is chiropractic treatment safe?', 'content' => '<p>Yes, all adjustments are performed by licensed chiropractors using safe, proven techniques.</p>'],
										['title' => 'How many visits will I need?', 'content' => '<p>Your chiropractor will recommend a plan based on your condition during your first visit.</p>'],
										['title' => 'Do you treat children?', 'content' => '<p>Yes, we welcome patients of all ages with age-appropriate techniques.</p>'],
										['title' => 'Is there a discount for ongoing care?', 'content' => '<p>Yes. Book a weekly recurring appointment and get 10% off every visit.</p>'],
									],
									'mpwpb_service_overview_content' => '<p><strong>Align Chiropractic Clinic</strong> helps patients of all ages find lasting relief from pain and stiffness.</p><ol><li>Book a consultation or adjustment.</li><li>Pick a date and time.</li><li>Add any therapy extras.</li><li>Confirm and start feeling better.</li></ol>',
									'mpwpb_service_details_content' => '<p>Our chiropractors use evidence-based techniques tailored to each patient, with ongoing care plans for lasting results.</p><p><strong>Your first visit</strong></p><ul><li>A full health and injury history</li><li>Posture, movement and spinal assessment</li><li>A clear treatment plan and your first adjustment if suitable</li></ul><p><strong>What to wear</strong></p><p>Comfortable, loose clothing you can move in easily.</p>',
									// Weekly care plans: recurring bookings with a 10% discount.
									'mpwpb_enable_recurring' => 'yes',
									'mpwpb_recurring_types' => ['weekly'],
									'mpwpb_max_recurring_count' => 20,
									'mpwpb_recurring_discount' => 10,
									'mpwpb_service_rating_scale' => 'Out of 5',
								]),
								'reviews' => [
									['Oscar L.', 5, 'No more back pain', 'After years of back pain, my chiropractor finally got me real relief.'],
									['Bianca M.', 5, 'Great with kids', 'They were wonderful adjusting my daughter after her sports injury.'],
									['Leo N.', 4, 'Solid results', 'Noticeable improvement in my posture after a month of visits.'],
									['Ingrid P.', 5, 'Friendly and professional', 'The whole team makes every visit easy and comfortable.'],
								],
							],
						],
					],
				];
			}

			/**
			 * Approved sample reviews for an imported service, plus the
			 * rating meta the Reviews admin would compute from them, so the
			 * star rating, "(N ratings)" text and Reviews tab all agree.
			 */
			private static function import_dummy_reviews(int $post_id, array $reviews) {
				global $wpdb;
				$table = $wpdb->prefix . 'mpwpb_reviews';
				if (empty($reviews) || $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) !== $table) {
					return;
				}
				$total = 0;
				$count = count($reviews);
				foreach ($reviews as $i => $review) {
					list($name, $rating, $title, $content) = $review;
					$wpdb->insert($table, array(
						'service_id' => $post_id,
						'user_id' => 0,
						'user_name' => $name,
						'rating' => (int) $rating,
						'title' => $title,
						'content' => $content,
						'status' => 'approved',
						// Spread over the last few weeks, oldest first.
						'date_created' => gmdate('Y-m-d H:i:s', time() - ($count - $i) * 9 * DAY_IN_SECONDS),
					));
					$total += (int) $rating;
				}
				update_post_meta($post_id, 'mpwpb_service_review_ratings', number_format($total / $count, 1));
				update_post_meta($post_id, 'mpwpb_service_rating_scale', 'Out of 5');
				/* translators: %d: number of ratings */
				update_post_meta($post_id, 'mpwpb_service_rating_text', sprintf(__('(%d ratings)', 'service-booking-manager'), $count));
			}
			public static function insert_media($file_path) {
				$attachment = self::does_attachment_exist(basename($file_path));
				if (empty($attachment)) {
					require_once(ABSPATH . 'wp-admin/includes/file.php');
					require_once(ABSPATH . 'wp-admin/includes/media.php');
					require_once(ABSPATH . 'wp-admin/includes/image.php');

					$filename = basename($file_path);
					$file_content = file_get_contents($file_path);

					if ($file_content === false) {
						return 'Failed to read file content.';
					}

					$upload = wp_upload_bits($filename, null, $file_content);

					if ($upload['error']) {
						return 'Upload error: ' . $upload['error'];
					}

					$attachment = array(
						'post_mime_type' => mime_content_type($upload['file']),
						'post_title'     => sanitize_file_name(pathinfo($filename, PATHINFO_FILENAME)),
						'post_content'   => '',
						'post_status'    => 'inherit',
					);

					$attachment_id = wp_insert_attachment($attachment, $upload['file']);
					if (is_wp_error($attachment_id)) {
						return 'Attachment insert failed: ' . $attachment_id->get_error_message();
					}

					$attachment_data = wp_generate_attachment_metadata($attachment_id, $upload['file']);
					wp_update_attachment_metadata($attachment_id, $attachment_data);

					return [
						'id'  => $attachment_id,
						'url' => wp_get_attachment_url($attachment_id),
					];
				}

				return $attachment;
			}
			public static function does_attachment_exist($filename) {
				global $wpdb;

				$filename = sanitize_file_name($filename);
				$like_filename = '%' . $wpdb->esc_like($filename) . '%';

				$args = [
					'post_type'      => 'attachment',
					'posts_per_page' => 1,
					'fields'         => 'ids',
					'meta_query'     => [
						'relation' => 'OR',
						[
							'key'     => '_wp_attached_file',
							'value'   => $like_filename,
							'compare' => 'LIKE',
						],
					],
					's' => $like_filename,
				];

				$cache_key = 'attachment_id_' . md5(json_encode($args));
				$attachment_id = wp_cache_get($cache_key, 'custom_cache_group');

				if ($attachment_id === false) {
					$attachments 	= get_posts($args);
					$attachment_id 	= !empty($attachments) ? $attachments[0] : null;
					wp_cache_set($cache_key, $attachment_id, 'custom_cache_group', HOUR_IN_SECONDS);
				}

				if ($attachment_id) {
					return [
						'id'  => (int) $attachment_id,
						'url' => wp_get_attachment_url($attachment_id),
					];
				}

				return false;
			}
		}
	}