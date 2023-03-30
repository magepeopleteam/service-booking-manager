<?php
	if ( ! defined( 'ABSPATH' ) ) {
		die;
	} // Cannot access pages directly.
	if ( ! class_exists( 'MPWPB_Settings_Global' ) ) {
		class MPWPB_Settings_Global {
			protected $settings_api;
			public function __construct() {
				$this->settings_api = new MAGE_Setting_API;
				add_action( 'admin_menu', array( $this, 'global_settings_menu' ) );
				add_action( 'admin_init', array( $this, 'admin_init' ) );
				add_filter( 'mpwpb_settings_sec_reg', array( $this, 'settings_sec_reg' ), 10 );
				add_filter( 'mpwpb_settings_sec_fields', array( $this, 'settings_sec_fields' ), 10 );
			}
			public function global_settings_menu() {
				$label = MPWPB_Function::get_name();
				$cpt   = MPWPB_Function::get_cpt_name();
				add_submenu_page( 'edit.php?post_type=' . $cpt, $label . esc_html__( ' Settings', 'bookingmaster' ), $label . esc_html__( ' Settings', 'bookingmaster' ), 'manage_options', 'mpwpb_settings_page', array( $this, 'settings_page' ) );
			}
			public function settings_page() {
				$plugin_data = get_plugin_data( __FILE__ );
				?>
				<div class="mp_settings_panel_header">
					<?php echo $plugin_data['Name']; ?>
					<small><?php echo $plugin_data['Version']; ?></small>
				</div>
				<div class="mp_settings_panel">
					<?php $this->settings_api->show_navigation(); ?>
					<?php $this->settings_api->show_forms(); ?>
				</div>
				<?php
			}
			public function admin_init() {
				$this->settings_api->set_sections( $this->get_settings_sections() );
				$this->settings_api->set_fields( $this->get_settings_fields() );
				$this->settings_api->admin_init();
			}
			public function get_settings_sections() {
				$sections = array();
				return apply_filters( 'mpwpb_settings_sec_reg', $sections );
			}
			public function get_settings_fields() {
				$settings_fields = array();
				return apply_filters( 'mpwpb_settings_sec_fields', $settings_fields );
			}
			public function settings_sec_reg( $default_sec ): array {
				$sections = array(
					array(
						'id'    => 'mpwpb_general_settings',
						'title' => __( 'General Settings', 'bookingmaster' )
					),
					array(
						'id'    => 'mpwpb_style_settings',
						'title' => __( 'Style Settings', 'bookingmaster' )
					),
					array(
						'id'    => 'mpwpb_custom_css',
						'title' => __( 'Custom CSS', 'bookingmaster' )
					)
				);
				return array_merge( $default_sec, $sections );
			}
			public function settings_sec_fields( $default_fields ): array {
				$label           = MPWPB_Function::get_name();
				$current_date    = current_time( 'Y-m-d' );
				$settings_fields = array(
					'mpwpb_general_settings' => apply_filters( 'filter_mpwpb_general_settings', array(
						array(
							'name'    => 'disable_block_editor',
							'label'   => esc_html__( 'Disable Block/Gutenberg Editor', 'bookingmaster' ),
							'desc'    => esc_html__( 'If you want to disable WordPress\'s new Block/Gutenberg editor for Tour, please select Yes.', 'bookingmaster' ),
							'type'    => 'select',
							'default' => 'yes',
							'options' => array(
								'yes' => esc_html__( 'Yes', 'bookingmaster' ),
								'no'  => esc_html__( 'No', 'bookingmaster' )
							)
						),
						array(
							'name'    => 'set_book_status',
							'label'   => esc_html__( 'Seat Booked Status', 'bookingmaster' ),
							'desc'    => esc_html__( 'Please Select when and which order status Seat Will be Booked/Reduced.', 'bookingmaster' ),
							'type'    => 'multicheck',
							'default' => array(
								'processing' => 'processing',
								'completed'  => 'completed'
							),
							'options' => array(
								'on-hold'    => esc_html__( 'On Hold', 'bookingmaster' ),
								'pending'    => esc_html__( 'Pending', 'bookingmaster' ),
								'processing' => esc_html__( 'Processing', 'bookingmaster' ),
								'completed'  => esc_html__( 'Completed', 'bookingmaster' ),
							)
						),
						array(
							'name'    => 'date_format',
							'label'   => esc_html__( 'Date Picker Format', 'bookingmaster' ),
							'desc'    => esc_html__( 'If you want to change Date Picker Format, please select format. Default  is D d M , yy.', 'bookingmaster' ),
							'type'    => 'select',
							'default' => 'D d M , yy',
							'options' => array(
								'yy-mm-dd'   => $current_date,
								'yy/mm/dd'   => date_i18n( 'Y/m/d', strtotime( $current_date ) ),
								'yy-dd-mm'   => date_i18n( 'Y-d-m', strtotime( $current_date ) ),
								'yy/dd/mm'   => date_i18n( 'Y/d/m', strtotime( $current_date ) ),
								'dd-mm-yy'   => date_i18n( 'd-m-Y', strtotime( $current_date ) ),
								'dd/mm/yy'   => date_i18n( 'd/m/Y', strtotime( $current_date ) ),
								'mm-dd-yy'   => date_i18n( 'm-d-Y', strtotime( $current_date ) ),
								'mm/dd/yy'   => date_i18n( 'm/d/Y', strtotime( $current_date ) ),
								'd M , yy'   => date_i18n( 'j M , Y', strtotime( $current_date ) ),
								'D d M , yy' => date_i18n( 'D j M , Y', strtotime( $current_date ) ),
								'M d , yy'   => date_i18n( 'M  j, Y', strtotime( $current_date ) ),
								'D M d , yy' => date_i18n( 'D M  j, Y', strtotime( $current_date ) ),
							)
						),
						array(
							'name'    => 'date_format_short',
							'label'   => esc_html__( 'Short Date  Format', 'bookingmaster' ),
							'desc'    => esc_html__( 'If you want to change Short Date  Format, please select format. Default  is M , Y.', 'bookingmaster' ),
							'type'    => 'select',
							'default' => 'M , Y',
							'options' => array(
								'M , Y' => date_i18n( 'M , Y', strtotime( $current_date ) ),
								'M , y' => date_i18n( 'M , y', strtotime( $current_date ) ),
								'M - Y' => date_i18n( 'M - Y', strtotime( $current_date ) ),
								'M - y' => date_i18n( 'M - y', strtotime( $current_date ) ),
								'F , Y' => date_i18n( 'F , Y', strtotime( $current_date ) ),
								'F , y' => date_i18n( 'F , y', strtotime( $current_date ) ),
								'F - Y' => date_i18n( 'F - y', strtotime( $current_date ) ),
								'F - y' => date_i18n( 'F - y', strtotime( $current_date ) ),
								'm - Y' => date_i18n( 'm - Y', strtotime( $current_date ) ),
								'm - y' => date_i18n( 'm - y', strtotime( $current_date ) ),
								'm , Y' => date_i18n( 'm , Y', strtotime( $current_date ) ),
								'm , y' => date_i18n( 'm , y', strtotime( $current_date ) ),
								'F'     => date_i18n( 'F', strtotime( $current_date ) ),
								'm'     => date_i18n( 'm', strtotime( $current_date ) ),
								'M'     => date_i18n( 'M', strtotime( $current_date ) ),
							)
						),
						array(
							'name'    => 'payment_system',
							'label'   => esc_html__( 'Payment System', 'bookingmaster' ),
							'desc'    => esc_html__( 'Please Select Payment System.', 'bookingmaster' ),
							'type'    => 'multicheck',
							'default' => array(
								'direct_order' => 'direct_order',
								'woocommerce'  => 'woocommerce'
							),
							'options' => array(
								'direct_order' => esc_html__( 'Pay on service', 'bookingmaster' ),
								'woocommerce'  => esc_html__( 'woocommerce Payment', 'bookingmaster' ),
							)
						),
						array(
							'name'    => 'direct_book_status',
							'label'   => esc_html__( 'Pay on service Booked Status', 'bookingmaster' ),
							'desc'    => esc_html__( 'Please Select when and which order status service Will be Booked/Reduced in Pay on service.', 'bookingmaster' ),
							'type'    => 'select',
							'default' => 'completed',
							'options' => array(
								'pending' => esc_html__( 'Pending', 'bookingmaster' ),
								'completed'  => esc_html__( 'completed', 'bookingmaster' )
							)
						),

						array(
							'name'    => 'label',
							'label'   => $label . ' ' . esc_html__( 'Label', 'bookingmaster' ),
							'desc'    => esc_html__( 'If you like to change the ' . $label . ' label in the dashboard menu, you can change it here.', 'bookingmaster' ),
							'type'    => 'text',
							'default' => 'WP Easy Booking'
						),
						array(
							'name'    => 'slug',
							'label'   => $label . ' ' . esc_html__( 'Slug', 'bookingmaster' ),
							'desc'    => esc_html__( 'Please enter the slug name you want. Remember, after changing this slug; you need to flush permalink; go to', 'bookingmaster' ) . '<strong>' . esc_html__( 'Settings-> Permalinks', 'bookingmaster' ) . '</strong> ' . esc_html__( 'hit the Save Settings button.', 'bookingmaster' ),
							'type'    => 'text',
							'default' => 'bookingmaster'
						),
						array(
							'name'    => 'icon',
							'label'   => $label . ' ' . esc_html__( 'Icon', 'bookingmaster' ),
							'desc'    => esc_html__( 'If you want to change the ' . $label . ' icon in the dashboard menu, you can change it from here, and the Dashboard icon only supports the Dashicons, So please go to ', 'bookingmaster' ) . '<a href=https://developer.wordpress.org/resource/dashicons/#calendar-alt target=_blank>' . esc_html__( 'Dashicons Library.', 'bookingmaster' ) . '</a>' . esc_html__( 'and copy your icon code and paste it here.', 'bookingmaster' ),
							'type'    => 'text',
							'default' => 'dashicons-list-view'
						),
						array(
							'name'    => 'category_label',
							'label'   => $label . ' ' . esc_html__( 'Category Label', 'bookingmaster' ),
							'desc'    => esc_html__( 'If you want to change the ' . $label . ' category label in the dashboard menu, you can change it here.', 'bookingmaster' ),
							'type'    => 'text',
							'default' => esc_html__( 'Category', 'bookingmaster' )
						),
						array(
							'name'    => 'category_slug',
							'label'   => $label . ' ' . esc_html__( 'Category Slug', 'bookingmaster' ),
							'desc'    => esc_html__( 'Please enter the slug name you want for ' . $label . ' category. Remember after change this slug you need to flush permalink, Just go to  ', 'bookingmaster' ) . '<strong>' . esc_html__( 'Settings-> Permalinks', 'bookingmaster' ) . '</strong> ' . esc_html__( 'hit the Save Settings button.', 'bookingmaster' ),
							'type'    => 'text',
							'default' => 'service-category'
						),
						array(
							'name'    => 'organizer_label',
							'label'   => $label . ' ' . esc_html__( 'Organizer Label', 'bookingmaster' ),
							'desc'    => esc_html__( 'If you want to change the  ' . $label . '  category label in the dashboard menu you can change here', 'bookingmaster' ),
							'type'    => 'text',
							'default' => 'Organizer'
						),
						array(
							'name'    => 'organizer_slug',
							'label'   => $label . ' ' . esc_html__( 'Organizer Slug', 'bookingmaster' ),
							'desc'    => esc_html__( 'Please enter the slug name you want for the  ' . $label . '  organizer. Remember, after changing this slug, you need to flush the permalinks. Just go to ', 'bookingmaster' ) . '<strong>' . esc_html__( 'Settings-> Permalinks', 'bookingmaster' ) . '</strong> ' . esc_html__( 'hit the Save Settings button.', 'bookingmaster' ),
							'type'    => 'text',
							'default' => 'service-organizer'
						),
						array(
							'name'    => 'category_text',
							'label'   => $label . ' ' . esc_html__( 'Product Category Text', 'bookingmaster' ),
							'desc'    => esc_html__( 'If you want to change the ' . $label . ' Product Category Text, you can change it here.', 'bookingmaster' ),
							'type'    => 'text',
							'default' => esc_html__( 'Category', 'bookingmaster' )
						),
						array(
							'name'    => 'sub_category_text',
							'label'   => $label . ' ' . esc_html__( 'Product Sub-Category Text', 'bookingmaster' ),
							'desc'    => esc_html__( 'If you want to change the ' . $label . ' Product Sub-Category Text, you can change it here.', 'bookingmaster' ),
							'type'    => 'text',
							'default' => esc_html__( 'Sub-Category', 'bookingmaster' )
						),
						array(
							'name'    => 'service_text',
							'label'   => $label . ' ' . esc_html__( 'Product ServiceText', 'bookingmaster' ),
							'desc'    => esc_html__( 'If you want to change the ' . $label . ' Product Service Text, you can change it here.', 'bookingmaster' ),
							'type'    => 'text',
							'default' => esc_html__( 'Service', 'bookingmaster' )
						),
					) ),
					'mpwpb_style_settings'   => apply_filters( 'filter_mpwpb_style_settings', array(
						array(
							'name'    => 'theme_color',
							'label'   => esc_html__( 'Theme Color', 'bookingmaster' ),
							'desc'    => esc_html__( 'Select Default Theme Color', 'bookingmaster' ),
							'type'    => 'color',
							'default' => '#0793C9'
						),
						array(
							'name'    => 'theme_alternate_color',
							'label'   => esc_html__( 'Theme Alternate Color', 'bookingmaster' ),
							'desc'    => esc_html__( 'Select Default Theme Alternate  Color that means, if background theme color then it will be text color.', 'bookingmaster' ),
							'type'    => 'color',
							'default' => '#fff'
						),
						array(
							'name'    => 'default_text_color',
							'label'   => esc_html__( 'Default Text Color', 'bookingmaster' ),
							'desc'    => esc_html__( 'Select Default Text  Color.', 'bookingmaster' ),
							'type'    => 'color',
							'default' => '#000'
						),
						array(
							'name'    => 'default_font_size',
							'label'   => esc_html__( 'Default Font Size', 'bookingmaster' ),
							'desc'    => esc_html__( 'Type Default Font Size(in PX Unit).', 'bookingmaster' ),
							'type'    => 'number',
							'default' => '15'
						),
						array(
							'name'    => 'font_size_h1',
							'label'   => esc_html__( 'Font Size h1 Title', 'bookingmaster' ),
							'desc'    => esc_html__( 'Type Font Size Main Title(in PX Unit).', 'bookingmaster' ),
							'type'    => 'number',
							'default' => '35'
						),
						array(
							'name'    => 'font_size_h2',
							'label'   => esc_html__( 'Font Size h2 Title', 'bookingmaster' ),
							'desc'    => esc_html__( 'Type Font Size h2 Title(in PX Unit).', 'bookingmaster' ),
							'type'    => 'number',
							'default' => '25'
						),
						array(
							'name'    => 'font_size_h3',
							'label'   => esc_html__( 'Font Size h3 Title', 'bookingmaster' ),
							'desc'    => esc_html__( 'Type Font Size h3 Title(in PX Unit).', 'bookingmaster' ),
							'type'    => 'number',
							'default' => '22'
						),
						array(
							'name'    => 'font_size_h4',
							'label'   => esc_html__( 'Font Size h4 Title', 'bookingmaster' ),
							'desc'    => esc_html__( 'Type Font Size h4 Title(in PX Unit).', 'bookingmaster' ),
							'type'    => 'number',
							'default' => '20'
						),
						array(
							'name'    => 'font_size_h5',
							'label'   => esc_html__( 'Font Size h5 Title', 'bookingmaster' ),
							'desc'    => esc_html__( 'Type Font Size h5 Title(in PX Unit).', 'bookingmaster' ),
							'type'    => 'number',
							'default' => '18'
						),
						array(
							'name'    => 'font_size_h6',
							'label'   => esc_html__( 'Font Size h6 Title', 'bookingmaster' ),
							'desc'    => esc_html__( 'Type Font Size h6 Title(in PX Unit).', 'bookingmaster' ),
							'type'    => 'number',
							'default' => '16'
						),
						array(
							'name'    => 'button_font_size',
							'label'   => esc_html__( 'Button Font Size ', 'bookingmaster' ),
							'desc'    => esc_html__( 'Type Font Size Button(in PX Unit).', 'bookingmaster' ),
							'type'    => 'number',
							'default' => '18'
						),
						array(
							'name'    => 'button_color',
							'label'   => esc_html__( 'Button Text Color', 'bookingmaster' ),
							'desc'    => esc_html__( 'Select Button Text  Color.', 'bookingmaster' ),
							'type'    => 'color',
							'default' => '#FFF'
						),
						array(
							'name'    => 'button_bg',
							'label'   => esc_html__( 'Button Background Color', 'bookingmaster' ),
							'desc'    => esc_html__( 'Select Button Background  Color.', 'bookingmaster' ),
							'type'    => 'color',
							'default' => '#222'
						),
						array(
							'name'    => 'font_size_label',
							'label'   => esc_html__( 'Label Font Size ', 'bookingmaster' ),
							'desc'    => esc_html__( 'Type Font Size Label(in PX Unit).', 'bookingmaster' ),
							'type'    => 'number',
							'default' => '18'
						),
						array(
							'name'    => 'warning_color',
							'label'   => esc_html__( 'Warning Color', 'bookingmaster' ),
							'desc'    => esc_html__( 'Select Warning  Color.', 'bookingmaster' ),
							'type'    => 'color',
							'default' => '#E67C30'
						),
						array(
							'name'    => 'section_bg',
							'label'   => esc_html__( 'Section Background color', 'bookingmaster' ),
							'desc'    => esc_html__( 'Select Background  Color.', 'bookingmaster' ),
							'type'    => 'color',
							'default' => '#FAFCFE'
						),
					) ),
					'mpwpb_custom_css'       => apply_filters( 'filter_mpwpb_custom_css', array(
						array(
							'name'  => 'custom_css',
							'label' => esc_html__( 'Custom CSS', 'bookingmaster' ),
							'desc'  => esc_html__( 'Write Your Custom CSS Code Here', 'bookingmaster' ),
							'type'  => 'textarea',
						)
					) )
				);
				return array_merge( $default_fields, $settings_fields );
			}
		}
		new  MPWPB_Settings_Global();
	}