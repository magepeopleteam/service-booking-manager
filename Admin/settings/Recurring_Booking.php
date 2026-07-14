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

			add_action('wp_ajax_mpwpb_get_filtered_time_by_date', array($this, 'mpwpb_get_filtered_time_by_date'));
			add_action('wp_ajax_nopriv_mpwpb_get_filtered_time_by_date', array($this, 'mpwpb_get_filtered_time_by_date'));
		}

		public function recurring_booking_settings($post_id) {
			$enable_recurring = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_enable_recurring', 'no');
			$recurring_types = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_recurring_types', array('daily, weekly', 'bi-weekly', 'monthly'));
			$max_recurring_count = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_max_recurring_count', 10);
			$recurring_discount = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_recurring_discount', 0);
            $checked = $enable_recurring == 'yes' ? 'checked' : '';
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
                        <p><?php esc_html_e('Enable Recurring Bookings', 'service-booking-manager'); ?></p>
                        
                        <?php MPWPB_Custom_Layout::switch_button('mpwpb_enable_recurring', $checked); ?>
					</label>
				</section>
				<section>
					<label class="label">
						<p><?php esc_html_e('Recurring Types', 'service-booking-manager'); ?></p>
							
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
						<p><?php esc_html_e('Maximum Recurring Count', 'service-booking-manager'); ?></p>
							
						<input type="number" name="mpwpb_max_recurring_count" value="<?php echo esc_attr($max_recurring_count); ?>" min="2" max="52" />
					</label>
				</section>
				<section>
					<label class="label">
						<p><?php esc_html_e('Recurring Booking Discount (%)', 'service-booking-manager'); ?></p>
						
						<input type="number" name="mpwpb_recurring_discount" value="<?php echo esc_attr($recurring_discount); ?>" min="0" max="100" />
					</label>
				</section>
			</div>
			<?php
		}

		public function mpwpb_get_filtered_time_by_date() {
            if ( isset( $_POST['nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'mpwpb_nonce' ) ) {
                $valid_html = '';
                $post_id = isset( $_POST['post_id'] ) ? sanitize_text_field(wp_unslash( $_POST['post_id'] ) ) : '';
                $target_date = isset( $_POST['dates'] ) ? sanitize_text_field( wp_unslash( $_POST['dates'] ) ) : '';
                $ordered_times = MPWPB_Recurring_Booking::wp_get_order_time_by_dates( $target_date, $post_id );
                $all_time_slots = MPWPB_Recurring_Booking::get_time_slot_on_date( $post_id, $target_date );
                foreach ( $all_time_slots as $time ) {
                    if ( in_array( $time, $ordered_times ) ) {
                        $class = 'mpwpb_selected_datetime_timeslot';
                    } else {
                        $class = 'mpwpb_select_datetime_timeslot';
                    }
                    $timeParts = explode(':', $time );
                    $hour = (int)$timeParts[0];
                    $valid_html .= '<span class="' . $class . '" data-time="' . $hour . '">' . $time . '</span>';
                }

                wp_send_json_success([
                    'dates' => $valid_html,
                    'message' => __('Recurring dates generated successfully', 'service-booking-manager')
                ]);
            }else{
                wp_send_json_error(['message' => __('Security check failed', 'service-booking-manager')]);
            }
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

                $all_future_order = MPWPB_Recurring_Booking::get_all_future_booking_order_date_times( $post_id );
                $off_days_recurring = MPWPB_Global_Function::get_post_info( $post_id, 'mpwpb_off_days' );
                $all_off_dates_recurring = MPWPB_Global_Function::get_post_info( $post_id, 'mpwpb_off_dates', array() );
                $off_dates_recurring = [];
                foreach ($all_off_dates_recurring as $off_date) {
                    $off_dates_recurring[] = date_i18n('Y-m-d', strtotime($off_date));
                }
                $off_days_array = array_map('strtolower', explode(',', $off_days_recurring));

                // Keeps generating candidate dates past any that land on an
                // off-day/off-date/already-booked slot instead of stopping once
                // $recurring_count raw candidates have been produced -- previously
                // an unavailable date in the middle of the sequence just ate one of
                // the requested occurrences instead of being backfilled by another
                // candidate, so asking for 6 occurrences with 2 unavailable produced
                // only 4 real bookable dates. Unavailable candidates are still
                // recorded (and shown as "Already Booked") for transparency, they
                // just don't count toward the requested occurrence total.
                $valid_recurring_dates = $this->generate_recurring_dates_with_availability(
                    $recurring_type, $recurring_count, $dates[0], $selectedRecurringDays,
                    $off_days_array, $off_dates_recurring, $all_future_order
                );

                $html = '';
                $selected_html_right = '';

                if( !empty( $valid_recurring_dates )){
                    foreach ( $valid_recurring_dates as $index => $dates ){
                        $count_li = $index + 1;
                        $date = $dates['date'];
                        $valid = $dates['valid'];

                        $formattedDate = date('F j, Y \a\t g:i A', strtotime($date));
                        $dateOnly = date('F j, Y', strtotime($date));
                        $dayTime = date('l \a\t g:i A', strtotime($date));

                        if( $valid == 1 ){
                            $html .= '<li data-date-time="'.esc_attr($date).'" class="mpwpb_recurring_days">
                                <span class="mpwpb_recurring_number">'.esc_html($count_li).'</span>
                                <div class="mpwpb_recurring_date_text">
                                    <strong>'.esc_html($dateOnly).'</strong>
                                    <span class="mpwpb_recurring_daytime">'.esc_html($dayTime).'</span>
                                </div>
                                <div class="mpwpb_recurring_actions">
                                    <span class="mpwpb_recurring_edit_icon"><i class="fas fa-pen"></i></span>
                                    <span class="mpwpb_recurring_delete_icon"><i class="fas fa-times"></i></span>
                                </div>
                            </li>';

                            $selected_html_right .= '<li class="mpwpd_service_date" data-cart-date-time="'.esc_attr($date).'">'.esc_html($formattedDate).'</li>';
                        }else{
                            $html .= '<li data-date-time="" class="mpwpb_invalid_recurring_days">
                                <span class="mpwpb_recurring_number">'.esc_html($count_li).'</span>
                                <div class="mpwpb_recurring_date_text">
                                    <strong>'.esc_html($dateOnly).'</strong>
                                    <span class="mpwpb_recurring_daytime">'.esc_html($dayTime).' &middot; '.esc_html__('Already Booked', 'service-booking-manager').'</span>
                                </div>
                                <div class="mpwpb_recurring_actions">
                                    <span class="mpwpb_recurring_delete_icon"><i class="fas fa-times"></i></span>
                                </div>
                            </li>';

							// Unavailable candidates are informational only and must not be
							// copied into the cart's selected-date collection.
                        }
                    }
                }

				wp_send_json_success([
					'dates' => $valid_recurring_dates,
					'dates_html' => $html,
					'selected_html' => $selected_html_right,
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
		$enable_recurring = isset($_POST['mpwpb_enable_recurring']) ? sanitize_text_field('yes') : 'no';
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

        /**
         * Generates recurring occurrence candidates and checks each one's
         * availability (off-day/off-date/already-booked) as it goes,
         * continuing to generate further candidates whenever one is
         * unavailable so that exactly $recurring_count *available* dates are
         * always produced (up to a safety cap) -- unavailable candidates are
         * still included in the returned list (marked valid => '0') so the
         * picker can show them as "Already Booked", they just don't count
         * toward the requested occurrence total.
         *
         * @return array[] Each item: ['date' => 'Y-m-d H:i:s', 'valid' => '1'|'0'], sorted chronologically.
         */
        private function generate_recurring_dates_with_availability($recurring_type, $recurring_count, $start_date, $selected_days, $off_days_array, $off_dates_recurring, $all_future_order) {
            $selected_days = array_map('strtolower', $selected_days);
            $start_timestamp = strtotime($start_date);
            $base_time = date('H:i:s', $start_timestamp);

            $results = [];
            $valid_count = 0;
            $index = 0;
            // Safety cap so a service with (almost) every day blocked can't
            // loop indefinitely -- generous enough for any realistic
            // occurrence count (mpwpb_max_recurring_count tops out at 52)
            // plus plenty of gaps.
            $max_candidates = max((int) $recurring_count * 10, 60);

            while ($valid_count < $recurring_count && count($results) < $max_candidates) {
                $dateTime = $this->generate_candidate_date($recurring_type, $index, $start_timestamp, $base_time, $selected_days);
                $index++;
                if ($dateTime === null) {
                    break;
                }

                $date_only = date('Y-m-d', strtotime($dateTime));
                $day_name = strtolower(date('l', strtotime($dateTime)));
                $is_unavailable = in_array($day_name, $off_days_array, true)
                    || in_array($date_only, $off_dates_recurring, true)
                    || in_array($dateTime, $all_future_order, true);

                $results[] = array(
                    'date' => $dateTime,
                    'valid' => $is_unavailable ? '0' : '1',
                );
                if (!$is_unavailable) {
                    $valid_count++;
                }
            }

            usort($results, function ($a, $b) {
                return strtotime($a['date']) <=> strtotime($b['date']);
            });

            return $results;
        }

        /**
         * Returns the Nth (0-based) candidate date in a recurring sequence --
         * same per-type advancement rules the old bulk generator used, just
         * computed one at a time so the caller above can keep asking for
         * "one more" past an unavailable candidate instead of being stuck
         * with a fixed-size batch.
         */
        private function generate_candidate_date($recurring_type, $index, $start_timestamp, $base_time, $selected_days) {
            if (in_array($recurring_type, ['weekly', 'bi-weekly', 'monthly'], true) && !empty($selected_days)) {
                $days_per_cycle = count($selected_days);
                $cycle = intdiv($index, $days_per_cycle);
                $day = $selected_days[$index % $days_per_cycle];

                $interval = $recurring_type === 'bi-weekly' ? $cycle * 2 : $cycle;

                if ($recurring_type === 'monthly') {
                    $month_base = strtotime("+{$interval} month", $start_timestamp);
                    $month_year = date('Y-m', $month_base);
                    $day_date = strtotime("first $day of $month_year");
                } else {
                    $week_base = strtotime("+{$interval} week", $start_timestamp);
                    $day_date = strtotime("next $day", $week_base);
                    if ($cycle === 0 && date('D', $start_timestamp) === ucfirst($day)) {
                        $day_date = $start_timestamp;
                    }
                }
                $day_time = strtotime($base_time, $day_date);

                if ($day_time < $start_timestamp) {
                    // Shouldn't normally happen given the "next $day"/first-of-
                    // month logic above, but never emit a date before the
                    // requested start -- ask for the next candidate instead.
                    return $this->generate_candidate_date($recurring_type, $index + 1, $start_timestamp, $base_time, $selected_days);
                }

                return date('Y-m-d H:i:s', $day_time);
            }

            // Simple sequential types (daily, or weekly/bi-weekly/monthly
            // without specific selected days) -- advance from the start date
            // $index times.
            $current_date = $start_timestamp;
            for ($step = 0; $step < $index; $step++) {
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
                        return null;
                }
            }
            return date('Y-m-d H:i:s', $current_date);
        }




    }
	new MPWPB_Recurring_Booking_Settings();
}
