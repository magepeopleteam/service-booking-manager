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
					'archives'              => $label.' '.esc_html__( ' List', 'mpwpb_plugin' ),
					'attributes'            =>  $label.' '.esc_html__( ' List', 'mpwpb_plugin' ),
					'parent_item_colon'     =>$label.' '. esc_html__( ' Item:', 'mpwpb_plugin' ),
					'all_items'             => esc_html__( 'All ' , 'mpwpb_plugin' ).' '.$label,
					'add_new_item'          => esc_html__( 'Add New ' , 'mpwpb_plugin' ).' '.$label,
					'add_new'               => esc_html__( 'Add New ' , 'mpwpb_plugin' ).' '.$label,
					'new_item'              => esc_html__( 'New ' , 'mpwpb_plugin' ).' '.$label,
					'edit_item'             => esc_html__( 'Edit ' , 'mpwpb_plugin' ).' '.$label,
					'update_item'           => esc_html__( 'Update ', 'mpwpb_plugin' ).' '.$label,
					'view_item'             => esc_html__( 'View ', 'mpwpb_plugin' ).' '.$label,
					'view_items'            => esc_html__( 'View ' , 'mpwpb_plugin' ).' '.$label,
					'search_items'          => esc_html__( 'Search ', 'mpwpb_plugin' ).' '.$label,
					'not_found'             => $label.' '. esc_html__( ' Not found', 'mpwpb_plugin' ),
					'not_found_in_trash'    =>$label.' '.  esc_html__( ' Not found in Trash', 'mpwpb_plugin' ),
					'featured_image'        => $label.' '. esc_html__( ' Feature Image', 'mpwpb_plugin' ),
					'set_featured_image'    => esc_html__( 'Set ' , 'mpwpb_plugin' ).' '.$label.' '.esc_html__( ' featured image', 'mpwpb_plugin' ),
					'remove_featured_image' => esc_html__( 'Remove ' , 'mpwpb_plugin' ).' '.$label.' '.esc_html__( ' featured image', 'mpwpb_plugin' ),
					'use_featured_image'    => esc_html__( 'Use as ' . $label . ' featured image', 'mpwpb_plugin' ).' '.$label.' '.esc_html__( ' featured image', 'mpwpb_plugin' ),
					'insert_into_item'      => esc_html__( 'Insert into ' , 'mpwpb_plugin' ).' '.$label,
					'uploaded_to_this_item' => esc_html__( 'Uploaded to this ', 'mpwpb_plugin' ).' '.$label,
					'items_list'            => $label.' '. esc_html__( ' list', 'mpwpb_plugin' ),
					'items_list_navigation' => $label.' '. esc_html__( ' list navigation', 'mpwpb_plugin' ),
					'filter_items_list'     => esc_html__( 'Filter ' , 'mpwpb_plugin' ).' '.$label.' '.esc_html__( ' list', 'mpwpb_plugin' )
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