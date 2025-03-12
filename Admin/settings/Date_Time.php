<?php
	/*
   * @Author 		engr.sumonazma@gmail.com
   * Copyright: 	mage-people.com
   */
	if (!defined('ABSPATH')) {
		die;
	} // Cannot access pages directly.
	if (!class_exists('MPWPB_Date_Time_Settings')) {
		class MPWPB_Date_Time_Settings {
			public function __construct() {
				add_action('add_mpwpb_settings_tab_content', [$this, 'date_time_settings'], 10, 1);
				/************************/
				add_action('wp_ajax_get_mpwpb_end_time_slot', array($this, 'get_mpwpb_end_time_slot'));
				add_action('wp_ajax_nopriv_get_mpwpb_end_time_slot', array($this, 'get_mpwpb_end_time_slot'));
				/***/
				add_action('wp_ajax_get_mpwpb_start_break_time', array($this, 'get_mpwpb_start_break_time'));
				add_action('wp_ajax_nopriv_get_mpwpb_start_break_time', array($this, 'get_mpwpb_start_break_time'));
				/***/
				add_action('wp_ajax_get_mpwpb_end_break_time', array($this, 'get_mpwpb_end_break_time'));
				add_action('wp_ajax_nopriv_get_mpwpb_end_break_time', array($this, 'get_mpwpb_end_break_time'));
			}
			public function date_time_settings($post_id) {
				$date_format = MP_Global_Function::date_picker_format();
				$now = date_i18n($date_format, strtotime(current_time('Y-m-d')));
				$date_type = MP_Global_Function::get_post_info($post_id, 'mpwpb_date_type', 'repeated');
				$time_slot = MP_Global_Function::get_post_info($post_id, 'mpwpb_time_slot_length');
				$capacity = MP_Global_Function::get_post_info($post_id, 'mpwpb_capacity_per_session', 1);
				$repeated_start_date = MP_Global_Function::get_post_info($post_id, 'mpwpb_repeated_start_date');
				$hidden_repeated_start_date = $repeated_start_date ? date_i18n('Y-m-d', strtotime($repeated_start_date)) : date_i18n('Y-m-d', strtotime($now)) ;
				$visible_repeated_start_date = $repeated_start_date ? date_i18n($date_format, strtotime($repeated_start_date)) : $now;
				$repeated_after = MP_Global_Function::get_post_info($post_id, 'mpwpb_repeated_after', 1);
				$active_days = MP_Global_Function::get_post_info($post_id, 'mpwpb_active_days', 10);
				?>
                <div class="tabsItem mpwpb_settings_date_time" data-tabs="#mpwpb_settings_date_time">
                    <header>
                        <h2><?php esc_html_e('Date & Time Settings', 'service-booking-manager'); ?></h2>
                        <span><?php MPWPB_Settings::info_text('date_time_desc'); ?></span>
                    </header>
                    <section class="section">
                        <h2><?php esc_html_e('General date time and Schedule settings', 'service-booking-manager'); ?></h2>
                        <span><?php MPWPB_Settings::info_text('general_date_time_desc'); ?></span>
                    </section>
                    <section>
						<div class="date-time-schedule">
							<div class="date-time-container">
								<div class="header">
									<h3><?php esc_html_e('Date time settings','service-booking-manager'); ?></h3>
								</div>
                                <section>
                                    <label>
                                        <p><?php esc_html_e('Date Type', 'service-booking-manager'); ?> <span class="textRequired">&nbsp;*</span></p>
                                        <select class="" name="mpwpb_date_type" data-collapse-target required>
                                            <option disabled selected><?php esc_html_e('Please select ...', 'service-booking-manager'); ?></option>
                                            <option value="particular" data-option-target="#mp_particular" <?php echo esc_attr($date_type == 'particular' ? 'selected' : ''); ?>><?php esc_html_e('Particular', 'service-booking-manager'); ?></option>
                                            <option value="repeated" data-option-target="#mp_repeated" <?php echo esc_attr($date_type == 'repeated' ? 'selected' : ''); ?>><?php esc_html_e('Repeated', 'service-booking-manager'); ?></option>
                                        </select>
                                    </label>
                                </section>
                                <section class="<?php echo esc_attr($date_type == 'particular' ? 'mActive' : ''); ?>" data-collapse="#mp_particular">
                                    <label>
                                        <p><?php esc_html_e('Particular Dates', 'service-booking-manager'); ?> <span class="textRequired">&nbsp;*</span></p>
                                        <div class="mp_settings_area">
                                            <div class="mp_item_insert mp_sortable_area">
                                                <?php
                                                    $particular_date_lists = MP_Global_Function::get_post_info($post_id, 'mpwpb_particular_dates', array());
                                                    if (sizeof($particular_date_lists)) {
                                                        foreach ($particular_date_lists as $particular_date) {
                                                            if ($particular_date) {
                                                                self::particular_date_item('mpwpb_particular_dates[]', $particular_date);
                                                            }
                                                        }
                                                    }
                                                ?>
                                            </div>
                                            <?php MP_Custom_Layout::add_new_button(esc_html__('Add New Particular date', 'service-booking-manager')); ?>
                                            <div class="mp_hidden_content">
                                                <div class="mp_hidden_item">
                                                    <?php self::particular_date_item('mpwpb_particular_dates[]'); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </label>
                                </section>
                                <section style="display: none;" >
                                    <label>
                                        <p><?php esc_html_e('Repeated Start Date', 'service-booking-manager'); ?> <span class="textRequired">&nbsp;*</span></p>
                                        <input type="hidden" name="mpwpb_repeated_start_date" value="<?php echo esc_attr($hidden_repeated_start_date); ?>" required/>
                                        <input type="text" readonly required name="" class="date_type" value="<?php echo esc_attr($visible_repeated_start_date); ?>" placeholder="<?php echo esc_attr($now); ?>" />
                                    </label>
                                </section> 
                                <section style="display: none;">
                                    <label>
                                        <p><?php esc_html_e('Repeated after', 'service-booking-manager'); ?> <span class="textRequired">&nbsp;*</span></p>
                                        <input type="text" name="mpwpb_repeated_after" class="mp_number_validation" value="<?php echo esc_attr($repeated_after); ?>"/>
                                    </label>
                                </section>
                                <section class="<?php echo esc_attr($date_type == 'repeated' ? 'mActive' : ''); ?>" data-collapse="#mp_repeated">
                                    <label>
                                        <p><?php esc_html_e('Maximum advanced day booking', 'service-booking-manager'); ?> <span class="textRequired">&nbsp;*</span></p>
                                        <input type="text" name="mpwpb_active_days" class="mp_number_validation" value="<?php echo esc_attr($active_days); ?>"/>
                                    </label>
                                </section>
                                <section>
                                    <label>
                                        
                                        <p><?php esc_html_e('Time Slot Length', 'service-booking-manager'); ?> <span class="textRequired">&nbsp;*</span></p>
                                        
                                        <select name="mpwpb_time_slot_length">
                                            <option selected disabled><?php esc_html_e('Select time slot Length', 'service-booking-manager'); ?></option>
                                            <option value="10" <?php echo esc_attr($time_slot == 10 ? 'selected' : ''); ?>><?php esc_html_e('10 min', 'service-booking-manager'); ?></option>
                                            <option value="15" <?php echo esc_attr($time_slot == 15 ? 'selected' : ''); ?>><?php esc_html_e('15 min', 'service-booking-manager'); ?></option>
                                            <option value="30" <?php echo esc_attr($time_slot == 30 ? 'selected' : ''); ?>><?php esc_html_e('30 min', 'service-booking-manager'); ?></option>
                                            <option value="60" <?php echo esc_attr($time_slot == 60 ? 'selected' : ''); ?>><?php esc_html_e('1 Hour', 'service-booking-manager'); ?></option>
                                            <option value="120" <?php echo esc_attr($time_slot == 120 ? 'selected' : ''); ?>><?php esc_html_e('2 Hour', 'service-booking-manager'); ?></option>
                                            <option value="180" <?php echo esc_attr($time_slot == 180 ? 'selected' : ''); ?>><?php esc_html_e('3 Hour', 'service-booking-manager'); ?></option>
                                        </select>
                                    </label>
                                </section>
                                <section>
                                    <label>
                                        <p><?php esc_html_e('Capacity per Session', 'service-booking-manager'); ?> <span class="textRequired">&nbsp;*</span></p>
                                        <input class="formControl" name="mpwpb_capacity_per_session" type="number" value="<?php echo esc_attr($capacity); ?>" placeholder="Ex. 25"/>
                                    </label>
                                </section>
							</div>
							<div class="schedule-container">
								<div class="header">
									<h3><?php esc_html_e('Shedule settings', 'service-booking-manager'); ?></h3>
								</div>
                                <table>
                                    <thead>
                                    <tr>
                                        <th style="text-align: left;"><?php esc_html_e('Day', 'service-booking-manager'); ?></th>
                                        <th><?php esc_html_e('Start Time', 'service-booking-manager'); ?></th>
                                        <th><?php esc_html_e('End Time', 'service-booking-manager'); ?></th>
                                        <th colspan="2"><?php esc_html_e('Break Time (Start - End)', 'service-booking-manager'); ?></th>
                                        
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php
                                        $this->time_slot_tr($post_id, 'default');
                                        $days = MP_Global_Function::week_day();
                                        foreach ($days as $key => $day) {
                                            $this->time_slot_tr($post_id, $key);
                                        }
                                    ?>
                                    </tbody>
                                </table>
							</div>
						</div>
					</section>
                    
                    <!-- ================ -->
                    <section class="section">
                        <h2><?php esc_html_e('Offdays and date settings', 'service-booking-manager'); ?></h2>
                        <span><?php esc_html_e('Offdays and date settings', 'service-booking-manager'); ?></span>
                    </section>
                    <section>
                        <label class="label">
                            <div class="groupCheckBox flexWrap">
								<?php
									$off_days = MP_Global_Function::get_post_info($post_id, 'mpwpb_off_days');
									$days = MP_Global_Function::week_day();
									$off_day_array = explode(',', $off_days);
								?>
                                <input type="hidden" name="mpwpb_off_days" value="<?php echo esc_attr($off_days); ?>"/>
								<?php foreach ($days as $key => $day) { ?>
                                    <label class="customCheckboxLabel ">
                                        <input type="checkbox" <?php echo esc_attr(in_array($key, $off_day_array) ? 'checked' : ''); ?> data-checked="<?php echo esc_attr($key); ?>"/>
                                        <span class="customCheckbox"><?php echo esc_html($day); ?></span>
                                    </label>
								<?php } ?>
                            </div>
                        </label>
                    </section>
                    <section>
                        <label class="_dFlex_justifyBetween">
                            <p>
								<?php esc_html_e('Off date', 'service-booking-manager'); ?>
                            </p>
                            <div class="mp_settings_area">
                                <div class="mp_item_insert mp_sortable_area">
									<?php
										$off_day_lists = MP_Global_Function::get_post_info($post_id, 'mpwpb_off_dates', array());
										if (sizeof($off_day_lists) > 0) {
											foreach ($off_day_lists as $off_day) {
												if ($off_day) {
													MPWPB_Date_Time_Settings::particular_date_item('mpwpb_off_dates[]', $off_day);
												}
											}
										}
									?>
                                </div>
								<?php MP_Custom_Layout::add_new_button(esc_html__('Add New Off date', 'service-booking-manager')); ?>
                                <div class="mp_hidden_content">
                                    <div class="mp_hidden_item">
										<?php MPWPB_Date_Time_Settings::particular_date_item('mpwpb_off_dates[]'); ?>
                                    </div>
                                </div>
                            </div>
                        </label>
                    </section>
                </div>
				<?php
			}
			public static function particular_date_item($name, $date = '') {
				$date_format = MP_Global_Function::date_picker_format();
				$now = date_i18n($date_format, strtotime(current_time('Y-m-d')));
				$hidden_date = $date ? date_i18n('Y-m-d', strtotime($date)) : '';
				$visible_date = $date ? date_i18n($date_format, strtotime($date)) : '';
				?>
                <div class="mp_remove_area">
                    <div class="justifyBetween">
                        <label>
                            <input type="hidden" name="<?php echo esc_attr($name); ?>" value="<?php echo esc_attr($hidden_date); ?>"/>
                            <input value="<?php echo esc_attr($visible_date); ?>" class="formControl date_type" placeholder="<?php echo esc_attr($now); ?>"/>
                        </label>
						<?php MP_Custom_Layout::move_remove_button(); ?>
                    </div>
                </div>
				<?php
			}
			public function time_slot_tr($post_id, $day) {
				$start_name = 'mpwpb_' . $day . '_start_time';
				$default_start_time = $day == 'default' ? 10 : '';
				$start_time = MP_Global_Function::get_post_info($post_id, $start_name, $default_start_time);
				$end_name = 'mpwpb_' . $day . '_end_time';
				$default_end_time = $day == 'default' ? 18 : '';
				$end_time = MP_Global_Function::get_post_info($post_id, $end_name, $default_end_time);
				$start_name_break = 'mpwpb_' . $day . '_start_break_time';
				$start_time_break = MP_Global_Function::get_post_info($post_id, $start_name_break);
				?>
                <tr>
                    <td style="text-transform: capitalize;"><?php echo esc_html($day); ?></td>
                    <td class="mpwpb_start_time" data-day-name="<?php echo esc_attr($day); ?>">
						<?php //echo '<pre>'; print_r( $start_time );echo '</pre>'; ?>
                        <label>
                            <select class="formControl" name="<?php echo esc_attr($start_name); ?>">
                                <option value="" <?php echo esc_attr($start_time == '' ? 'selected' : ''); ?>>
									<?php $this->default_text($day); ?>
                                </option>
								<?php $this->time_slot($start_time); ?>
                            </select>
                        </label>
                    </td>
                    <td class="mpwpb_end_time">
						<?php $this->end_time_slot($post_id, $day, $start_time); ?>
                    </td>
                    <td class="mpwpb_start_break_time">
						<?php $this->start_break_time_slot($post_id, $day, $start_time, $end_time) ?>
                    </td>
                    <td class="mpwpb_end_break_time">
						<?php $this->end_break_time_slot($post_id, $day, $start_time_break, $end_time) ?>
                    </td>
                </tr>
				<?php
			}
			public function end_time_slot($post_id, $day, $start_time) {
				$end_name = 'mpwpb_' . $day . '_end_time';
				$default_end_time = $day == 'default' ? 18 : '';
				$end_time = MP_Global_Function::get_post_info($post_id, $end_name, $default_end_time);
				?>
                <label>
                    <select class="formControl " name="<?php echo esc_attr($end_name); ?>">
						<?php if ($start_time == '') { ?>
                            <option value="" selected><?php $this->default_text($day); ?></option>
						<?php } ?>
						<?php $this->time_slot($end_time, $start_time); ?>
                    </select>
                </label>
				<?php
			}
			public function start_break_time_slot($post_id, $day, $start_time, $end_time = '') {
				$start_name_break = 'mpwpb_' . $day . '_start_break_time';
				$start_time_break = MP_Global_Function::get_post_info($post_id, $start_name_break);
				?>
                <label>
                    <select class="formControl" name="<?php echo esc_attr($start_name_break); ?>">
                        <option value="" <?php echo esc_attr(!$start_time_break ? 'selected' : ''); ?>><?php esc_html_e('No Break', 'service-booking-manager'); ?></option>
						<?php $this->time_slot($start_time_break, $start_time, $end_time); ?>
                    </select>
                </label>
				<?php
			}
			public function end_break_time_slot($post_id, $day, $start_time_break, $end_time) {
				$end_name_break = 'mpwpb_' . $day . '_end_break_time';
				$end_time_break = MP_Global_Function::get_post_info($post_id, $end_name_break);
				?>
                <label>
                    <select class="formControl" name="<?php echo esc_attr($end_name_break); ?>">
						<?php if ($start_time_break == '') { ?>
                            <option value="" selected><?php esc_html_e('No Break', 'service-booking-manager'); ?></option>
						<?php } ?>
						<?php $this->time_slot($end_time_break, $start_time_break, $end_time); ?>
                    </select>
                </label>
				<?php
			}
			public function time_slot($time, $stat_time = '', $end_time = '') {
				if ($stat_time >= 0 || $stat_time == '') {
					$time_count = $stat_time == '' ? 0 : $stat_time;
					$end_time = $end_time != '' ? $end_time : 23.5;
					for ($i = $time_count; $i <= $end_time; $i = $i + 0.5) {
						if ($stat_time == 'yes' || $i > $time_count) {
							?>
                            <option value="<?php echo esc_attr($i); ?>" <?php echo esc_attr($time != '' && $time == $i ? 'selected' : ''); ?>><?php echo esc_html(date_i18n('h:i A', $i * 3600)); ?></option>
							<?php
						}
					}
				}
			}
			public function default_text($day) {
				if ($day == 'default') {
					esc_html_e('Please select', 'service-booking-manager');
				} else {
					esc_html_e('Default', 'service-booking-manager');
				}
			}
			/*************************************/
			public function get_mpwpb_end_time_slot() {
				if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'mpwpb_admin_nonce')) {
					wp_send_json_error('Invalid nonce!'); // Prevent unauthorized access
				}
				$post_id = isset($_POST['post_id']) ? sanitize_text_field(wp_unslash($_POST['post_id'])) : '';
				$day = isset($_POST['day_name']) ? sanitize_text_field(wp_unslash($_POST['day_name'])) : '';
				$start_time = isset($_POST['start_time']) ? sanitize_text_field(wp_unslash($_POST['start_time'])) : '';
				$this->end_time_slot($post_id, $day, $start_time);
				die();
			}
			public function get_mpwpb_start_break_time() {
				if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'mpwpb_admin_nonce')) {
					wp_send_json_error('Invalid nonce!'); // Prevent unauthorized access
				}
				$post_id = isset($_POST['post_id']) ? sanitize_text_field(wp_unslash($_POST['post_id'])) : '';
				$day = isset($_POST['day_name']) ? sanitize_text_field(wp_unslash($_POST['day_name'])) : '';
				$start_time = isset($_POST['start_time']) ? sanitize_text_field(wp_unslash($_POST['start_time'])) : '';
				$end_time = isset($_POST['end_time']) ? sanitize_text_field(wp_unslash($_POST['end_time'])) : '';
				$this->start_break_time_slot($post_id, $day, $start_time, $end_time);
				die();
			}
			public function get_mpwpb_end_break_time() {
				if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'mpwpb_admin_nonce')) {
					wp_send_json_error('Invalid nonce!'); // Prevent unauthorized access
				}
				$post_id = isset($_POST['post_id']) ? sanitize_text_field(wp_unslash($_POST['post_id'])) : '';
				$day = isset($_POST['day_name']) ? sanitize_text_field(wp_unslash($_POST['day_name'])) : '';
				$start_time = isset($_POST['start_time']) ? sanitize_text_field(wp_unslash($_POST['start_time'])) : '';
				$end_time = isset($_POST['end_time']) ? sanitize_text_field(wp_unslash($_POST['end_time'])) : '';
				$this->end_break_time_slot($post_id, $day, $start_time, $end_time);
				die();
			}

		}
		new MPWPB_Date_Time_Settings();
	}