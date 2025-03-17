<?php
	/*
* @Author 		engr.sumonazma@gmail.com
* Copyright: 	mage-people.com
*/
	if (!defined('ABSPATH')) {
		die;
	} // Cannot access pages directly.
	if (!class_exists('MPWPB_Woocommerce')) {
		class MPWPB_Woocommerce {
			public function __construct() {
				add_filter('woocommerce_add_cart_item_data', array($this, 'add_cart_item_data'), 90, 3);
				add_action('woocommerce_before_calculate_totals', array($this, 'before_calculate_totals'), 90, 1);
				add_filter('woocommerce_cart_item_thumbnail', array($this, 'cart_item_thumbnail'), 90, 3);
				add_filter('woocommerce_get_item_data', array($this, 'get_item_data'), 90, 2);
				//************//
				//add_filter('woocommerce_add_to_cart_redirect', [$this, 'add_to_cart_redirect'], 10, 2);
				//************//
				add_action('woocommerce_after_checkout_validation', array($this, 'after_checkout_validation'));
				add_action('woocommerce_checkout_create_order_line_item', array($this, 'checkout_create_order_line_item'), 90, 4);
				add_action('woocommerce_checkout_order_processed', array($this, 'checkout_order_processed'), 90, 3);
				add_action('woocommerce_store_api_checkout_order_processed', array($this, 'checkout_order_processed'), 90, 3);
				add_filter('woocommerce_order_status_changed', array($this, 'order_status_changed'), 10, 4);
				/*****************************/
				add_action('wp_ajax_mpwpb_add_to_cart', [$this, 'mpwpb_add_to_cart']);
				add_action('wp_ajax_nopriv_mpwpb_add_to_cart', [$this, 'mpwpb_add_to_cart']);
			}
			public function add_cart_item_data($cart_item_data, $product_id) {
				$linked_id = MP_Global_Function::get_post_info($product_id, 'link_mpwpb_id', $product_id);
				$product_id = is_string(get_post_status($linked_id)) ? $linked_id : $product_id;
				if (isset($_POST['nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'mpwpb_nonce')) {
					if (get_post_type($product_id) == MPWPB_Function::get_cpt()) {
						$category = isset($_POST['mpwpb_category']) ? sanitize_text_field(wp_unslash($_POST['mpwpb_category'])) : '';
						$sub_category = isset($_POST['mpwpb_sub_category']) ? sanitize_text_field(wp_unslash($_POST['mpwpb_sub_category'])) : '';
						$services = isset($_POST['mpwpb_service']) ? array_map('sanitize_text_field', wp_unslash($_POST['mpwpb_service'])) : [];
						$date = isset($_POST['mpwpb_date']) ? sanitize_text_field(wp_unslash($_POST['mpwpb_date'])) : '';
						$all_service = [];
						if (is_array($services) && sizeof($services)) {
							foreach ($services as $key => $service) {
								$all_service[$key]['name'] = MPWPB_Function::get_service_name($product_id, $service);
								$all_service[$key]['price'] = MPWPB_Function::get_price($product_id, $service, $date);
							}
						}
						$ex_service_types = isset($_POST['mpwpb_extra_service_type']) ? array_map('sanitize_text_field', wp_unslash($_POST['mpwpb_extra_service_type'])) : [];
						$ex_service_qty = isset($_POST['mpwpb_extra_service_qty']) ? array_map('sanitize_text_field', wp_unslash($_POST['mpwpb_extra_service_qty'])) : [];
						$ex_service_group = isset($_POST['mpwpb_extra_service']) ? array_map('sanitize_text_field', wp_unslash($_POST['mpwpb_extra_service'])) : [];
						$total_price = self::get_cart_total_price($product_id, $all_service, $ex_service_types, $ex_service_qty, $ex_service_group);
						$cart_item_data['mpwpb_category'] = MPWPB_Function::get_category_name($product_id, $category);
						$cart_item_data['mpwpb_sub_category'] = MPWPB_Function::get_sub_category_name($product_id, $sub_category);
						$cart_item_data['mpwpb_service'] = $all_service;
						$cart_item_data['mpwpb_date'] = $date;
						$cart_item_data['mpwpb_extra_service_info'] = self::cart_extra_service_info($product_id, $date, $ex_service_types, $ex_service_qty);
						$cart_item_data['mpwpb_tp'] = $total_price;
						$cart_item_data['line_total'] = $total_price;
						$cart_item_data['line_subtotal'] = $total_price;
						$cart_item_data = apply_filters('mpwpb_add_cart_item', $cart_item_data, $product_id);
					}
					$cart_item_data['mpwpb_id'] = $product_id;
				}
				//echo '<pre>'; print_r( $cart_item_data ); echo '</pre>'; die();
				return $cart_item_data;
			}
			public function before_calculate_totals($cart_object): void {
				foreach ($cart_object->cart_contents as $value) {
					$post_id = array_key_exists('mpwpb_id', $value) ? $value['mpwpb_id'] : 0;
					if (get_post_type($post_id) == MPWPB_Function::get_cpt()) {
						$total_price = $value['mpwpb_tp'];
						$value['data']->set_price($total_price);
						$value['data']->set_regular_price($total_price);
						$value['data']->set_sale_price($total_price);
						$value['data']->set_sold_individually('yes');
						$value['data']->get_price();
					}
				}
			}
			public function cart_item_thumbnail($thumbnail, $cart_item) {
				$post_id = array_key_exists('mpwpb_id', $cart_item) ? $cart_item['mpwpb_id'] : 0;
				if (get_post_type($post_id) == MPWPB_Function::get_cpt()) {
					$thumbnail = '<div class="bg_image_area" data-href="' . get_the_permalink($post_id) . '"><div data-bg-image="' . MP_Global_Function::get_image_url($post_id) . '"></div></div>';
				}
				return $thumbnail;
			}
			public function get_item_data($item_data, $cart_item) {
				ob_start();
				$post_id = array_key_exists('mpwpb_id', $cart_item) ? $cart_item['mpwpb_id'] : 0;
				if (get_post_type($post_id) == MPWPB_Function::get_cpt()) {
					$this->show_cart_item($cart_item, $post_id);
					do_action('mpwpb_show_cart_item', $cart_item, $post_id);
				}
				$item_data[] = array('key' => esc_html__('Booking Details ', 'service-booking-manager'), 'value' => ob_get_clean());
				return $item_data;
			}
			//**************//
			public function after_checkout_validation() {
				global $woocommerce;
				$items = $woocommerce->cart->get_cart();
				foreach ($items as $values) {
					$post_id = array_key_exists('mpwpb_id', $values) ? $values['mpwpb_id'] : 0;
					if (get_post_type($post_id) == MPWPB_Function::get_cpt()) {
						//wc_add_notice( __( "custom_notice", 'fake_error' ), 'error');
						do_action('mpwpb_validate_cart_item', $values, $post_id);
					}
				}
			}
			public function checkout_create_order_line_item($item, $cart_item_key, $values) {
				$post_id = array_key_exists('mpwpb_id', $values) ? $values['mpwpb_id'] : 0;
				if (get_post_type($post_id) == MPWPB_Function::get_cpt()) {
					$category = $values['mpwpb_category'] ?: '';
					$sub_category = $values['mpwpb_sub_category'] ?: '';
					$services = $values['mpwpb_service'] ?: [];
					$date = $values['mpwpb_date'] ?: '';
					$total_price = $values['mpwpb_tp'] ?? '';
					$extra_service = $values['mpwpb_extra_service_info'] ?: [];
					if ($category) {
						$item->add_meta_data(MPWPB_Function::get_category_text($post_id), $category);
						if ($sub_category) {
							$item->add_meta_data(MPWPB_Function::get_sub_category_text($post_id), $sub_category);
						}
					}
					if (is_array($services) && sizeof($services)) {
						foreach ($services as $service) {
							$item->add_meta_data(MPWPB_Function::get_service_text($post_id), $service['name']);
							$item->add_meta_data(esc_html__('Price ', 'service-booking-manager'), MP_Global_Function::wc_price($post_id, $service['price']));
						}
					}
					$item->add_meta_data(esc_html__('Date ', 'service-booking-manager'), esc_html(MP_Global_Function::date_format($date)));
					$item->add_meta_data(esc_html__('Time ', 'service-booking-manager'), esc_html(MP_Global_Function::date_format($date, 'time')));
					if (sizeof($extra_service) > 0) {
						foreach ($extra_service as $ex_service) {
							$item->add_meta_data(esc_html__('Services Name ', 'service-booking-manager'), $ex_service['ex_name']);
							$item->add_meta_data(esc_html__('Quantity ', 'service-booking-manager'), $ex_service['ex_qty']);
							$item->add_meta_data(esc_html__('Price ', 'service-booking-manager'), ' ( ' . MP_Global_Function::wc_price($post_id, $ex_service['ex_price']) . ' x ' . $ex_service['ex_qty'] . ') = ' . MP_Global_Function::wc_price($post_id, ($ex_service['ex_price'] * $ex_service['ex_qty'])));
						}
					}
					$item->add_meta_data('_mpwpb_id', $post_id);
					$item->add_meta_data('_mpwpb_date', $date);
					if ($category) {
						$item->add_meta_data('_mpwpb_category', $category);
						if ($sub_category) {
							$item->add_meta_data('_mpwpb_sub_category', $sub_category);
						}
					}
					$item->add_meta_data('_mpwpb_service', $services);
					$item->add_meta_data('_mpwpb_tp', $total_price);
					$item->add_meta_data('_mpwpb_extra_service_info', $extra_service);
					do_action('mpwpb_checkout_create_order_line_item', $item, $values);
				}
			}
			public function checkout_order_processed($order_id) {
				if (is_object($order_id)) {
					$order_id = $order_id->get_id();
				}
				if ($order_id) {
					$order = wc_get_order($order_id);
					$order_status = $order->get_status();
					if ($order_status != 'failed') {
						//$item_id = current( array_keys( $order->get_items() ) );
						foreach ($order->get_items() as $item_id => $item) {
							$post_id = wc_get_order_item_meta($item_id, '_mpwpb_id');
							if (get_post_type($post_id) == MPWPB_Function::get_cpt()) {
								$date = wc_get_order_item_meta($item_id, '_mpwpb_date');
								$date = $date ? MP_Global_Function::data_sanitize($date) : '';
								$category = wc_get_order_item_meta($item_id, '_mpwpb_category');
								$category = $category ? MP_Global_Function::data_sanitize($category) : '';
								$sub_category = wc_get_order_item_meta($item_id, '_mpwpb_sub_category');
								$sub_category = $sub_category ? MP_Global_Function::data_sanitize($sub_category) : '';
								$service = wc_get_order_item_meta($item_id, '_mpwpb_service');
								$service = $service ? MP_Global_Function::data_sanitize($service) : [];
								$total_price = wc_get_order_item_meta($item_id, '_mpwpb_tp');
								$total_price = $total_price ? MP_Global_Function::data_sanitize($total_price) : '';
								$ex_service = wc_get_order_item_meta($item_id, '_mpwpb_extra_service_info');
								$ex_service_infos = $ex_service ? MP_Global_Function::data_sanitize($ex_service) : [];
								$data['mpwpb_id'] = $post_id;
								$data['mpwpb_date'] = $date;
								if ($category) {
									$data['mpwpb_category'] = $category;
									if ($sub_category) {
										$data['mpwpb_sub_category'] = $sub_category;
									}
								}
								$data['mpwpb_service'] = $service;
								$data['mpwpb_tp'] = $total_price;
								$data['mpwpb_service_info'] = $ex_service_infos;
								$data['mpwpb_order_id'] = $order_id;
								$data['mpwpb_order_status'] = $order_status;
								$data['mpwpb_payment_method'] = $order->get_payment_method();
								$data['mpwpb_user_id'] = $order->get_user_id() ?? '';
								$data['mpwpb_extra_service_info'] = $ex_service_infos;
								$data['mpwpb_billing_name'] = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
								$data['mpwpb_billing_email'] = $order->get_billing_email();
								$data['mpwpb_billing_phone'] = $order->get_billing_phone();
								$data['mpwpb_billing_address'] = $order->get_billing_address_1() . ' ' . $order->get_billing_address_2();
								$booking_data = apply_filters('add_mpwpb_booking_data', $data, $post_id);
								self::add_cpt_data('mpwpb_booking', $booking_data['mpwpb_billing_name'], $booking_data);
								if (sizeof($ex_service_infos) > 0) {
									foreach ($ex_service_infos as $ex_service_info) {
										$ex_data['mpwpb_id'] = $post_id;
										$ex_data['mpwpb_date'] = $date;
										$ex_data['mpwpb_order_id'] = $order_id;
										$ex_data['mpwpb_order_status'] = $order_status;
										$ex_data['mpwpb_ex_name'] = $ex_service_info['ex_name'];
										$ex_data['mpwpb_ex_price'] = $ex_service_info['ex_price'];
										$ex_data['mpwpb_ex_qty'] = $ex_service_info['ex_qty'];
										$ex_data['mpwpb_payment_method'] = $order->get_payment_method();
										$ex_data['mpwpb_user_id'] = $order->get_user_id() ?? '';
										self::add_cpt_data('mpwpb_extra_service_booking', '#' . $order_id . $ex_data['mpwpb_ex_name'], $ex_data);
									}
								}
							}
						}
					}
				}
			}
			public function order_status_changed($order_id) {
				$order = wc_get_order($order_id);
				$order_status = $order->get_status();
				foreach ($order->get_items() as $item_id => $item_values) {
					$post_id = wc_get_order_item_meta($item_id, '_mpwpb_id');
					if (get_post_type($post_id) == MPWPB_Function::get_cpt()) {
						$this->wc_order_status_change($order_status, $post_id, $order_id);
					}
				}
			}
			//**************************//
			public function show_cart_item($cart_item, $post_id) {
				$extra_service = $cart_item['mpwpb_extra_service_info'] ?: [];
				?>
                <div class="mpStyle">
					<?php do_action('mpwpb_before_cart_item_display', $cart_item, $post_id); ?>
                    <div class="dLayout_xs">
                        <ul class="cart_list">
							<?php if ($cart_item['mpwpb_category']) { ?>
                                <li>
                                    <h6><?php echo esc_html(MPWPB_Function::get_category_text($post_id)); ?>&nbsp;:&nbsp;</h6>
                                    <span><?php echo esc_html($cart_item['mpwpb_category']); ?></span>
                                </li>
							<?php } ?>
							<?php if ($cart_item['mpwpb_sub_category']) { ?>
                                <li>
                                    <h6><?php echo esc_html(MPWPB_Function::get_sub_category_text($post_id)); ?>&nbsp;:&nbsp;</h6>
                                    <span><?php echo esc_html($cart_item['mpwpb_sub_category']); ?></span>
                                </li>
							<?php } ?>
							<?php
								$services = $cart_item['mpwpb_service'];
								if (is_array($services) && sizeof($services)) {
									foreach ($services as $service) {
										?>
                                        <li>
                                            <h6><?php echo esc_html(MPWPB_Function::get_service_text($post_id)); ?>&nbsp;:&nbsp;</h6>
                                            <span><?php echo esc_html($service['name']); ?></span>
                                        </li>
                                        <li>
                                            <h6><?php esc_html_e('Price', 'service-booking-manager'); ?>&nbsp;:&nbsp;</h6>
                                            <span><?php echo wp_kses_post(' ( ' . MP_Global_Function::wc_price($post_id, $service['price']) . ' x 1 ) = ' . MP_Global_Function::wc_price($post_id, ($service['price'] * 1))); ?></span>
                                        </li>
										<?php
									}
								}
							?>
                            <li>
                                <span class="far fa-calendar-alt"></span>
                                <h6><?php esc_html_e('Date', 'service-booking-manager'); ?>&nbsp;:&nbsp;</h6>
                                <span><?php echo esc_html(MP_Global_Function::date_format($cart_item['mpwpb_date'])); ?></span>
                            </li>
                            <li>
                                <span class="far fa-clock"></span>
                                <h6><?php esc_html_e('Time', 'service-booking-manager'); ?>&nbsp;:&nbsp;</h6>
                                <span><?php echo esc_html(MP_Global_Function::date_format($cart_item['mpwpb_date'], 'time')); ?></span>
                            </li>
                        </ul>
                    </div>
					<?php if (sizeof($extra_service) > 0) { ?>
                        <div class="dLayout_xs">
                            <h5 class="mB_xs"><?php esc_html_e('Extra Services', 'service-booking-manager'); ?></h5>
							<?php foreach ($extra_service as $service) { ?>
                                <div class="divider"></div>
                                <div class="dFlex">
                                    <h6><?php esc_html_e('Services Name', 'service-booking-manager'); ?>&nbsp;:&nbsp;</h6>
                                    <span><?php echo esc_html($service['ex_name']); ?>
									</span>
                                </div>
                                <div class="dFlex">
                                    <h6><?php esc_html_e('Price', 'service-booking-manager'); ?>&nbsp;:&nbsp;</h6>
                                    <span><?php echo wp_kses_post(' ( ' . MP_Global_Function::wc_price($post_id, $service['ex_price']) . ' x ' . $service['ex_qty'] . ' ) = ' . MP_Global_Function::wc_price($post_id, ($service['ex_price'] * $service['ex_qty']))); ?></span>
                                </div>
							<?php } ?>
                        </div>
					<?php } ?>
					<?php do_action('mpwpb_after_cart_item_display', $cart_item, $post_id); ?>
                </div>
				<?php
			}
			public function wc_order_status_change($order_status, $post_id, $order_id) {
				$args = array(
					'post_type' => 'mpwpb_booking',
					'posts_per_page' => -1,
					'meta_query' => array(
						'relation' => 'AND',
						array(
							array(
								'key' => 'mpwpb_id',
								'value' => $post_id,
								'compare' => '='
							),
							array(
								'key' => 'mpwpb_order_id',
								'value' => $order_id,
								'compare' => '='
							)
						)
					)
				);
				$loop = new WP_Query($args);
				foreach ($loop->posts as $user) {
					$user_id = $user->ID;
					//echo '<pre>';print_r($user_id);echo '</pre>';
					update_post_meta($user_id, 'mpwpb_order_status', $order_status);
				}
				$args = array(
					'post_type' => 'mpwpb_extra_service_booking',
					'posts_per_page' => -1,
					'meta_query' => array(
						'relation' => 'AND',
						array(
							array(
								'key' => 'mpwpb_id',
								'value' => $post_id,
								'compare' => '='
							),
							array(
								'key' => 'mpwpb_order_id',
								'value' => $order_id,
								'compare' => '='
							)
						)
					)
				);
				$loop = new WP_Query($args);
				foreach ($loop->posts as $user) {
					$user_id = $user->ID;
					update_post_meta($user_id, 'mpwpb_order_status', $order_status);
				}
			}
			//**********************//
			public static function cart_extra_service_info($post_id, $date, $ex_service_types, $ex_service_qty): array {
				$extra_service = array();
				$service_count = sizeof($ex_service_types);
				if ($service_count > 0) {
					$count = 0;
					for ($i = 0; $i < $service_count; $i++) {
						if ($ex_service_types[$i]) {
							$ex_price = MPWPB_Function::get_extra_price($post_id, $ex_service_types[$i]);
							$extra_service[$count]['ex_name'] = $ex_service_types[$i];
							$extra_service[$count]['ex_price'] = $ex_price;
							$extra_service[$count]['ex_qty'] = $ex_service_qty[$i];
							$extra_service[$count]['mpwpb_date'] = $date ?? '';
							$count++;
						}
					}
				}
				return $extra_service;
			}
			public static function get_cart_total_price($post_id, $all_service, $ex_service_types, $ex_service_qty, $ex_service_group) {
				$price = 0;
				if (is_array($all_service) && sizeof($all_service)) {
					foreach ($all_service as $service) {
						$price = $price + $service['price'];
					}
				}
				$ex_price = 0;
				$service_count = sizeof($ex_service_types);
				if ($service_count > 0) {
					for ($i = 0; $i < $service_count; $i++) {
						if ($ex_service_types[$i]) {
							$group_name = array_key_exists($i, $ex_service_group) ? $ex_service_group[$i] : '';
							$ex_price = $ex_price + MPWPB_Function::get_extra_price($post_id, $ex_service_types[$i], $group_name) * $ex_service_qty[$i];
						}
					}
				}
				$total_price = $price + $ex_price;
				return max(0, $total_price);
			}
			public static function add_cpt_data($cpt_name, $title, $meta_data = array(), $status = 'publish', $cat = array()) {
				$new_post = array(
					'post_title' => $title,
					'post_content' => '',
					'post_category' => $cat,
					'tags_input' => array(),
					'post_status' => $status,
					'post_type' => $cpt_name
				);
				wp_reset_postdata();
				$post_id = wp_insert_post($new_post);
				if (sizeof($meta_data) > 0) {
					foreach ($meta_data as $key => $value) {
						update_post_meta($post_id, $key, $value);
					}
				}
				if ($cpt_name == 'mpwpb_booking') {
					$pin = $meta_data['mpwpb_user_id'] . $meta_data['mpwpb_order_id'] . $meta_data['mpwpb_id'] . $post_id;
					update_post_meta($post_id, 'mpwpb_pin', $pin);
				}
				wp_reset_postdata();
			}
			/****************************/
			public function mpwpb_add_to_cart() {
				if (isset($_POST['nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'mpwpb_nonce')) {
					$link_id = isset($_POST['link_id']) ? sanitize_text_field(wp_unslash($_POST['link_id'])) : '';
					$product_id = apply_filters('woocommerce_add_to_cart_product_id', $link_id);
					$quantity = 1;
					$passed_validation = apply_filters('woocommerce_add_to_cart_validation', true, $product_id, $quantity);
					$product_status = get_post_status($product_id);
					WC()->cart->empty_cart();
					if ($passed_validation && WC()->cart->add_to_cart($product_id, 1) && 'publish' === $product_status) {
						echo esc_url(wc_get_checkout_url());
					}
				}
				die();
			}
		}
		new MPWPB_Woocommerce();
	}