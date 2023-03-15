<?php
	if ( ! defined( 'ABSPATH' ) ) {
		die;
	} // Cannot access pages directly.
	if ( ! class_exists( 'MPWPB_Shortcodes' ) ) {
		class MPWPB_Shortcodes {
			public function __construct() {
				add_shortcode( 'mpwpb-order-success', array( $this, 'order_success' ) );
			}
			public function order_success() {
				ob_start();
				if ( isset( $_POST['mpwpb_product_id'] ) && $_POST['mpwpb_product_id'] > 0 ) {
					$post_id            = MPWPB_Function::get_submit_info( 'post_id', 0 );
					$total_price        = MPWPB_Woocommerce::get_cart_total_price( $post_id );
					$order_date         = date( 'M-d-Y-hi-a' );
					$order_date_title   = date( 'F d, Y @ h:i A' );
					$order_status       = MPWPB_Function::get_general_settings( 'direct_book_status', 'completed' );
					$wc_order_status    = $order_status == 'pending' ? 'wc-pending' : 'wc-completed';
					$wc_order_status    = $order_status == 'requested' ? 'wc-requested' : $wc_order_status;
					$billing_name_text  = MPWPB_Function::get_post_info( $post_id, 'mpwpb_bill_name' );
					$billing_name       = MPWPB_Function::get_submit_info( $billing_name_text, array() )[0];
					$billing_email_text = MPWPB_Function::get_post_info( $post_id, 'mpwpb_bill_email' );
					$billing_email      = MPWPB_Function::get_submit_info( $billing_email_text, array() )[0];
					$order_data         = array(
						'post_name'      => 'order-' . $order_date,
						'post_type'      => 'shop_order',
						'post_title'     => 'Order &ndash; ' . $order_date_title,
						'post_status'    => $wc_order_status,
						'ping_status'    => 'closed',
						'post_excerpt'   => 'Order Created From customer.',
						//'post_author' => $user_id,
						'post_password'  => uniqid( 'order_' ),
						'post_date'      => date( 'Y-m-d H:i:s e' ),
						'comment_status' => 'open'
					);
					// create order
					$order_id = wp_insert_post( $order_data, true );
					if ( ! is_wp_error( $order_id ) ) {
						add_post_meta( $order_id, '_payment_method_title', 'Import', true );
						add_post_meta( $order_id, '_order_total', $total_price, true );
						add_post_meta( $order_id, '_completed_date', $order_date, true );
						add_post_meta( $order_id, '_order_currency', get_woocommerce_currency_symbol(), true );
						add_post_meta( $order_id, '_paid_date', $order_date, true );
						add_post_meta( $order_id, '_billing_first_name', $billing_name, true );
						add_post_meta( $order_id, '_billing_email', $billing_email, true );
						// get product by item_id
						$product_id = get_post_meta( $post_id, 'link_wc_product', true );
						$product    = MPWPB_Function::wc_product_sku( $product_id );
						if ( $product ) {
							// add item
							$item_id = wc_add_order_item( $order_id, array(
								'order_item_name' => $product->get_title(),
								'order_item_type' => 'line_item'
							) );
							if ( $item_id ) {
								wc_add_order_item_meta( $item_id, '_qty', 1 );
								wc_add_order_item_meta( $item_id, '_tax_class', 'no_tax' );
								wc_add_order_item_meta( $item_id, '_product_id', $product_id );
								wc_add_order_item_meta( $item_id, '_variation_id', '' );
								wc_add_order_item_meta( $item_id, '_line_subtotal', wc_format_decimal( $total_price ) );
								wc_add_order_item_meta( $item_id, '_line_total', wc_format_decimal( $total_price ) );
								wc_add_order_item_meta( $item_id, '_line_tax', wc_format_decimal( 0 ) );
								wc_add_order_item_meta( $item_id, '_line_subtotal_tax', wc_format_decimal( 0 ) );
								/*************************************/
								$category       = MPWPB_Function::get_submit_info( 'mpwpb_category' );
								$sub_category   = MPWPB_Function::get_submit_info( 'mpwpb_sub_category' );
								$service        = MPWPB_Function::get_submit_info( 'mpwpb_service' );
								$date           = MPWPB_Function::get_submit_info( 'mpwpb_date' );
								$price          = MPWPB_Function::get_price( $product_id, $service, $category, $sub_category, $date );
								$attendee_info  = apply_filters( 'mpwpb_user_info', array(), $post_id );
								$extra_services = MPWPB_Woocommerce::cart_extra_service_info( $post_id );
								/*************************************/
								if ( $category ) {
									wc_add_order_item_meta( $item_id, MPWPB_Function::get_category_text( $post_id ), $category);
									if ( $sub_category ) {
										wc_add_order_item_meta( $item_id, MPWPB_Function::get_sub_category_text( $post_id ), $sub_category);
									}
								}
								wc_add_order_item_meta( $item_id, esc_html__( 'Price ', 'mpwpb_plugin' ), $price );
								wc_add_order_item_meta( $item_id, esc_html__( 'Date ', 'mpwpb_plugin' ), esc_html( MPWPB_Function::date_format( $date ) ) );
								wc_add_order_item_meta( $item_id, esc_html__( 'Time ', 'mpwpb_plugin' ), esc_html( MPWPB_Function::date_format( $date, 'time' ) ) );
								/*************************************/
								if ( sizeof( $extra_services ) > 0 ) {
									foreach ( $extra_services as $service ) {
										wc_add_order_item_meta( $item_id, MPWPB_Function::get_service_text( $post_id ), $service['service_name'] . ' (' . esc_html( $service['service_category_name'] ) . ')' );
										wc_add_order_item_meta( $item_id, esc_html__( 'Quantity ', 'mpwpb_plugin' ), $service['service_qty'] );
										wc_add_order_item_meta( $item_id, esc_html__( 'Price ', 'mpwpb_plugin' ), ' ( ' . MPWPB_Function::wc_price( $post_id, $service['service_price'] ) . ' x ' . $service['service_qty'] . ') = ' . MPWPB_Function::wc_price( $post_id, ( $service['service_price'] * $service['service_qty'] ) ) );
									}
								}
								/*************************************/
								wc_add_order_item_meta( $item_id, '_mpwpb_id', $post_id );
								wc_add_order_item_meta( $item_id, '_mpwpb_date', $date );
								if ( $category ) {
									wc_add_order_item_meta( $item_id, '_mpwpb_category', $category );
									if ( $sub_category ) {
										wc_add_order_item_meta( $item_id, '_mpwpb_sub_category', $sub_category );
									}
								}
								wc_add_order_item_meta( $item_id, '_mpwpb_service', $service );
								wc_add_order_item_meta( $item_id, '_mpwpb_price', $price );
								wc_add_order_item_meta( $item_id, '_mpwpb_user_info', $attendee_info );
								wc_add_order_item_meta( $item_id, '_mpwpb_service_info', $extra_services );
								wc_add_order_item_meta( $item_id, '_product_id', $post_id );
								MPWPB_Woocommerce::add_billing_data( $order_id );
							}
							// set order status as completed
							wp_set_object_terms( $order_id, $order_status, 'shop_order_status' );
							?>
							<div class="mpStyle">
								<div class="justifyBetween">
									<h3 class="textSuccess _mR"><?php esc_html_e( 'Booked Successfully', 'mpwpb_plugin' ); ?></h3>
									<?php
										if ( $order_status == 'completed' ) {
											do_action( 'mpwpb_pdf_button', $order_id );
											do_action( 'mpwpb_send_mail', $order_id );
										}
									?>
								</div>
							</div>
							<?php
							//do_action( 'mpwpb_order_details', $order_id );
						} else {
							echo MPWPB_Function::get_service_text( $post_id ).' '.esc_html__( ' not found', 'mpwpb_plugin' );
						}
					}
					// }
				}
				return ob_get_clean();
			}
		}
		new MPWPB_Shortcodes();
	}