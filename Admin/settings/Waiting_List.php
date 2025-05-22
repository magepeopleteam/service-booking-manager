<?php
/*
* @Author 		MagePeople Team
* Copyright: 	mage-people.com
*/
if (!defined('ABSPATH')) {
	die;
} // Cannot access pages directly.
if (!class_exists('MPWPB_Waiting_List_Settings')) {
	class MPWPB_Waiting_List_Settings {
		public function __construct() {
			add_action('add_mpwpb_settings_tab_content', [$this, 'waiting_list_settings'], 10, 1);
			add_action('wp_ajax_mpwpb_join_waiting_list', array($this, 'join_waiting_list'));
			add_action('wp_ajax_nopriv_mpwpb_join_waiting_list', array($this, 'join_waiting_list'));
			add_action('wp_ajax_mpwpb_check_waiting_list', array($this, 'check_waiting_list'));
			add_action('wp_ajax_nopriv_mpwpb_check_waiting_list', array($this, 'check_waiting_list'));
			add_action('mpwpb_settings_save', array($this, 'save_waiting_list_settings'), 10, 1);
			// Hook into order cancellation to notify waiting list
			add_action('woocommerce_order_status_cancelled', array($this, 'notify_waiting_list_on_cancellation'), 10, 2);
        }
			
			
		

		public function waiting_list_settings($post_id) {
			$enable_waiting_list = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_enable_waiting_list', 'no');
			$max_waiting_list = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_max_waiting_list', 10);
			$waiting_list_email_subject = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_waiting_list_email_subject', 'Slot Available for {service_name}');
			$waiting_list_email_body = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_waiting_list_email_body', "Hello {customer_name},\n\nA slot has become available for {service_name} on {date} at {time}.\n\nPlease book soon as this slot may be taken by someone else.\n\nRegards,\n{site_name}");
			?>
			<div class="tabsItem" data-tabs="#mpwpb_waiting_list">
				<header>
					<h2><?php esc_html_e('Waiting List Settings', 'service-booking-manager'); ?></h2>
					<span><?php esc_html_e('Configure waiting list options for fully booked time slots', 'service-booking-manager'); ?></span>
				</header>
				<section class="section">
					<h2><?php esc_html_e('Waiting List Settings', 'service-booking-manager'); ?></h2>
					<span><?php esc_html_e('Configure waiting list options for fully booked time slots', 'service-booking-manager'); ?></span>
				</section>
				<section>
					<label class="label">
						<div>
							<p><?php esc_html_e('Enable Waiting List', 'service-booking-manager'); ?></p>
							<span><?php esc_html_e('Allow customers to join waiting list for fully booked slots', 'service-booking-manager'); ?></span>
						</div>
						<div class="customCheckboxLabel">
							<select name="mpwpb_enable_waiting_list">
								<option value="yes" <?php echo esc_attr($enable_waiting_list == 'yes' ? 'selected' : ''); ?>><?php esc_html_e('Yes', 'service-booking-manager'); ?></option>
								<option value="no" <?php echo esc_attr($enable_waiting_list == 'no' ? 'selected' : ''); ?>><?php esc_html_e('No', 'service-booking-manager'); ?></option>
							</select>
						</div>
					</label>
				</section>
				<section>
					<label class="label">
						<div>
							<p><?php esc_html_e('Maximum Waiting List Size', 'service-booking-manager'); ?></p>
							<span><?php esc_html_e('Maximum number of people allowed on waiting list per time slot', 'service-booking-manager'); ?></span>
						</div>
						<input type="number" name="mpwpb_max_waiting_list" value="<?php echo esc_attr($max_waiting_list); ?>" min="1" max="50" />
					</label>
				</section>
				<section>
					<label class="label">
						<div>
							<p><?php esc_html_e('Notification Email Subject', 'service-booking-manager'); ?></p>
							<span><?php esc_html_e('Subject for notification email when slot becomes available', 'service-booking-manager'); ?></span>
						</div>
						<input type="text" name="mpwpb_waiting_list_email_subject" value="<?php echo esc_attr($waiting_list_email_subject); ?>" />
					</label>
				</section>
				<section>
					<label class="label">
						<div>
							<p><?php esc_html_e('Notification Email Body', 'service-booking-manager'); ?></p>
							<span>
								<?php esc_html_e('Body for notification email when slot becomes available', 'service-booking-manager'); ?>
								<br>
								<?php esc_html_e('Available placeholders: {customer_name}, {service_name}, {date}, {time}, {site_name}', 'service-booking-manager'); ?>
							</span>
						</div>
						<textarea name="mpwpb_waiting_list_email_body" rows="10"><?php echo esc_textarea($waiting_list_email_body); ?></textarea>
					</label>
				</section>
			</div>
			<?php
		}

		public function join_waiting_list() {
			if (isset($_POST['nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'mpwpb_nonce')) {
				$post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
				$date = isset($_POST['date']) ? sanitize_text_field(wp_unslash($_POST['date'])) : '';
				$name = isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : '';
				$email = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
				$phone = isset($_POST['phone']) ? sanitize_text_field(wp_unslash($_POST['phone'])) : '';
				
				// Validate the data
				if (!$post_id || !$date || !$name || !$email) {
					wp_send_json_error(['message' => __('Please fill all required fields', 'service-booking-manager')]);
					return;
				}
				
				// Check if waiting list is enabled for this service
				$enable_waiting_list = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_enable_waiting_list', 'no');
				if ($enable_waiting_list !== 'yes') {
					wp_send_json_error(['message' => __('Waiting list is not enabled for this service', 'service-booking-manager')]);
					return;
				}
				
				// Check if waiting list is full
				$max_waiting_list = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_max_waiting_list', 10);
				$waiting_list = get_post_meta($post_id, 'mpwpb_waiting_list_' . sanitize_title($date), true);
				
				if (!is_array($waiting_list)) {
					$waiting_list = [];
				}
				
				if (count($waiting_list) >= $max_waiting_list) {
					wp_send_json_error(['message' => __('Waiting list is full for this time slot', 'service-booking-manager')]);
					return;
				}
				
				// Check if user is already on the waiting list
				foreach ($waiting_list as $entry) {
					if ($entry['email'] === $email) {
						wp_send_json_error(['message' => __('You are already on the waiting list for this time slot', 'service-booking-manager')]);
						return;
					}
				}
				
				// Add to waiting list
				$waiting_list[] = [
					'name' => $name,
					'email' => $email,
					'phone' => $phone,
					'date_added' => current_time('mysql')
				];
				
				update_post_meta($post_id, 'mpwpb_waiting_list_' . sanitize_title($date), $waiting_list);
				
				// Send confirmation email to customer
				$this->send_waiting_list_confirmation($post_id, $date, $name, $email);
				
				wp_send_json_success(['message' => __('You have been added to the waiting list. We will notify you if a slot becomes available.', 'service-booking-manager')]);
			} else {
				wp_send_json_error(['message' => __('Security check failed', 'service-booking-manager')]);
			}
		}
		
		public function check_waiting_list() {
			if (isset($_POST['nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'mpwpb_nonce')) {
				$post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
				$date = isset($_POST['date']) ? sanitize_text_field(wp_unslash($_POST['date'])) : '';
				
				if (!$post_id || !$date) {
					wp_send_json_error(['message' => __('Invalid data provided', 'service-booking-manager')]);
					return;
				}
				
				$enable_waiting_list = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_enable_waiting_list', 'no');
				$waiting_list = get_post_meta($post_id, 'mpwpb_waiting_list_' . sanitize_title($date), true);
				
				if (!is_array($waiting_list)) {
					$waiting_list = [];
				}
				
				$max_waiting_list = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_max_waiting_list', 10);
				$waiting_list_count = count($waiting_list);
				$waiting_list_available = $enable_waiting_list === 'yes' && $waiting_list_count < $max_waiting_list;
				
				wp_send_json_success([
					'enabled' => $enable_waiting_list === 'yes',
					'available' => $waiting_list_available,
					'count' => $waiting_list_count,
					'max' => $max_waiting_list
				]);
			} else {
				wp_send_json_error(['message' => __('Security check failed', 'service-booking-manager')]);
			}
		}
		
		public function notify_waiting_list_on_cancellation($order_id, $order) {
			foreach ($order->get_items() as $item_id => $item) {
				$post_id = wc_get_order_item_meta($item_id, '_mpwpb_id');
				if (get_post_type($post_id) == MPWPB_Function::get_cpt()) {
					$date = wc_get_order_item_meta($item_id, '_mpwpb_date');
					if ($date) {
						$this->notify_waiting_list($post_id, $date);
					}
				}
			}
		}

		 /**
         * Save waiting list settings
         * 
         * @param int $post_id
         */
        public function save_waiting_list_settings($post_id) {
		if (!isset($_POST['mpwpb_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['mpwpb_nonce'])), 'mpwpb_nonce')) {
		    return;
		}
		
		// Save enable waiting list setting
		$enable_waiting_list = isset($_POST['mpwpb_enable_waiting_list']) ? sanitize_text_field(wp_unslash($_POST['mpwpb_enable_waiting_list'])) : 'no';
		update_post_meta($post_id, 'mpwpb_enable_waiting_list', $enable_waiting_list);
		
		// Save max waiting list setting
		$max_waiting_list = isset($_POST['mpwpb_max_waiting_list']) ? absint($_POST['mpwpb_max_waiting_list']) : 10;
		update_post_meta($post_id, 'mpwpb_max_waiting_list', $max_waiting_list);
		
		// Save email subject
		$waiting_list_email_subject = isset($_POST['mpwpb_waiting_list_email_subject']) ? sanitize_text_field(wp_unslash($_POST['mpwpb_waiting_list_email_subject'])) : 'Slot Available for {service_name}';
		update_post_meta($post_id, 'mpwpb_waiting_list_email_subject', $waiting_list_email_subject);
		
		// Save email body
		$waiting_list_email_body = isset($_POST['mpwpb_waiting_list_email_body']) ? wp_kses_post(wp_unslash($_POST['mpwpb_waiting_list_email_body'])) : "Hello {customer_name},\n\nA slot has become available for {service_name} on {date} at {time}.\n\nPlease book soon as this slot may be taken by someone else.\n\nRegards,\n{site_name}";
		update_post_meta($post_id, 'mpwpb_waiting_list_email_body', $waiting_list_email_body);
	    }
	
		
		private function notify_waiting_list($post_id, $date) {
			$enable_waiting_list = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_enable_waiting_list', 'no');
			if ($enable_waiting_list !== 'yes') {
				return;
			}
			
			$waiting_list = get_post_meta($post_id, 'mpwpb_waiting_list_' . sanitize_title($date), true);
			if (!is_array($waiting_list) || empty($waiting_list)) {
				return;
			}
			
			// Get the first person on the waiting list
			$person = array_shift($waiting_list);
			
			// Update the waiting list
			update_post_meta($post_id, 'mpwpb_waiting_list_' . sanitize_title($date), $waiting_list);
			
			// Send notification email
			$this->send_slot_available_notification($post_id, $date, $person['name'], $person['email']);
		}
		
		private function send_waiting_list_confirmation($post_id, $date, $name, $email) {
			$service_name = get_the_title($post_id);
			$date_formatted = MPWPB_Global_Function::date_format($date);
			$time_formatted = MPWPB_Global_Function::date_format($date, 'time');
			
			$subject = sprintf(__('You have been added to the waiting list for %s', 'service-booking-manager'), $service_name);
			
			$message = sprintf(
				__("Hello %s,\n\nYou have been added to the waiting list for %s on %s at %s.\n\nWe will notify you if a slot becomes available.\n\nRegards,\n%s", 'service-booking-manager'),
				$name,
				$service_name,
				$date_formatted,
				$time_formatted,
				get_bloginfo('name')
			);
			
			$headers = ['Content-Type: text/html; charset=UTF-8'];
			
			wp_mail($email, $subject, nl2br($message), $headers);
		}
		
		private function send_slot_available_notification($post_id, $date, $name, $email) {
			$service_name = get_the_title($post_id);
			$date_formatted = MPWPB_Global_Function::date_format($date);
			$time_formatted = MPWPB_Global_Function::date_format($date, 'time');
			$site_name = get_bloginfo('name');
			
			$subject = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_waiting_list_email_subject', 'Slot Available for {service_name}');
			$body = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_waiting_list_email_body', "Hello {customer_name},\n\nA slot has become available for {service_name} on {date} at {time}.\n\nPlease book soon as this slot may be taken by someone else.\n\nRegards,\n{site_name}");
			
			// Replace placeholders
			$subject = str_replace(
				['{service_name}', '{date}', '{time}', '{customer_name}', '{site_name}'],
				[$service_name, $date_formatted, $time_formatted, $name, $site_name],
				$subject
			);
			
			$body = str_replace(
				['{service_name}', '{date}', '{time}', '{customer_name}', '{site_name}'],
				[$service_name, $date_formatted, $time_formatted, $name, $site_name],
				$body
			);
			
			$headers = ['Content-Type: text/html; charset=UTF-8'];
			
			wp_mail($email, $subject, nl2br($body), $headers);
		}
	}
	new MPWPB_Waiting_List_Settings();
}