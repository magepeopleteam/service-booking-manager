<?php
	/*
   * @Author 		engr.sumonazma@gmail.com
   * Copyright: 	mage-people.com
   */
	if (!defined('ABSPATH')) {
		die;
	} // Cannot access pages directly.
	if (!class_exists('MPWPB_Settings')) {
		class MPWPB_Settings {
			public function __construct() {
				add_action('add_meta_boxes', [$this, 'settings_meta']);
				add_action('save_post', array($this, 'save_settings'), 99, 1);
			}
			//************************//
			public function settings_meta() {
				$label = MPWPB_Function::get_name();
				$cpt = MPWPB_Function::get_cpt();
				add_meta_box('mp_meta_box_panel', $label . esc_html__(' Information Settings : ', 'service-booking-manager') . get_the_title(get_the_id()), array($this, 'settings'), $cpt, 'normal', 'high');
			}
			//******************************//
			public function settings() {
				$post_id = get_the_id();
				wp_nonce_field('mpwpb_nonce', 'mpwpb_nonce');
				?>
				<div class="mpwpb_style">
					<div class="mpwpb_tabs metabox">
						<div class="tabLists">
							<ul>
								<li  data-tabs-target="#mpwpb_general_info">
									<i class="fas fa-tools pe-1"></i><?php esc_html_e('General Info', 'service-booking-manager'); ?>
								</li>
								<li  data-tabs-target="#mpwpb_settings_date_time">
									<i class="fas fa-clock pe-1"></i><?php esc_html_e('Date & Time', 'service-booking-manager'); ?>
								</li>
								<li  data-tabs-target="#mpwpb_price_settings">
									<i class="fas fa-hand-holding-usd pe-1"></i><?php esc_html_e('Services & Pricing', 'service-booking-manager'); ?>
								</li>
								<li  data-tabs-target="#mpwpb_extra_service_settings">
									<i class="fas fa-funnel-dollar pe-1"></i><?php esc_html_e('Extra Service', 'service-booking-manager'); ?>
								</li>
								<li  data-tabs-target="#mpwpb_faq_settings">
									<i class="fas fa-question-circle pe-1"></i><?php esc_html_e('FAQ', 'service-booking-manager'); ?>
								</li>
								<li  data-tabs-target="#mpwpb_service_details">
									<i class="fas fa-wrench pe-1"></i><?php esc_html_e('Service Details', 'service-booking-manager'); ?>
								</li>
								<li  data-tabs-target="#mpwpb_service_settings">
                                    <i class="fa-solid fa-gear"></i><?php esc_html_e('Service Settings', 'service-booking-manager'); ?>
								</li>
								<?php do_action('add_mpwpb_settings_tab_after_date', $post_id); ?>

                                <?php
                                if ( is_plugin_active('service-booking-manager-pro/MPWPB_Plugin_Pro.php') ) {
                                ?>
                                <li  data-tabs-target="#mpwpb_staff_members">
                                    <i class="fa-solid fa-gear"></i><?php esc_html_e('Staff Member', 'service-booking-manager'); ?>
                                </li>
                                <?php }?>
							</ul>
						</div>
						<div class="tabsContent">
							<?php do_action('add_mpwpb_settings_tab_content', $post_id); ?>
							
						</div>
					</div>
				</div>
				<?php
			}
			public function save_settings($post_id) {
				if (!isset($_POST['mpwpb_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['mpwpb_nonce'])), 'mpwpb_nonce') && defined('DOING_AUTOSAVE') && DOING_AUTOSAVE && !current_user_can('edit_post', $post_id)) {
					return;
				}
				if (get_post_type($post_id) == MPWPB_Function::get_cpt()) {
					$title = isset($_POST['mpwpb_shortcode_title']) ? sanitize_text_field(wp_unslash($_POST['mpwpb_shortcode_title'])) : '';
					$sub_title = isset($_POST['mpwpb_shortcode_sub_title']) ? sanitize_text_field(wp_unslash($_POST['mpwpb_shortcode_sub_title'])) : '';
					$mpwpb_template = isset($_POST['mpwpb_template']) ? sanitize_text_field(wp_unslash($_POST['mpwpb_template'])) : 'default.php';
					update_post_meta($post_id, 'mpwpb_shortcode_title', $title);
					update_post_meta($post_id, 'mpwpb_shortcode_sub_title', $sub_title);
					update_post_meta($post_id, 'mpwpb_template', $mpwpb_template);
				}
				if (get_post_type($post_id) == MPWPB_Function::get_cpt()) {
					//************************************//
					$date_type = isset($_POST['mpwpb_date_type']) ? sanitize_text_field(wp_unslash($_POST['mpwpb_date_type'])) : '';
					update_post_meta($post_id, 'mpwpb_date_type', $date_type);
					//**********************//
					$particular_dates = isset($_POST['mpwpb_particular_dates']) ? array_map('sanitize_text_field', wp_unslash($_POST['mpwpb_particular_dates'])) : [];
					$particular = array();
					if (sizeof($particular_dates) > 0) {
						foreach ($particular_dates as $particular_date) {
							if ($particular_date) {
								$particular[] = date_i18n('Y-m-d', strtotime($particular_date));
							}
						}
					}
					update_post_meta($post_id, 'mpwpb_particular_dates', $particular);
					//*************************//
					$repeated_start_date = isset($_POST['mpwpb_repeated_start_date']) ? sanitize_text_field(wp_unslash($_POST['mpwpb_repeated_start_date'])) : date_i18n('Y-m-d', strtotime(date('Y-m-d')));
					$repeated_start_date = $repeated_start_date ? date_i18n('Y-m-d', strtotime($repeated_start_date)) : date_i18n('Y-m-d', strtotime(date('Y-m-d'))) ;
					update_post_meta($post_id, 'mpwpb_repeated_start_date', $repeated_start_date);
					$repeated_after = isset($_POST['mpwpb_repeated_after']) ? sanitize_text_field(wp_unslash($_POST['mpwpb_repeated_after'])) : 1;
					update_post_meta($post_id, 'mpwpb_repeated_after', $repeated_after);
					$active_days = isset($_POST['mpwpb_active_days']) ? sanitize_text_field(wp_unslash($_POST['mpwpb_active_days'])) : '';
					update_post_meta($post_id, 'mpwpb_active_days', $active_days);
					//**********************//
					$time_slot_length = isset($_POST['mpwpb_time_slot_length']) ? sanitize_text_field(wp_unslash($_POST['mpwpb_time_slot_length'])) : '';
					$capacity_per_session = isset($_POST['mpwpb_capacity_per_session']) ? sanitize_text_field(wp_unslash($_POST['mpwpb_capacity_per_session'])) : '';
					update_post_meta($post_id, 'mpwpb_time_slot_length', $time_slot_length);
					update_post_meta($post_id, 'mpwpb_capacity_per_session', $capacity_per_session);
					//**********************//
					$this->save_schedule($post_id, 'default');
					$days = MPWPB_Global_Function::week_day();
					foreach ($days as $key => $day) {
						$this->save_schedule($post_id, $key);
					}
					//**********************//
					$off_days = isset($_POST['mpwpb_off_days']) ? sanitize_text_field(wp_unslash($_POST['mpwpb_off_days'])):'';

					update_post_meta($post_id, 'mpwpb_off_days', $off_days);
					//**********************//
					$off_dates = isset($_POST['mpwpb_off_dates']) ? array_map('sanitize_text_field', wp_unslash($_POST['mpwpb_off_dates'])) : [];
					$_off_dates = array();
					if (sizeof($off_dates) > 0) {
						foreach ($off_dates as $off_date) {
							if ($off_date) {
								$_off_dates[] = date_i18n('Y-m-d', strtotime($off_date));
							}
						}
					}
					update_post_meta($post_id, 'mpwpb_off_dates', $_off_dates);
				}
				if (get_post_type($post_id) == MPWPB_Function::get_cpt()) {
					$mpwpb_faq_active = isset($_POST['mpwpb_faq_active']) ? sanitize_text_field(wp_unslash($_POST['mpwpb_faq_active'])) : '';
					update_post_meta($post_id, 'mpwpb_faq_active', $mpwpb_faq_active);
				}
				if (get_post_type($post_id) == MPWPB_Function::get_cpt()) {
					$slider = isset($_POST['mpwpb_display_slider']) && sanitize_text_field(wp_unslash($_POST['mpwpb_display_slider'])) ? 'on' : 'off';
					update_post_meta($post_id, 'mpwpb_display_slider', $slider);
					$images = isset($_POST['mpwpb_slider_images']) ? sanitize_text_field(wp_unslash($_POST['mpwpb_slider_images'])) : '';
					$all_images = explode(',', $images);
					update_post_meta($post_id, 'mpwpb_slider_images', $all_images);
				}
				if (get_post_type($post_id) == MPWPB_Function::get_cpt()) {
					$service_features_status = isset($_POST['mpwpb_features_status']) ? sanitize_text_field(wp_unslash($_POST['mpwpb_features_status'])) : 'off';
					$service_overview_status = isset($_POST['mpwpb_service_overview_status']) ? sanitize_text_field(wp_unslash($_POST['mpwpb_service_overview_status'])) : 'off';
					$service_details_status = isset($_POST['mpwpb_service_details_status']) ? sanitize_text_field(wp_unslash($_POST['mpwpb_service_details_status'])) : 'off';
					$service_rating = isset($_POST['mpwpb_service_review_ratings']) ? sanitize_text_field(wp_unslash($_POST['mpwpb_service_review_ratings'])) : '';
					$service_rating_scale = isset($_POST['mpwpb_service_rating_scale']) ? sanitize_text_field(wp_unslash($_POST['mpwpb_service_rating_scale'])) : '';
					$service_rating_text = isset($_POST['mpwpb_service_rating_text']) ? sanitize_text_field(wp_unslash($_POST['mpwpb_service_rating_text'])) : '';
					$service_overview_content =  isset($_POST['mpwpb_service_overview_content']) ? wp_kses_post(wp_unslash($_POST['mpwpb_service_overview_content'])):'';
					$service_details_content =  isset($_POST['mpwpb_service_details_content']) ? wp_kses_post(wp_unslash($_POST['mpwpb_service_details_content'])):'';
                    $service_multiple_category_check = isset($_POST['mpwpb_service_multiple_category_check']) ? sanitize_text_field(wp_unslash($_POST['mpwpb_service_multiple_category_check'])) : 'off';
                    $multiple_service_select = isset($_POST['mpwpb_multiple_service_select']) ? sanitize_text_field(wp_unslash($_POST['mpwpb_multiple_service_select'])) : 'off';
                    update_post_meta($post_id, 'mpwpb_features_status', $service_features_status);
					update_post_meta($post_id, 'mpwpb_service_overview_status', $service_overview_status);
					update_post_meta($post_id, 'mpwpb_service_details_status', $service_details_status);
					update_post_meta($post_id, 'mpwpb_service_overview_content', $service_overview_content);
					update_post_meta($post_id, 'mpwpb_service_details_content', $service_details_content);
					update_post_meta($post_id, 'mpwpb_service_review_ratings', $service_rating);
					update_post_meta($post_id, 'mpwpb_service_rating_scale', $service_rating_scale);
					update_post_meta($post_id, 'mpwpb_service_rating_text', $service_rating_text);
					update_post_meta($post_id, 'mpwpb_service_multiple_category_check', $service_multiple_category_check);
					update_post_meta($post_id, 'mpwpb_multiple_service_select', $multiple_service_select);
					$features = isset($_POST['mpwpb_features']) ? array_map('sanitize_text_field', wp_unslash($_POST['mpwpb_features'])) : [];
					$features_lists = array();
					if (sizeof($features) > 0) {
						foreach ($features as $feature) {
							if ($feature) {
								$features_lists[] = $feature;
							}
						}
					}
					update_post_meta($post_id, 'mpwpb_features', $features_lists);
				}
				do_action('mpwpb_settings_save', $post_id);
			}
			public function save_schedule($post_id, $day) {
				if (!isset($_POST['mpwpb_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['mpwpb_nonce'])), 'mpwpb_nonce') && defined('DOING_AUTOSAVE') && DOING_AUTOSAVE && !current_user_can('edit_post', $post_id)) {
					return;
				}
				$start_name = 'mpwpb_' . $day . '_start_time';
				$start_time = isset($_POST[$start_name]) ? sanitize_text_field(wp_unslash($_POST[$start_name])) : '';
				update_post_meta($post_id, $start_name, $start_time);
				$end_name = 'mpwpb_' . $day . '_end_time';
				$end_time = isset($_POST[$end_name]) ? sanitize_text_field(wp_unslash($_POST[$end_name])) : '';
				update_post_meta($post_id, $end_name, $end_time);
				$start_name_break = 'mpwpb_' . $day . '_start_break_time';
				$start_time_break = isset($_POST[$start_name_break]) ? sanitize_text_field(wp_unslash($_POST[$start_name_break])) : '';
				update_post_meta($post_id, $start_name_break, $start_time_break);
				$end_name_break = 'mpwpb_' . $day . '_end_break_time';
				$end_time_break = isset($_POST[$end_name_break]) ? sanitize_text_field(wp_unslash($_POST[$end_name_break])) : '';
				update_post_meta($post_id, $end_name_break, $end_time_break);
			}
			public static function description_array($key) {
				$des = array(
					'mpwpb_service_details_active' => esc_html__('By default Service Details  is OFF but you can keep it ON by switching this option', 'service-booking-manager'),
					'mpwpb_service_duration_active' => esc_html__('By default Service Duration  is ON but you can keep it OFF by switching this option', 'service-booking-manager'),
					'mpwpb_service_multi_select_active' => esc_html__('By default Multi Select  is OFF but you can keep it ON by switching this option', 'service-booking-manager'),
					'mpwpb_service_staff_active' => esc_html__('By default Staff  is OFF but you can keep it ON by switching this option', 'service-booking-manager'),
					'mpwpb_extra_service_active' => esc_html__('By default extra service  is OFF but you can keep it ON by switching this option', 'service-booking-manager'),
					//======staff==========//
					'mpwpb_staff_service_display' => esc_html__('By default staff  is OFF but you can keep it ON by switching this option', 'service-booking-manager'),
					//======Slider==========//
					'mpwpb_display_slider' => esc_html__('By default slider is ON but you can keep it off by switching this option', 'service-booking-manager'),
					'mpwpb_slider_images' => esc_html__('Please upload images for gallery', 'service-booking-manager'),
					//''          => esc_html__( '', 'service-booking-manager' ),
					'mpwpb_short_code' => esc_html__('Copty this shortcode and paste any post or page.', 'service-booking-manager'),
					'date_time_desc' => esc_html__('Date & time settings', 'service-booking-manager'),
					'general_date_time_desc' => esc_html__('Date & time settings', 'service-booking-manager'),
					'ex_service' => esc_html__('Date & time settings', 'service-booking-manager'),
					'ex_service_desc' => esc_html__('Date & time settings', 'service-booking-manager'),
				);
				$des = apply_filters('mpwpb_filter_description_array', $des);
				return $des[$key];
			}
			public static function info_text($key) {
				$data = self::description_array($key);
				if ($data) {
					 echo esc_html($data);
				}
			}
		}
		new MPWPB_Settings();
	}