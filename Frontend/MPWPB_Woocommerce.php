<?php
	if ( ! defined( 'ABSPATH' ) ) {
		die;
	} // Cannot access pages directly.
	if ( ! class_exists( 'MPWPB_Woocommerce' ) ) {
		class MPWPB_Woocommerce {
			public function __construct() {
				add_filter( 'woocommerce_add_cart_item_data', array( $this, 'add_cart_item_data' ), 90, 3 );
				add_action( 'woocommerce_before_calculate_totals', array( $this, 'before_calculate_totals' ), 90, 1 );
				add_filter( 'woocommerce_cart_item_thumbnail', array( $this, 'cart_item_thumbnail' ), 90, 3 );
				add_filter( 'woocommerce_get_item_data', array( $this, 'get_item_data' ), 90, 2 );
				//************//
				add_action( 'woocommerce_after_checkout_validation', array( $this, 'after_checkout_validation' ) );
				add_action( 'woocommerce_checkout_create_order_line_item', array( $this, 'checkout_create_order_line_item' ), 90, 4 );
				add_action( 'woocommerce_checkout_order_processed', array( $this, 'checkout_order_processed' ), 10 );
				add_filter( 'woocommerce_order_status_changed', array( $this, 'order_status_changed' ), 10, 4 );
			}
			public function add_cart_item_data( $cart_item_data, $product_id ) {
				$linked_id  = MPWPB_Function::get_post_info( $product_id, 'link_mpwpb_id', $product_id );
				$product_id = is_string( get_post_status( $linked_id ) ) ? $linked_id : $product_id;
				if ( get_post_type( $product_id ) == MPWPB_Function::get_cpt_name() ) {
					$category                                   = MPWPB_Function::get_submit_info( 'mpwpb_category' );
					$sub_category                               = MPWPB_Function::get_submit_info( 'mpwpb_sub_category' );
					$service                                    = MPWPB_Function::get_submit_info( 'mpwpb_service' );
					$date                                       = MPWPB_Function::get_submit_info( 'mpwpb_date' );
					$price                                      = MPWPB_Function::get_price( $product_id, $service, $category, $sub_category, $date );
					$total_price                                = $this->get_cart_total_price( $product_id );
					$cart_item_data['mpwpb_category']           = $category;
					$cart_item_data['mpwpb_sub_category']       = $sub_category;
					$cart_item_data['mpwpb_service']            = $service;
					$cart_item_data['mpwpb_date']               = $date;
					$cart_item_data['mpwpb_price']              = $price;
					$cart_item_data['mpwpb_user_info']          = apply_filters( 'add_mpwpb_user_info_data', array(), $product_id );
					$cart_item_data['mpwpb_extra_service_info'] = self::cart_extra_service_info( $product_id );
					$cart_item_data['mpwpb_tp']                 = $total_price;
					$cart_item_data['line_total']               = $total_price;
					$cart_item_data['line_subtotal']            = $total_price;
					$cart_item_data                             = apply_filters( 'mpwpb_add_cart_item', $cart_item_data, $product_id );
				}
				$cart_item_data['mpwpb_id'] = $product_id;
				//echo '<pre>'; print_r( $cart_item_data ); echo '</pre>'; die();
				return $cart_item_data;
			}
			public function before_calculate_totals( $cart_object ): void {
				foreach ( $cart_object->cart_contents as $value ) {
					$post_id = array_key_exists( 'mpwpb_id', $value ) ? $value['mpwpb_id'] : 0;
					if ( get_post_type( $post_id ) == MPWPB_Function::get_cpt_name() ) {
						$total_price = $value['mpwpb_tp'];
						$value['data']->set_price( $total_price );
						$value['data']->set_regular_price( $total_price );
						$value['data']->set_sale_price( $total_price );
						$value['data']->set_sold_individually( 'yes' );
						$value['data']->get_price();
					}
				}
			}
			public function cart_item_thumbnail( $thumbnail, $cart_item ) {
				$post_id = array_key_exists( 'mpwpb_id', $cart_item ) ? $cart_item['mpwpb_id'] : 0;
				if ( get_post_type( $post_id ) == MPWPB_Function::get_cpt_name() ) {
					$thumbnail = '<div class="bg_image_area" data-href="' . get_the_permalink( $post_id ) . '"><div data-bg-image="' . MPWPB_Function::get_image_url( $post_id ) . '"></div></div>';
				}
				return $thumbnail;
			}
			public function get_item_data( $item_data, $cart_item ) {
				ob_start();
				$post_id = array_key_exists( 'mpwpb_id', $cart_item ) ? $cart_item['mpwpb_id'] : 0;
				if ( get_post_type( $post_id ) == MPWPB_Function::get_cpt_name() ) {
					$this->show_cart_item( $cart_item, $post_id );
					do_action( 'mpwpb_show_cart_item', $cart_item, $post_id );
				}
				$item_data[] = array( 'key' => ob_get_clean() );
				return $item_data;
			}
			//**************//
			public function after_checkout_validation() {
				global $woocommerce;
				$items = $woocommerce->cart->get_cart();
				foreach ( $items as $values ) {
					$post_id = array_key_exists( 'mpwpb_id', $values ) ? $values['mpwpb_id'] : 0;
					if ( get_post_type( $post_id ) == MPWPB_Function::get_cpt_name() ) {
						do_action( 'mpwpb_validate_cart_item', $values, $post_id );
					}
				}
			}
			public function checkout_create_order_line_item( $item, $cart_item_key, $values ) {
				$post_id = array_key_exists( 'mpwpb_id', $values ) ? $values['mpwpb_id'] : 0;
				if ( get_post_type( $post_id ) == MPWPB_Function::get_cpt_name() ) {
					$category      = $values['mpwpb_category'] ?: '';
					$sub_category      = $values['mpwpb_sub_category'] ?: '';
					$service       = $values['mpwpb_service'] ?: '';
					$date          = $values['mpwpb_date'] ?: '';
					$price         = $values['mpwpb_price'] ?: '';
					$extra_service = $values['mpwpb_extra_service_info'] ?: [];
					$user_info     = $values['mpwpb_user_info'] ?: [];
					if($category){
						$item->add_meta_data( esc_html__( 'Category ', 'mpwpb_plugin' ), $category );
						if($sub_category){
							$item->add_meta_data( esc_html__( 'Sub-Category ', 'mpwpb_plugin' ), $sub_category );
						}
					}

					$item->add_meta_data( esc_html__( 'Service Name ', 'mpwpb_plugin' ), $service );
					$item->add_meta_data( esc_html__( 'Price ', 'mpwpb_plugin' ), $price );
					$item->add_meta_data( esc_html__( 'Date ', 'mpwpb_plugin' ), esc_html( MPWPB_Function::date_format( $date ) ) );
					$item->add_meta_data( esc_html__( 'Time ', 'mpwpb_plugin' ), esc_html( MPWPB_Function::date_format( $date, 'time' ) ) );
					if ( sizeof( $extra_service ) > 0 ) {
						foreach ( $extra_service as $service ) {
							$item->add_meta_data( esc_html__( 'Services Name ', 'mpwpb_plugin' ), $service['service_name'] . ' (' . esc_html( $service['service_category_name'] ) . ')' );
							$item->add_meta_data( esc_html__( 'Price ', 'mpwpb_plugin' ), ' ( ' . MPWPB_Function::wc_price( $post_id, $service['service_price'] ) . ' x 1) = ' . MPWPB_Function::wc_price( $post_id, ( $service['service_price'] * 1 ) ) );
						}
					}
					$item->add_meta_data( '_mpwpb_id', $post_id );
					$item->add_meta_data( '_mpwpb_date', $date );
					if($category){
						$item->add_meta_data( '_mpwpb_category', $category );
						if($sub_category){
							$item->add_meta_data( '_mpwpb_sub_category', $sub_category );
						}
					}
					$item->add_meta_data( '_mpwpb_service', $service );
					$item->add_meta_data( '_mpwpb_price', $price );
					$item->add_meta_data( '_mpwpb_user_info', $user_info );
					$item->add_meta_data( '_mpwpb_service_info', $extra_service );
					do_action( 'mpwpb_checkout_create_order_line_item', $item, $values );
				}
			}
			public function checkout_order_processed( $order_id ) {
				if ( $order_id ) {
					$order          = wc_get_order( $order_id );
					$order_status   = $order->get_status();
					$order_meta     = get_post_meta( $order_id );
					$payment_method = $order_meta['_payment_method_title'][0] ?? '';
					$user_id        = $order_meta['_customer_user'][0] ?? '';
					if ( $order_status != 'failed' ) {
						//$item_id = current( array_keys( $order->get_items() ) );
						foreach ( $order->get_items() as $item_id => $item ) {
							$post_id = MPWPB_Query::get_order_meta( $item_id, '_mpwpb_id' );
							if ( get_post_type( $post_id ) == MPWPB_Function::get_cpt_name() ) {
								$date                          = self::get_order_item_meta( $item_id, '_mpwpb_date' );
								$date                          = $date ? MPWPB_Function::data_sanitize( $date ) : '';
								$start_category                = self::get_order_item_meta( $item_id, '_mpwpb_category' );
								$start_category                = $start_category ? MPWPB_Function::data_sanitize( $start_category ) : '';
								$service                       = self::get_order_item_meta( $item_id, '_mpwpb_service' );
								$service                       = $service ? MPWPB_Function::data_sanitize( $service ) : '';
								$price                         = self::get_order_item_meta( $item_id, '_mpwpb_price' );
								$price                         = $price ? MPWPB_Function::data_sanitize( $price ) : '';
								$user_info                     = self::get_order_item_meta( $item_id, '_mpwpb_user_info' );
								$user_info                     = $user_info ? MPWPB_Function::data_sanitize( $user_info ) : [];
								$data['mpwpb_id']              = $post_id;
								$data['mpwpb_date']            = $date;
								$data['mpwpb_category']        = $start_category;
								$data['mpwpb_service']         = $service;
								$data['mpwpb_price']           = $price;
								$data['mpwpb_order_id']        = $order_id;
								$data['mpwpb_order_status']    = $order_status;
								$data['mpwpb_payment_method']  = $payment_method;
								$data['mpwpb_user_id']         = $user_id;
								$data['mpwpb_billing_name']    = $order_meta['_billing_first_name'][0] . ' ' . $order_meta['_billing_last_name'][0];
								$data['mpwpb_billing_email']   = $order_meta['_billing_email'][0];
								$data['mpwpb_billing_phone']   = $order_meta['_billing_phone'][0];
								$data['mpwpb_billing_address'] = $order_meta['_billing_address_1'][0] . ' ' . $order_meta['_billing_address_2'][0];
								$user_data                     = apply_filters( 'mpwpb_user_booking_data', $data, $post_id, $user_info );
								self::add_cpt_data( 'mpwpb_booking', $user_data['mpwpb_billing_name'], $user_data );
								$service      = self::get_order_item_meta( $item_id, '_mpwpb_service_info' );
								$service_info = $service ? MPWPB_Function::data_sanitize( $service ) : [];
								if ( sizeof( $service_info ) > 0 ) {
									foreach ( $service_info as $service ) {
										$ex_data['mpwpb_id']                    = $post_id;
										$ex_data['mpwpb_date']                  = $date;
										$ex_data['mpwpb_order_id']              = $order_id;
										$ex_data['mpwpb_order_status']          = $order_status;
										$ex_data['mpwpb_service_category_name'] = $service['service_category_name'];
										$ex_data['mpwpb_service_name']          = $service['service_name'];
										$ex_data['mpwpb_service_price']         = $service['service_price'];
										$ex_data['mpwpb_payment_method']        = $payment_method;
										$ex_data['mpwpb_user_id']               = $user_id;
										self::add_cpt_data( 'mpwpb_service_booking', '#' . $order_id . $ex_data['mpwpb_service_name'], $ex_data );
									}
								}
							}
						}
					}
				}
			}
			public function order_status_changed( $order_id ) {
				$order        = wc_get_order( $order_id );
				$order_status = $order->get_status();
				foreach ( $order->get_items() as $item_id => $item_values ) {
					$post_id = MPWPB_Query::get_order_meta( $item_id, '_mpwpb_id' );
					if ( get_post_type( $post_id ) == MPWPB_Function::get_cpt_name() ) {
						if ( $order->has_status( 'processing' ) || $order->has_status( 'pending' ) || $order->has_status( 'on-hold' ) || $order->has_status( 'completed' ) || $order->has_status( 'cancelled' ) || $order->has_status( 'refunded' ) || $order->has_status( 'failed' ) || $order->has_status( 'requested' ) ) {
							$this->wc_order_status_change( $order_status, $post_id, $order_id );
						}
					}
				}
			}
			//**************************//
			public function show_cart_item( $cart_item, $post_id ) {
				$extra_service = $cart_item['mpwpb_extra_service_info'] ?: [];
				?>
				<div class="mpStyle">
					<?php do_action( 'mpwpb_before_cart_item_display', $cart_item, $post_id ); ?>
					<div class="dLayout_xs">
						<ul class="cart_list">
							<?php if ( $cart_item['mpwpb_category'] ) { ?>
								<li>
									<h6><?php esc_html_e( 'Category Name : ', 'mpwpb_plugin' ); ?></h6>&nbsp;
									<span><?php echo esc_html( $cart_item['mpwpb_category'] ); ?></span>
								</li>
							<?php } ?>
							<?php if ( $cart_item['mpwpb_sub_category'] ) { ?>
								<li>
									<h6><?php esc_html_e( 'Sub-Category Name : ', 'mpwpb_plugin' ); ?></h6>&nbsp;
									<span><?php echo esc_html( $cart_item['mpwpb_sub_category'] ); ?></span>
								</li>
							<?php } ?>
							<li>
								<h6><?php esc_html_e( 'Service Name : ', 'mpwpb_plugin' ); ?></h6>&nbsp;
								<span><?php echo esc_html( $cart_item['mpwpb_service'] ); ?></span>
							</li>
							<li>
								<h6><?php esc_html_e( 'Price : ', 'mpwpb_plugin' ); ?></h6>&nbsp;
								<span><?php echo ' ( ' . MPWPB_Function::wc_price( $post_id, $cart_item['mpwpb_price'] ) . ' x 1 ) = ' . MPWPB_Function::wc_price( $post_id, ( $cart_item['mpwpb_price'] * 1 ) ); ?></span>
							</li>
							<li>
								<span class="far fa-calendar-alt"></span>
								<h6><?php esc_html_e( 'Date : ', 'mpwpb_plugin' ); ?></h6>&nbsp;
								<span><?php echo esc_html( MPWPB_Function::date_format( $cart_item['mpwpb_date'] ) ); ?></span>
							</li>
							<li>
								<span class="far fa-clock"></span>
								<h6><?php esc_html_e( 'Time : ', 'mpwpb_plugin' ); ?></h6>&nbsp;
								<span><?php echo esc_html( MPWPB_Function::date_format( $cart_item['mpwpb_date'], 'time' ) ); ?></span>
							</li>
						</ul>
					</div>
					<?php if ( sizeof( $extra_service ) > 0 ) { ?>
						<div class="dLayout_xs">
							<h5 class="mb_xs"><?php esc_html_e( 'Extra Services', 'mpwpb_plugin' ); ?></h5>
							<?php foreach ( $extra_service as $service ) { ?>
								<div class="divider"></div>
								<div class="dFlex">
									<h6><?php esc_html_e( 'Services Name : ', 'mpwpb_plugin' ); ?></h6>&nbsp;
									<span><?php echo esc_html( $service['service_name'] ) . ' (' . esc_html( $service['service_category_name'] ) . ')'; ?></span>
								</div>
								<div class="dFlex">
									<h6><?php esc_html_e( 'Price : ', 'mpwpb_plugin' ); ?></h6>&nbsp;
									<span><?php echo ' ( ' . MPWPB_Function::wc_price( $post_id, $service['service_price'] ) . ' x ' . $service['service_qty'] . ' ) = ' . MPWPB_Function::wc_price( $post_id, ( $service['service_price'] * $service['service_qty'] ) ); ?></span>
								</div>
							<?php } ?>
						</div>
					<?php } ?>
					<?php do_action( 'mpwpb_after_cart_item_display', $cart_item, $post_id ); ?>
				</div>
				<?php
			}
			public function wc_order_status_change( $order_status, $post_id, $order_id ) {
				$args = array(
					'post_type'      => 'mpwpb_booking',
					'posts_per_page' => - 1,
					'meta_query'     => array(
						'relation' => 'AND',
						array(
							array(
								'key'     => 'mpwpb_id',
								'value'   => $post_id,
								'compare' => '='
							),
							array(
								'key'     => 'mpwpb_order_id',
								'value'   => $order_id,
								'compare' => '='
							)
						)
					)
				);
				$loop = new WP_Query( $args );
				foreach ( $loop->posts as $user ) {
					$user_id = $user->ID;
					update_post_meta( $user_id, 'mpwpb_order_status', $order_status );
				}
				$args = array(
					'post_type'      => 'mpwpb_service_booking',
					'posts_per_page' => - 1,
					'meta_query'     => array(
						'relation' => 'AND',
						array(
							array(
								'key'     => 'mpwpb_id',
								'value'   => $post_id,
								'compare' => '='
							),
							array(
								'key'     => 'mpwpb_order_id',
								'value'   => $order_id,
								'compare' => '='
							)
						)
					)
				);
				$loop = new WP_Query( $args );
				foreach ( $loop->posts as $user ) {
					$user_id = $user->ID;
					update_post_meta( $user_id, 'mpwpb_order_status', $order_status );
				}
			}
			//**********************//
			public static function cart_extra_service_info( $post_id ): array {
				$date                  = MPWPB_Function::get_submit_info( 'mpwpb_date' );
				$ex_service_categories = MPWPB_Function::get_submit_info( 'mpwpb_extra_service', array() );
				$ex_service_types      = MPWPB_Function::get_submit_info( 'mpwpb_extra_service_type', array() );
				$ex_service_qty        = MPWPB_Function::get_submit_info( 'mpwpb_extra_service_qty', array() );
				$extra_service         = array();
				if ( sizeof( $ex_service_categories ) > 0 ) {
					$count = 0;
					foreach ( $ex_service_categories as $key => $ex_service_category ) {
						if ( $ex_service_category && $ex_service_types[ $key ] ) {
							$ex_price                                         = MPWPB_Function::get_extra_price( $post_id, $ex_service_category, $ex_service_types[ $key ] );
							$extra_service[ $count ]['service_category_name'] = $ex_service_category;
							$extra_service[ $count ]['service_name']          = $ex_service_types[ $key ];
							$extra_service[ $count ]['service_price']         = $ex_price;
							$extra_service[ $count ]['service_qty']           = $ex_service_qty[ $key ];
							$extra_service[ $count ]['mpwpb_date']            = $date ?? '';
							$count ++;
						}
					}
				}
				return $extra_service;
			}
			public function get_cart_total_price( $post_id ) {
				$category_name         = MPWPB_Function::get_submit_info( 'mpwpb_category' );
				$sub_category_name     = MPWPB_Function::get_submit_info( 'mpwpb_sub_category' );
				$service_name          = MPWPB_Function::get_submit_info( 'mpwpb_service' );
				$date                  = MPWPB_Function::get_submit_info( 'mpwpb_date' );
				$price                 = MPWPB_Function::get_price( $post_id, $service_name, $category_name, $sub_category_name, $date );
				$ex_service_categories = MPWPB_Function::get_submit_info( 'mpwpb_extra_service', array() );
				$ex_service_types      = MPWPB_Function::get_submit_info( 'mpwpb_extra_service_type', array() );
				$ex_service_qty        = MPWPB_Function::get_submit_info( 'mpwpb_extra_service_qty', array() );
				$ex_price              = 0;
				if ( sizeof( $ex_service_categories ) > 0 ) {
					foreach ( $ex_service_categories as $key => $ex_service_category ) {
						if ( $ex_service_category && $ex_service_types[ $key ] && $ex_service_qty[ $key ] > 0 ) {
							$ex_price = $ex_price + MPWPB_Function::get_extra_price( $post_id, $ex_service_category, $ex_service_types[ $key ] ) * $ex_service_qty[ $key ];
						}
					}
				}
				$total_price = $price + $ex_price;
				return max( 0, $total_price );
			}
			public static function add_cpt_data( $cpt_name, $title, $meta_data = array(), $status = 'publish', $cat = array() ) {
				$new_post = array(
					'post_title'    => $title,
					'post_content'  => '',
					'post_category' => $cat,
					'tags_input'    => array(),
					'post_status'   => $status,
					'post_type'     => $cpt_name
				);
				$post_id  = wp_insert_post( $new_post );
				if ( sizeof( $meta_data ) > 0 ) {
					foreach ( $meta_data as $key => $value ) {
						update_post_meta( $post_id, $key, $value );
					}
				}
				if ( $cpt_name == 'ttbm_booking' ) {
					$ttbm_pin = $meta_data['ttbm_user_id'] . $meta_data['ttbm_order_id'] . $meta_data['ttbm_id'] . $post_id;
					update_post_meta( $post_id, 'ttbm_pin', $ttbm_pin );
				}
			}
			public static function get_order_item_meta( $item_id, $key ): string {
				global $wpdb;
				$table_name = $wpdb->prefix . "woocommerce_order_itemmeta";
				$results    = $wpdb->get_results( $wpdb->prepare( "SELECT meta_value FROM $table_name WHERE order_item_id = %d AND meta_key = %s", $item_id, $key ) );
				foreach ( $results as $result ) {
					$value = $result->meta_value;
				}
				return $value ?? '';
			}
		}
		new MPWPB_Woocommerce();
	}