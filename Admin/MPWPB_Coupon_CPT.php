<?php
	/*
   * @Author 		engr.sumonazma@gmail.com
   * Copyright: 	mage-people.com
   */
	if (!defined('ABSPATH')) {
		die;
	} // Cannot access pages directly.
	if (!class_exists('MPWPB_Coupon_CPT')) {
		class MPWPB_Coupon_CPT {
			public function __construct() {
				add_action('init', [$this, 'add_cpt']);
			}
			public function add_cpt(): void {
				$labels = [
					'name' => esc_html__('Coupons', 'service-booking-manager'),
					'singular_name' => esc_html__('Coupon', 'service-booking-manager'),
					'menu_name' => esc_html__('Coupons', 'service-booking-manager'),
					'name_admin_bar' => esc_html__('Coupon', 'service-booking-manager'),
					'all_items' => esc_html__('All Coupons', 'service-booking-manager'),
					'add_new_item' => esc_html__('Add New Coupon', 'service-booking-manager'),
					'add_new' => esc_html__('Add New Coupon', 'service-booking-manager'),
					'new_item' => esc_html__('New Coupon', 'service-booking-manager'),
					'edit_item' => esc_html__('Edit Coupon', 'service-booking-manager'),
					'update_item' => esc_html__('Update Coupon', 'service-booking-manager'),
					'view_item' => esc_html__('View Coupon', 'service-booking-manager'),
					'search_items' => esc_html__('Search Coupons', 'service-booking-manager'),
					'not_found' => esc_html__('No coupons found', 'service-booking-manager'),
					'not_found_in_trash' => esc_html__('No coupons found in Trash', 'service-booking-manager'),
				];
				$args = [
					'public' => false,
					'labels' => $labels,
					'supports' => ['title'],
					'show_in_rest' => false,
					'capability_type' => 'post',
					'publicly_queryable' => false,
					'show_ui' => true,
					'show_in_menu' => 'edit.php?post_type=' . MPWPB_Function::get_cpt(),
					'exclude_from_search' => true,
					'show_in_nav_menus' => false,
					'has_archive' => false,
				];
				register_post_type('mpwpb_coupon', $args);
			}
		}
		new MPWPB_Coupon_CPT();
	}

	function mpwpb_hide_coupon_cpt_all_posts_submenu() {
		remove_submenu_page('edit.php?post_type=' . MPWPB_Function::get_cpt(), 'edit.php?post_type=mpwpb_coupon');
		remove_submenu_page('edit.php?post_type=' . MPWPB_Function::get_cpt(), 'post-new.php?post_type=mpwpb_coupon');
	}
	add_action('admin_menu', 'mpwpb_hide_coupon_cpt_all_posts_submenu', 999);
