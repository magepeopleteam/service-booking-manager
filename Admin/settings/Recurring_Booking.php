<?php
/*
* @Author 		MagePeople Team
* Copyright: 	mage-people.com
*/
if (!defined('ABSPATH')) {
	die;
} // Cannot access pages directly.
if (!class_exists('MPWPB_Recurring_Booking_Settings')) {
	class MPWPB_Recurring_Booking_Settings {
		public function __construct() {
			add_action('add_mpwpb_settings_tab_content', [$this, 'recurring_booking_settings'], 10, 1);
			add_action('mpwpb_settings_save', array($this, 'save_recurring_booking_settings'), 10, 1);
			add_action('wp_ajax_mpwpb_save_recurring_booking', array($this, 'save_recurring_booking'));
			add_action('wp_ajax_nopriv_mpwpb_save_recurring_booking', array($this, 'save_recurring_booking'));
		}

		public function recurring_booking_settings($post_id) {
			$enable_recurring = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_enable_recurring', 'no');
			$recurring_types = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_recurring_types', array('daily, weekly', 'bi-weekly', 'monthly'));
			$max_recurring_count = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_max_recurring_count', 10);
			$recurring_discount = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_recurring_discount', 0);
			?>
			<div class="tabsItem" data-tabs="#mpwpb_recurring_booking">
				<header>
					<h2><?php esc_html_e('Recurring Booking Settings', 'service-booking-manager'); ?></h2>
					<span><?php esc_html_e('Configure recurring booking options for this service', 'service-booking-manager'); ?></span>
				</header>
				<section class="section">
					<h2><?php esc_html_e('Recurring Booking Settings', 'service-booking-manager'); ?></h2>
					<span><?php esc_html_e('Configure recurring booking options for this service', 'service-booking-manager'); ?></span>
				</section>
				<section>
					<label class="label">
						<div>
							<p><?php esc_html_e('Enable Recurring Bookings', 'service-booking-manager'); ?></p>
							<span><?php esc_html_e('Allow customers to book recurring appointments', 'service-booking-manager'); ?></span>
						</div>
						<div class="customCheckboxLabel">
							<select name="mpwpb_enable_recurring">
								<option value="yes" <?php echo esc_attr($enable_recurring == 'yes' ? 'selected' : ''); ?>><?php esc_html_e('Yes', 'service-booking-manager'); ?></option>
								<option value="no" <?php echo esc_attr($enable_recurring == 'no' ? 'selected' : ''); ?>><?php esc_html_e('No', 'service-booking-manager'); ?></option>
							</select>
						</div>
					</label>
				</section>
				<section>
					<label class="label">
						<div>
							<p><?php esc_html_e('Recurring Types', 'service-booking-manager'); ?></p>
							<span><?php esc_html_e('Select available recurring booking types', 'service-booking-manager'); ?></span>
						</div>
						<div class="groupCheckBox flexWrap">
							<label class="customCheckboxLabel">
								<input type="checkbox" name="mpwpb_recurring_types[]" value="daily" <?php echo esc_attr(in_array('daily', $recurring_types) ? 'checked' : ''); ?> />
								<span class="customCheckbox"><?php esc_html_e('Daily', 'service-booking-manager'); ?></span>
							</label>
							<label class="customCheckboxLabel">
								<input type="checkbox" name="mpwpb_recurring_types[]" value="weekly" <?php echo esc_attr(in_array('weekly', $recurring_types) ? 'checked' : ''); ?> />
								<span class="customCheckbox"><?php esc_html_e('Weekly', 'service-booking-manager'); ?></span>
							</label>
							<label class="customCheckboxLabel">
								<input type="checkbox" name="mpwpb_recurring_types[]" value="bi-weekly" <?php echo esc_attr(in_array('bi-weekly', $recurring_types) ? 'checked' : ''); ?> />
								<span class="customCheckbox"><?php esc_html_e('Bi-Weekly', 'service-booking-manager'); ?></span>
							</label>
							<label class="customCheckboxLabel">
								<input type="checkbox" name="mpwpb_recurring_types[]" value="monthly" <?php echo esc_attr(in_array('monthly', $recurring_types) ? 'checked' : ''); ?> />
								<span class="customCheckbox"><?php esc_html_e('Monthly', 'service-booking-manager'); ?></span>
							</label>
						</div>
					</label>
				</section>
				<section>
					<label class="label">
						<div>
							<p><?php esc_html_e('Maximum Recurring Count', 'service-booking-manager'); ?></p>
							<span><?php esc_html_e('Maximum number of recurring bookings allowed', 'service-booking-manager'); ?></span>
						</div>
						<input type="number" name="mpwpb_max_recurring_count" value="<?php echo esc_attr($max_recurring_count); ?>" min="2" max="52" />
					</label>
				</section>
				<section>
					<label class="label">
						<div>
							<p><?php esc_html_e('Recurring Booking Discount (%)', 'service-booking-manager'); ?></p>
							<span><?php esc_html_e('Discount percentage for recurring bookings', 'service-booking-manager'); ?></span>
						</div>
						<input type="number" name="mpwpb_recurring_discount" value="<?php echo esc_attr($recurring_discount); ?>" min="0" max="100" />
					</label>
				</section>
			</div>
			<?php
		}

		public function save_recurring_booking() {
			if (isset($_POST['nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'mpwpb_nonce')) {
				$post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
				$recurring_type = isset($_POST['recurring_type']) ? sanitize_text_field(wp_unslash($_POST['recurring_type'])) : '';
				$recurring_count = isset($_POST['recurring_count']) ? absint($_POST['recurring_count']) : 0;
				$dates = isset($_POST['dates']) ? array_map('sanitize_text_field', wp_unslash($_POST['dates'])) : [];
                $selectedRecurringDays = isset($_POST['selectedRecurringDays']) ? array_map('sanitize_text_field', wp_unslash( $_POST['selectedRecurringDays'] ) ) : [];
				// Validate the data
				if (!$post_id || !$recurring_type || $recurring_count < 2 || empty($dates)) {
					wp_send_json_error(['message' => __('Invalid data provided', 'service-booking-manager')]);
					return;
				}
				
				// Return the dates for the recurring bookings
				$recurring_dates = $this->generate_recurring_dates($recurring_type, $recurring_count, $dates[0], $selectedRecurringDays );
				
				wp_send_json_success([
					'dates' => $recurring_dates,
					'message' => __('Recurring dates generated successfully', 'service-booking-manager')
				]);
			} else {
				wp_send_json_error(['message' => __('Security check failed', 'service-booking-manager')]);
			}
		}
		/**
         * Save recurring booking settings
         * 
         * @param int $post_id
         */
        public function save_recurring_booking_settings($post_id) {
		if (!isset($_POST['mpwpb_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['mpwpb_nonce'])), 'mpwpb_nonce')) {
		    return;
		}
		
		// Save enable recurring setting
		$enable_recurring = isset($_POST['mpwpb_enable_recurring']) ? sanitize_text_field(wp_unslash($_POST['mpwpb_enable_recurring'])) : 'no';
		update_post_meta($post_id, 'mpwpb_enable_recurring', $enable_recurring);
		
		// Save recurring types
		$recurring_types = isset($_POST['mpwpb_recurring_types']) ? array_map('sanitize_text_field', wp_unslash($_POST['mpwpb_recurring_types'])) : array('weekly', 'bi-weekly', 'monthly');
		update_post_meta($post_id, 'mpwpb_recurring_types', $recurring_types);
		
		// Save max recurring count
		$max_recurring_count = isset($_POST['mpwpb_max_recurring_count']) ? absint($_POST['mpwpb_max_recurring_count']) : 10;
		update_post_meta($post_id, 'mpwpb_max_recurring_count', $max_recurring_count);
		
		// Save recurring discount
		$recurring_discount = isset($_POST['mpwpb_recurring_discount']) ? absint($_POST['mpwpb_recurring_discount']) : 0;
		update_post_meta($post_id, 'mpwpb_recurring_discount', $recurring_discount);
	    }
	
		
		private function generate_recurring_dates_old($recurring_type, $recurring_count, $start_date) {
			$dates = [];
			$dates[] = $start_date; // Add the initial date
			
			$current_date = strtotime($start_date);
			
			for ($i = 1; $i < $recurring_count; $i++) {
				switch ($recurring_type) {
                    case 'daily':
                        $current_date = strtotime('+1 day', $current_date);
                        break;
					case 'weekly':
						$current_date = strtotime('+1 week', $current_date);
						break;
					case 'bi-weekly':
						$current_date = strtotime('+2 weeks', $current_date);
						break;
					case 'monthly':
						$current_date = strtotime('+1 month', $current_date);
						break;
					default:
						break;
				}
				
				$dates[] = date('Y-m-d H:i:s', $current_date);
			}

			return $dates;
		}

        private function generate_recurring_dates_y($recurring_type, $recurring_count, $start_date, $selected_days = []) {

            $selected_days = [ 'mon', 'fri' ];
            $dates = [];

            $start_timestamp = strtotime($start_date);
            $base_time = date('H:i:s', $start_timestamp); // preserve time

            if ($recurring_type === 'weekly' && !empty($selected_days)) {
                // Normalize selected days to lowercase 3-letter codes
                $selected_days = array_map('strtolower', $selected_days);

                for ($week = 0; $week < $recurring_count; $week++) {
                    foreach ($selected_days as $day) {
                        // Get date of this day in current week
                        $day_date = strtotime("next $day", strtotime("+$week week", $start_timestamp));

                        // Only include dates after or equal to the start date
                        if ($day_date >= $start_timestamp) {
                            $dates[] = date('Y-m-d', $day_date) . ' ' . $base_time;
                        }
                    }
                }

            } else {
                // Default recurring behavior (daily, bi-weekly, monthly)
                $dates[] = date('Y-m-d H:i:s', $start_timestamp); // include start date

                $current_date = $start_timestamp;

                for ($i = 1; $i < $recurring_count; $i++) {
                    switch ($recurring_type) {
                        case 'daily':
                            $current_date = strtotime('+1 day', $current_date);
                            break;
                        case 'bi-weekly':
                            $current_date = strtotime('+2 weeks', $current_date);
                            break;
                        case 'monthly':
                            $current_date = strtotime('+1 month', $current_date);
                            break;
                        default:
                            break;
                    }

                    $dates[] = date('Y-m-d H:i:s', $current_date);
                }
            }

            return $dates;
        }
        private function generate_recurring_dates($recurring_type, $recurring_count, $start_date, $selected_days = []) {
//            $selected_days = [ 'mon', 'fri' ];
            $dates = [];
            $start_timestamp = strtotime($start_date);
            $base_time = date('H:i:s', $start_timestamp); // keep time part
            $selected_days = array_map('strtolower', $selected_days);

            if (in_array($recurring_type, ['weekly', 'bi-weekly', 'monthly']) && !empty($selected_days)) {
                for ($i = 0; $i < $recurring_count; $i++) {
                    $interval = 0;

                    // Set number of weeks/months to jump per iteration
                    switch ($recurring_type) {
                        case 'weekly':
                            $interval = $i; // each week
                            break;
                        case 'bi-weekly':
                            $interval = $i * 2; // every 2 weeks
                            break;
                        case 'monthly':
                            $interval = $i; // we’ll handle month-based below
                            break;
                    }

                    foreach ($selected_days as $day) {
                        if ($recurring_type === 'monthly') {
                            // For monthly, find this weekday in the Nth month
                            $month_base = strtotime("+{$interval} month", $start_timestamp);
                            $month_year = date('Y-m', $month_base);

                            // Find the weekday in that month (first occurrence after month start)
                            $day_date = strtotime("first $day of $month_year");
                            $day_time = strtotime($base_time, $day_date);
                        } else {
                            // For weekly/bi-weekly, get specific day after interval weeks
                            $week_base = strtotime("+{$interval} week", $start_timestamp);
                            $day_date = strtotime("next $day", $week_base);

                            // Ensure we don't skip the exact start day
                            if (date('D', $start_timestamp) === ucfirst($day) && $i === 0) {
                                $day_date = $start_timestamp;
                            }

                            $day_time = strtotime($base_time, $day_date);
                        }

                        if ($day_time >= $start_timestamp) {
                            $dates[] = date('Y-m-d H:i:s', $day_time);
                        }
                    }
                }
            } else {
                // For daily or default cases
                $dates[] = date('Y-m-d H:i:s', $start_timestamp); // Add start date
                $current_date = $start_timestamp;

                for ($i = 1; $i < $recurring_count; $i++) {
                    switch ($recurring_type) {
                        case 'daily':
                            $current_date = strtotime('+1 day', $current_date);
                            break;
                        case 'bi-weekly':
                            $current_date = strtotime('+2 weeks', $current_date);
                            break;
                        case 'monthly':
                            $current_date = strtotime('+1 month', $current_date);
                            break;
                    }
                    $dates[] = date('Y-m-d H:i:s', $current_date);
                }
            }

            // Sort and return
            sort($dates);
            return $dates;
        }




    }
	new MPWPB_Recurring_Booking_Settings();
}