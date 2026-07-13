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
                return $all_dates[0];
            }

            public static function display_booking_time( $post_id, $all_dates, $active_date = null ){
                $start_date = $all_dates[0];
                $end_date = end($all_dates);
                $active_date = $active_date ?? self::get_default_active_date( $post_id, $all_dates );
                while (strtotime($start_date) <= strtotime($end_date)) {
                    if( $start_date === $active_date ){
                        $display = 'flex';
                    }else{
                        $display = 'none';
                    }
                    ?>
                    <div class="mpwpb_time_display" id="<?php echo esc_attr($start_date);?>" style="display: <?php echo esc_attr( $display );?>" data-date-filder="<?php echo esc_attr( $start_date );?>">
                        <?php
                        $all_time_slots = MPWPB_Function::get_time_slot( $post_id, $start_date );
                        $happy_hours_badge = class_exists('MPWPB_Happy_Hours_Helper') ? MPWPB_Happy_Hours_Helper::get_badge_label( $post_id ) : '';
                        $happy_hours_rule = $happy_hours_badge !== '' ? MPWPB_Happy_Hours_Helper::get_rule( $post_id ) : null;
                        if (sizeof($all_time_slots) > 0) {
                            foreach ($all_time_slots as $slot) {
                                $available = MPWPB_Function::get_total_available($post_id, $slot );
                                if ($available > 0) {
                                    $is_happy_hour = $happy_hours_rule !== null && MPWPB_Happy_Hours_Helper::time_in_window( $slot, $happy_hours_rule );
                                    ?>
                                    <button type="button" class=" to-book mpwpb_time_btn<?php echo $is_happy_hour ? ' mpwpb-happy-hour-slot' : ''; ?>" data-date="<?php echo esc_attr(MPWPB_Global_Function::date_format($slot, 'full')); ?>" data-radio-check="<?php echo esc_attr($slot); ?>" data-open-icon="fas fa-check" data-close-icon=""<?php if ( $is_happy_hour ) { ?> data-hh-type="<?php echo esc_attr($happy_hours_rule['discount_type']); ?>" data-hh-value="<?php echo esc_attr($happy_hours_rule['discount_value']); ?>"<?php } ?>>
                                        <!-- <span data-icon></span> --><?php echo esc_html(MPWPB_Global_Function::date_format($slot, 'time')); ?>
                                        <?php if ( $is_happy_hour ) { ?>
                                            <span class="mpwpb-happy-hour-badge"><?php echo esc_html($happy_hours_badge); ?></span>
                                        <?php } ?>
                                    </button>
                                <?php } else {
                                    ?>
                                    <button type="button" class="_mpBtn mActive booked"><?php esc_html_e('Booked', 'service-booking-manager'); ?></button>
                                    <?php
                                }
                            }
                        } ?>
                    </div>
                    <?php
                    $start_date = date_i18n('Y-m-d', strtotime($start_date . ' +1 day'));
                }
            }
            public static function display_booking_date( $post_id, $all_dates, $active_date = null ){
                $start_date = $all_dates[0];
                $end_date = end($all_dates);
                $active_date = $active_date ?? self::get_default_active_date( $post_id, $all_dates );

                $today = date_i18n('Y-m-d');
                $tomorrow = date_i18n('Y-m-d', strtotime('+1 day'));

                while (strtotime($start_date) <= strtotime($end_date)) {
                    if( $start_date === $active_date ){
                        $selected = 'mpwpb_get_date_selected';
                    }else{
                        $selected = '';
                    }
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
                    ?>
                    <div class="fdColumn mpwpb_date_time_line">

                        <?php if (!in_array($start_date, $all_dates)) {
                            ?>
                            <div class="_mpBtn_mpDisabled_fullHeight_bgLight mpwpb_get_close_date">
                                <div class="mpwpb_close_date"><?php echo esc_html(MPWPB_Global_Function::date_format($start_date)); ?></div>
                            </div>
                        <?php } elseif ( ! self::has_available_slots( $post_id, $start_date ) ) {
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
                            <div class="_mpBtn_mpDisabled_fullHeight_bgLight mpwpb_get_date_passed">
                                <span class="mptrs_day_with_date"><?php echo esc_html( $day_label ); ?></span>
                                <strong class="mpwpb-date-number"><?php echo esc_html( $day_number ); ?></strong>
                                <span class="mpwpb_date_passed_label"><?php esc_html_e('Passed', 'service-booking-manager'); ?></span>
                            </div>
                        <?php } else { ?>
                            <div class="<?php echo esc_attr( $selected );?> mpwpb_get_date" data-find-time="<?php echo esc_attr( $start_date );?>">
                                <span class="mptrs_day_with_date"><?php echo esc_html( $day_label ); ?></span>
                                <strong class="mpwpb-date-number"><?php echo esc_html( $day_number ); ?></strong>
                            </div>
                        <?php } ?>
                    </div>
                    <?php
                    $start_date = date_i18n('Y-m-d', strtotime($start_date . ' +1 day'));
                }
            }
		}
		new MPWPB_Details_Layout();
	}