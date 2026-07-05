/**
 * Booking popup -> unified checkbox-tree picker.
 *
 * The old two-stage flow (category-selection.php cards -> service-selection.php
 * flat list with Add buttons) is left completely intact in the DOM -- it is
 * just hidden via CSS (see mpwpb-service-page-modern.css). This module reads
 * that already-rendered, already-correct markup once on load and relocates
 * (never clones) each real .mpwpb_service_item into a freshly built tree of
 * category/sub-category header rows, grouped by the item's own existing
 * data-category/data-sub-category attributes.
 *
 * Every relocated node keeps its real id/class/data attributes and hidden
 * mpwpb_service[]/mpwpb_service_qtt[] inputs, so the existing delegated click
 * handlers in mpwpb_registration.js (.mpwpb_service_button, .service_incQty/
 * .service_decQty, .mpwpb_service_button_remove) keep working completely
 * unchanged -- this file adds no new selection/cart/checkout logic, only
 * DOM layout + a live selected/total count display.
 */
(function ($) {
	'use strict';

	function relocateService($item, $target) {
		$item.appendTo($target).addClass('mpwpb-tree-service-row');
		// Move the real Add/checkbox button to the front of the row (before
		// the name) -- same button, same delegated click handler in
		// mpwpb_registration.js, just repositioned in the DOM so it reads
		// left-to-right as [checkbox] [name/duration] ... [price].
		var $flex = $item.find('> ._dFlex').first();
		var $btn = $item.find('> ._dFlex .mpwpb_service_button').first();
		if ($flex.length && $btn.length) {
			$flex.prepend($btn);
		}

		// "View Details" used to expand its description inline via the
		// generic [data-collapse-target] mechanism (mp_global/assets/
		// mp_style/mpwpb_plugin_global.js) -- inside this compact row that
		// grew the row awkwardly and broke the list layout. Detach it from
		// that generic handler (remove the attribute it matches on) and,
		// rather than any click action, surface the same text as a small
		// CSS-driven tooltip on hover (mpwpb-service-page-modern.css,
		// .mpwpb-tree-view-details:hover::after) -- a real title attribute
		// was tried first but native tooltips are slow/inconsistent and
		// don't work at all on touch devices, so this reads the text off
		// data-tooltip via CSS attr() instead, fully within our control.
		var $trigger = $item.find('[data-collapse-target]').first();
		if ($trigger.length) {
			// Scoped to a direct child of $item (matching the real markup:
			// [data-collapse] always sits as $item's own last child, sibling
			// of ._dFlex) rather than a global [data-collapse="<id>"] lookup
			// -- more robust, and doesn't depend on the id string matching
			// correctly across the whole document.
			var $desc = $item.children('[data-collapse]').first();
			$trigger
				.removeAttr('data-collapse-target')
				.addClass('mpwpb-tree-view-details')
				.attr('data-tooltip', $desc.length ? $.trim($desc.text()) : '');
		}
	}

	function buildRow(kind, name) {
		var iconHtml = kind === 'cat'
			? '<i class="fas fa-folder mpwpb-tree-folder-icon"></i>'
			: '<span class="mpwpb-tree-corner"></span>';
		var $wrap = $(
			'<div class="mpwpb-tree-' + (kind === 'cat' ? 'cat' : 'subcat') + '">' +
				'<div class="mpwpb-tree-row mpwpb-tree-' + (kind === 'cat' ? 'cat' : 'subcat') + '-row" data-tree-toggle>' +
					iconHtml +
					'<span class="mpwpb-tree-name"></span>' +
					'<span class="mpwpb-tree-count"></span>' +
					'<i class="fas fa-chevron-down mpwpb-tree-chevron"></i>' +
				'</div>' +
				'<div class="mpwpb-tree-children' + (kind === 'cat' ? '' : ' mpwpb-tree-children--sub') + '"></div>' +
			'</div>'
		);
		$wrap.find('.mpwpb-tree-name').text(name);
		return $wrap;
	}

	function updateBranchCount($branch) {
		var $children = $branch.find('> .mpwpb-tree-children');
		var $items = $children.find('.mpwpb_service_item');
		var total = $items.length;
		var selected = $items.filter('.mpActive').length;
		$branch.find('> .mpwpb-tree-row .mpwpb-tree-count').text(selected > 0 ? (selected + ' / ' + total) : total);
	}

	function updateAllCounts($tree) {
		$tree.find('.mpwpb-tree-cat, .mpwpb-tree-subcat').each(function () {
			updateBranchCount($(this));
		});
	}

	function updateDecIcon($row) {
		var qty = parseInt($row.find('.inputIncDec').first().val(), 10) || 1;
		var $icon = $row.find('.service_decQty .fas').first();
		if (!$icon.length) {
			return;
		}
		$icon.toggleClass('fa-minus', qty > 1).toggleClass('fa-times', qty <= 1);
	}

	function updateAllDecIcons() {
		$('.mpwpb-tree-service-row').each(function () {
			updateDecIcon($(this));
		});
	}

	function updateSelectedFooter($registration) {
		var count = $registration.find('.mpwpb_service_item.mpActive').length;
		var $label = $registration.find('.mpwpb-tree-selected-label');
		if (!$label.length) {
			return;
		}
		if (count > 0) {
			$label.text(count + (count === 1 ? ' service selected' : ' services selected')).show();
		} else {
			$label.text('').hide();
		}
	}

	function relabelContinueButton($registration) {
		var $btn = $registration.find('.mpwpb_service_next');
		if (!$btn.length || $btn.data('mpwpbRelabelled')) {
			return;
		}
		$btn.data('mpwpbRelabelled', true);
		var $textNode = $btn.contents().filter(function () {
			return this.nodeType === 3 && $.trim(this.data) !== '';
		}).first();
		if ($textNode.length) {
			$textNode[0].data = ' Continue to schedule ';
		}
		var $h3 = $btn.closest('.justifyBetween').find('h3').first();
		if ($h3.length && !$h3.find('.mpwpb-tree-selected-label').length) {
			$h3.prepend('<span class="mpwpb-tree-selected-label"></span>');
		}
	}

	function initBookingTree($registration) {
		var $allServiceArea = $registration.find('.all_service_area').first();
		if (!$allServiceArea.length || $allServiceArea.data('mpwpbTreeInit')) {
			return;
		}
		$allServiceArea.data('mpwpbTreeInit', true);

		var $categoryArea = $allServiceArea.find('.mpwpb_category_area').first();
		var $serviceArea = $allServiceArea.find('.mpwpb_service_area').first();
		var $noCatArea = $allServiceArea.find('.mpwpb_without_cat_service_area').first();

		if (!$serviceArea.length) {
			return;
		}

		var $tree = $('<div class="mpwpb-service-tree mpwpb-booking-tree"></div>');
		var hasCategories = $categoryArea.length > 0 && $categoryArea.find('.mpwpb_category_item').length > 0;

		if (hasCategories) {
			$categoryArea.find('> .mpwpb_category_section').each(function () {
				var $section = $(this);
				var $catItem = $section.find('> .mpwpb_item_box.mpwpb_category_item').first();
				if (!$catItem.length) {
					return;
				}
				var catId = parseInt($catItem.data('category'), 10);
				var catName = $.trim($catItem.find('h6').first().text());

				var $catRow = buildRow('cat', catName);
				$catRow.addClass('is-open');
				var $catChildren = $catRow.find('> .mpwpb-tree-children');

				// This category's real subcategory ids (only the ones
				// actually nested under it in the DOM). A service's own
				// data-sub-category is checked against THIS list rather
				// than just "is it blank" -- some services were saved back
				// when their category had no subcategories yet, and the
				// old picker's markup renders their leftover raw sub_cat
				// (e.g. "0") as a non-empty data-sub-category that happens
				// to number-match a subcategory belonging to a DIFFERENT
				// category. Anything not one of this category's own real
				// subcategory ids is an own/direct service, not orphaned.
				var subIds = [];
				$section.find('.mpwpb_sub_category_area .mpwpb_item_box.mpwpb_sub_category_item').each(function () {
					var id = parseInt($(this).data('sub-category'), 10);
					if (!isNaN(id)) {
						subIds.push(id);
					}
				});

				$serviceArea.find('.mpwpb_service_item').filter(function () {
					var $svc = $(this);
					var svcCat = parseInt($svc.data('category'), 10);
					var svcSub = parseInt($svc.data('sub-category'), 10);
					return svcCat === catId && (isNaN(svcSub) || $.inArray(svcSub, subIds) === -1);
				}).each(function () {
					relocateService($(this), $catChildren);
				});

				$section.find('.mpwpb_sub_category_area .mpwpb_item_box.mpwpb_sub_category_item').each(function () {
					var $subItem = $(this);
					var subId = parseInt($subItem.data('sub-category'), 10);
					var subName = $.trim($subItem.find('h6').first().text());

					var $subRow = buildRow('sub', subName);
					var $subChildren = $subRow.find('> .mpwpb-tree-children');

					$serviceArea.find('.mpwpb_service_item').filter(function () {
						var $svc = $(this);
						return parseInt($svc.data('category'), 10) === catId && parseInt($svc.data('sub-category'), 10) === subId;
					}).each(function () {
						relocateService($(this), $subChildren);
					});

					// A subcategory with no services assigned to it is left
					// out entirely -- its parent category (and its own
					// direct services, if any) still display normally.
					if (!$subChildren.children('.mpwpb_service_item').length) {
						return;
					}
					$subRow.addClass('is-open');
					$catChildren.append($subRow);
					updateBranchCount($subRow);
				});

				// A category with no services anywhere in it (no direct
				// services and no non-empty subcategories) is left out too.
				if (!$catChildren.find('.mpwpb_service_item').length) {
					return;
				}
				$tree.append($catRow);
				updateBranchCount($catRow);
			});
		} else {
			$tree.addClass('mpwpb-service-tree--flat');
			$serviceArea.find('.mpwpb_service_item').each(function () {
				relocateService($(this), $tree);
			});
		}

		var $quickItems = $noCatArea.length ? $noCatArea.find('.mpwpb_service_item') : $();
		if ($quickItems.length) {
			var $quickWrap = $('<div class="mpwpb-tree-quick-options"></div>');
			$quickWrap.append('<div class="mpwpb-tree-quick-label">Quick options</div>');
			var $quickChildren = $('<div class="mpwpb-tree-children mpwpb-tree-children--quick"></div>');
			$quickItems.each(function () {
				relocateService($(this), $quickChildren);
			});
			$quickWrap.append($quickChildren);
			$tree.append($quickWrap);
		}

		var $header = $allServiceArea.find('.selection-header').first();
		if ($header.length) {
			$header.after($tree);
		} else {
			$allServiceArea.prepend($tree);
		}

		// Expand/collapse is handled by the existing document-delegated
		// handler in mpwpb-service-tree.js (matches any .mpwpb-service-tree
		// [data-tree-toggle], including this dynamically-built one) -- no
		// separate handler needed here, and adding one would double-toggle.

		relabelContinueButton($registration);
		updateAllCounts($tree);
		updateSelectedFooter($registration);
	}

	// Registered once at module scope (not per popup instance) since this is
	// document-delegated and matches any relocated row regardless of when
	// it was built. The cart-summary panel (and its own .mpwpb_service_
	// button_remove) is hidden entirely now -- unchecking the box here is
	// the only remove path, and it's the same button/handler as adding.
	$(document).on('click', '.mpwpb-booking-tree .mpwpb_service_button', function () {
		var $tree = $(this).closest('.mpwpb-service-tree');
		if ($tree.length) {
			updateAllCounts($tree);
		}
		updateSelectedFooter($(this).closest('div.mpwpb_registration'));
		updateDecIcon($(this).closest('.mpwpb-tree-service-row'));
	});

	// The minus button reads as a "remove from selection" action once qty is
	// already at 1 (its icon switches to a cross via updateDecIcon) --
	// decrementing further doesn't make sense while still selected, so
	// instead it deselects the service the same way unchecking it would.
	// Read BEFORE the real .service_decQty handler (mpwpb_registration.js)
	// runs its own clamp-at-min logic; that handler still fires too, but is
	// a harmless no-op here since the row is about to be deselected anyway.
	$(document).on('click', '.mpwpb-tree-service-row .service_decQty', function () {
		var $row = $(this).closest('.mpwpb-tree-service-row');
		var qty = parseInt($row.find('.inputIncDec').first().val(), 10) || 1;
		if (qty <= 1) {
			$row.find('.mpwpb_service_button').trigger('click');
		}
	});

	$(document).on('click', '.mpwpb-tree-service-row .service_incQty, .mpwpb-tree-service-row .service_decQty', function () {
		updateDecIcon($(this).closest('.mpwpb-tree-service-row'));
	});

	// Clicking anywhere on a relocated service row (name, duration, price)
	// selects/deselects it, same as clicking the checkbox -- except over the
	// checkbox itself (already handles its own click), the qty stepper, or
	// the "View Details" toggle, which must keep their own behavior.
	$(document).on('click', '.mpwpb-tree-service-row', function (e) {
		var $target = $(e.target);
		if ($target.closest('.mpwpb_service_button').length ||
			$target.closest('.quantity-box').length ||
			$target.closest('.mpwpb-tree-view-details').length) {
			return;
		}
		$(this).find('.mpwpb_service_button').first().trigger('click');
	});

	$(function () {
		$('div.mpwpb_registration').each(function () {
			initBookingTree($(this));
		});
	});

})(jQuery);
