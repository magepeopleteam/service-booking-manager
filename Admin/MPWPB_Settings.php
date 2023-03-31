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
				add_meta_box( 'mp_meta_box_panel', '<span class="fas fa-cogs"></span>' . $label . esc_html__( ' Information Settings : ', 'bookingplus' ) . get_the_title( get_the_id() ), array( $this, 'settings' ), $cpt, 'normal', 'high' );
			}
			//******************************//
			public function settings() {
				$post_id = get_the_id();
				wp_nonce_field( 'mpwpb_nonce', 'mpwpb_nonce' );
				?>
				<div class="mpStyle">
					<div class="mpTabs leftTabs">
						<ul class="tabLists">
							<li data-tabs-target="#mpwpb_general_info">
								<span class="fas fa-tools"></span><?php esc_html_e( 'General Info', 'bookingplus' ); ?>
							</li>
							<li data-tabs-target="#mpwpb_price_settings">
								<span class="fas fa-hand-holding-usd"></span><?php esc_html_e( 'Pricing', 'bookingplus' ); ?>
							</li>
							<li data-tabs-target="#mpwpb_settings_date_time">
								<span class="far fa-clock"></span><?php esc_html_e( 'Date & Time', 'bookingplus' ); ?>
							</li>
							<?php do_action( 'add_mpwpb_settings_tab_after_date', $post_id ); ?>
						</ul>
						<div class="tabsContent">
							<?php do_action( 'add_mpwpb_settings_tab_content', $post_id ); ?>
						</div>
					</div>
				</div>
				<?php
			}
			public static function description_array( $key ) {
				$des = array(
					'mpwpb_category_active'         => esc_html__( 'By default Category  is ON but you can keep it off by switching this option', 'bookingplus' ),
					'mpwpb_sub_category_active'     => esc_html__( 'By default Sub-Category  is ON but you can keep it off by switching this option', 'bookingplus' ),
					'mpwpb_service_details_active'  => esc_html__( 'By default Service Details  is OFF but you can keep it ON by switching this option', 'bookingplus' ),
					'mpwpb_service_duration_active' => esc_html__( 'By default Service Duration  is ON but you can keep it OFF by switching this option', 'bookingplus' ),
					'mpwpb_extra_service_active'    => esc_html__( 'By default extra service  is OFF but you can keep it ON by switching this option', 'bookingplus' ),
					//======Slider==========//
					'mpwpb_display_slider'          => esc_html__( 'By default slider is ON but you can keep it off by switching this option', 'bookingplus' ),
					'mpwpb_slider_images'           => esc_html__( 'Please upload images for gallery', 'bookingplus' ),
					//''          => esc_html__( '', 'bookingplus' ),
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