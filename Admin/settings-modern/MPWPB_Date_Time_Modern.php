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
	 * "Weekly hours" toggle-row redesign: time_slot_tr() still renders each
	 * real <tr> (same 5 <td>s, same <select> field names, same data-day-name
	 * AJAX cascade) completely unchanged — assets/admin/mpwpb-datetime-modern.css
	 * turns the <table> into flexed row-cards purely visually, and
	 * assets/admin/mpwpb-datetime-modern.js injects a per-row toggle switch
	 * that forwards clicks to the real (still-present, just visually hidden)
	 * "Weekly Off-days" checkboxes in #mpwpb-dtm-offdays, rather than
	 * reimplementing that aggregation logic. Three other reused classic
	 * methods:
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
					<div class="mpwpb-dtm__date-card-row">
						<span class="dashicons dashicons-calendar-alt mpwpb-dtm__date-card-icon"></span>
						<?php MPWPB_Date_Time_Settings::particular_date_item($name, $date); ?>
					</div>
					<p class="mpwpb-dtm__date-card-sub"><?php echo esc_html($sub_label); ?></p>
				</div>
				<?php
			}

			public function render($post_id) {
				$classic = $this->classic_instance();
				if (!$classic) {
					return;
				}

				// New services start on "Every day" with a 1 hour slot until the admin picks otherwise.
				$date_type = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_date_type', 'repeated') ?: 'repeated';
				$time_slot = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_time_slot_length');
				if ($time_slot === '' || $time_slot === null || $time_slot === false) {
					$time_slot = 60;
				}
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

				$slot_presets = MPWPB_Function::slot_length_presets();
				$slot_is_custom = $time_slot !== '' && !array_key_exists((int) $time_slot, $slot_presets);
				?>
				<div class="mpwpb-dtm mpwpb_settings_date_time" id="mpwpb-dtm" data-tabs="#mpwpb_settings_date_time">

					<div class="mpwpb-dtm__header">
						<h2><?php esc_html_e('Availability', 'service-booking-manager'); ?></h2>
						<p><?php esc_html_e('Turn days on or off, set the hours, done.', 'service-booking-manager'); ?></p>
					</div>

					<div class="mpwpb-dtm__grid">
						<div class="mpwpb-dtm__col mpwpb-dtm__col--main">

							<div class="mpwpb-dtm__card mpwpb-dtm__hours" data-dtm-hours>
								<div class="mpwpb-dtm__card-head">
									<span class="mpwpb-dtm__rules-icon" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></span>
									<div>
										<h3><?php esc_html_e('Weekly hours', 'service-booking-manager'); ?></h3>
										<p data-dtm-open-sub><?php echo esc_html(sprintf(_n('%d day open', '%d days open', $open_days_count, 'service-booking-manager'), $open_days_count)); ?></p>
									</div>
									<button type="button" class="mpwpb-dtm__copy-btn" data-dtm-copy-monday>
										<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="12" height="12" rx="2"/><path d="M5 15V5a2 2 0 0 1 2-2h10"/></svg>
										<?php esc_html_e('Copy Monday to all days', 'service-booking-manager'); ?>
									</button>
								</div>
								<div class="mpwpb-dtm__hours-cols" aria-hidden="true">
									<span><?php esc_html_e('Day', 'service-booking-manager'); ?></span>
									<span><?php esc_html_e('Hours', 'service-booking-manager'); ?></span>
									<span class="mpwpb-dtm__hours-scale" data-dtm-scale></span>
									<span><?php esc_html_e('Break', 'service-booking-manager'); ?></span>
								</div>
								<div class="mpwpb-dtm__card-body mpwpb-dtm__table-wrap">
									<table class="mpwpb-dtm__table mpwpb-dtm__table--rows">
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

							<?php
							/* Real off-day checkboxes/hidden field, UNCHANGED (same groupCheckBox
							 * aggregation the save handler and existing JS already expect) --
							 * just visually hidden off-screen (not display:none, so any logic
							 * that cares about visibility still works). The per-row toggle
							 * switches assets/admin/mpwpb-datetime-modern.js injects into the
							 * table above forward their clicks here, so this stays the single
							 * source of truth for mpwpb_off_days. */
							?>
							<div class="mpwpb-dtm__offscreen">
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

						<div class="mpwpb-dtm__col mpwpb-dtm__col--side">

							<div class="mpwpb-dtm__card mpwpb-dtm__rules" data-dtm-rules>
								<div class="mpwpb-dtm__card-head">
									<span class="mpwpb-dtm__rules-icon" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/><path d="M9 15l2 2 4-4"/></svg></span>
									<div>
										<h3><?php esc_html_e('Booking rules', 'service-booking-manager'); ?></h3>
										<p><?php esc_html_e('When customers can book, how long each slot is, and how many fit in it.', 'service-booking-manager'); ?></p>
									</div>
								</div>
								<div class="mpwpb-dtm__card-body">

									<div class="mpwpb-dtm__field mpwpb-dtm__rule">
										<div class="mpwpb-dtm__rule-head">
											<label class="mpwpb-dtm__field-label"><?php esc_html_e('Availability', 'service-booking-manager'); ?></label>
											<p class="mpwpb-dtm__rule-hint"><?php esc_html_e('Open on a rolling window, or only on dates you pick.', 'service-booking-manager'); ?></p>
										</div>
										<select class="formControl mpwpb-dtm__sr-select" name="mpwpb_date_type" data-collapse-target required>
											<option value="particular" data-option-target="#mp_particular" <?php selected($date_type, 'particular'); ?>><?php esc_html_e('Particular', 'service-booking-manager'); ?></option>
											<option value="repeated" data-option-target="#mp_repeated" <?php selected($date_type, 'repeated'); ?>><?php esc_html_e('Repeated', 'service-booking-manager'); ?></option>
										</select>
										<div class="mpwpb-dtm__segmented" data-dtm-schedule-type role="radiogroup" aria-label="<?php esc_attr_e('Schedule type', 'service-booking-manager'); ?>">
											<button type="button" role="radio" aria-checked="<?php echo $date_type === 'repeated' ? 'true' : 'false'; ?>" class="mpwpb-dtm__segmented-btn<?php echo $date_type === 'repeated' ? ' is-active' : ''; ?>" data-dtm-schedule-value="repeated">
												<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 2l3 3-3 3"/><path d="M4 11V9a4 4 0 0 1 4-4h12"/><path d="M7 22l-3-3 3-3"/><path d="M20 13v2a4 4 0 0 1-4 4H4"/></svg>
												<?php esc_html_e('Every day', 'service-booking-manager'); ?>
											</button>
											<button type="button" role="radio" aria-checked="<?php echo $date_type === 'particular' ? 'true' : 'false'; ?>" class="mpwpb-dtm__segmented-btn<?php echo $date_type === 'particular' ? ' is-active' : ''; ?>" data-dtm-schedule-value="particular">
												<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/><circle cx="12" cy="15" r="1.6"/></svg>
												<?php esc_html_e('Specific dates', 'service-booking-manager'); ?>
											</button>
										</div>

										<div class="mpwpb-dtm__collapse mpwpb-dtm__rule-sub<?php echo $date_type === 'particular' ? ' mActive' : ''; ?>" data-collapse="#mp_particular">
											<label class="mpwpb-dtm__sub-label"><?php esc_html_e('Bookable dates', 'service-booking-manager'); ?> <span class="textRequired">&nbsp;*</span></label>
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
												<p class="mpwpb-dtm__rule-warning" data-dtm-no-dates><?php esc_html_e('Add at least one date, or customers will have nothing to book.', 'service-booking-manager'); ?></p>
												<?php MPWPB_Custom_Layout::add_new_button(esc_html__('Add date', 'service-booking-manager')); ?>
												<div class="mpwpb_hidden_content" style="display:none">
													<div class="mpwpb_hidden_item">
														<?php $this->date_card('mpwpb_particular_dates[]', '', esc_html__('Bookable date', 'service-booking-manager')); ?>
													</div>
												</div>
											</div>
										</div>

										<input type="hidden" name="mpwpb_repeated_start_date" value="<?php echo esc_attr($hidden_repeated_start_date); ?>" required/>
										<input type="hidden" name="mpwpb_repeated_after" value="<?php echo esc_attr($repeated_after); ?>"/>

										<div class="mpwpb-dtm__collapse mpwpb-dtm__rule-sub<?php echo $date_type === 'repeated' ? ' mActive' : ''; ?>" data-collapse="#mp_repeated">
											<label class="mpwpb-dtm__sub-label" for="mpwpb-dtm-active-days"><?php esc_html_e('Customers can book up to', 'service-booking-manager'); ?> <span class="textRequired">&nbsp;*</span></label>
											<div class="mpwpb-dtm__days-row">
												<div class="mpwpb-dtm__counter">
													<button type="button" class="mpwpb-dtm__counter-btn" data-dtm-days-step="-1" aria-label="<?php esc_attr_e('Fewer days', 'service-booking-manager'); ?>">&minus;</button>
													<input type="text" id="mpwpb-dtm-active-days" inputmode="numeric" class="formControl mpwpb_number_validation" name="mpwpb_active_days" value="<?php echo esc_attr($active_days); ?>"/>
													<button type="button" class="mpwpb-dtm__counter-btn" data-dtm-days-step="1" aria-label="<?php esc_attr_e('More days', 'service-booking-manager'); ?>">+</button>
												</div>
												<span class="mpwpb-dtm__counter-suffix"><?php esc_html_e('days ahead', 'service-booking-manager'); ?></span>
											</div>
											<div class="mpwpb-dtm__presets" data-dtm-day-presets>
												<?php foreach (array(7, 14, 30, 60, 90) as $preset_days) : ?>
													<button type="button" class="mpwpb-dtm__preset<?php echo ((int) $active_days === $preset_days) ? ' is-active' : ''; ?>" data-dtm-days-preset="<?php echo esc_attr($preset_days); ?>">
														<?php /* translators: %d: number of days */ echo esc_html(sprintf(__('%d days', 'service-booking-manager'), $preset_days)); ?>
													</button>
												<?php endforeach; ?>
											</div>
										</div>
									</div>

									<div class="mpwpb-dtm__field mpwpb-dtm__rule">
										<div class="mpwpb-dtm__rule-head">
											<label class="mpwpb-dtm__field-label"><?php esc_html_e('Slot length', 'service-booking-manager'); ?> <span class="textRequired">&nbsp;*</span></label>
											<p class="mpwpb-dtm__rule-hint"><?php esc_html_e('How long one booking lasts. Sets the times shown to customers.', 'service-booking-manager'); ?></p>
										</div>
										<select class="formControl mpwpb-dtm__sr-select mpwpb-slot-length-select" name="mpwpb_time_slot_length">
											<option value="" <?php selected($time_slot, ''); ?> disabled><?php esc_html_e('Select time slot Length', 'service-booking-manager'); ?></option>
											<?php foreach ($slot_presets as $preset_value => $preset_label) { ?>
												<option value="<?php echo esc_attr($preset_value); ?>" <?php selected((int) $time_slot, $preset_value); ?>><?php echo esc_html($preset_label); ?></option>
											<?php } ?>
											<option value="custom" <?php selected($slot_is_custom, true); ?>><?php esc_html_e('Custom…', 'service-booking-manager'); ?></option>
										</select>
										<div class="mpwpb-dtm__pillgroup" data-dtm-slot-pills>
											<?php foreach ($slot_presets as $preset_value => $preset_label) : ?>
												<button type="button" aria-pressed="<?php echo ((int) $time_slot === $preset_value) ? 'true' : 'false'; ?>" class="mpwpb-dtm__pillgroup-btn<?php echo ((int) $time_slot === $preset_value) ? ' is-active' : ''; ?>" data-dtm-slot-value="<?php echo esc_attr($preset_value); ?>"><?php echo esc_html($preset_label); ?></button>
											<?php endforeach; ?>
											<button type="button" aria-pressed="<?php echo $slot_is_custom ? 'true' : 'false'; ?>" class="mpwpb-dtm__pillgroup-btn mpwpb-dtm__pillgroup-btn--dashed<?php echo $slot_is_custom ? ' is-active' : ''; ?>" data-dtm-slot-value="custom"><?php esc_html_e('Custom', 'service-booking-manager'); ?></button>
										</div>
										<div class="mpwpb-dtm__slot-custom" style="<?php echo $slot_is_custom ? '' : 'display:none;'; ?>">
											<label class="mpwpb-dtm__sub-label" for="mpwpb-dtm-slot-custom"><?php esc_html_e('Minutes', 'service-booking-manager'); ?></label>
											<div class="mpwpb-dtm__suffix-input">
												<input type="number" id="mpwpb-dtm-slot-custom" class="formControl mpwpb-slot-length-custom" name="mpwpb_time_slot_length_custom" min="1" step="1"
												       value="<?php echo esc_attr($slot_is_custom ? (int) $time_slot : ''); ?>"
												       placeholder="<?php esc_attr_e('Ex. 45', 'service-booking-manager'); ?>"/>
												<span><?php esc_html_e('min', 'service-booking-manager'); ?></span>
											</div>
										</div>
									</div>

									<div class="mpwpb-dtm__field mpwpb-dtm__rule">
										<div class="mpwpb-dtm__rule-head">
											<label class="mpwpb-dtm__field-label" for="mpwpb-dtm-capacity"><?php esc_html_e('People per slot', 'service-booking-manager'); ?> <span class="textRequired">&nbsp;*</span></label>
											<p class="mpwpb-dtm__rule-hint"><?php esc_html_e('Bookings allowed at the same time. Use 1 for one-to-one.', 'service-booking-manager'); ?></p>
										</div>
										<div class="mpwpb-dtm__days-row">
											<div class="mpwpb-dtm__counter mpwpb-dtm__stepper">
												<button type="button" class="mpwpb-dtm__counter-btn" data-dtm-capacity-minus aria-label="<?php esc_attr_e('Decrease capacity', 'service-booking-manager'); ?>">&minus;</button>
												<input class="formControl" id="mpwpb-dtm-capacity" name="mpwpb_capacity_per_session" type="number" value="<?php echo esc_attr($capacity); ?>" min="1"/>
												<button type="button" class="mpwpb-dtm__counter-btn" data-dtm-capacity-plus aria-label="<?php esc_attr_e('Increase capacity', 'service-booking-manager'); ?>">+</button>
											</div>
											<span class="mpwpb-dtm__capacity-hint" data-dtm-capacity-hint
											      data-one="<?php esc_attr_e('One-to-one', 'service-booking-manager'); ?>"
											      data-many="<?php /* translators: %d: number of people */ esc_attr_e('%d people can book the same time', 'service-booking-manager'); ?>"></span>
										</div>
									</div>

								</div>
								<div class="mpwpb-dtm__rules-summary" data-dtm-rules-summary
								     data-label="<?php esc_attr_e('Customers see:', 'service-booking-manager'); ?>"
								     data-every="<?php /* translators: %d: days */ esc_attr_e('any open day up to %d days ahead', 'service-booking-manager'); ?>"
								     data-dates-one="<?php esc_attr_e('only on 1 chosen date', 'service-booking-manager'); ?>"
								     data-dates-many="<?php /* translators: %d: dates */ esc_attr_e('only on %d chosen dates', 'service-booking-manager'); ?>"
								     data-dates-none="<?php esc_attr_e('no dates yet', 'service-booking-manager'); ?>"
								     data-slots="<?php /* translators: %s: slot length, e.g. 1 Hour */ esc_attr_e('%s slots', 'service-booking-manager'); ?>"
								     data-cap-one="<?php esc_attr_e('one booking per slot', 'service-booking-manager'); ?>"
								     data-cap-many="<?php /* translators: %d: capacity */ esc_attr_e('up to %d bookings per slot', 'service-booking-manager'); ?>">
									<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8h.01M11 12h1v4h1"/></svg>
									<span data-dtm-rules-summary-text></span>
								</div>
							</div>

							<div class="mpwpb-dtm__preview" data-dtm-preview>
								<div class="mpwpb-dtm__preview-eyebrow"><?php esc_html_e('Customers will see', 'service-booking-manager'); ?></div>
								<div class="mpwpb-dtm__preview-sub" data-dtm-preview-sub>&nbsp;</div>
								<div class="mpwpb-dtm__preview-slots" data-dtm-preview-slots></div>
							</div>

						</div>
					</div>

					<?php
					$off_dates_json = wp_json_encode(array_values(array_filter($off_day_lists)));
					?>
					<div class="mpwpb-dtm__card mpwpb-dtm__card--full">
						<div class="mp_settings_area mpwpb-dtm__special-area">
							<div class="mpwpb-dtm__card-head">
								<div>
									<h3><?php esc_html_e('Special Dates & Holidays', 'service-booking-manager'); ?></h3>
									<p><?php esc_html_e('Click a date to mark it closed.', 'service-booking-manager'); ?></p>
								</div>
								<span class="mpwpb-dtm__badge" data-dtm-off-cal-summary></span>
							</div>
							<div class="mpwpb-dtm__card-body">
								<?php
								/* The calendar itself (nav, month label, day grid) is built
								 * entirely by mpwpb-datetime-modern.js -- data-dtm-off-dates
								 * seeds it with whatever was already saved (same Y-m-d
								 * strings the old repeater stored), and every click
								 * add/removes one real <input type="hidden"
								 * name="mpwpb_off_dates[]"> in data-dtm-off-dates-fields, so
								 * the save handler's expected field (one hidden input per
								 * date, same name, unchanged since Classic) never had to
								 * change -- only how those values get populated. */
								?>
								<div class="mpwpb-dtm__special-grid">
									<div class="mpwpb-dtm__calendar" data-dtm-off-calendar data-dtm-off-dates="<?php echo esc_attr($off_dates_json); ?>"></div>
									<div class="mpwpb-dtm__off-list-col">
										<div class="mpwpb-dtm__off-list-label"><?php esc_html_e('Closed dates', 'service-booking-manager'); ?></div>
										<div class="mpwpb-dtm__off-list" data-dtm-off-list></div>
									</div>
								</div>
								<div class="mpwpb-dtm__offscreen" data-dtm-off-dates-fields></div>
							</div>
						</div>
					</div>

				</div>
				<?php
			}
		}
	}
