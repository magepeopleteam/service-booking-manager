<?php
	/*
   * @Author 		engr.sumonazma@gmail.com
   * Copyright: 	mage-people.com
   */
	if (!defined('ABSPATH')) {
		die;
	} // Cannot access pages directly.
	if (!class_exists('MPWPB_Dependencies')) {
		class MPWPB_Dependencies {
			public function __construct() {
				add_action('init', [$this, 'language_load']);
				$this->load_file();
				add_action('wp_enqueue_scripts', [$this, 'frontend_script'], 90);
				add_action('admin_enqueue_scripts', [$this, 'admin_scripts'], 90);
			}
			public function language_load(): void {
				$plugin_dir = basename(dirname(__DIR__)) . "/languages/";
				load_plugin_textdomain('service-booking-manager', false, $plugin_dir);
			}
			private function load_file(): void {
				require_once MPWPB_PLUGIN_DIR . '/inc/MPWPB_Function.php';
				require_once MPWPB_PLUGIN_DIR . '/inc/MPWPB_Query.php';
				require_once MPWPB_PLUGIN_DIR . '/inc/MPWPB_Layout.php';
				require_once MPWPB_PLUGIN_DIR . '/Admin/MPWPB_Admin.php';
				require_once MPWPB_PLUGIN_DIR . '/Frontend/MPWPB_Frontend.php';
				
			}
			public function global_enqueue() {
				do_action('add_mpwpb_common_script');
			}
			public function admin_scripts() {
				$this->global_enqueue();
				// ****custom************//
				wp_enqueue_style('mpwpb_admin', MPWPB_PLUGIN_URL . '/assets/admin/mpwpb_admin.css', [], time());
				wp_enqueue_style('admin_style', MPWPB_PLUGIN_URL . '/assets/admin/admin_style.css', [], time());
				wp_enqueue_script('mpwpb_admin', MPWPB_PLUGIN_URL . '/assets/admin/mpwpb_admin.js', ['jquery'], time(), true);
				wp_localize_script('mpwpb_admin', 'mpwpb_admin_ajax', array(
					'ajax_url' => admin_url('admin-ajax.php'),
					'nonce'    => wp_create_nonce('mpwpb_admin_nonce'),
					'loadingTxt'    => __('Loading...','service-booking-manager'),
				));
				do_action('add_mpwpb_admin_script');
			}
			public function frontend_script() {
				$this->global_enqueue();
				wp_enqueue_script('wc-checkout');
				// custom
				wp_enqueue_style('mpwpb', MPWPB_PLUGIN_URL . '/assets/frontend/mpwpb.css', [], time());
				wp_enqueue_script('mpwpb', MPWPB_PLUGIN_URL . '/assets/frontend/mpwpb.js', ['jquery'], time(), true);
				wp_enqueue_style('mpwpb_registration', MPWPB_PLUGIN_URL . '/assets/frontend/mpwpb_registration.css', [], time());
				wp_enqueue_script('mpwpb_registration', MPWPB_PLUGIN_URL . '/assets/frontend/mpwpb_registration.js', ['jquery'], time());
				wp_localize_script('mpwpb_registration', 'mpwpb_ajax', array(
					'ajax_url' => admin_url('admin-ajax.php'),
					'nonce'    => wp_create_nonce('mpwpb_nonce')
				));
				do_action('add_mpwpb_frontend_script');
			}
		}
		new MPWPB_Dependencies();
	}