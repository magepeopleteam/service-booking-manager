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
				$this->settings_api = new MPWPB_Setting_API;
				add_action('admin_menu', array($this, 'global_settings_menu'));
				add_action('admin_init', array($this, 'admin_init'));
				add_filter('mpwpb_settings_sec_reg', array($this, 'settings_sec_reg'));
				add_filter('mpwpb_settings_sec_reg', array($this, 'global_sec_reg'), 90);
				add_filter('mpwpb_settings_sec_fields', array($this, 'settings_sec_fields'), 10);
				add_action('wsa_form_bottom_mpwpb_license_settings', [$this, 'license_settings'], 5);
			}
			public function global_settings_menu() {
				$label = MPWPB_Function::get_name();
				$cpt = MPWPB_Function::get_cpt();
				add_submenu_page('edit.php?post_type=' . $cpt, $label . esc_html__(' Settings', 'service-booking-manager'), $label . esc_html__(' Settings', 'service-booking-manager'), 'manage_options', 'mpwpb_settings_page', array($this, 'settings_page'));
			}
			public function settings_page() {
				$label = MPWPB_Function::get_name();
				?>
				<div class="mpwpb_style mpwpb_global_settings">
					<div class="_dShadow_6 mpPanel">
						<div class="mpPanelHeader"><?php echo esc_html($label . esc_html__(' Global Settings', 'service-booking-manager')); ?></div>
						<div class="mpPanelBody mp_zero">
							<div class="mpwpb_tabs leftTabs">
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
				return apply_filters('mpwpb_settings_sec_reg', $sections);
			}
			public function get_settings_fields() {
				$settings_fields = array();
				return apply_filters('mpwpb_settings_sec_fields', $settings_fields);
			}
			public function settings_sec_reg($default_sec): array {
				$sections = array(
					array(
						'id' => 'mpwpb_general_settings',
						'icon' => 'fas fa-sliders-h',
						'title' => __('General Settings', 'service-booking-manager')
					),
					array(
						'id' => 'mpwpb_global_settings',
						'title' => esc_html__('Global Settings', 'service-booking-manager')
					)
				);
				return array_merge($default_sec, $sections);
			}
			public function global_sec_reg($default_sec): array {
				$sections = array(

					array(
						'id' => 'mpwpb_slider_settings',
						'title' => esc_html__('Slider Settings', 'service-booking-manager')
					),
					array(
						'id' => 'mpwpb_style_settings',
						'title' => esc_html__('Style Settings', 'service-booking-manager')
					),
					array(
						'id' => 'mpwpb_custom_css',
						'title' => esc_html__('Custom CSS', 'service-booking-manager')
					),
					array(
						'id' => 'mpwpb_license_settings',
						'title' => esc_html__('Mage-People License', 'service-booking-manager')
					)
				);
				return array_merge($default_sec, $sections);
			}
			public function settings_sec_fields($default_fields): array {
				$label = MPWPB_Function::get_name();
				$current_date = current_time('Y-m-d');
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
						array(
							'name' => 'single_page_checkout',
							'label' => esc_html__('Disable single page checkout', 'service-booking-manager'),
							'desc' => esc_html__('If you want to disable single page checkout, please select Yes.That means active woocommerce checkout page active', 'service-booking-manager'),
							'type' => 'select',
							'default' => 'no',
							'options' => array(
								'yes' => esc_html__('Yes', 'service-booking-manager'),
								'no' => esc_html__('No', 'service-booking-manager')
							)
						),
						array(
							'name' => 'buffer_time',
							'label' => esc_html__('Buffer Time', 'service-booking-manager'),
							'desc' => esc_html__('Please enter here  buffer time in minute. By default is 0', 'service-booking-manager'),
							'type' => 'number',
							'default' => 0,
							'placeholder' => esc_html__('Ex:50', 'service-booking-manager'),
						),
                        array(
                            'name' => 'booking_widget_sticky_on_scrolling',
                            'label' => esc_html__('Booking widget sticky on scrolling', 'service-booking-manager'),
                            'desc'   => 'A booking widget sticky on scrolling refers to a booking form or section that remains visible in a fixed position while the user scrolls the page. Instead of disappearing as the user scrolls down',
                            'type' => 'select',
                            'default' => 'yes',
                            'options' => array(
                                'yes' => esc_html__('Yes', 'service-booking-manager'),
                                'no' => esc_html__('No', 'service-booking-manager')
                            )
                        ),
					)),
					'mpwpb_global_settings' => apply_filters('filter_mpwpb_global_settings', array(
						array(
							'name' => 'disable_block_editor',
							'label' => esc_html__('Disable Block/Gutenberg Editor', 'service-booking-manager'),
							'desc' => esc_html__('If you want to disable WordPress\'s new Block/Gutenberg editor, please select Yes.', 'service-booking-manager'),
							'type' => 'select',
							'default' => 'yes',
							'options' => array(
								'yes' => esc_html__('Yes', 'service-booking-manager'),
								'no' => esc_html__('No', 'service-booking-manager')
							)
						),
						array(
							'name' => 'set_book_status',
							'label' => esc_html__('Seat Booked Status', 'service-booking-manager'),
							'desc' => esc_html__('Please Select when and which order status Seat Will be Booked/Reduced.', 'service-booking-manager'),
							'type' => 'multicheck',
							'default' => array(
								'processing' => 'processing',
								'completed' => 'completed'
							),
							'options' => array(
								'on-hold' => esc_html__('On Hold', 'service-booking-manager'),
								'pending' => esc_html__('Pending', 'service-booking-manager'),
								'processing' => esc_html__('Processing', 'service-booking-manager'),
								'completed' => esc_html__('Completed', 'service-booking-manager'),
							)
						),
						array(
							'name' => 'date_format',
							'label' => esc_html__('Date Picker Format', 'service-booking-manager'),
							'desc' => esc_html__('If you want to change Date Picker Format, please select format. Default  is D d M , yy.', 'service-booking-manager'),
							'type' => 'select',
							'default' => 'D d M , yy',
							'options' => array(
								'yy-mm-dd' => $current_date,
								'yy/mm/dd' => date_i18n('Y/m/d', strtotime($current_date)),
								'yy-dd-mm' => date_i18n('Y-d-m', strtotime($current_date)),
								'yy/dd/mm' => date_i18n('Y/d/m', strtotime($current_date)),
								'dd-mm-yy' => date_i18n('d-m-Y', strtotime($current_date)),
								'dd/mm/yy' => date_i18n('d/m/Y', strtotime($current_date)),
								'mm-dd-yy' => date_i18n('m-d-Y', strtotime($current_date)),
								'mm/dd/yy' => date_i18n('m/d/Y', strtotime($current_date)),
								'd M , yy' => date_i18n('j M , Y', strtotime($current_date)),
								'D d M , yy' => date_i18n('D j M , Y', strtotime($current_date)),
								'M d , yy' => date_i18n('M  j, Y', strtotime($current_date)),
								'D M d , yy' => date_i18n('D M  j, Y', strtotime($current_date)),
							)
						),
						array(
							'name' => 'date_format_short',
							'label' => esc_html__('Short Date  Format', 'service-booking-manager'),
							'desc' => esc_html__('If you want to change Short Date  Format, please select format. Default  is M , Y.', 'service-booking-manager'),
							'type' => 'select',
							'default' => 'M , Y',
							'options' => array(
								'D , M d' => date_i18n('D , M d', strtotime($current_date)),
								'M , Y' => date_i18n('M , Y', strtotime($current_date)),
								'M , y' => date_i18n('M , y', strtotime($current_date)),
								'M - Y' => date_i18n('M - Y', strtotime($current_date)),
								'M - y' => date_i18n('M - y', strtotime($current_date)),
								'F , Y' => date_i18n('F , Y', strtotime($current_date)),
								'F , y' => date_i18n('F , y', strtotime($current_date)),
								'F - Y' => date_i18n('F - y', strtotime($current_date)),
								'F - y' => date_i18n('F - y', strtotime($current_date)),
								'm - Y' => date_i18n('m - Y', strtotime($current_date)),
								'm - y' => date_i18n('m - y', strtotime($current_date)),
								'm , Y' => date_i18n('m , Y', strtotime($current_date)),
								'm , y' => date_i18n('m , y', strtotime($current_date)),
								'F' => date_i18n('F', strtotime($current_date)),
								'm' => date_i18n('m', strtotime($current_date)),
								'M' => date_i18n('M', strtotime($current_date)),
							)
						),
					)),
					'mpwpb_slider_settings' => array(
						array(
							'name' => 'slider_type',
							'label' => esc_html__('Slider Type', 'service-booking-manager'),
							'desc' => esc_html__('Please Select Slider Type Default Slider', 'service-booking-manager'),
							'type' => 'select',
							'default' => 'slider',
							'options' => array(
								'slider' => esc_html__('Slider', 'service-booking-manager'),
								'single_image' => esc_html__('Post Thumbnail', 'service-booking-manager')
							)
						),
						array(
							'name' => 'slider_style',
							'label' => esc_html__('Slider Style', 'service-booking-manager'),
							'desc' => esc_html__('Please Select Slider Style Default Style One', 'service-booking-manager'),
							'type' => 'select',
							'default' => 'style_1',
							'options' => array(
								'style_1' => esc_html__('Style One', 'service-booking-manager'),
								'style_2' => esc_html__('Style Two', 'service-booking-manager'),
							)
						),
						array(
							'name' => 'indicator_visible',
							'label' => esc_html__('Slider Indicator Visible?', 'service-booking-manager'),
							'desc' => esc_html__('Please Select Slider Indicator Visible or Not? Default ON', 'service-booking-manager'),
							'type' => 'select',
							'default' => 'on',
							'options' => array(
								'on' => esc_html__('ON', 'service-booking-manager'),
								'off' => esc_html__('Off', 'service-booking-manager')
							)
						),
						array(
							'name' => 'indicator_type',
							'label' => esc_html__('Slider Indicator Type', 'service-booking-manager'),
							'desc' => esc_html__('Please Select Slider Indicator Type Default Icon', 'service-booking-manager'),
							'type' => 'select',
							'default' => 'icon',
							'options' => array(
								'icon' => esc_html__('Icon Indicator', 'service-booking-manager'),
								'image' => esc_html__('image Indicator', 'service-booking-manager')
							)
						),
						array(
							'name' => 'showcase_visible',
							'label' => esc_html__('Slider Showcase Visible?', 'service-booking-manager'),
							'desc' => esc_html__('Please Select Slider Showcase Visible or Not? Default ON', 'service-booking-manager'),
							'type' => 'select',
							'default' => 'on',
							'options' => array(
								'on' => esc_html__('ON', 'service-booking-manager'),
								'off' => esc_html__('Off', 'service-booking-manager')
							)
						),
						array(
							'name' => 'showcase_position',
							'label' => esc_html__('Slider Showcase Position', 'service-booking-manager'),
							'desc' => esc_html__('Please Select Slider Showcase Position Default Right', 'service-booking-manager'),
							'type' => 'select',
							'default' => 'right',
							'options' => array(
								'top' => esc_html__('At Top Position', 'service-booking-manager'),
								'right' => esc_html__('At Right Position', 'service-booking-manager'),
								'bottom' => esc_html__('At Bottom Position', 'service-booking-manager'),
								'left' => esc_html__('At Left Position', 'service-booking-manager')
							)
						),
						array(
							'name' => 'popup_image_indicator',
							'label' => esc_html__('Slider Popup Image Indicator', 'service-booking-manager'),
							'desc' => esc_html__('Please Select Slider Popup Indicator Image ON or Off? Default ON', 'service-booking-manager'),
							'type' => 'select',
							'default' => 'on',
							'options' => array(
								'on' => esc_html__('ON', 'service-booking-manager'),
								'off' => esc_html__('Off', 'service-booking-manager')
							)
						),
						array(
							'name' => 'popup_icon_indicator',
							'label' => esc_html__('Slider Popup Icon Indicator', 'service-booking-manager'),
							'desc' => esc_html__('Please Select Slider Popup Indicator Icon ON or Off? Default ON', 'service-booking-manager'),
							'type' => 'select',
							'default' => 'on',
							'options' => array(
								'on' => esc_html__('ON', 'service-booking-manager'),
								'off' => esc_html__('Off', 'service-booking-manager')
							)
						)
					),
					'mpwpb_style_settings' => apply_filters('filter_mpwpb_style_settings', array(
						array(
							'name' => 'theme_color',
							'label' => esc_html__('Theme Color', 'service-booking-manager'),
							'desc' => esc_html__('Select Default Theme Color', 'service-booking-manager'),
							'type' => 'color',
							'default' => '#0793C9'
						),
						array(
							'name' => 'theme_alternate_color',
							'label' => esc_html__('Theme Alternate Color', 'service-booking-manager'),
							'desc' => esc_html__('Select Default Theme Alternate  Color that means, if background theme color then it will be text color.', 'service-booking-manager'),
							'type' => 'color',
							'default' => '#fff'
						),
						array(
							'name' => 'default_text_color',
							'label' => esc_html__('Default Text Color', 'service-booking-manager'),
							'desc' => esc_html__('Select Default Text  Color.', 'service-booking-manager'),
							'type' => 'color',
							'default' => '#000'
						),
						array(
							'name' => 'default_font_size',
							'label' => esc_html__('Default Font Size', 'service-booking-manager'),
							'desc' => esc_html__('Type Default Font Size(in PX Unit).', 'service-booking-manager'),
							'type' => 'number',
							'default' => '15'
						),
						array(
							'name' => 'font_size_h1',
							'label' => esc_html__('Font Size h1 Title', 'service-booking-manager'),
							'desc' => esc_html__('Type Font Size Main Title(in PX Unit).', 'service-booking-manager'),
							'type' => 'number',
							'default' => '35'
						),
						array(
							'name' => 'font_size_h2',
							'label' => esc_html__('Font Size h2 Title', 'service-booking-manager'),
							'desc' => esc_html__('Type Font Size h2 Title(in PX Unit).', 'service-booking-manager'),
							'type' => 'number',
							'default' => '25'
						),
						array(
							'name' => 'font_size_h3',
							'label' => esc_html__('Font Size h3 Title', 'service-booking-manager'),
							'desc' => esc_html__('Type Font Size h3 Title(in PX Unit).', 'service-booking-manager'),
							'type' => 'number',
							'default' => '22'
						),
						array(
							'name' => 'font_size_h4',
							'label' => esc_html__('Font Size h4 Title', 'service-booking-manager'),
							'desc' => esc_html__('Type Font Size h4 Title(in PX Unit).', 'service-booking-manager'),
							'type' => 'number',
							'default' => '20'
						),
						array(
							'name' => 'font_size_h5',
							'label' => esc_html__('Font Size h5 Title', 'service-booking-manager'),
							'desc' => esc_html__('Type Font Size h5 Title(in PX Unit).', 'service-booking-manager'),
							'type' => 'number',
							'default' => '18'
						),
						array(
							'name' => 'font_size_h6',
							'label' => esc_html__('Font Size h6 Title', 'service-booking-manager'),
							'desc' => esc_html__('Type Font Size h6 Title(in PX Unit).', 'service-booking-manager'),
							'type' => 'number',
							'default' => '16'
						),
						array(
							'name' => 'button_font_size',
							'label' => esc_html__('Button Font Size ', 'service-booking-manager'),
							'desc' => esc_html__('Type Font Size Button(in PX Unit).', 'service-booking-manager'),
							'type' => 'number',
							'default' => '18'
						),
						array(
							'name' => 'button_color',
							'label' => esc_html__('Button Text Color', 'service-booking-manager'),
							'desc' => esc_html__('Select Button Text  Color.', 'service-booking-manager'),
							'type' => 'color',
							'default' => '#FFF'
						),
						array(
							'name' => 'button_bg',
							'label' => esc_html__('Button Background Color', 'service-booking-manager'),
							'desc' => esc_html__('Select Button Background  Color.', 'service-booking-manager'),
							'type' => 'color',
							'default' => '#222'
						),
						array(
							'name' => 'font_size_label',
							'label' => esc_html__('Label Font Size ', 'service-booking-manager'),
							'desc' => esc_html__('Type Font Size Label(in PX Unit).', 'service-booking-manager'),
							'type' => 'number',
							'default' => '18'
						),
						array(
							'name' => 'warning_color',
							'label' => esc_html__('Warning Color', 'service-booking-manager'),
							'desc' => esc_html__('Select Warning  Color.', 'service-booking-manager'),
							'type' => 'color',
							'default' => '#E67C30'
						),
						array(
							'name' => 'section_bg',
							'label' => esc_html__('Section Background color', 'service-booking-manager'),
							'desc' => esc_html__('Select Background  Color.', 'service-booking-manager'),
							'type' => 'color',
							'default' => '#FAFCFE'
						),
					)),
					'mpwpb_custom_css' => apply_filters('filter_mpwpb_custom_css', array(
						array(
							'name' => 'custom_css',
							'label' => esc_html__('Custom CSS', 'service-booking-manager'),
							'desc' => esc_html__('Write Your Custom CSS Code Here', 'service-booking-manager'),
							'type' => 'textarea',
						)
					))
				);
				return array_merge($default_fields, $settings_fields);
			}
			public function license_settings() {
				?>
                <div class="mpwpb_license_settings">
                    <h3><?php esc_html_e('Mage-People License', 'service-booking-manager'); ?></h3>
                    <div class="_dFlex">
                        <span class="fas fa-info-circle _mR_xs"></span>
                        <i><?php esc_html_e('Thanking you for using our Mage-People plugin. Our some plugin  free and no license is required. We have some Additional addon to enhance feature of this plugin functionality. If you have any addon you need to enter a valid license for that plugin below.', 'service-booking-manager'); ?>                    </i>
                    </div>
                    <div class="divider"></div>
                    <div class="dLayout mp_basic_license_area">
                        <table>
                            <thead>
                            <tr>
                                <th colspan="4"><?php esc_html_e('Plugin Name', 'service-booking-manager'); ?></th>
                                <th><?php esc_html_e('Type', 'service-booking-manager'); ?></th>
                                <th><?php esc_html_e('Order No', 'service-booking-manager'); ?></th>
                                <th colspan="2"><?php esc_html_e('Expire on', 'service-booking-manager'); ?></th>
                                <th colspan="3"><?php esc_html_e('License Key', 'service-booking-manager'); ?></th>
                                <th><?php esc_html_e('Status', 'service-booking-manager'); ?></th>
                                <th colspan="2"><?php esc_html_e('Action', 'service-booking-manager'); ?></th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr>
                                <th colspan="4" class="_textLeft"><?php esc_html_e('Service Booking & Scheduling Solution | All-in-one Booking Systems', 'service-booking-manager'); ?></th>
                                <th><?php esc_html_e('Free','service-booking-manager'); ?></th>
                                <th></th>
                                <th colspan="2"><?php esc_html_e('Unlimited','service-booking-manager'); ?></th>
                                <th colspan="3"><?php esc_html_e('No Need','service-booking-manager'); ?></th>
                                <th class="textSuccess"><?php esc_html_e('Active','service-booking-manager'); ?></th>
                                <td colspan="2"></td>
                            </tr>
		                    <?php do_action('mp_license_page_plugin_list'); ?>
                            </tbody>
                        </table>
                    </div>
                </div>
				<?php
			}
		}
		new  MPWPB_Settings_Global();
	}