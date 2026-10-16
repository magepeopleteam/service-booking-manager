/**
 * Staff Management page -- split view (staff list + editor).
 *
 * Persistence, the schedule AJAX cascade (start -> end -> break selects),
 * staff load/delete, repeaters, the media uploader, the schedule-type pills
 * and "Copy Monday to all" all stay in mpwpb_admin.js / mp_global, unchanged.
 * The real form fields are still the source of truth; this file only:
 *  - filters the staff list and marks the one open in the editor,
 *  - wires "Add staff", "Remove staff" and "Cancel" to the existing handlers,
 *  - turns each weekday row into: on/off switch (writes the hidden weekly
 *    off-day checkboxes + mpwpb_off_days, as the old checkbox group did),
 *    "Usual hours · 10:00 AM – 06:00 PM" summary, and "Set different hours"
 *    to reveal that day's own selects,
 *  - hides the weekday rows for a "Specific dates" schedule.
 * Delegated off .mpwpb_staff_page (never replaced) because the editor and
 * the list are both re-rendered via AJAX.
 */
(function ($) {
	'use strict';

	var $page = $('.mpwpb_staff_page.mpwpb-sm');
	if (!$page.length) {
		return;
	}
	var t = window.mpwpbStaffScheduleI18n || {};

	function optText($select) {
		var $o = $select.find('option:selected');
		return $o.length && $o.val() !== '' ? $.trim($o.text()) : '';
	}

	/* ---------- Staff list ---------- */
	function markActive() {
		var id = String($('#mpwpb_user_id').val() || '');
		$page.find('.mpwpb-sm__item').each(function () {
			var on = id !== '' && String($(this).attr('data-staff-id')) === id;
			$(this).toggleClass('is-active', on).find('.mpwpb-sm__pick').attr('aria-current', on ? 'true' : null);
		});
	}
	$page.on('input', '[data-sm-search]', function () {
		var q = $.trim($(this).val()).toLowerCase(), shown = 0;
		$page.find('.mpwpb-sm__item').each(function () {
			var hit = !q || String($(this).attr('data-search') || '').indexOf(q) > -1;
			$(this).prop('hidden', !hit);
			if (hit) { shown++; }
		});
		$page.find('[data-sm-nomatch]').prop('hidden', shown > 0);
	});
	function openNew() {
		var $sel = $page.find('.mpwpb_user_select');
		if ($sel.length) {
			$sel.val(' ').trigger('change'); // existing handler loads an empty form
		}
	}
	$page.on('click', '[data-sm-add]', function () {
		openNew();
		var main = $page.find('.mpwpb-sm__main')[0];
		if (main && main.getBoundingClientRect().top > window.innerHeight) {
			main.scrollIntoView({ behavior: 'smooth', block: 'start' });
		}
	});
	$page.on('click', '[data-sm-remove]', function () {
		$page.find('.mpwpb_staff_list .mpwpb-sm__del[data-staff-id="' + $(this).attr('data-sm-remove') + '"]').trigger('click');
	});
	// Cancel: reload the open person (or an empty form), dropping unsaved edits.
	$page.on('click', '[data-sm-cancel]', function () {
		var id = String($('#mpwpb_user_id').val() || '');
		var $pick = id ? $page.find('.mpwpb-sm__pick[data-staff-id="' + id + '"]') : $();
		if ($pick.length) { $pick.trigger('click'); } else { openNew(); }
	});

	/* ---------- Working hours ---------- */
	function usualText() {
		var $row = $page.find('.mpwpb-sm__row.is-default');
		var s = optText($row.find('.mpwpb_start_time select')), e = optText($row.find('.mpwpb_end_time select'));
		var bs = optText($row.find('.mpwpb_start_break_time select')), be = optText($row.find('.mpwpb_end_break_time select'));
		var txt = (t.usual || 'Usual hours') + (s && e ? ' · ' + s + ' – ' + e : '');
		if (bs && be) { txt += ' · ' + (t.breakTxt || 'break') + ' ' + bs + ' – ' + be; }
		return txt;
	}
	function paintRows() {
		var usual = usualText();
		$page.find('.mpwpb-sm__row:not(.is-default)').each(function () {
			var $row = $(this);
			var off = $row.hasClass('mpwpb-staff-row-off');
			if (!$row.data('smInit')) {
				// A day with its own start time is "custom" from the start.
				$row.data('smInit', true).toggleClass('is-custom', $row.find('.mpwpb_start_time select').val() !== '');
			}
			var custom = $row.hasClass('is-custom');
			$row.find('[data-sm-summary]').text(off ? (t.dayOff || 'Day off') : (custom ? '' : usual));
			$row.find('[data-sm-custom]').text(custom ? (t.useUsual || 'Use usual hours') : (t.custom || 'Set different hours'));
			$row.find('[data-sm-day-toggle]').attr('aria-checked', off ? 'false' : 'true');
		});
	}
	function paintType() {
		var type = $page.find('select[name="mpwpb_date_type"]').val() || 'repeated';
		$page.find('.mpwpb-sm__hours').attr('data-sm-type', type);
		$page.find('.mpwpb_schedule_type_pill').each(function () {
			$(this).attr('aria-pressed', $(this).attr('data-schedule-type') === type ? 'true' : 'false');
		});
	}
	// Same value the shared groupCheckBox handler would build.
	function writeOffDays() {
		var $group = $page.find('.mpwpb-sm__offdays-weekly'), keys = [];
		$group.find('input[type="checkbox"]:checked').each(function () { keys.push($(this).attr('data-checked')); });
		$group.find('input[type="hidden"][name="mpwpb_off_days"]').val(keys.join(','));
	}
	$page.on('click', '[data-sm-day-toggle]', function () {
		var $row = $(this).closest('.mpwpb-sm__row');
		var day = $row.attr('data-sm-day');
		var off = !$row.hasClass('mpwpb-staff-row-off');
		$row.toggleClass('mpwpb-staff-row-off', off);
		$page.find('.mpwpb-sm__offdays-weekly input[data-checked="' + day + '"]').prop('checked', off);
		writeOffDays();
		paintRows();
	});
	$page.on('click', '[data-sm-custom]', function () {
		var $row = $(this).closest('.mpwpb-sm__row');
		if ($row.hasClass('is-custom')) {
			$row.removeClass('is-custom');
			// Back to "Default": the cascade re-renders this day's end/break selects.
			$row.find('.mpwpb_start_time select').val('').trigger('change');
		} else {
			$row.addClass('is-custom');
			$row.find('.mpwpb_start_time select').trigger('focus');
		}
		paintRows();
	});
	$page.on('change', '.mpwpb-sm__table select', function () { setTimeout(paintRows, 0); });
	$page.on('change', 'select[name="mpwpb_date_type"]', paintType);

	/* ---------- Re-init after AJAX re-renders ---------- */
	function init() {
		markActive();
		paintType();
		paintRows();
	}
	if (window.MutationObserver) {
		var main = $page.find('#mpwpb_staff_members_holder')[0];
		var list = $page.find('#mpwpb_staff_lists_holder')[0];
		if (main) { new MutationObserver(function () { setTimeout(init, 0); }).observe(main, { childList: true }); }
		if (list) {
			new MutationObserver(function () {
				// The person open in the editor was just removed -> empty form.
				var id = String($('#mpwpb_user_id').val() || '');
				if (id && !$page.find('.mpwpb-sm__item[data-staff-id="' + id + '"]').length) { openNew(); }
				$page.find('[data-sm-search]').trigger('input');
				markActive();
			}).observe(list, { childList: true });
		}
	}
	init();
})(jQuery);
