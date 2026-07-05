/**
 * Staff Management page — live behavior layered over the classic markup.
 * Persistence, the schedule AJAX cascade, tab switching, staff CRUD,
 * repeaters and profile image upload are all handled by the existing
 * mpwpb_admin.js / mp_global JS, completely unchanged.
 *
 * This file only adds: toggling an off-day pill live-marks the matching
 * Time Schedule row as "Closed" (mirroring the initial state already
 * computed server-side in MPWPB_Staff_Members::schedule_settings()).
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

	function syncRow(day, isOff) {
		$page.find('[data-day-name="' + day + '"]').closest('tr').toggleClass('mpwpb-staff-row-off', isOff);
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
