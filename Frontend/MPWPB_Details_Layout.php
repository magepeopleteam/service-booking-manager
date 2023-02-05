<?php
	if ( ! defined( 'ABSPATH' ) ) {
		die;
	} // Cannot access pages directly.
	if ( ! class_exists( 'MPWPB_Details_Layout' ) ) {
		class MPWPB_Details_Layout {
			public function __construct() {
				add_action( 'mpwpb_category_list', array( $this, 'category_list' ), 10, 1 );
				/**************/
				add_action( 'wp_ajax_get_mpwpb_category_service_type', array( $this, 'get_mpwpb_category_service_type' ) );
				add_action( 'wp_ajax_nopriv_get_mpwpb_category_service_type', array( $this, 'get_mpwpb_category_service_type' ) );
			}
			public function category_list( $post_id ) {
				$category_infos = MPWPB_Function::get_post_info( $post_id, 'mpwpb_category_infos', array() );
				if ( sizeof( $category_infos ) > 0 ) {
					$active_category_name = $active_category_name ?? '';
					?>
					<div class="groupRadioCheck fdColumn">
						<input type="hidden" name="mpwpb_category_type" value="<?php echo esc_attr( array_key_exists( 'category', $category_infos[0] ) ? $category_infos[0]['category'] : '' ); ?>">
						<?php
							foreach ( $category_infos as $categories ) {
								$category_name        = array_key_exists( 'category', $categories ) ? $categories['category'] : '';
								$active_category_name = $active_category_name ?: $category_name;
								$active_icon          = $active_category_name == $category_name ? 'fas fa-check' : '';
								$active_class         = $active_category_name == $category_name ? 'mpActive' : '';
								?>
								<button type="button" class="dButton <?php echo esc_attr( $active_class ); ?>" data-radio-check="<?php echo esc_attr( $category_name ); ?>" data-icon-change data-open-icon="fas fa-check" data-close-icon="">
									<span data-icon class="<?php echo esc_attr( $active_icon ); ?>"></span><?php echo esc_html( $category_name ); ?>
								</button>
								<div class="divider"></div>
								<?php
							}
						?>
					</div>
					<?php
				}
			}
			/*************/
			public function get_mpwpb_category_service_type() {
				$post_id       = $_REQUEST['post_id'];
				$category_name = $_REQUEST['category_type'];
				include( MPWPB_Function::template_path( 'registration/car_wash_registration.php' ) );
				die();
			}
			/*****************/
			public static function extra_service_layout( $post_id, $service_info, $group_service_name ) {
				$service_price     = array_key_exists( 'price', $service_info ) ? $service_info['price'] : 0;
				$service_price     = MPWPB_Function::wc_price( $post_id, $service_price );
				$service_price_raw = MPWPB_Function::price_convert_raw( $service_price );
				?>
				<td class="w_100">
					<div class="bg_image_area">
						<div data-bg-image="<?php echo esc_attr( MPWPB_Function::get_image_url( '', $service_info['img'], 'medium' ) ); ?>"></div>
					</div>
				</td>
				<td colspan="3" class="verticalTop">
					<strong><?php echo esc_html( $service_info['name'] ); ?></strong><br/>
					<?php if ( $service_info['details'] ) { ?>
						<small><?php echo esc_html( $service_info['details'] ); ?></small>
					<?php } ?>
				</td>
				<th class="textTheme w_100"><?php echo MPWPB_Function::esc_html( $service_price ); ?></th>
				<td class="_w_150_verticalTop">
					<button type="button" class="dButton mpwpb_price_calculation" data-all-change data-open-icon="far fa-check-circle" data-close-icon="" data-open-text="<?php esc_attr_e( 'Select', 'mpwpb_plugin' ); ?>" data-close-text="<?php esc_attr_e( 'Selected', 'mpwpb_plugin' ); ?>" data-add-class="mpActive">
						<input type="hidden" name="mpwpb_extra_service[]" data-value="<?php echo esc_attr( $group_service_name ); ?>" value=""/>
						<input type="hidden" name="mpwpb_extra_service_type[]" data-value="<?php echo esc_attr( $service_info['name'] ); ?>" value=""/>
						<input type="hidden" name="mpwpb_extra_service_type_price[]" data-extra-price data-value="<?php echo esc_attr( $service_price_raw ); ?>" value=""/>
						<span data-text><?php esc_html_e( 'Select', 'mptbm_plugin' ); ?></span>
						<span data-icon class="mp_zero"></span>
					</button>
				</td>
				<?php
			}
		}
		new MPWPB_Details_Layout();
	}