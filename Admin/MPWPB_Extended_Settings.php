<?php
/*
* @Author 		MagePeople Team
* Copyright: 	mage-people.com
*/
if (!defined('ABSPATH')) {
	die;
} // Cannot access pages directly.

if (!class_exists('MPWPB_Extended_Settings')) {
	class MPWPB_Extended_Settings {
		public function __construct() {
			// Include the extended settings files
			$this->load_files();
			
			// Add tabs to the settings panel
			add_action('add_mpwpb_settings_tab_after_date', [$this, 'add_settings_tabs'], 10, 1);
		}
		
		private function load_files(): void {
			// Include the Recurring Booking settings
			if (!class_exists('MPWPB_Recurring_Booking_Settings')) {
				require_once MPWPB_PLUGIN_DIR . '/Admin/settings/Recurring_Booking.php';
			}
			
			// Include the Waiting List settings
			if (!class_exists('MPWPB_Waiting_List_Settings')) {
				require_once MPWPB_PLUGIN_DIR . '/Admin/settings/Waiting_List.php';
			}
		}
		
		public function add_settings_tabs($post_id) {
			?>
			<li data-tabs-target="#mpwpb_recurring_booking">
				<i class="fas fa-redo pe-1"></i><?php esc_html_e('Recurring Booking', 'service-booking-manager'); ?>
			</li>
			<li data-tabs-target="#mpwpb_waiting_list">
				<i class="fas fa-user-clock pe-1"></i><?php esc_html_e('Waiting List', 'service-booking-manager'); ?>
			</li>
			<?php
		}
	}
	
	// Initialize the class
	new MPWPB_Extended_Settings();
}