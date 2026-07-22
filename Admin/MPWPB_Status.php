<?php
	/*
   * @Author 		engr.sumonazma@gmail.com
   * Copyright: 	mage-people.com
   */
	if (!defined('ABSPATH')) {
		die;
	} // Cannot access pages directly.
	if (!class_exists('MPWPB_Status')) {
		class MPWPB_Status {
			public function __construct() {
				add_action('admin_menu', array($this, 'status_menu'));
			}
			public function status_menu() {
				$cpt = MPWPB_Function::get_cpt();
				add_submenu_page('edit.php?post_type=' . $cpt, esc_html__('Status', 'service-booking-manager'), '<span style="color:yellow">' . esc_html__('Status', 'service-booking-manager') . '</span>', 'manage_options', 'mpwpb_status_page', array($this, 'status_page'));
			}

			/**
			 * A coloured status pill (good / warn / bad / na).
			 */
			private function pill($state, $text) {
				$map = array(
					'good' => array('mpwpb-st-pill--good', 'dashicons-yes-alt'),
					'warn' => array('mpwpb-st-pill--warn', 'dashicons-warning'),
					'bad'  => array('mpwpb-st-pill--bad', 'dashicons-dismiss'),
					'na'   => array('mpwpb-st-pill--na', 'dashicons-minus'),
				);
				$m = isset($map[$state]) ? $map[$state] : $map['na'];
				return '<span class="mpwpb-st-pill ' . esc_attr($m[0]) . '"><span class="dashicons ' . esc_attr($m[1]) . '"></span>' . esc_html($text) . '</span>';
			}

			/**
			 * One label/value row inside a status card. $value is pre-escaped /
			 * trusted HTML (plain text is passed through esc_html by the caller).
			 */
			private function row($label, $value) {
				echo '<div class="mpwpb-st-row"><span class="mpwpb-st-label">' . esc_html($label) . '</span><span class="mpwpb-st-value">' . $value . '</span></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $value is escaped/trusted by callers.
			}

			public function status_page() {
				global $wpdb;
				$label      = MPWPB_Function::get_name();
				$wc_i       = MPWPB_Global_Function::check_woocommerce();
				$wc_active  = ($wc_i == 1);

				/* ── versions ─────────────────────────────────────────── */
				$wp_v     = get_bloginfo('version');
				$php_v    = phpversion();
				$wc_v     = class_exists('WooCommerce') ? WC()->version : '-';
				$plugin_v = defined('MPWPB_VERSION') ? MPWPB_VERSION : '-';

				/* ── PHP ──────────────────────────────────────────────── */
				$mem_limit  = ini_get('memory_limit');
				$max_exec   = (int) ini_get('max_execution_time');
				$max_input  = ini_get('max_input_vars');
				$post_max   = ini_get('post_max_size');
				$upload_max = ini_get('upload_max_filesize');
				$sapi       = php_sapi_name();
				$disp_err   = ini_get('display_errors');

				/* ── Server ───────────────────────────────────────────── */
				$server_sw   = isset($_SERVER['SERVER_SOFTWARE']) ? sanitize_text_field(wp_unslash($_SERVER['SERVER_SOFTWARE'])) : esc_html__('Unknown', 'service-booking-manager');
				$server_os   = function_exists('php_uname') ? php_uname('s') . ' ' . php_uname('r') : PHP_OS;
				$server_ip   = isset($_SERVER['SERVER_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['SERVER_ADDR'])) : '';
				$server_host = isset($_SERVER['SERVER_NAME']) ? sanitize_text_field(wp_unslash($_SERVER['SERVER_NAME'])) : (function_exists('gethostname') ? gethostname() : '');
				$https       = is_ssl();

				/* ── Database ─────────────────────────────────────────── */
				$db_version = $wpdb->get_var('SELECT VERSION()');
				$db_charset = $wpdb->charset;
				$db_prefix  = $wpdb->prefix;

				/* ── WordPress ────────────────────────────────────────── */
				$wp_mem     = defined('WP_MEMORY_LIMIT') ? WP_MEMORY_LIMIT : '-';
				$wp_upload  = size_format(wp_max_upload_size());
				$theme      = wp_get_theme();
				$active_pl  = count((array) get_option('active_plugins', array()));
				$locale     = get_locale();
				$tz         = function_exists('wp_timezone_string') ? wp_timezone_string() : get_option('timezone_string');
				$permalinks = get_option('permalink_structure');
				$debug      = defined('WP_DEBUG') && WP_DEBUG;
				$multisite  = is_multisite();

				/* ── WooCommerce ──────────────────────────────────────── */
				$from_name  = get_option('woocommerce_email_from_name');
				$from_email = get_option('woocommerce_email_from_address');
				$currency   = function_exists('get_woocommerce_currency') ? get_woocommerce_currency() : '-';

				$dummy_imported = class_exists('MPWPB_Dummy_Import') && MPWPB_Dummy_Import::is_already_imported();

				/* ── status evaluations ───────────────────────────────── */
				$php_state    = version_compare($php_v, '7.4', '>=') ? 'good' : (version_compare($php_v, '7.2', '>=') ? 'warn' : 'bad');
				$wp_state     = version_compare($wp_v, '5.5', '>=') ? 'good' : 'warn';
				$mem_bytes    = wp_convert_hr_to_bytes($mem_limit);
				$mem_state    = $mem_bytes >= 128 * MB_IN_BYTES ? 'good' : ($mem_bytes >= 64 * MB_IN_BYTES ? 'warn' : 'bad');
				$upload_state = wp_convert_hr_to_bytes($upload_max) >= 8 * MB_IN_BYTES ? 'good' : 'warn';
				$exec_state   = ($max_exec === 0 || $max_exec >= 30) ? 'good' : 'warn';

				$this->render_status_styles();
				?>
				<div class="wrap mpwpb_style mpwpb-status-modern">

					<div class="mpwpb-st-header">
						<div>
							<h1><?php echo esc_html($label) . ' ' . esc_html__('System Status', 'service-booking-manager'); ?></h1>
							<p><?php esc_html_e('Server, PHP, database and WordPress environment for your booking system.', 'service-booking-manager'); ?></p>
						</div>
						<div class="mpwpb-st-quickbadges">
							<span class="mpwpb-st-qb"><small><?php esc_html_e('PHP', 'service-booking-manager'); ?></small><b><?php echo esc_html($php_v); ?></b></span>
							<span class="mpwpb-st-qb"><small><?php esc_html_e('WordPress', 'service-booking-manager'); ?></small><b><?php echo esc_html($wp_v); ?></b></span>
							<span class="mpwpb-st-qb"><small><?php echo esc_html($label); ?></small><b><?php echo esc_html($plugin_v); ?></b></span>
						</div>
					</div>

					<?php do_action('mp_status_notice_sec'); ?>

					<div class="mpwpb-st-grid">

						<div class="mpwpb-st-card">
							<div class="mpwpb-st-card-head"><span class="mpwpb-st-ico mpwpb-st-ico-blue"><span class="dashicons dashicons-cloud"></span></span><h3><?php esc_html_e('Server', 'service-booking-manager'); ?></h3></div>
							<?php
							$this->row(__('Web Server', 'service-booking-manager'), esc_html($server_sw));
							$this->row(__('Operating System', 'service-booking-manager'), esc_html($server_os));
							if ($server_host) { $this->row(__('Hostname', 'service-booking-manager'), esc_html($server_host)); }
							if ($server_ip) { $this->row(__('Server IP', 'service-booking-manager'), esc_html($server_ip)); }
							$this->row(__('HTTPS', 'service-booking-manager'), $https ? $this->pill('good', __('Enabled', 'service-booking-manager')) : $this->pill('warn', __('Disabled', 'service-booking-manager')));
							$this->row(__('PHP Interface', 'service-booking-manager'), esc_html($sapi));
							?>
						</div>

						<div class="mpwpb-st-card">
							<div class="mpwpb-st-card-head"><span class="mpwpb-st-ico mpwpb-st-ico-purple"><span class="dashicons dashicons-editor-code"></span></span><h3><?php esc_html_e('PHP', 'service-booking-manager'); ?></h3></div>
							<?php
							$this->row(__('PHP Version', 'service-booking-manager'), esc_html($php_v) . ' ' . $this->pill($php_state, $php_state === 'good' ? __('Good', 'service-booking-manager') : ($php_state === 'warn' ? __('Update advised', 'service-booking-manager') : __('Outdated', 'service-booking-manager'))));
							$this->row(__('Memory Limit', 'service-booking-manager'), esc_html($mem_limit) . ' ' . $this->pill($mem_state, $mem_state === 'good' ? __('OK', 'service-booking-manager') : __('Low', 'service-booking-manager')));
							$this->row(__('Max Execution Time', 'service-booking-manager'), esc_html($max_exec) . 's ' . $this->pill($exec_state, $exec_state === 'good' ? __('OK', 'service-booking-manager') : __('Low', 'service-booking-manager')));
							$this->row(__('Upload Max Filesize', 'service-booking-manager'), esc_html($upload_max) . ' ' . $this->pill($upload_state, $upload_state === 'good' ? __('OK', 'service-booking-manager') : __('Low', 'service-booking-manager')));
							$this->row(__('Post Max Size', 'service-booking-manager'), esc_html($post_max));
							$this->row(__('Max Input Vars', 'service-booking-manager'), esc_html($max_input));
							$this->row(__('Display Errors', 'service-booking-manager'), $disp_err ? $this->pill('warn', __('On', 'service-booking-manager')) : $this->pill('good', __('Off', 'service-booking-manager')));
							?>
						</div>

						<div class="mpwpb-st-card">
							<div class="mpwpb-st-card-head"><span class="mpwpb-st-ico mpwpb-st-ico-teal"><span class="dashicons dashicons-database"></span></span><h3><?php esc_html_e('Database', 'service-booking-manager'); ?></h3></div>
							<?php
							$this->row(__('Server Version', 'service-booking-manager'), esc_html($db_version));
							$this->row(__('Charset', 'service-booking-manager'), esc_html($db_charset));
							$this->row(__('Table Prefix', 'service-booking-manager'), esc_html($db_prefix));
							?>
						</div>

						<div class="mpwpb-st-card">
							<div class="mpwpb-st-card-head"><span class="mpwpb-st-ico mpwpb-st-ico-indigo"><span class="dashicons dashicons-wordpress"></span></span><h3><?php esc_html_e('WordPress', 'service-booking-manager'); ?></h3></div>
							<?php
							$this->row(__('Version', 'service-booking-manager'), esc_html($wp_v) . ' ' . $this->pill($wp_state, $wp_state === 'good' ? __('Good', 'service-booking-manager') : __('Update advised', 'service-booking-manager')));
							$this->row(__('WP Memory Limit', 'service-booking-manager'), esc_html($wp_mem));
							$this->row(__('Max Upload Size', 'service-booking-manager'), esc_html($wp_upload));
							$this->row(__('Multisite', 'service-booking-manager'), $multisite ? __('Yes', 'service-booking-manager') : __('No', 'service-booking-manager'));
							$this->row(__('Debug Mode', 'service-booking-manager'), $debug ? $this->pill('warn', __('On', 'service-booking-manager')) : $this->pill('good', __('Off', 'service-booking-manager')));
							$this->row(__('Language', 'service-booking-manager'), esc_html($locale));
							$this->row(__('Timezone', 'service-booking-manager'), esc_html($tz ? $tz : 'UTC'));
							$this->row(__('Permalinks', 'service-booking-manager'), $permalinks ? esc_html($permalinks) : $this->pill('warn', __('Plain', 'service-booking-manager')));
							?>
						</div>

						<div class="mpwpb-st-card">
							<div class="mpwpb-st-card-head"><span class="mpwpb-st-ico mpwpb-st-ico-green"><span class="dashicons dashicons-cart"></span></span><h3><?php esc_html_e('WooCommerce', 'service-booking-manager'); ?></h3></div>
							<?php
							$this->row(__('Installed', 'service-booking-manager'), $wc_active ? $this->pill('good', __('Yes', 'service-booking-manager')) : $this->pill('bad', __('No', 'service-booking-manager')));
							if ($wc_active) {
								$this->row(__('Version', 'service-booking-manager'), esc_html($wc_v));
								$this->row(__('Currency', 'service-booking-manager'), esc_html($currency));
								$this->row(__('Email From Name', 'service-booking-manager'), $from_name ? esc_html($from_name) : $this->pill('warn', __('Not set', 'service-booking-manager')));
								$this->row(__('Email From Address', 'service-booking-manager'), $from_email ? esc_html($from_email) : $this->pill('warn', __('Not set', 'service-booking-manager')));
							}
							?>
						</div>

						<div class="mpwpb-st-card">
							<div class="mpwpb-st-card-head"><span class="mpwpb-st-ico mpwpb-st-ico-amber"><span class="dashicons dashicons-admin-appearance"></span></span><h3><?php esc_html_e('Theme &amp; Plugins', 'service-booking-manager'); ?></h3></div>
							<?php
							$this->row(__('Active Theme', 'service-booking-manager'), esc_html($theme->get('Name') . ' ' . $theme->get('Version')));
							$this->row(__('Active Plugins', 'service-booking-manager'), esc_html($active_pl));
							$this->row($label . ' ' . __('Version', 'service-booking-manager'), esc_html($plugin_v));
							?>
						</div>

					</div><!-- /.mpwpb-st-grid -->

					<div class="mpwpb-st-card mpwpb-st-block">
						<div class="mpwpb-st-card-head"><span class="mpwpb-st-ico mpwpb-st-ico-slate"><span class="dashicons dashicons-admin-plugins"></span></span><h3><?php esc_html_e('PHP Extensions', 'service-booking-manager'); ?></h3></div>
						<div class="mpwpb-st-ext">
							<?php
							$exts = array('curl', 'mbstring', 'gd', 'imagick', 'zip', 'dom', 'xml', 'openssl', 'intl', 'soap', 'fileinfo', 'exif', 'json', 'bcmath', 'sodium');
							foreach ($exts as $ext) {
								$on = extension_loaded($ext);
								echo '<span class="mpwpb-st-ext-item ' . ($on ? 'is-on' : 'is-off') . '"><span class="dashicons ' . ($on ? 'dashicons-yes' : 'dashicons-no-alt') . '"></span>' . esc_html($ext) . '</span>';
							}
							?>
						</div>
					</div>

					<?php
					ob_start();
					do_action('mp_status_table_item_sec');
					$hooked = trim(ob_get_clean());
					if ($hooked) {
						?>
						<div class="mpwpb-st-card mpwpb-st-block">
							<div class="mpwpb-st-card-head"><span class="mpwpb-st-ico mpwpb-st-ico-slate"><span class="dashicons dashicons-admin-generic"></span></span><h3><?php esc_html_e('Add-ons &amp; Libraries', 'service-booking-manager'); ?></h3></div>
							<table class="mpwpb-st-hooktable">
								<tbody><?php echo $hooked; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted add-on markup. ?></tbody>
							</table>
						</div>
						<?php
					}
					?>

					<?php if ($wc_active) { ?>
						<div class="mpwpb-st-card mpwpb-st-block mpwpb-st-import">
							<div class="mpwpb-st-card-head"><span class="mpwpb-st-ico mpwpb-st-ico-blue"><span class="dashicons dashicons-download"></span></span><h3><?php esc_html_e('Dummy Data Import', 'service-booking-manager'); ?></h3></div>
							<div class="mpwpb-st-import-body">
								<?php if ($dummy_imported) { ?>
									<p><span class="dashicons dashicons-yes-alt" style="color:#16a34a;"></span> <?php esc_html_e('Dummy data has already been imported. You can re-import to restore the default demo services.', 'service-booking-manager'); ?></p>
								<?php } else { ?>
									<p><span class="dashicons dashicons-info" style="color:#2563eb;"></span> <?php esc_html_e('Import dummy services to quickly see how Service Booking Manager works. This creates sample service posts with categories and settings.', 'service-booking-manager'); ?></p>
								<?php } ?>
								<button type="button" id="mpwpb-trigger-dummy-import-btn" class="mpwpb-st-import-btn">
									<span class="dashicons dashicons-download"></span>
									<?php echo $dummy_imported ? esc_html__('Re-Import Dummy Data', 'service-booking-manager') : esc_html__('Import Dummy Data', 'service-booking-manager'); ?>
								</button>
							</div>
						</div>
					<?php } ?>

				</div>
				<?php
			}

			/**
			 * Inline, page-scoped styles for the modern status dashboard.
			 */
			private function render_status_styles() {
				?>
				<style>
					.mpwpb-status-modern{background:#f8fafc;margin:12px 20px 0 0;padding:6px 0 40px;}
					.mpwpb-status-modern *{box-sizing:border-box;}
					.mpwpb-status-modern .mpwpb-st-header{display:flex;align-items:flex-start;justify-content:space-between;gap:20px;flex-wrap:wrap;margin-bottom:22px; padding: 10px;}
					.mpwpb-status-modern .mpwpb-st-header h1{font-size:24px;font-weight:700;color:#0f172a;margin:0 0 5px;padding:0;}
					.mpwpb-status-modern .mpwpb-st-header p{margin:0;color:#64748b;font-size:13.5px;}
					.mpwpb-status-modern .mpwpb-st-quickbadges{display:flex;gap:10px;flex-wrap:wrap;}
					.mpwpb-status-modern .mpwpb-st-qb{display:flex;flex-direction:column;gap:1px;background:#fff;border:1px solid #e8edf3;border-radius:10px;padding:8px 16px;min-width:80px;box-shadow:0 1px 2px rgba(15,23,42,.04);}
					.mpwpb-status-modern .mpwpb-st-qb small{font-size:10px;text-transform:uppercase;letter-spacing:.05em;color:#94a3b8;font-weight:600;}
					.mpwpb-status-modern .mpwpb-st-qb b{font-size:16px;font-weight:700;color:#0f172a;}

					.mpwpb-status-modern .mpwpb-st-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:18px;}
					.mpwpb-status-modern .mpwpb-st-card{background:#fff;border:1px solid #e8edf3;border-radius:14px;box-shadow:0 1px 3px rgba(15,23,42,.05);padding:16px 20px 6px;}
					.mpwpb-status-modern .mpwpb-st-block{margin-top:18px;padding-bottom:18px;}
					.mpwpb-status-modern .mpwpb-st-card-head{display:flex;align-items:center;gap:11px;padding-bottom:13px;margin-bottom:4px;border-bottom:1px solid #f1f5f9;}
					.mpwpb-status-modern .mpwpb-st-card-head h3{margin:0;padding:0;font-size:15px;font-weight:700;color:#0f172a;}
					.mpwpb-status-modern .mpwpb-st-ico{width:34px;height:34px;border-radius:9px;display:flex;align-items:center;justify-content:center;flex:0 0 auto;}
					.mpwpb-status-modern .mpwpb-st-ico .dashicons{font-size:18px;width:18px;height:18px;line-height:18px;}
					.mpwpb-st-ico-blue{background:#eff6ff;color:#2563eb;}
					.mpwpb-st-ico-purple{background:#f5f3ff;color:#7c3aed;}
					.mpwpb-st-ico-teal{background:#f0fdfa;color:#0d9488;}
					.mpwpb-st-ico-indigo{background:#eef2ff;color:#4f46e5;}
					.mpwpb-st-ico-green{background:#f0fdf4;color:#16a34a;}
					.mpwpb-st-ico-amber{background:#fffbeb;color:#d97706;}
					.mpwpb-st-ico-slate{background:#f1f5f9;color:#475569;}

					.mpwpb-status-modern .mpwpb-st-row{display:flex;align-items:center;justify-content:space-between;gap:14px;padding:9px 0;border-bottom:1px solid #f4f6f9;font-size:13px;}
					.mpwpb-status-modern .mpwpb-st-row:last-child{border-bottom:none;}
					.mpwpb-status-modern .mpwpb-st-label{color:#64748b;flex:0 0 auto;}
					.mpwpb-status-modern .mpwpb-st-value{color:#0f172a;font-weight:600;text-align:right;word-break:break-word;display:flex;align-items:center;gap:7px;justify-content:flex-end;flex-wrap:wrap;}

					.mpwpb-st-pill{display:inline-flex;align-items:center;gap:4px;padding:2px 9px;border-radius:999px;font-size:11px;font-weight:600;white-space:nowrap;}
					.mpwpb-st-pill .dashicons{font-size:13px;width:13px;height:13px;line-height:13px;}
					.mpwpb-st-pill--good{background:#dcfce7;color:#15803d;}
					.mpwpb-st-pill--warn{background:#fef3c7;color:#b45309;}
					.mpwpb-st-pill--bad{background:#fee2e2;color:#b91c1c;}
					.mpwpb-st-pill--na{background:#f1f5f9;color:#64748b;}

					.mpwpb-status-modern .mpwpb-st-ext{display:flex;flex-wrap:wrap;gap:8px;padding:2px 0 6px;}
					.mpwpb-st-ext-item{display:inline-flex;align-items:center;gap:5px;padding:5px 12px;border-radius:8px;font-size:12.5px;font-weight:600;}
					.mpwpb-st-ext-item .dashicons{font-size:15px;width:15px;height:15px;line-height:15px;}
					.mpwpb-st-ext-item.is-on{background:#f0fdf4;color:#15803d;border:1px solid #dcfce7;}
					.mpwpb-st-ext-item.is-off{background:#fef2f2;color:#b91c1c;border:1px solid #fee2e2;}

					.mpwpb-status-modern .mpwpb-st-hooktable{width:100%;border-collapse:collapse;}
					.mpwpb-status-modern .mpwpb-st-hooktable th{text-align:left;font-weight:400;padding:9px 0;border:none;border-bottom:1px solid #f4f6f9;font-size:13px;color:#64748b;background:transparent;}
					.mpwpb-status-modern .mpwpb-st-hooktable tr:last-child th{border-bottom:none;}
					.mpwpb-status-modern .mpwpb-st-hooktable th:last-child{text-align:right;color:#0f172a;font-weight:600;}
					.mpwpb-status-modern .textSuccess{color:#16a34a;}
					.mpwpb-status-modern .textWarning{color:#d97706;}

					.mpwpb-status-modern .mpwpb-st-import-body{padding:6px 0 2px;}
					.mpwpb-status-modern .mpwpb-st-import-body p{font-size:13.5px;color:#475569;margin:0 0 14px;display:flex;align-items:center;gap:6px;}
					.mpwpb-st-import-btn{display:inline-flex;align-items:center;gap:7px;background:#2563eb;color:#fff;border:none;border-radius:9px;padding:10px 22px;font-size:13.5px;font-weight:600;cursor:pointer;}
					.mpwpb-st-import-btn:hover{background:#1d4ed8;}
					.mpwpb-st-import-btn .dashicons{font-size:16px;width:16px;height:16px;line-height:16px;}

					@media (max-width:782px){
						.mpwpb-status-modern{margin-right:10px;}
						.mpwpb-status-modern .mpwpb-st-grid{grid-template-columns:1fr;}
					}
				</style>
				<?php
			}
		}
		new MPWPB_Status();
	}
