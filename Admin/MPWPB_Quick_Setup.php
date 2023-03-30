<?php
	if ( ! defined( 'ABSPATH' ) ) {
		die;
	} // Cannot access pages directly.
	if ( ! class_exists( 'MPWPB_Quick_Setup' ) ) {
		class MPWPB_Quick_Setup {
			public function __construct() {
				if ( ! class_exists( 'MPWPB_Dependencies' ) ) {
					add_action( 'admin_enqueue_scripts', array( $this, 'add_admin_scripts' ), 10, 1 );
				}
				add_action( 'admin_menu', array( $this, 'quick_setup_menu' ) );
				add_action( 'mpwpb_quick_setup_content_start', array( $this, 'setup_welcome_content' ) );
				add_action( 'mpwpb_quick_setup_content_general', array( $this, 'setup_general_content' ) );
				add_action( 'mpwpb_quick_setup_content_done', array( $this, 'setup_content_done' ) );
			}
			public function add_admin_scripts() {
				wp_enqueue_style( 'mp_plugin_global', MPWPB_PLUGIN_URL . '/assets/helper/mp_style/mp_style.css', array(), time() );
				wp_enqueue_script( 'mp_plugin_global', MPWPB_PLUGIN_URL . '/assets/helper/mp_style/mp_script.js', array( 'jquery' ), time(), true );
				wp_enqueue_script( 'mp_admin_settings', MPWPB_PLUGIN_URL . '/assets/admin/mp_admin_settings.js', array( 'jquery' ), time(), true );
				wp_enqueue_style( 'mp_admin_settings', MPWPB_PLUGIN_URL . '/assets/admin/mp_admin_settings.css', array(), time() );
				wp_enqueue_script( 'mpwpb_admin', MPWPB_PLUGIN_URL . '/assets/admin/mpwpb_admin.js', array( 'jquery' ), time(), true );
				wp_enqueue_style( 'mpwpb_admin', MPWPB_PLUGIN_URL . '/assets/admin/mpwpb_admin.css', array(), time() );
				wp_enqueue_style( 'mptbm_font_awesome', '//cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.15.4/css/all.min.css', array(), '5.15.4' );
			}
			public function quick_setup_menu() {
				$status = MPWPB_Plugin::check_woocommerce();
				if ( $status == 1 ) {
					add_submenu_page( 'edit.php?post_type=mpwpb_item', esc_html__( 'Quick Setup', 'bookingmaster' ), '<span style="color:#10dd10">' . esc_html__( 'Quick Setup', 'bookingmaster' ) . '</span>', 'manage_options', 'mpwpb_quick_setup', array( $this, 'quick_setup' ) );
					add_submenu_page( 'mpwpb_item', esc_html__( 'Quick Setup', 'bookingmaster' ), '<span style="color:#10dd10">' . esc_html__( 'Quick Setup', 'bookingmaster' ) . '</span>', 'manage_options', 'mpwpb_quick_setup', array( $this, 'quick_setup' ) );
				} else {
					add_menu_page( esc_html__( 'Easy Booking', 'bookingmaster' ), esc_html__( 'Easy Booking', 'bookingmaster' ), 'manage_options', 'transportation', array( $this, 'quick_setup' ), 'dashicons-admin-site-alt2', 6 );
					add_submenu_page( 'mpwpb_item', esc_html__( 'Quick Setup', 'bookingmaster' ), '<span style="color:#10dd17">' . esc_html__( 'Quick Setup', 'bookingmaster' ) . '</span>', 'manage_options', 'mpwpb_quick_setup', array( $this, 'quick_setup' ) );
				}
			}
			public function quick_setup() {
				$mep_settings_tab   = array();
				$mep_settings_tab[] = array(
					'id'       => 'start',
					'title'    => '<i class="far fa-thumbs-up mR_xs"></i>' . esc_html__( 'Welcome', 'bookingmaster' ),
					'priority' => 1,
					'active'   => true,
				);
				$mep_settings_tab[] = array(
					'id'       => 'general',
					'title'    => '<i class="fas fa-list-ul mR_xs"></i>' . esc_html__( 'General', 'bookingmaster' ),
					'priority' => 2,
					'active'   => false,
				);
				$mep_settings_tab[] = array(
					'id'       => 'done',
					'title'    => '<i class="fas fa-pencil-alt mR_xs"></i>' . esc_html__( 'Done', 'bookingmaster' ),
					'priority' => 4,
					'active'   => false,
				);
				$mep_settings_tab   = apply_filters( 'qa_welcome_tabs', $mep_settings_tab );
				$tabs_sorted        = array();
				foreach ( $mep_settings_tab as $page_key => $tab ) {
					$tabs_sorted[ $page_key ] = $tab['priority'] ?? 0;
				}
				array_multisort( $tabs_sorted, SORT_ASC, $mep_settings_tab );
				if ( isset( $_POST['active_woo_btn'] ) ) {
					?>
					<script>
								  dLoaderBody();
					</script>
					<?php
					activate_plugin( 'woocommerce/woocommerce.php' );
					?>
					<script>
								  let mpwpb_admin_location = window.location.href;
								  mpwpb_admin_location = mpwpb_admin_location.replace('admin.php?page=mpwpb_item', 'edit.php?post_type=mpwpb_item&page=mpwpb_quick_setup');
								  window.location.href = mpwpb_admin_location;
					</script>
					<?php
				}
				if ( isset( $_POST['install_and_active_woo_btn'] ) ) {
					echo '<div style="display:none">';
					include_once( ABSPATH . 'wp-admin/includes/plugin-install.php' ); //for plugins_api..
					$plugin = 'woocommerce';
					$api    = plugins_api( 'plugin_information', array(
						'slug'   => $plugin,
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
					//includes necessary for Plugin_Upgrader and Plugin_Installer_Skin
					include_once( ABSPATH . 'wp-admin/includes/file.php' );
					include_once( ABSPATH . 'wp-admin/includes/misc.php' );
					include_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );
					$woocommerce_plugin = new Plugin_Upgrader( new Plugin_Installer_Skin( compact( 'title', 'url', 'nonce', 'plugin', 'api' ) ) );
					$woocommerce_plugin->install( $api->download_link );
					activate_plugin( 'woocommerce/woocommerce.php' );
					echo '</div>';
					?>
					<script>
								  let mpwpb_admin_location = window.location.href;
								  mpwpb_admin_location = mpwpb_admin_location.replace('admin.php?page=mpwpb_item', 'edit.php?post_type=mpwpb_item&page=mpwpb_quick_setup');
								  window.location.href = mpwpb_admin_location;
					</script>
					<?php
				}
				if ( isset( $_POST['finish_quick_setup'] ) ) {
					$label                       = isset( $_POST['mpwpb_label'] ) ? sanitize_text_field( $_POST['mpwpb_label'] ) : 'Transportation';
					$slug                        = isset( $_POST['mpwpb_slug'] ) ? sanitize_text_field( $_POST['mpwpb_slug'] ) : 'transportation';
					$general_settings_data       = get_option( 'mpwpb_general_settings' );
					$update_general_settings_arr = [
						'label' => $label,
						'slug'  => $slug
					];
					$new_general_settings_data   = is_array( $general_settings_data ) ? array_replace( $general_settings_data, $update_general_settings_arr ) : $update_general_settings_arr;
					update_option( 'mpwpb_general_settings', $new_general_settings_data );
					flush_rewrite_rules();
					wp_redirect( admin_url( 'edit.php?post_type=mpwpb_item&page=mpwpb_quick_setup' ) );
				}
				?>
				<div class="mpStyle">
					<div id="mp_quick_setup" class="dLayout">
						<form method="post" action="">
							<div class="welcome-tabs">
								<ul class="tab-navs">
									<?php
										foreach ( $mep_settings_tab as $tab ) {
											$id     = $tab['id'];
											$title  = $tab['title'];
											$active = $tab['active'];
											$hidden = $tab['hidden'] ?? false;
											?>
											<li class="tab-nav <?php echo esc_attr($active ? 'active' : ''); ?> <?php echo esc_attr($hidden ? 'hidden' : ''); ?> " data-id="<?php echo esc_attr( $id ); ?>"><?php echo esc_attr($title); ?></li>
										<?php } ?>
								</ul>
								<?php
									foreach ( $mep_settings_tab as $tab ) {
										$id     = $tab['id'];
										$active = $tab['active'];
										?>
										<div class="tab-content <?php echo esc_attr($active ? 'active' : ''); ?>" id="<?php echo esc_html( $id ); ?>">
											<?php do_action( 'mpwpb_quick_setup_content_' . $id ); ?>
											<?php do_action( 'mpwpb_quick_setup_content_after', $tab ); ?>
										</div>
									<?php } ?>
								<div class="next-prev">
									<div class="prev"><span>&longleftarrow;<?php esc_html_e( 'Previous', 'bookingmaster' ); ?></span></div>
									<div></div>
									<div class="next"><span><?php esc_html_e( 'Next', 'bookingmaster' ); ?>&longrightarrow;</span></div>
								</div>
							</div>
						</form>
					</div>
				</div>
				<?php
			}
			public function setup_welcome_content() {
				$status = MPWPB_Plugin::check_woocommerce();
				?>
				<h2><?php esc_html_e( 'WP Easy Booking Manager For Woocommerce Plugin', 'bookingmaster' ); ?></h2>
				<p class="mTB_xs"><?php esc_html_e( 'WP Easy Booking manager Plugin for WooCommerce for your site, Please go step by step and choose some options to get started.', 'bookingmaster' ); ?></p>
				<table class="wc_status_table widefat" id="status">
					<tr>
						<td>
							<?php if ( $status == 1 ) {
								esc_html_e( 'Woocommerce already installed and activated', 'bookingmaster' );
							} elseif ( $status == 0 ) {
								esc_html_e( 'Woocommerce need to install and active', 'bookingmaster' );
							} else {
								esc_html_e( 'Woocommerce already install , please activate it', 'bookingmaster' );
							} ?>
						</td>
						<td class="woo_btn_td">
							<?php if ( $status == 1 ) { ?>
								<span class="fas fa-check-circle"></span>
							<?php } elseif ( $status == 0 ) { ?>
								<button class="button" type="submit" name="install_and_active_woo_btn"><?php esc_html_e( 'Install & Active Now', 'bookingmaster' ); ?></button>
							<?php } else { ?>
								<button class="button" type="submit" name="active_woo_btn"><?php esc_html_e( 'Active Now', 'bookingmaster' ); ?></button>
							<?php } ?>
						</td>
					</tr>
				</table>
				<?php
			}
			public function setup_general_content() {
				$general_data = get_option( 'mpwpb_general_settings' );
				$label        = $general_data['label'] ?? 'WP Easy Booking';
				$slug         = $general_data['slug'] ?? 'service';
				?>
				<div class="section">
					<h2><?php esc_html_e( 'General settings', 'bookingmaster' ); ?></h2>
					<p class="mTB_xs"><?php echo __( 'Choose some general option.', 'bookingmaster' ); ?></p>
					<table class="wc_status_table widefat" id="status">
						<tr>
							<td><?php esc_html_e( 'WP Easy Booking Label:', 'bookingmaster' ); ?></td>
							<td>
								<label><input type="text" name="mpwpb_label" value='<?php echo esc_html( $label ); ?>'/></label>
								<p class="info"><?php esc_html_e( 'It will change the WP Easy Booking post type label on the entire plugin.', 'bookingmaster' ); ?></p>
							</td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'WP Easy Booking Slug:', 'bookingmaster' ); ?></td>
							<td>
								<label><input type="text" name="mpwpb_slug" value='<?php echo esc_html( $slug ); ?>'/></label>
								<p class="info"><?php esc_html_e( 'It will change the WP Easy Booking slug on the entire plugin. Remember after changing this slug you need to flush permalinks. Just go to Settings->Permalinks hit the Save Settings button', 'bookingmaster' ); ?></p>
							</td>
						</tr>
					</table>
				</div>
				<?php
			}
			public function setup_content_done() {
				?>
				<div class="section">
					<h2><?php esc_html_e( 'Finalize Setup', 'bookingmaster' ); ?></h2>
					<p class="mTB_xs"><?php esc_html_e( 'You are about to Finish & Save WP Easy Booking Manager For Woocommerce Plugin setup process', 'bookingmaster' ); ?></p>
					<div class="setup_save_finish_area">
						<button type="submit" name="finish_quick_setup" class="button setup_save_finish"><?php esc_html_e( 'Finish & Save', 'bookingmaster' ); ?></button>
					</div>
				</div>
				<?php
			}
		}
		new MPWPB_Quick_Setup();
	}