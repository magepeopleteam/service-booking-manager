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
							array('MPWPB_General_Settings', 'general_settings', __('Customer Reviews', 'service-booking-manager'), __('Rating, scale and review count shown for this service.', 'service-booking-manager')),
							array('MPWPB_Service_Features_Modern', 'render', __('Service Feature Details', 'service-booking-manager'), __('Highlight key features of this service.', 'service-booking-manager')),
							array('MPWPB_Service_Details', 'service_details', __('Service Details', 'service-booking-manager'), __('Overview and details content shown on the service page.', 'service-booking-manager')),
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
							array('MPWPB_Faq_Settings', 'faq_settings', __('FAQ', 'service-booking-manager'), __('Frequently asked questions shown on the service page.', 'service-booking-manager')),
							array('Tax_Settings', 'tax_settings', __('Tax Settings', 'service-booking-manager'), __('Charge tax on this service using your WooCommerce tax settings.', 'service-booking-manager')),
						),
					),
				);
				// Same Pro gate the classic tab nav uses (MPWPB_Settings.php / MPWPB_Extended_Settings.php) —
				// these render methods have no internal Pro guard of their own.
				if (MPWPB_Global_Function::is_pro_active()) {
					$steps[3]['sections'][] = array('Staff_Member', 'staff_member_settings', __('Staff Member', 'service-booking-manager'), __('Assign staff members who can be booked for this service.', 'service-booking-manager'));
					$steps[3]['sections'][] = array('MPWPB_Recurring_Booking_Settings', 'recurring_booking_settings', __('Recurring Booking', 'service-booking-manager'), __('Let customers book this service on a recurring schedule.', 'service-booking-manager'));
				}
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
						<a class="mpwpb-sme__back" href="<?php echo esc_url($list_url); ?>">
							<span class="dashicons dashicons-arrow-left-alt2"></span>
							<?php echo esc_html(sprintf(__('Back to %s', 'service-booking-manager'), MPWPB_Function::get_name())); ?>
						</a>
						<input type="text" class="mpwpb-sme__ttl mpwpb-sme__ttl-input" id="mpwpb-sme-title" value="<?php echo esc_attr($service_title); ?>" placeholder="<?php esc_attr_e('Service name', 'service-booking-manager'); ?>" aria-label="<?php esc_attr_e('Service name', 'service-booking-manager'); ?>"/>
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

					<div class="mpwpb-sme__stepper">
						<?php foreach ($steps as $i => $step) : ?>
							<?php if ($i > 0) : ?>
								<div class="mpwpb-sme__conn" data-sme-conn="<?php echo esc_attr($i); ?>"></div>
							<?php endif; ?>
							<div class="mpwpb-sme__step<?php echo $i === 0 ? ' active' : ''; ?>" data-sme-go="<?php echo esc_attr($step['id']); ?>" data-sme-index="<?php echo esc_attr($i); ?>">
								<div class="mpwpb-sme__num"><?php echo esc_html($i + 1); ?></div>
								<div class="mpwpb-sme__lab"><?php echo esc_html($step['label']); ?></div>
							</div>
						<?php endforeach; ?>
					</div>

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

					<div class="mpwpb-sme__navbar">
						<div class="mpwpb-sme__navinner">
							<button type="button" class="mpwpb-sme__btn mpwpb-sme__nav-back" data-sme-prev disabled><?php esc_html_e('Back', 'service-booking-manager'); ?></button>
							<div class="mpwpb-sme__stepof" data-sme-stepof><?php echo esc_html(sprintf(__('Step %1$d of %2$d', 'service-booking-manager'), 1, $total)); ?></div>
							<button type="button" class="mpwpb-sme__btn mpwpb-sme__btn--primary" data-sme-next><?php esc_html_e('Next Step', 'service-booking-manager'); ?></button>
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
					<div class="mpwpb-sme__rail-card mpwpb-sme__feat-card mpwpb-sme__rail-card--loading">
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
							<div class="mpwpb-sme__gallery-head-text">
								<div class="mpwpb-sme__feat-head"><?php esc_html_e('Gallery Images', 'service-booking-manager'); ?></div>
								<p class="mpwpb-sme__gallery-sub"><?php esc_html_e('Additional photos.', 'service-booking-manager'); ?></p>
							</div>
							<?php MPWPB_Custom_Layout::switch_button('mpwpb_display_slider', $gallery_checked); ?>
						</div>
						<div class="mpwpb-sme__gallery-body <?php echo esc_attr($gallery_active_class); ?>" data-collapse="#mpwpb_display_slider">
							<?php do_action('mpwpb_add_multi_image', 'mpwpb_slider_images', $gallery_image_ids); ?>
						</div>
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
					<div class="mpwpb-sme__rail-card mpwpb-sme__rail-card--loading">
						<?php $this->render_rail_card_skeleton('info'); ?>
						<div class="mpwpb-sme__feat-head"><?php esc_html_e('Payment Method', 'service-booking-manager'); ?></div>
						<div class="mpwpb-sme__rail-info-list">
							<div class="mpwpb-sme__rail-info-row">
								<span><?php esc_html_e('Active Method', 'service-booking-manager'); ?></span>
								<strong><?php echo esc_html($mpwpb_pm_type_label); ?></strong>
							</div>
							<div class="mpwpb-sme__rail-info-row">
								<span><?php esc_html_e('Active Gateway', 'service-booking-manager'); ?></span>
								<strong><?php echo esc_html($mpwpb_pm_gateway_names ? implode(', ', $mpwpb_pm_gateway_names) : __('None', 'service-booking-manager')); ?></strong>
							</div>
							<?php if ($mpwpb_pm_gateway_names) : ?>
								<p class="mpwpb-sme__rail-payment-link">
									<a href="#" data-sme-payment-modal-open><?php esc_html_e('Payment Settings', 'service-booking-manager'); ?></a>
								</p>
							<?php endif; ?>
							<?php if (!$mpwpb_pm_active) : ?>
								<p class="mpwpb-sme__rail-payment-warning">
									<a href="#" data-sme-payment-modal-open><?php esc_html_e('Configure payment method', 'service-booking-manager'); ?></a>
								</p>
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
					<div class="mpwpb-sme__rail-card mpwpb-sme__rail-card--loading">
						<?php $this->render_rail_card_skeleton('info'); ?>
						<div class="mpwpb-sme__feat-head"><?php esc_html_e('Shortcode &amp; Template', 'service-booking-manager'); ?></div>
						<div class="mpwpb-sme__rail-field-list">
							<div class="mpwpb-sme__field-slot" data-sme-shortcode-slot></div>
							<div class="mpwpb-sme__field-slot" data-sme-template-slot></div>
						</div>
					</div>

					<div class="mpwpb-sme__rail-card mpwpb-sme__rail-card--loading" data-sme-step-only="availability pricing advanced">
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

					<?php
					$services = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_service', array());
					$total_services = is_array($services) ? count($services) : 0;
					$extra_services = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_extra_service', array());
					$total_extra_services = is_array($extra_services) ? count($extra_services) : 0;
					$extra_service_on = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_extra_service_active') === 'on';
					?>
					<div class="mpwpb-sme__rail-card mpwpb-sme__rail-card--loading" data-sme-step-only="availability advanced">
						<?php $this->render_rail_card_skeleton('info'); ?>
						<div class="mpwpb-sme__feat-head"><?php esc_html_e('Pricing Summary', 'service-booking-manager'); ?></div>
						<div class="mpwpb-sme__rail-info-list">
							<div class="mpwpb-sme__rail-info-row">
								<span><?php esc_html_e('Services', 'service-booking-manager'); ?></span>
								<strong><?php echo esc_html($total_services); ?></strong>
							</div>
							<div class="mpwpb-sme__rail-info-row">
								<span><?php esc_html_e('Extra Services', 'service-booking-manager'); ?></span>
								<strong><?php echo esc_html($total_extra_services); ?></strong>
							</div>
							<div class="mpwpb-sme__rail-info-row">
								<span><?php esc_html_e('Extra Service Enabled', 'service-booking-manager'); ?></span>
								<span class="mpwpb-sme__rail-pill<?php echo $extra_service_on ? ' is-on' : ' is-off'; ?>"><?php echo $extra_service_on ? esc_html__('On', 'service-booking-manager') : esc_html__('Off', 'service-booking-manager'); ?></span>
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
				?>
				<div class="mpwpb-sme__postfields" data-sme-section="<?php echo esc_attr($class); ?>">
					<div class="mpwpb-sme__postfields-header">
						<div class="mpwpb-sme__postfields-header-text">
							<div class="mpwpb-sme__postfields-header-title"><?php echo esc_html($title); ?></div>
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
				?>
				<div class="mpwpb-sme__postfields basic-Information" data-sme-postfields>
					<div class="mpwpb-sme__postfields-header">
						<div class="mpwpb-sme__postfields-header-title"><?php esc_html_e('Basic Information', 'service-booking-manager'); ?></div>
						<div class="mpwpb-sme__postfields-header-sub"><?php esc_html_e('The core details of your service.', 'service-booking-manager'); ?></div>
					</div>
					<div class="mpwpb-sme__postfields-body">
						<div class="mpwpb-sme__subsection-label">
							<label><?php esc_html_e('Title', 'service-booking-manager'); ?></label>
						</div>
						<input type="text" class="formControl" id="mpwpb-sme-title-inline" value="<?php echo esc_attr($title); ?>" placeholder="<?php esc_attr_e('Service name', 'service-booking-manager'); ?>"/>

						<?php // Service Title isn't shown in the modern editor — its existing value is preserved unchanged on save (Classic mode's own field is untouched and still fully editable there). ?>
						<input type="hidden" name="mpwpb_shortcode_title" value="<?php echo esc_attr(MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_shortcode_title')); ?>"/>

						<div class="mpwpb-sme__subsection-label">
							<label><?php esc_html_e('Service Sub Title', 'service-booking-manager'); ?></label>
						</div>
						<div class="mpwpb-sme__field-slot" data-sme-service-subtitle-slot></div>

						<?php // Modern editor keeps Service Overview always on — no toggle shown; Classic mode's own toggle is untouched. ?>
						<input type="hidden" name="mpwpb_service_overview_status" value="on"/>
						<div class="mpwpb-sme__subsection-label">
							<label><?php esc_html_e('Service Overview', 'service-booking-manager'); ?></label>
						</div>
						<div class="mpwpb-sme__content-slot" data-sme-overview-slot></div>
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
				// The switcher button (classic mode) needs the tiny AJAX handler too.
				wp_enqueue_script('mpwpb-service-edit-modern', MPWPB_PLUGIN_URL . '/assets/admin/mpwpb-service-edit-modern.js', array('jquery'), $this->asset_ver('/assets/admin/mpwpb-service-edit-modern.js'), true);
				wp_localize_script(
					'mpwpb-service-edit-modern',
					'mpwpbSme',
					array(
						'ajax' => admin_url('admin-ajax.php'),
						'nonce' => wp_create_nonce('mpwpb_sme_ui'),
						'listUrl' => admin_url('edit.php?post_type=' . MPWPB_Function::get_cpt() . '&page=mpwpb_service_list'),
						'savedTxt' => esc_html__('Saved', 'service-booking-manager'),
						'savingTxt' => esc_html__('Saving…', 'service-booking-manager'),
						'nextTxt' => esc_html__('Next Step', 'service-booking-manager'),
						'updateTxt' => esc_html__('Update', 'service-booking-manager'),
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
						'daysOpenLabel' => esc_html__('Days Open', 'service-booking-manager'),
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
