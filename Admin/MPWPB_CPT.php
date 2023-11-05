<?php
	/*
   * @Author 		engr.sumonazma@gmail.com
   * Copyright: 	mage-people.com
   */
	if (!defined('ABSPATH')) {
		die;
	} // Cannot access pages directly.
	if (!class_exists('MPWPB_CPT')) {
		class MPWPB_CPT {
			public function __construct() {
				add_action('init', [$this, 'add_cpt']);
			}
			public function add_cpt(): void {
				$cpt = MPWPB_Function::get_cpt();
				$label = MPWPB_Function::get_name();
				$slug = MPWPB_Function::get_slug();
				$icon = MPWPB_Function::get_icon();
				$labels = [
					'name' => $label,
					'singular_name' => $label,
					'menu_name' => $label,
					'name_admin_bar' => $label,
					'archives' => $label . ' ' . esc_html__(' List', 'service-booking-manager'),
					'attributes' => $label . ' ' . esc_html__(' List', 'service-booking-manager'),
					'parent_item_colon' => $label . ' ' . esc_html__(' Item:', 'service-booking-manager'),
					'all_items' => esc_html__('All ', 'service-booking-manager') . ' ' . $label,
					'add_new_item' => esc_html__('Add New ', 'service-booking-manager') . ' ' . $label,
					'add_new' => esc_html__('Add New ', 'service-booking-manager') . ' ' . $label,
					'new_item' => esc_html__('New ', 'service-booking-manager') . ' ' . $label,
					'edit_item' => esc_html__('Edit ', 'service-booking-manager') . ' ' . $label,
					'update_item' => esc_html__('Update ', 'service-booking-manager') . ' ' . $label,
					'view_item' => esc_html__('View ', 'service-booking-manager') . ' ' . $label,
					'view_items' => esc_html__('View ', 'service-booking-manager') . ' ' . $label,
					'search_items' => esc_html__('Search ', 'service-booking-manager') . ' ' . $label,
					'not_found' => $label . ' ' . esc_html__(' Not found', 'service-booking-manager'),
					'not_found_in_trash' => $label . ' ' . esc_html__(' Not found in Trash', 'service-booking-manager'),
					'featured_image' => $label . ' ' . esc_html__(' Feature Image', 'service-booking-manager'),
					'set_featured_image' => esc_html__('Set ', 'service-booking-manager') . ' ' . $label . ' ' . esc_html__(' featured image', 'service-booking-manager'),
					'remove_featured_image' => esc_html__('Remove ', 'service-booking-manager') . ' ' . $label . ' ' . esc_html__(' featured image', 'service-booking-manager'),
					'use_featured_image' => esc_html__('Use as ' . $label . ' featured image', 'service-booking-manager') . ' ' . $label . ' ' . esc_html__(' featured image', 'service-booking-manager'),
					'insert_into_item' => esc_html__('Insert into ', 'service-booking-manager') . ' ' . $label,
					'uploaded_to_this_item' => esc_html__('Uploaded to this ', 'service-booking-manager') . ' ' . $label,
					'items_list' => $label . ' ' . esc_html__(' list', 'service-booking-manager'),
					'items_list_navigation' => $label . ' ' . esc_html__(' list navigation', 'service-booking-manager'),
					'filter_items_list' => esc_html__('Filter ', 'service-booking-manager') . ' ' . $label . ' ' . esc_html__(' list', 'service-booking-manager')
				];
				$args = [
					'public' => false,
					'labels' => $labels,
					'menu_icon' => $icon,
					'supports' => ['title', 'thumbnail'],
					'show_in_rest' => true,
					'capability_type' => 'post',
					'publicly_queryable' => true,  // you should be able to query it
					'show_ui' => true,  // you should be able to edit it in wp-admin
					'exclude_from_search' => true,  // you should exclude it from search results
					'show_in_nav_menus' => false,  // you shouldn't be able to add it to menus
					'has_archive' => false,  // it shouldn't have archive page
					'rewrite' => ['slug' => $slug],
				];
				register_post_type($cpt, $args);
			}
		}
		new MPWPB_CPT();
	}