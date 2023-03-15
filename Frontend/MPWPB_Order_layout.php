<?php
	if ( ! defined( 'ABSPATH' ) ) {
		die;
	} // Cannot access pages directly.
	if ( ! class_exists( 'MPWPB_Order_layout' ) ) {
		class MPWPB_Order_layout {
			public function __construct() {
				add_action( 'mpwpb_order_details', array( $this, 'order_details' ), 10, 1 );
			}
			public function order_details( $order_id ) {
				$order_infos = MPWPB_Function::get_order_details( $order_id );
				if ( sizeof( $order_infos ) > 0 ) {
					$all_attendees = $order_infos[ $order_id ]['attendee_id'];
					$attendee_id   = $all_attendees[0];
					$post_id       = MPWPB_Function::get_post_info( $attendee_id, 'mpwpb_id' );
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
								<th><?php esc_html_e( 'Ticket price:', 'ttbm-pro' ); ?></th>
								<th><?php esc_html_e( 'Ticket type:', 'ttbm-pro' ) ?></th>
							</tr>
							</thead>
							<tbody>
							<?php
								if ( sizeof( $all_attendees ) > 0 ) {
									foreach ( $all_attendees as $attendee ) {
										?>
										<tr>
											<td><?php echo MPWPB_Function::get_post_info( $attendee, 'ttbm_pin' ); ?></td>
											<td><?php echo wc_price( MPWPB_Function::get_post_info( $attendee, 'ttbm_ticket_price' ) ); ?></td>
											<td><?php echo MPWPB_Function::get_post_info( $attendee, 'ttbm_ticket_name' ); ?></td>
										</tr>
										<?php
									}
								}
							?>
							</tbody>
						</table>
					</div>
					<?php
				}
			}
			public static function order_info( $attendee_id ) {
				if ( $attendee_id > 0 ) {
					$post_id     = MPWPB_Function::get_post_info( $attendee_id, 'mpwpb_id' );
					$attendee_info = get_post( $attendee_id );
					$date        = MPWPB_Function::get_post_info( $attendee_id, 'mpwpb_date' );
					?>
					<ul>
						<li><strong><?php echo esc_html( MPWPB_Function::get_name() ); ?> :</strong>&nbsp;<?php echo get_the_title( $post_id ); ?></li>
						<li><strong><?php esc_attr_e( 'Booking Date : ', 'ttbm-pro' ); ?></strong>&nbsp;<?php echo date_i18n( 'full', $attendee_info->post_date ); ?></li>
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