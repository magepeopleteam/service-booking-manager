<?php
	/*
	 * Modern-only "Date & Time" section for the Availability step of the
	 * modern service editor. Reuses the EXISTING classic field names, AJAX
	 * cascade (get_mpwpb_end_time_slot / start_break_time / end_break_time)
	 * and the shared save handler (MPWPB_Settings::save_settings /
	 * save_schedule) unchanged — this class only renders a fresh card-based
	 * layout around the same inputs. The classic tabbed UI
	 * (MPWPB_Date_Time_Settings::date_time_settings) is untouched and still
	 * used as-is in Classic mode.
	 *
	 * Two of the classic class's methods are reused directly (via a
	 * reflection instance, so its constructor's AJAX hooks aren't
	 * re-registered):
	 *  - time_slot_tr() for each weekly schedule row, so the start/end/break
	 *    time <select> cascade (and its data-day-name AJAX wiring) is 100%
	 *    identical to Classic.
	 *  - particular_date_item() (static) for each "Particular Dates" /
	 *    "Special Dates & Holidays" row, so the existing add/remove/sort/
	 *    datepicker JS (mp_add_item, mpwpb_item_remove, mpwpb_sortable_button,
	 *    .date_type datepicker) keeps working unchanged.
	 */
	if (!defined('ABSPATH')) {
		die;
	} // Cannot access pages directly.
	if (!class_exists('MPWPB_Date_Time_Modern')) {
		class MPWPB_Date_Time_Modern {
			/** Reflection-built instance (no constructor side effects) of the classic renderer. */
			private function classic_instance() {
				if (!class_exists('MPWPB_Date_Time_Settings')) {
					return null;
				}
				try {
					$ref = new ReflectionClass('MPWPB_Date_Time_Settings');
					return $ref->newInstanceWithoutConstructor();
				} catch (\ReflectionException $e) {
					return null;
				}
			}

			/** One "Particular Dates" / "Special Dates" row, wrapped as a card. */
			private function date_card($name, $date, $sub_label) {
				?>
				<div class="mpwpb-dtm__date-card">
					<span class="dashicons dashicons-calendar-alt mpwpb-dtm__date-card-icon"></span>
					<?php MPWPB_Date_Time_Settings::particular_date_item($name, $date); ?>
					<p class="mpwpb-dtm__date-card-sub"><?php echo esc_html($sub_label); ?></p>
				</div>
				<?php
			}

			public function render($post_id) {
				$classic = $this->classic_instance();
				if (!$classic) {
					return;
				}

				$date_type = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_date_type', 'repeated');
				$time_slot = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_time_slot_length');
				$capacity = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_capacity_per_session', 1);
				$repeated_start_date = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_repeated_start_date');
				$hidden_repeated_start_date = $repeated_start_date ? date_i18n('Y-m-d', strtotime($repeated_start_date)) : date_i18n('Y-m-d', strtotime(current_time('Y-m-d')));
				$repeated_after = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_repeated_after', 1);
				$active_days = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_active_days', 10);
				$particular_date_lists = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_particular_dates', array());
				$particular_date_lists = is_array($particular_date_lists) ? $particular_date_lists : array();

				$off_days = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_off_days');
				$off_day_keys = array_filter(explode(',', (string) $off_days));
				$days = MPWPB_Global_Function::week_day();
				$open_days_count = count($days) - count($off_day_keys);
				$off_day_lists = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_off_dates', array());
				$off_day_lists = is_array($off_day_lists) ? $off_day_lists : array();
				?>
				<div class="mpwpb-dtm mpwpb_settings_date_time" id="mpwpb-dtm" data-tabs="#mpwpb_settings_date_time">

					<div class="mpwpb-dtm__grid">
						<div class="mpwpb-dtm__col mpwpb-dtm__col--side">

							<div class="mpwpb-dtm__card">
								<div class="mpwpb-dtm__card-head">
									<span class="dashicons dashicons-admin-generic"></span>
									<div><h3><?php esc_html_e('General Configuration', 'service-booking-manager'); ?></h3></div>
								</div>
								<div class="mpwpb-dtm__card-body">
									<div class="mpwpb-dtm__field">
										<label class="mpwpb-dtm__field-label"><?php esc_html_e('Date Type', 'service-booking-manager'); ?> <span class="textRequired">&nbsp;*</span></label>
										<select class="formControl" name="mpwpb_date_type" data-collapse-target required>
											<option disabled selected><?php esc_html_e('Please select ...', 'service-booking-manager'); ?></option>
											<option value="particular" data-option-target="#mp_particular" <?php selected($date_type, 'particular'); ?>><?php esc_html_e('Particular', 'service-booking-manager'); ?></option>
											<option value="repeated" data-option-target="#mp_repeated" <?php selected($date_type, 'repeated'); ?>><?php esc_html_e('Repeated', 'service-booking-manager'); ?></option>
										</select>
									</div>

									<div class="mpwpb-dtm__field mpwpb-dtm__collapse<?php echo $date_type === 'particular' ? ' mActive' : ''; ?>" data-collapse="#mp_particular">
										<label class="mpwpb-dtm__field-label"><?php esc_html_e('Particular Dates', 'service-booking-manager'); ?> <span class="textRequired">&nbsp;*</span></label>
										<div class="mp_settings_area">
											<div class="mp_item_insert mp_sortable_area mpwpb-dtm__date-grid">
												<?php
												foreach ($particular_date_lists as $particular_date) {
													if ($particular_date) {
														$this->date_card('mpwpb_particular_dates[]', $particular_date, esc_html__('Bookable date', 'service-booking-manager'));
													}
												}
												?>
											</div>
											<?php MPWPB_Custom_Layout::add_new_button(esc_html__('Add New Particular Date', 'service-booking-manager')); ?>
											<div class="mpwpb_hidden_content" style="display:none">
												<div class="mpwpb_hidden_item">
													<?php $this->date_card('mpwpb_particular_dates[]', '', esc_html__('Bookable date', 'service-booking-manager')); ?>
												</div>
											</div>
										</div>
									</div>

									<input type="hidden" name="mpwpb_repeated_start_date" value="<?php echo esc_attr($hidden_repeated_start_date); ?>" required/>
									<input type="hidden" name="mpwpb_repeated_after" value="<?php echo esc_attr($repeated_after); ?>"/>

									<div class="mpwpb-dtm__field mpwpb-dtm__collapse<?php echo $date_type === 'repeated' ? ' mActive' : ''; ?>" data-collapse="#mp_repeated">
										<label class="mpwpb-dtm__field-label"><?php esc_html_e('Max Advanced Booking (Days)', 'service-booking-manager'); ?> <span class="textRequired">&nbsp;*</span></label>
										<input type="text" class="formControl mpwpb_number_validation" name="mpwpb_active_days" value="<?php echo esc_attr($active_days); ?>"/>
									</div>

									<div class="mpwpb-dtm__field">
										<label class="mpwpb-dtm__field-label"><?php esc_html_e('Time Slot Length', 'service-booking-manager'); ?> <span class="textRequired">&nbsp;*</span></label>
										<select class="formControl" name="mpwpb_time_slot_length">
											<option selected disabled><?php esc_html_e('Select time slot Length', 'service-booking-manager'); ?></option>
											<option value="10" <?php selected($time_slot, 10); ?>><?php esc_html_e('10 min', 'service-booking-manager'); ?></option>
											<option value="15" <?php selected($time_slot, 15); ?>><?php esc_html_e('15 min', 'service-booking-manager'); ?></option>
											<option value="30" <?php selected($time_slot, 30); ?>><?php esc_html_e('30 min', 'service-booking-manager'); ?></option>
											<option value="60" <?php selected($time_slot, 60); ?>><?php esc_html_e('1 Hour', 'service-booking-manager'); ?></option>
											<option value="120" <?php selected($time_slot, 120); ?>><?php esc_html_e('2 Hour', 'service-booking-manager'); ?></option>
											<option value="180" <?php selected($time_slot, 180); ?>><?php esc_html_e('3 Hour', 'service-booking-manager'); ?></option>
										</select>
									</div>

									<div class="mpwpb-dtm__field">
										<label class="mpwpb-dtm__field-label"><?php esc_html_e('Capacity per Session', 'service-booking-manager'); ?> <span class="textRequired">&nbsp;*</span></label>
										<input class="formControl" name="mpwpb_capacity_per_session" type="number" value="<?php echo esc_attr($capacity); ?>" placeholder="Ex. 25"/>
									</div>
								</div>
							</div>

							<div class="mpwpb-dtm__card">
								<div class="mpwpb-dtm__card-head">
									<span class="dashicons dashicons-editor-break"></span>
									<div><h3><?php esc_html_e('Weekly Off-days', 'service-booking-manager'); ?></h3></div>
								</div>
								<div class="mpwpb-dtm__card-body">
									<p class="mpwpb-dtm__hint"><?php esc_html_e('Select the days the operation is closed.', 'service-booking-manager'); ?></p>
									<div class="groupCheckBox mpwpb-dtm__offdays" id="mpwpb-dtm-offdays">
										<input type="hidden" name="mpwpb_off_days" value="<?php echo esc_attr($off_days); ?>"/>
										<?php foreach ($days as $key => $day) : ?>
											<label class="customCheckboxLabel mpwpb-dtm__pill" data-dtm-offday-pill title="<?php echo esc_attr($day); ?>">
												<input type="checkbox" <?php echo in_array($key, $off_day_keys, true) ? 'checked' : ''; ?> data-checked="<?php echo esc_attr($key); ?>"/>
												<span class="customCheckbox mpwpb-dtm__pill-txt"><?php echo esc_html(mb_substr($day, 0, 3)); ?></span>
											</label>
										<?php endforeach; ?>
									</div>
								</div>
							</div>

						</div>

						<div class="mpwpb-dtm__col mpwpb-dtm__col--main">
							<div class="mpwpb-dtm__card">
								<div class="mpwpb-dtm__card-head">
									<span class="dashicons dashicons-clock"></span>
									<div>
										<h3><?php esc_html_e('Weekly Schedule', 'service-booking-manager'); ?></h3>
										<p><?php esc_html_e('Configure standard operating hours for each day.', 'service-booking-manager'); ?></p>
									</div>
									<span class="mpwpb-dtm__badge" data-dtm-open-badge><?php echo esc_html(sprintf(__('%1$d / %2$d Days Open', 'service-booking-manager'), $open_days_count, count($days))); ?></span>
								</div>
								<div class="mpwpb-dtm__card-body mpwpb-dtm__table-wrap">
									<table class="mpwpb-dtm__table">
										<thead>
										<tr>
											<th style="text-align:left;"><?php esc_html_e('Day', 'service-booking-manager'); ?></th>
											<th><?php esc_html_e('Start Time', 'service-booking-manager'); ?></th>
											<th><?php esc_html_e('End Time', 'service-booking-manager'); ?></th>
											<th><?php esc_html_e('Break (Start)', 'service-booking-manager'); ?></th>
											<th><?php esc_html_e('Break (End)', 'service-booking-manager'); ?></th>
										</tr>
										</thead>
										<tbody>
										<?php
										$classic->time_slot_tr($post_id, 'default');
										foreach ($days as $key => $day) {
											$classic->time_slot_tr($post_id, $key, in_array($key, $off_day_keys, true));
										}
										?>
										</tbody>
									</table>
								</div>
							</div>
						</div>
					</div>

					<div class="mpwpb-dtm__card mpwpb-dtm__card--full">
						<div class="mp_settings_area mpwpb-dtm__special-area">
							<div class="mpwpb-dtm__card-head">
								<span class="dashicons dashicons-calendar-alt"></span>
								<div>
									<h3><?php esc_html_e('Special Dates & Holidays', 'service-booking-manager'); ?></h3>
									<p><?php esc_html_e('Add specific dates where the standard schedule does not apply.', 'service-booking-manager'); ?></p>
								</div>
								<?php MPWPB_Custom_Layout::add_new_button(esc_html__('Add Special Date', 'service-booking-manager'), 'mp_add_item', 'mpwpb-dtm__add-btn', 'dashicons dashicons-plus-alt2'); ?>
							</div>
							<div class="mpwpb-dtm__card-body">
								<div class="mp_item_insert mp_sortable_area mpwpb-dtm__date-grid">
									<?php
									$has_off_dates = false;
									foreach ($off_day_lists as $off_day) {
										if ($off_day) {
											$has_off_dates = true;
											$this->date_card('mpwpb_off_dates[]', $off_day, esc_html__('Operation: Closed', 'service-booking-manager'));
										}
									}
									?>
								</div>
								<p class="mpwpb-dtm__empty" style="<?php echo $has_off_dates ? 'display:none' : ''; ?>"><?php esc_html_e('No special dates added yet.', 'service-booking-manager'); ?></p>
								<div class="mpwpb_hidden_content" style="display:none">
									<div class="mpwpb_hidden_item">
										<?php $this->date_card('mpwpb_off_dates[]', '', esc_html__('Operation: Closed', 'service-booking-manager')); ?>
									</div>
								</div>
							</div>
						</div>
					</div>

				</div>
				<?php
			}
		}
	}
