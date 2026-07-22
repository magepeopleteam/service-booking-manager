<?php
/*
* Free-plugin-only "Pro Features" placeholder menu -- a single admin page,
* last in the menu (after Analytics), that teases the 4 admin pages that
* live entirely in service-booking-manager-pro (Backend Order, Order List,
* Service Queue, Service Calendar) as a 4-box grid. When Pro is active this
* class registers nothing at all -- Pro's own admin_menu hooks register the
* real, functional pages under their own slugs, so there is no collision.
*/
if (!defined('ABSPATH')) {
	die;
} // Cannot access pages directly.

if (!class_exists('MPWPB_Pro_Locked_Menus')) {
	class MPWPB_Pro_Locked_Menus {
		public function __construct() {
			if (MPWPB_Global_Function::is_pro_active()) {
				return;
			}
			add_action('admin_menu', array($this, 'register_locked_menu'));
		}

		/** No explicit position -- appends naturally after whatever else has
		 * already registered under this parent (Service List/Coupons/Status/
		 * Reviews/Staff Members/Settings/Analytics all run before this, since
		 * this file is required last in MPWPB_Admin.php::load_file()), landing
		 * it last, after Analytics. */
		public function register_locked_menu() {
			add_submenu_page(
				'edit.php?post_type=mpwpb_item',
				esc_html__('Pro Features', 'service-booking-manager'),
				esc_html__('Pro Features', 'service-booking-manager') . ' <span style="display:inline-block;background:linear-gradient(135deg,#f7b733,#fc4a1a);color:#fff;font-size:9px;font-weight:700;letter-spacing:.5px;border-radius:999px;padding:1px 7px;margin-left:4px;vertical-align:middle;">' . esc_html__('PRO', 'service-booking-manager') . '</span>',
				'manage_options',
				'mpwpb_pro_features',
				array($this, 'render_locked_page')
			);
		}

		public function render_locked_page() {
			$features = array(
				array(
					'icon' => 'dashicons-list-view',
					'label' => esc_html__('Order List', 'service-booking-manager'),
					'desc' => esc_html__('A dedicated, filterable list of every booking order, separate from WooCommerce\'s own Orders screen.', 'service-booking-manager'),
				),
				array(
					'icon' => 'dashicons-clock',
					'label' => esc_html__('Service Queue', 'service-booking-manager'),
					'desc' => esc_html__('A live queue view of upcoming and in-progress appointments across every service.', 'service-booking-manager'),
				),
				array(
					'icon' => 'dashicons-calendar-alt',
					'label' => esc_html__('Service Calendar', 'service-booking-manager'),
					'desc' => esc_html__('A calendar view of every booking across all services and staff, at a glance.', 'service-booking-manager'),
				),
				array(
					'icon' => 'dashicons-plus-alt',
					'label' => esc_html__('Backend Order', 'service-booking-manager'),
					'desc' => esc_html__('Create a booking manually from the admin on behalf of a customer -- phone bookings, walk-ins, or any order placed for them rather than by them.', 'service-booking-manager'),
				),
				array(
					'icon' => 'dashicons-tag',
					'label' => esc_html__('Happy Hours Pricing', 'service-booking-manager'),
					'desc' => esc_html__('Automatic time-of-day discounts -- give customers a lower price when their appointment falls inside a set window.', 'service-booking-manager'),
				),
				array(
					'icon' => 'dashicons-google',
					'label' => esc_html__('Google Calendar', 'service-booking-manager'),
					'desc' => esc_html__('Every completed booking is pushed straight to a connected Google Calendar as a real event.', 'service-booking-manager'),
				),
				array(
					'icon' => 'dashicons-media-spreadsheet',
					'label' => esc_html__('Google Sheets Sync', 'service-booking-manager'),
					'desc' => esc_html__('Keep a live, always up-to-date spreadsheet of every booking, synced automatically to Google Sheets.', 'service-booking-manager'),
				),
				array(
					'icon' => 'dashicons-shield',
					'label' => esc_html__('Two-Factor Authentication', 'service-booking-manager'),
					'desc' => esc_html__('Add an authenticator-app second login step, with backup codes, to admin and staff accounts.', 'service-booking-manager'),
				),
				array(
					'icon' => 'dashicons-yes-alt',
					'label' => esc_html__('Booking Verification', 'service-booking-manager'),
					'desc' => esc_html__('Confirm a booking is real before it\'s placed -- send the customer a phone or email verification code at checkout.', 'service-booking-manager'),
				),
				array(
					'icon' => 'dashicons-money-alt',
					'label' => esc_html__('Custom Payment (PayPal & Stripe)', 'service-booking-manager'),
					'desc' => esc_html__('Take bookings without WooCommerce -- a built-in checkout that accepts payment directly via PayPal and Stripe.', 'service-booking-manager'),
				),
				array(
					'icon' => 'dashicons-media-document',
					'label' => esc_html__('PDF Ticketing', 'service-booking-manager'),
					'desc' => esc_html__('Generate a downloadable PDF ticket for every booking, with a one-click Download Ticket button on the confirmation page.', 'service-booking-manager'),
				),
				array(
					'icon' => 'dashicons-email-alt',
					'label' => esc_html__('Automated Email Confirmation', 'service-booking-manager'),
					'desc' => esc_html__('Automatically email the customer a booking confirmation, with their PDF ticket attached, the moment their order completes.', 'service-booking-manager'),
				),
			);
			?>
			<div class="wrap">
				<div class="mpwpb-pro-lock-wrap">
					<header class="mpwpb-pro-lock-header">
						<h1><?php esc_html_e('Pro Features', 'service-booking-manager'); ?></h1>
						<p><?php esc_html_e('Requires the service-booking-manager-pro plugin to be installed and activated.', 'service-booking-manager'); ?></p>
					</header>
					<div class="mpwpb-pro-lock-grid">
						<?php foreach ($features as $feature) : ?>
							<div class="mpwpb-pro-lock-box">
								<div class="mpwpb-pro-lock-icon"><span class="dashicons <?php echo esc_attr($feature['icon']); ?>"></span></div>
								<h2><?php echo esc_html($feature['label']); ?> <span class="mpwpb-pro-badge"><?php esc_html_e('PRO', 'service-booking-manager'); ?></span></h2>
								<p><?php echo esc_html($feature['desc']); ?></p>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			</div>
			<style>
				.mpwpb-pro-lock-wrap { max-width: 1280px; margin: 30px auto 0; }
				.mpwpb-pro-lock-header { text-align: center; margin-bottom: 28px; }
				.mpwpb-pro-lock-header h1 { font-size: 22px; margin-bottom: 8px; }
				.mpwpb-pro-lock-header p { color: #787c82; font-size: 13px; }
				.mpwpb-pro-lock-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; }
				@media (max-width: 1200px) { .mpwpb-pro-lock-grid { grid-template-columns: repeat(3, 1fr); } }
				@media (max-width: 900px) { .mpwpb-pro-lock-grid { grid-template-columns: repeat(2, 1fr); } }
				@media (max-width: 600px) { .mpwpb-pro-lock-grid { grid-template-columns: 1fr; } }
				.mpwpb-pro-lock-box { background: #fff; border: 1px solid #dcdcde; border-radius: 10px; padding: 22px; text-align: center; }
				.mpwpb-pro-lock-icon { width: 52px; height: 52px; margin: 0 auto 14px; border-radius: 50%; background: #f0f0f1; display: flex; align-items: center; justify-content: center; }
				.mpwpb-pro-lock-icon .dashicons { font-size: 24px; width: 24px; height: 24px; color: #787c82; }
				.mpwpb-pro-lock-box h2 { font-size: 16px; margin: 0 0 10px; }
				.mpwpb-pro-lock-box p { color: #50575e; font-size: 13.5px; line-height: 1.6; margin: 0; }
				.mpwpb-pro-badge { display: inline-block; background: linear-gradient(135deg,#f7b733,#fc4a1a); color: #fff; font-size: 10px; font-weight: 700; letter-spacing: .5px; border-radius: 999px; padding: 2px 8px; margin-left: 4px; vertical-align: middle; }
			</style>
			<?php
		}
	}
	new MPWPB_Pro_Locked_Menus();
}
