<?php
	/*
	 * Modern service add/edit editor (parallel to the classic tabbed metabox).
	 *
	 * Design: a 4-step wizard (General, Availability, Pricing, Advanced). This
	 * class ONLY renders a modern shell and reuses the EXISTING classic section
	 * render methods for the body, so every field name, AJAX endpoint and the
	 * shared save handler (MPWPB_Settings::save_settings) keep working
	 * unchanged. Switching is per-user (user meta); the classic path is never
	 * modified.
	 */
	if (!defined('ABSPATH')) {
		die;
	} // Cannot access pages directly.
	if (!class_exists('MPWPB_Settings_Modern')) {
		class MPWPB_Settings_Modern {
			/** User-meta key holding each admin's preferred editor: 'classic' | 'modern' (default). */
			const USER_META = 'mpwpb_service_edit_ui';

			/** Cache of reflection-built section renderers (no constructor side effects). */
			private $section_cache = array();

			public function __construct() {
				add_action('add_meta_boxes', array($this, 'register_meta_boxes'), 99);
				add_action('admin_enqueue_scripts', array($this, 'enqueue'), 90);
				add_filter('admin_body_class', array($this, 'body_class'));
				add_action('wp_ajax_mpwpb_set_service_edit_ui', array($this, 'ajax_set_ui'));
				add_action('wp_ajax_mpwpb_save_theme_color', array($this, 'ajax_save_theme_color'));
				add_action('wp_ajax_mpwpb_save_service_editor', array($this, 'ajax_save_service'));
				add_action('save_post', array($this, 'save_feature_image'), 20);
			}

			/* ------------------------------------------------------------------ *
			 *  Preference helpers
			 * ------------------------------------------------------------------ */

			/**
			 * Current user's editor preference. Defaults to modern until the
			 * user explicitly switches to classic — once they do, that choice
			 * (like an explicit modern choice) sticks across reloads.
			 */
			public static function current_ui() {
				$ui = get_user_meta(get_current_user_id(), self::USER_META, true);
				return $ui === 'classic' ? 'classic' : 'modern';
			}

			private function is_modern() {
				return self::current_ui() === 'modern';
			}

			/** True only on the add/edit screen of the service CPT. */
			private function is_service_edit_screen() {
				if (!function_exists('get_current_screen')) {
					return false;
				}
				$screen = get_current_screen();
				return $screen && $screen->base === 'post' && $screen->post_type === MPWPB_Function::get_cpt();
			}

			/* ------------------------------------------------------------------ *
			 *  Metaboxes
			 * ------------------------------------------------------------------ */

			public function register_meta_boxes() {
				$cpt = MPWPB_Function::get_cpt();

				// Editor-style switcher — always available so users can flip either direction.
				add_meta_box(
					'mpwpb_sme_ui_switcher',
					esc_html__('Editor Style', 'service-booking-manager'),
					array($this, 'render_switcher'),
					$cpt,
					'side',
					'high'
				);

				if ($this->is_modern()) {
					// Replace the classic panel with the modern shell (classic file untouched).
					remove_meta_box('mp_meta_box_panel', $cpt, 'normal');
					add_meta_box(
						'mpwpb_sme_meta_box_panel',
						esc_html__('Service Settings', 'service-booking-manager'),
						array($this, 'render_modern'),
						$cpt,
						'normal',
						'high'
					);
				}
			}

			/* ------------------------------------------------------------------ *
			 *  Step registry
			 * ------------------------------------------------------------------ */

			/**
			 * The 4 wizard steps. Each section reuses a classic renderer:
			 * [ class, method, title, subtitle ].
			 */
			private function get_steps() {
				$steps = array(
					array(
						'id' => 'general',
						'label' => __('General', 'service-booking-manager'),
						'sections' => array(
							array('MPWPB_General_Settings', 'general_settings', __('Customer reviews', 'service-booking-manager'), __('The rating badge shown near the top of your service page.', 'service-booking-manager')),
							array('MPWPB_Service_Features_Modern', 'render', __('Features', 'service-booking-manager'), __('Short selling points shown as a checklist on the service page.', 'service-booking-manager')),
							array('MPWPB_Service_Details', 'service_details', __('Full description', 'service-booking-manager'), __('The detailed write-up customers can read further down the service page.', 'service-booking-manager')),
						),
					),
					array(
						'id' => 'pricing',
						'label' => __('Pricing', 'service-booking-manager'),
						'sections' => array(
							array('MPWPB_Categories_Services_Modern', 'render', __('Services & Pricing', 'service-booking-manager'), ''),
							array('MPWPB_Extra_Service_Modern', 'render', __('Extra Service', 'service-booking-manager'), __('Optional paid add-ons customers can choose during booking.', 'service-booking-manager')),
						),
					),
					array(
						'id' => 'availability',
						'label' => __('Availability', 'service-booking-manager'),
						'sections' => array(
							array('MPWPB_Date_Time_Modern', 'render', __('Date & Time', 'service-booking-manager'), __('Available dates, weekly schedule, off days and time slots.', 'service-booking-manager')),
						),
					),
					array(
						'id' => 'advanced',
						'label' => __('Advanced', 'service-booking-manager'),
						'sections' => array(
							array('MPWPB_Faq_Settings', 'faq_settings', __('FAQ', 'service-booking-manager'), __('Answer common questions on the service page.', 'service-booking-manager')),
							array('Tax_Settings', 'tax_settings', __('Tax', 'service-booking-manager'), __('Add tax to this service\'s price at checkout.', 'service-booking-manager')),
							array('MPWPB_Happy_Hours_Settings', 'happy_hours_settings', __('Happy hours pricing', 'service-booking-manager'), __('Discount bookings that start during your quieter hours.', 'service-booking-manager')),
						),
					),
				);
				// Staff Member and Recurring Booking are both free-tier features (see
				// MPWPB_Settings.php / MPWPB_Extended_Settings.php's classic tab navs,
				// which show them unconditionally) -- always included here too.
				$steps[3]['sections'][] = array('Staff_Member', 'staff_member_settings', __('Staff', 'service-booking-manager'), __('Let customers pick who they book with.', 'service-booking-manager'));
				$steps[3]['sections'][] = array('MPWPB_Recurring_Booking_Settings', 'recurring_booking_settings', __('Recurring booking', 'service-booking-manager'), __('Let customers book this service as a repeating series.', 'service-booking-manager'));
				return $steps;
			}

			/**
			 * Build (once) a renderer instance WITHOUT invoking the constructor, so the
			 * classic add_action hooks are not registered twice. The original singletons
			 * (created in their own files) keep all their AJAX / sub-action hooks active.
			 */
			private function section_instance($class) {
				if (!array_key_exists($class, $this->section_cache)) {
					$this->section_cache[$class] = null;
					if (class_exists($class)) {
						try {
							$ref = new ReflectionClass($class);
							$this->section_cache[$class] = $ref->newInstanceWithoutConstructor();
						} catch (\ReflectionException $e) {
							$this->section_cache[$class] = null;
						}
					}
				}
				return $this->section_cache[$class];
			}

			/* ------------------------------------------------------------------ *
			 *  Panel skeleton (server-rendered so it shows before JS runs)
			 * ------------------------------------------------------------------ */

			private function render_panel_skeleton(string $id): void {
				$row = '<div class="mpwpb-sme__skel-row"><div class="mpwpb-sme__skel mpwpb-sme__skel-lbl"></div><div class="mpwpb-sme__skel mpwpb-sme__skel-inp"></div></div>';
				$rows = function (int $n) use ($row): string {
					return str_repeat($row, $n);
				};
				$card = function (int $n, string $w = '35%') use ($rows): string {
					return '<div class="mpwpb-sme__skel-card"><div class="mpwpb-sme__skel mpwpb-sme__skel-card-hd" style="width:' . $w . '"></div>' . $rows($n) . '</div>';
				};
				$shapes = array(
					'general' => $card(4, '30%') . $card(3, '25%'),
					'availability' => $card(3, '25%') . $card(4, '35%'),
					'pricing' => $card(3, '30%') . $card(2, '25%'),
					'advanced' => $card(3, '35%') . $card(2, '30%'),
				);
				$inner = isset($shapes[$id]) ? $shapes[$id] : $card(3, '30%');
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fully static markup, no user data
				echo '<div class="mpwpb-sme__skel-ov">' . $inner . '</div>';
			}

			private function render_rail_card_skeleton(string $type): void {
				$row = function (string $lw = '28%'): string {
					return '<div style="display:flex;align-items:center;gap:12px;padding:6px 0">'
						. '<div class="mpwpb-sme__skel mpwpb-sme__skel-lbl" style="width:' . $lw . '"></div>'
						. '<div class="mpwpb-sme__skel mpwpb-sme__skel-inp"></div>'
						. '</div>';
				};
				if ($type === 'featured') {
					$inner = '<div class="mpwpb-sme__skel mpwpb-sme__skel-block" style="height:155px;margin-bottom:14px"></div>'
						. '<div style="display:flex;gap:10px">'
						. '<div class="mpwpb-sme__skel" style="height:24px;flex:1;border-radius:6px"></div>'
						. '<div class="mpwpb-sme__skel" style="height:24px;flex:1;border-radius:6px"></div>'
						. '</div>';
				} else {
					$inner = '<div class="mpwpb-sme__skel" style="height:13px;width:40%;margin-bottom:14px;border-radius:5px"></div>'
						. $row('30%') . $row('30%') . $row('35%');
				}
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fully static markup, no user data
				echo '<div class="mpwpb-sme__skel-ov">' . $inner . '</div>';
			}

			/* ------------------------------------------------------------------ *
			 *  Modern shell
			 * ------------------------------------------------------------------ */

			public function render_modern($post) {
				$post_id = (int) $post->ID;
				$steps = $this->get_steps();
				$total = count($steps);
				$service_title = get_the_title($post_id);
				$service_title = $service_title !== '' ? $service_title : MPWPB_Function::get_name();
				$list_url = admin_url('edit.php?post_type=' . MPWPB_Function::get_cpt() . '&page=mpwpb_service_list');

				// Shared plumbing — MUST match the classic save handler.
				wp_nonce_field('mpwpb_nonce', 'mpwpb_nonce');
				?>
				<input type="hidden" name="mpwpb_sme_post_id" value="<?php echo esc_attr($post_id); ?>"/>
				<?php
				/* Service_Settings' "Enable Multiple Category Check" / "Enable
				 * Multiple Service Select" toggles have no card in the modern
				 * wizard — always submitted as "on" via hidden fields instead
				 * (Classic mode's own card with real toggles is untouched and
				 * still fully functional there). */
				?>
				<input type="hidden" name="mpwpb_service_multiple_category_check" value="on"/>
				<input type="hidden" name="mpwpb_multiple_service_select" value="on"/>
				<?php // The mpwpb_style class keeps classic JS (tabs, collapse, sortable, datepicker) working for the reused sections. ?>
				<div class="mpwpb-sme mpwpb_style" id="mpwpb-sme" data-total="<?php echo esc_attr($total); ?>" data-step="general">

					<header class="mpwpb-sme__topbar">
						<a class="mpwpb-sme__back" href="<?php echo esc_url($list_url); ?>" title="<?php echo esc_attr(sprintf(__('Back to %s', 'service-booking-manager'), MPWPB_Function::get_name())); ?>">
							<span class="dashicons dashicons-arrow-left-alt2" aria-hidden="true"></span>
							<span class="screen-reader-text"><?php echo esc_html(sprintf(__('Back to %s', 'service-booking-manager'), MPWPB_Function::get_name())); ?></span>
						</a>
						<input type="text" class="mpwpb-sme__ttl mpwpb-sme__ttl-input" id="mpwpb-sme-title" value="<?php echo esc_attr($service_title); ?>" placeholder="<?php esc_attr_e('Service name', 'service-booking-manager'); ?>" aria-label="<?php esc_attr_e('Service name', 'service-booking-manager'); ?>"/>
						<nav class="mpwpb-sme__stepper" aria-label="<?php esc_attr_e('Editor steps', 'service-booking-manager'); ?>">
							<?php foreach ($steps as $i => $step) : ?>
								<?php if ($i > 0) : ?>
									<div class="mpwpb-sme__conn" data-sme-conn="<?php echo esc_attr($i); ?>"></div>
								<?php endif; ?>
								<div class="mpwpb-sme__step<?php echo $i === 0 ? ' active' : ''; ?>" data-sme-go="<?php echo esc_attr($step['id']); ?>" data-sme-index="<?php echo esc_attr($i); ?>">
									<div class="mpwpb-sme__num"><?php echo esc_html($i + 1); ?></div>
									<div class="mpwpb-sme__lab"><?php echo esc_html($step['label']); ?></div>
								</div>
							<?php endforeach; ?>
						</nav>
						<div class="mpwpb-sme__acts">
							<?php
							// Same "Published"/"Update" vs "Draft"/"Publish" split WordPress's own
							// Publish box uses.
							$sme_post_status = get_post_status($post_id);
							$sme_is_published = in_array($sme_post_status, array('publish', 'private', 'future'), true);
							$sme_primary_label = $sme_is_published ? __('Update', 'service-booking-manager') : __('Publish', 'service-booking-manager');
							$sme_secondary_label = $sme_is_published ? __('Switch to Draft', 'service-booking-manager') : __('Save Draft', 'service-booking-manager');
							$sme_status_label = $sme_is_published ? __('Published', 'service-booking-manager') : __('Draft', 'service-booking-manager');
							?>
							<span class="mpwpb-sme__local-save is-ready" data-sme-local-save role="status" aria-live="polite">
								<span class="mpwpb-sme__local-save-dot" aria-hidden="true"></span>
								<span data-sme-local-save-text><?php esc_html_e('Local autosave ready', 'service-booking-manager'); ?></span>
							</span>
							<span class="mpwpb-sme__status-pill<?php echo $sme_is_published ? ' is-published' : ' is-draft'; ?>"><?php echo esc_html($sme_status_label); ?></span>
							<a href="<?php echo esc_url(get_preview_post_link($post_id)); ?>" target="wp-preview-<?php echo esc_attr($post_id); ?>" class="mpwpb-sme__btn" data-sme-preview>
								<span class="dashicons dashicons-visibility" aria-hidden="true"></span>
								<?php esc_html_e('Preview', 'service-booking-manager'); ?>
							</a>
							<div class="mpwpb-sme__split" data-sme-split>
								<button type="button" class="mpwpb-sme__btn mpwpb-sme__btn--primary" data-sme-save><?php echo esc_html($sme_primary_label); ?></button>
								<button type="button" class="mpwpb-sme__btn mpwpb-sme__btn--primary mpwpb-sme__split-caret" data-sme-split-toggle aria-haspopup="true" aria-expanded="false">
									<span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span>
									<span class="screen-reader-text"><?php esc_html_e('More save options', 'service-booking-manager'); ?></span>
								</button>
								<div class="mpwpb-sme__split-menu" data-sme-split-menu hidden>
									<button type="button" class="mpwpb-sme__split-menu-item" data-sme-save-as="draft"><?php echo esc_html($sme_secondary_label); ?></button>
									<button type="button" class="mpwpb-sme__split-menu-item" data-sme-ui="classic"><?php esc_html_e('Classic editor', 'service-booking-manager'); ?></button>
								</div>
							</div>
						</div>
					</header>

					<?php if (!MPWPB_Global_Function::has_functional_payment_method()) : ?>
						<div class="mpwpb-sme__payment-notice">
							<?php esc_html_e('No payment method is currently configured.', 'service-booking-manager'); ?>
							<a href="#" class="mpwpb-sme__payment-notice-link" data-sme-payment-modal-open>
								<?php esc_html_e('Please configure a payment method to accept bookings.', 'service-booking-manager'); ?>
							</a>
						</div>
					<?php endif; ?>

					<div class="mpwpb-sme__payment-modal" id="mpwpb-sme-payment-modal" data-sme-payment-modal style="display:none;">
						<div class="mpwpb-sme__payment-modal-box">
							<div class="mpwpb-sme__payment-modal-head">
								<h2><?php esc_html_e('Payment Method', 'service-booking-manager'); ?></h2>
								<button type="button" class="mpwpb-sme__payment-modal-close" data-sme-payment-modal-close aria-label="<?php esc_attr_e('Close', 'service-booking-manager'); ?>">&times;</button>
							</div>
							<div class="mpwpb-sme__payment-modal-body" id="mpwpb-sme-payment-modal-body">
								<?php
								/**
								 * Reuses the real Payment Method settings panel verbatim (same
								 * markup/JS/AJAX as Settings > Payment Method) via Reflection,
								 * matching the pattern already used elsewhere in this file for
								 * classic settings render methods — its constructor only wires
								 * admin hooks, no state the render method depends on.
								 */
								if (class_exists('MPWPB_Native_Checkout_Settings')) {
									(new ReflectionClass('MPWPB_Native_Checkout_Settings'))
										->newInstanceWithoutConstructor()
										->render_payment_method_panel();
								}
								?>
							</div>
						</div>
					</div>

					<div class="mpwpb-sme__wrap">
						<div class="mpwpb-sme__body">
							<div class="mpwpb-sme__main">
								<?php foreach ($steps as $i => $step) : ?>
									<section class="mpwpb-sme__panel mpwpb-sme__panel--loading<?php echo $i === 0 ? ' active' : ''; ?>" data-sme-panel="<?php echo esc_attr($step['id']); ?>">
										<?php $this->render_panel_skeleton($step['id']); ?>
										<?php
										if ($step['id'] === 'general') {
											$this->render_post_fields_subsection($post_id);
										}
										foreach ($step['sections'] as $section) {
											$this->render_section_card($section, $post_id);
										}
										?>
									</section>
								<?php endforeach; ?>
							</div>
							<?php $this->render_preview_rail($post_id); ?>
						</div>
					</div>

					<?php
					// One-line summary of what each step holds, shown under the step name in the bottom bar.
					$nav_hints = array(
						'general' => __('Name, photos and description', 'service-booking-manager'),
						'pricing' => __('Services, prices and add-ons', 'service-booking-manager'),
						'availability' => __('Hours, booking rules and days off', 'service-booking-manager'),
						'advanced' => __('FAQ, tax, staff and extras', 'service-booking-manager'),
					);
					$nav_steps = array();
					foreach ($steps as $step) {
						$nav_steps[] = array('label' => $step['label'], 'hint' => isset($nav_hints[$step['id']]) ? $nav_hints[$step['id']] : '');
					}
					?>
					<div class="mpwpb-sme__navbar" data-sme-nav-steps="<?php echo esc_attr(wp_json_encode($nav_steps)); ?>">
						<div class="mpwpb-sme__nav-progress" aria-hidden="true"><span data-sme-nav-progress style="width:<?php echo esc_attr(round(100 / max(1, $total), 2)); ?>%"></span></div>
						<div class="mpwpb-sme__navinner">
							<span class="mpwpb-sme__nav-num" data-sme-nav-num aria-hidden="true">1</span>
							<div class="mpwpb-sme__nav-text" aria-live="polite">
								<strong class="mpwpb-sme__nav-name" data-sme-nav-name><?php echo esc_html($nav_steps ? $nav_steps[0]['label'] : ''); ?></strong>
								<span class="mpwpb-sme__stepof" data-sme-stepof><?php echo esc_html(sprintf(__('Step %1$d of %2$d', 'service-booking-manager'), 1, $total) . ($nav_steps && $nav_steps[0]['hint'] !== '' ? ' · ' . $nav_steps[0]['hint'] : '')); ?></span>
							</div>
							<span class="mpwpb-sme__nav-spacer"></span>
							<span class="mpwpb-sme__local-save mpwpb-sme__nav-save is-ready" data-sme-local-save>
								<span class="mpwpb-sme__local-save-dot" aria-hidden="true"></span>
								<span data-sme-local-save-text><?php esc_html_e('Local autosave ready', 'service-booking-manager'); ?></span>
							</span>
							<span class="mpwpb-sme__nav-sep" aria-hidden="true"></span>
							<button type="button" class="mpwpb-sme__btn mpwpb-sme__nav-back" data-sme-prev disabled hidden><?php esc_html_e('Back', 'service-booking-manager'); ?></button>
							<button type="button" class="mpwpb-sme__btn mpwpb-sme__btn--primary mpwpb-sme__nav-next" data-sme-next>
								<span data-sme-next-label><?php echo esc_html(count($nav_steps) > 1 ? sprintf(/* translators: %s: next step name */ __('Next: %s', 'service-booking-manager'), $nav_steps[1]['label']) : __('Update', 'service-booking-manager')); ?></span>
								<svg class="mpwpb-sme__nav-ico-next" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 18l6-6-6-6"></path></svg>
								<svg class="mpwpb-sme__nav-ico-save" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6L9 17l-5-5"></path></svg>
							</button>
						</div>
					</div>
					</div>

					<div class="mpwpb-sme__toast" data-sme-toast>
						<span class="dashicons dashicons-yes-alt"></span>
						<span data-sme-toast-msg><?php esc_html_e('Saved', 'service-booking-manager'); ?></span>
					</div>
				</div>
				<?php
			}

			/**
			 * Sticky right-rail preview: featured image + progressive summaries.
			 */
			private function render_preview_rail($post_id) {
				$service_title = get_the_title($post_id);
				$mpwpb_template = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_template', 'static.php');

				$thumb_id = (int) get_post_thumbnail_id($post_id);
				$hero = $thumb_id ? wp_get_attachment_image_url($thumb_id, 'medium') : '';
				?>
				<aside class="mpwpb-sme__rail">
					<div class="mpwpb-sme__rail-card mpwpb-sme__feat-card mpwpb-sme__rail-card--loading" data-sme-step-only="general">
						<?php $this->render_rail_card_skeleton('featured'); ?>
						<div class="mpwpb-sme__feat-head"><?php esc_html_e('Featured Image', 'service-booking-manager'); ?></div>
						<div class="mpwpb-sme__feat-preview">
							<img class="mpwpb-sme__rail-hero-img" id="mpwpb-sme-hero-img" src="<?php echo esc_url($hero); ?>" alt="" style="<?php echo $hero ? '' : 'display:none'; ?>"/>
							<span class="dashicons dashicons-format-image mpwpb-sme__rail-hero-ph" style="<?php echo $hero ? 'display:none' : ''; ?>"></span>
							<input type="hidden" id="mpwpb-sme-thumbnail" name="mpwpb_sme_thumbnail_id" value="<?php echo esc_attr($thumb_id); ?>"/>
						</div>
						<div class="mpwpb-sme__feat-acts">
							<button type="button" class="mpwpb-sme__feat-link" data-sme-feat-set><?php echo esc_html($thumb_id ? __('Change image', 'service-booking-manager') : __('Set image', 'service-booking-manager')); ?></button>
							<button type="button" class="mpwpb-sme__feat-link mpwpb-sme__feat-link--rm" data-sme-feat-remove style="<?php echo $thumb_id ? '' : 'display:none'; ?>"><?php esc_html_e('Remove', 'service-booking-manager'); ?></button>
						</div>
					</div>

					<?php
					/* Gallery Images: reuses the exact classic fields/handlers from
					 * Admin/settings/Gallery.php (MPWPB_Gallery_Settings) —
					 * 'mpwpb_display_slider' (real checkbox, MPWPB_Custom_Layout::
					 * switch_button()) and 'mpwpb_slider_images' (the
					 * mpwpb_add_multi_image action / MPWPB_Select_Icon_image::
					 * add_multi_image(), whose click handlers in mp_global/assets/
					 * admin/mpwpb_admin_settings.js are document-delegated and
					 * already enqueued admin-wide regardless of editor mode) —
					 * rather than a new field/upload mechanism. That classic tab
					 * is Classic-only (never in get_steps()), so without a real
					 * field here neither of these ever reaches $_POST while
					 * editing in Modern mode, and the shared save handler
					 * (Admin/MPWPB_Settings.php save_settings(), unconditional on
					 * every service save) would silently wipe both to
					 * off/empty on every Modern-mode save. */
					$gallery_display = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_display_slider', 'off');
					$gallery_checked = $gallery_display == 'off' ? '' : 'checked';
					$gallery_active_class = $gallery_display == 'off' ? '' : 'mActive';
					$gallery_image_ids = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_slider_images', array());
					?>
					<div class="mpwpb-sme__rail-card mpwpb-sme__gallery-card mpwpb-sme__rail-card--loading" data-sme-step-only="general">
						<?php $this->render_rail_card_skeleton('featured'); ?>
						<div class="mpwpb-sme__gallery-head">
							<span class="mpwpb-sme__gallery-icon" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="14" height="14" rx="2"/><path d="M21 8v11a2 2 0 0 1-2 2H8"/><circle cx="8.5" cy="10" r="1.5"/><path d="M17 15l-3.5-3.5L6 19"/></svg></span>
							<div class="mpwpb-sme__gallery-head-text">
								<div class="mpwpb-sme__feat-head"><?php esc_html_e('Gallery images', 'service-booking-manager'); ?></div>
								<p class="mpwpb-sme__gallery-sub" data-sme-gallery-sub
								   data-on="<?php esc_attr_e('Shown as a slider on the service page', 'service-booking-manager'); ?>"
								   data-off="<?php esc_attr_e('Slider off', 'service-booking-manager'); ?>"><?php echo $gallery_checked ? esc_html__('Shown as a slider on the service page', 'service-booking-manager') : esc_html__('Slider off', 'service-booking-manager'); ?></p>
							</div>
							<?php MPWPB_Custom_Layout::switch_button('mpwpb_display_slider', $gallery_checked); ?>
						</div>
						<div class="mpwpb-sme__gallery-body <?php echo esc_attr($gallery_active_class); ?>" data-collapse="#mpwpb_display_slider"
						     data-add-tile="<?php esc_attr_e('Add', 'service-booking-manager'); ?>"
						     data-add-title="<?php esc_attr_e('Add photos', 'service-booking-manager'); ?>"
						     data-add-hint="<?php esc_attr_e('Choose from the Media Library. Pick several at once.', 'service-booking-manager'); ?>"
						     data-one="<?php esc_attr_e('1 photo', 'service-booking-manager'); ?>"
						     data-many="<?php /* translators: %d: number of photos */ esc_attr_e('%d photos', 'service-booking-manager'); ?>"
						     data-reorder="<?php esc_attr_e('Drag to reorder', 'service-booking-manager'); ?>"
						     data-remove="<?php esc_attr_e('Remove photo', 'service-booking-manager'); ?>">
							<?php do_action('mpwpb_add_multi_image', 'mpwpb_slider_images', $gallery_image_ids); ?>
						</div>
						<p class="mpwpb-sme__gallery-off" data-sme-gallery-off<?php echo $gallery_checked ? ' hidden' : ''; ?>><?php esc_html_e('The photo slider is hidden on the service page. Your photos are kept; switch it on to show them again.', 'service-booking-manager'); ?></p>
					</div>

					<?php
					$mpwpb_pm_active = MPWPB_Global_Function::has_functional_payment_method();
					$mpwpb_pm_type = MPWPB_Global_Function::get_payment_method_type();
					$mpwpb_pm_type_label = $mpwpb_pm_type === 'woocommerce'
						? __('WooCommerce', 'service-booking-manager')
						: ($mpwpb_pm_type === 'custom' ? __('Custom Payment', 'service-booking-manager') : __('Not set', 'service-booking-manager'));
					$mpwpb_pm_gateway_names = [];
					if ($mpwpb_pm_type === 'woocommerce' && function_exists('WC') && WC()->payment_gateways()) {
						foreach (WC()->payment_gateways()->payment_gateways() as $mpwpb_pm_gw) {
							if ($mpwpb_pm_gw->enabled === 'yes') {
								$mpwpb_pm_gateway_names[] = $mpwpb_pm_gw->get_method_title();
							}
						}
					} elseif ($mpwpb_pm_type === 'custom' && class_exists('MPWPB_Native_Checkout')) {
						$mpwpb_pm_gateway_names = array_values(MPWPB_Native_Checkout::get_enabled_gateways());
					}
					?>
					<?php
					// Ready = the same check that used to decide whether the
					// "Configure payment method" warning showed.
					if (!$mpwpb_pm_active) {
						$mpwpb_pm_problem = $mpwpb_pm_type === 'woocommerce' || $mpwpb_pm_type === 'custom'
							? ($mpwpb_pm_gateway_names
								? __('Payment isn\'t ready yet, so customers can\'t complete a booking.', 'service-booking-manager')
								: __('No payment gateway is turned on, so customers can\'t complete a booking yet.', 'service-booking-manager'))
							: __('No payment method is set up, so customers can\'t complete a booking yet.', 'service-booking-manager');
					}
					?>
					<div class="mpwpb-sme__rail-card mpwpb-sme__rail-card--loading mpwpb-sme__pay-card<?php echo $mpwpb_pm_active ? ' is-ready' : ' is-setup'; ?>" data-sme-step-only="general">
						<?php $this->render_rail_card_skeleton('info'); ?>
						<div class="mpwpb-sme__pay-head">
							<span class="mpwpb-sme__pay-icon" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20M6 15h4"/></svg></span>
							<div class="mpwpb-sme__pay-head-text">
								<div class="mpwpb-sme__feat-head"><?php esc_html_e('Payment', 'service-booking-manager'); ?></div>
								<p><?php esc_html_e('How customers pay for this booking.', 'service-booking-manager'); ?></p>
							</div>
							<span class="mpwpb-sme__pay-status">
								<span aria-hidden="true"></span><?php echo $mpwpb_pm_active ? esc_html__('Ready', 'service-booking-manager') : esc_html__('Needs setup', 'service-booking-manager'); ?>
							</span>
						</div>
						<div class="mpwpb-sme__pay-body">
							<div class="mpwpb-sme__pay-method">
								<span class="mpwpb-sme__pay-method-icon" aria-hidden="true"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><path d="M3 6h18M16 10a4 4 0 0 1-8 0"/></svg></span>
								<div>
									<div class="mpwpb-sme__pay-method-label"><?php esc_html_e('Checkout by', 'service-booking-manager'); ?></div>
									<div class="mpwpb-sme__pay-method-name"><?php echo esc_html($mpwpb_pm_type_label); ?></div>
								</div>
							</div>
							<?php if ($mpwpb_pm_gateway_names) : ?>
								<div class="mpwpb-sme__pay-gateways">
									<div class="mpwpb-sme__pay-gateways-label"><?php esc_html_e('Active gateways', 'service-booking-manager'); ?></div>
									<div class="mpwpb-sme__pay-chips">
										<?php foreach ($mpwpb_pm_gateway_names as $mpwpb_pm_gw_name) : ?>
											<span class="mpwpb-sme__pay-chip"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12l5 5 9-10"/></svg><?php echo esc_html(wp_strip_all_tags($mpwpb_pm_gw_name)); ?></span>
										<?php endforeach; ?>
									</div>
								</div>
							<?php endif; ?>
							<?php if ($mpwpb_pm_active) : ?>
								<a href="#" class="mpwpb-sme__pay-btn" data-sme-payment-modal-open>
									<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 21v-7M4 10V3M12 21v-9M12 8V3M20 21v-5M20 12V3M1 14h6M9 8h6M17 16h6"/></svg>
									<?php esc_html_e('Payment settings', 'service-booking-manager'); ?>
								</a>
							<?php else : ?>
								<div class="mpwpb-sme__pay-warning" role="status">
									<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 9v4M12 17h.01"/><path d="M10.3 3.9L1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/></svg>
									<span><?php echo esc_html($mpwpb_pm_problem); ?></span>
								</div>
								<a href="#" class="mpwpb-sme__pay-btn mpwpb-sme__pay-btn--primary" data-sme-payment-modal-open>
									<?php esc_html_e('Set up payment', 'service-booking-manager'); ?>
									<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
								</a>
							<?php endif; ?>
						</div>
					</div>

					<?php
					/* "Add To Cart Form Shortcode" (read-only display) + "Service
					 * template" (real <select name="mpwpb_template">) — relocated
					 * here by JS from MPWPB_General_Settings::general_settings()'s
					 * card in the General step (still rendered normally there
					 * first, untouched), so mpwpb_template still submits exactly
					 * once. */
					?>
					<?php $mpwpb_shortcode = '[service-booking post_id="' . absint($post_id) . '"]'; ?>
					<div class="mpwpb-sme__rail-card mpwpb-sme__rail-card--loading mpwpb-sme__code-card" data-sme-step-only="general">
						<?php $this->render_rail_card_skeleton('info'); ?>
						<div class="mpwpb-sme__pay-head">
							<span class="mpwpb-sme__pay-icon" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 6l-6 6 6 6M16 6l6 6-6 6"/></svg></span>
							<div class="mpwpb-sme__pay-head-text">
								<div class="mpwpb-sme__feat-head"><?php esc_html_e('Shortcode & template', 'service-booking-manager'); ?></div>
								<p><?php esc_html_e('Show this booking form anywhere on your site.', 'service-booking-manager'); ?></p>
							</div>
						</div>
						<div class="mpwpb-sme__code-block">
							<div class="mpwpb-sme__code-label" id="mpwpb-sme-shortcode-label"><?php esc_html_e('Booking form shortcode', 'service-booking-manager'); ?></div>
							<div class="mpwpb-sme__code-strip">
								<code data-sme-shortcode aria-labelledby="mpwpb-sme-shortcode-label"><?php echo esc_html($mpwpb_shortcode); ?></code>
								<button type="button" class="mpwpb-sme__code-copy" data-sme-copy-shortcode
								        data-copied="<?php esc_attr_e('Copied', 'service-booking-manager'); ?>"
								        data-failed="<?php esc_attr_e('Press Ctrl/Cmd+C', 'service-booking-manager'); ?>">
									<svg class="mpwpb-sme__code-copy-icon" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="12" height="12" rx="2"/><path d="M5 15V5a2 2 0 0 1 2-2h10"/></svg>
									<svg class="mpwpb-sme__code-done-icon" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12l5 5 9-10"/></svg>
									<span data-sme-copy-label><?php esc_html_e('Copy', 'service-booking-manager'); ?></span>
								</button>
							</div>
							<p class="mpwpb-sme__code-hint"><?php esc_html_e('Paste it into any page or post to show this booking form there.', 'service-booking-manager'); ?></p>
						</div>
						<div class="mpwpb-sme__code-divider" aria-hidden="true"></div>
						<div class="mpwpb-sme__code-block">
							<label class="mpwpb-sme__code-label" for="mpwpb-sme-template"><?php esc_html_e('Page template', 'service-booking-manager'); ?></label>
							<?php // The real <select name="mpwpb_template"> is moved in here by JS (relocateShortcodeAndTemplate()). ?>
							<div class="mpwpb-sme__field-slot mpwpb-sme__code-select" data-sme-template-slot></div>
							<p class="mpwpb-sme__code-hint"><?php esc_html_e('The layout used for this service\'s own page.', 'service-booking-manager'); ?></p>
						</div>
					</div>

					<?php
					/**
					 * Mocked, not iframed: a real iframe of the live preview URL
					 * pulled in the full theme + every frontend asset the booking
					 * widget depends on (see mpwpb-categories-services-modern.js's
					 * own header comment on the parallel Services & Pricing UI for
					 * the same "reuse the data, not the page" reasoning) and still
					 * needed client-side surgery to isolate just the form. This
					 * renders a lightweight static mock from the same service data
					 * instead, then mpwpb-service-edit-modern.js keeps it live by
					 * listening for the 'mpwpb:csm-services-changed' event
					 * mpwpb-categories-services-modern.js fires on every add/edit/
					 * delete in the Services & Pricing list -- no network round trip.
					 */
					$fp_services = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_service', array());
					$fp_services = is_array($fp_services) ? $fp_services : array();
					// Keys left as-is (not array_values()'d): these are the same
					// stable per-service ids MPWPB_Categories_Services_Modern::
					// get_payload() exposes as `id`, so data-sme-fp-add="<id>"
					// here still matches after a live update swaps the rows.
					$fp_currency = MPWPB_Global_Function::is_wc_payment_mode()
						? get_woocommerce_currency_symbol()
						: MPWPB_Global_Function::native_currency_setting('symbol', '$');
					$preview_url = get_preview_post_link($post_id);

					/* Extra services are page-wide (no per-service link in the
					 * real data model -- see mpwpb_extra_service's save/read
					 * sites), so "has extra service option" just means: this
					 * booking page has the switch on AND at least one extra
					 * configured. The preview shows the list once ANY regular
					 * service is picked, not tied to which one. */
					$fp_extra_services = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_extra_service', array());
					$fp_extra_services = is_array($fp_extra_services) ? $fp_extra_services : array();

					// This service's display mode (per-service, Admin/MPWPB_Settings.php
					// save_settings()) and the site-wide theme color (shared across
					// every service, Admin/MPWPB_Settings_Global.php's classic color
					// picker, saved here via its own immediate AJAX call -- see
					// ajax_save_theme_color() above) for the card right after this one.
					$booking_display_mode = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_booking_display_mode', 'popup');
					$current_theme_color = MPWPB_Global_Function::get_style_settings('theme_color', '#00685f');
					$theme_color_swatches = array('#00685f', '#2451e0', '#d97706', '#be123c', '#7c3aed', '#0f172a');

					/* Group services into category -> subcategory -> service,
					 * same three meta keys/relationships MPWPB_Categories_
					 * Services_Modern::get_payload() reads. A service with no
					 * (or an unknown) category falls back to $fp_uncategorized
					 * and renders as a plain row exactly like before this
					 * grouping existed -- so an installation with no
					 * categories at all still looks and behaves unchanged. */
					$fp_categories = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_category_service', array());
					$fp_categories = is_array($fp_categories) ? $fp_categories : array();
					$fp_sub_categories = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_sub_category_service', array());
					$fp_sub_categories = is_array($fp_sub_categories) ? $fp_sub_categories : array();

					$fp_services_by_cat = array();
					$fp_services_by_sub = array();
					$fp_uncategorized = array();
					foreach ($fp_services as $fp_sid => $fp_svc) {
						$fp_parent_cat = (isset($fp_svc['parent_cat']) && $fp_svc['parent_cat'] !== '') ? (string) $fp_svc['parent_cat'] : '';
						$fp_sub_cat = (isset($fp_svc['sub_cat']) && $fp_svc['sub_cat'] !== '') ? (string) $fp_svc['sub_cat'] : '';
						if ($fp_parent_cat === '' || !isset($fp_categories[$fp_parent_cat])) {
							$fp_uncategorized[] = $fp_sid;
						} elseif (
							$fp_sub_cat !== ''
							&& isset($fp_sub_categories[$fp_sub_cat]['cat_id'])
							&& (string) $fp_sub_categories[$fp_sub_cat]['cat_id'] === $fp_parent_cat
						) {
							// A saved sub_cat id isn't enough on its own -- it's
							// also the default/empty value for "no subcategory",
							// so e.g. sub_cat=0 on a service whose parent_cat
							// ISN'T subcategory 0's own parent must NOT be
							// treated as a real subcategory assignment (it would
							// silently misfile the service under some other
							// category's subcategory of the same id instead of
							// directly under its own, correct parent_cat).
							$fp_services_by_sub[$fp_sub_cat][] = $fp_sid;
						} else {
							$fp_services_by_cat[$fp_parent_cat][] = $fp_sid;
						}
					}
					$fp_subs_by_cat = array();
					foreach ($fp_sub_categories as $fp_subid => $fp_sub) {
						$fp_sub_cat_id = isset($fp_sub['cat_id']) ? (string) $fp_sub['cat_id'] : '';
						if ($fp_sub_cat_id !== '' && isset($fp_categories[$fp_sub_cat_id])) {
							$fp_subs_by_cat[$fp_sub_cat_id][] = (string) $fp_subid;
						}
					}

					/** Renders one service row -- unchanged markup, now called from inside a group as well as flat. */
					$fp_render_row = function ($fp_id, $fp_svc) use ($fp_currency) {
						$fp_name = isset($fp_svc['name']) ? $fp_svc['name'] : '';
						$fp_price = isset($fp_svc['price']) ? $fp_svc['price'] : '0';
						$fp_has_duration = isset($fp_svc['duration']) && trim((string) $fp_svc['duration']) !== '';
						$fp_duration = $fp_has_duration ? $fp_svc['duration'] : __('No duration set', 'service-booking-manager');
						?>
						<div class="mpwpb-sme__fp-row" data-sme-fp-row="<?php echo esc_attr($fp_id); ?>">
							<div class="mpwpb-sme__fp-row-main">
								<div class="mpwpb-sme__fp-row-name"><?php echo esc_html($fp_name); ?></div>
								<div class="mpwpb-sme__fp-row-meta">
									<span class="mpwpb-sme__fp-row-dur<?php echo $fp_has_duration ? '' : ' is-missing'; ?>">
										<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><polyline points="12,7 12,12 15,14"></polyline></svg>
										<?php echo esc_html($fp_duration); ?>
									</span>
									<span class="mpwpb-sme__fp-row-price" data-sme-fp-row-price="<?php echo esc_attr($fp_price); ?>"><?php echo esc_html($fp_currency . $fp_price); ?></span>
								</div>
							</div>
							<div class="mpwpb-sme__fp-row-side" data-sme-fp-row-side>
								<button type="button" class="mpwpb-sme__fp-row-add" data-sme-fp-add="<?php echo esc_attr($fp_id); ?>" aria-label="<?php echo esc_attr(sprintf(__('Add %s', 'service-booking-manager'), $fp_name)); ?>">
									<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg><span><?php esc_html_e('Add', 'service-booking-manager'); ?></span>
								</button>
							</div>
						</div>
						<?php
					};
					$fp_chevron_svg = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="mpwpb-sme__fp-group-chev"><polyline points="18,15 12,9 6,15"></polyline></svg>';
					?>
					<div class="mpwpb-sme__rail-card mpwpb-sme__preview-card" data-sme-step-only="pricing availability advanced">
						<div class="mpwpb-sme__preview-head">
							<div class="mpwpb-sme__preview-head-main">
								<span class="mpwpb-sme__preview-icon" aria-hidden="true">
									<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
								</span>
								<div class="mpwpb-sme__preview-head-text">
									<div class="mpwpb-sme__preview-title-row">
										<div class="mpwpb-sme__feat-head"><?php esc_html_e('Preview mode', 'service-booking-manager'); ?></div>
									</div>
									<p class="mpwpb-sme__preview-sub"><?php esc_html_e('Customer view · nothing here is editable', 'service-booking-manager'); ?></p>
								</div>
							</div>
							<span class="mpwpb-sme__preview-live"><i aria-hidden="true"></i><?php esc_html_e('Live', 'service-booking-manager'); ?></span>
							<a class="mpwpb-sme__preview-open-icon" href="<?php echo esc_url($preview_url); ?>" target="wp-preview-<?php echo esc_attr($post_id); ?>" aria-label="<?php esc_attr_e('Open full preview in a new tab', 'service-booking-manager'); ?>" title="<?php esc_attr_e('Open full preview', 'service-booking-manager'); ?>">
								<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 4h6v6"></path><path d="M20 4L10 14"></path><path d="M18 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5"></path></svg>
							</a>
						</div>

						<div class="mpwpb-sme__fp-browser mpwpb-sme__fp-device">
						<div class="mpwpb-sme__fp-screen">
							<div class="mpwpb-sme__fp-handle" aria-hidden="true"><i></i></div>
						<div class="mpwpb-sme__fp-mock" data-sme-fp-mock style="--fp-theme:<?php echo esc_attr($current_theme_color); ?>">
							<div class="mpwpb-sme__fp-steps" data-sme-fp-steps>
								<div class="mpwpb-sme__fp-steps-item is-active" data-sme-fp-steps-item="services">
									<span class="mpwpb-sme__fp-steps-dot">1</span><?php esc_html_e('Services', 'service-booking-manager'); ?>
								</div>
								<div class="mpwpb-sme__fp-steps-sep"></div>
								<div class="mpwpb-sme__fp-steps-item" data-sme-fp-steps-item="datetime">
									<span class="mpwpb-sme__fp-steps-dot">2</span><?php esc_html_e('Date & Time', 'service-booking-manager'); ?>
								</div>
								<?php // Only shown while "Staff Member" is switched on (fpSyncSteps() in mpwpb-service-edit-modern.js renumbers the dots). ?>
								<div class="mpwpb-sme__fp-steps-sep" data-sme-fp-staff-only hidden></div>
								<div class="mpwpb-sme__fp-steps-item" data-sme-fp-steps-item="staff" data-sme-fp-staff-only hidden>
									<span class="mpwpb-sme__fp-steps-dot">3</span><?php esc_html_e('Staff', 'service-booking-manager'); ?>
								</div>
								<div class="mpwpb-sme__fp-steps-sep"></div>
								<div class="mpwpb-sme__fp-steps-item" data-sme-fp-steps-item="checkout">
									<span class="mpwpb-sme__fp-steps-dot">3</span><?php esc_html_e('Checkout', 'service-booking-manager'); ?>
								</div>
							</div>

							<div class="mpwpb-sme__fp-step mpwpb-sme__fp-step--services is-active" data-sme-fp-step="services">
								<div class="mpwpb-sme__fp-heading"><?php esc_html_e('Our services', 'service-booking-manager'); ?></div>
								<p class="mpwpb-sme__fp-sub"><?php esc_html_e('Tap a service to start booking. You can add more inside.', 'service-booking-manager'); ?></p>

								<div class="mpwpb-sme__fp-rows" data-sme-fp-rows<?php echo empty($fp_services) ? ' style="display:none"' : ''; ?>>
									<?php foreach ($fp_categories as $fp_cat_id => $fp_cat) :
										$fp_cat_id = (string) $fp_cat_id;
										$fp_direct_ids = isset($fp_services_by_cat[$fp_cat_id]) ? $fp_services_by_cat[$fp_cat_id] : array();
										$fp_sub_ids = isset($fp_subs_by_cat[$fp_cat_id]) ? $fp_subs_by_cat[$fp_cat_id] : array();
										$fp_cat_count = count($fp_direct_ids);
										foreach ($fp_sub_ids as $fp_sub_id) {
											$fp_cat_count += isset($fp_services_by_sub[$fp_sub_id]) ? count($fp_services_by_sub[$fp_sub_id]) : 0;
										}
										if ($fp_cat_count === 0) {
											continue; // nothing in this category (or its subcategories) to preview
										}
										?>
										<div class="mpwpb-sme__fp-group" data-sme-fp-group="<?php echo esc_attr($fp_cat_id); ?>">
											<button type="button" class="mpwpb-sme__fp-group-head" data-sme-fp-group-toggle>
												<span class="mpwpb-sme__fp-group-icon" aria-hidden="true"><span class="dashicons dashicons-category"></span></span>
												<span class="mpwpb-sme__fp-group-name"><?php echo esc_html(isset($fp_cat['name']) ? $fp_cat['name'] : ''); ?></span>
												<span class="mpwpb-sme__fp-group-count"><?php echo (int) $fp_cat_count; ?></span>
												<?php echo $fp_chevron_svg; ?>
											</button>
											<div class="mpwpb-sme__fp-group-body" data-sme-fp-group-body>
												<?php
												foreach ($fp_direct_ids as $fp_id) {
													if (isset($fp_services[$fp_id])) {
														$fp_render_row($fp_id, $fp_services[$fp_id]);
													}
												}
												foreach ($fp_sub_ids as $fp_sub_id) {
													$fp_sub_svc_ids = isset($fp_services_by_sub[$fp_sub_id]) ? $fp_services_by_sub[$fp_sub_id] : array();
													if (empty($fp_sub_svc_ids)) {
														continue;
													}
													?>
													<div class="mpwpb-sme__fp-subgroup" data-sme-fp-group="<?php echo esc_attr($fp_sub_id); ?>">
														<button type="button" class="mpwpb-sme__fp-subgroup-head" data-sme-fp-group-toggle>
															<span class="mpwpb-sme__fp-group-icon" aria-hidden="true"><span class="dashicons dashicons-admin-generic"></span></span>
															<span class="mpwpb-sme__fp-group-name"><?php echo esc_html(isset($fp_sub_categories[$fp_sub_id]['name']) ? $fp_sub_categories[$fp_sub_id]['name'] : ''); ?></span>
															<span class="mpwpb-sme__fp-group-count"><?php echo (int) count($fp_sub_svc_ids); ?></span>
															<?php echo $fp_chevron_svg; ?>
														</button>
														<div class="mpwpb-sme__fp-subgroup-body" data-sme-fp-group-body>
															<?php
															foreach ($fp_sub_svc_ids as $fp_id) {
																if (isset($fp_services[$fp_id])) {
																	$fp_render_row($fp_id, $fp_services[$fp_id]);
																}
															}
															?>
														</div>
													</div>
													<?php
												}
												?>
											</div>
										</div>
									<?php endforeach; ?>
									<?php
									foreach ($fp_uncategorized as $fp_id) {
										if (isset($fp_services[$fp_id])) {
											$fp_render_row($fp_id, $fp_services[$fp_id]);
										}
									}
									?>
								</div>

								<p class="mpwpb-sme__fp-empty" data-sme-fp-empty<?php echo empty($fp_services) ? '' : ' style="display:none"'; ?>><?php esc_html_e('Add a service in Services & Pricing to see it appear here.', 'service-booking-manager'); ?></p>

								<?php // Always rendered (hidden) so switching Extra Service on/off or editing
								// the list live can show it; fpUpdateExtrasVisibility() decides. ?>
									<div class="mpwpb-sme__fp-extras" data-sme-fp-extras style="display:none">
										<div class="mpwpb-sme__fp-extras-label"><?php esc_html_e('Extra services', 'service-booking-manager'); ?></div>
										<div data-sme-fp-extra-rows>
											<?php foreach ($fp_extra_services as $fp_ex_id => $fp_ex) :
												$fp_ex_name = isset($fp_ex['name']) ? $fp_ex['name'] : '';
												$fp_ex_price = isset($fp_ex['price']) ? $fp_ex['price'] : '0';
												?>
												<div class="mpwpb-sme__fp-row" data-sme-fp-extra-row="<?php echo esc_attr($fp_ex_id); ?>">
													<div class="mpwpb-sme__fp-row-main">
														<div class="mpwpb-sme__fp-row-name"><?php echo esc_html($fp_ex_name); ?></div>
														<div class="mpwpb-sme__fp-row-meta">
															<span class="mpwpb-sme__fp-row-price" data-sme-fp-row-price="<?php echo esc_attr($fp_ex_price); ?>"><?php echo esc_html($fp_currency . $fp_ex_price); ?></span>
														</div>
													</div>
													<div class="mpwpb-sme__fp-row-side" data-sme-fp-row-side>
														<button type="button" class="mpwpb-sme__fp-row-add" data-sme-fp-extra-add="<?php echo esc_attr($fp_ex_id); ?>" aria-label="<?php echo esc_attr(sprintf(__('Add %s', 'service-booking-manager'), $fp_ex_name)); ?>">
															<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
														<span><?php esc_html_e('Add', 'service-booking-manager'); ?></span></button>
													</div>
												</div>
											<?php endforeach; ?>
										</div>
									</div>

								<?php
								/* Built/updated entirely by JS (fpUpdateServicesSummary) as qty
								 * changes -- empty + hidden until at least one service is added. */
								?>
								<div class="mpwpb-sme__fp-bar" data-sme-fp-bar<?php echo empty($fp_services) ? ' style="display:none"' : ''; ?>>
									<span data-sme-fp-bar-count><?php esc_html_e('Nothing selected yet', 'service-booking-manager'); ?></span>
									<strong data-sme-fp-bar-total></strong>
								</div>

								<?php /* Both labels are rendered; CSS shows one from the button's own
								 * :disabled state, which fpUpdateCta() already toggles. */ ?>
								<button type="button" class="mpwpb-sme__fp-cta" data-sme-fp-cta disabled<?php echo empty($fp_services) ? ' style="display:none"' : ''; ?>>
									<span class="mpwpb-sme__fp-cta-label mpwpb-sme__fp-cta-label--ready"><?php esc_html_e('Continue to schedule', 'service-booking-manager'); ?>
										<svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
									<span class="mpwpb-sme__fp-cta-label mpwpb-sme__fp-cta-label--idle">
										<svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="5" y="11" width="14" height="9" rx="2" stroke="currentColor" stroke-width="2.2"/><path d="M8 11V8a4 4 0 0 1 8 0v3" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/></svg>
										<?php esc_html_e('Select a service to continue', 'service-booking-manager'); ?></span>
								</button>

								<div class="mpwpb-sme__fp-trust" data-sme-fp-trust<?php echo empty($fp_services) ? ' style="display:none"' : ''; ?>>
									<span class="mpwpb-sme__fp-trust-item">
										<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3l7 3v5c0 5-3.5 8.5-7 10-3.5-1.5-7-5-7-10V6l7-3z"></path></svg>
										<?php esc_html_e('Secure payment', 'service-booking-manager'); ?>
									</span>
									<span class="mpwpb-sme__fp-trust-item">
										<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"></rect><line x1="3" y1="10" x2="21" y2="10"></line><line x1="8" y1="3" x2="8" y2="7"></line><line x1="16" y1="3" x2="16" y2="7"></line></svg>
										<?php esc_html_e('Free rescheduling up to 24h', 'service-booking-manager'); ?>
									</span>
								</div>
							</div>

							<?php
							/* Steps 2 (Date & Time) and 3 (Checkout) are built entirely
							 * by JS — "Today"/"Tomorrow" need the real current date and
							 * the order summary needs the live qty selections, so
							 * there's nothing server-side worth pre-rendering for either. */
							?>
							<div class="mpwpb-sme__fp-step mpwpb-sme__fp-step--datetime" data-sme-fp-step="datetime" hidden></div>
							<div class="mpwpb-sme__fp-step mpwpb-sme__fp-step--staff" data-sme-fp-step="staff" hidden></div>
							<div class="mpwpb-sme__fp-step mpwpb-sme__fp-step--checkout" data-sme-fp-step="checkout" hidden></div>
						</div>
						</div>
						</div>

						<div class="mpwpb-sme__feat-acts">
							<button type="button" class="mpwpb-sme__fp-back" data-sme-fp-footer-back hidden>
								<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 18l-6-6 6-6"></path></svg>
								<?php esc_html_e('Back', 'service-booking-manager'); ?>
							</button>
							<span class="mpwpb-sme__fp-note"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="M12 8v4"></path><path d="M12 16h.01"></path></svg><?php esc_html_e('Preview only. Nothing is booked.', 'service-booking-manager'); ?></span>
							<a class="mpwpb-sme__feat-link mpwpb-sme__preview-open" href="<?php echo esc_url($preview_url); ?>" target="wp-preview-<?php echo esc_attr($post_id); ?>">
								<?php esc_html_e('Open full preview', 'service-booking-manager'); ?>
								<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 4h6v6"></path><path d="M20 4L10 14"></path><path d="M8 5H5a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2v-3"></path></svg>
							</a>
						</div>
					</div>

					<?php
					/**
					 * Display mode is per-service (Admin/MPWPB_Settings.php's
					 * save_settings(), same save_post flow every other field on
					 * this screen already goes through -- a real off-screen
					 * <select> drives it, nothing new to wire up). Theme color is
					 * site-wide (mpwpb_style_settings['theme_color'], the SAME
					 * field the classic Settings page's color picker saves) and
					 * doesn't belong to this post at all, so it saves itself
					 * immediately on click via ajax_save_theme_color() above
					 * instead of waiting for Update/Publish.
					 */
					?>
					<div class="mpwpb-sme__rail-card mpwpb-sme__bw-card" data-sme-step-only="pricing availability advanced" style="--bw-accent:<?php echo esc_attr($current_theme_color); ?>">
						<div class="mpwpb-sme__feat-head"><?php esc_html_e('Booking widget', 'service-booking-manager'); ?></div>
						<p class="mpwpb-sme__bw-sub"><?php esc_html_e('How customers open the booking form.', 'service-booking-manager'); ?></p>
						<div class="mpwpb-sme__rail-field-list">

							<div class="mpwpb-sme__field-slot">
								<?php
								$bw_modes = array(
									'popup' => array(__('Popup', 'service-booking-manager'), __('A "Book now" button opens the form over the page.', 'service-booking-manager')),
									'inline' => array(__('Inline', 'service-booking-manager'), __('The form sits right on the service page.', 'service-booking-manager')),
								);
								$bw_active = $booking_display_mode === 'inline' ? 'inline' : 'popup';
								$bw_tips = array(
									'popup' => __('Best when the page has a lot to read. Customers stay on the page and book in an overlay.', 'service-booking-manager'),
									'inline' => __('Best for short pages. Customers start booking without an extra click.', 'service-booking-manager'),
								);
								?>
								<div class="mpwpb-sme__bw-modes" role="radiogroup" aria-label="<?php esc_attr_e('Booking form display', 'service-booking-manager'); ?>" data-sme-display-mode>
									<?php foreach ($bw_modes as $bw_value => $bw_mode) : $bw_on = $bw_value === $bw_active; ?>
										<button type="button" role="radio" aria-checked="<?php echo $bw_on ? 'true' : 'false'; ?>" class="mpwpb-sme__bw-mode<?php echo $bw_on ? ' is-active' : ''; ?>" data-sme-display-mode-value="<?php echo esc_attr($bw_value); ?>" data-tip="<?php echo esc_attr($bw_tips[$bw_value]); ?>">
											<span class="mpwpb-sme__bw-art mpwpb-sme__bw-art--<?php echo esc_attr($bw_value); ?>" aria-hidden="true">
												<i class="l1"></i><i class="l2"></i><i class="l3"></i>
												<?php if ($bw_value === 'popup') : ?>
													<i class="dim"></i><i class="box"><i class="b1"></i><i class="b2"></i><i class="cta"></i></i>
												<?php else : ?>
													<i class="box"><i class="b1"></i><i class="c1"></i><i class="c2"></i><i class="cta"></i></i>
												<?php endif; ?>
											</span>
											<span class="mpwpb-sme__bw-mode-row">
												<span class="mpwpb-sme__bw-mode-name"><?php echo esc_html($bw_mode[0]); ?></span>
												<span class="mpwpb-sme__bw-radio" aria-hidden="true"></span>
											</span>
											<span class="mpwpb-sme__bw-mode-hint"><?php echo esc_html($bw_mode[1]); ?></span>
										</button>
									<?php endforeach; ?>
								</div>
								<p class="mpwpb-sme__bw-tip" data-sme-display-mode-tip>
									<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8v.5M12 11v5"/></svg>
									<span><?php echo esc_html($bw_tips[$bw_active]); ?></span>
								</p>
								<div class="mpwpb-sme__offscreen">
									<select name="mpwpb_booking_display_mode" data-sme-display-mode-real>
										<option value="popup" <?php selected($booking_display_mode, 'popup'); ?>><?php esc_html_e('Popup Modal', 'service-booking-manager'); ?></option>
										<option value="inline" <?php selected($booking_display_mode, 'inline'); ?>><?php esc_html_e('Inline Embedded', 'service-booking-manager'); ?></option>
									</select>
								</div>
							</div>

							<div class="mpwpb-sme__field-slot">
								<div class="mpwpb-sme__bw-label"><?php esc_html_e('Theme color', 'service-booking-manager'); ?></div>
								<p class="mpwpb-sme__bw-hint mpwpb-sme__bw-hint--top"><?php esc_html_e('Used by every service\'s booking widget. Saved right away.', 'service-booking-manager'); ?></p>
								<div class="mpwpb-sme__bw-swatches" data-sme-theme-swatches>
									<?php foreach ($theme_color_swatches as $swatch) : ?>
										<button type="button" class="mpwpb-sme__bw-swatch<?php echo strtolower($swatch) === strtolower($current_theme_color) ? ' is-active' : ''; ?>" style="background:<?php echo esc_attr($swatch); ?>" data-sme-theme-color="<?php echo esc_attr($swatch); ?>" aria-label="<?php echo esc_attr($swatch); ?>"></button>
									<?php endforeach; ?>
									<?php $is_custom_theme_color = !in_array(strtolower($current_theme_color), array_map('strtolower', $theme_color_swatches), true); ?>
									<label class="mpwpb-sme__bw-swatch mpwpb-sme__bw-swatch--custom<?php echo $is_custom_theme_color ? ' is-active has-color' : ''; ?>"<?php echo $is_custom_theme_color ? ' style="--swatch:' . esc_attr($current_theme_color) . '"' : ''; ?> title="<?php esc_attr_e('Custom color', 'service-booking-manager'); ?>">
										<span class="mpwpb-sme__bw-swatch-plus" aria-hidden="true">+</span>
										<input type="color" data-sme-theme-color-custom value="<?php echo esc_attr($current_theme_color); ?>" aria-label="<?php esc_attr_e('Custom color', 'service-booking-manager'); ?>">
									</label>
								</div>
							</div>

						</div>
					</div>

					<div class="mpwpb-sme__rail-card mpwpb-sme__rail-card--loading" data-sme-step-only="general">
						<?php $this->render_rail_card_skeleton('info'); ?>
						<div class="mpwpb-sme__feat-head"><?php esc_html_e('General Info Summary', 'service-booking-manager'); ?></div>
						<div class="mpwpb-sme__rail-info-list">
							<div class="mpwpb-sme__rail-info-row">
								<span><?php esc_html_e('Service Title', 'service-booking-manager'); ?></span>
								<strong><?php echo esc_html($service_title !== '' ? $service_title : '—'); ?></strong>
							</div>
							<div class="mpwpb-sme__rail-info-row">
								<span><?php esc_html_e('Template', 'service-booking-manager'); ?></span>
								<strong><?php echo esc_html($mpwpb_template !== '' ? $mpwpb_template : '—'); ?></strong>
							</div>
						</div>
					</div>

					<?php
					$date_type = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_date_type', 'repeated');
					$date_type_label = $date_type === 'particular' ? __('Particular Dates', 'service-booking-manager') : __('Repeated', 'service-booking-manager');
					$time_slot_length = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_time_slot_length');
					$capacity_per_session = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_capacity_per_session');
					?>
					<div class="mpwpb-sme__rail-card mpwpb-sme__rail-card--loading" data-sme-step-only="advanced">
						<?php $this->render_rail_card_skeleton('info'); ?>
						<div class="mpwpb-sme__feat-head"><?php esc_html_e('Availability Summary', 'service-booking-manager'); ?></div>
						<div class="mpwpb-sme__rail-info-list">
							<div class="mpwpb-sme__rail-info-row">
								<span><?php esc_html_e('Date Type', 'service-booking-manager'); ?></span>
								<strong><?php echo esc_html($date_type_label); ?></strong>
							</div>
							<div class="mpwpb-sme__rail-info-row">
								<span><?php esc_html_e('Time Slot Length', 'service-booking-manager'); ?></span>
								<strong><?php echo esc_html($time_slot_length !== '' ? $time_slot_length : '—'); ?></strong>
							</div>
							<div class="mpwpb-sme__rail-info-row">
								<span><?php esc_html_e('Capacity / Session', 'service-booking-manager'); ?></span>
								<strong><?php echo esc_html($capacity_per_session !== '' ? $capacity_per_session : '—'); ?></strong>
							</div>
						</div>
					</div>

				</aside>
				<?php
			}

			/** Wrap one reused classic section in a modern card. */
			private function render_section_card($section, $post_id) {
				list($class, $method, $title, $subtitle) = $section;
				// These render their own full-width layout (sidebar + list, or
				// multiple cards in a custom grid — not a single simple field
				// form), so they're called directly instead of being wrapped in
				// the generic card below. Neither has constructor side effects to
				// avoid (unlike the classic sections), so no need to go through
				// section_instance()'s Reflection-based cache.
				$self_rendering = array('MPWPB_Categories_Services_Modern', 'MPWPB_Date_Time_Modern');
				if (in_array($class, $self_rendering, true)) {
					if (class_exists($class)) {
						$instance = new $class();
						$instance->$method($post_id);
					}
					return;
				}
				$instance = $this->section_instance($class);
				if (!$instance || !method_exists($instance, $method)) {
					return;
				}
				// Happy Hours Pricing is the one section whose own render method
				// (Admin/settings/Happy_Hours.php) puts a "PRO" badge in ITS OWN
				// <header><h2> -- but that classic <header> is hidden entirely in
				// this modern shell (.mpwpb-sme .tabsItem > header{display:none}),
				// replaced by this card's own title above. Without this, the
				// modern card's title would show no Pro indicator at all even
				// though the toggle relocated into the header-actions slot below
				// is correctly disabled/locked. Reuses the .mpwpb-pro-badge class
				// already styled by Happy_Hours.php's own inline <style> output
				// (CSS classes apply document-wide regardless of source order).
				$pro_locked = ($class === 'MPWPB_Happy_Hours_Settings' && !MPWPB_Global_Function::is_pro_active());
				?>
				<div class="mpwpb-sme__postfields" data-sme-section="<?php echo esc_attr($class); ?>">
					<div class="mpwpb-sme__postfields-header">
						<div class="mpwpb-sme__postfields-header-text">
							<div class="mpwpb-sme__postfields-header-title">
								<?php echo esc_html($title); ?>
								<?php if ($pro_locked) : ?>
									<span class="mpwpb-pro-badge"><?php esc_html_e('PRO', 'service-booking-manager'); ?></span>
								<?php endif; ?>
							</div>
							<?php if ($subtitle) : ?>
								<div class="mpwpb-sme__postfields-header-sub"><?php echo esc_html($subtitle); ?></div>
							<?php endif; ?>
						</div>
						<?php // Filled by JS (relocateHeaderToggle()) for sections that have their own
						// on/off switch (e.g. Extra Service's "mpwpb_extra_service_active"), so the
						// real toggle sits beside the title instead of buried in the card body. ?>
						<div class="mpwpb-sme__postfields-header-actions" data-sme-header-actions></div>
					</div>
					<div class="mpwpb-sme__postfields-body">
						<?php $instance->$method($post_id); ?>
					</div>
				</div>
				<?php
			}

			/**
			 * "Post Title" / "Service Sub Title" / "Service Overview"
			 * fields, placed at the top of the General step.
			 * #mpwpb-sme-title-inline just mirrors the real #title input
			 * (kept in sync by JS, same as the topbar title). Service Sub
			 * Title (from MPWPB_General_Settings::general_settings()'s card
			 * further down this step) and Service Overview content (from
			 * MPWPB_Service_Details::service_details(), likewise) are
			 * relocated here by JS — those methods render normally,
			 * unchanged, in their own cards first; JS then moves the real
			 * <textarea> DOM node up here (so each field still submits
			 * exactly once) and removes the now-empty wrapper left behind,
			 * so nothing shows twice in one step. Service Title isn't shown
			 * in the modern editor at all — a hidden field preserves its
			 * existing value unchanged (Classic mode's own field is
			 * untouched and still fully editable there); the classic
			 * .service-title section is removed by JS from wherever it
			 * would otherwise render. Service Overview has no visible
			 * toggle here — the modern editor always submits
			 * mpwpb_service_overview_status=on via a hidden field (Classic
			 * mode's own real toggle is untouched and still fully
			 * functional there). WordPress' native post_content editor
			 * (#postdivrich) is not used by the modern editor at all; it's
			 * hidden via CSS instead.
			 */
			private function render_post_fields_subsection($post_id) {
				$title = get_the_title($post_id);
				// Overview on/off is a real checkbox now (unchecked = absent =
				// "off", MPWPB_Settings::save_settings()); the booking page only
				// shows the overview when it is "on" (MPWPB_Static_Template).
				$overview_on = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_service_overview_status', 'on') !== 'off';
				?>
				<div class="mpwpb-sme__postfields basic-Information mpwpb-sme__details" data-sme-postfields>
					<div class="mpwpb-sme__postfields-header">
						<span class="mpwpb-sme__details-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><path d="M14 3v6h6"/><path d="M8 13h8M8 17h5"/></svg></span>
						<div>
							<div class="mpwpb-sme__postfields-header-title"><?php esc_html_e('Service details', 'service-booking-manager'); ?></div>
							<div class="mpwpb-sme__postfields-header-sub"><?php esc_html_e('What customers read at the top of your booking page.', 'service-booking-manager'); ?></div>
						</div>
					</div>
					<div class="mpwpb-sme__postfields-body">
						<div class="mpwpb-sme__details-field">
							<div class="mpwpb-sme__details-label-row">
								<label for="mpwpb-sme-title-inline"><?php esc_html_e('Service name', 'service-booking-manager'); ?> <span class="textRequired">*</span></label>
								<span class="mpwpb-sme__details-count" data-sme-count-for="#mpwpb-sme-title-inline" data-max="70"></span>
							</div>
							<input type="text" class="formControl mpwpb-sme__details-name" id="mpwpb-sme-title-inline" value="<?php echo esc_attr($title); ?>" placeholder="<?php esc_attr_e('e.g. Teeth cleaning', 'service-booking-manager'); ?>"/>
							<p class="mpwpb-sme__details-hint"><?php esc_html_e('Shown as the page title and in the booking summary.', 'service-booking-manager'); ?></p>
						</div>

						<?php // Service Title isn't shown in the modern editor — its existing value is preserved unchanged on save (Classic mode's own field is untouched and still fully editable there). ?>
						<input type="hidden" name="mpwpb_shortcode_title" value="<?php echo esc_attr(MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_shortcode_title')); ?>"/>

						<div class="mpwpb-sme__details-field">
							<div class="mpwpb-sme__details-label-row">
								<label for="mpwpb_shortcode_sub_title"><?php esc_html_e('Tagline', 'service-booking-manager'); ?></label>
								<span class="mpwpb-sme__details-count" data-sme-count-for="textarea[name='mpwpb_shortcode_sub_title']" data-max="160"></span>
							</div>
							<div class="mpwpb-sme__field-slot" data-sme-service-subtitle-slot></div>
							<p class="mpwpb-sme__details-hint"><?php esc_html_e('One line under the name. Keep it short so it fits on phones.', 'service-booking-manager'); ?></p>
						</div>

						<div class="mpwpb-sme__details-field">
							<div class="mpwpb-sme__details-label-row mpwpb-sme__details-label-row--overview">
								<div>
									<div class="mpwpb-sme__details-title"><?php esc_html_e('Overview', 'service-booking-manager'); ?></div>
									<p class="mpwpb-sme__details-hint"><?php esc_html_e('A short description with the key points of this service.', 'service-booking-manager'); ?></p>
								</div>
								<label class="mpwpb-sme__details-switch">
									<span><?php esc_html_e('Show on page', 'service-booking-manager'); ?></span>
									<input type="checkbox" name="mpwpb_service_overview_status" value="on" role="switch" data-sme-overview-toggle <?php checked($overview_on); ?>/>
									<span class="mpwpb-sme__details-switch-track" aria-hidden="true"></span>
								</label>
							</div>
							<div class="mpwpb-sme__content-slot" data-sme-overview-slot></div>
							<p class="mpwpb-sme__details-off" data-sme-overview-off<?php echo $overview_on ? ' hidden' : ''; ?>><?php esc_html_e('The overview is hidden on the booking page. Your text is kept; switch it on to show it again.', 'service-booking-manager'); ?></p>
						</div>
					</div>
				</div>
				<?php
			}

			/* ------------------------------------------------------------------ *
			 *  Editor-style switcher (side metabox)
			 * ------------------------------------------------------------------ */

			public function render_switcher() {
				$ui = self::current_ui();
				?>
				<div class="mpwpb-sme-ui-switch" data-sme-switch>
					<button type="button" class="mpwpb-sme-ui-switch__opt<?php echo $ui === 'classic' ? ' is-active' : ''; ?>" data-sme-ui="classic"><?php esc_html_e('Classic', 'service-booking-manager'); ?></button>
					<button type="button" class="mpwpb-sme-ui-switch__opt<?php echo $ui === 'modern' ? ' is-active' : ''; ?>" data-sme-ui="modern"><?php esc_html_e('Modern', 'service-booking-manager'); ?></button>
				</div>
				<p class="howto" style="margin:8px 2px 0;color:#646970;font-size:12px;">
					<?php esc_html_e('Choose how the service editor looks for your account. This only affects you.', 'service-booking-manager'); ?>
				</p>
				<style>
					.mpwpb-sme-ui-switch{display:flex;gap:0;border:1px solid #dcdfe5;border-radius:8px;overflow:hidden;}
					.mpwpb-sme-ui-switch__opt{flex:1;border:0;background:#fff;color:#475569;font-weight:600;padding:8px 10px;cursor:pointer;}
					.mpwpb-sme-ui-switch__opt+.mpwpb-sme-ui-switch__opt{border-left:1px solid #dcdfe5;}
					.mpwpb-sme-ui-switch__opt.is-active{background:#2451e0;color:#fff;}
				</style>
				<?php
			}

			/**
			 * Save the WordPress featured image (post thumbnail) chosen from the
			 * modern rail. Only acts when the modern field is submitted, so
			 * classic-editor saves (which never post this field) are untouched.
			 */
			public function save_feature_image($post_id) {
				if (!array_key_exists('mpwpb_sme_thumbnail_id', $_POST)) {
					return;
				}
				if (get_post_type($post_id) !== MPWPB_Function::get_cpt()) {
					return;
				}
				if (!isset($_POST['mpwpb_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['mpwpb_nonce'])), 'mpwpb_nonce')) {
					return;
				}
				if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
					return;
				}
				if (!current_user_can('edit_post', $post_id)) {
					return;
				}
				$thumb = (int) wp_unslash($_POST['mpwpb_sme_thumbnail_id']);
				if ($thumb > 0) {
					set_post_thumbnail($post_id, $thumb);
				} else {
					delete_post_thumbnail($post_id);
				}
			}

			public function ajax_set_ui() {
				check_ajax_referer('mpwpb_sme_ui', 'nonce');
				if (!current_user_can('edit_posts')) {
					wp_send_json_error('forbidden');
				}
				$ui = (isset($_POST['ui']) && sanitize_text_field(wp_unslash($_POST['ui'])) === 'modern') ? 'modern' : 'classic';
				update_user_meta(get_current_user_id(), self::USER_META, $ui);
				wp_send_json_success(array('ui' => $ui));
			}

			/**
			 * Theme color (mpwpb_style_settings['theme_color'], consumed as
			 * --mpwpb_color_theme by mp_global/class/MPWPB_Global_Style.php) is a
			 * single site-wide option -- the SAME real field the classic Settings
			 * page's color picker saves (Admin/MPWPB_Settings_Global.php), just
			 * saved here through its own immediate AJAX call instead of a full
			 * options.php form submit, since it applies to every service, not
			 * just the one currently being edited. manage_options (not
			 * edit_posts) matches the capability that classic Settings page
			 * itself requires, since this changes a site-wide value.
			 */
			public function ajax_save_theme_color() {
				check_ajax_referer('mpwpb_sme_ui', 'nonce');
				if (!current_user_can('manage_options')) {
					wp_send_json_error('forbidden');
				}
				$color = isset($_POST['color']) ? sanitize_hex_color(wp_unslash($_POST['color'])) : '';
				if (!$color) {
					wp_send_json_error('invalid_color');
				}
				$settings = get_option('mpwpb_style_settings', array());
				$settings = is_array($settings) ? $settings : array();
				$settings['theme_color'] = $color;
				update_option('mpwpb_style_settings', $settings);
				wp_send_json_success(array('color' => $color));
			}

			/**
			 * Save the native post form without reloading the modern editor.
			 *
			 * edit_post() is the same WordPress core path used by post.php, so all
			 * existing save_post callbacks continue to receive the complete form.
			 */
			public function ajax_save_service() {
				$post_id = isset($_POST['post_ID']) ? absint($_POST['post_ID']) : 0;
				$post = $post_id ? get_post($post_id) : null;

				if (!$post || $post->post_type !== MPWPB_Function::get_cpt()) {
					wp_send_json_error(array('message' => esc_html__('Invalid service.', 'service-booking-manager')), 400);
				}
				if (!current_user_can('edit_post', $post_id)) {
					wp_send_json_error(array('message' => esc_html__('You are not allowed to edit this service.', 'service-booking-manager')), 403);
				}

				check_ajax_referer('update-post_' . $post_id, '_wpnonce');
				if (!isset($_POST['mpwpb_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['mpwpb_nonce'])), 'mpwpb_nonce')) {
					wp_send_json_error(array('message' => esc_html__('The service form expired. Please refresh the page and try again.', 'service-booking-manager')), 403);
				}

				$save_mode = isset($_POST['save_mode']) ? sanitize_key(wp_unslash($_POST['save_mode'])) : 'primary';
				if ($save_mode === 'draft') {
					$_POST['saveasdraft'] = '1';
					unset($_POST['publish']);
				} else {
					unset($_POST['saveasdraft']);
					if (in_array($post->post_status, array('publish', 'private', 'future'), true)) {
						unset($_POST['publish']);
					} else {
						$_POST['publish'] = '1';
					}
				}
				$post_data = $_POST;
				unset($post_data['action'], $post_data['save_mode']);

				require_once ABSPATH . 'wp-admin/includes/post.php';
				$saved_id = edit_post($post_data);
				$saved_post = get_post($saved_id);
				if (!$saved_post) {
					wp_send_json_error(array('message' => esc_html__('The service could not be saved.', 'service-booking-manager')), 500);
				}

				$is_published = in_array($saved_post->post_status, array('publish', 'private', 'future'), true);
				wp_send_json_success(array(
					'message' => esc_html__('Saved', 'service-booking-manager'),
					'postId' => (int) $saved_id,
					'postStatus' => sanitize_key($saved_post->post_status),
					'isPublished' => $is_published,
					'statusLabel' => $is_published ? esc_html__('Published', 'service-booking-manager') : esc_html__('Draft', 'service-booking-manager'),
					'primaryLabel' => $is_published ? esc_html__('Update', 'service-booking-manager') : esc_html__('Publish', 'service-booking-manager'),
					'draftLabel' => $is_published ? esc_html__('Switch to Draft', 'service-booking-manager') : esc_html__('Save Draft', 'service-booking-manager'),
					'editUrl' => get_edit_post_link($saved_id, 'raw'),
					'postNonce' => wp_create_nonce('update-post_' . $saved_id),
					'formNonce' => wp_create_nonce('mpwpb_nonce'),
					'editLock' => (string) get_post_meta($saved_id, '_edit_lock', true),
					'postRevision' => (string) $saved_post->post_modified_gmt,
				));
			}

			/* ------------------------------------------------------------------ *
			 *  Assets & body class
			 * ------------------------------------------------------------------ */

			public function body_class($classes) {
				if ($this->is_service_edit_screen() && $this->is_modern()) {
					$classes .= ' mpwpb-sme-active';
				}
				return $classes;
			}

			/** Cache-bust on file change so edits show without a manual hard-refresh. */
			private function asset_ver($rel_path) {
				$file = MPWPB_PLUGIN_DIR . $rel_path;
				return file_exists($file) ? (string) filemtime($file) : '1.0.0';
			}

			public function enqueue() {
				if (!$this->is_service_edit_screen()) {
					return;
				}
				$editor_post_id = isset($_GET['post']) ? absint(wp_unslash($_GET['post'])) : 0;
				if (!$editor_post_id && isset($GLOBALS['post']->ID)) {
					$editor_post_id = absint($GLOBALS['post']->ID);
				}
				// The switcher button (classic mode) needs the tiny AJAX handler too.
				wp_enqueue_script('mpwpb-service-edit-modern', MPWPB_PLUGIN_URL . '/assets/admin/mpwpb-service-edit-modern.js', array('jquery'), $this->asset_ver('/assets/admin/mpwpb-service-edit-modern.js'), true);
				wp_localize_script(
					'mpwpb-service-edit-modern',
					'mpwpbSme',
					array(
						'ajax' => admin_url('admin-ajax.php'),
						'nonce' => wp_create_nonce('mpwpb_sme_ui'),
						'listUrl' => admin_url('edit.php?post_type=' . MPWPB_Function::get_cpt() . '&page=mpwpb_service_list'),
						'postId' => $editor_post_id,
						'userId' => get_current_user_id(),
						'postRevision' => $editor_post_id ? (string) get_post_field('post_modified_gmt', $editor_post_id) : '',
						'savedTxt' => esc_html__('Saved', 'service-booking-manager'),
						'savingTxt' => esc_html__('Saving…', 'service-booking-manager'),
						'saveErrorTxt' => esc_html__('The service could not be saved. Please try again.', 'service-booking-manager'),
						'themeColorSavedTxt' => esc_html__('Theme color updated', 'service-booking-manager'),
						'themeColorErrorTxt' => esc_html__('Could not save theme color', 'service-booking-manager'),
						'localReadyTxt' => esc_html__('Local autosave ready', 'service-booking-manager'),
						'localSavingTxt' => esc_html__('Saving locally…', 'service-booking-manager'),
						'localSavedTxt' => esc_html__('Draft saved locally', 'service-booking-manager'),
						'localRestoredTxt' => esc_html__('Local draft restored', 'service-booking-manager'),
						'localUnavailableTxt' => esc_html__('Local autosave unavailable', 'service-booking-manager'),
						'nextTxt' => esc_html__('Next Step', 'service-booking-manager'),
						/* translators: %s: next step name */
						'nextToTxt' => esc_html__('Next: %s', 'service-booking-manager'),
						/* translators: 1: current step number, 2: total steps */
						'stepOfTxt' => esc_html__('Step %1$d of %2$d', 'service-booking-manager'),
						'updateTxt' => esc_html__('Update', 'service-booking-manager'),
						'previewCurrency' => MPWPB_Global_Function::is_wc_payment_mode()
							? get_woocommerce_currency_symbol()
							: MPWPB_Global_Function::native_currency_setting('symbol', '$'),
						'previewAddTxt' => esc_html__('Add', 'service-booking-manager'),
						'previewNoServicesTxt' => esc_html__('Add a service in Services & Pricing to see it appear here.', 'service-booking-manager'),
						'previewStep2HeadingTxt' => esc_html__('Choose date & time', 'service-booking-manager'),
						'previewStep2SubTxt' => esc_html__('Customers pick a slot here, then continue to checkout.', 'service-booking-manager'),
						'previewTodayTxt' => esc_html__('Today', 'service-booking-manager'),
						'previewTomorrowTxt' => esc_html__('Tomorrow', 'service-booking-manager'),
						'previewContinueTxt' => esc_html__('Continue to Checkout', 'service-booking-manager'),
						'previewOnlyTxt' => esc_html__('Preview only — real bookings continue on the live page.', 'service-booking-manager'),
						'previewCheckoutHeadingTxt' => esc_html__('Review & confirm', 'service-booking-manager'),
						'previewCheckoutSubTxt' => esc_html__('Customers enter their contact details here before confirming.', 'service-booking-manager'),
						'previewDateTimeLabelTxt' => esc_html__('Date & Time', 'service-booking-manager'),
						'previewDateLabelTxt' => esc_html__('Date', 'service-booking-manager'),
						'previewTimeLabelTxt' => esc_html__('Time', 'service-booking-manager'),
						'previewMoreDatesTxt' => esc_html__('See more dates', 'service-booking-manager'),
						'previewTotalTxt' => esc_html__('Total', 'service-booking-manager'),
						'previewConfirmTxt' => esc_html__('Confirm Booking', 'service-booking-manager'),
						'showOnPageTxt' => esc_html__('Show on page', 'service-booking-manager'),
						'reviewRatingTxt' => esc_html__('Rating', 'service-booking-manager'),
						'reviewRatingHintTxt' => esc_html__('From 0 to 5, one decimal (e.g. 4.5).', 'service-booking-manager'),
						'reviewScaleTxt' => esc_html__('Scale text', 'service-booking-manager'),
						'reviewCountTxt' => esc_html__('Review count text', 'service-booking-manager'),
						'reviewCountPlaceholderTxt' => esc_html__('e.g. 120 customer reviews', 'service-booking-manager'),
						'reviewHideHintTxt' => esc_html__('Leave the rating empty to hide the badge.', 'service-booking-manager'),
						'reviewPreviewTxt' => esc_html__('Preview', 'service-booking-manager'),
						'reviewNoneTxt' => esc_html__('No rating set, so the badge is hidden.', 'service-booking-manager'),
						'reviewLowerTxt' => esc_html__('Lower rating', 'service-booking-manager'),
						'reviewRaiseTxt' => esc_html__('Raise rating', 'service-booking-manager'),
						'descriptionOffTxt' => esc_html__('The full description is hidden on the service page. Your text is kept; switch it on to show it again.', 'service-booking-manager'),
						'previewNextStaffTxt' => esc_html__('Next: Staff Member', 'service-booking-manager'),
						'previewStaffHeadingTxt' => esc_html__('Choose a staff member', 'service-booking-manager'),
						'previewStaffSubTxt' => esc_html__('Customers pick who they want, or let you assign anyone free.', 'service-booking-manager'),
						'previewAnyStaffTxt' => esc_html__('Any Staff', 'service-booking-manager'),
						'previewNoStaffTxt' => esc_html__('No staff selected yet. Pick staff in Staff Member settings.', 'service-booking-manager'),
						'previewStaffLabelTxt' => esc_html__('Staff', 'service-booking-manager'),
						'previewRecurTitleTxt' => esc_html__('Recurring booking', 'service-booking-manager'),
						'previewRecurSubTxt' => esc_html__('Book this same time again on a regular schedule.', 'service-booking-manager'),
						'previewRecurCountTxt' => esc_html__('Number of occurrences', 'service-booking-manager'),
						'previewRecurBookingsTxt' => esc_html__('bookings', 'service-booking-manager'),
						'previewRecurLabelTxt' => esc_html__('Recurring', 'service-booking-manager'),
						'previewRecurDiscountTxt' => esc_html__('Recurring discount', 'service-booking-manager'),
						'previewRecurTypes' => array(
							'daily' => esc_html__('Daily', 'service-booking-manager'),
							'weekly' => esc_html__('Weekly', 'service-booking-manager'),
							'bi-weekly' => esc_html__('Bi-weekly', 'service-booking-manager'),
							'monthly' => esc_html__('Monthly', 'service-booking-manager'),
						),
						'previewNoDurationTxt' => esc_html__('No duration set', 'service-booking-manager'),
						'previewAddLabel' => esc_html__('Add', 'service-booking-manager'),
						'previewNothingTxt' => esc_html__('Nothing selected yet', 'service-booking-manager'),
						'previewOneSelected' => esc_html__('1 service selected', 'service-booking-manager'),
						/* translators: %d: number of services */
						'previewManySelected' => esc_html__('%d services selected', 'service-booking-manager'),
						/* translators: %d: number of services added from this group */
						'previewGroupAdded' => esc_html__('%d added', 'service-booking-manager'),
						'previewStep1Label' => esc_html__('Services', 'service-booking-manager'),
						'previewStep2Label' => esc_html__('Date & Time', 'service-booking-manager'),
						'hhWindowTitle' => esc_html__('Time window', 'service-booking-manager'),
						'hhWindowSub' => esc_html__('Applies to every open day, based on the booking\'s start time.', 'service-booking-manager'),
						'hhStartLabel' => esc_html__('Starts at', 'service-booking-manager'),
						'hhEndLabel' => esc_html__('Ends at', 'service-booking-manager'),
						'hhEarlierTxt' => esc_html__('30 minutes earlier', 'service-booking-manager'),
						'hhLaterTxt' => esc_html__('30 minutes later', 'service-booking-manager'),
						/* translators: %s: length of the window, e.g. "2 h" or "30 min" */
						'hhWindowLen' => esc_html__('%s window', 'service-booking-manager'),
						'hhHoursUnit' => esc_html__('h', 'service-booking-manager'),
						'hhMinsUnit' => esc_html__('min', 'service-booking-manager'),
						'hhWindowInvalid' => esc_html__('Invalid window', 'service-booking-manager'),
						'hhRangeError' => esc_html__('End time must be later than the start time.', 'service-booking-manager'),
						'hhAmTxt' => esc_html__('AM', 'service-booking-manager'),
						'hhPmTxt' => esc_html__('PM', 'service-booking-manager'),
						'hhOfferTitle' => esc_html__('Discount', 'service-booking-manager'),
						'hhOfferSub' => esc_html__('Taken off the service price for bookings in this window.', 'service-booking-manager'),
						'hhTypeLabel' => esc_html__('Discount type', 'service-booking-manager'),
						'hhPercentTxt' => esc_html__('Percentage', 'service-booking-manager'),
						'hhFixedTxt' => esc_html__('Fixed amount', 'service-booking-manager'),
						'hhValueLabel' => esc_html__('Discount value', 'service-booking-manager'),
						'hhValueEmpty' => esc_html__('Enter a discount greater than 0.', 'service-booking-manager'),
						'hhValueOver' => esc_html__('A percentage discount can\'t be more than 100%.', 'service-booking-manager'),
						'hhSeeLabel' => esc_html__('Customers see:', 'service-booking-manager'),
						/* translators: 1: start time, 2: end time, 3: discount, e.g. "20% off" */
						'hhSummary' => esc_html__('bookings starting between %1$s and %2$s get %3$s, shown as a "%3$s" badge on those time slots.', 'service-booking-manager'),
						'hhSummaryInvalid' => esc_html__('nothing yet. Fix the highlighted fields to turn this on.', 'service-booking-manager'),
						/* translators: %s: discount amount, e.g. "20%" or "$15" */
						'hhOffTxt' => esc_html__('%s off', 'service-booking-manager'),
						'extraOffNote' => esc_html__('Extra services are off. Customers won\'t see add-ons when booking; your list is kept.', 'service-booking-manager'),
						'hhOffNote' => esc_html__('Happy hours pricing is off. Every booking uses the regular price.', 'service-booking-manager'),
						'hhProNote' => esc_html__('Happy hours pricing needs Service Booking Manager Pro to be installed and active.', 'service-booking-manager'),
						'hhCurrencySymbol' => function_exists('get_woocommerce_currency_symbol') ? html_entity_decode(get_woocommerce_currency_symbol(), ENT_QUOTES, 'UTF-8') : '',
						'recurPatternTitle' => esc_html__('Repeat Pattern', 'service-booking-manager'),
						'recurPatternSub' => esc_html__('Choose the recurrence options customers can use.', 'service-booking-manager'),
						'recurLimitsTitle' => esc_html__('Limits & Incentive', 'service-booking-manager'),
						'recurLimitsSub' => esc_html__('Control the maximum series length and optional discount.', 'service-booking-manager'),
						'recurRuleLabel' => esc_html__('Booking series', 'service-booking-manager'),
						'recurOccurrencesTxt' => esc_html__('occurrences maximum', 'service-booking-manager'),
						'recurDiscountTxt' => esc_html__('recurring discount', 'service-booking-manager'),
						'recurNoneTxt' => esc_html__('Select a repeat pattern', 'service-booking-manager'),
						'recurOptionsTitle' => esc_html__('Repeat options', 'service-booking-manager'),
						'recurOptionsSub' => esc_html__('Pick the patterns customers can choose from.', 'service-booking-manager'),
						'recurOptionsOn' => esc_html__('%1$d of %2$d on', 'service-booking-manager'),
						'recurOptionsNone' => esc_html__('Pick at least one option, or customers won\'t be able to repeat a booking.', 'service-booking-manager'),
						'recurHintDaily' => esc_html__('Same time every day', 'service-booking-manager'),
						'recurHintWeekly' => esc_html__('Same weekday each week', 'service-booking-manager'),
						'recurHintBiweekly' => esc_html__('Every other week', 'service-booking-manager'),
						'recurHintMonthly' => esc_html__('Same date each month', 'service-booking-manager'),
						'recurMaxTitle' => esc_html__('Maximum bookings in a series', 'service-booking-manager'),
						'recurMaxSub' => esc_html__('Between 2 and 52.', 'service-booking-manager'),
						'recurMaxUnit' => esc_html__('bookings', 'service-booking-manager'),
						'recurDiscTitle' => esc_html__('Series discount', 'service-booking-manager'),
						'recurDiscSub' => esc_html__('Optional reward for booking a series.', 'service-booking-manager'),
						'recurDiscNone' => esc_html__('None', 'service-booking-manager'),
						'recurSeeLabel' => esc_html__('Customers see:', 'service-booking-manager'),
						'recurSeeText' => esc_html__('repeat %1$s, up to %2$d bookings', 'service-booking-manager'),
						'recurSeeDiscount' => esc_html__(', with %d%% off the series', 'service-booking-manager'),
						'recurSeeNone' => esc_html__('no repeat options yet', 'service-booking-manager'),
						'recurOr' => esc_html__('or', 'service-booking-manager'),
						'recurOffTxt' => esc_html__('Recurring booking is off. Customers book one appointment at a time.', 'service-booking-manager'),
						'recurFewerTxt' => esc_html__('Fewer', 'service-booking-manager'),
						'recurMoreTxt' => esc_html__('More', 'service-booking-manager'),
						'faqListTitle' => esc_html__('Questions', 'service-booking-manager'),
						'faqCountOne' => esc_html__('1 question', 'service-booking-manager'),
						/* translators: %d: number of questions */
						'faqCountMany' => esc_html__('%d questions', 'service-booking-manager'),
						'faqDragHint' => esc_html__('Drag to reorder. Customers see them in this order.', 'service-booking-manager'),
						'faqAddTxt' => esc_html__('Add question', 'service-booking-manager'),
						'faqEmptyTitle' => esc_html__('No questions yet', 'service-booking-manager'),
						'faqEmptyText' => esc_html__('Add the questions customers ask most, like what to bring or how to reschedule.', 'service-booking-manager'),
						'faqOffNote' => esc_html__('FAQ is hidden on the service page. Your questions are kept.', 'service-booking-manager'),
						'faqDragTxt' => esc_html__('Drag to reorder', 'service-booking-manager'),
						'faqEditTxt' => esc_html__('Edit question', 'service-booking-manager'),
						'faqDeleteTxt' => esc_html__('Delete question', 'service-booking-manager'),
						'faqModalAdd' => esc_html__('Add question', 'service-booking-manager'),
						'faqModalEdit' => esc_html__('Edit question', 'service-booking-manager'),
						'faqModalSub' => esc_html__('Shown as an expandable question on the service page.', 'service-booking-manager'),
						'faqQuestionLabel' => esc_html__('Question', 'service-booking-manager'),
						'faqQuestionPh' => esc_html__('e.g. What should I bring to my appointment?', 'service-booking-manager'),
						'faqQuestionError' => esc_html__('Enter the question customers will see.', 'service-booking-manager'),
						'faqAnswerLabel' => esc_html__('Answer', 'service-booking-manager'),
						'faqSaveAnother' => esc_html__('Save & add another', 'service-booking-manager'),
						'faqSaveTxt' => esc_html__('Save question', 'service-booking-manager'),
						'faqSaveChanges' => esc_html__('Save changes', 'service-booking-manager'),
						'faqCancelTxt' => esc_html__('Cancel', 'service-booking-manager'),
						'taxClassTitle' => esc_html__('Tax class', 'service-booking-manager'),
						'taxClassSub' => esc_html__('Services in the same class share one rate.', 'service-booking-manager'),
						'taxClassSubOwn' => esc_html__('Pick the class this service is taxed under.', 'service-booking-manager'),
						/* translators: %s: rate, e.g. "8.5%" */
						'taxClassRate' => esc_html__('%s rate', 'service-booking-manager'),
						'taxClassNoRate' => esc_html__('No rate set yet', 'service-booking-manager'),
						/* translators: %s: tax class name, e.g. "Standard" */
						'taxRateTitle' => esc_html__('Tax rate (%s)', 'service-booking-manager'),
						'taxRateSub' => esc_html__('Between 0 and 100. Use up to 4 decimals, e.g. 8.875.', 'service-booking-manager'),
						'taxRateError' => esc_html__('Enter a rate between 0 and 100.', 'service-booking-manager'),
						/* translators: %s: tax class name */
						'taxSharedNote' => esc_html__('This rate is shared. Changing it also updates every other service in the %s class.', 'service-booking-manager'),
						'taxSharedOne' => esc_html__('1 other service uses it.', 'service-booking-manager'),
						/* translators: %d: number of services */
						'taxSharedMany' => esc_html__('%d other services use it.', 'service-booking-manager'),
						'taxSeeLabel' => esc_html__('Customers see:', 'service-booking-manager'),
						/* translators: 1: rate, 2: example price, 3: price with tax, 4: tax amount */
						'taxSummary' => esc_html__('%1$s tax added at checkout. On a %2$s booking they pay %3$s (%4$s tax).', 'service-booking-manager'),
						/* translators: 1: rate, 2: example price, 3: tax amount included */
						'taxSummaryIncl' => esc_html__('prices already include %1$s tax. On a %2$s booking, %3$s of it is tax.', 'service-booking-manager'),
						/* translators: %s: tax class name */
						'taxSummaryZero' => esc_html__('no tax added (%s, 0%%).', 'service-booking-manager'),
						'taxSummaryInvalid' => esc_html__('nothing yet. Fix the rate to turn this on.', 'service-booking-manager'),
						'taxOffNote' => esc_html__('Tax is off. Customers pay the service price with no tax added.', 'service-booking-manager'),
						'taxDecimals' => function_exists('wc_get_price_decimals') ? wc_get_price_decimals() : 2,
						'staffSelectTitle' => esc_html__('Select Staff', 'service-booking-manager'),
						'staffSelectSub' => esc_html__('Choose the team members customers can book.', 'service-booking-manager'),
						'staffSelectedTxt' => esc_html__('staff selected', 'service-booking-manager'),
						'staffNoneTxt' => esc_html__('No staff members available.', 'service-booking-manager'),
						'staffWhoTitle' => esc_html__('Who can be booked', 'service-booking-manager'),
						'staffCountTxt' => esc_html__('%1$d of %2$d selected', 'service-booking-manager'),
						'staffSelectAllTxt' => esc_html__('Select all', 'service-booking-manager'),
						'staffClearTxt' => esc_html__('Clear', 'service-booking-manager'),
						'staffManageTxt' => esc_html__('Manage staff', 'service-booking-manager'),
						'staffAddTxt' => esc_html__('Add staff member', 'service-booking-manager'),
						'staffOnTxt' => esc_html__('Can be booked', 'service-booking-manager'),
						'staffOffTxt' => esc_html__('Not offered', 'service-booking-manager'),
						'staffPickWarn' => esc_html__('Pick at least one staff member, or customers will have no one to book with.', 'service-booking-manager'),
						'staffEmptyTxt' => esc_html__('No staff members yet. Add your team first, then pick who can be booked here.', 'service-booking-manager'),
						'staffSeeLabel' => esc_html__('Customers see:', 'service-booking-manager'),
						'staffSeeText' => esc_html__('"Any Staff" or %s.', 'service-booking-manager'),
						'staffSeeNone' => esc_html__('no one to book with yet.', 'service-booking-manager'),
						'staffOr' => esc_html__('or', 'service-booking-manager'),
						'staffOffNote' => esc_html__('Staff choice is off. Customers book the service without picking a person.', 'service-booking-manager'),
						'staffManageUrl' => esc_url_raw(admin_url('edit.php?post_type=' . MPWPB_Function::get_cpt() . '&page=mpwpb_staffs')),
						'featTitle' => esc_html__('Select featured image', 'service-booking-manager'),
						'featBtn' => esc_html__('Use image', 'service-booking-manager'),
						'featSet' => esc_html__('Featured image set', 'service-booking-manager'),
						'featRemoved' => esc_html__('Featured image removed', 'service-booking-manager'),
						'paymentNonce' => wp_create_nonce('mpwpb_save_payment_method_settings'),
						'paymentSaved' => esc_html__('Payment settings saved.', 'service-booking-manager'),
						'paymentError' => esc_html__('Something went wrong.', 'service-booking-manager'),
					)
				);

				if ($this->is_modern()) {
					wp_enqueue_style('mpwpb-sme-fonts', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap', array(), null);
					wp_enqueue_style('mpwpb-service-edit-modern', MPWPB_PLUGIN_URL . '/assets/admin/mpwpb-service-edit-modern.css', array(), $this->asset_ver('/assets/admin/mpwpb-service-edit-modern.css'));

					// Categories & Services sidebar/list UI (Pricing step). Depends on
					// window.mpwpb_admin_ajax (localized unconditionally by
					// inc/MPWPB_Dependencies.php on every admin page) for its ajax
					// url/nonce, and on the mpwpbCsm payload localized by
					// MPWPB_Categories_Services_Modern::render() when that step's card
					// actually renders — both are available by the time this script runs.
					wp_enqueue_style('mpwpb-categories-services-modern', MPWPB_PLUGIN_URL . '/assets/admin/mpwpb-categories-services-modern.css', array('mpwpb-service-edit-modern'), $this->asset_ver('/assets/admin/mpwpb-categories-services-modern.css'));
					// Static "quick add" service-template catalogue (window.mpwpbServiceTemplates)
					// used by the "Use Template" picker below -- pure data, kept in its own
					// file/handle so it stays easy to extend without touching the behaviour file.
					wp_enqueue_script('mpwpb-service-templates-data', MPWPB_PLUGIN_URL . '/assets/admin/mpwpb-service-templates-data.js', array(), $this->asset_ver('/assets/admin/mpwpb-service-templates-data.js'), true);
					wp_enqueue_script('mpwpb-categories-services-modern', MPWPB_PLUGIN_URL . '/assets/admin/mpwpb-categories-services-modern.js', array('jquery', 'mpwpb-service-templates-data'), $this->asset_ver('/assets/admin/mpwpb-categories-services-modern.js'), true);

					// Extra Service list (Pricing step). Visually reuses .mpwpb-csm__*
					// classes from the stylesheet above (declared as a dependency)
					// rather than duplicating near-identical list/row/modal styles.
					wp_enqueue_style('mpwpb-extra-service-modern', MPWPB_PLUGIN_URL . '/assets/admin/mpwpb-extra-service-modern.css', array('mpwpb-categories-services-modern'), $this->asset_ver('/assets/admin/mpwpb-extra-service-modern.css'));
					wp_enqueue_script('mpwpb-extra-service-modern', MPWPB_PLUGIN_URL . '/assets/admin/mpwpb-extra-service-modern.js', array('jquery'), $this->asset_ver('/assets/admin/mpwpb-extra-service-modern.js'), true);

					// Date & Time card layout (Availability step). Self-contained;
					// only needs the shell's CSS custom properties (--brand, --line,
					// --ink) from mpwpb-service-edit-modern.css.
					wp_enqueue_style('mpwpb-datetime-modern', MPWPB_PLUGIN_URL . '/assets/admin/mpwpb-datetime-modern.css', array('mpwpb-service-edit-modern'), $this->asset_ver('/assets/admin/mpwpb-datetime-modern.css'));
					wp_enqueue_script('mpwpb-datetime-modern', MPWPB_PLUGIN_URL . '/assets/admin/mpwpb-datetime-modern.js', array('jquery'), $this->asset_ver('/assets/admin/mpwpb-datetime-modern.js'), true);
					wp_localize_script('mpwpb-datetime-modern', 'mpwpbDtm', array(
						'dayOpenLabel' => esc_html__('day open', 'service-booking-manager'),
						'daysOpenLabel2' => esc_html__('days open', 'service-booking-manager'),
						'mondayTxt' => esc_html__('Monday', 'service-booking-manager'),
						'mondayClosedTxt' => esc_html__('Monday is closed', 'service-booking-manager'),
						'noSlotsTxt' => esc_html__('No slots available', 'service-booking-manager'),
						'slotsOfTxt' => esc_html__('slots of', 'service-booking-manager'),
						'addBreakTxt' => esc_html__('+ Add break', 'service-booking-manager'),
						'editBreakTxt' => esc_html__('Edit break', 'service-booking-manager'),
						'removeBreakTxt' => esc_html__('Remove break', 'service-booking-manager'),
						'specialDateLabel' => esc_html__('date', 'service-booking-manager'),
						'specialDatesLabel' => esc_html__('dates', 'service-booking-manager'),
						'weeklyClosedTxt' => esc_html__('Closed every week', 'service-booking-manager'),
						'legendCustomTxt' => esc_html__('Closed (custom)', 'service-booking-manager'),
						'legendWeeklyTxt' => esc_html__('Closed weekly', 'service-booking-manager'),
						'legendTodayTxt' => esc_html__('Today', 'service-booking-manager'),
						'offListEmptyTxt' => esc_html__('No dates marked closed yet.', 'service-booking-manager'),
						'removeDateTxt' => esc_html__('Remove', 'service-booking-manager'),
						'usuallyTxt' => esc_html__('usually', 'service-booking-manager'),
						'defaultHoursTxt' => esc_html__('Default hours', 'service-booking-manager'),
						'defaultHoursSubTxt' => esc_html__('Used by every open day', 'service-booking-manager'),
						'customHoursTxt' => esc_html__('Custom hours', 'service-booking-manager'),
						'notBookableTxt' => esc_html__('Not bookable', 'service-booking-manager'),
						'closedTxt' => esc_html__('Closed', 'service-booking-manager'),
						'changeTxt' => esc_html__('Change', 'service-booking-manager'),
						'useDefaultTxt' => esc_html__('Use default hours', 'service-booking-manager'),
						'defaultBreakTxt' => esc_html__('Default break', 'service-booking-manager'),
						'noBreakTxt' => esc_html__('No break', 'service-booking-manager'),
					));

					// FAQ (Advanced step). Pure CSS reskin of the classic accordion —
					// no new JS/PHP; the classic AJAX handlers, TinyMCE-backed modal
					// and jQuery UI sortable are reused completely unchanged.
					wp_enqueue_style('mpwpb-faq-modern', MPWPB_PLUGIN_URL . '/assets/admin/mpwpb-faq-modern.css', array('mpwpb-service-edit-modern'), $this->asset_ver('/assets/admin/mpwpb-faq-modern.css'));
				}
			}
		}

		new MPWPB_Settings_Modern();
	}
