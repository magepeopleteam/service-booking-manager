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