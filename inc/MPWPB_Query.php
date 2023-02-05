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
			public static function get_order_meta( $item_id, $key ): string {
				global $wpdb;
				$table_name = $wpdb->prefix . "woocommerce_order_itemmeta";
				$results    = $wpdb->get_results( $wpdb->prepare( "SELECT meta_value FROM $table_name WHERE order_item_id = %d AND meta_key = %s", $item_id, $key ) );
				foreach ( $results as $result ) {
					$value = $result->meta_value;
				}
				return $value ?? '';
			}
		}
		new MPWPB_Query();
	}