<?php
	if ( ! defined( 'ABSPATH' ) ) {
		die;
	} // Cannot access pages directly.
	if ( ! class_exists( 'MPWPB_Query' ) ) {
		class MPWPB_Query {
			public function __construct() {}
			public static function query_post_type( $post_type ): WP_Query {
				$args = array(
					'post_type'      => $post_type,
					'posts_per_page' => - 1,
				);
				return new WP_Query( $args );
			}
			public static function query_all_sold( $post_id, $date): WP_Query {
				$_seat_booked_status      = MPWPB_Function::get_general_settings( 'set_book_status', array( 'processing', 'completed' ) );
				$seat_booked_status       = ! empty( $_seat_booked_status ) ? $_seat_booked_status : [];

				$date_filter              = ! empty( $date ) ? array(
					'key'     => 'mpwpb_date',
					'value'   => $date,
					'compare' => 'LIKE'
				) : '';
				$pending_status_filter    = in_array( 'pending', $seat_booked_status ) ? array(
					'key'     => 'mpwpb_order_status',
					'value'   => 'pending',
					'compare' => '='
				) : '';
				$on_hold_status_filter    = in_array( 'on-hold', $seat_booked_status ) ? array(
					'key'     => 'mpwpb_order_status',
					'value'   => 'on-hold',
					'compare' => '='
				) : '';
				$processing_status_filter = in_array( 'processing', $seat_booked_status ) ? array(
					'key'     => 'mpwpb_order_status',
					'value'   => 'processing',
					'compare' => '='
				) : '';
				$completed_status_filter  = in_array( 'completed', $seat_booked_status ) ? array(
					'key'     => 'mpwpb_order_status',
					'value'   => 'completed',
					'compare' => '='
				) : '';
				$args                     = array(
					'post_type'      => 'mpwpb_booking',
					'posts_per_page' => - 1,
					'meta_query'     => array(
						'relation' => 'AND',
						array(
							'relation' => 'AND',
							array(
								'key'     => 'mpwpb_id',
								'value'   => $post_id,
								'compare' => '='
							),
							$date_filter
						),
						array(
							'relation' => 'OR',
							$pending_status_filter,
							$on_hold_status_filter,
							$processing_status_filter,
							$completed_status_filter
						)
					)
				);
				return new WP_Query( $args );
			}
			public static function get_order_meta( $item_id, $key ): string {
				global $wpdb;
				$table_name = $wpdb->prefix . "woocommerce_order_itemmeta";
				$results    = $wpdb->get_results( $wpdb->prepare( "SELECT meta_value FROM $table_name WHERE order_item_id = %d AND meta_key = %s", $item_id, $key ) );
				foreach ( $results as $result ) {
					$value = $result->meta_value;
				}
				return $value ?? '';
			}
			public static function get_order_info( $order_id ): WP_Query {
				$args = array(
					'posts_per_page' => - 1,
					'post_type'      => 'mpwpb_booking',
					'meta_query'     => array(
						array(
							'key'     => 'mpwpb_order_id',
							'value'   => $order_id,
							'compare' => '=',
						)
					)
				);
				return new WP_Query( $args );
			}
		}
		new MPWPB_Query();
	}