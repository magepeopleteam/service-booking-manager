<?php
	if ( ! defined( 'ABSPATH' ) ) {
		die;
	} // Cannot access pages directly.
	if ( ! class_exists( 'MPWPB_Taxonomy' ) ) {
		class MPWPB_Taxonomy {
			public function __construct() {
				//add_action( 'init', [ $this, 'taxonomy' ] );
			}
			public function taxonomy(){
				$label     = MPWPB_Function::get_name();
				$cat_label = MPWPB_Function::get_category_label();
				$cat_slug  = MPWPB_Function::get_category_slug();
				$labels         = [
					'name'                       => $label . ' ' . $cat_label,
					'singular_name'              => $label . ' ' . $cat_label,
					'menu_name'                  => $cat_label,
					'all_items'                  => esc_html__( 'All ' , 'servicebookingmanager' ).' '.$label . ' ' . $cat_label,
					'parent_item'                => esc_html__( 'Parent ' , 'servicebookingmanager' ). ' ' . $cat_label,
					'parent_item_colon'          =>esc_html__( 'Parent ' , 'servicebookingmanager' ). ' ' . $cat_label,
					'new_item_name'              => esc_html__( 'New ' . $cat_label . ' Name', 'servicebookingmanager' ),
					'add_new_item'               => esc_html__( 'Add New ' . $cat_label, 'servicebookingmanager' ),
					'edit_item'                  => esc_html__( 'Edit ' . $cat_label, 'servicebookingmanager' ),
					'update_item'                => esc_html__( 'Update ' . $cat_label, 'servicebookingmanager' ),
					'view_item'                  => esc_html__( 'View ' . $cat_label, 'servicebookingmanager' ),
					'separate_items_with_commas' => esc_html__( 'Separate ' . $cat_label . ' with commas', 'servicebookingmanager' ),
					'add_or_remove_items'        => esc_html__( 'Add or remove ' . $cat_label, 'servicebookingmanager' ),
					'choose_from_most_used'      => esc_html__( 'Choose from the most used', 'servicebookingmanager' ),
					'popular_items'              => esc_html__( 'Popular ' . $cat_label, 'servicebookingmanager' ),
					'search_items'               => esc_html__( 'Search ' . $cat_label, 'servicebookingmanager' ),
					'not_found'                  => esc_html__( 'Not Found', 'servicebookingmanager' ),
					'no_terms'                   => esc_html__( 'No ' . $cat_label, 'servicebookingmanager' ),
					'items_list'                 => esc_html__( $cat_label . ' list', 'servicebookingmanager' ),
					'items_list_navigation'      => esc_html__( $cat_label . ' list navigation', 'servicebookingmanager' ),
				];
				$args           = [
					'hierarchical'          => true,
					"public"                => true,
					'labels'                => $labels,
					'show_ui'               => true,
					'show_admin_column'     => true,
					'update_count_callback' => '_update_post_term_count',
					'query_var'             => true,
					'rewrite'               => [ 'slug' => $cat_slug ],
					'show_in_rest'          => true,
					'rest_base'             => 'mpwpb_category'
				];
				register_taxonomy( 'mpwpb_category', 'mpwpb_item', $args );
			}
		}
		new MPWPB_Taxonomy();
	}