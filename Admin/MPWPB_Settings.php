<?php
	if ( ! defined( 'ABSPATH' ) ) {
		die;
	} // Cannot access pages directly.
	if ( ! class_exists( 'MPWPB_Settings' ) ) {
		class MPWPB_Settings {
			public function __construct() {
				add_action( 'add_meta_boxes', [ $this, 'settings_meta' ] );
			}
			//************************//
			public function settings_meta() {
				$label = MPWPB_Function::get_name();
				$cpt   = MPWPB_Function::get_cpt_name();
				add_meta_box( 'mp_meta_box_panel', '<span class="fas fa-cogs"></span>' . $label . esc_html__( ' Information Settings : ', 'mpwpb_plugin' ) . get_the_title( get_the_id() ), array( $this, 'settings' ), $cpt, 'normal', 'high' );
			}
			//******************************//
			public function settings() {
				$post_id = get_the_id();
				wp_nonce_field('mpwpb_nonce', 'mpwpb_nonce');
				?>
				<div class="mpStyle">
					<div class="mpTabs leftTabs">
						<ul class="tabLists">
							<li data-tabs-target="#mpwpb_general_info">
								<span class="fas fa-tools"></span><?php esc_html_e( 'General Info', 'mpwpb_plugin' ); ?>
							</li>
							<li data-tabs-target="#mpwpb_price_settings">
								<span class="fas fa-hand-holding-usd"></span><?php esc_html_e( 'Pricing', 'mpwpb_plugin' ); ?>
							</li>
							<li data-tabs-target="#mpwpb_settings_date_time">
								<span class="far fa-clock"></span><?php esc_html_e( 'Date & Time', 'mpwpb_plugin' ); ?>
							</li>
							<li data-tabs-target="#mpwpb_settings_gallery">
								<span class="fas fa-images"></span><?php esc_html_e( 'Gallery ', 'mpwpb_plugin' ); ?>
							</li>
						</ul>
						<div class="tabsContent tab-content">
							<?php
								do_action( 'add_mpwpb_settings_tab_content', $post_id );
							?>
						</div>
					</div>
				</div>
				<?php
			}
			public static function description_array( $key ) {
				$des = array(
					'start_price'                  => esc_html__( 'Price Starts  are displayed on the tour details and tour list pages. If you would like to hide them, you can do so by switching the option.', 'mpwpb_plugin' ),
					'max_people'                   => esc_html__( 'This tour only allows a maximum of X people. This number is displayed for informational purposes only and can be hidden by switching the option.', 'mpwpb_plugin' ),
					'age_range'                    => esc_html__( 'The age limit for this tour is X to Y years old. This is for information purposes only.', 'mpwpb_plugin' ),
					'start_place'                  => esc_html__( 'This will be the starting point for the tour group. The tour will begin from here.', 'mpwpb_plugin' ),
					'location'                     => esc_html__( 'Please select the name of the location you wish to create a tour for. If you would like to create a new location, please go to the Tour page.', 'mpwpb_plugin' ),
					'full_location'                => esc_html__( 'Please Type Full Address of the location, it will use for the google map', 'mpwpb_plugin' ),
					'short_des'                    => esc_html__( 'For a Tour short description, toggle this switching option.', 'mpwpb_plugin' ),
					'duration'                     => esc_html__( 'Please enter the number of days and nights for your tour package.', 'mpwpb_plugin' ),
					'ttbm_new_location_name'       => esc_html__( 'Please add the new location to the location list when creating a tour.', 'mpwpb_plugin' ),
					'ttbm_location_description'    => esc_html__( 'The description is not always visible by default, but some themes may display it.', 'mpwpb_plugin' ),
					'ttbm_location_address'        => esc_html__( 'Please Enter the Full Address of Your Location', 'mpwpb_plugin' ),
					'ttbm_location_country'        => esc_html__( 'Please select your tour location country from the list below.', 'mpwpb_plugin' ),
					'ttbm_location_image'          => esc_html__( 'Please select an image for your tour location.', 'mpwpb_plugin' ),
					'ttbm_display_registration'    => esc_html__( "If you don't want to use the tour registration feature, you can just keep it turned off.", 'mpwpb_plugin' ),
					'ttbm_short_code'              => esc_html__( 'You can display this Ticket type list with the add to cart button anywhere on your website by copying the shortcode and using it on any post or page.', 'mpwpb_plugin' ),
					'ttbm_display_schedule'        => esc_html__( 'Please find the detailed timeline for you tour as day 1, day 2 etc.', 'mpwpb_plugin' ),
					'add_new_feature_popup'        => esc_html__( 'To include or exclude a feature from your tour, please select it from the list below. To create a new feature, go to the Tour page.', 'mpwpb_plugin' ),
					'ttbm_display_include_service' => esc_html__( 'The price of this tour includes the service, which you can keep hidden by turning it off.', 'mpwpb_plugin' ),
					'ttbm_display_exclude_service' => esc_html__( 'The price of this tour excludes the service, which you can keep hidden by turning it off.', 'mpwpb_plugin' ),
					'ttbm_feature_name'            => esc_html__( 'The name is how it appears on your site.', 'mpwpb_plugin' ),
					'ttbm_feature_description'     => esc_html__( 'The description is not prominent by default; however, some themes may show it.', 'mpwpb_plugin' ),
					'ttbm_display_hiphop'          => esc_html__( 'By default Places You\'ll See  is ON but you can keep it off by switching this option', 'mpwpb_plugin' ),
					'ttbm_place_you_see'           => esc_html__( 'Please Select Place Name. To create new place, go Tour->Places; or click on the Create New Place button', 'mpwpb_plugin' ),
					'ttbm_place_name'              => esc_html__( 'The name is how it appears on your site.', 'mpwpb_plugin' ),
					'ttbm_place_description'       => esc_html__( 'The description is not prominent by default; however, some themes may show it.', 'mpwpb_plugin' ),
					'ttbm_place_image'             => esc_html__( 'Please Select Place Image.', 'mpwpb_plugin' ),
					'ttbm_display_faq'             => esc_html__( 'Frequently Asked Questions about this tour that customers need to know', 'mpwpb_plugin' ),
					'ttbm_display_why_choose_us'   => esc_html__( 'Why choose us section, write a key feature list that tourist get Trust to book. you can switch it off.', 'mpwpb_plugin' ),
					'why_chose_us'                 => esc_html__( 'Please add why to book feature list one by one.', 'mpwpb_plugin' ),
					'ttbm_display_activities'      => esc_html__( 'By default Activities type is ON but you can keep it off by switching this option', 'mpwpb_plugin' ),
					'activities'                   => esc_html__( 'Add a list of tour activities for this tour.', 'mpwpb_plugin' ),
					'ttbm_activity_name'           => esc_html__( 'The name is how it appears on your site.', 'mpwpb_plugin' ),
					'ttbm_activity_description'    => esc_html__( 'The description is not prominent by default; however, some themes may show it.', 'mpwpb_plugin' ),
					'ttbm_display_related'         => esc_html__( 'Please select a related tour from this list.', 'mpwpb_plugin' ),
					'ttbm_section_title_style'     => esc_html__( 'By default Section title is style one', 'mpwpb_plugin' ),
					'ttbm_ticketing_system'        => esc_html__( 'By default, the ticket purchase system is open. Once you check the availability, you can choose the system that best suits your needs.', 'mpwpb_plugin' ),
					'ttbm_display_seat_details'    => esc_html__( 'By default Seat Info is ON but you can keep it off by switching this option', 'mpwpb_plugin' ),
					'ttbm_display_tour_type'       => esc_html__( 'By default Tour type is ON but you can keep it off by switching this option', 'mpwpb_plugin' ),
					'ttbm_display_hotels'          => esc_html__( 'By default Display hotels is ON but you can keep it off by switching this option', 'mpwpb_plugin' ),
					'ttbm_display_get_question'    => esc_html__( 'By default Display Get a Questions is ON but you can keep it off by switching this option', 'mpwpb_plugin' ),
					'ttbm_display_sidebar'         => esc_html__( 'By default Sidebar Widget is Off but you can keep it ON by switching this option', 'mpwpb_plugin' ),
					'ttbm_display_duration'        => esc_html__( 'By default Duration is ON but you can keep it off by switching this option', 'mpwpb_plugin' ),
					'ttbm_related_tour'            => esc_html__( 'Please add related  Tour', 'mpwpb_plugin' ),
					'ttbm_contact_phone'           => esc_html__( 'Please Enter contact phone no', 'mpwpb_plugin' ),
					'ttbm_contact_text'            => esc_html__( 'Please Enter Contact Section Text', 'mpwpb_plugin' ),
					'ttbm_contact_email'           => esc_html__( 'Please Enter contact phone email', 'mpwpb_plugin' ),
					'ttbm_type'                    => esc_html__( 'By default Type is General', 'mpwpb_plugin' ),
					'ttbm_display_advance'         => esc_html__( 'By default Advance option is Off but you can keep it On by switching this option', 'mpwpb_plugin' ),
					'ttbm_display_extra_advance'   => esc_html__( 'By default Advance option is on but you can keep it off by switching this option', 'mpwpb_plugin' ),
					'ttbm_display_hotel_distance'  => esc_html__( 'Please add Distance Description', 'mpwpb_plugin' ),
					'ttbm_display_hotel_rating'    => esc_html__( 'Please Select Hotel rating ', 'mpwpb_plugin' ),
					'ttbm_display_tour_guide'      => esc_html__( 'You can keep off tour guide information by switching this option', 'mpwpb_plugin' ),
					'ttbm_tour_guide'              => esc_html__( 'To add tour guide information, simply select an option from the list below.', 'mpwpb_plugin' ),
					//======Slider==========//
					'mpwpb_display_slider'         => esc_html__( 'By default slider is ON but you can keep it off by switching this option', 'mpwpb_plugin' ),
					'mpwpb_slider_images'          => esc_html__( 'Please upload images for gallery', 'mpwpb_plugin' ),
					//''          => esc_html__( '', 'mpwpb_plugin' ),
				);
				$des = apply_filters( 'mptbm_filter_description_array', $des );
				return $des[ $key ];
			}
			public static function info_text( $key ) {
				$data = self::description_array( $key );
				if ( $data ) {
					?>
					<i class="info_text">
						<span class="fas fa-info-circle"></span>
						<?php echo esc_html( $data ); ?>
					</i>
					<?php
				}
			}
		}
		new MPWPB_Settings();
	}