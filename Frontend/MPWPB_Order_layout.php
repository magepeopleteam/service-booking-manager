<?php
	/*
* @Author 		engr.sumonazma@gmail.com
* Copyright: 	mage-people.com
*/
	if ( ! defined( 'ABSPATH' ) ) {
		die;
	} // Cannot access pages directly.
	if ( ! class_exists( 'MPWPB_Order_layout' ) ) {
		class MPWPB_Order_layout {
			public function __construct() {
				add_action( 'mpwpb_order_details', array( $this, 'order_details' ), 10, 1 );
			}
			public function order_details( $order_id ) {
				if ( $order_id ) {
					$wc_order     = wc_get_order( $order_id );
					$item_id      = current( array_keys( $wc_order->get_items() ) );
					$order_status = $wc_order->get_status();
					if ( $order_status != 'failed' ) {
						$total       = MP_Global_Function::get_post_info( $order_id, '_order_total' );
						$order_infos = MPWPB_Query::get_order_info( $order_id );
						if ( $order_infos->found_posts > 0 ) {
							$order_info = $order_infos->posts;
							if ( sizeof( $order_info ) > 0 ) {
								foreach ( $order_info as $order ) {
									$attendee_id = $order->ID;
									?>
									<div class="mpStyle">
										<div class="dLayout">
											<div class="flexWrap">
												<div class="col_5 col_xs_12">
													<?php self::order_info( $attendee_id ); ?>
													<div class="divider"></div>
													<?php self::billing_info( $attendee_id ); ?>
													<div class="divider"></div>>
												</div>
												<div class="col_1"></div>
												<div class="col_6 col_xs_12">
													<?php self::service_info( $attendee_id ); ?>
													<div class="divider"></div>
													<?php self::ex_service_info( $item_id ); ?>
													<div class="divider"></div>
													<h4 class="justifyBetween">
														<span><?php esc_html_e( 'Total Bill : ', 'service-booking-manager' ) ?></span>
														<span class="textTheme"><?php echo wc_price( $total ); ?></span>
													</h4>
												</div>
											</div>
										</div>
									</div>
									<?php
								}
							}
						}
					}
				}
			}
			public static function order_info( $attendee_id ) {
				if ( $attendee_id > 0 ) {
					$post_id       = MP_Global_Function::get_post_info( $attendee_id, 'mpwpb_id' );
					$order_id      = MP_Global_Function::get_post_info( $attendee_id, 'mpwpb_order_id' );
					$attendee_info = get_post( $attendee_id );
					$date          = MP_Global_Function::get_post_info( $attendee_id, 'mpwpb_date' );
					?>
					<h4><?php esc_html_e( 'Order details', 'service-booking-manager' ); ?></h4>
					<div class="divider"></div>
					<ul class="mp_list">
						<li><strong class="min_100"><?php esc_html_e( 'Order ID:', 'service-booking-manager' ); ?> :</strong>&nbsp;#<?php echo esc_html( $order_id ); ?></li>
						<li><strong class="min_100"><?php esc_html_e( 'Ticket No', 'service-booking-manager' ); ?> :</strong>&nbsp;<?php echo MP_Global_Function::get_post_info( $attendee_id, 'mpwpb_pin' ); ?></li>
						<li><strong class="min_100"><?php echo MPWPB_Function::get_service_text( $post_id ) . ' ' . esc_html__( ' Date : ', 'service-booking-manager' ); ?></strong>&nbsp;<?php echo MP_Global_Function::date_format( $date, 'full' ); ?></li>
						<li><strong class="min_100"><?php esc_attr_e( 'Booking Date : ', 'service-booking-manager' ); ?></strong>&nbsp;<?php echo MP_Global_Function::date_format( $attendee_info->post_date, 'full' ); ?></li>
						<li><strong class="min_100"><?php echo esc_html( MPWPB_Function::get_name() ); ?> :</strong>&nbsp;<?php echo get_the_title( $post_id ); ?></li>
					</ul>
					<?php
				}
			}
			public static function service_info( $attendee_id ) {
				if ( $attendee_id > 0 ) {
					$post_id      = MP_Global_Function::get_post_info( $attendee_id, 'mpwpb_id' );
					$category     = MP_Global_Function::get_post_info( $attendee_id, 'mpwpb_category' );
					$sub_category = MP_Global_Function::get_post_info( $attendee_id, 'mpwpb_sub_category' );
					$service      = MP_Global_Function::get_post_info( $attendee_id, 'mpwpb_service' );
					$price        = MP_Global_Function::get_post_info( $attendee_id, 'mpwpb_price' );
					?>
					<h4><?php echo MPWPB_Function::get_service_text( $post_id ) . ' ' . esc_html__( 'Information', 'service-booking-manager' ); ?></h4>
					<div class="divider"></div>
					<ul class="mp_list">
						<?php if ( $category ) { ?>
							<li><strong class="min_100"><?php echo esc_html( MPWPB_Function::get_category_text( $post_id ) ); ?> :</strong>&nbsp;<?php echo esc_html( $category ); ?></li>
						<?php } ?>
						<?php if ( $sub_category ) { ?>
							<li><strong class="min_100"><?php echo esc_html( MPWPB_Function::get_sub_category_text( $post_id ) ); ?> :</strong>&nbsp;<?php echo esc_html( $sub_category ); ?></li>
						<?php } ?>
						<li><strong class="min_100"><?php echo esc_html( MPWPB_Function::get_service_text( $post_id ) ); ?> :</strong>&nbsp;<?php echo esc_html( $service ); ?></li>
						<li><strong class="min_100"><?php esc_html_e( 'Price', 'service-booking-manager' ); ?> :</strong>&nbsp;<?php echo wc_price( $price ); ?></li>
						<?php do_action( 'mpwpb_after_order_info', $attendee_id ); ?>
					</ul>
					<?php
				}
			}
			public static function ex_service_info( $item_id ) {
				$post_id          = MP_Global_Function::get_order_item_meta( $item_id, '_mpwpb_id' );
				$ex_service       = MP_Global_Function::get_order_item_meta( $item_id, '_mpwpb_extra_service_info' );
				$ex_service_infos = $ex_service ? MP_Global_Function::data_sanitize( $ex_service ) : [];
				if ( sizeof( $ex_service_infos ) > 0 ) {
					?>
					<h4><?php echo esc_html__( 'Extra', 'service-booking-manager' ) . ' ' . MPWPB_Function::get_service_text( $post_id ); ?></h4>
					<div class="divider"></div>
					<ul class="mp_list">
						<?php
							foreach ( $ex_service_infos as $ex_service_info ) {
								$group_name = array_key_exists( 'ex_group_name', $ex_service_info ) ? $ex_service_info['ex_group_name'] : '';
								$name       = $ex_service_info['ex_name'];
								$price      = $ex_service_info['ex_price'];
								$qty        = $ex_service_info['ex_qty'];
								?>
								<li class="justifyBetween">
									<strong>
										<?php echo esc_html( $name ); ?>
										<?php if ( $group_name ) { ?>
											(<span class="textTheme"><?php echo esc_html( $group_name ) ?></span>)
										<?php } ?>
									</strong>
									<h6><span class="ex_service_qty">x<?php echo esc_html( $qty ); ?></span>&nbsp;|&nbsp;<?php echo wc_price( $price ); ?>=<?php echo wc_price( $price * $qty ); ?></h6>
								</li>
								<?php
							}
						?>
					</ul>
					<?php
				}
			}
			public static function billing_info( $attendee_id ) {
				$billing_name = MP_Global_Function::get_post_info( $attendee_id, 'mpwpb_billing_name' );
				$email        = MP_Global_Function::get_post_info( $attendee_id, 'mpwpb_billing_email' );
				$phone        = MP_Global_Function::get_post_info( $attendee_id, 'mpwpb_billing_phone' );
				$address      = MP_Global_Function::get_post_info( $attendee_id, 'mpwpb_billing_address' );
				?>
				<h4><?php esc_html_e( 'Billing information', 'service-booking-manager' ); ?></h4>
				<div class="divider"></div>
				<ul class="mp_list">
					<?php if ( $billing_name ) { ?>
						<li><strong class="min_100"><?php esc_html_e( 'Name', 'service-booking-manager' ); ?> : &nbsp;</strong><?php echo esc_html( $billing_name ); ?></li>
					<?php } ?>
					<?php if ( $email ) { ?>
						<li><strong class="min_100"><?php esc_html_e( 'E-mail', 'service-booking-manager' ); ?> : &nbsp;</strong><?php echo esc_html( $email ); ?></li>
					<?php } ?>
					<?php if ( $phone ) { ?>
						<li><strong class="min_100"><?php esc_html_e( 'Phone', 'service-booking-manager' ); ?> : &nbsp;</strong><?php echo esc_html( $phone ); ?></li>
					<?php } ?>
					<?php if ( $address ) { ?>
						<li><strong class="min_100"><?php esc_html_e( 'Address', 'service-booking-manager' ); ?> : &nbsp;</strong><?php echo esc_html( $address ); ?></li>
					<?php } ?>
				</ul>
				<?php
			}
		}
		new MPWPB_Order_layout();
	}