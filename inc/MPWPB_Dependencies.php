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
				add_action('wp_footer', [$this, 'ensure_service_import_map'], 1);
				add_action('admin_enqueue_scripts', [$this, 'admin_scripts'], 90);
			}
			public function language_load(): void {
				$plugin_dir = basename(dirname(__DIR__)) . "/languages/";
				load_plugin_textdomain('service-booking-manager', false, $plugin_dir);
			}
			private function load_file(): void {
                require_once MPWPB_PLUGIN_DIR . '/Admin/MPWPB_Service_List.php';
				require_once MPWPB_PLUGIN_DIR . '/Admin/data/MPWPB_Business_Templates_Data.php';
				require_once MPWPB_PLUGIN_DIR . '/Admin/MPWPB_Business_Templates_Import.php';
				require_once MPWPB_PLUGIN_DIR . '/inc/MPWPB_Function.php';
				require_once MPWPB_PLUGIN_DIR . '/inc/MPWPB_Query.php';
				require_once MPWPB_PLUGIN_DIR . '/inc/MPWPB_Layout.php';
				//*************Coupon Engine*****************//
				require_once MPWPB_PLUGIN_DIR . '/inc/MPWPB_Coupon_Function.php';
				require_once MPWPB_PLUGIN_DIR . '/inc/MPWPB_Coupon_Validator.php';
				require_once MPWPB_PLUGIN_DIR . '/inc/MPWPB_Coupon_Usage.php';
				require_once MPWPB_PLUGIN_DIR . '/Admin/MPWPB_Coupon_List.php';
				require_once MPWPB_PLUGIN_DIR . '/Frontend/MPWPB_Coupon_Frontend.php';
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
			/**
			 * The plugin's classic single-service template discovers the block
			 * theme/WooCommerce modules after WordPress's head import-map pass.
			 * Print the now-complete map before footer modules execute.
			 */
			public function ensure_service_import_map(): void {
				if (is_singular(MPWPB_Function::get_cpt()) && function_exists('wp_script_modules')) {
					wp_script_modules()->print_import_map();
				}
			}
			public function global_enqueue() {
				do_action('add_mpwpb_common_script');
				wp_enqueue_style('mage-icon', MPWPB_PLUGIN_URL . '/assets/mage-icon/css/mage-icon.css', array(), MPWPB_VERSION);
				
                self::staff_dashboard_enqueue_scripts();
			}

            public static function staff_dashboard_enqueue_scripts() {
                $current_user = wp_get_current_user();
                if (in_array('mpwpb_staff', $current_user->roles)) {

                    wp_enqueue_script('jquery-ui-sortable');
                    // your script depends on jQuery UI
                    wp_enqueue_style('mpwpb-user-dashboard', MPWPB_PLUGIN_URL . '/assets/frontend/mpwpb_user_dashboard.css', array(), MPWPB_VERSION);
                    wp_enqueue_script('mpwpb-user-dashboard', MPWPB_PLUGIN_URL . '/assets/frontend/mpwpb_user_dashboard.js', array('jquery'), MPWPB_VERSION, true);
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
				wp_enqueue_style('mpwpb_admin', MPWPB_PLUGIN_URL . '/assets/admin/mpwpb_admin.css', [], MPWPB_VERSION);
				wp_enqueue_style('admin_style', MPWPB_PLUGIN_URL . '/assets/admin/admin_style.css', [], MPWPB_VERSION);
                wp_enqueue_style('mpwpb_service_list', MPWPB_PLUGIN_URL . '/assets/admin/mpwpb_service_list.css', [], MPWPB_VERSION);
                wp_enqueue_style('mpwpb_staff_member', MPWPB_PLUGIN_URL . '/assets/admin/mpwpb_staff_member.css', [], MPWPB_VERSION);
                wp_enqueue_style('mpwpb_analytics_dashboard', MPWPB_PLUGIN_URL . '/assets/admin/mpwpb_analytics_dashboard.css', [], MPWPB_VERSION);
				wp_enqueue_script('mpwpb_admin', MPWPB_PLUGIN_URL . '/assets/admin/mpwpb_admin.js', ['jquery'], MPWPB_VERSION, true);
				// Staff Management page reskin — live off-day/schedule-row sync only;
				// no-ops (returns early) on any other admin screen.
				wp_enqueue_script('mpwpb_staff_management_modern', MPWPB_PLUGIN_URL . '/assets/admin/mpwpb-staff-management-modern.js', ['jquery'], MPWPB_VERSION, true);
				wp_localize_script('mpwpb_staff_management_modern', 'mpwpbStaffScheduleI18n', array(
					'active' => __('ACTIVE', 'service-booking-manager'),
					'off'    => __('OFF', 'service-booking-manager'),
				));
				wp_localize_script('mpwpb_admin', 'mpwpb_admin_ajax', array(
					'ajax_url' => admin_url('admin-ajax.php'),
					'nonce'    => wp_create_nonce('mpwpb_admin_nonce'),
					'loadingTxt'    => __('Loading...','service-booking-manager'),
				));
				do_action('add_mpwpb_admin_script');
			}
			public function frontend_script() {
				if (!MPWPB_Global_Function::is_booking_frontend_context()) {
					return;
				}
				$this->global_enqueue();
				wp_enqueue_script('wc-checkout');
				// custom
				wp_enqueue_style('mpwpb', MPWPB_PLUGIN_URL . '/assets/frontend/mpwpb.css', [], MPWPB_VERSION);
				wp_enqueue_script('mpwpb', MPWPB_PLUGIN_URL . '/assets/frontend/mpwpb.js', ['jquery'], MPWPB_VERSION, true);
				wp_enqueue_style('mpwpb_registration', MPWPB_PLUGIN_URL . '/assets/frontend/mpwpb_registration.css', [], MPWPB_VERSION);
				wp_enqueue_script('mpwpb_registration', MPWPB_PLUGIN_URL . '/assets/frontend/mpwpb_registration.js', ['jquery'], MPWPB_VERSION);
				wp_enqueue_style('mpwpb_coupon', MPWPB_PLUGIN_URL . '/assets/frontend/mpwpb-coupon.css', [], MPWPB_VERSION);
				// Depends on mpwpb_registration (not just jquery) so the
				// mpwpb_ajax object it localizes is guaranteed to already exist.
				wp_enqueue_script('mpwpb_coupon', MPWPB_PLUGIN_URL . '/assets/frontend/mpwpb-coupon.js', ['jquery', 'mpwpb_registration'], MPWPB_VERSION, true);
				// Pay in Full / Pay Deposit Now toggle on the checkout pages
				// (WC checkout + native checkout) -- same AJAX-then-refresh
				// pattern as mpwpb_coupon above.
				wp_enqueue_script('mpwpb_payment_choice', MPWPB_PLUGIN_URL . '/assets/frontend/mpwpb-payment-choice.js', ['jquery', 'mpwpb_registration'], MPWPB_VERSION, true);
				// Single service page redesign (hero/tabs/Overview/FAQ/Details) —
				// pure reskin, loaded after mpwpb_registration so its overrides win.
				wp_enqueue_style('mpwpb_service_page_modern', MPWPB_PLUGIN_URL . '/assets/frontend/mpwpb-service-page-modern.css', ['mpwpb_registration'], MPWPB_VERSION);
				wp_enqueue_script('mpwpb_service_page_modern', MPWPB_PLUGIN_URL . '/assets/frontend/mpwpb-service-page-modern.js', ['jquery', 'mpwpb_registration'], MPWPB_VERSION, true);
				// "Our services" sidebar tree expand/collapse only — selecting a
				// service still goes through mpwpb_registration.js unchanged.
				wp_enqueue_script('mpwpb_service_tree', MPWPB_PLUGIN_URL . '/assets/frontend/mpwpb-service-tree.js', ['jquery', 'mpwpb_registration'], MPWPB_VERSION, true);
				// Booking popup's inner picker: relocates the real category/
				// service elements (untouched click handlers/hidden inputs)
				// into a unified checkbox-tree — no new selection logic.
				wp_enqueue_script('mpwpb_booking_tree', MPWPB_PLUGIN_URL . '/assets/frontend/mpwpb-booking-tree.js', ['jquery', 'mpwpb_registration', 'mpwpb_service_tree'], MPWPB_VERSION, true);
				// WooCommerce My Account > Orders reskin — pure CSS, no
				// template override or new markup (see the file's own header
				// comment). Loaded after mpwpb_registration so its overrides win.
				wp_enqueue_style('mpwpb_account_orders_modern', MPWPB_PLUGIN_URL . '/assets/frontend/mpwpb-account-orders-modern.css', ['mpwpb_registration'], MPWPB_VERSION);
				wp_localize_script('mpwpb_registration', 'mpwpb_ajax', array(
					'ajax_url' => admin_url('admin-ajax.php'),
					'nonce'    => wp_create_nonce('mpwpb_nonce'),
					'use_24hour' => MPWPB_Global_Function::get_settings('mpwpb_global_settings', 'time_format_24hour', 'no'),
					// Lets the "Proceed to Checkout" handler decide whether the
					// mpwpb_add_to_cart response is a URL to navigate to (WooCommerce)
					// or a signal to load the native billing form inside the same
					// popup instead of leaving it (Custom Payment, WooCommerce off).
					'is_custom_payment_mode' => MPWPB_Global_Function::is_custom_payment_mode(),
					'booking_error' => esc_html__('The booking could not be completed. Please review your selection and try again.', 'service-booking-manager'),
				));
				do_action('add_mpwpb_frontend_script');
			}
		}
		new MPWPB_Dependencies();
	}
	
