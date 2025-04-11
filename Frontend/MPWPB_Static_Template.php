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
                                    <li><i class="fas fa-check-circle"></i><?php echo esc_html($value); ?></li>
								<?php endif; ?>
							<?php endforeach; ?>
						<?php if (count($features_heightlight) > $limit): ?>
                            <h5 class="view_more" data-target-popup="#mpwpb_view_more_popup"><?php esc_html_e('View more!', 'service-booking-manager'); ?></h5>
						<?php endif; ?>
                    </ul>
				<?php
				endif;
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
				$rating_text = get_post_meta(get_the_ID(), 'mpwpb_service_rating_text', true);
				?>
				<?php self::get_ratings(); ?>
				<?php if ($rating_text): ?>
                    <p><?php echo esc_html($rating_text); ?></p>
				<?php endif; ?>
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
                <nav>
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
				$service_overview_status = get_post_meta(get_the_ID(), 'mpwpb_service_overview_status', true);
				$service_overview_content = get_post_meta(get_the_ID(), 'mpwpb_service_overview_content', true);
				if ($service_overview_status === 'on'):
					?>
                    <section id="service-overview">
                        <h2><?php esc_html_e('Service Overview', 'service-booking-manager'); ?></h2>
						<?php echo wp_kses_post($service_overview_content); ?>
                    </section>
				<?php
				endif;
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
						<?php echo wp_kses_post($service_details_content); ?>
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
		}
		new MPWPB_Static_Template();
	}