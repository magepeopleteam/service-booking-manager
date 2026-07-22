<?php
	if (!defined('ABSPATH')) {
		die;
	}

	if (!class_exists('MPWPB_Recurring_Account_Manager')) {
		/** Customer-side editor for individual occurrences in a paid series. */
		class MPWPB_Recurring_Account_Manager {
			public function __construct() {
				add_action('wp_ajax_mpwpb_recurring_account_times', array($this, 'ajax_times'));
				add_action('wp_ajax_mpwpb_recurring_account_save', array($this, 'ajax_save'));
			}

			public static function selection($booking_id): array {
				$service_id = absint(get_post_meta($booking_id, 'mpwpb_id', true));
				$live = (array) MPWPB_Global_Function::get_post_info($service_id, 'mpwpb_service', array());
				$selection = array();
				foreach ((array) get_post_meta($booking_id, 'mpwpb_service', true) as $stored) {
					if (!is_array($stored)) {
						continue;
					}
					$key = absint($stored['service_id'] ?? 0);
					if (!$key || !isset($live[$key - 1]) || ($stored['name'] ?? '') !== ($live[$key - 1]['name'] ?? '')) {
						$key = 0;
						foreach ($live as $live_key => $item) {
							if (($item['name'] ?? '') === ($stored['name'] ?? '')) {
								$key = $live_key + 1;
								break;
							}
						}
					}
					if ($key) {
						$selection[$key] = max(1, absint($stored['qty'] ?? 1));
					}
				}
				return $selection;
			}

			public static function can_edit($booking_id): bool {
				$allowed = get_post_type($booking_id) === 'mpwpb_booking'
					&& (int) get_post_meta($booking_id, 'mpwpb_user_id', true) === get_current_user_id()
					&& get_post_meta($booking_id, 'mpwpb_order_status', true) !== 'cancelled'
					&& (!class_exists('MPWPB_Cancellation') || MPWPB_Cancellation::get_status($booking_id) !== MPWPB_Cancellation::STATUS_PENDING)
					&& MPWPB_Booking_History::is_within_lead_time(get_post_meta($booking_id, 'mpwpb_date', true), 'reschedule');
				if (!$allowed || !class_exists('MPWPB_Cancellation')) {
					return $allowed;
				}
				foreach (MPWPB_User_Dashboard::get_bookings_for_order(get_post_meta($booking_id, 'mpwpb_order_id', true)) as $series_id) {
					if (MPWPB_Cancellation::get_status($series_id) === MPWPB_Cancellation::STATUS_PENDING) {
						return false;
					}
				}
				return true;
			}

			public static function can_add(array $booking_ids, $service_id): bool {
				$max = max(2, absint(MPWPB_Global_Function::get_post_info($service_id, 'mpwpb_max_recurring_count', 26)));
				if (MPWPB_Global_Function::get_post_info($service_id, 'mpwpb_enable_recurring', 'no') !== 'yes' || count($booking_ids) >= $max) {
					return false;
				}
				foreach ($booking_ids as $booking_id) {
					if (get_post_meta($booking_id, 'mpwpb_order_status', true) === 'cancelled'
						|| (class_exists('MPWPB_Cancellation') && MPWPB_Cancellation::get_status($booking_id) === MPWPB_Cancellation::STATUS_PENDING)) {
						return false;
					}
				}
				return true;
			}

			public static function render_modal($order_id, array $booking_ids, $service_id): void {
				$available_dates = MPWPB_Function::get_date($service_id);
				$dates = $available_dates;
				foreach ($booking_ids as $booking_id) {
					$current_date = substr((string) get_post_meta($booking_id, 'mpwpb_date', true), 0, 10);
					if ($current_date) {
						$dates[] = $current_date;
					}
				}
				$dates = array_values(array_unique($dates));
				sort($dates);
				$services = (array) MPWPB_Global_Function::get_post_info($service_id, 'mpwpb_service', array());
				$multiple = MPWPB_Global_Function::get_post_info($service_id, 'mpwpb_multiple_service_select', 'off') === 'on';
				?>
				<div id="mpwpb-recurring-editor" class="mpwpb-recurring-editor" role="dialog" aria-modal="true" aria-labelledby="mpwpb-recurring-editor-title" aria-hidden="true">
					<div class="mpwpb-recurring-editor__panel">
						<button type="button" class="mpwpb-recurring-editor__close" aria-label="<?php esc_attr_e('Close recurring booking editor', 'service-booking-manager'); ?>">&times;</button>
						<header class="mpwpb-recurring-editor__header">
							<span class="mpwpb-recurring-editor__icon"><i class="fas fa-calendar-alt" aria-hidden="true"></i></span>
							<div><span><?php esc_html_e('Manage recurring booking', 'service-booking-manager'); ?></span><h3 id="mpwpb-recurring-editor-title"><?php esc_html_e('Edit appointment', 'service-booking-manager'); ?></h3><p><?php esc_html_e('Choose the date first, then an available time, then review or add services.', 'service-booking-manager'); ?></p></div>
						</header>
						<nav class="mpwpb-recurring-editor__steps" aria-label="<?php esc_attr_e('Editing steps', 'service-booking-manager'); ?>">
							<span class="is-active" data-recurring-step-indicator="date"><b>1</b><?php esc_html_e('Date', 'service-booking-manager'); ?></span>
							<span data-recurring-step-indicator="time"><b>2</b><?php esc_html_e('Time', 'service-booking-manager'); ?></span>
							<span data-recurring-step-indicator="services"><b>3</b><?php esc_html_e('Services', 'service-booking-manager'); ?></span>
						</nav>
						<form id="mpwpb-recurring-editor-form">
							<input type="hidden" name="action" value="mpwpb_recurring_account_save">
							<input type="hidden" name="nonce" value="<?php echo esc_attr(wp_create_nonce('mpwpb_dashboard_nonce')); ?>">
							<input type="hidden" name="booking_id" id="mpwpb_recurring_editor_booking_id" value="">
							<input type="hidden" name="mode" id="mpwpb_recurring_editor_mode" value="edit">
							<input type="hidden" name="new_date" id="mpwpb_recurring_editor_date" value="">
							<input type="hidden" name="new_time" id="mpwpb_recurring_editor_time" value="">

							<section class="mpwpb-recurring-editor__stage is-active" data-recurring-stage="date">
								<div class="mpwpb-recurring-editor__stage-heading"><span><?php esc_html_e('Step 1', 'service-booking-manager'); ?></span><h4><?php esc_html_e('Select a date', 'service-booking-manager'); ?></h4></div>
								<div class="mpwpb-recurring-editor__date-grid">
									<?php foreach ($dates as $date) : ?>
										<button type="button" data-recurring-date="<?php echo esc_attr($date); ?>" data-standard-date="<?php echo esc_attr(in_array($date, $available_dates, true) ? '1' : '0'); ?>"><small><?php echo esc_html(date_i18n('D', strtotime($date))); ?></small><strong><?php echo esc_html(date_i18n('j', strtotime($date))); ?></strong><span><?php echo esc_html(date_i18n('M', strtotime($date))); ?></span></button>
									<?php endforeach; ?>
									<?php if (!$dates) : ?><p><?php esc_html_e('No bookable dates are currently available.', 'service-booking-manager'); ?></p><?php endif; ?>
								</div>
							</section>

							<section class="mpwpb-recurring-editor__stage" data-recurring-stage="time" hidden>
								<div class="mpwpb-recurring-editor__stage-heading"><span><?php esc_html_e('Step 2', 'service-booking-manager'); ?></span><h4><?php esc_html_e('Select an available time', 'service-booking-manager'); ?></h4><button type="button" data-recurring-back="date"><?php esc_html_e('Change date', 'service-booking-manager'); ?></button></div>
								<div class="mpwpb-recurring-editor__time-grid" aria-live="polite"></div>
							</section>

							<section class="mpwpb-recurring-editor__stage" data-recurring-stage="services" hidden>
								<div class="mpwpb-recurring-editor__stage-heading"><span><?php esc_html_e('Step 3', 'service-booking-manager'); ?></span><h4><?php esc_html_e('Review and add services', 'service-booking-manager'); ?></h4><button type="button" data-recurring-back="time"><?php esc_html_e('Change time', 'service-booking-manager'); ?></button></div>
								<p class="mpwpb-recurring-editor__help"><?php esc_html_e('Previously selected services remain checked. You may add another service or adjust quantities before updating.', 'service-booking-manager'); ?></p>
								<div class="mpwpb-recurring-editor__service-list">
									<?php foreach ($services as $key => $service) : $service_key = $key + 1; ?>
										<label class="mpwpb-recurring-editor__service" data-service-price="<?php echo esc_attr((float) ($service['price'] ?? 0)); ?>">
											<input type="checkbox" name="services[]" value="<?php echo esc_attr($service_key); ?>">
											<span class="mpwpb-recurring-editor__check"><i class="fas fa-check"></i></span>
											<span class="mpwpb-recurring-editor__service-copy"><strong><?php echo esc_html($service['name'] ?? ''); ?></strong><?php if (!empty($service['duration'])) : ?><small><i class="far fa-clock"></i> <?php echo esc_html($service['duration']); ?></small><?php endif; ?></span>
											<span class="mpwpb-recurring-editor__price"><?php echo wp_kses_post(MPWPB_Global_Function::wc_price($service_id, $service['price'] ?? 0)); ?></span>
											<?php if ($multiple) : ?><input class="mpwpb-recurring-editor__qty" type="number" name="service_qty[<?php echo esc_attr($service_key); ?>]" min="1" max="10" value="1" aria-label="<?php echo esc_attr(sprintf(__('Quantity for %s', 'service-booking-manager'), $service['name'] ?? '')); ?>"><?php else : ?><input type="hidden" name="service_qty[<?php echo esc_attr($service_key); ?>]" value="1"><?php endif; ?>
										</label>
									<?php endforeach; ?>
								</div>
							</section>

							<div class="mpwpb-recurring-editor__message" role="status" aria-live="polite"></div>
							<footer class="mpwpb-recurring-editor__footer">
								<div><small><?php esc_html_e('Selected service subtotal', 'service-booking-manager'); ?></small><strong data-recurring-price>—</strong></div>
								<button type="submit" class="mpwpb-btn" disabled><?php esc_html_e('Update appointment', 'service-booking-manager'); ?></button>
							</footer>
						</form>
					</div>
				</div>
				<?php
			}

			private function authorize($booking_id) {
				if (!is_user_logged_in() || get_post_type($booking_id) !== 'mpwpb_booking'
					|| (int) get_post_meta($booking_id, 'mpwpb_user_id', true) !== get_current_user_id()) {
					return new WP_Error('mpwpb_forbidden', __('You do not have permission to manage this recurring booking.', 'service-booking-manager'));
				}
				$order_id = absint(get_post_meta($booking_id, 'mpwpb_order_id', true));
				$series = MPWPB_User_Dashboard::get_bookings_for_order($order_id);
				if (count($series) < 2 || !in_array((int) $booking_id, $series, true)) {
					return new WP_Error('mpwpb_not_series', __('This booking is not part of a recurring series.', 'service-booking-manager'));
				}
				return array($order_id, $series, absint(get_post_meta($booking_id, 'mpwpb_id', true)));
			}

			public function ajax_times(): void {
				$this->verify_nonce();
				$booking_id = absint($_POST['booking_id'] ?? 0);
				$date = sanitize_text_field(wp_unslash($_POST['date'] ?? ''));
				$auth = $this->authorize($booking_id);
				if (is_wp_error($auth)) {
					wp_send_json_error(array('message' => $auth->get_error_message()), 403);
				}
				$service_id = $auth[2];
				$current = (string) get_post_meta($booking_id, 'mpwpb_date', true);
				if (!in_array($date, MPWPB_Function::get_date($service_id), true) && $date !== substr($current, 0, 10)) {
					wp_send_json_error(array('message' => __('That date is no longer available.', 'service-booking-manager')), 400);
				}
				$times = array();
				foreach (MPWPB_Function::get_time_slot($service_id, $date) as $slot) {
					$datetime = date('Y-m-d H:i', strtotime($slot));
					if ($datetime === $current || MPWPB_Function::get_total_available($service_id, $datetime) > 0) {
						$time = substr($datetime, 11, 5);
						$times[] = array('value' => $time, 'label' => MPWPB_Function::format_time($time));
					}
				}
				wp_send_json_success(array('times' => $times));
			}

			public function ajax_save(): void {
				$this->verify_nonce();
				$booking_id = absint($_POST['booking_id'] ?? 0);
				$mode = sanitize_key(wp_unslash($_POST['mode'] ?? 'edit'));
				$date = sanitize_text_field(wp_unslash($_POST['new_date'] ?? ''));
				$time = sanitize_text_field(wp_unslash($_POST['new_time'] ?? ''));
				$auth = $this->authorize($booking_id);
				if (is_wp_error($auth)) {
					wp_send_json_error(array('message' => $auth->get_error_message()), 403);
				}
				list($order_id, $series, $service_id) = $auth;
				if ($mode !== 'add' && !self::can_edit($booking_id)) {
					wp_send_json_error(array('message' => __('This appointment is no longer inside the online editing window.', 'service-booking-manager')), 400);
				}
				if ($mode === 'add' && !self::can_add($series, $service_id)) {
					wp_send_json_error(array('message' => __('This recurring series has reached its appointment limit.', 'service-booking-manager')), 400);
				}
				$new_datetime = $this->validate_datetime($service_id, $date, $time, $mode === 'edit' ? $booking_id : 0);
				if (is_wp_error($new_datetime)) {
					wp_send_json_error(array('message' => $new_datetime->get_error_message()), 400);
				}
				foreach ($series as $series_id) {
					$is_current_edit = $mode === 'edit' && (int) $series_id === $booking_id;
					if (!$is_current_edit && get_post_meta($series_id, 'mpwpb_order_status', true) !== 'cancelled'
						&& get_post_meta($series_id, 'mpwpb_date', true) === $new_datetime) {
						wp_send_json_error(array('message' => __('Another appointment in this series already uses that date and time.', 'service-booking-manager')), 400);
					}
				}
				$selection = $this->validate_services($service_id, $new_datetime);
				if (is_wp_error($selection)) {
					wp_send_json_error(array('message' => $selection->get_error_message()), 400);
				}
				$source_id = $mode === 'add' ? (int) $series[0] : $booking_id;
				$extra_total = $this->extra_total($source_id);
				$discount = min(100, max(0, (float) MPWPB_Global_Function::get_post_info($service_id, 'mpwpb_recurring_discount', 0)));
				$new_total = round(($selection['total'] + $extra_total) * (1 - $discount / 100), 2);

				if ($mode === 'add') {
					$changed_id = $this->add_occurrence($source_id, $new_datetime, $selection['services'], $new_total, count($series) + 1);
					if (is_wp_error($changed_id)) {
						wp_send_json_error(array('message' => $changed_id->get_error_message()), 500);
					}
					$series[] = $changed_id;
					$message = __('The appointment was added. Its balance is ready for payment.', 'service-booking-manager');
				} else {
					$changed_id = $booking_id;
					$paid = (float) get_post_meta($booking_id, 'mpwpb_amount_paid', true);
					if ($new_total + 0.005 < $paid) {
						wp_send_json_error(array('message' => __('The new selection costs less than the amount already paid. Please contact the administrator for a refund-assisted change.', 'service-booking-manager')), 400);
					}
					$old_datetime = (string) get_post_meta($booking_id, 'mpwpb_date', true);
					$old_services = (array) get_post_meta($booking_id, 'mpwpb_service', true);
					if ($old_datetime !== $new_datetime) {
						$result = MPWPB_Booking_History::reschedule($booking_id, $new_datetime, sprintf(__('Recurring appointment updated by customer: %s', 'service-booking-manager'), $new_datetime));
						if (is_wp_error($result)) {
							wp_send_json_error(array('message' => $result->get_error_message()), 400);
						}
						$this->sync_extra_dates($order_id, $service_id, $old_datetime, $new_datetime);
					}
					update_post_meta($booking_id, 'mpwpb_service', $selection['services']);
					update_post_meta($booking_id, 'mpwpb_tp', $new_total);
					update_post_meta($booking_id, 'mpwpb_amount_due', max(0, round($new_total - $paid, 2)));
					if (class_exists('MPWPB_Partial_Payment')) {
						MPWPB_Partial_Payment::sync_display_status($booking_id);
					}
					if ($old_services !== $selection['services']) {
						MPWPB_Booking_History::log($booking_id, MPWPB_Booking_History::ACTION_SERVICES_UPDATED, $old_datetime, $new_datetime, __('Recurring appointment services updated by customer.', 'service-booking-manager'));
					}
					$message = max(0, $new_total - $paid) > 0
						? __('The appointment was updated. Please pay the new balance to confirm the added services.', 'service-booking-manager')
						: __('The recurring appointment was updated successfully.', 'service-booking-manager');
				}

				$series = $this->renumber_series($series);
				$this->sync_wc_order($order_id, $service_id, $series, $message);
				wp_send_json_success(array('message' => $message, 'booking_id' => $changed_id));
			}

			private function verify_nonce(): void {
				if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'mpwpb_dashboard_nonce')) {
					wp_send_json_error(array('message' => __('Security check failed.', 'service-booking-manager')), 403);
				}
			}

			private function validate_datetime($service_id, $date, $time, $booking_id = 0) {
				$current = $booking_id ? (string) get_post_meta($booking_id, 'mpwpb_date', true) : '';
				$is_current_date = $current && substr($current, 0, 10) === $date;
				if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !preg_match('/^\d{2}:\d{2}$/', $time)
					|| (!in_array($date, MPWPB_Function::get_date($service_id), true) && !$is_current_date)) {
					return new WP_Error('mpwpb_invalid_date', __('Choose a valid available date.', 'service-booking-manager'));
				}
				$datetime = $date . ' ' . $time;
				$valid_slots = array_map(static function($slot) { return date('Y-m-d H:i', strtotime($slot)); }, MPWPB_Function::get_time_slot($service_id, $date));
				if (!in_array($datetime, $valid_slots, true)) {
					return new WP_Error('mpwpb_invalid_time', __('Choose a valid available time.', 'service-booking-manager'));
				}
				if ($datetime !== $current && MPWPB_Function::get_total_available($service_id, $datetime) < 1) {
					return new WP_Error('mpwpb_full_time', __('That time was just booked. Please choose another.', 'service-booking-manager'));
				}
				return $datetime;
			}

			private function validate_services($service_id, $datetime) {
				$ids = isset($_POST['services']) && is_array($_POST['services']) ? array_values(array_unique(array_map('absint', wp_unslash($_POST['services'])))) : array();
				$quantities = isset($_POST['service_qty']) && is_array($_POST['service_qty']) ? array_map('absint', wp_unslash($_POST['service_qty'])) : array();
				$live = (array) MPWPB_Global_Function::get_post_info($service_id, 'mpwpb_service', array());
				$max_qty = MPWPB_Global_Function::get_post_info($service_id, 'mpwpb_multiple_service_select', 'off') === 'on' ? 10 : 1;
				if (!$ids) {
					return new WP_Error('mpwpb_no_service', __('Select at least one service.', 'service-booking-manager'));
				}
				$stored = array();
				$total = 0;
				foreach ($ids as $id) {
					$qty = max(1, absint($quantities[$id] ?? 1));
					if (!$id || !isset($live[$id - 1]) || empty($live[$id - 1]['name']) || $qty > $max_qty) {
						return new WP_Error('mpwpb_invalid_service', __('A selected service or quantity is invalid.', 'service-booking-manager'));
					}
					$price = (float) MPWPB_Function::get_price($service_id, $id, $datetime);
					$stored[] = array('service_id' => (string) $id, 'name' => $live[$id - 1]['name'], 'price' => $price, 'qty' => (string) $qty);
					$total += $price * $qty;
				}
				return array('services' => $stored, 'total' => $total);
			}

			private function extra_total($booking_id): float {
				$total = 0;
				foreach ((array) get_post_meta($booking_id, 'mpwpb_extra_service_info', true) as $extra) {
					if (is_array($extra)) {
						$total += (float) ($extra['ex_price'] ?? 0) * max(1, absint($extra['ex_qty'] ?? 1));
					}
				}
				return $total;
			}

			private function add_occurrence($source_id, $datetime, array $services, $total, $index) {
				$new_id = wp_insert_post(array('post_type' => 'mpwpb_booking', 'post_status' => 'publish', 'post_title' => get_the_title($source_id) . ' - Recurring #' . $index), true);
				if (is_wp_error($new_id)) {
					return $new_id;
				}
				$skip = array('mpwpb_date', 'mpwpb_service', 'mpwpb_tp', 'mpwpb_amount_paid', 'mpwpb_amount_due', 'mpwpb_order_status', 'mpwpb_recurring_index', 'mpwpb_recurring_total', 'mpwpb_cancellation_status', 'mpwpb_cancellation_reason', 'mpwpb_cancellation_requested_at', 'mpwpb_cancellation_requested_by');
				foreach (get_post_meta($source_id) as $key => $values) {
					if (in_array($key, $skip, true) || strpos($key, '_edit_') === 0) {
						continue;
					}
					foreach ($values as $value) {
						add_post_meta($new_id, $key, maybe_unserialize($value));
					}
				}
				update_post_meta($new_id, 'mpwpb_date', $datetime);
				update_post_meta($new_id, 'mpwpb_service', $services);
				update_post_meta($new_id, 'mpwpb_tp', $total);
				update_post_meta($new_id, 'mpwpb_amount_paid', 0);
				update_post_meta($new_id, 'mpwpb_amount_due', $total);
				update_post_meta($new_id, 'mpwpb_recurring_index', $index);
				$real_status = get_post_meta($new_id, 'mpwpb_real_order_status', true) ?: 'processing';
				update_post_meta($new_id, 'mpwpb_order_status', class_exists('MPWPB_Partial_Payment') ? MPWPB_Partial_Payment::compute_display_status($real_status, $total) : 'partially-paid');
				MPWPB_Booking_History::log($new_id, MPWPB_Booking_History::ACTION_RECURRING_ADDED, '', $datetime, __('Recurring appointment added by customer.', 'service-booking-manager'));
				foreach ((array) get_post_meta($new_id, 'mpwpb_extra_service_info', true) as $extra) {
					if (!is_array($extra) || empty($extra['ex_name'])) {
						continue;
					}
					$extra_data = array(
						'mpwpb_id' => get_post_meta($new_id, 'mpwpb_id', true),
						'mpwpb_date' => $datetime,
						'mpwpb_order_id' => get_post_meta($new_id, 'mpwpb_order_id', true),
						'mpwpb_order_status' => get_post_meta($new_id, 'mpwpb_order_status', true),
						'mpwpb_ex_name' => $extra['ex_name'],
						'mpwpb_ex_price' => $extra['ex_price'] ?? 0,
						'mpwpb_ex_qty' => $extra['ex_qty'] ?? 1,
						'mpwpb_payment_method' => get_post_meta($new_id, 'mpwpb_payment_method', true),
						'mpwpb_user_id' => get_post_meta($new_id, 'mpwpb_user_id', true),
					);
					MPWPB_Woocommerce::add_cpt_data('mpwpb_extra_service_booking', '#' . $extra_data['mpwpb_order_id'] . $extra_data['mpwpb_ex_name'] . '-' . $index, $extra_data);
				}
				return $new_id;
			}

			private function renumber_series(array $series): array {
				usort($series, static function($a, $b) { return strcmp((string) get_post_meta($a, 'mpwpb_date', true), (string) get_post_meta($b, 'mpwpb_date', true)); });
				$total = count($series);
				foreach ($series as $index => $booking_id) {
					update_post_meta($booking_id, 'mpwpb_recurring_index', $index + 1);
					update_post_meta($booking_id, 'mpwpb_recurring_total', $total);
				}
				return $series;
			}

			private function sync_extra_dates($order_id, $service_id, $old_datetime, $new_datetime): void {
				$ids = get_posts(array('post_type' => 'mpwpb_extra_service_booking', 'post_status' => 'any', 'posts_per_page' => -1, 'fields' => 'ids', 'meta_query' => array(
					array('key' => 'mpwpb_order_id', 'value' => $order_id), array('key' => 'mpwpb_id', 'value' => $service_id), array('key' => 'mpwpb_date', 'value' => $old_datetime),
				)));
				foreach ($ids as $id) {
					update_post_meta($id, 'mpwpb_date', $new_datetime);
				}
			}

			private function sync_wc_order($order_id, $service_id, array $series, $note): void {
				if (!function_exists('wc_get_order') || !($order = wc_get_order($order_id))) {
					return;
				}
				$item = null;
				foreach ($order->get_items() as $candidate) {
					if ((int) $candidate->get_meta('_mpwpb_id', true) === (int) $service_id) {
						$item = $candidate;
						break;
					}
				}
				if (!$item) {
					return;
				}
				$aggregate = 0;
				$dates = array();
				$breakdown = array();
				foreach ($series as $index => $booking_id) {
					$date = (string) get_post_meta($booking_id, 'mpwpb_date', true);
					$services = (array) get_post_meta($booking_id, 'mpwpb_service', true);
					$total = (float) get_post_meta($booking_id, 'mpwpb_tp', true);
					$aggregate += $total;
					$dates[] = $date;
					$breakdown[] = array('booking_id' => (int) $booking_id, 'date' => $date, 'services' => $services, 'total' => $total);
				}
				$remove_keys = array(MPWPB_Function::get_service_text($service_id), __('Price ', 'service-booking-manager'), __('Date ', 'service-booking-manager'), __('Time ', 'service-booking-manager'));
				foreach ($item->get_meta_data() as $meta) {
					if (in_array($meta->key, $remove_keys, true) || strpos((string) $meta->key, __('Appointment ', 'service-booking-manager')) === 0) {
						$item->delete_meta_data($meta->key);
					}
				}
				// Re-add after old appointment rows have been removed.
				foreach ($breakdown as $index => $occurrence) {
					$names = array_values(array_filter(array_map(static function($service) { return is_array($service) ? ($service['name'] ?? '') : ''; }, $occurrence['services'])));
					$item->add_meta_data(sprintf(__('Appointment %d', 'service-booking-manager'), $index + 1), sprintf('%s · %s — %s', MPWPB_Global_Function::date_format($occurrence['date']), MPWPB_Global_Function::date_format($occurrence['date'], 'time'), implode(', ', $names)), false);
				}
				$item->update_meta_data('_mpwpb_date', implode(',', $dates));
				$item->update_meta_data('_mpwpb_tp', round($aggregate, 2));
				$item->update_meta_data('_mpwpb_recurring_occurrences', $breakdown);
				// The parent order is the original payment receipt (and can contain
				// only a deposit). Never rewrite its charged totals after payment;
				// added cost is tracked as mpwpb_amount_due and collected through
				// the existing linked Pay Balance order instead.
				$item->save();
				$order->add_order_note($note, true);
				$order->save();
			}
		}
		new MPWPB_Recurring_Account_Manager();
	}
