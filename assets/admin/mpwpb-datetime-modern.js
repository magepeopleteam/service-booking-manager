/**
 * Date & Time (Availability step) — live behavior for the card layout.
 * Persistence is entirely the existing classic mechanism (shared
 * mpwpb_settings_save handler + mpwpb_plugin_global.js's collapse /
 * groupCheckBox / sortable-repeater handlers); this file adds the new
 * interactive layer on top of it:
 *  - a per-row toggle switch (injected here, not real form data) that
 *    forwards clicks to the real, off-screen "Weekly Off-days" checkbox
 *    for that day — the single source of truth for mpwpb_off_days stays
 *    untouched.
 *  - the Schedule type segmented control and Time Slot Length pill group
 *    each drive their own real (off-screen) <select>, via .val()+
 *    trigger('change'), so the existing data-collapse-target cascade and
 *    the slot-length custom-input reveal (mpwpb_admin.js) keep working.
 *  - a capacity +/- stepper wrapping the real mpwpb_capacity_per_session
 *    <input>, and a "Copy Monday to all days" button.
 *  - a live "Customers will see" slot preview for Monday, using the same
 *    slot-generation loop as the real booking engine
 *    (inc/MPWPB_Function.php::get_time_slot()) ported to JS.
 */
(function ($) {
	'use strict';

	var $root = $('#mpwpb-dtm');
	if (!$root.length) {
		return;
	}

	/* ---------------------------------------------------------------- *
	 *  Per-row toggle switches, proxying the real off-day checkboxes
	 * ---------------------------------------------------------------- */
	function syncRow(day, isOff) {
		$root.find('.mpwpb-dtm__table--rows [data-day-name="' + day + '"]').closest('tr').toggleClass('mpwpb-dtm-row-off', isOff);
	}

	function updateOpenSub() {
		var $realPills = $root.find('[data-dtm-offday-pill]');
		var total = $realPills.length;
		if (!total) {
			return;
		}
		var offCount = $realPills.find('input:checked').length;
		var openCount = total - offCount;
		var label = openCount === 1
			? ((window.mpwpbDtm && mpwpbDtm.dayOpenLabel) || 'day open')
			: ((window.mpwpbDtm && mpwpbDtm.daysOpenLabel2) || 'days open');
		$root.find('[data-dtm-open-sub]').text(openCount + ' ' + label);
	}

	function injectRowToggles() {
		$root.find('.mpwpb-dtm__table--rows tbody tr').each(function () {
			var $tr = $(this);
			var $dayCell = $tr.find('[data-day-name]');
			if (!$dayCell.length) {
				return; // the "Default" row has no data-day-name cell -- no toggle
			}
			var day = $dayCell.data('day-name');
			var $realCb = $root.find('[data-dtm-offday-pill] input[data-checked="' + day + '"]');
			if (!$realCb.length) {
				return;
			}
			var isOpen = !$realCb.is(':checked');
			var $toggle = $(
				'<label class="mpwpb-dtm__row-toggle">' +
					'<input type="checkbox">' +
					'<span class="mpwpb-dtm__row-toggle-track" aria-hidden="true"></span>' +
				'</label>'
			);
			$toggle.find('input').prop('checked', isOpen).attr('aria-label', day);
			$tr.find('td:first-child').prepend($toggle);
			syncRow(day, !isOpen);
		});
	}

	$root.on('change', '.mpwpb-dtm__row-toggle input', function () {
		var $cb = $(this);
		var day = $cb.attr('aria-label');
		var isOpen = $cb.is(':checked');
		var $realCb = $root.find('[data-dtm-offday-pill] input[data-checked="' + day + '"]');
		if ($realCb.is(':checked') === isOpen) {
			// .trigger('click') on a checkbox also runs the browser's native
			// click activation, which flips `checked` a second time on top
			// of what .prop() just set -- the classic groupCheckBox handler
			// (which builds the saved mpwpb_off_days value) reads the
			// checkbox before that native flip, so the SAVE is correct, but
			// the checkbox's own live `checked` is left inverted afterward.
			// Re-set it back so any code in this same session (e.g. the
			// calendar's live "weekly closed" marking below) reads the
			// correct value, not the ghost-toggled one.
			$realCb.prop('checked', !isOpen).trigger('click');
			$realCb.prop('checked', !isOpen);
		}
		syncRow(day, !isOpen);
		updateOpenSub();
		schedulePreview();
		if (offCal) {
			renderOffCalendar(); // a day's weekly open/closed state marks matching dates on the calendar too
		}
	});

	/* ---------------------------------------------------------------- *
	 *  Break: a compact "+ Add break" link, or a pill once both break
	 *  times are set, in place of two always-visible <select>s — this is
	 *  what actually keeps the row narrow enough to never need the
	 *  horizontal scrollbar. The two real <select>s stay in the DOM and
	 *  still submit normally; only their visibility toggles.
	 *
	 *  The classic admin cascade (mpwpb_admin.js) replaces the whole
	 *  <select> inside the start_break_time/end_break_time <td> with a
	 *  fresh one via AJAX whenever a sibling field (start_time, end_time,
	 *  start_break_time) changes — even when the user never touched the
	 *  break fields. So every helper here re-queries the selects fresh
	 *  from $tr every time instead of caching a jQuery reference, and a
	 *  MutationObserver re-syncs the slot UI whenever that swap happens.
	 * ---------------------------------------------------------------- */
	function formatBreakRange($startSel, $endSel) {
		var startMin = Math.round(parseFloat($startSel.val()) * 60);
		var endMin = Math.round(parseFloat($endSel.val()) * 60);
		return formatClock(startMin) + '–' + formatClock(endMin);
	}
	function openBreakEditing($tr) {
		var $slot = $tr.find('.mpwpb-dtm__break-slot');
		$slot.data('editing', true);
		$tr.find('select[name$="_start_break_time"]').closest('td').show();
		$tr.find('select[name$="_end_break_time"]').closest('td').show();
		$slot.hide();
		$tr.find('select[name$="_start_break_time"]').trigger('focus');
	}
	function renderBreakSlot($slot, $tr) {
		var $startSel = $tr.find('select[name$="_start_break_time"]');
		var $endSel = $tr.find('select[name$="_end_break_time"]');
		$slot.empty();
		if ($startSel.val() !== '' && $endSel.val() !== '') {
			var $remove = $('<span aria-hidden="true">×</span>')
				.attr('title', (window.mpwpbDtm && mpwpbDtm.removeBreakTxt) || 'Remove break')
				.on('click', function (e) {
					e.stopPropagation();
					var $t = $(this).closest('tr');
					$t.find('select[name$="_start_break_time"]').val('').trigger('change');
					$t.find('select[name$="_end_break_time"]').val('').trigger('change');
				});
			$('<button type="button" class="mpwpb-dtm__break-pill"></button>')
				.attr('aria-label', (window.mpwpbDtm && mpwpbDtm.editBreakTxt) || 'Edit break')
				.append(document.createTextNode(formatBreakRange($startSel, $endSel) + ' '))
				.append($remove)
				.on('click', function () {
					openBreakEditing($(this).closest('tr'));
				})
				.appendTo($slot);
		} else {
			$('<button type="button" class="mpwpb-dtm__break-add"></button>')
				.text((window.mpwpbDtm && mpwpbDtm.addBreakTxt) || '+ Add break')
				.on('click', function () {
					openBreakEditing($(this).closest('tr'));
				})
				.appendTo($slot);
		}
	}
	function syncBreakSlot($tr) {
		var $startSel = $tr.find('select[name$="_start_break_time"]');
		var $endSel = $tr.find('select[name$="_end_break_time"]');
		var $slot = $tr.find('.mpwpb-dtm__break-slot');
		if (!$startSel.length || !$endSel.length || !$slot.length) {
			return;
		}
		var bothFilled = $startSel.val() !== '' && $endSel.val() !== '';
		if (bothFilled || !$slot.data('editing')) {
			$slot.data('editing', false);
			$startSel.closest('td').hide();
			$endSel.closest('td').hide();
			$slot.show();
		}
		renderBreakSlot($slot, $tr);
	}
	function injectBreakControls() {
		$root.find('.mpwpb-dtm__table--rows tbody tr').each(function () {
			var $tr = $(this);
			var $startSel = $tr.find('select[name$="_start_break_time"]');
			var $endSel = $tr.find('select[name$="_end_break_time"]');
			if (!$startSel.length || !$endSel.length) {
				return;
			}
			var $slot = $('<td class="mpwpb-dtm__break-slot"></td>');
			$startSel.closest('td').before($slot);
			syncBreakSlot($tr);

			if (window.MutationObserver) {
				var observer = new MutationObserver(function () {
					syncBreakSlot($tr);
					schedulePreview();
				});
				observer.observe($startSel.closest('td').get(0), { childList: true });
				observer.observe($endSel.closest('td').get(0), { childList: true });
			}
		});
	}
	$root.on('change', 'select[name$="_start_break_time"], select[name$="_end_break_time"]', function () {
		var $tr = $(this).closest('tr');
		syncBreakSlot($tr);
		schedulePreview();
	});

	/* ---------------------------------------------------------------- *
	 *  "Copy Monday to all days"
	 * ---------------------------------------------------------------- */
	$root.on('click', '[data-dtm-copy-monday]', function () {
		var fields = ['start_time', 'end_time', 'start_break_time', 'end_break_time'];
		var days = ['tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
		fields.forEach(function (field) {
			var val = $root.find('[name="mpwpb_monday_' + field + '"]').val();
			days.forEach(function (day) {
				// trigger('change') so each day's break pill/link (and the
				// live preview) refreshes to reflect the copied values.
				$root.find('[name="mpwpb_' + day + '_' + field + '"]').val(val).trigger('change');
			});
		});
		schedulePreview();
	});

	/* ---------------------------------------------------------------- *
	 *  Schedule type segmented control -> real (off-screen) select
	 * ---------------------------------------------------------------- */
	$root.on('click', '[data-dtm-schedule-value]', function () {
		var $btn = $(this);
		var value = $btn.data('dtm-schedule-value');
		$root.find('[data-dtm-schedule-type] .mpwpb-dtm__segmented-btn').removeClass('is-active');
		$btn.addClass('is-active');
		$root.find('select[name="mpwpb_date_type"]').val(value).trigger('change');
	});

	/* ---------------------------------------------------------------- *
	 *  Time Slot Length pill group -> real (off-screen) select
	 * ---------------------------------------------------------------- */
	$root.on('click', '[data-dtm-slot-value]', function () {
		var $btn = $(this);
		var value = $btn.data('dtm-slot-value');
		$root.find('[data-dtm-slot-pills] .mpwpb-dtm__pillgroup-btn').removeClass('is-active');
		$btn.addClass('is-active');
		$root.find('.mpwpb-slot-length-select').val(String(value)).trigger('change');
		schedulePreview();
	});
	// The custom-length <input> isn't covered by the pill click above.
	$root.on('input', '.mpwpb-slot-length-custom', function () {
		schedulePreview();
	});

	/* ---------------------------------------------------------------- *
	 *  Capacity +/- stepper
	 * ---------------------------------------------------------------- */
	$root.on('click', '[data-dtm-capacity-minus], [data-dtm-capacity-plus]', function () {
		var $input = $root.find('input[name="mpwpb_capacity_per_session"]');
		var val = parseInt($input.val(), 10);
		if (isNaN(val)) {
			val = 1;
		}
		val += $(this).is('[data-dtm-capacity-plus]') ? 1 : -1;
		if (val < 1) {
			val = 1;
		}
		$input.val(val).trigger('change');
	});

	/* ---------------------------------------------------------------- *
	 *  "Customers will see" — live slot preview for Monday, using the
	 *  same loop as MPWPB_Function::get_time_slot() (inc/MPWPB_Function.
	 *  php): step from start to end in slot-length increments, dropping
	 *  any slot that overlaps the break window, stopping once a slot's
	 *  end would exceed the day's end time. Values here are decimal
	 *  hours-since-midnight (the real <select>s' own value format,
	 *  e.g. "16" = 4:00 PM) converted to minutes for the loop.
	 * ---------------------------------------------------------------- */
	function fieldVal(day, field) {
		return $root.find('[name="mpwpb_' + day + '_' + field + '"]').val();
	}
	function resolveMinutes(day, field) {
		var own = fieldVal(day, field);
		var effective = (own === '' || own === undefined) ? fieldVal('default', field) : own;
		if (effective === '' || effective === undefined || effective === null) {
			return null;
		}
		var hours = parseFloat(effective);
		return isNaN(hours) ? null : Math.round(hours * 60);
	}
	function currentSlotLengthMinutes() {
		var selectVal = $root.find('.mpwpb-slot-length-select').val();
		if (selectVal === 'custom') {
			var custom = parseInt($root.find('.mpwpb-slot-length-custom').val(), 10);
			return custom > 0 ? custom : 0;
		}
		var minutes = parseInt(selectVal, 10);
		return minutes > 0 ? minutes : 0;
	}
	function formatClock(totalMinutes) {
		var h = Math.floor(totalMinutes / 60) % 24;
		var m = totalMinutes % 60;
		var period = h < 12 ? 'AM' : 'PM';
		var h12 = h % 12;
		if (h12 === 0) {
			h12 = 12;
		}
		var mm = (m < 10 ? '0' : '') + m;
		return h12 + ':' + mm + ' ' + period;
	}
	function formatDuration(minutes) {
		if (minutes % 60 === 0) {
			var hrs = minutes / 60;
			return hrs === 1 ? '1 hour' : (hrs + ' hours');
		}
		if (minutes < 60) {
			return minutes + 'm';
		}
		return Math.floor(minutes / 60) + 'h ' + (minutes % 60) + 'm';
	}

	var previewTimer = null;
	function schedulePreview() {
		clearTimeout(previewTimer);
		previewTimer = setTimeout(renderPreview, 80);
	}

	function renderPreview() {
		var $sub = $root.find('[data-dtm-preview-sub]');
		var $slots = $root.find('[data-dtm-preview-slots]');
		$slots.empty();

		var $mondayToggle = $root.find('.mpwpb-dtm__table--rows [data-day-name="monday"]').closest('tr').find('.mpwpb-dtm__row-toggle input');
		if ($mondayToggle.length && !$mondayToggle.is(':checked')) {
			$sub.text((window.mpwpbDtm && mpwpbDtm.mondayClosedTxt) || 'Monday is closed');
			return;
		}

		var start = resolveMinutes('monday', 'start_time');
		var end = resolveMinutes('monday', 'end_time');
		var breakStart = resolveMinutes('monday', 'start_break_time');
		var breakEnd = resolveMinutes('monday', 'end_break_time');
		var slotLength = currentSlotLengthMinutes();
		var hasBreak = breakStart !== null && breakEnd !== null;

		if (start === null || end === null || !slotLength) {
			$sub.text((window.mpwpbDtm && mpwpbDtm.noSlotsTxt) || 'No slots available');
			return;
		}

		var slots = [];
		for (var i = start; i + slotLength <= end; i += slotLength) {
			var slotEnd = i + slotLength;
			var overlapsBreak = hasBreak && i < breakEnd && slotEnd > breakStart;
			if (!overlapsBreak) {
				slots.push(i);
			}
		}

		if (!slots.length) {
			$sub.text((window.mpwpbDtm && mpwpbDtm.noSlotsTxt) || 'No slots available');
			return;
		}

		var monday = (window.mpwpbDtm && mpwpbDtm.mondayTxt) || 'Monday';
		var slotsOf = (window.mpwpbDtm && mpwpbDtm.slotsOfTxt) || 'slots of';
		$sub.text(monday + ' · ' + slots.length + ' ' + slotsOf + ' ' + formatDuration(slotLength));
		slots.forEach(function (minutes) {
			$('<span class="mpwpb-dtm__preview-slot"></span>').text(formatClock(minutes)).appendTo($slots);
		});
	}

	// Recompute whenever anything the preview depends on changes.
	$root.on('change', '[name^="mpwpb_monday_"], [name^="mpwpb_default_"], .mpwpb-slot-length-select', schedulePreview);

	/* Empty-state placeholder for the Particular Dates repeater (still a
	   real mp_add_item/mpwpb_item_remove list -- Special Dates & Holidays
	   below is its own calendar now, not this repeater). */
	$root.on('click', '.mp_add_item', function () {
		$(this).closest('.mp_settings_area').find('.mpwpb-dtm__empty').hide();
	});
	$root.on('click', '.mpwpb_item_remove', function () {
		var $area = $(this).closest('.mp_settings_area');
		setTimeout(function () {
			if (!$area.find('.mp_item_insert > div').length) {
				$area.find('.mpwpb-dtm__empty').show();
			}
		}, 0);
	});

	/* ---------------------------------------------------------------- *
	 *  Special Dates & Holidays — an inline multi-select month calendar
	 *  in place of the old "Add Special Date" button + one-card-per-date
	 *  list. A hand-built grid rather than jQuery UI Datepicker: that
	 *  widget's single-selected-day model fights a "many days can be
	 *  toggled independently" use case, so this avoids fighting it.
	 *  Submission contract is unchanged -- one real
	 *  <input type="hidden" name="mpwpb_off_dates[]" value="Y-m-d"> per
	 *  selected date, the same shape the old repeater produced, just
	 *  built here instead of by mp_add_item/particular_date_item().
	 * ---------------------------------------------------------------- */
	var MPWPB_MONTH_NAMES = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
	var MPWPB_DOW_NAMES = ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'];
	var MPWPB_DOW_SHORT = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
	var MPWPB_DAY_TO_DOW = { sunday: 0, monday: 1, tuesday: 2, wednesday: 3, thursday: 4, friday: 5, saturday: 6 };
	var offCal = null; // { viewYear, viewMonth (0-11), selected: {'Y-m-d': true} }

	/** Reads the real (possibly just-toggled) weekly off-day checkboxes live, so the calendar always reflects the current state, not just what was saved on load. */
	function getWeeklyOffDows() {
		var dows = {};
		$root.find('[data-dtm-offday-pill] input[data-checked]').each(function () {
			var $cb = $(this);
			if ($cb.is(':checked')) {
				var dow = MPWPB_DAY_TO_DOW[$cb.attr('data-checked')];
				if (dow !== undefined) {
					dows[dow] = true;
				}
			}
		});
		return dows;
	}

	function pad2(n) {
		return (n < 10 ? '0' : '') + n;
	}
	function dateKey(y, m, d) {
		return y + '-' + pad2(m + 1) + '-' + pad2(d);
	}
	function formatOffDateLabel(key) {
		var parts = key.split('-');
		var y = parseInt(parts[0], 10);
		var m = parseInt(parts[1], 10) - 1;
		var d = parseInt(parts[2], 10);
		var dow = new Date(y, m, d).getDay();
		return MPWPB_DOW_SHORT[dow] + ', ' + MPWPB_MONTH_NAMES[m].slice(0, 3) + ' ' + d + ', ' + y;
	}
	function renderOffList() {
		var $list = $root.find('[data-dtm-off-list]');
		$list.empty();
		var keys = Object.keys(offCal.selected).sort();
		if (!keys.length) {
			$('<p class="mpwpb-dtm__off-list-empty"></p>')
				.text((window.mpwpbDtm && mpwpbDtm.offListEmptyTxt) || 'No dates marked closed yet.')
				.appendTo($list);
			return;
		}
		keys.forEach(function (key) {
			var $item = $('<div class="mpwpb-dtm__off-list-item"></div>');
			$('<span class="mpwpb-dtm__off-list-icon" aria-hidden="true"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"></rect><line x1="3" y1="10" x2="21" y2="10"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="16" y1="2" x2="16" y2="6"></line></svg></span>')
				.appendTo($item);
			$('<span class="mpwpb-dtm__off-list-text"></span>').text(formatOffDateLabel(key)).appendTo($item);
			$('<button type="button" class="mpwpb-dtm__off-list-remove"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg></button>')
				.attr('aria-label', ((window.mpwpbDtm && mpwpbDtm.removeDateTxt) || 'Remove') + ' ' + formatOffDateLabel(key))
				.on('click', function () {
					delete offCal.selected[key];
					syncOffDateFields();
					renderOffCalendar(); // un-highlight the day if its month is the one currently shown
				})
				.appendTo($item);
			$list.append($item);
		});
	}
	function syncOffDateFields() {
		var $fields = $root.find('[data-dtm-off-dates-fields]');
		$fields.empty();
		Object.keys(offCal.selected).sort().forEach(function (key) {
			$('<input type="hidden" name="mpwpb_off_dates[]">').val(key).appendTo($fields);
		});
		var count = Object.keys(offCal.selected).length;
		var label = count === 1
			? ((window.mpwpbDtm && mpwpbDtm.specialDateLabel) || 'date')
			: ((window.mpwpbDtm && mpwpbDtm.specialDatesLabel) || 'dates');
		$root.find('[data-dtm-off-cal-summary]').text(count + ' ' + label);
		renderOffList();
	}
	function renderOffCalendar() {
		var $mount = $root.find('[data-dtm-off-calendar]');
		if (!$mount.length) {
			return;
		}
		$mount.empty();

		var $head = $('<div class="mpwpb-dtm__cal-head"></div>').appendTo($mount);
		$('<button type="button" class="mpwpb-dtm__cal-nav" aria-label="Previous month">&lsaquo;</button>')
			.on('click', function () {
				offCal.viewMonth--;
				if (offCal.viewMonth < 0) {
					offCal.viewMonth = 11;
					offCal.viewYear--;
				}
				renderOffCalendar();
			})
			.appendTo($head);
		$('<div class="mpwpb-dtm__cal-label"></div>').text(MPWPB_MONTH_NAMES[offCal.viewMonth] + ' ' + offCal.viewYear).appendTo($head);
		$('<button type="button" class="mpwpb-dtm__cal-nav" aria-label="Next month">&rsaquo;</button>')
			.on('click', function () {
				offCal.viewMonth++;
				if (offCal.viewMonth > 11) {
					offCal.viewMonth = 0;
					offCal.viewYear++;
				}
				renderOffCalendar();
			})
			.appendTo($head);

		var $grid = $('<div class="mpwpb-dtm__cal-grid"></div>').appendTo($mount);
		MPWPB_DOW_NAMES.forEach(function (d) {
			$('<div class="mpwpb-dtm__cal-dow"></div>').text(d).appendTo($grid);
		});

		var startOffset = new Date(offCal.viewYear, offCal.viewMonth, 1).getDay();
		var daysInMonth = new Date(offCal.viewYear, offCal.viewMonth + 1, 0).getDate();
		var weeklyOffDows = getWeeklyOffDows();
		var today = new Date();
		var todayKey = dateKey(today.getFullYear(), today.getMonth(), today.getDate());
		for (var i = 0; i < startOffset; i++) {
			$('<div class="mpwpb-dtm__cal-blank"></div>').appendTo($grid);
		}
		for (var d = 1; d <= daysInMonth; d++) {
			var key = dateKey(offCal.viewYear, offCal.viewMonth, d);
			var isWeekOff = !!weeklyOffDows[new Date(offCal.viewYear, offCal.viewMonth, d).getDay()];
			var cls = 'mpwpb-dtm__cal-day'
				+ (offCal.selected[key] ? ' is-off' : '')
				+ (isWeekOff ? ' is-week-off' : '')
				+ (key === todayKey ? ' is-today' : '');
			$('<button type="button" class="' + cls + '"></button>')
				.text(d)
				.attr('data-dtm-cal-date', key)
				.attr('title', isWeekOff ? ((window.mpwpbDtm && mpwpbDtm.weeklyClosedTxt) || 'Closed every week') : '')
				.appendTo($grid);
		}

		// Legend -- explains the is-off (custom) vs is-week-off (regularly
		// closed) colors, and marks which cell is today, none of which was
		// self-evident from the grid alone.
		var $legend = $('<div class="mpwpb-dtm__cal-legend"></div>').appendTo($mount);
		var legendItems = [
			['mpwpb-dtm__cal-legend-swatch is-off', (window.mpwpbDtm && mpwpbDtm.legendCustomTxt) || 'Closed (custom)'],
			['mpwpb-dtm__cal-legend-swatch is-week-off', (window.mpwpbDtm && mpwpbDtm.legendWeeklyTxt) || 'Closed weekly'],
			['mpwpb-dtm__cal-legend-swatch is-today', (window.mpwpbDtm && mpwpbDtm.legendTodayTxt) || 'Today']
		];
		legendItems.forEach(function (item) {
			var $row = $('<div class="mpwpb-dtm__cal-legend-item"></div>').appendTo($legend);
			$('<span class="' + item[0] + '" aria-hidden="true"></span>').appendTo($row);
			$('<span></span>').text(item[1]).appendTo($row);
		});
	}
	$root.on('click', '[data-dtm-cal-date]', function () {
		if ($(this).hasClass('is-week-off')) {
			return; // already closed every week -- nothing to toggle
		}
		var key = $(this).data('dtm-cal-date');
		if (offCal.selected[key]) {
			delete offCal.selected[key];
		} else {
			offCal.selected[key] = true;
		}
		$(this).toggleClass('is-off');
		syncOffDateFields();
	});
	function initOffCalendar() {
		var $mount = $root.find('[data-dtm-off-calendar]');
		if (!$mount.length) {
			return;
		}
		var initialDates = [];
		try {
			initialDates = JSON.parse($mount.attr('data-dtm-off-dates') || '[]');
		} catch (e) {}
		var today = new Date();
		offCal = { viewYear: today.getFullYear(), viewMonth: today.getMonth(), selected: {} };
		initialDates.forEach(function (d) {
			if (d) {
				offCal.selected[d] = true;
			}
		});
		renderOffCalendar();
		syncOffDateFields();
	}

	injectRowToggles();
	injectBreakControls();
	updateOpenSub();
	renderPreview();
	initOffCalendar();
})(jQuery);
