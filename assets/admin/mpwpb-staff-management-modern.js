/**
 * Staff Management page — live behavior layered over the classic markup.
 * Persistence, the schedule AJAX cascade, tab switching, staff CRUD,
 * repeaters and profile image upload are all handled by the existing
 * mpwpb_admin.js / mp_global JS, completely unchanged.
 *
 * This file only adds: toggling an off-day pill live-marks the matching
 * Time Schedule row as "Closed" (mirroring the initial state already
 * computed server-side in MPWPB_Staff_Members::schedule_settings()), and
 * keeps that row's ACTIVE/OFF status badge in sync with it — both are
 * driven by the same server-computed $is_off, so a live checkbox toggle
 * has to update both together or they visibly disagree.
 * Delegated off .mpwpb_staff_page (never replaced) rather than the form
 * itself, since editing a different staff member reloads the form's
 * inner HTML via AJAX (load_staff_form() in mpwpb_admin.js).
 */
(function ($) {
	'use strict';

	var $page = $('.mpwpb_staff_page');
	if (!$page.length) {
		return;
	}

	var i18n = window.mpwpbStaffScheduleI18n || { active: 'ACTIVE', off: 'OFF' };

	function syncRow(day, isOff) {
		var $row = $page.find('[data-day-name="' + day + '"]').closest('tr');
		$row.toggleClass('mpwpb-staff-row-off', isOff);
		$row.find('.mpwpb_staff_status_badge')
			.toggleClass('is-off', isOff)
			.toggleClass('is-active', !isOff)
			.text(isOff ? i18n.off : i18n.active);
	}

	$page.on('click', '.groupCheckBox .customCheckboxLabel', function () {
		var $cb = $(this).find('input[type="checkbox"]');
		var day = $cb.attr('data-checked');
		if (!day) {
			return;
		}
		// Deferred to the next tick so the native checkbox toggle (and the
		// shared groupCheckBox handler) have already updated .checked.
		setTimeout(function () {
			syncRow(day, $cb.is(':checked'));
		}, 0);
	});

})(jQuery);
