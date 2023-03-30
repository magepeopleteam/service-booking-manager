<?php
	if ( ! defined( 'ABSPATH' ) ) {
		die;
	} // Cannot access pages directly.
	if ( ! class_exists( 'MPWPB_CPT' ) ) {
		class MPWPB_CPT {
			public function __construct() {
				add_action( 'init', [ $this, 'add_cpt' ] );
			}
			public function add_cpt(): void {
				$label = MPWPB_Function::get_name();
				$slug  = MPWPB_Function::get_slug();
				$icon  = MPWPB_Function::get_icon();
				$labels     = [
					'name'                  => $label,
					'singular_name'         => $label,
					'menu_name'             => $label,
					'name_admin_bar'        => $label,
					'archives'              => $label.' '.esc_html__( ' List', 'bookingmaster' ),
					'attributes'            =>  $label.' '.esc_html__( ' List', 'bookingmaster' ),
					'parent_item_colon'     =>$label.' '. esc_html__( ' Item:', 'bookingmaster' ),
					'all_items'             => esc_html__( 'All ' , 'bookingmaster' ).' '.$label,
					'add_new_item'          => esc_html__( 'Add New ' , 'bookingmaster' ).' '.$label,
					'add_new'               => esc_html__( 'Add New ' , 'bookingmaster' ).' '.$label,
					'new_item'              => esc_html__( 'New ' , 'bookingmaster' ).' '.$label,
					'edit_item'             => esc_html__( 'Edit ' , 'bookingmaster' ).' '.$label,
					'update_item'           => esc_html__( 'Update ', 'bookingmaster' ).' '.$label,
					'view_item'             => esc_html__( 'View ', 'bookingmaster' ).' '.$label,
					'view_items'            => esc_html__( 'View ' , 'bookingmaster' ).' '.$label,
					'search_items'          => esc_html__( 'Search ', 'bookingmaster' ).' '.$label,
					'not_found'             => $label.' '. esc_html__( ' Not found', 'bookingmaster' ),
					'not_found_in_trash'    =>$label.' '.  esc_html__( ' Not found in Trash', 'bookingmaster' ),
					'featured_image'        => $label.' '. esc_html__( ' Feature Image', 'bookingmaster' ),
					'set_featured_image'    => esc_html__( 'Set ' , 'bookingmaster' ).' '.$label.' '.esc_html__( ' featured image', 'bookingmaster' ),
					'remove_featured_image' => esc_html__( 'Remove ' , 'bookingmaster' ).' '.$label.' '.esc_html__( ' featured image', 'bookingmaster' ),
					'use_featured_image'    => esc_html__( 'Use as ' . $label . ' featured image', 'bookingmaster' ).' '.$label.' '.esc_html__( ' featured image', 'bookingmaster' ),
					'insert_into_item'      => esc_html__( 'Insert into ' , 'bookingmaster' ).' '.$label,
					'uploaded_to_this_item' => esc_html__( 'Uploaded to this ', 'bookingmaster' ).' '.$label,
					'items_list'            => $label.' '. esc_html__( ' list', 'bookingmaster' ),
					'items_list_navigation' => $label.' '. esc_html__( ' list navigation', 'bookingmaster' ),
					'filter_items_list'     => esc_html__( 'Filter ' , 'bookingmaster' ).' '.$label.' '.esc_html__( ' list', 'bookingmaster' )
				];
				$args       = [
					'public'       => true,
					'labels'       => $labels,
					'menu_icon'    => $icon,
					'supports'     => [ 'title', 'thumbnail' ],
					'rewrite'      => [ 'slug' => $slug ],
					'show_in_rest' => true
				];
				register_post_type( MPWPB_Function::get_cpt_name(), $args );
			}
		}
		new MPWPB_CPT();
	}