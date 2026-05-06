<?php
/**
 * MPWPB WooCommerce Installer
 * Handles WooCommerce dependency check, popup display,
 * and AJAX-based installation & activation.
 * The popup shows on every admin page when WooCommerce is not active.
 *
 * @package ServiceBookingManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

if ( ! class_exists( 'MPWPB_Woo_Installer' ) ) {

	class MPWPB_Woo_Installer {

		public function __construct() {
			add_action( 'admin_init', array( $this, 'handle_activation_redirect' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
			add_action( 'admin_footer', array( $this, 'render_popup' ) );
			add_action( 'wp_ajax_mpwpb_install_woocommerce', array( $this, 'ajax_install_woocommerce' ) );
			add_action( 'wp_ajax_mpwpb_activate_woocommerce', array( $this, 'ajax_activate_woocommerce' ) );
		}

		private function is_woo_installed() {
			return file_exists( WP_PLUGIN_DIR . '/woocommerce/woocommerce.php' );
		}

		private function is_woo_active() {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
			return is_plugin_active( 'woocommerce/woocommerce.php' );
		}

		public function handle_activation_redirect() {
			if ( ! get_transient( 'mpwpb_plugin_activated' ) ) {
				return;
			}

			if ( is_network_admin() || isset( $_GET['activate-multi'] ) ) {
				delete_transient( 'mpwpb_plugin_activated' );
				return;
			}

			if ( $this->is_woo_active() ) {
				delete_transient( 'mpwpb_plugin_activated' );
				wp_safe_redirect( admin_url( 'edit.php?post_type=mpwpb_item&page=mpwpb_service_list' ) );
				exit;
			}

			delete_transient( 'mpwpb_plugin_activated' );
		}

		private function should_show_popup() {
			return ! $this->is_woo_active();
		}

		public function enqueue_assets() {
			if ( ! $this->should_show_popup() ) {
				return;
			}

			wp_enqueue_style(
				'mpwpb-woo-installer',
				MPWPB_PLUGIN_URL . '/assets/admin/mpwpb_woo_installer.css',
				array(),
				filemtime( MPWPB_PLUGIN_DIR . '/assets/admin/mpwpb_woo_installer.css' )
			);

			wp_enqueue_script(
				'mpwpb-woo-installer',
				MPWPB_PLUGIN_URL . '/assets/admin/mpwpb_woo_installer.js',
				array( 'jquery' ),
				filemtime( MPWPB_PLUGIN_DIR . '/assets/admin/mpwpb_woo_installer.js' ),
				true
			);

			wp_localize_script( 'mpwpb-woo-installer', 'mpwpb_woo_installer', array(
				'ajax_url'      => admin_url( 'admin-ajax.php' ),
				'install_nonce'  => wp_create_nonce( 'mpwpb_install_woo' ),
				'activate_nonce' => wp_create_nonce( 'mpwpb_activate_woo' ),
				'redirect_url'   => admin_url( 'edit.php?post_type=mpwpb_item&page=mpwpb_service_list' ),
				'woo_installed'  => $this->is_woo_installed() ? 'yes' : 'no',
				'i18n'           => array(
					'installing'     => __( 'Installing WooCommerce...', 'service-booking-manager' ),
					'activating'     => __( 'Activating WooCommerce...', 'service-booking-manager' ),
					'success'        => __( 'WooCommerce activated successfully!', 'service-booking-manager' ),
					'redirecting'    => __( 'Redirecting...', 'service-booking-manager' ),
					'install_error'  => __( 'Installation failed. Please install WooCommerce manually.', 'service-booking-manager' ),
					'activate_error' => __( 'Activation failed. Please activate WooCommerce manually.', 'service-booking-manager' ),
				),
			) );
		}

		public function render_popup() {
			if ( ! $this->should_show_popup() ) {
				return;
			}

			$is_installed = $this->is_woo_installed();
			$btn_text     = $is_installed
				? __( 'Activate WooCommerce', 'service-booking-manager' )
				: __( 'Install & Activate WooCommerce', 'service-booking-manager' );
			?>
			<div id="mpwpb-woo-overlay" class="mpwpb-woo-overlay">
				<div class="mpwpb-woo-popup">
					<div class="mpwpb-woo-header">
						<div class="mpwpb-woo-header-icon">
							<svg width="24" height="24" viewBox="0 0 24 24" fill="none">
								<path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
							</svg>
						</div>
						<span class="mpwpb-woo-header-text"><?php esc_html_e( 'Service Booking Manager', 'service-booking-manager' ); ?></span>
					</div>

					<div class="mpwpb-woo-icon-wrapper">
						<div class="mpwpb-woo-icon">
							<svg width="40" height="40" viewBox="0 0 24 24" fill="none">
								<circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="1.5"/>
								<path d="M12 8v4M12 16h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
							</svg>
						</div>
					</div>

					<div class="mpwpb-woo-content">
						<h2 class="mpwpb-woo-title"><?php esc_html_e( 'WooCommerce Required', 'service-booking-manager' ); ?></h2>
						<p class="mpwpb-woo-desc">
							<?php esc_html_e( 'Service Booking Manager requires WooCommerce to manage service bookings, payments, and orders. Please install and activate WooCommerce to continue.', 'service-booking-manager' ); ?>
						</p>
					</div>

					<div class="mpwpb-woo-features">
						<div class="mpwpb-woo-feature">
							<span class="mpwpb-woo-feature-icon">
								<svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M13.3 4.3L6 11.6 2.7 8.3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
							</span>
							<span><?php esc_html_e( 'Service booking & payments', 'service-booking-manager' ); ?></span>
						</div>
						<div class="mpwpb-woo-feature">
							<span class="mpwpb-woo-feature-icon">
								<svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M13.3 4.3L6 11.6 2.7 8.3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
							</span>
							<span><?php esc_html_e( 'Order management', 'service-booking-manager' ); ?></span>
						</div>
						<div class="mpwpb-woo-feature">
							<span class="mpwpb-woo-feature-icon">
								<svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M13.3 4.3L6 11.6 2.7 8.3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
							</span>
							<span><?php esc_html_e( 'Customer registration', 'service-booking-manager' ); ?></span>
						</div>
					</div>

					<div id="mpwpb-woo-progress" class="mpwpb-woo-progress" style="display:none;">
						<div class="mpwpb-woo-progress-bar">
							<div id="mpwpb-woo-progress-fill" class="mpwpb-woo-progress-fill"></div>
						</div>
						<p id="mpwpb-woo-status-text" class="mpwpb-woo-status-text"></p>
					</div>

					<div class="mpwpb-woo-actions">
						<button type="button" id="mpwpb-woo-install-btn" class="mpwpb-woo-btn mpwpb-woo-btn-primary">
							<span class="mpwpb-woo-btn-icon">
								<svg width="18" height="18" viewBox="0 0 20 20" fill="none">
									<path d="M10 3v10m0 0l-4-4m4 4l4-4M3 17h14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
								</svg>
							</span>
							<span class="mpwpb-woo-btn-text"><?php echo esc_html( $btn_text ); ?></span>
						</button>
						<a href="<?php echo esc_url( admin_url( 'plugin-install.php?s=woocommerce&tab=search&type=term' ) ); ?>" class="mpwpb-woo-btn mpwpb-woo-btn-secondary">
							<?php esc_html_e( 'Install Manually', 'service-booking-manager' ); ?>
						</a>
					</div>

					<p class="mpwpb-woo-footer-note">
						<svg width="14" height="14" viewBox="0 0 14 14" fill="none" style="vertical-align: -2px; flex-shrink: 0;">
							<path d="M7 1a6 6 0 100 12A6 6 0 007 1zm0 8.5a.75.75 0 110-1.5.75.75 0 010 1.5zM7.75 6.25a.75.75 0 01-1.5 0V4a.75.75 0 011.5 0v2.25z" fill="currentColor"/>
						</svg>
						<?php esc_html_e( 'WooCommerce is free, open-source, and trusted by millions of stores worldwide.', 'service-booking-manager' ); ?>
					</p>
				</div>
			</div>
			<?php
		}

		public function ajax_install_woocommerce() {
			check_ajax_referer( 'mpwpb_install_woo', 'nonce' );

			if ( ! current_user_can( 'install_plugins' ) ) {
				wp_send_json_error( array( 'message' => __( 'You do not have permission to install plugins.', 'service-booking-manager' ) ) );
			}

			include_once ABSPATH . 'wp-admin/includes/plugin-install.php';
			include_once ABSPATH . 'wp-admin/includes/file.php';
			include_once ABSPATH . 'wp-admin/includes/misc.php';
			include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

			$api = plugins_api( 'plugin_information', array(
				'slug'   => 'woocommerce',
				'fields' => array(
					'short_description' => false,
					'sections'          => false,
					'requires'          => false,
					'rating'            => false,
					'ratings'           => false,
					'downloaded'        => false,
					'last_updated'      => false,
					'added'             => false,
					'tags'              => false,
					'compatibility'     => false,
					'homepage'          => false,
					'donate_link'       => false,
				),
			) );

			if ( is_wp_error( $api ) ) {
				wp_send_json_error( array( 'message' => $api->get_error_message() ) );
			}

			$upgrader = new Plugin_Upgrader( new WP_Ajax_Upgrader_Skin() );
			$result   = $upgrader->install( $api->download_link );

			if ( is_wp_error( $result ) ) {
				wp_send_json_error( array( 'message' => $result->get_error_message() ) );
			}

			if ( $result === false ) {
				wp_send_json_error( array( 'message' => __( 'Installation failed.', 'service-booking-manager' ) ) );
			}

			wp_send_json_success( array( 'message' => __( 'WooCommerce installed successfully.', 'service-booking-manager' ) ) );
		}

		public function ajax_activate_woocommerce() {
			check_ajax_referer( 'mpwpb_activate_woo', 'nonce' );

			if ( ! current_user_can( 'activate_plugins' ) ) {
				wp_send_json_error( array( 'message' => __( 'You do not have permission to activate plugins.', 'service-booking-manager' ) ) );
			}

			$result = activate_plugin( 'woocommerce/woocommerce.php' );

			if ( is_wp_error( $result ) ) {
				wp_send_json_error( array( 'message' => $result->get_error_message() ) );
			}

			wp_send_json_success( array( 'message' => __( 'WooCommerce activated successfully!', 'service-booking-manager' ) ) );
		}
	}
}