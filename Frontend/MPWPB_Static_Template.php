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
				add_action('mpwpb_service_reviews', [$this, 'show_service_reviews']);
				add_action('mpwpb_added_staff_details', [$this, 'show_added_staff_details']);
                add_action('mpwpb_progress_bar', [$this, 'mpwpb_progress_bar_callback'], 10, 2 );
			}

            public function mpwpb_progress_bar_callback( $service_id, $is_active ) {
                $enable_staff_member = get_post_meta($service_id, 'mpwpb_staff_member_add', true);
                ?>
                <div class="mpwpb_cart_progress_wrapper">
                    <div class="mpwpb_cart_progress_step active" id="mpwpb_progress_service">
                        <div class="mpwpb_cart_progress_circle"><?php esc_html_e(1, 'service-booking-manager') ?></div>
                        <div class="mpwpb_cart_progress_label"><?php esc_html_e('Service', 'service-booking-manager') ?></div>
                    </div>
                    <div class="mpwpb_cart_progress_arrow">→</div>
                    <div class="mpwpb_cart_progress_step" id="mpwpb_progress_date_time">
                        <div class="mpwpb_cart_progress_circle"><?php esc_html_e(2, 'service-booking-manager') ?></div>
                        <div class="mpwpb_cart_progress_label"><?php esc_html_e('Date & Time', 'service-booking-manager') ?></div>
                    </div>
                    <div class="mpwpb_cart_progress_arrow">→</div>
                    <?php  if ( is_plugin_active('service-booking-manager-pro/MPWPB_Plugin_Pro.php') && $enable_staff_member === 'on' ) {
                        $number = 4;
                        ?>
                        <div class="mpwpb_cart_progress_step" id="mpwpb_progress_staff">
                            <div class="mpwpb_cart_progress_circle"><?php esc_html_e(3, 'service-booking-manager') ?></div>
                            <div class="mpwpb_cart_progress_label"><?php esc_html_e('Staff', 'service-booking-manager') ?></div>
                        </div>
                        <div class="mpwpb_cart_progress_arrow" id="mpwpb_staff_arrow">→</div>
                    <?php } else {  $number = 3; }?>
                    <div class="mpwpb_cart_progress_step" id="mpwpb_progress_checkout">
                        <div class="mpwpb_cart_progress_circle"><?php echo esc_html( $number ) ?></div>
                        <div class="mpwpb_cart_progress_label"><?php esc_html_e('Checkout', 'service-booking-manager') ?></div>
                    </div>
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
							foreach ($features_heightlight as $key => $value):
								if ($key < $limit) : ?>
                                    <li>
                                        <span class="mpwpb-feature-icon"><i class="fas fa-check-circle"></i></span>
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
				$reviews_status = 'off';
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
						<?php
							// Content is sanitized on save according to the author's capability
							// (see MPWPB_Settings::sanitize_rich_content), mirroring core post_content.
							// Re-running wp_kses_post() here would strip admin-authored <style>/CSS.
							echo do_shortcode($service_overview_content); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						?>
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
						<?php
							// Content is sanitized on save according to the author's capability
							// (see MPWPB_Settings::sanitize_rich_content), mirroring core post_content.
							// Re-running wp_kses_post() here would strip admin-authored <style>/CSS.
							echo do_shortcode($service_details_content); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						?>
                    </section>
				<?php
				endif;
			}
			public function show_service_reviews() {
				$review_status = 'off';
				if ($review_status === 'on'):
					?>
                    <section id="service-reviews">
                        <h2><?php esc_html_e('Servie Reviews', 'service-booking-manager'); ?></h2>
                    </section>
				<?php
				endif;
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
                            $image_url = 'https://via.placeholder.com/80'; // fallback image
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
                    <section id="service-reviews">
                        <h2><?php esc_html_e('Staff Members', 'service-booking-manager'); ?></h2>
                    </section>
				<?php

                    if ( sizeof($all_staffs) > 0) {
                        echo $this->display_staff_member( $all_staffs );
                    }

                }
			}
		}
		new MPWPB_Static_Template();
	}