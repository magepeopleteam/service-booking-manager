<?php
	if ( ! defined( 'ABSPATH' ) ) {
		die;
	} // Cannot access pages directly.
	if ( ! class_exists( 'MPWPB_Order_layout' ) ) {
		class MPWPB_Order_layout {
			public function __construct() {
				add_action( 'mpwpb_direct_order_place', array( $this, 'direct_order_place' ) );
				add_action( 'mpwpb_order_details', array( $this, 'order_details' ), 10, 1 );
			}
			public function direct_order_place() {
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
									wc_add_order_item_meta( $item_id, MPWPB_Function::get_category_text( $post_id ), $category );
									if ( $sub_category ) {
										wc_add_order_item_meta( $item_id, MPWPB_Function::get_sub_category_text( $post_id ), $sub_category );
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
							$order_url = home_url() . '/mpwpb-order-details/?order_id=' . $order_id;
							?>
							<script>
											  window.location.href = "<?php echo esc_url( $order_url ); ?>";
							</script>
							<?php
						} else {
							echo MPWPB_Function::get_service_text( $post_id ) . ' ' . esc_html__( ' not found', 'mpwpb_plugin' );
						}
					}
					// }
				}
			}
			public function order_details( $order_id ) {
				$order_infos = MPWPB_Query::get_order_info( $order_id );
				if ( $order_infos->found_posts > 0 ) {
					$order_info = $order_infos->posts;
					if ( sizeof( $order_info ) > 0 ) {
						foreach ( $order_info as $order ) {
							$attendee_id = $order->ID;
							$post_id     = MPWPB_Function::get_post_info( $attendee_id, 'mpwpb_id' );
							//echo '<pre>';print_r($order);echo '</pre>';
							?>
							<div class="mpStyle">
								<div class="divider"></div>
								<h5><strong><?php esc_html_e( 'Order ID:', 'ttbm-pro' ); ?></strong> #<?php echo esc_html( $order_id ); ?></h5>
								<div class="divider"></div>
								<div class="flexWrap">
									<div class="dLayout col_6 col_xs_12">
										<h4><?php echo MPWPB_Function::get_name() . ' ' . esc_html__( 'details', 'ttbm-pro' ); ?></h4>
										<div class="divider"></div>
										<?php self::order_info( $attendee_id ); ?>
									</div>
									<div class="dLayout col_6 col_xs_12">
										<h4><?php esc_html_e( 'Billing information', 'ttbm-pro' ); ?></h4>
										<div class="divider"></div>
										<?php self::billing_info( $attendee_id ); ?>
									</div>
								</div>
								<table class="mp_zero">
									<thead>
									<tr>
										<th><?php esc_html_e( 'Ticket No:', 'ttbm-pro' ); ?></th>
										<th><?php echo MPWPB_Function::get_service_text( $post_id ) . ' ' . esc_html__( 'type:', 'ttbm-pro' ) ?></th>
										<th><?php esc_html_e( 'Price:', 'ttbm-pro' ); ?></th>
									</tr>
									</thead>
									<tbody>
									<tr>
										<td><?php echo MPWPB_Function::get_post_info( $attendee_id, 'mpwpb_pin' ); ?></td>
										<td><?php echo MPWPB_Function::get_post_info( $attendee_id, 'mpwpb_service_name' ); ?></td>
										<td><?php echo wc_price( MPWPB_Function::get_post_info( $attendee_id, 'mpwpb_service_price' ) ); ?></td>
									</tr>
									</tbody>
								</table>
							</div>
							<?php
						}
					}
				}
			}
			public static function order_info( $attendee_id ) {
				if ( $attendee_id > 0 ) {
					$post_id       = MPWPB_Function::get_post_info( $attendee_id, 'mpwpb_id' );
					$attendee_info = get_post( $attendee_id );
					$date          = MPWPB_Function::get_post_info( $attendee_id, 'mpwpb_date' );
					?>
					<ul>
						<li><strong><?php echo esc_html( MPWPB_Function::get_name() ); ?> :</strong>&nbsp;<?php echo get_the_title( $post_id ); ?></li>
						<li><strong><?php echo MPWPB_Function::get_service_text( $post_id ) . ' ' . esc_html__( ' Date : ', 'mpwpb_plugin' ); ?></strong>&nbsp;<?php echo MPWPB_Function::date_format( $date, 'full' ); ?></li>
						<li><strong><?php esc_attr_e( 'Booking Date : ', 'ttbm-pro' ); ?></strong>&nbsp;<?php echo MPWPB_Function::date_format( $attendee_info->post_date, 'full' ); ?></li>
						<?php do_action( 'mpwpb_after_order_info', $attendee_id ); ?>
					</ul>
					<?php
				}
			}
			public static function billing_info( $attendee_id ) {
				$billing_name = MPWPB_Function::get_post_info( $attendee_id, 'ttbm_billing_name' );
				$email        = MPWPB_Function::get_post_info( $attendee_id, 'ttbm_billing_email' );
				$phone        = MPWPB_Function::get_post_info( $attendee_id, 'ttbm_billing_phone' );
				$address      = MPWPB_Function::get_post_info( $attendee_id, 'ttbm_billing_address' );
				?>
				<ul>
					<?php if ( $billing_name ) { ?>
						<li><strong><?php esc_html_e( 'Name', 'ttbm-pro' ); ?> : &nbsp;</strong><?php echo esc_html( $billing_name ); ?></li>
					<?php } ?>
					<?php if ( $email ) { ?>
						<li><strong><?php esc_html_e( 'E-mail', 'ttbm-pro' ); ?> : &nbsp;</strong><?php echo esc_html( $email ); ?></li>
					<?php } ?>
					<?php if ( $phone ) { ?>
						<li><strong><?php esc_html_e( 'Phone', 'ttbm-pro' ); ?> : &nbsp;</strong><?php echo esc_html( $phone ); ?></li>
					<?php } ?>
					<?php if ( $address ) { ?>
						<li><strong><?php esc_html_e( 'Address', 'ttbm-pro' ); ?> : &nbsp;</strong><?php echo esc_html( $address ); ?></li>
					<?php } ?>
				</ul>
				<?php
			}
		}
		new MPWPB_Order_layout();
	}