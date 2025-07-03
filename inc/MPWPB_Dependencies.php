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
				add_action( 'admin_init', array( $this, 'mpwpb_upgrade' ) );
				add_action('wp_enqueue_scripts', [$this, 'frontend_script'], 90);
				add_action('admin_enqueue_scripts', [$this, 'admin_scripts'], 90);
			}
			public function language_load(): void {
				$plugin_dir = basename(dirname(__DIR__)) . "/languages/";
				load_plugin_textdomain('service-booking-manager', false, $plugin_dir);
			}
			private function load_file(): void {
                require_once MPWPB_PLUGIN_DIR . '/Admin/MPWPB_Service_List.php';
				require_once MPWPB_PLUGIN_DIR . '/inc/MPWPB_Function.php';
				require_once MPWPB_PLUGIN_DIR . '/inc/MPWPB_Query.php';
				require_once MPWPB_PLUGIN_DIR . '/inc/MPWPB_Layout.php';
				require_once MPWPB_PLUGIN_DIR . '/Admin/MPWPB_Admin.php';
				require_once MPWPB_PLUGIN_DIR . '/Frontend/MPWPB_Frontend.php';

				require_once MPWPB_PLUGIN_DIR . '/Frontend/MPWPB_Form_Hook.php';
				require_once MPWPB_PLUGIN_DIR . '/Frontend/MPWPB_Checkout_Form_Modifier.php';
				require_once MPWPB_PLUGIN_DIR . '/Frontend/MPWPB_Direct_Form_Modifier.php';
				require_once MPWPB_PLUGIN_DIR . '/Frontend/MPWPB_Ajax_File_Upload.php';
				require_once MPWPB_PLUGIN_DIR . '/Frontend/MPWPB_File_Display_Helper.php';
				require_once MPWPB_PLUGIN_DIR . '/Frontend/MPWPB_Display_Fixer.php';
			}
			public function mpwpb_upgrade() {
				if ( get_option( 'mpwpb_conflict_update' ) != 'completed' ) {
					$global_settings = get_option( 'mp_global_settings' );
					update_option( 'mpwpb_global_settings', $global_settings );
					$style_settings = get_option( 'mp_style_settings' );
					update_option( 'mpwpb_style_settings', $style_settings );
					$slider_settings = get_option( 'mp_slider_settings' );
					update_option( 'mpwpb_slider_settings', $slider_settings );
					$custom_css = get_option( 'mp_add_custom_css' );
					update_option( 'mpwpb_custom_css', $custom_css );
					$license_settings = get_option( 'mp_basic_license_settings' );
					update_option( 'mpwpb_license_settings', $license_settings );
					update_option( 'mpwpb_conflict_update', 'completed' );
				}

			}
			public function global_enqueue() {
				do_action('add_mpwpb_common_script');

                self::staff_dashboard_enqueue_scripts();
			}

            public static function staff_dashboard_enqueue_scripts() {
                $current_user = wp_get_current_user();
                if (in_array('mpwpb_staff', $current_user->roles)) {

                    wp_enqueue_script('jquery-ui-sortable');
                    // your script depends on jQuery UI
                    wp_enqueue_style('mpwpb-user-dashboard', MPWPB_PLUGIN_URL . '/assets/frontend/mpwpb_user_dashboard.css', array(), time());
                    wp_enqueue_script('mpwpb-user-dashboard', MPWPB_PLUGIN_URL . '/assets/frontend/mpwpb_user_dashboard.js', array('jquery'), time(), true);
                    wp_localize_script('mpwpb-user-dashboard', 'mpwpb_dashboard', array(
                        'ajaxurl' => admin_url('admin-ajax.php'),
                        'nonce' => wp_create_nonce('mpwpb_dashboard_nonce'),
                        'cancel_confirm' => esc_html__('Are you sure you want to cancel this booking?', 'service-booking-manager'),
                        'reschedule_confirm' => esc_html__('Are you sure you want to reschedule this booking?', 'service-booking-manager')
                    ));
                }
            }
			public function admin_scripts() {
				$this->global_enqueue();
				// ****custom************//
				wp_enqueue_style('mpwpb_admin', MPWPB_PLUGIN_URL . '/assets/admin/mpwpb_admin.css', [], time());
				wp_enqueue_style('admin_style', MPWPB_PLUGIN_URL . '/assets/admin/admin_style.css', [], time());
                wp_enqueue_style('mpwpb_service_list', MPWPB_PLUGIN_URL . '/assets/admin/mpwpb_service_list.css', [], time());
                wp_enqueue_style('mpwpb_staff_member', MPWPB_PLUGIN_URL . '/assets/admin/mpwpb_staff_member.css', [], time());
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

                wp_enqueue_script('mpwpb-recurring-booking', MPWPB_PLUGIN_URL . '/assets/frontend/mpwpb_recurring_booking.js', array('jquery'),  true);
                wp_enqueue_style('mpwpb-recurring-booking', MPWPB_PLUGIN_URL . '/assets/frontend/mpwpb_recurring_booking.css', array(), true);

                // Pass post ID to JavaScript
                wp_localize_script('mpwpb-recurring-booking', 'mpwpb_recurring_data', array(
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('mpwpb_nonce'),
                    'plugin_url' => MPWPB_PLUGIN_URL
                ));

				do_action('add_mpwpb_frontend_script');
			}
		}
		new MPWPB_Dependencies();
	}