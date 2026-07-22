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
				add_action('wp_ajax_mpwpb_dismiss_dummy_import', array($this, 'ajax_dismiss_dummy_import'));
			}

			public static function is_already_imported() {
				return get_option('mpwpb_dummy_already_inserted') == 'yes';
			}

			public function is_eligible() {
				$count_posts = wp_count_posts('mpwpb_item');
				$count_existing = isset($count_posts->publish) ? $count_posts->publish : 0;
				$plugin_active = MPWPB_Global_Function::check_woocommerce();
				return $plugin_active == 1;
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
				if (MPWPB_Global_Function::check_woocommerce() != 1) {
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
				$display_style = $this->should_auto_show_popup() ? '' : 'display: none;';
				?>
				<div id="mpwpb-woo-overlay" class="mpwpb-woo-overlay mpwpb-dummy-overlay" style="<?php echo esc_attr($display_style); ?>">
					<div class="mpwpb-woo-popup">
						<div class="mpwpb-woo-header">
							<div class="mpwpb-woo-header-icon">
								<svg width="24" height="24" viewBox="0 0 24 24" fill="none">
									<path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
								</svg>
							</div>
							<span class="mpwpb-woo-header-text"><?php esc_html_e('Service Booking Manager', 'service-booking-manager'); ?></span>
						</div>

						<div class="mpwpb-woo-icon-wrapper">
							<div class="mpwpb-woo-icon">
								<svg width="40" height="40" viewBox="0 0 24 24" fill="none">
									<circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="1.5"/>
									<path d="M12 8v4M12 16h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
								</svg>
							</div>
						</div>

						<div class="mpwpb-woo-content">
							<h2 class="mpwpb-woo-title"><?php esc_html_e('Import Dummy Services?', 'service-booking-manager'); ?></h2>
							<p class="mpwpb-woo-desc">
								<?php esc_html_e('Would you like to import dummy services, categories, and settings to see how Service Booking Manager works?', 'service-booking-manager'); ?>
							</p>
						</div>

						<div id="mpwpb-woo-progress" class="mpwpb-woo-progress" style="display:none;">
							<div class="mpwpb-woo-progress-bar">
								<div id="mpwpb-woo-progress-fill" class="mpwpb-woo-progress-fill"></div>
							</div>
							<p id="mpwpb-woo-status-text" class="mpwpb-woo-status-text"></p>
						</div>

						<div class="mpwpb-woo-actions">
							<button type="button" id="mpwpb-dummy-install-btn" class="mpwpb-woo-btn mpwpb-woo-btn-primary">
								<span class="mpwpb-woo-btn-icon">
									<svg width="18" height="18" viewBox="0 0 20 20" fill="none">
										<path d="M10 3v10m0 0l-4-4m4 4l4-4M3 17h14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
									</svg>
								</span>
								<span class="mpwpb-woo-btn-text"><?php esc_html_e('Yes, Import Data', 'service-booking-manager'); ?></span>
							</button>
							<button type="button" id="mpwpb-dummy-dismiss-btn" class="mpwpb-woo-btn mpwpb-woo-btn-secondary">
								<?php esc_html_e('No, Skip', 'service-booking-manager'); ?>
							</button>
						</div>
					</div>
				</div>

				<script>
				(function($) {
					$(document).ready(function() {
						var $overlay = $('#mpwpb-woo-overlay.mpwpb-dummy-overlay');
						var $popup = $overlay.find('.mpwpb-woo-popup');
						var $btn = $('#mpwpb-dummy-install-btn');
						var $dismissBtn = $('#mpwpb-dummy-dismiss-btn');
						var $progress = $('#mpwpb-woo-progress');
						var $fill = $('#mpwpb-woo-progress-fill');
						var $status = $('#mpwpb-woo-status-text');
						var $actions = $overlay.find('.mpwpb-woo-actions');
						var isWorking = false;

						if (!$overlay.length) return;

						// Manual trigger from other pages
					$(document).on('click', '#mpwpb-trigger-dummy-import-btn', function(e) {
							e.preventDefault();
							isWorking = false;
							$btn.prop('disabled', false);
							$dismissBtn.prop('disabled', false);
							$popup.removeClass('mpwpb-state-success mpwpb-state-error');
							$progress.hide();
							$fill.css('width', '0%');
							$status.text('').removeClass('mpwpb-success mpwpb-error');
							$actions.slideDown(0);
							$overlay.css('display', 'flex').hide().fadeIn(200);
						});

						$btn.on('click', function(e) {
							e.preventDefault();
							if (isWorking) return;
							isWorking = true;
							$btn.prop('disabled', true);
							$dismissBtn.prop('disabled', true);

							$actions.slideUp(250);
							$progress.slideDown(300);

							$fill.css('width', '50%');
							$status.text('<?php echo esc_js(__("Importing dummy data. This may take a moment...", "service-booking-manager")); ?>').removeClass('mpwpb-success mpwpb-error');

							$.ajax({
								url: ajaxurl,
								type: 'POST',
								data: {
									action: 'mpwpb_import_dummy_data',
									nonce: '<?php echo wp_create_nonce("mpwpb_import_dummy"); ?>'
								},
								success: function(response) {
									if (response.success) {
										$fill.css('width', '100%');
										$status.text('<?php echo esc_js(__("Import complete!", "service-booking-manager")); ?>').addClass('mpwpb-success');
										$popup.addClass('mpwpb-state-success');
										$popup.find('.mpwpb-woo-icon').html(
											'<svg width="40" height="40" viewBox="0 0 24 24" fill="none">' +
											'<circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="1.5"/>' +
											'<path d="M8 12l3 3 5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>' +
											'</svg>'
										);
										$popup.find('.mpwpb-woo-title').text('<?php echo esc_js(__("Success", "service-booking-manager")); ?>');
										$popup.find('.mpwpb-woo-desc').text('<?php echo esc_js(__("Dummy data imported successfully. Reloading page...", "service-booking-manager")); ?>');

										setTimeout(function() {
											window.location.reload();
										}, 1500);
									} else {
										showError(response.data && response.data.message ? response.data.message : '<?php echo esc_js(__("Failed to import.", "service-booking-manager")); ?>');
									}
								},
								error: function() {
									showError('<?php echo esc_js(__("Failed to import. Please try again.", "service-booking-manager")); ?>');
								}
							});
						});

					$dismissBtn.on('click', function(e) {
							e.preventDefault();
							if (isWorking) return;
							isWorking = true;

							$overlay.fadeOut(200);

							$.ajax({
								url: ajaxurl,
								type: 'POST',
								data: {
									action: 'mpwpb_dismiss_dummy_import',
									nonce: '<?php echo wp_create_nonce("mpwpb_dismiss_dummy"); ?>'
								}
							});
						});

						function showError(message) {
							isWorking = false;
							$popup.addClass('mpwpb-state-error');
							$status.text(message).addClass('mpwpb-error');
							$fill.css('width', '100%');

							$btn.prop('disabled', false);
							$dismissBtn.prop('disabled', false);
							$actions.slideDown(250);

							setTimeout(function() {
								$popup.removeClass('mpwpb-state-error');
								$progress.slideUp(250);
								$fill.css('width', '0%');
							}, 3000);
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

			private function do_dummy_import() {
				$dummy_post = get_option('mpwpb_dummy_already_inserted');
				$all_post = MPWPB_Global_Function::query_post_type('mpwpb_item');
				if ($all_post->post_count == 0 && $dummy_post != 'yes') {
					$dummy_data = $this->dummy_data();
					foreach ($dummy_data as $type => $dummy) {
						if ($type == 'taxonomy') {
							foreach ($dummy as $taxonomy => $dummy_taxonomy) {
								$check_taxonomy = MPWPB_Global_Function::get_taxonomy($taxonomy);
								if (is_string($check_taxonomy) || sizeof($check_taxonomy) == 0) {
									foreach ($dummy_taxonomy as $taxonomy_data) {
										wp_insert_term($taxonomy_data['name'], $taxonomy);
									}
								}
							}
						}
						if ($type == 'custom_post') {
							foreach ($dummy as $custom_post => $dummy_post) {
								$post = MPWPB_Global_Function::query_post_type($custom_post);
								if ($post->post_count == 0) {
									foreach ($dummy_post as $key => $dummy_data) {
										$title = $dummy_data['name'];
										$post_id = wp_insert_post([
											'post_title' => $title,
											'post_status' => 'publish',
											'post_type' => $custom_post
										]);
										if (array_key_exists('post_data', $dummy_data)) {
											foreach ($dummy_data['post_data'] as $meta_key => $data) {
												update_post_meta($post_id, $meta_key, $data);
											}
										}
										$image = MPWPB_PLUGIN_DIR . '/assets/images/dummy-image-' . $key . '.png';
										$image_attached = self::insert_media($image);
										set_post_thumbnail($post_id, $image_attached['id'] ?? '');
									}
								}
							}
						}
					}
					update_option('mpwpb_dummy_already_inserted', 'yes');
				}
			}
			public function dummy_data(): array {
				return [
					'taxonomy' => [],
					'custom_post' => [
						'mpwpb_item' => [
							0 => [
								'name' => 'Hair Cut Salon Booking',
								'post_data' => [
									'mpwpb_shortcode_title' => 'Hair Cut Service',
									'mpwpb_shortcode_sub_title' => 'Cut your hair beautifully with affordable price',
									'mpwpb_template' => 'static.php',
									'mpwpb_time_slot_length' => '60',
									'mpwpb_capacity_per_session' => '1',
									'mpwpb_default_start_time' => '10',
									'mpwpb_default_end_time' => '16',
									'mpwpb_default_start_break_time' => '13',
									'mpwpb_default_end_break_time' => '15',
									'mpwpb_monday_start_time' => '',
									'mpwpb_monday_end_time' => '',
									'mpwpb_monday_start_break_time' => '',
									'mpwpb_monday_end_break_time' => '',
									'mpwpb_tuesday_start_time' => '',
									'mpwpb_tuesday_end_time' => '',
									'mpwpb_tuesday_start_break_time' => '',
									'mpwpb_tuesday_end_break_time' => '',
									'mpwpb_wednesday_start_time' => '',
									'mpwpb_wednesday_end_time' => '',
									'mpwpb_wednesday_start_break_time' => '',
									'mpwpb_wednesday_end_break_time' => '',
									'mpwpb_thursday_start_time' => '',
									'mpwpb_thursday_end_time' => '',
									'mpwpb_thursday_start_break_time' => '',
									'mpwpb_thursday_end_break_time' => '',
									'mpwpb_off_days' => 'saturday,sunday',
									'mpwpb_off_dates' => [],
									'mpwpb_service_type' => 'car_wash',
									'mpwpb_service_details_active' => 'on',
									'mpwpb_service_duration_active' => 'on',
									'mpwpb_category_text' => 'Category',
									'mpwpb_sub_category_text' => 'Sub-Category',
									'mpwpb_service_text' => 'Service',
									'mpwpb_category_service' => [],
									'mpwpb_sub_category_service'=>[],
									'mpwpb_service'=>[
										[
											'name' => 'Fade Haircut',
											'price' => '10',
											'details' => 'Shampooing can strip your hair of all its natural oils, leaving it dry and brittle. Pre-pooing acts as a base or protective barrier against over-cleansing',
											'duration' => '60m',
											'icon' => '',
											'image' => '',
											'show_cat_status' => 'on',
											'parent_cat' => '',
											'sub_cat' => '',
										],
										[
											'name' => 'Taper Haircut',
											'price' => '15',
											'details' => 'Shampooing can strip your hair of all its natural oils, leaving it dry and brittle. Pre-pooing acts as a base or protective barrier against over-cleansing',
											'duration' => '60m',
											'icon' => '',
											'image' => '',
											'show_cat_status' => 'on',
											'parent_cat' =>'',
											'sub_cat' => '',
										],
										[
											'name' => 'Buzz Cut',
											'price' => '20',
											'details' => 'Shampooing can strip your hair of all its natural oils, leaving it dry and brittle. Pre-pooing acts as a base or protective barrier against over-cleansing',
											'duration' => '60m',
											'icon' => '',
											'image' => '',
											'show_cat_status' => 'on',
											'parent_cat' => '',
											'sub_cat' => '',
										],
										[
											'name' => 'Crew Cut',
											'price' => '30',
											'details' => 'Shampooing can strip your hair of all its natural oils, leaving it dry and brittle. Pre-pooing acts as a base or protective barrier against over-cleansing',
											'duration' => '60m',
											'icon' => '',
											'image' => '',
											'show_cat_status' => 'on',
											'parent_cat' => '',
											'sub_cat' => '',
										]
									],
									'mpwpb_extra_service_active' => 'on',
									'mpwpb_service_multiple_category_check' => 'on',
									'mpwpb_multiple_service_select' => 'on',
									'mpwpb_group_extra_service_active' => 'off',
									'mpwpb_extra_service' => [
										[
											'name' => 'Pre Hair Wash',
											'qty' => '10',
											'price' => '10',
											'details' => 'A pre-shampoo treatment (also referred to as a pre-poo) is exactly what it says on the tin, a treatment that is applied to the hair before jumping into the shower to give your hair a good suds and rinse.',
											'icon' => '',
											'image' => ''
										],
										[
											'name' => 'After Hair Wash',
											'qty' => '15',
											'price' => '15',
											'details' => 'A pre-shampoo treatment (also referred to as a pre-poo) is exactly what it says on the tin, a treatment that is applied to the hair before jumping into the shower to give your hair a good suds and rinse.',
											'icon' => '',
											'image' => ''
										],
										[
											'name' => 'Face Wash',
											'qty' => '45',
											'price' => '45',
											'details' => 'A pre-shampoo treatment (also referred to as a pre-poo) is exactly what it says on the tin, a treatment that is applied to the hair before jumping into the shower to give your hair a good suds and rinse.',
											'icon' => '',
											'image' => ''
										]
									],
									'mpwpb_display_slider' => 'on',
									'mpwpb_slider_images' => [0 => ''],
									'mpwpb_faq_active' => 'on',
									'mpwpb_faq' => [
										['title' => 'What services can I book?', 'content' => '<p>You can book a variety of services, including haircuts, coloring, styling, manicures, pedicures, facials, and massages.</p>'],
										['title' => 'Is the booking system easy to use?', 'content' => '<p>Yes, our online booking system is user-friendly, allowing you to navigate and schedule appointments effortlessly.</p>'],
										['title' => 'Can I choose my stylist?', 'content' => '<ul><li><p>Yes, you can select your preferred stylist based on their expertise and availability.</p></li></ul>'],
										['title' => 'What if I need to cancel or reschedule my appointment?', 'content' => '<ul><li><p>You can easily cancel or reschedule your appointment online, ideally 24 hours in advance.</p></li></ul>']
									],
									'mpwpb_features_status' => 'on',
									'mpwpb_features' => ["Wide Range of Courses", "General Health Checkups", "What types of repair services can I book?", "Wide Range of Courses", "Wide Range of Courses"],
									'mpwpb_service_overview_status' => 'on',
									'mpwpb_service_overview_content' => '<ol><li><strong>Select Service</strong>: Choose from our extensive list of salon services.</li><li><strong>Choose Date and Time</strong>: Pick a date and time that works best for you.</li><li><strong>Select Stylist</strong>: If desired, select your preferred stylist based on their availability and expertise.</li><li><strong>Confirm Appointment</strong>: Review your booking details and confirm your appointment.</li><li><strong>Receive Confirmation</strong>: Get an email or text confirmation with your appointment details.</li></ol>With our <strong>Salon Booking</strong> system, experiencing top-notch beauty and wellness services is just a few clicks away. Enjoy a seamless appointment process and a relaxing salon experience tailored to your needs!',
									'mpwpb_service_details_status' => 'on',
									'mpwpb_service_details_content' => '<strong>Salon Booking</strong> is a streamlined and user-friendly system designed to enhance your salon experience by making it easy to schedule appointments for a wide range of beauty and wellness services. Whether you need a haircut, manicure, facial, or massage, our platform simplifies the booking process, allowing you to secure your desired time and service with just a few clicks.',
									'mpwpb_service_review_ratings' => '4.8',
									'mpwpb_service_rating_scale' => 'out of 5',
									'mpwpb_service_rating_text' => 'total 200 review',
									'_thumbnail_id' => '597',
								],
							],
							1 => [
								'name' => 'Car Wash',
								'post_data' => [
									'mpwpb_shortcode_title' => 'Car Wash Service',
									'mpwpb_shortcode_sub_title' => 'Wash your car easily with affordable price',
									'mpwpb_template' => 'static.php',
									'mpwpb_service_start_date' => '2023-03-01',
									'mpwpb_time_slot_length' => '60',
									'mpwpb_capacity_per_session' => '1',
									'mpwpb_default_start_time' => '10',
									'mpwpb_default_end_time' => '16',
									'mpwpb_default_start_break_time' => '13',
									'mpwpb_default_end_break_time' => '15',
									'mpwpb_monday_start_time' => '',
									'mpwpb_monday_end_time' => '',
									'mpwpb_monday_start_break_time' => '',
									'mpwpb_monday_end_break_time' => '',
									'mpwpb_tuesday_start_time' => '',
									'mpwpb_tuesday_end_time' => '',
									'mpwpb_tuesday_start_break_time' => '',
									'mpwpb_tuesday_end_break_time' => '',
									'mpwpb_wednesday_start_time' => '',
									'mpwpb_wednesday_end_time' => '',
									'mpwpb_wednesday_start_break_time' => '',
									'mpwpb_wednesday_end_break_time' => '',
									'mpwpb_thursday_start_time' => '',
									'mpwpb_thursday_end_time' => '',
									'mpwpb_thursday_start_break_time' => '',
									'mpwpb_thursday_end_break_time' => '',
									'mpwpb_off_days' => 'saturday,sunday',
									'mpwpb_off_dates' => [],
									'mpwpb_service_details_active' => 'on',
									'mpwpb_service_duration_active' => 'on',
									'mpwpb_category_text' => 'Wash Type',
									'mpwpb_sub_category_text' => 'Car Type',
									'mpwpb_service_text' => 'Service',
									'mpwpb_category_service' => [
										['name' => 'Car Wash Polish', 'icon' => '', 'image' => ''],
										['name' => 'Car Detailing', 'icon' => '', 'image' => ''],
									],
									'mpwpb_sub_category_service'=>[
										['name' => 'Car Type SUV', 'icon' => 'fas fa-dog', 'image' => '', 'cat_id'=> 0],
										['name' => 'Car Type Zeep', 'icon' => 'fas fa-dragon', 'image' => '', 'cat_id'=> 0],
										['name' => 'Car Type Sedan', 'icon' => 'fas fa-truck-monster', 'image' => '', 'cat_id'=> 0],
										['name' => 'Car Type Sedan', 'icon' => 'fas fa-otter', 'image' => '', 'cat_id'=> 1],
									],
									'mpwpb_service'=>[
										['name' => 'Hand Wash', 'price' => 450, 'details' => 'Lorem Ipsum is simply dummy text of the printing and typesetting industry.', 'duration' => '1h 30m', 'icon' => 'fas fa-frog', 'image' => '', 'show_cat_status' => 'on', 'parent_cat' => 0, 'sub_cat' => 0],
										['name' => 'Exterior Handwax', 'price' => 200, 'details' => 'Lorem Ipsum is simply dummy text of the printing and typesetting industry.', 'duration' => '1 Hour', 'icon' => 'fas fa-paw', 'image' => '', 'show_cat_status' => 'on', 'parent_cat' => 0, 'sub_cat' => 0],
										['name' => 'Hand Wash Wax', 'price' => 650, 'details' => 'Lorem Ipsum is simply dummy text of the printing and typesetting industry.', 'duration' => '1h 30m', 'icon' => 'fas fa-dragon', 'image' => '', 'show_cat_status' => 'on', 'parent_cat' => 0, 'sub_cat' => 0],
										['name' => 'Hand Wash', 'price' => 450, 'details' => 'Lorem Ipsum is simply dummy text.', 'duration' => '', 'icon' => 'fas fa-spider', 'image' => '', 'show_cat_status' => 'on', 'parent_cat' => 0, 'sub_cat' => 1],
										['name' => 'Exterior Handwax', 'price' => 600, 'details' => 'Lorem Ipsum is simply dummy text.', 'duration' => '', 'icon' => 'fas fa-crow', 'image' => '', 'show_cat_status' => 'on', 'parent_cat' => 0, 'sub_cat' => 1],
										['name' => 'Hand Wash Wax', 'price' => 500, 'details' => 'Lorem Ipsum is simply dummy text.', 'duration' => '', 'icon' => 'fas fa-dog', 'image' => '', 'show_cat_status' => 'on', 'parent_cat' => 0, 'sub_cat' => 1],
										['name' => 'Standard Interior', 'price' => '750', 'details' => 'Lorem Ipsum is simply dummy text.', 'duration' => '1h 30m', 'icon' => 'fas fa-taxi', 'image' => '', 'show_cat_status' => 'on', 'parent_cat' => 1, 'sub_cat' => 0],
										['name' => 'Premium Interior', 'price' => '600', 'details' => 'Lorem Ipsum is simply dummy text.', 'duration' => '1h 30m', 'icon' => 'fas fa-church', 'image' => '', 'show_cat_status' => 'on', 'parent_cat' => 1, 'sub_cat' => 0],
										['name' => 'Complete Detail', 'price' => '500', 'details' => 'Lorem Ipsum is simply dummy text.', 'duration' => '1h 30m', 'icon' => 'fas fa-place-of-worship', 'image' => '', 'show_cat_status' => 'on', 'parent_cat' => 1, 'sub_cat' => 0],
									],
									'mpwpb_extra_service_active' => 'on',
									'mpwpb_service_multiple_category_check' => 'on',
									'mpwpb_multiple_service_select' => 'on',
									'mpwpb_group_extra_service_active' => 'off',
									'mpwpb_extra_service' => [
										['name' => 'Tyre Pressure Checking', 'qty' => 10, 'price' => 10, 'details' => 'A gentle but detailed hand wash procedure.', 'icon' => 'fas fa-tractor', 'image' => ''],
										['name' => 'Tyre Changing', 'qty' => 5, 'price' => 5, 'details' => 'A gentle but detailed hand wash procedure.', 'icon' => 'fas fa-torii-gate', 'image' => ''],
										['name' => 'Odor Removal', 'qty' => 10, 'price' => 10, 'details' => 'A gentle but detailed hand wash procedure.', 'icon' => 'fas fa-user-secret', 'image' => ''],
									],
									'mpwpb_display_slider' => 'on',
									'mpwpb_slider_images' => [0 => ''],
									'mpwpb_faq_active' => 'on',
									'mpwpb_faq' => [
										['title' => 'What types of car wash services do you offer?', 'content' => ''],
										['title' => 'Do I need an appointment for a car wash?', 'content' => ''],
										['title' => 'How long does a car wash take?', 'content' => ''],
										['title' => 'Do you offer mobile car wash services?', 'content' => ''],
										['title' => "What's the difference between a regular wash and detailing?", 'content' => ''],
									],
									'mpwpb_features_status' => 'on',
									'mpwpb_features' => ["Hand Wash Expertise", "Eco-Friendly Cleaning Products", "Mobile Car Wash Convenience", "Full Detailing Services"],
									'mpwpb_service_overview_status' => 'on',
									'mpwpb_service_overview_content' => 'Our car wash service is dedicated to providing top-tier care for your vehicle, offering a wide range of cleaning options to suit all needs.',
									'mpwpb_service_details_status' => 'on',
									'mpwpb_service_details_content' => '<strong>Why Choose Us?</strong><ul><li><strong>Eco-Friendly Products:</strong> We use biodegradable, non-toxic cleaning agents.</li><li><strong>Experienced Staff:</strong> Our skilled technicians are trained to handle all types of vehicles with care and precision.</li><li><strong>State-of-the-Art Equipment:</strong> We use modern tools and techniques.</li></ul>',
									'mpwpb_service_review_ratings' => '4.5',
									'mpwpb_service_rating_scale' => 'out of 5',
									'mpwpb_service_rating_text' => '2888 total customer reviews',
									'_thumbnail_id' => '1431',
								],
							],
							2 => [
								'name' => 'Repair service Booking Online',
								'post_data' => [
									'mpwpb_shortcode_title' => 'Vehicle Repair Service',
									'mpwpb_shortcode_sub_title' => 'Repair your vehicle easily with affordable price',
									'mpwpb_template' => 'static.php',
									'mpwpb_service_start_date' => '2023-02-01',
									'mpwpb_time_slot_length' => '60',
									'mpwpb_capacity_per_session' => '1',
									'mpwpb_default_start_time' => '10',
									'mpwpb_default_end_time' => '18',
									'mpwpb_default_start_break_time' => '13',
									'mpwpb_default_end_break_time' => '15',
									'mpwpb_off_days' => 'saturday,sunday',
									'mpwpb_off_dates' => [],
									'mpwpb_service_details_active' => 'on',
									'mpwpb_service_duration_active' => 'on',
									'mpwpb_category_text' => 'Service Type',
									'mpwpb_sub_category_text' => 'Sub-Category',
									'mpwpb_service_text' => 'Service Details',
									'mpwpb_category_service' => [
										['name' => 'Car Maintenance', 'icon' => '', 'image' => ''],
										['name' => 'Car Repair', 'icon' => '', 'image' => ''],
									],
									'mpwpb_sub_category_service'=>[],
									'mpwpb_service'=>[
										['name' => 'Auto Maintenance Services', 'price' => '500', 'details' => 'Lorem Ipsum is simply dummy text.', 'duration' => '1 hour', 'icon' => 'fas fa-air-freshener', 'image' => '', 'show_cat_status' => 'on', 'parent_cat' => 0, 'sub_cat' => ''],
										['name' => 'Oil Filter Change', 'price' => '300', 'details' => 'Lorem Ipsum is simply dummy text.', 'duration' => '1 hour', 'icon' => 'fas fa-tape', 'image' => '', 'show_cat_status' => 'on', 'parent_cat' => 0, 'sub_cat' => ''],
										['name' => 'Engine Performance', 'price' => '300', 'details' => 'Lorem Ipsum is simply dummy text.', 'duration' => '30m', 'icon' => 'fas fa-truck-monster', 'image' => '', 'show_cat_status' => 'on', 'parent_cat' => 0, 'sub_cat' => ''],
										['name' => 'Brake Repair Pads Rotors', 'price' => '200', 'details' => 'Lorem Ipsum is simply dummy text.', 'duration' => '30m', 'icon' => '', 'image' => '', 'show_cat_status' => 'on', 'parent_cat' => 1, 'sub_cat' => ''],
										['name' => 'Air Conditioning Services', 'price' => '100', 'details' => 'Lorem Ipsum is simply dummy text.', 'duration' => '30m', 'icon' => '', 'image' => '', 'show_cat_status' => 'on', 'parent_cat' => 1, 'sub_cat' => ''],
										['name' => 'Body Repair Painting', 'price' => '30', 'details' => 'Lorem Ipsum is simply dummy text.', 'duration' => '30m', 'icon' => '', 'image' => '', 'show_cat_status' => 'on', 'parent_cat' => 1, 'sub_cat' => ''],
									],
									'mpwpb_extra_service_active' => 'on',
									'mpwpb_service_multiple_category_check' => 'on',
									'mpwpb_multiple_service_select' => 'on',
									'mpwpb_group_extra_service_active' => 'off',
									'mpwpb_extra_service' => [
										['name' => 'Driver Seating Chair', 'qty' => '200', 'price' => '200', 'details' => 'Lorem Ipsum is simply dummy text.', 'icon' => 'fas fa-ship', 'image' => ''],
										['name' => 'Lunch box for driver', 'qty' => '300', 'price' => '300', 'details' => 'Lorem Ipsum is simply dummy text.', 'icon' => 'fas fa-hamburger', 'image' => ''],
									],
									'mpwpb_display_slider' => 'on',
									'mpwpb_slider_images' => [0 => ''],
									'mpwpb_faq_active' => 'on',
									'mpwpb_faq' => [
										['title' => 'What types of repair services can I book?', 'content' => '<p>We offer a wide range of repair services, including appliance repairs, electronics troubleshooting, plumbing fixes, HVAC servicing, and automotive repairs.</p>'],
										['title' => 'How do I book a repair service?', 'content' => '<p>Booking a repair service is simple! Just visit our website, select the type of service you need, choose your preferred date and time.</p>'],
										['title' => 'Is there a fee for booking an appointment?', 'content' => '<p>There is no fee for booking an appointment. You will only be charged for the service provided once the repair is completed.</p>'],
										['title' => 'Can I reschedule or cancel my appointment?', 'content' => '<p>Yes, you can reschedule or cancel your appointment through our online booking system. Please do so at least 24 hours in advance.</p>'],
									],
									'mpwpb_features_status' => 'on',
									'mpwpb_features' => ['Wide Range of Courses', 'General Health Checkups', 'What types of repair services can I book?', 'General Health Checkups'],
									'mpwpb_service_overview_status' => 'on',
									'mpwpb_service_overview_content' => 'Experience hassle-free repair services with our <strong>Repair Service Booking</strong> system.',
									'mpwpb_service_details_status' => 'on',
									'mpwpb_service_details_content' => '<ol><li><strong>Select Service</strong>: Choose the type of repair service you need.</li><li><strong>Schedule Appointment</strong>: Pick a date and time that works for you.</li><li><strong>Provide Details</strong>: Share relevant information about the repair issue.</li><li><strong>Confirmation</strong>: Receive a confirmation of your appointment via email or text.</li></ol>',
									'mpwpb_service_review_ratings' => '4.5',
									'mpwpb_service_rating_scale' => 'out of 5',
									'mpwpb_service_rating_text' => '2888 total customer reviews',
									'_thumbnail_id' => '261',
								],
							],
							3 => [
								'name' => 'Music Learning Online',
								'post_data' => [
									'mpwpb_shortcode_title' => 'Musical Service',
									'mpwpb_shortcode_sub_title' => 'Find your musical instructor easily with affordable price.',
									'mpwpb_template' => 'static.php',
									'mpwpb_service_start_date' => '2023-02-01',
									'mpwpb_time_slot_length' => '60',
									'mpwpb_capacity_per_session' => '1',
									'mpwpb_default_start_time' => '10',
									'mpwpb_default_end_time' => '18',
									'mpwpb_default_start_break_time' => '13',
									'mpwpb_default_end_break_time' => '15',
									'mpwpb_off_days' => 'saturday,sunday',
									'mpwpb_off_dates' => [],
									'mpwpb_service_details_active' => 'on',
									'mpwpb_service_duration_active' => 'on',
									'mpwpb_category_text' => 'Category',
									'mpwpb_sub_category_text' => 'Sub-Category',
									'mpwpb_service_text' => 'Service',
									'mpwpb_category_service' => [],
									'mpwpb_sub_category_service'=>[],
									'mpwpb_service'=>[
										['name' => 'Classical Class (3 Months)', 'price' => '10', 'details' => 'derived from the Latin word classics', 'duration' => '30m', 'icon' => '', 'image' => '', 'show_cat_status' => 'off', 'parent_cat' => '', 'sub_cat' => ''],
										['name' => 'Jazz Classes (2 Months)', 'price' => '15', 'details' => 'Classical music, strictly defined', 'duration' => '30m', 'icon' => '', 'image' => '', 'show_cat_status' => 'off', 'parent_cat' => '', 'sub_cat' => ''],
										['name' => 'Classical Private Tutor', 'price' => '20', 'details' => 'derived from the Latin word classics', 'duration' => '30m', 'icon' => '', 'image' => '', 'show_cat_status' => 'off', 'parent_cat' => '', 'sub_cat' => ''],
										['name' => 'Pop Songs Classes', 'price' => '30', 'details' => 'Classical music, strictly defined', 'duration' => '30m', 'icon' => '', 'image' => '', 'show_cat_status' => 'off', 'parent_cat' => '', 'sub_cat' => ''],
										['name' => 'Rock Music Piano Class', 'price' => '40', 'details' => 'derived from the Latin word classics', 'duration' => '30m', 'icon' => '', 'image' => '', 'show_cat_status' => 'off', 'parent_cat' => '', 'sub_cat' => ''],
										['name' => 'Classical Advance Class', 'price' => '78', 'details' => 'Classical music, strictly defined', 'duration' => '30m', 'icon' => '', 'image' => '', 'show_cat_status' => 'off', 'parent_cat' => '', 'sub_cat' => ''],
									],
									'mpwpb_extra_service_active' => 'off',
									'mpwpb_service_multiple_category_check' => 'on',
									'mpwpb_multiple_service_select' => 'on',
									'mpwpb_group_extra_service_active' => 'off',
									'mpwpb_extra_service' => [],
									'mpwpb_display_slider' => 'on',
									'mpwpb_slider_images' => [0 => ''],
									'mpwpb_faq_active' => 'on',
									'mpwpb_faq' => [
										['title' => 'What instruments can I learn on the platform?', 'content' => '<p>We offer a variety of instrument courses, including piano, guitar, drums, violin, flute, saxophone, and more.</p>'],
										['title' => 'Is Music Learning Online suitable for beginners?', 'content' => '<p>Absolutely! We have beginner-friendly courses for each instrument.</p>'],
										['title' => 'Do I need any prior musical knowledge to join?', 'content' => '<p>No prior experience is necessary.</p>'],
										['title' => 'How do I access the lessons?', 'content' => '<p>All lessons are available online, so you can access them anytime.</p>'],
									],
									'mpwpb_features_status' => 'on',
									'mpwpb_features' => ['Wide Range of Courses', 'Expert Instructors', 'Flexible, Self-Paced Learning', 'Interactive Tools', 'Progress Tracking'],
									'mpwpb_service_overview_status' => 'on',
									'mpwpb_service_overview_content' => '<strong>Music Learning Online</strong> is a dynamic and user-friendly platform designed to help learners of all levels develop their musical skills from anywhere in the world.',
									'mpwpb_service_details_status' => 'on',
									'mpwpb_service_details_content' => 'Discover the joy of learning music from the comfort of your home with <strong>Music Learning Online</strong>.',
									'mpwpb_service_review_ratings' => '4.5',
									'mpwpb_service_rating_scale' => 'out of 5',
									'mpwpb_service_rating_text' => '2888 total customer reviews',
									'_thumbnail_id' => '261',
								],
							],
							4 => [
								'name' => 'Medical & Dental',
								'post_data' => [
									'mpwpb_shortcode_title' => 'Medical & Dental Service',
									'mpwpb_shortcode_sub_title' => 'Choose your medical and dental services easily with affordable price.',
									'mpwpb_template' => 'static.php',
									'mpwpb_service_start_date' => '2023-02-01',
									'mpwpb_time_slot_length' => '60',
									'mpwpb_capacity_per_session' => '1',
									'mpwpb_default_start_time' => '10',
									'mpwpb_default_end_time' => '18',
									'mpwpb_default_start_break_time' => '13',
									'mpwpb_default_end_break_time' => '15',
									'mpwpb_off_days' => 'saturday,sunday',
									'mpwpb_off_dates' => [],
									'mpwpb_service_details_active' => 'on',
									'mpwpb_service_duration_active' => 'on',
									'mpwpb_category_text' => 'Category',
									'mpwpb_sub_category_text' => 'Sub-Category',
									'mpwpb_service_text' => 'Service',
									'mpwpb_category_service' => [],
									'mpwpb_sub_category_service'=>[],
									'mpwpb_service'=>[
										['name' => 'Fever', 'price' => '10', 'details' => 'Nisl tempus, sollicitudin amet.', 'duration' => '30m', 'icon' => '', 'image' => '', 'show_cat_status' => 'off', 'parent_cat' =>'', 'sub_cat' => ''],
										['name' => 'Dry Cough', 'price' => '30', 'details' => 'Nisl tempus, metus, sollicitudin amet.', 'duration' => '30m', 'icon' => '', 'image' => '', 'show_cat_status' => 'off', 'parent_cat' =>'', 'sub_cat' => ''],
										['name' => 'Shortness of Breath', 'price' => '10', 'details' => 'Nisl tempus, metus, sollicitudin amet.', 'duration' => '30m', 'icon' => '', 'image' => '', 'show_cat_status' => 'off', 'parent_cat' =>'', 'sub_cat' => ''],
										['name' => 'Aches and Pains', 'price' => '20', 'details' => 'Nisl tempus, metus, sollicitudin amet.', 'duration' => '30m', 'icon' => '', 'image' => '', 'show_cat_status' => 'off', 'parent_cat' =>'', 'sub_cat' => ''],
										['name' => 'Sore Throat', 'price' => '30', 'details' => 'Nisl tempus, metus, sollicitudin amet.', 'duration' => '30m', 'icon' => '', 'image' => '', 'show_cat_status' => 'off', 'parent_cat' =>'', 'sub_cat' => ''],
										['name' => 'Sleep Apnea', 'price' => '50', 'details' => 'Nisl tempus, metus, sollicitudin amet.', 'duration' => '60m', 'icon' => '', 'image' => '', 'show_cat_status' => 'off', 'parent_cat' =>'', 'sub_cat' => ''],
									],
									'mpwpb_extra_service_active' => 'off',
									'mpwpb_service_multiple_category_check' => 'on',
									'mpwpb_multiple_service_select' => 'on',
									'mpwpb_group_extra_service_active' => 'off',
									'mpwpb_extra_service' => [],
									'mpwpb_display_slider' => 'on',
									'mpwpb_slider_images' => [0 => ''],
									'mpwpb_faq_active' => 'on',
									'mpwpb_faq' => [
										['title' => 'What types of medical services are available?', 'content' => '<p>Medical services include primary care, emergency services, specialty care, preventive care, diagnostic services, surgical services, rehabilitation, mental health services.</p>'],
										['title' => 'How do I choose a primary care provider?', 'content' => '<p>When selecting a primary care provider, consider factors such as location, insurance coverage, provider specialties, availability, and personal recommendations.</p>'],
									],
									'mpwpb_features_status' => 'on',
									'mpwpb_features' => ['Wide Range of Courses', 'Expert Instructors', 'Flexible, Self-Paced Learning', 'Interactive Tools', 'Progress Tracking'],
									'mpwpb_service_overview_status' => 'on',
									'mpwpb_service_overview_content' => '<ul><li><strong>Comprehensive Care</strong>: Medical services cover a broad spectrum of health needs.</li><li><strong>Access to Expertise</strong>: Patients receive specialized care from qualified professionals.</li></ul>',
									'mpwpb_service_details_status' => 'on',
									'mpwpb_service_details_content' => 'Medical services are essential for maintaining health, preventing illness, and providing treatment and support for various medical conditions.',
									'mpwpb_service_review_ratings' => '4.5',
									'mpwpb_service_rating_scale' => 'out of 5',
									'mpwpb_service_rating_text' => '2888 total customer reviews',
									'_thumbnail_id' => '1450',
								],
							],
							5 => [
								'name' => 'Rent Your Dream Car for Single Day long tour',
								'post_data' => [
									'mpwpb_shortcode_title' => 'Rent-A-Car Service',
									'mpwpb_shortcode_sub_title' => 'Rent your dream car easily with affordable price',
									'mpwpb_template' => 'static.php',
									'mpwpb_service_start_date' => '2023-02-01',
									'mpwpb_time_slot_length' => '60',
									'mpwpb_capacity_per_session' => '1',
									'mpwpb_default_start_time' => '10',
									'mpwpb_default_end_time' => '18',
									'mpwpb_default_start_break_time' => '13',
									'mpwpb_default_end_break_time' => '15',
									'mpwpb_off_days' => 'saturday,sunday',
									'mpwpb_off_dates' => [],
									'mpwpb_service_details_active' => 'on',
									'mpwpb_service_duration_active' => 'on',
									'mpwpb_category_text' => 'Car Type',
									'mpwpb_sub_category_text' => 'Sub-Category',
									'mpwpb_service_text' => 'Service',
									'mpwpb_category_service' => [
										['name' => 'Economy Car', 'icon' => '', 'image' => ''],
										['name' => 'Standard Car', 'icon' => '', 'image' => ''],
										['name' => 'SUV Car', 'icon' => '', 'image' => ''],
									],
									'mpwpb_sub_category_service'=>[],
									'mpwpb_service'=>[
										['name' => 'Casinos', 'price' => '10', 'details' => '', 'duration' => '30m', 'icon' => '', 'image' => '', 'show_cat_status' => 'on', 'parent_cat' => 0, 'sub_cat' => ''],
										['name' => 'Birthdays', 'price' => '20', 'details' => '', 'duration' => '30m', 'icon' => '', 'image' => '', 'show_cat_status' => 'on', 'parent_cat' => 0, 'sub_cat' => ''],
										['name' => 'Airport Transfer', 'price' => '20', 'details' => '', 'duration' => '30m', 'icon' => '', 'image' => '', 'show_cat_status' => 'on', 'parent_cat' => 0, 'sub_cat' => ''],
										['name' => 'Weddings', 'price' => '30', 'details' => '', 'duration' => '30m', 'icon' => '', 'image' => '', 'show_cat_status' => 'on', 'parent_cat' => 1, 'sub_cat' => ''],
										['name' => 'Night Parties Long Drive', 'price' => '30', 'details' => '', 'duration' => '30m', 'icon' => '', 'image' => '', 'show_cat_status' => 'on', 'parent_cat' => 2, 'sub_cat' => ''],
									],
									'mpwpb_extra_service_active' => 'off',
									'mpwpb_service_multiple_category_check' => 'on',
									'mpwpb_multiple_service_select' => 'on',
									'mpwpb_group_extra_service_active' => 'off',
									'mpwpb_extra_service' => [],
									'mpwpb_display_slider' => 'on',
									'mpwpb_slider_images' => [0 => ''],
									'mpwpb_faq_active' => 'on',
									'mpwpb_faq' => [
										['title' => 'What types of cars are available for rent?', 'content' => '<p>We offer a wide range of luxury vehicles, including high-performance sports cars, sleek sedans, and premium SUVs.</p>'],
										['title' => 'How long can I rent a car for a day tour?', 'content' => '<p>Our day tour rentals are typically available for a 24-hour period.</p>'],
										['title' => 'Do I need a special license to rent a luxury car?', 'content' => '<p>No, a standard driver\'s license is typically sufficient.</p>'],
									],
									'mpwpb_features_status' => 'on',
									'mpwpb_features' => ["Exclusive Luxury Fleet", "Flexible Itinerary", "24-Hour Rental Period", "Hassle-Free Booking", "Insurance and Safety Coverage"],
									'mpwpb_service_overview_status' => 'on',
									'mpwpb_service_overview_content' => 'Transform your day trip into an extraordinary adventure with our exclusive <strong>Rent Your Dream Car for Day Tour</strong> service.',
									'mpwpb_service_details_status' => 'on',
									'mpwpb_service_details_content' => 'Our <strong>Rent Your Dream Car for Day Tour</strong> service is perfect for anyone looking to elevate their travel experience.',
									'mpwpb_service_review_ratings' => '4.5',
									'mpwpb_service_rating_scale' => 'out of 5',
									'mpwpb_service_rating_text' => '2888 total customer reviews',
									'_thumbnail_id' => '1450',
								],
							]
						]
					]
				];
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