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

            public static function display_booking_time( $post_id, $all_dates ){
                $start_date = $all_dates[0];
                $end_date = end($all_dates);
                $start = 0;
                while (strtotime($start_date) <= strtotime($end_date)) {
                    if( $start === 0 ){
                        $display = 'flex';
                    }else{
                        $display = 'none';
                    }
                    ?>
                    <div class="mpwpb_time_display" id="<?php echo esc_attr($start_date);?>" style="display: <?php echo esc_attr( $display );?>" data-date-filder="<?php echo esc_attr( $start_date );?>">
                        <?php
                        $all_time_slots = MPWPB_Function::get_time_slot( $post_id, $start_date );
                        if (sizeof($all_time_slots) > 0) {
                            foreach ($all_time_slots as $slot) {
                                $available = MPWPB_Function::get_total_available($post_id, $slot );
                                if ($available > 0) {
                                    ?>
                                    <button type="button" class=" to-book mpwpb_time_btn" data-date="<?php echo esc_attr(MPWPB_Global_Function::date_format($slot, 'full')); ?>" data-radio-check="<?php echo esc_attr($slot); ?>" data-open-icon="fas fa-check" data-close-icon="">
                                        <!-- <span data-icon></span> --><?php echo esc_html(date_i18n('h:i A', strtotime($slot))); ?>
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
                    $start++;
                }
            }
            public static function display_booking_date( $post_id, $all_dates ){
                $start_date = $all_dates[0];
                $end_date = end($all_dates);

                $loop_start = 0;
                while (strtotime($start_date) <= strtotime($end_date)) {
                    if( $loop_start === 0 ){
                        $selected = 'mpwpb_get_date_selected';
                    }else{
                        $selected = '';
                    }
                    ?>
                    <div class="fdColumn mpwpb_date_time_line">

                        <?php if (!in_array($start_date, $all_dates)) {
                            ?>
                            <div class="_mpBtn_mpDisabled_fullHeight_bgLight mpwpb_get_close_date">
                                <div class="mpwpb_close_date"><?php echo esc_html(MPWPB_Global_Function::date_format($start_date)); ?></div>
                            </div>
                        <?php } else {
                            $day = date('l', strtotime( $start_date ) );
                            ?>
                            <div class="<?php echo esc_attr( $selected );?> mpwpb_get_date" data-find-time="<?php echo esc_attr( $start_date );?>">
                                <strong><?php echo esc_html(MPWPB_Global_Function::date_format($start_date)).'</br> <span class="mptrs_day_with_date">'; echo esc_attr( $day ).'</span>';?></strong>
                            </div>
                        <?php } ?>
                    </div>
                    <?php
                    $start_date = date_i18n('Y-m-d', strtotime($start_date . ' +1 day'));
                    $loop_start++;
                }
            }
		}
		new MPWPB_Details_Layout();
	}