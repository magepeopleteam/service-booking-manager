<?php
	/*
   * @Author 		engr.sumonazma@gmail.com
   * Copyright: 	mage-people.com
   */
	if (!defined('ABSPATH')) {
		die;
	} // Cannot access pages directly.
	if (!class_exists('MPWPB_Price_Settings')) {
		class MPWPB_Price_Settings {
			public function __construct() {
				add_action('add_mpwpb_settings_tab_content', [$this, 'price_settings'], 10, 1);
			}

			public function price_settings($post_id) {
				?>
				<div class="tabsItem mpwpb_price_settings" data-tabs="#mpwpb_price_settings">
					<header>
							<h2><?php esc_html_e('Price Settings', 'service-booking-manager'); ?></h2>
							<span><?php esc_html_e('Price Settings', 'service-booking-manager'); ?></span>
                    </header>

					<section class="section">
							<h2><?php esc_html_e('Pricing Settings', 'service-booking-manager'); ?></h2>
							<span><?php esc_html_e('Pricing Settings', 'service-booking-manager'); ?></span>
                    </section>
					<section>
						<div class="category-service-area">
							<div class="category-container">
								<div class="header">
									<h3><?php _e('Categories','service-booking-manager'); ?></h3>
									<button class="button" type="button"><?php _e('Show All service','service-booking-manager'); ?></button>
								</div>
								<?php do_action('mpwpb_show_category',$post_id); ?>
							</div>
							<div class="service-container">
								<div class="header">
									<h3 class="service-title"><?php _e('All Services','service-booking-manager'); ?></h3>
									<button class="button mpwpb-service-new" data-modal="mpwpb-service-new" type="button"><?php _e('Add Service Category','service-booking-manager'); ?></button>
								</div>
								<?php do_action('mpwpb_show_service',$post_id); ?>
							</div>
						</div>
					</section>
				</div>
				<?php
			}
		}
		new MPWPB_Price_Settings();
	}