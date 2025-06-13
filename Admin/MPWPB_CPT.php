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
                add_action('init', [$this, 'mpwpb_register_staff_taxonomy']);
			}

            public function mpwpb_register_staff_taxonomy() {
                $slug = MPWPB_Function::get_slug();
                register_taxonomy(
                    'mpwpb_staff',
                    'mpwpb_item',
                    array(
                        'label' => 'Staff Members',
                        'hierarchical' => false,
                        'public' => true,
                        'show_admin_column' => true,
                        'show_ui' => true,
                        'show_in_menu' => $slug,
                    )
                );
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
					'use_featured_image' => esc_html__('Use as', 'service-booking-manager') . ' ' . $label . ' ' . esc_html__(' featured image', 'service-booking-manager') . ' ' . $label . ' ' . esc_html__(' featured image', 'service-booking-manager'),
					'insert_into_item' => esc_html__('Insert into', 'service-booking-manager') . ' ' . $label,
					'uploaded_to_this_item' => esc_html__('Uploaded to this ', 'service-booking-manager') . ' ' . $label,
					'items_list' => $label . ' ' . esc_html__(' list', 'service-booking-manager'),
					'items_list_navigation' => $label . ' ' . esc_html__(' list navigation', 'service-booking-manager'),
					'filter_items_list' => esc_html__('Filter ', 'service-booking-manager') . ' ' . $label . ' ' . esc_html__(' list', 'service-booking-manager')
				];
				$args = [
					'public' 		=> true,
					'labels' 		=> $labels,
					'menu_icon' 	=> $icon,
					'supports' 		=> ['title', 'editor', 'thumbnail'],
					'show_in_rest' 	=> true,
					'capability_type' => 'post',
					'publicly_queryable' => true,  // you should be able to query it
					'show_ui' 		=> true,  // you should be able to edit it in wp-admin
					'exclude_from_search' => true,  // you should exclude it from search results
					'show_in_nav_menus' => false,  // you shouldn't be able to add it to menus
					
					'has_archive' 	=> false,  // it shouldn't have archive page
					'rewrite' => ['slug' => $slug],
				];
				register_post_type($cpt, $args);
			}
		}
		new MPWPB_CPT();
	}

	function mpwpb_hide_cpt_all_posts_submenu() {
    	// Replace 'my_custom_post' with your actual CPT slug
   	 	remove_submenu_page('edit.php?post_type=mpwpb_item', 'edit.php?post_type=mpwpb_item');
	}
	add_action('admin_menu', 'mpwpb_hide_cpt_all_posts_submenu', 999);


	function mpwpb_reorder_service_list_submenu() {
    	global $submenu;
		$parent_slug = 'edit.php?post_type=mpwpb_item';
		if (!isset($submenu[$parent_slug])) {
			return;
		}
		$original = $submenu[$parent_slug];
		$reordered = [];
		$first_item = isset($original[0]) ? $original[0] : null;
		if ($first_item) {
			$reordered[] = $first_item;
		}
		foreach ($original as $item) {
			if ($item[2] === 'mpwpb_service_list') {
				$reordered[] = $item;
			}
		}
		foreach ($original as $item) {
			if (
				($first_item && $item === $first_item) ||
				$item[2] === 'mpwpb_service_list'
			) {
				continue;
			}
			$reordered[] = $item;
		}
		$submenu[$parent_slug] = $reordered;
	}
	add_action('admin_menu', 'mpwpb_reorder_service_list_submenu', 999);