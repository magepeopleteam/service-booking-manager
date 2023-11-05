<?php
	/*
   * @Author 		engr.sumonazma@gmail.com
   * Copyright: 	mage-people.com
   */
	if (!defined('ABSPATH')) {
		die;
	} // Cannot access pages directly.
	if (!class_exists('MPWPB_Settings_Global')) {
		class MPWPB_Settings_Global {
			protected $settings_api;
			public function __construct() {
				$this->settings_api = new MAGE_Setting_API;
				add_action('admin_menu', array($this, 'global_settings_menu'));
				add_action('admin_init', array($this, 'admin_init'));
				add_filter('mp_settings_sec_reg', array($this, 'settings_sec_reg'), 10);
				add_filter('mp_settings_sec_fields', array($this, 'settings_sec_fields'), 10);
			}
			public function global_settings_menu() {
				$label = MPWPB_Function::get_name();
				$cpt = MPWPB_Function::get_cpt();
				add_submenu_page('edit.php?post_type=' . $cpt, $label . esc_html__(' Settings', 'service-booking-manager'), $label . esc_html__(' Settings', 'service-booking-manager'), 'manage_options', 'mpwpb_settings_page', array($this, 'settings_page'));
			}
			public function settings_page() {
				$label = MPWPB_Function::get_name();
				?>
				<div class="mpStyle mp_global_settings">
					<div class="_dShadow_6 mpPanel">
						<div class="mpPanelHeader"><?php echo esc_html($label . esc_html__(' Global Settings', 'service-booking-manager')); ?></div>
						<div class="mpPanelBody mp_zero">
							<div class="mpTabs leftTabs">
								<?php $this->settings_api->show_navigation(); ?>
								<div class="tabsContent">
									<?php $this->settings_api->show_forms(); ?>
								</div>
							</div>
						</div>
					</div>
				</div>
				<?php
			}
			public function admin_init() {
				$this->settings_api->set_sections($this->get_settings_sections());
				$this->settings_api->set_fields($this->get_settings_fields());
				$this->settings_api->admin_init();
			}
			public function get_settings_sections() {
				$sections = array();
				return apply_filters('mp_settings_sec_reg', $sections);
			}
			public function get_settings_fields() {
				$settings_fields = array();
				return apply_filters('mp_settings_sec_fields', $settings_fields);
			}
			public function settings_sec_reg($default_sec): array {
				$sections = array(
					array(
						'id' => 'mpwpb_general_settings',
						'icon' => 'fas fa-sliders-h',
						'title' => __('General Settings', 'service-booking-manager')
					)
				);
				return array_merge($default_sec, $sections);
			}
			public function settings_sec_fields($default_fields): array {
				$label = MPWPB_Function::get_name();
				$settings_fields = array(
					'mpwpb_general_settings' => apply_filters('filter_mpwpb_general_settings', array(
						array(
							'name' => 'label',
							'label' => $label . ' ' . esc_html__('Label', 'service-booking-manager'),
							'desc' => esc_html__('If you like to change the label in the dashboard menu, you can change it here.', 'service-booking-manager'),
							'type' => 'text',
							'default' => 'service-booking-manager'
						),
						array(
							'name' => 'slug',
							'label' => $label . ' ' . esc_html__('Slug', 'service-booking-manager'),
							'desc' => esc_html__('Please enter the slug name you want. Remember, after changing this slug; you need to flush permalink; go to', 'service-booking-manager') . '<strong>' . esc_html__('Settings-> Permalinks', 'service-booking-manager') . '</strong> ' . esc_html__('hit the Save Settings button.', 'service-booking-manager'),
							'type' => 'text',
							'default' => 'service-booking-manager'
						),
						array(
							'name' => 'icon',
							'label' => $label . ' ' . esc_html__('Icon', 'service-booking-manager'),
							'desc' => esc_html__('If you want to change the  icon in the dashboard menu, you can change it from here, and the Dashboard icon only supports the Dashicons, So please go to ', 'service-booking-manager') . '<a href=https://developer.wordpress.org/resource/dashicons/#calendar-alt target=_blank>' . esc_html__('Dashicons Library.', 'service-booking-manager') . '</a>' . esc_html__('and copy your icon code and paste it here.', 'service-booking-manager'),
							'type' => 'text',
							'default' => 'dashicons-list-view'
						),
						array(
							'name' => 'category_label',
							'label' => $label . ' ' . esc_html__('Category Label', 'service-booking-manager'),
							'desc' => esc_html__('If you want to change the  category label in the dashboard menu, you can change it here.', 'service-booking-manager'),
							'type' => 'text',
							'default' => esc_html__('Category', 'service-booking-manager')
						),
						array(
							'name' => 'category_slug',
							'label' => $label . ' ' . esc_html__('Category Slug', 'service-booking-manager'),
							'desc' => esc_html__('Please enter the slug name you want for  category. Remember after change this slug you need to flush permalink, Just go to  ', 'service-booking-manager') . '<strong>' . esc_html__('Settings-> Permalinks', 'service-booking-manager') . '</strong> ' . esc_html__('hit the Save Settings button.', 'service-booking-manager'),
							'type' => 'text',
							'default' => 'service-category'
						),
						array(
							'name' => 'organizer_label',
							'label' => $label . ' ' . esc_html__('Organizer Label', 'service-booking-manager'),
							'desc' => esc_html__('If you want to change the   category label in the dashboard menu you can change here', 'service-booking-manager'),
							'type' => 'text',
							'default' => 'Organizer'
						),
						array(
							'name' => 'organizer_slug',
							'label' => $label . ' ' . esc_html__('Organizer Slug', 'service-booking-manager'),
							'desc' => esc_html__('Please enter the slug name you want for the   organizer. Remember, after changing this slug, you need to flush the permalinks. Just go to ', 'service-booking-manager') . '<strong>' . esc_html__('Settings-> Permalinks', 'service-booking-manager') . '</strong> ' . esc_html__('hit the Save Settings button.', 'service-booking-manager'),
							'type' => 'text',
							'default' => 'service-organizer'
						),
						array(
							'name' => 'category_text',
							'label' => $label . ' ' . esc_html__('Product Category Text', 'service-booking-manager'),
							'desc' => esc_html__('If you want to change the  Product Category Text, you can change it here.', 'service-booking-manager'),
							'type' => 'text',
							'default' => esc_html__('Category', 'service-booking-manager')
						),
						array(
							'name' => 'sub_category_text',
							'label' => $label . ' ' . esc_html__('Product Sub-Category Text', 'service-booking-manager'),
							'desc' => esc_html__('If you want to change the  Product Sub-Category Text, you can change it here.', 'service-booking-manager'),
							'type' => 'text',
							'default' => esc_html__('Sub-Category', 'service-booking-manager')
						),
						array(
							'name' => 'service_text',
							'label' => $label . ' ' . esc_html__('Product ServiceText', 'service-booking-manager'),
							'desc' => esc_html__('If you want to change the  Product Service Text, you can change it here.', 'service-booking-manager'),
							'type' => 'text',
							'default' => esc_html__('Service', 'service-booking-manager')
						),
					))
				);
				return array_merge($default_fields, $settings_fields);
			}
		}
		new  MPWPB_Settings_Global();
	}