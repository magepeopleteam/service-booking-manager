/**
 * Date & Time (Availability step) — live behavior for the card layout.
 * Persistence is entirely the existing classic mechanism (shared
 * mpwpb_settings_save handler + mpwpb_plugin_global.js's collapse /
 * groupCheckBox / sortable-repeater handlers); this file only adds the
 * visual feedback that's new to the card layout:
 *  - toggling a "Weekly Off-days" pill marks/unmarks the matching Weekly
 *    Schedule row as closed and updates the "X / 7 Days Open" badge.
 *  - showing/hiding the "No special dates yet" placeholder as rows are
 *    added/removed from the Particular Dates / Special Dates repeaters.
 */
(function ($) {
	'use strict';

	var $root = $('#mpwpb-dtm');
	if (!$root.length) {
		return;
	}

	function syncRow(day, isOff) {
		$root.find('.mpwpb-dtm__table [data-day-name="' + day + '"]').closest('tr').toggleClass('mpwpb-dtm-row-off', isOff);
	}

	function updateBadge() {
		var $pills = $root.find('[data-dtm-offday-pill]');
		var total = $pills.length;
		if (!total) {
			return;
		}
		var offCount = $pills.find('input:checked').length;
		var label = (window.mpwpbDtm && mpwpbDtm.daysOpenLabel) || 'Days Open';
		$root.find('[data-dtm-open-badge]').text((total - offCount) + ' / ' + total + ' ' + label);
	}

	$root.on('click', '[data-dtm-offday-pill]', function () {
		var $pill = $(this);
		var $cb = $pill.find('input[type="checkbox"]');
		var day = $cb.attr('data-checked');
		// Deferred to the next tick so the native checkbox toggle (and the
		// shared groupCheckBox handler) have already updated .checked.
		setTimeout(function () {
			syncRow(day, $cb.is(':checked'));
			updateBadge();
		}, 0);
	});

	/* Empty-state placeholder for the date-card repeaters. */
	$root.on('click', '.mp_add_item', function () {
		$(this).closest('.mp_settings_area').find('.mpwpb-dtm__empty').hide();
	});
	$root.on('click', '.mpwpb_item_remove', function () {
		var $area = $(this).closest('.mp_settings_area');
		setTimeout(function () {
			if ($area.length && !$area.find('.mpwpb-dtm__date-card').length) {
				$area.find('.mpwpb-dtm__empty').show();
			}
		}, 300);
	});

})(jQuery);
