<?php
	/*
	 * Modern "Upgrade to Pro" admin notice.
	 *
	 * Shown only on this plugin's own admin screens, only to users who can
	 * manage options, and only while Pro is inactive -- Pro sites already have
	 * the real Order List / Service Queue / Calendar / Backend Orders, so they
	 * are never nagged. Dismissible per-user (persisted in user meta) so it
	 * never becomes noise. Links to the public pricing page and to the built-in
	 * "Pro Features" overview page.
	 */
	if (!defined('ABSPATH')) {
		die;
	} // Cannot access pages directly.

	if (!class_exists('MPWPB_Pro_Upsell_Notice')) {
		class MPWPB_Pro_Upsell_Notice {
			const DISMISS_META = 'mpwpb_pro_upsell_dismissed';
			const PRO_URL = 'https://mage-people.com/product/service-booking-plugin-wpbookingly/#pricing';

			public function __construct() {
				if (MPWPB_Global_Function::is_pro_active()) {
					return;
				}
				add_action('admin_init', [$this, 'maybe_dismiss']);
				add_action('admin_notices', [$this, 'render_notice']);
			}

			/** Persistent, per-user dismissal via a nonce-protected link (no JS
			 *  dependency -- the core is-dismissible handler only hides for the
			 *  current page load and wouldn't survive a refresh). */
			public function maybe_dismiss(): void {
				if (!isset($_GET['mpwpb_dismiss_pro_notice'])) {
					return;
				}
				if (!current_user_can('manage_options') || !check_admin_referer('mpwpb_dismiss_pro_notice')) {
					return;
				}
				update_user_meta(get_current_user_id(), self::DISMISS_META, '1');
				wp_safe_redirect(remove_query_arg(['mpwpb_dismiss_pro_notice', '_wpnonce']));
				exit;
			}

			private function is_target_screen(): bool {
				// Always surface on the main Dashboard -- the first place an admin
				// lands after logging in, so the upgrade is discoverable without
				// first having to open the plugin's own menu.
				if (isset($GLOBALS['pagenow']) && $GLOBALS['pagenow'] === 'index.php') {
					return true;
				}
				if (function_exists('get_current_screen')) {
					$screen = get_current_screen();
					if ($screen) {
						if ($screen->base === 'dashboard') {
							return true;
						}
						if (isset($screen->post_type) && $screen->post_type === 'mpwpb_item') {
							return true;
						}
					}
				}
				// Our own submenu pages register under the mpwpb_item CPT parent but
				// carry a ?page=mpwpb_* slug; catch those too (their screen post_type
				// isn't always populated the same way).
				if (isset($_GET['page']) && strpos(sanitize_text_field(wp_unslash($_GET['page'])), 'mpwpb') === 0) {
					return true;
				}
				return false;
			}

			public function render_notice(): void {
				if (!current_user_can('manage_options') || !$this->is_target_screen()) {
					return;
				}
				if (get_user_meta(get_current_user_id(), self::DISMISS_META, true)) {
					return;
				}
				$dismiss_url = wp_nonce_url(
					add_query_arg('mpwpb_dismiss_pro_notice', '1'),
					'mpwpb_dismiss_pro_notice'
				);
				$features_url = admin_url('edit.php?post_type=mpwpb_item&page=mpwpb_pro_features');
				$chips = [
					esc_html__('Advanced Order List', 'service-booking-manager'),
					esc_html__('Service Queue', 'service-booking-manager'),
					esc_html__('Service Calendar', 'service-booking-manager'),
					esc_html__('Backend Orders', 'service-booking-manager'),
					esc_html__('PDF Tickets', 'service-booking-manager'),
					esc_html__('Stripe & PayPal', 'service-booking-manager'),
					esc_html__('Google Calendar & Sheets', 'service-booking-manager'),
				];
				?>
				<div class="mpwpb-pro-notice">
					<span class="mpwpb-pro-notice-orb mpwpb-pro-notice-orb--a"></span>
					<span class="mpwpb-pro-notice-orb mpwpb-pro-notice-orb--b"></span>
					<a class="mpwpb-pro-notice-dismiss" href="<?php echo esc_url($dismiss_url); ?>" aria-label="<?php esc_attr_e('Dismiss this notice', 'service-booking-manager'); ?>" title="<?php esc_attr_e('Dismiss', 'service-booking-manager'); ?>">&times;</a>
					<div class="mpwpb-pro-notice-inner">
						<div class="mpwpb-pro-notice-icon"><span class="dashicons dashicons-star-filled"></span></div>
						<div class="mpwpb-pro-notice-body">
							<div class="mpwpb-pro-notice-badge"><?php esc_html_e('PRO', 'service-booking-manager'); ?></div>
							<h3><?php esc_html_e('Unlock the full booking suite', 'service-booking-manager'); ?></h3>
							<p><?php esc_html_e('You\'re on the free plan. Upgrade to Pro to manage every order in one place and unlock the complete toolkit:', 'service-booking-manager'); ?></p>
							<ul class="mpwpb-pro-notice-chips">
								<?php foreach ($chips as $chip) : ?>
									<li><span class="dashicons dashicons-yes"></span><?php echo esc_html($chip); ?></li>
								<?php endforeach; ?>
							</ul>
						</div>
						<div class="mpwpb-pro-notice-actions">
							<a class="mpwpb-pro-notice-btn" href="<?php echo esc_url(self::PRO_URL); ?>" target="_blank" rel="noopener">
								<span class="dashicons dashicons-cart"></span><?php esc_html_e('Upgrade to Pro', 'service-booking-manager'); ?>
							</a>
							<a class="mpwpb-pro-notice-link" href="<?php echo esc_url($features_url); ?>">
								<?php esc_html_e('See all Pro features', 'service-booking-manager'); ?> <span class="dashicons dashicons-arrow-right-alt2"></span>
							</a>
						</div>
					</div>
				</div>
				<?php $this->print_styles(); ?>
				<?php
			}

			private function print_styles(): void {
				?>
				<style>
					.mpwpb-pro-notice{
						position:relative;overflow:hidden;margin:16px 20px 14px 2px;padding:24px 26px;
						border:0;border-radius:16px;color:#eafaf6;
						background:linear-gradient(125deg,#00352f 0%,#00685f 42%,#0a9d8c 100%);
						box-shadow:0 14px 34px rgba(0,54,48,.34),inset 0 1px 0 rgba(255,255,255,.08);
					}
					.mpwpb-pro-notice *{box-sizing:border-box;}
					.mpwpb-pro-notice-orb{position:absolute;border-radius:50%;filter:blur(6px);opacity:.5;pointer-events:none;}
					.mpwpb-pro-notice-orb--a{width:200px;height:200px;top:-90px;right:-40px;background:radial-gradient(circle,rgba(247,183,51,.55),transparent 70%);}
					.mpwpb-pro-notice-orb--b{width:240px;height:240px;bottom:-140px;left:34%;background:radial-gradient(circle,rgba(45,212,191,.4),transparent 70%);}
					.mpwpb-pro-notice-inner{position:relative;z-index:1;display:flex;align-items:center;gap:22px;}
					.mpwpb-pro-notice-icon{
						flex:0 0 auto;width:60px;height:60px;display:flex;align-items:center;justify-content:center;
						border-radius:16px;background:linear-gradient(135deg,#f7b733,#fc4a1a);
						box-shadow:0 10px 22px rgba(252,74,26,.36);
					}
					.mpwpb-pro-notice-icon .dashicons{color:#fff;font-size:30px;width:30px;height:30px;}
					.mpwpb-pro-notice-body{flex:1 1 auto;min-width:0;}
					.mpwpb-pro-notice-badge{
						display:inline-block;margin-bottom:7px;padding:2px 10px;border-radius:999px;
						background:linear-gradient(135deg,#f7b733,#fc4a1a);color:#fff;
						font-size:10px;font-weight:800;letter-spacing:1px;
					}
					.mpwpb-pro-notice-body h3{margin:0 0 4px;color:#fff;font-size:20px;font-weight:800;line-height:1.25;}
					.mpwpb-pro-notice-body p{margin:0 0 12px;color:rgba(234,250,246,.82);font-size:13.5px;line-height:1.5;max-width:640px;}
					.mpwpb-pro-notice-chips{display:flex;flex-wrap:wrap;gap:8px;margin:0;padding:0;list-style:none;}
					.mpwpb-pro-notice-chips li{
						display:inline-flex;align-items:center;gap:5px;margin:0;padding:5px 12px 5px 8px;
						border-radius:999px;background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.16);
						color:#f2fdfa;font-size:12px;font-weight:600;
					}
					.mpwpb-pro-notice-chips li .dashicons{font-size:14px;width:14px;height:14px;color:#5eead4;}
					.mpwpb-pro-notice-actions{flex:0 0 auto;display:flex;flex-direction:column;align-items:stretch;gap:10px;text-align:center;}
					.mpwpb-pro-notice-btn{
						display:inline-flex;align-items:center;justify-content:center;gap:8px;
						padding:13px 26px;border-radius:12px;text-decoration:none;white-space:nowrap;
						background:linear-gradient(135deg,#f7b733,#fc4a1a);color:#fff !important;
						font-size:14.5px;font-weight:800;box-shadow:0 10px 24px rgba(252,74,26,.34);
						transition:transform .12s ease,box-shadow .12s ease;
					}
					.mpwpb-pro-notice-btn:hover{transform:translateY(-1px);box-shadow:0 14px 30px rgba(252,74,26,.44);color:#fff !important;}
					.mpwpb-pro-notice-btn .dashicons{font-size:17px;width:17px;height:17px;}
					.mpwpb-pro-notice-link{color:#bff3ea !important;font-size:12.5px;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;justify-content:center;gap:2px;}
					.mpwpb-pro-notice-link:hover{color:#fff !important;}
					.mpwpb-pro-notice-link .dashicons{font-size:15px;width:15px;height:15px;}
					.mpwpb-pro-notice-dismiss{
						position:absolute;top:12px;right:14px;z-index:2;width:26px;height:26px;line-height:24px;text-align:center;
						border-radius:50%;color:rgba(255,255,255,.72) !important;font-size:18px;text-decoration:none;
						background:rgba(255,255,255,.1);transition:background .12s ease,color .12s ease;
					}
					.mpwpb-pro-notice-dismiss:hover{background:rgba(255,255,255,.22);color:#fff !important;}
					@media (max-width:960px){
						.mpwpb-pro-notice-inner{flex-wrap:wrap;}
						.mpwpb-pro-notice-actions{flex:1 1 100%;flex-direction:row;flex-wrap:wrap;justify-content:flex-start;align-items:center;gap:14px;}
					}
					@media (max-width:600px){
						.mpwpb-pro-notice{padding:20px;margin:12px 10px 12px 0;}
						.mpwpb-pro-notice-icon{width:48px;height:48px;}
						.mpwpb-pro-notice-icon .dashicons{font-size:24px;width:24px;height:24px;}
						.mpwpb-pro-notice-body h3{font-size:17px;}
						.mpwpb-pro-notice-btn{width:100%;}
					}
				</style>
				<?php
			}
		}
		new MPWPB_Pro_Upsell_Notice();
	}
