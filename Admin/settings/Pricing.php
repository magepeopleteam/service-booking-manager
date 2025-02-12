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
				add_action('wp_ajax_mpwpb_import_old_data', [$this, 'import_old_data'], 10, 1);
				add_action('wp_ajax_nopriv_mpwpb_import_old_data', [$this, 'import_old_data'],10);
			}

			public function import_old_data() {
				$post_id = sanitize_text_field($_POST['postId']);
				
				ob_start();
				$resultMessage = esc_html__('Data Updated Successfully', 'service-booking-manager');
				MPWPB_Service_Category::copy_old_category_data($post_id);
				$html_output = ob_get_clean();
				wp_send_json_success([
					'message' => $resultMessage,
					'html' => $html_output,
				]);
				die;
			}
			public function price_settings($post_id) {
				?>
				<div class="tabsItem mpwpb_price_settings" data-tabs="#mpwpb_price_settings">
					<header>
							<h2><?php esc_html_e('Services and Price Settings', 'service-booking-manager'); ?></h2>
							<span><?php esc_html_e('Manage and customize your offerings with ease! Create new services, organize them into categories, and set competitive prices to cater to your customers\' needs effectively.', 'service-booking-manager'); ?></span>
                    </header>

					<section class="section">
							<h2><?php esc_html_e('Category and Services', 'service-booking-manager'); ?></h2>
							<span><?php esc_html_e('Create and organize service categories, adding services under each to keep everything structured and accessible.', 'service-booking-manager'); ?></span>
                    </section>
					<section>
						<div class="category-service-area">
							<div class="category-container">
								<div class="header">
									<h3><?php esc_html_e('Categories','service-booking-manager'); ?></h3>
									<button class="button show-all-services" type="button"><?php esc_html_e('Show All service','service-booking-manager'); ?></button>
								</div>
								<?php do_action('mpwpb_show_category',$post_id); ?>
							</div>
							<div class="service-container">
								<div class="header">
									<h3 class="service-title"><?php esc_html_e('All Services','service-booking-manager'); ?></h3>
									<button class="button mpwpb-service-new" data-modal="mpwpb-service-new" type="button"><?php esc_html_e('Add New Service','service-booking-manager'); ?></button>
								</div>
								<?php do_action('mpwpb_show_service',$post_id); ?>
							</div>
						</div>
					</section>
					<?php 
						$all_meata_data    = get_post_meta($post_id);
						if (array_key_exists('mpwpb_category_infos', $all_meata_data)){
							$category_info = get_post_meta($post_id, 'mpwpb_category_infos', true);
						}
						$cat_service_copy =  get_post_meta($post_id, 'mpwpb_old_cat_service_copy', true);
						$cat_service_copy =$cat_service_copy?$cat_service_copy:'no';
					?>
					<?php if (!empty($category_info) and $cat_service_copy =='no'): ?>
					<section>
							<p><?php esc_html_e('If you have trouble with old data, click to ', 'service-booking-manager'); ?><a href="#" class="mpwpb-import-old-data" data-id="<?php echo esc_attr($post_id); ?>"><?php esc_html_e('Import Old Services', 'service-booking-manager'); ?></a></p>
                    </section>
					<?php endif; ?>
				</div>
				<?php
			}
		}
		new MPWPB_Price_Settings();
	}