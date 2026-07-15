<?php
	/**
	 * @author Shahadat Hossain <raselsha@gmail.com>
	 * @version 1.0.0
	 */
	if (!defined('ABSPATH'))
		die;
	if (!class_exists('MPWPB_Static_Template')) {
		class MPWPB_Static_Template {
			public function __construct() {
				add_action('mpwpb_service_show_ratings', [$this, 'show_ratings']);
				add_action('mpwpb_service_feature_heighlight', [$this, 'features_heighlight']);
				add_action('mpwpb_service_feature_heighlight', [$this, 'popup_feature_lists']);
				add_action('mpwpb_service_nav', [$this, 'show_service_nav']);
				add_action('mpwpb_service_overview', [$this, 'show_service_overview']);
				add_action('mpwpb_service_faq', [$this, 'show_service_faq']);
				add_action('mpwpb_service_details', [$this, 'show_service_details']);
				add_action('mpwpb_service_gallery', [$this, 'show_service_gallery']);
				add_action('mpwpb_service_reviews', [$this, 'show_service_reviews']);
				add_action('mpwpb_added_staff_details', [$this, 'show_added_staff_details']);
                add_action('mpwpb_progress_bar', [$this, 'mpwpb_progress_bar_callback'], 10, 2 );
			}

            public function mpwpb_progress_bar_callback( $service_id, $is_active ) {
				$steps = [
					'mpwpb_progress_service' => esc_html__('Service', 'service-booking-manager'),
					'mpwpb_progress_date_time' => esc_html__('Date & Time', 'service-booking-manager'),
					'mpwpb_progress_billing' => esc_html__('Billing', 'service-booking-manager'),
					'mpwpb_progress_payment' => esc_html__('Payment', 'service-booking-manager'),
					'mpwpb_progress_confirmation' => esc_html__('Confirmation', 'service-booking-manager'),
				];
                ?>
				<div class="mpwpb_cart_progress_wrapper" aria-label="<?php esc_attr_e('Booking progress', 'service-booking-manager'); ?>">
					<?php foreach ($steps as $step_id => $label) { ?>
						<div class="mpwpb_cart_progress_step<?php echo $step_id === 'mpwpb_progress_service' ? ' active' : ''; ?>" id="<?php echo esc_attr($step_id); ?>">
							<div class="mpwpb_cart_progress_label"><?php echo esc_html($label); ?></div>
						</div>
					<?php } ?>
                </div>
                <?php
            }
			public function features_heighlight($limit = '') {
				$features_heightlight = MPWPB_Global_Function::get_post_info(get_the_ID(), 'mpwpb_features', []);
				$limit = $limit ? $limit : 3;
				if (!empty($features_heightlight)):
					?>
                    <ul class="features">
						<?php
							// No per-feature icon field exists on this free-text list, so a
							// small default set just cycles by position -- purely
							// decorative variety (matches the design reference showing a
							// distinct icon per item), not tied to what the admin typed.
							$default_icons = ['fas fa-check-circle', 'fas fa-leaf', 'fas fa-map-marker-alt'];
							foreach ($features_heightlight as $key => $value):
								if ($key < $limit) :
									$icon_class = $default_icons[$key % count($default_icons)];
									?>
                                    <li>
                                        <span class="mpwpb-feature-icon"><i class="<?php echo esc_attr($icon_class); ?>"></i></span>
                                        <span class="mpwpb-feature-label"><?php echo esc_html($value); ?></span>
                                    </li>
								<?php endif; ?>
							<?php endforeach; ?>
						<?php if (count($features_heightlight) > $limit): ?>
                            <h5 class="view_more" data-target-popup="#mpwpb_view_more_popup"><?php esc_html_e('View more!', 'service-booking-manager'); ?></h5>
						<?php endif; ?>
                    </ul>
				<?php
				endif;
			}
			/**
			 * Solid stars for the floor of the rating, one half-star for a
			 * >=.5 remainder, outline stars for the rest -- always 5 total.
			 */
			private static function render_star_icons(float $rating): void {
				for ($i = 1; $i <= 5; $i++) {
					if ($rating >= $i) {
						echo '<i class="fas fa-star"></i>';
					} elseif ($rating >= $i - 0.5) {
						echo '<i class="fas fa-star-half-alt"></i>';
					} else {
						echo '<i class="far fa-star"></i>';
					}
				}
			}
			public function popup_feature_lists() {
				$features_heightlight = MPWPB_Global_Function::get_post_info(get_the_ID(), 'mpwpb_features', []);
				?>
                <div class="mpwpb_popup mpwpb_style popup-features" data-popup="#mpwpb_view_more_popup">
                    <div class="mpwpb_popup_main_area">
                        <div class="mpwpb_popup_header">
                            <h3><?php esc_html_e('Features Heighlight', 'service-booking-manager'); ?></h3>
                            <span class="fas fa-times mpwpb_popup_close"></span>
                        </div>
                        <div class="mpwpb_popup_body">
                            <ul class="features">
								<?php
									foreach ($features_heightlight as $value):
										?>
                                        <li style="color:#333"><i class="fas fa-check-circle"></i><?php echo esc_html($value); ?></li>
									<?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
				<?php
			}
			public static function show_ratings() {
				$rating = get_post_meta(get_the_ID(), 'mpwpb_service_review_ratings', true);
				$rating_text = get_post_meta(get_the_ID(), 'mpwpb_service_rating_text', true);
				if (!$rating):
					return;
				endif;
				?>
                <div class="mpwpb-hero-rating-row">
                    <span class="mpwpb-hero-stars"><?php self::render_star_icons((float) $rating); ?></span>
                    <strong class="mpwpb-hero-rating-value"><?php echo esc_html($rating); ?></strong>
					<?php if ($rating_text): ?>
                        <span class="mpwpb-hero-rating-text"><?php echo esc_html($rating_text); ?></span>
					<?php endif; ?>
                </div>
				<?php
			}
			public static function get_ratings() {
				$rating = get_post_meta(get_the_ID(), 'mpwpb_service_review_ratings', true);
				$scale = get_post_meta(get_the_ID(), 'mpwpb_service_rating_scale', true);
				if ($rating):
					?>
                    <div class="ratings"><i class="fas fa-star"></i> <?php echo esc_html($rating); ?> <span><?php echo esc_html($scale); ?></span></div>
				<?php
				endif;
			}
			public function show_service_nav() {
				$service_overview_status = get_post_meta(get_the_ID(), 'mpwpb_service_overview_status', true);
				$faq_status = get_post_meta(get_the_ID(), 'mpwpb_faq_active', true);
				$service_details_status = get_post_meta(get_the_ID(), 'mpwpb_service_details_status', true);
				// Unlike Overview/FAQ/Details, reviews have no per-service
				// admin on/off setting -- the tab is always shown.
				$reviews_status = 'on';
				?>
                <nav class="mpwpb-details-page-tab">
                    <ul>
						<?php if ($service_overview_status === 'on'): ?>
                            <li>
                                <a href="#service-overview"><?php esc_html_e('Overview', 'service-booking-manager') ?></a>
                            </li>
						<?php endif; ?>
						<?php if ($faq_status === 'on'): ?>
                            <li>
                                <a href="#service-faq"><?php esc_html_e('FAQ', 'service-booking-manager') ?></a>
                            </li>
						<?php endif; ?>
						<?php if ($service_details_status === 'on'): ?>
                            <li>
                                <a href="#service-details"><?php esc_html_e('Details', 'service-booking-manager') ?></a>
                            </li>
						<?php endif; ?>
						<?php if ($reviews_status === 'on'): ?>
                            <li>
                                <a href="#service-reviews"><?php esc_html_e('Reviews', 'service-booking-manager') ?></a>
                            </li>
						<?php endif; ?>
                    </ul>
                </nav>
				<?php
			}
			public function show_service_overview() {
				$post_id = get_the_ID();
				$service_overview_status = get_post_meta($post_id, 'mpwpb_service_overview_status', true);
				$service_overview_content = get_post_meta($post_id, 'mpwpb_service_overview_content', true);
				if ($service_overview_status === 'on'):
					?>
                    <section id="service-overview">
                        <h2><?php esc_html_e('Service Overview', 'service-booking-manager'); ?></h2>
                        <div class="mpwpb-overview-content">
							<?php
								// Content is sanitized on save according to the author's capability
								// (see MPWPB_Settings::sanitize_rich_content), mirroring core post_content.
								// Re-running wp_kses_post() here would strip admin-authored <style>/CSS.
								echo do_shortcode($service_overview_content); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							?>
                        </div>
						<?php $this->show_overview_stats($post_id); ?>
						<?php $this->show_hours($post_id); ?>
                    </section>
				<?php
				endif;
			}

			/**
			 * Real, computed numbers — not decorative placeholders: total
			 * bookings made against this service, its configured time-slot
			 * length, and how many bookable services it offers (including
			 * ones nested under categories/sub-categories).
			 */
			private function show_overview_stats($post_id) {
				$bookings = get_posts(array(
					'post_type' => 'mpwpb_booking',
					'posts_per_page' => -1,
					'fields' => 'ids',
					'meta_key' => 'mpwpb_id',
					'meta_value' => $post_id,
				));
				$booking_count = count($bookings);
				$time_slot_length = (int) MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_time_slot_length', 0);
				// Not MPWPB_Function::get_all_service() — that reads the older,
				// unused 'mpwpb_category_infos' meta shape. Real service data
				// (confirmed against live posts) lives in 'mpwpb_service', the
				// same flat array the booking widget itself reads from.
				$service_count = count(MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_service', array()));
				// Only a real, non-zero count is worth a card -- an empty "—"
				// placeholder next to two real numbers reads as broken rather
				// than honest, so this card is dropped entirely instead, and
				// the remaining two split the row 50/50 (mpwpb-overview-stats--two).
				$show_bookings_stat = $booking_count > 0;
				?>
                <div class="mpwpb-overview-stats<?php echo $show_bookings_stat ? '' : ' mpwpb-overview-stats--two'; ?>">
					<?php if ($show_bookings_stat): ?>
                    <div class="mpwpb-overview-stat">
                        <p class="mpwpb-overview-stat-num"><?php echo esc_html(number_format_i18n($booking_count) . '+'); ?></p>
                        <p class="mpwpb-overview-stat-label"><?php esc_html_e('Bookings completed', 'service-booking-manager'); ?></p>
                    </div>
					<?php endif; ?>
                    <div class="mpwpb-overview-stat">
                        <p class="mpwpb-overview-stat-num"><?php echo esc_html($time_slot_length > 0 ? $time_slot_length . ' ' . __('min', 'service-booking-manager') : '—'); ?></p>
                        <p class="mpwpb-overview-stat-label"><?php esc_html_e('Typical slot length', 'service-booking-manager'); ?></p>
                    </div>
                    <div class="mpwpb-overview-stat">
                        <p class="mpwpb-overview-stat-num"><?php echo esc_html($service_count); ?></p>
                        <p class="mpwpb-overview-stat-label"><?php esc_html_e('Services offered', 'service-booking-manager'); ?></p>
                    </div>
                </div>
				<?php
			}

			/**
			 * Real configured hours (per-day start/end times + off days),
			 * simplified to the default schedule + a list of closed days
			 * rather than a full day-by-day breakdown.
			 */
			private function show_hours($post_id) {
				$default_start = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_default_start_time', '');
				$default_end = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_default_end_time', '');
				if ($default_start === '' || $default_end === '') {
					return;
				}
				$off_days = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_off_days', '');
				$off_day_keys = array_filter(explode(',', (string) $off_days));
				$days = MPWPB_Global_Function::week_day();
				$open_days = array();
				foreach ($days as $key => $day) {
					if (!in_array($key, $off_day_keys, true)) {
						$open_days[] = $day;
					}
				}
				?>
                <div class="mpwpb-hours-strip">
                    <p class="mpwpb-hours-open">
                        <i class="fas fa-clock"></i>
						<?php if (!empty($open_days)): ?>
                            <span class="mpwpb-hours-days"><?php echo esc_html(implode(' - ', $open_days)); ?></span>
						<?php else: ?>
							<?php esc_html_e('By appointment', 'service-booking-manager'); ?>
						<?php endif; ?>
                    </p>
                    <p class="mpwpb-hours-time"><?php echo esc_html($this->format_hour($default_start) . ' – ' . $this->format_hour($default_end)); ?></p>
                </div>
				<?php
			}

			private function format_hour($hour) {
				if ($hour === '' || $hour === null) {
					return '';
				}
				$use_24hour = MPWPB_Global_Function::get_settings('mpwpb_global_settings', 'time_format_24hour', 'no');
				return $use_24hour === 'yes' ? date_i18n('H:i', $hour * 3600) : date_i18n('h:i A', $hour * 3600);
			}
			public function show_service_faq() {
				$mpwpb_faq = get_post_meta(get_the_ID(), 'mpwpb_faq', true);
				$faq_status = get_post_meta(get_the_ID(), 'mpwpb_faq_active', true);
				if ($faq_status == 'on'):
					?>
                    <section id="service-faq">
                        <h2><?php esc_html_e('Service FAQ', 'service-booking-manager'); ?></h2>
						<?php
							if (!empty($mpwpb_faq)) {
								foreach ($mpwpb_faq as $value) {
									$this->show_faq_data($value['title'], $value['content']);
								}
							}
						?>
                    </section>
				<?php
				endif;
			}
			public function show_faq_data($title, $content) {
				?>
                <div class="mpwpb-serivice-faq">
                    <div class="faq-header">
                        <i class="fas fa-plus"></i> <?php echo esc_html($title); ?>
                    </div>
                    <div class="faq-content">
						<?php echo wp_kses_post($content); ?>
                    </div>
                </div>
				<?php
			}
			public function show_service_details() {
				$service_details_status = get_post_meta(get_the_ID(), 'mpwpb_service_details_status', true);
				$service_details_content = get_post_meta(get_the_ID(), 'mpwpb_service_details_content', true);
				if ($service_details_status === 'on'):
					?>
                    <section id="service-details">
                        <h2><?php esc_html_e('Service Details', 'service-booking-manager'); ?></h2>
						<div class="mpwpb-service-details-content">
							<?php
								// Content is sanitized on save according to the author's capability
								// (see MPWPB_Settings::sanitize_rich_content), mirroring core post_content.
								// Re-running wp_kses_post() here would strip admin-authored <style>/CSS.
								echo do_shortcode($service_details_content); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							?>
						</div>
                    </section>
				<?php
				endif;
			}
			/**
			 * "Our Past Work" — renders the same per-service gallery images
			 * uploaded via the admin "Gallery Images" field (Admin/settings/
			 * Gallery.php & the Modern editor's rail card, both save to the
			 * 'mpwpb_slider_images' meta gated by 'mpwpb_display_slider').
			 * Nothing else on the front end reads this meta yet, so this is
			 * the first consumer of it. Hidden entirely when the gallery is
			 * off or has no images -- no empty section/heading is shown.
			 */
			public function show_service_gallery() {
				$post_id = get_the_ID();
				$display_status = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_display_slider', 'off');
				$image_ids = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_slider_images', array());
				$image_ids = is_array($image_ids) ? array_values(array_filter($image_ids)) : array();
				if ($display_status !== 'on' || empty($image_ids)) {
					return;
				}
				?>
                <section id="service-gallery" class="mpwpb-gallery-section">
                    <div class="mpwpb-gallery-head">
                        <div>
                            <h2><?php esc_html_e('Our Past Work', 'service-booking-manager'); ?></h2>
                            <p class="mpwpb-gallery-sub"><?php esc_html_e('See the stunning results of our meticulous work.', 'service-booking-manager'); ?></p>
                        </div>
                        <div class="mpwpb-gallery-nav">
                            <span class="fas fa-chevron-left prev"></span>
                            <span class="fas fa-chevron-right next"></span>
                        </div>
                    </div>
                    <div class="owl-theme mpwpb-owl-carousel">
						<?php foreach ($image_ids as $image_id):
							$image_url = MPWPB_Global_Function::get_image_url('', $image_id, 'large');
							if (!$image_url) {
								continue;
							}
							$full_url = MPWPB_Global_Function::get_image_url('', $image_id, 'full') ?: $image_url;
							?>
                            <div class="mpwpb-gallery-item" data-target-popup="#mpwpb_gallery_lightbox" data-full="<?php echo esc_url($full_url); ?>">
                                <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr(get_the_title($post_id)); ?>"/>
                                <span class="mpwpb-gallery-zoom"><i class="fas fa-search-plus"></i></span>
                            </div>
						<?php endforeach; ?>
                    </div>
                </section>
                <!-- Click-to-zoom viewer: reuses the plugin's existing generic
                     popup mechanism (data-target-popup/data-popup, document-
                     delegated open/close in mp_global/assets/mp_style/
                     mpwpb_plugin_global.js) -- only the image swap on click
                     and prev/next cycling are custom JS (mpwpb-service-page-
                     modern.js). -->
                <div class="mpwpb_popup mpwpb_style mpwpb-gallery-lightbox" data-popup="#mpwpb_gallery_lightbox">
                    <div class="mpwpb_popup_main_area">
                        <span class="fas fa-times mpwpb_popup_close"></span>
                        <div class="mpwpb-gallery-lightbox-body">
                            <span class="fas fa-chevron-left mpwpb-gallery-lightbox-prev"></span>
                            <img src="" alt="" class="mpwpb-gallery-lightbox-img"/>
                            <span class="fas fa-chevron-right mpwpb-gallery-lightbox-next"></span>
                        </div>
                        <div class="mpwpb-gallery-lightbox-counter"></div>
                    </div>
                </div>
				<?php
			}
			/**
			 * Approved reviews for a service, newest first. Reads the
			 * mpwpb_reviews table directly (MPWPB_Reviews_Admin owns that
			 * table's schema/creation, but its query helpers are private and
			 * scoped to the admin list-table's pagination/filters, which
			 * doesn't fit this simpler "all approved reviews for one
			 * service" read).
			 */
			private function get_approved_reviews($service_id) {
				global $wpdb;
				$table_name = $wpdb->prefix . 'mpwpb_reviews';
				if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
					return array();
				}
				return $wpdb->get_results($wpdb->prepare(
					"SELECT * FROM $table_name WHERE service_id = %d AND status = 'approved' ORDER BY date_created DESC",
					$service_id
				));
			}

			public function show_service_reviews() {
				$post_id = get_the_ID();
				$reviews = $this->get_approved_reviews($post_id);
				$current_user_id = get_current_user_id();
				$can_review = $current_user_id && class_exists('MPWPB_Reviews_Admin') && MPWPB_Reviews_Admin::user_can_review($post_id, $current_user_id);
				?>
                <section id="service-reviews">
                    <div class="mpwpb-reviews-head">
                        <h2><?php esc_html_e('Customer Reviews', 'service-booking-manager'); ?></h2>
                        <button type="button" class="mpwpb-write-review-btn" data-target-popup="#mpwpb_write_review_popup">
                            <i class="fas fa-pen"></i> <?php esc_html_e('Write a Review', 'service-booking-manager'); ?>
                        </button>
                    </div>

                    <div class="mpwpb-reviews-list">
						<?php if (empty($reviews)): ?>
                            <p class="mpwpb-no-reviews"><?php esc_html_e('No reviews yet. Be the first to review this service!', 'service-booking-manager'); ?></p>
						<?php else: foreach ($reviews as $review): ?>
                            <div class="mpwpb-review-item">
                                <div class="mpwpb-review-item-head">
                                    <span class="mpwpb-review-stars"><?php self::render_star_icons((float) $review->rating); ?></span>
                                    <strong class="mpwpb-review-author"><?php echo esc_html($review->user_name); ?></strong>
                                    <span class="mpwpb-review-date"><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($review->date_created))); ?></span>
                                </div>
								<?php if ($review->title): ?>
                                    <h4 class="mpwpb-review-title"><?php echo esc_html($review->title); ?></h4>
								<?php endif; ?>
                                <p class="mpwpb-review-content"><?php echo esc_html($review->content); ?></p>
                            </div>
						<?php endforeach; endif; ?>
                    </div>

                    <!-- Reuses the plugin's existing generic popup mechanism
                         (data-target-popup/data-popup, document-delegated
                         open/close in mp_global/assets/mp_style/
                         mpwpb_plugin_global.js) -- same pattern as the
                         gallery lightbox / features popup elsewhere in this
                         template, so no bespoke open/close JS is needed here. -->
                    <div class="mpwpb_popup mpwpb_style mpwpb-review-popup" data-popup="#mpwpb_write_review_popup">
                        <div class="mpwpb_popup_main_area">
                            <div class="mpwpb_popup_header">
                                <h3><?php esc_html_e('Write a Review', 'service-booking-manager'); ?></h3>
                                <span class="fas fa-times mpwpb_popup_close"></span>
                            </div>
                            <div class="mpwpb_popup_body">
                                <div class="mpwpb-review-form-wrap">
									<?php if (!$current_user_id): ?>
                                        <p class="mpwpb-review-login-notice">
											<?php
												printf(
													/* translators: %s: login link */
													esc_html__('Please %s to leave a review.', 'service-booking-manager'),
													'<a href="' . esc_url(wp_login_url(get_permalink($post_id))) . '">' . esc_html__('log in', 'service-booking-manager') . '</a>'
												);
											?>
                                        </p>
									<?php elseif (!$can_review): ?>
                                        <p class="mpwpb-review-login-notice"><?php esc_html_e('You can write a review after booking this service.', 'service-booking-manager'); ?></p>
									<?php else: ?>
                                        <form id="mpwpb-review-form" data-service-id="<?php echo esc_attr($post_id); ?>">
                                            <div class="mpwpb-review-rating-group">
                                                <span class="mpwpb-review-rating-label"><?php esc_html_e('Overall Rating', 'service-booking-manager'); ?></span>
                                                <div class="mpwpb-star-input" data-rating="0">
													<?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <i class="far fa-star" data-value="<?php echo esc_attr($i); ?>"></i>
													<?php endfor; ?>
                                                </div>
                                            </div>
                                            <input type="hidden" name="rating" class="mpwpb-review-rating-value" value="0">
                                            <label class="mpwpb-review-field">
												<?php esc_html_e('Title (optional)', 'service-booking-manager'); ?>
                                                <input type="text" name="title" maxlength="255" placeholder="<?php esc_attr_e('Summarize your experience', 'service-booking-manager'); ?>">
                                            </label>
                                            <label class="mpwpb-review-field">
												<?php esc_html_e('Your Review', 'service-booking-manager'); ?>
                                                <textarea name="content" rows="4" required placeholder="<?php esc_attr_e('Tell us what you liked or what could be improved…', 'service-booking-manager'); ?>"></textarea>
                                            </label>
                                            <div class="mpwpb-review-msg" role="status"></div>
                                            <div class="mpwpb-review-actions">
                                                <button type="button" class="mpwpb-review-cancel mpwpb_popup_close"><?php esc_html_e('Cancel', 'service-booking-manager'); ?></button>
                                                <button type="submit" class="mpwpb-review-submit"><?php esc_html_e('Submit Review', 'service-booking-manager'); ?></button>
                                            </div>
                                        </form>
									<?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
				<?php
			}


            public function display_staff_member( $all_staffs ) {
                ob_start(); ?>
                <div class="mpwpb_added_staff_holder">
                    <?php foreach ( $all_staffs as $staff_data ) {
                        $staff_id   = $staff_data->ID;
                        $staff_name = $staff_data->display_name;
                        $image_id   = get_user_meta( $staff_id, 'mpwpb_custom_profile_image', true );
                        $image_url  = esc_url( wp_get_attachment_url( $image_id ) );

                        if ( empty( $image_url ) ) {
                            // Same fallback MPWPB_Staff_Members::get_custom_user_profile_image()
                            // already uses elsewhere -- WordPress's own default avatar, no
                            // dependency on an external placeholder service.
                            $image_url = esc_url( get_avatar_url( $staff_id, [ 'size' => 80 ] ) );
                        }
                        ?>
                        <div class="mpwpb_staff_card">
                            <img src="<?php echo $image_url; ?>" alt="<?php echo esc_attr( $staff_name ); ?>" />
                            <div class="mpwpb_staff_name"><?php echo esc_html( $staff_name ); ?></div>
                        </div>
                    <?php } ?>
                </div>

                <?php
                return ob_get_clean();
            }


            public function show_added_staff_details() {
                $enable_staff_member = MPWPB_Global_Function::get_post_info( get_the_ID(), 'mpwpb_staff_member_add', 'no' );
				if ( $enable_staff_member === 'on' ){
                    $all_staffs = [];
                    $get_selected_staff = get_post_meta( get_the_ID(), 'mpwpb_selected_staff_ids', array() );
                    $flat_selected_staff_ids = call_user_func_array('array_merge', $get_selected_staff);
                    if( is_array( $flat_selected_staff_ids ) && !empty( $flat_selected_staff_ids ) ) {
                        $all_staffs = get_users([
                            'include' => $flat_selected_staff_ids,
                            'role' => 'mpwpb_staff'
                        ]);
                    }
                    ?>
                    <section id="service-staff">
                        <h2><?php esc_html_e('Staff Members', 'service-booking-manager'); ?></h2>
                    </section>
				<?php

                    if ( sizeof($all_staffs) > 0) {
                        echo $this->display_staff_member( $all_staffs );
                    }

                }
			}

			/**
			 * "Reorder" support: reads the ?mpwpb_reorder={booking_id} query arg
			 * (set by Frontend/MPWPB_User_Dashboard.php's Reorder link) and
			 * resolves it into the data the booking wizard's JS needs to
			 * re-open with the same selection -- the LIVE mpwpb_service array
			 * key for EVERY service that was in the original booking (a
			 * booking can contain more than one -- the wizard's own service
			 * step supports multi-select), plus the staff id.
			 *
			 * Category/sub-category don't need resolving here: the tree in
			 * category_selection_static.php positions each service node under
			 * whichever category it's CURRENTLY assigned to (via the service's
			 * own live parent_cat/sub_cat), so matching purely on each
			 * service's data-service attribute already lands the click on the
			 * right node regardless of category. Date/time is intentionally
			 * never prefilled -- the old date has passed, the customer must
			 * pick a fresh one.
			 *
			 * @return array{service_keys:int[], service_quantities:array,
			 *         extra_services:array, staff_id:string, advance_to_schedule:bool,
			 *         notice_title:string, notice:string}|null Null
			 *         when there's no reorder request, or it doesn't belong to
			 *         the current user / this service (never surfaces someone
			 *         else's booking data).
			 */
			public static function get_reorder_prefill($post_id) {
				if (!is_user_logged_in() || empty($_GET['mpwpb_reorder'])) {
					return null;
				}
				$booking_id = absint($_GET['mpwpb_reorder']);
				if (get_post_type($booking_id) !== 'mpwpb_booking'
					|| (int) get_post_meta($booking_id, 'mpwpb_user_id', true) !== get_current_user_id()
					|| (int) get_post_meta($booking_id, 'mpwpb_id', true) !== (int) $post_id) {
					return null;
				}

				$old_services = self::normalise_reorder_value(get_post_meta($booking_id, 'mpwpb_service', true));
				$old_extras = self::normalise_reorder_value(get_post_meta($booking_id, 'mpwpb_extra_service_info', true));
				if (!is_array($old_extras) || !$old_extras) {
					$old_extras = self::normalise_reorder_value(get_post_meta($booking_id, 'mpwpb_extra_services', true));
				}

				// Backend-created and older bookings did not always copy the
				// selection arrays onto the booking CPT. WooCommerce's line item is
				// the second authoritative copy, so recover from it before deciding
				// that an order has nothing to restore.
				$order_item = self::get_reorder_order_item($booking_id, $post_id);
				if ((!is_array($old_services) || !$old_services) && $order_item) {
					$old_services = self::normalise_reorder_value($order_item->get_meta('_mpwpb_service', true));
				}
				if ((!is_array($old_extras) || !$old_extras) && $order_item) {
					$old_extras = self::normalise_reorder_value($order_item->get_meta('_mpwpb_extra_service_info', true));
				}

				$live_services = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_service', array());
				$service_keys = array();
				$service_quantities = array();
				$missing_services = 0;
				if (is_array($old_services)) {
					foreach ($old_services as $old_service) {
						if (!is_array($old_service)) {
							continue;
						}
						$key = null;
						$stored_key = isset($old_service['service_id']) ? absint($old_service['service_id']) - 1 : -1;
						if ($stored_key >= 0 && isset($live_services[$stored_key])) {
							$stored_name = (string) ($old_service['name'] ?? '');
							$live_name = (string) ($live_services[$stored_key]['name'] ?? '');
							if ($stored_name === '' || $stored_name === $live_name) {
								$key = $stored_key;
							}
						}
						if ($key === null) {
							$key = self::find_key_by_name($live_services, $old_service['name'] ?? '');
						}
						if ($key !== null) {
							$service_id = $key + 1;
							$service_keys[] = $service_id;
							$service_quantities[$service_id] = max(1, absint($old_service['qty'] ?? 1));
						} else {
							$missing_services++;
						}
					}
				}

				$live_extras = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_extra_service', array());
				$extra_services = array();
				$missing_extras = 0;
				if (is_array($old_extras)) {
					foreach ($old_extras as $old_extra) {
						if (!is_array($old_extra)) {
							continue;
						}
						$name = (string) ($old_extra['ex_name'] ?? $old_extra['name'] ?? '');
						if (self::find_key_by_name($live_extras, $name) !== null) {
							$extra_services[] = array(
								'name' => $name,
								'qty' => max(1, absint($old_extra['ex_qty'] ?? $old_extra['qty'] ?? 1)),
							);
						} elseif ($name !== '') {
							$missing_extras++;
						}
					}
				}

				if (!$service_keys) {
					$notice_title = __('Review your reorder', 'service-booking-manager');
					$notice = $extra_services
						? __('The original order did not store a base service. Its available extras were restored; choose a service and a new date and time to continue.', 'service-booking-manager')
						: __('The original service is no longer available. Choose a current service and a new date and time to continue.', 'service-booking-manager');
				} elseif ($missing_services || $missing_extras) {
					$notice_title = __('Review your reorder', 'service-booking-manager');
					$notice = __('Available choices from the original order were restored. Some old choices are no longer available; review the selection and choose a new date and time.', 'service-booking-manager');
				} else {
					$notice_title = __('Reorder ready', 'service-booking-manager');
					$notice = __('Your previous choices were restored. Choose a new date and time to complete the reorder.', 'service-booking-manager');
				}

				return array(
					'service_keys' => $service_keys,
					'service_quantities' => $service_quantities,
					'extra_services' => $extra_services,
					'staff_id' => get_post_meta($booking_id, 'mpwpb_staff_term_id', true),
					'advance_to_schedule' => !empty($service_keys),
					'notice_title' => $notice_title,
					'notice' => $notice,
				);
			}

			/** Find the WooCommerce line item which produced this booking. */
			private static function get_reorder_order_item($booking_id, $post_id) {
				if (!function_exists('wc_get_order')) {
					return null;
				}
				$order = wc_get_order(absint(get_post_meta($booking_id, 'mpwpb_order_id', true)));
				if (!$order) {
					return null;
				}
				foreach ($order->get_items() as $item) {
					if ((int) self::normalise_reorder_value($item->get_meta('_mpwpb_id', true)) === (int) $post_id) {
						return $item;
					}
				}
				return null;
			}

			/** Unwrap legacy values which were saved as serialized strings. */
			private static function normalise_reorder_value($value) {
				for ($i = 0; $i < 2 && is_string($value) && is_serialized($value); $i++) {
					$value = maybe_unserialize($value);
				}
				return $value;
			}

			/**
			 * mpwpb_service/mpwpb_category_service/mpwpb_sub_category_service
			 * entries only carry their resolved name at booking-creation time
			 * (see Frontend/MPWPB_Woocommerce.php::build_booking_item_from_request()),
			 * not the original array key -- so recovering "which live entry is
			 * this" has to go back through the name.
			 */
			private static function find_key_by_name($list, $name) {
				if (!is_array($list) || $name === '' || $name === null) {
					return null;
				}
				foreach ($list as $key => $entry) {
					if (is_array($entry) && ($entry['name'] ?? '') === $name) {
						return $key;
					}
				}
				return null;
			}
		}
		new MPWPB_Static_Template();
	}
