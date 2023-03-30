<?php
	if ( ! defined( 'ABSPATH' ) ) {
		die;
	} // Cannot access pages directly.
	if ( ! class_exists( 'MPWPB_Shortcodes' ) ) {
		class MPWPB_Shortcodes {
			public function __construct() {
				add_shortcode( 'mpwpb-order-details', array( $this, 'order_details' ) );
				add_shortcode( 'bookingmaster', array( $this, 'service_booking' ) );
			}
			public function order_details() {
				ob_start();
				$order_id = $_GET['order_id'] ?? '';
				if ( $order_id ) {
					$order_details = wc_get_order( $order_id );
					$order_status  = $order_details->get_status();
					?>
					<div class="mpStyle">
						<div class="justifyBetween mB">
							<h3 class="textSuccess _mR"><?php esc_html_e( 'Booked Successfully', 'bookingmaster' ); ?></h3>
							<?php
								if ( $order_status == 'completed' ) {
									do_action( 'mpwpb_pdf_button', $order_id );
									do_action( 'mpwpb_send_mail', $order_id );
								}
							?>
						</div>
					</div>
					<?php
					do_action( 'mpwpb_order_details', $order_id );
				}
				return ob_get_clean();
			}
			public function service_booking( $attribute ) {
				ob_start();
				$defaults = array(
					'post_id' => '',
				);
				$params   = shortcode_atts( $defaults, $attribute );
				$post_id  = $params['post_id'];
				if ( $post_id ) {
					include( MPWPB_Function::details_template_path( $post_id ) );
				}
				return ob_get_clean();
			}
		}
		new MPWPB_Shortcodes();
	}