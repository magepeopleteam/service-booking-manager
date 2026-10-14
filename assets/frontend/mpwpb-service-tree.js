/**
 * "Our services" sidebar tree — expand/collapse, plus a quick-add checkbox
 * on each leaf that mirrors the real selection state.
 *
 * Clicking a leaf's name/row still opens the booking popup and simulates
 * the matching real selection click inside it (assets/frontend/
 * mpwpb_registration.js, div.mpwpb_static .mpwpb_item_box handler) --
 * unchanged. The checkbox added here is a SEPARATE quick-add path: it
 * proxies straight to the real service's own .mpwpb_service_button
 * (relocated into the popup's tree by mpwpb-booking-tree.js) without
 * opening the popup. Quantity adjustment is popup-only -- no qty stepper
 * here, just add/remove -- so this file never creates new selection state
 * of its own, only triggers the real button and mirrors its resulting
 * .mActive state back onto the leaf.
 */
(function ($) {
	'use strict';

	var $trees = $('.mpwpb-service-tree');
	if (!$trees.length) {
		return;
	}

	$(document).on('click', '.mpwpb-service-tree [data-tree-toggle]', function (e) {
		e.stopPropagation();
		var $node = $(this).closest('.mpwpb-tree-cat, .mpwpb-tree-subcat');
		$node.toggleClass('is-open');
	});

	// All categories and sub-categories open by default so visitors can see
	// and book services from any category/sub-category at a glance, without
	// needing to tap through to expand each branch first.
	$trees.each(function () {
		$(this).find('.mpwpb-tree-cat, .mpwpb-tree-subcat').addClass('is-open');
	});

	function findRealServiceItem(serviceId) {
		// Scoped to the class mpwpb-booking-tree.js adds only to the one
		// real, relocated, visible instance -- avoids any duplicate/orphaned
		// copy of the same service left behind in a now-hidden container.
		return $('.mpwpb-tree-service-row[data-service="' + serviceId + '"]');
	}

	function syncLeaf($leaf) {
		var serviceId = $leaf.attr('data-service');
		if (!serviceId) {
			return;
		}
		var $real = findRealServiceItem(serviceId);
		var isActive = $real.length > 0 && $real.hasClass('mpActive');
		$leaf.toggleClass('mpActive', isActive);
		$leaf.find('[data-tree-tap-hint]').toggle(!isActive);
	}

	function syncAllLeaves() {
		$('.mpwpb-tree-service[data-service]').each(function () {
			syncLeaf($(this));
		});
	}

	// Live "N services selected · total" summary in the sidebar's CTA footer,
	// reading the same real relocated rows (data-price + the real qty input)
	// syncLeaf already reads from -- never a count/total kept separately, so
	// it can't drift from the real selection state.
	function updateSidebarSummary() {
		var $summary = $('[data-tree-summary]');
		if (!$summary.length) {
			return;
		}
		var i18n = window.mpwpbTreeI18n || {};
		var count = 0;
		var total = 0;
		$('.mpwpb-tree-service-row.mpActive').each(function () {
			var $row = $(this);
			var price = parseFloat($row.attr('data-price')) || 0;
			var qty = parseInt($row.find('input[name="mpwpb_service_qtt[]"]').val(), 10);
			if (!(qty > 0)) {
				qty = 1;
			}
			count++;
			total += price * qty;
		});
		if (count === 0) {
			$summary.hide();
			return;
		}
		var countLabel = count === 1
			? (i18n.oneServiceTxt || '1 service selected')
			: (i18n.manyServicesTxt || '%d services selected').replace('%d', count);
		$summary.find('[data-tree-summary-count]').text(countLabel);
		$summary.find('[data-tree-summary-total]').text((i18n.currencySymbol || '') + total.toFixed(2));
		$summary.show();
	}

	$(document).on('click', '.mpwpb-tree-service [data-tree-checkbox]', function (e) {
		e.stopPropagation();
		var $leaf = $(this).closest('.mpwpb-tree-service');
		var $real = findRealServiceItem($leaf.attr('data-service'));
		$real.find('.mpwpb_service_button').trigger('click');
	});

	// Re-sync every leaf whenever the real state changes anywhere (whether
	// triggered from this sidebar or from inside the popup itself).
	$(document).on('click', '.mpwpb_service_button, .service_incQty, .service_decQty', function () {
		syncAllLeaves();
		updateSidebarSummary();
	});

	syncAllLeaves();
	updateSidebarSummary();

})(jQuery);
