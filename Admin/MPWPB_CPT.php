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
					'archives'              => $label.' '.esc_html__( ' List', 'servicebookingmanager' ),
					'attributes'            =>  $label.' '.esc_html__( ' List', 'servicebookingmanager' ),
					'parent_item_colon'     =>$label.' '. esc_html__( ' Item:', 'servicebookingmanager' ),
					'all_items'             => esc_html__( 'All ' , 'servicebookingmanager' ).' '.$label,
					'add_new_item'          => esc_html__( 'Add New ' , 'servicebookingmanager' ).' '.$label,
					'add_new'               => esc_html__( 'Add New ' , 'servicebookingmanager' ).' '.$label,
					'new_item'              => esc_html__( 'New ' , 'servicebookingmanager' ).' '.$label,
					'edit_item'             => esc_html__( 'Edit ' , 'servicebookingmanager' ).' '.$label,
					'update_item'           => esc_html__( 'Update ', 'servicebookingmanager' ).' '.$label,
					'view_item'             => esc_html__( 'View ', 'servicebookingmanager' ).' '.$label,
					'view_items'            => esc_html__( 'View ' , 'servicebookingmanager' ).' '.$label,
					'search_items'          => esc_html__( 'Search ', 'servicebookingmanager' ).' '.$label,
					'not_found'             => $label.' '. esc_html__( ' Not found', 'servicebookingmanager' ),
					'not_found_in_trash'    =>$label.' '.  esc_html__( ' Not found in Trash', 'servicebookingmanager' ),
					'featured_image'        => $label.' '. esc_html__( ' Feature Image', 'servicebookingmanager' ),
					'set_featured_image'    => esc_html__( 'Set ' , 'servicebookingmanager' ).' '.$label.' '.esc_html__( ' featured image', 'servicebookingmanager' ),
					'remove_featured_image' => esc_html__( 'Remove ' , 'servicebookingmanager' ).' '.$label.' '.esc_html__( ' featured image', 'servicebookingmanager' ),
					'use_featured_image'    => esc_html__( 'Use as ' . $label . ' featured image', 'servicebookingmanager' ).' '.$label.' '.esc_html__( ' featured image', 'servicebookingmanager' ),
					'insert_into_item'      => esc_html__( 'Insert into ' , 'servicebookingmanager' ).' '.$label,
					'uploaded_to_this_item' => esc_html__( 'Uploaded to this ', 'servicebookingmanager' ).' '.$label,
					'items_list'            => $label.' '. esc_html__( ' list', 'servicebookingmanager' ),
					'items_list_navigation' => $label.' '. esc_html__( ' list navigation', 'servicebookingmanager' ),
					'filter_items_list'     => esc_html__( 'Filter ' , 'servicebookingmanager' ).' '.$label.' '.esc_html__( ' list', 'servicebookingmanager' )
				];
				$args       = [
					'public'       => true,
					'labels'       => $labels,
					'menu_icon'    => $icon,
					'supports'     => [ 'title', 'thumbnail' ],
					'rewrite'      => [ 'slug' => $slug ],
					'show_in_rest' => true
				];
				register_post_type( MPWPB_Function::mp_cpt(), $args );
			}
		}
		new MPWPB_CPT();
	}