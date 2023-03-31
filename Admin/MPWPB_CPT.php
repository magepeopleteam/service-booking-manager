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
					'archives'              => $label.' '.esc_html__( ' List', 'bookingplus' ),
					'attributes'            =>  $label.' '.esc_html__( ' List', 'bookingplus' ),
					'parent_item_colon'     =>$label.' '. esc_html__( ' Item:', 'bookingplus' ),
					'all_items'             => esc_html__( 'All ' , 'bookingplus' ).' '.$label,
					'add_new_item'          => esc_html__( 'Add New ' , 'bookingplus' ).' '.$label,
					'add_new'               => esc_html__( 'Add New ' , 'bookingplus' ).' '.$label,
					'new_item'              => esc_html__( 'New ' , 'bookingplus' ).' '.$label,
					'edit_item'             => esc_html__( 'Edit ' , 'bookingplus' ).' '.$label,
					'update_item'           => esc_html__( 'Update ', 'bookingplus' ).' '.$label,
					'view_item'             => esc_html__( 'View ', 'bookingplus' ).' '.$label,
					'view_items'            => esc_html__( 'View ' , 'bookingplus' ).' '.$label,
					'search_items'          => esc_html__( 'Search ', 'bookingplus' ).' '.$label,
					'not_found'             => $label.' '. esc_html__( ' Not found', 'bookingplus' ),
					'not_found_in_trash'    =>$label.' '.  esc_html__( ' Not found in Trash', 'bookingplus' ),
					'featured_image'        => $label.' '. esc_html__( ' Feature Image', 'bookingplus' ),
					'set_featured_image'    => esc_html__( 'Set ' , 'bookingplus' ).' '.$label.' '.esc_html__( ' featured image', 'bookingplus' ),
					'remove_featured_image' => esc_html__( 'Remove ' , 'bookingplus' ).' '.$label.' '.esc_html__( ' featured image', 'bookingplus' ),
					'use_featured_image'    => esc_html__( 'Use as ' . $label . ' featured image', 'bookingplus' ).' '.$label.' '.esc_html__( ' featured image', 'bookingplus' ),
					'insert_into_item'      => esc_html__( 'Insert into ' , 'bookingplus' ).' '.$label,
					'uploaded_to_this_item' => esc_html__( 'Uploaded to this ', 'bookingplus' ).' '.$label,
					'items_list'            => $label.' '. esc_html__( ' list', 'bookingplus' ),
					'items_list_navigation' => $label.' '. esc_html__( ' list navigation', 'bookingplus' ),
					'filter_items_list'     => esc_html__( 'Filter ' , 'bookingplus' ).' '.$label.' '.esc_html__( ' list', 'bookingplus' )
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