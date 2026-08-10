<?php
	/*
   * @Author 		engr.sumonazma@gmail.com
   * Copyright: 	mage-people.com
   */
	if ( ! defined( 'ABSPATH' ) ) {
		die;
	} // Cannot access pages directly.
	if ( ! class_exists( 'MPWPB_Details_Layout' ) ) {
		class MPWPB_Details_Layout {
			public function __construct() {
				/**************/
			}

            /**
             * True if $date has at least one time slot that's both still in
             * the future (MPWPB_Function::get_time_slot() drops anything
             * before "now" + buffer) and not fully booked.
             */
            public static function has_available_slots( $post_id, $date ) {
                foreach ( MPWPB_Function::get_time_slot( $post_id, $date ) as $slot ) {
                    if ( MPWPB_Function::get_total_available( $post_id, $slot ) > 0 ) {
                        return true;
                    }
                }
                return false;
            }

			public static function has_waiting_list_slots( $post_id, $date ) {
				return MPWPB_Global_Function::get_post_info( $post_id, 'mpwpb_enable_waiting_list', 'no' ) === 'yes'
					&& !empty( MPWPB_Function::get_time_slot( $post_id, $date ) );
			}

            /**
             * The date to default-select/show time slots for isn't
             * necessarily $all_dates[0] -- if that date's slots have all
             * already passed today or are fully booked, defaulting to it
             * left the date button marked "selected" with an empty,
             * button-less time panel underneath: looks broken even though
             * nothing actually failed. Returns the first date in the range
             * that has at least one bookable slot; falls back to
             * $all_dates[0] if none do, so there's still a sensible default.
             */
            public static function get_default_active_date( $post_id, $all_dates ) {
                foreach ( $all_dates as $date ) {
                    if ( self::has_available_slots( $post_id, $date ) ) {
                        return $date;
                    }
                }
				return $all_dates[0] ?? '';
            }

            public static function display_booking_time( $post_id, $all_dates, $active_date = null ){
				if ( empty( $all_dates ) ) {
					return;
				}
                $active_date = $active_date ?? self::get_default_active_date( $post_id, $all_dates );
                foreach ( $all_dates as $start_date ) {
                    if( $start_date === $active_date ){
                        $display = 'flex';
                    }else{
                        $display = 'none';
                    }
                    ?>
                    <div class="mpwpb_time_display" id="<?php echo esc_attr($start_date);?>" style="display: <?php echo esc_attr( $display );?>" data-date-filder="<?php echo esc_attr( $start_date );?>">
                        <?php
                        $all_time_slots = MPWPB_Function::get_time_slot( $post_id, $start_date );
                        // data-date feeds the wizard's "Selected Date & Time" summary
                        // (mpwpb_registration.js) -- carry the slot's end time so the
                        // customer sees the whole window, not just when it starts.
                        $slot_minutes = MPWPB_Function::get_slot_length( $post_id );
                        $happy_hours_badge = class_exists('MPWPB_Happy_Hours_Helper') ? MPWPB_Happy_Hours_Helper::get_badge_label( $post_id ) : '';
                        $happy_hours_rule = $happy_hours_badge !== '' ? MPWPB_Happy_Hours_Helper::get_rule( $post_id ) : null;
                        if (sizeof($all_time_slots) > 0) {
                            foreach ($all_time_slots as $slot) {
                                $available = MPWPB_Function::get_total_available($post_id, $slot );
                                if ($available > 0) {
                                    $is_happy_hour = $happy_hours_rule !== null && MPWPB_Happy_Hours_Helper::time_in_window( $slot, $happy_hours_rule );
                                    ?>
                                    <button type="button" class=" to-book mpwpb_time_btn<?php echo $is_happy_hour ? ' mpwpb-happy-hour-slot' : ''; ?>" data-date="<?php echo esc_attr(MPWPB_Global_Function::date_format($slot, 'date') . ' ' . MPWPB_Function::format_slot_time_range($slot, $slot_minutes)); ?>" data-radio-check="<?php echo esc_attr($slot); ?>" data-open-icon="fas fa-check" data-close-icon=""<?php if ( $is_happy_hour ) { ?> data-hh-type="<?php echo esc_attr($happy_hours_rule['discount_type']); ?>" data-hh-value="<?php echo esc_attr($happy_hours_rule['discount_value']); ?>"<?php } ?>>
                                        <!-- <span data-icon></span> --><?php echo esc_html(MPWPB_Global_Function::date_format($slot, 'time')); ?>
                                        <?php if ( $is_happy_hour ) { ?>
                                            <span class="mpwpb-happy-hour-badge"><?php echo esc_html($happy_hours_badge); ?></span>
                                        <?php } ?>
                                    </button>
								<?php } elseif ( MPWPB_Global_Function::get_post_info( $post_id, 'mpwpb_enable_waiting_list', 'no' ) === 'yes' ) {
									?>
									<button type="button" class="_mpBtn waiting-list" data-slot="<?php echo esc_attr( $slot ); ?>" data-post-id="<?php echo esc_attr( $post_id ); ?>"><?php esc_html_e('Join Waiting List', 'service-booking-manager'); ?></button>
									<?php
								} else {
                                    ?>
                                    <button type="button" class="_mpBtn mActive booked"><?php esc_html_e('Booked', 'service-booking-manager'); ?></button>
                                    <?php
                                }
                            }
                        } ?>
                    </div>
                    <?php
                }
            }
            /**
             * Renders the date picker.
             *
             * Two layouts, chosen by the "Date Picker Layout" global setting:
             *
             *  - 'slider'   (default, unchanged): one card per calendar day in
             *               a single flow, paged 8 at a time by the prev/next
             *               arrows. Fine for a short booking window, painful
             *               once the window is long -- a 90-day window is 12
             *               pages of clicking to reach the far end.
             *  - 'calendar': the same day cards laid out as real month grids,
             *               one month visible at a time with its name and year
             *               in a header and the prev/next arrows stepping whole
             *               months. Also fixes the month only ever being
             *               readable on closed days, since open days showed the
             *               weekday and day-of-month but never the month.
             *
             * Both layouts emit identical day-cell markup, so the click
             * handling, selected state and disabled states in
             * mpwpb_registration.js are shared and unchanged.
             */
            public static function display_booking_date( $post_id, $all_dates, $active_date = null ){
				if ( empty( $all_dates ) ) {
					echo '<p class="mpwpb-no-booking-dates">' . esc_html__('No booking dates are currently available.', 'service-booking-manager') . '</p>';
					return;
				}
                $active_date = $active_date ?? self::get_default_active_date( $post_id, $all_dates );
                if ( self::get_date_picker_layout() === 'calendar' ) {
                    self::render_date_calendar( $post_id, $all_dates, $active_date );
                    return;
                }
                $start_date = $all_dates[0];
                $end_date = end($all_dates);
                while (strtotime($start_date) <= strtotime($end_date)) {
                    self::render_date_cell( $post_id, $all_dates, $start_date, $active_date );
                    $start_date = date_i18n('Y-m-d', strtotime($start_date . ' +1 day'));
                }
            }

            /**
             * 'slider' (default -- the historical layout) or 'calendar'.
             */
            public static function get_date_picker_layout() {
                $layout = MPWPB_Global_Function::get_settings( 'mpwpb_global_settings', 'date_picker_layout', 'slider' );
                return $layout === 'calendar' ? 'calendar' : 'slider';
            }

            /**
             * One day of the picker. Shared by both layouts so a day looks and
             * behaves the same either way:
             *
             *  - closed  (not in $all_dates -- an off day or off date),
             *  - passed  (open, but every slot is behind "now" or fully booked),
             *  - bookable (clickable; carries data-find-time, which is what
             *    mpwpb_registration.js binds the time-panel switch to).
             */
            private static function render_date_cell( $post_id, $all_dates, $start_date, $active_date ) {
                $today = date_i18n('Y-m-d');
                $tomorrow = date_i18n('Y-m-d', strtotime('+1 day'));
                $selected = $start_date === $active_date ? 'mpwpb_get_date_selected' : '';
                // "Today"/"Tomorrow" read clearer than the full date +
                // weekday name in a compact card -- falls back to the
                // short weekday (Wed, Thu...) beyond that, with the
                // day-of-month as the big number.
                if ( $start_date === $today ) {
                    $day_label = esc_html__('Today', 'service-booking-manager');
                } elseif ( $start_date === $tomorrow ) {
                    $day_label = esc_html__('Tomorrow', 'service-booking-manager');
                } else {
                    $day_label = date_i18n('D', strtotime($start_date));
                }
                $day_number = date_i18n('j', strtotime($start_date));
                // The full date is always one hover/screen-reader away, in both
                // layouts -- the compact card itself has no room for the month.
                $full_date = MPWPB_Global_Function::date_format($start_date);
                ?>
                <div class="fdColumn mpwpb_date_time_line" data-date="<?php echo esc_attr( $start_date ); ?>">
                    <?php if (!in_array($start_date, $all_dates)) { ?>
                        <div class="_mpBtn_mpDisabled_fullHeight_bgLight mpwpb_get_close_date" title="<?php echo esc_attr( $full_date ); ?>">
                            <span class="mptrs_day_with_date"><?php echo esc_html( $day_label ); ?></span>
                            <strong class="mpwpb-date-number"><?php echo esc_html( $day_number ); ?></strong>
                            <span class="mpwpb_close_date"><?php esc_html_e('Closed', 'service-booking-manager'); ?></span>
                        </div>
					<?php } elseif ( ! self::has_available_slots( $post_id, $start_date ) && ! self::has_waiting_list_slots( $post_id, $start_date ) ) {
                        // An open day (it's in $all_dates), but every one
                        // of its slots is already gone -- either passed
                        // (typically today, once its cutoff time is
                        // behind "now") or fully booked. Shown (not
                        // silently dropped from the calendar) but
                        // disabled instead of clickable, no
                        // "mpwpb_get_date"/data-find-time so the click
                        // handler in mpwpb_registration.js never binds
                        // to it and it can't end up selected.
                        ?>
                        <div class="_mpBtn_mpDisabled_fullHeight_bgLight mpwpb_get_date_passed" title="<?php echo esc_attr( $full_date ); ?>">
                            <span class="mptrs_day_with_date"><?php echo esc_html( $day_label ); ?></span>
                            <strong class="mpwpb-date-number"><?php echo esc_html( $day_number ); ?></strong>
                            <span class="mpwpb_date_passed_label"><?php esc_html_e('Passed', 'service-booking-manager'); ?></span>
                        </div>
                    <?php } else { ?>
                        <div class="<?php echo esc_attr( $selected );?> mpwpb_get_date" data-find-time="<?php echo esc_attr( $start_date );?>" title="<?php echo esc_attr( $full_date ); ?>">
                            <span class="mptrs_day_with_date"><?php echo esc_html( $day_label ); ?></span>
                            <strong class="mpwpb-date-number"><?php echo esc_html( $day_number ); ?></strong>
                        </div>
                    <?php } ?>
                </div>
                <?php
            }

            /**
             * Month-grid layout: one .mpwpb-date-calendar-month block per month
             * touched by the booking window, each a real 7-column calendar with
             * leading blanks so every day sits under its weekday column. Weeks
             * start on the day configured in Settings > General (start_of_week),
             * so the grid matches the rest of the site.
             *
             * All months are rendered server-side and paged client-side
             * (initDateCalendarPagination() in mpwpb-booking-tree.js) -- no
             * extra AJAX round trip per month, and the whole window stays in
             * the DOM for the existing selection logic to work against.
             */
            private static function render_date_calendar( $post_id, $all_dates, $active_date ) {
                $range_start = $all_dates[0];
                $range_end   = end( $all_dates );
                $start_of_week = (int) get_option( 'start_of_week', 1 );
                $weekday_labels = array();
                for ( $i = 0; $i < 7; $i ++ ) {
                    // 1970-01-04 was a Sunday, so this walks Sunday..Saturday
                    // from whichever weekday the site starts its weeks on.
                    $weekday_labels[] = date_i18n( 'D', strtotime( '1970-01-04 +' . ( ( $start_of_week + $i ) % 7 ) . ' day' ) );
                }
                // date() not date_i18n() for anything that is then compared or
                // cast: date_i18n() localises digits in some locales, which turns
                // arithmetic on "t"/"w" into garbage. date_i18n() is used below
                // only where the value is printed for a human to read.
                $month_cursor = date( 'Y-m-01', strtotime( $range_start ) );
                $last_month   = date( 'Y-m-01', strtotime( $range_end ) );
                while ( strtotime( $month_cursor ) <= strtotime( $last_month ) ) {
                    $month_ts    = strtotime( $month_cursor );
                    $days_in_month = (int) date( 't', $month_ts );
                    $lead_blanks = ( (int) date( 'w', $month_ts ) - $start_of_week + 7 ) % 7;
                    ?>
                    <div class="mpwpb-date-calendar-month" data-month="<?php echo esc_attr( date( 'Y-m', $month_ts ) ); ?>">
                        <div class="mpwpb-date-month-label"><?php echo esc_html( date_i18n( 'F Y', $month_ts ) ); ?></div>
                        <div class="mpwpb-date-weekday-row">
                            <?php foreach ( $weekday_labels as $weekday_label ) { ?>
                                <span><?php echo esc_html( $weekday_label ); ?></span>
                            <?php } ?>
                        </div>
                        <div class="mpwpb-date-month-days">
                            <?php
                                for ( $i = 0; $i < $lead_blanks; $i ++ ) {
                                    echo '<div class="mpwpb-date-blank" aria-hidden="true"></div>';
                                }
                                for ( $day = 1; $day <= $days_in_month; $day ++ ) {
                                    $date = date( 'Y-m-d', strtotime( date( 'Y-m', $month_ts ) . '-' . $day ) );
                                    // Days of this month that fall outside the
                                    // booking window are placeholders, not
                                    // "closed" days -- the service simply isn't
                                    // open for booking that far out (or that far
                                    // back), which is a different thing to say.
                                    if ( strtotime( $date ) < strtotime( $range_start ) || strtotime( $date ) > strtotime( $range_end ) ) {
                                        echo '<div class="mpwpb-date-blank mpwpb-date-out-of-range" aria-hidden="true"><span>' . esc_html( $day ) . '</span></div>';
                                        continue;
                                    }
                                    self::render_date_cell( $post_id, $all_dates, $date, $active_date );
                                }
                            ?>
                        </div>
                    </div>
                    <?php
                    $month_cursor = date( 'Y-m-01', strtotime( $month_cursor . ' +1 month' ) );
                }
            }
		}
		new MPWPB_Details_Layout();
	}
