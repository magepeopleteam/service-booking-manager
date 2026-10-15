/**
 * Modern service editor — shell behaviour only (stepper, save, per-user
 * switch, toast, featured image).
 *
 * IMPORTANT: This script drives ONLY the modern shell. All section
 * functionality (repeaters, AJAX-saved Service/Extra-Service/Category/FAQ
 * cards, schedule table, icon/image picker) is still driven by the existing
 * admin scripts (mpwpb_admin.js / mpwpb_admin_settings.js / mpwpb_plugin_global.js),
 * which remain enqueued. We never touch those nodes here beyond relocating
 * the real WP title/content fields (never duplicating them).
 */
(function ($) {
	'use strict';

	var cfg = window.mpwpbSme || {};

	/* ---------------------------------------------------------------- *
	 *  Per-user editor switch (works in both classic & modern screens)
	 * ---------------------------------------------------------------- */
	function setUi(ui) {
		if (!cfg.ajax) {
			return;
		}
		$.post(cfg.ajax, {
			action: 'mpwpb_set_service_edit_ui',
			nonce: cfg.nonce,
			ui: ui
		}).always(function () {
			window.location.reload();
		});
	}

	$(document).on('click', '[data-sme-ui]', function (e) {
		e.preventDefault();
		setUi($(this).data('sme-ui'));
	});

	/* ---------------------------------------------------------------- *
	 *  Everything below requires the modern shell to be present
	 * ---------------------------------------------------------------- */
	var $root = $('#mpwpb-sme');
	if (!$root.length) {
		return;
	}

	// Full-screen takeover hook for the <html> element (CSS removes the
	// admin-bar padding only when this class is present).
	document.documentElement.classList.add('mpwpb-sme-html');

	var $steps = $root.find('.mpwpb-sme__step');
	var order = $steps.map(function () { return $(this).data('sme-go'); }).get();
	var total = parseInt($root.data('total'), 10) || order.length;
	var cur = 0;

	// Remembers which wizard step was last open, keyed per edited post (the
	// URL's post= query arg already tells different services apart), so a
	// plain page refresh reopens that step instead of always restarting the
	// wizard at General.
	function smeStepStorageKey() {
		return 'mpwpb_sme_step::' + location.pathname + location.search;
	}

	// Bottom bar: progress line, step name + summary, Back, and a Next
	// button that names the step it goes to (Update on the last step).
	var navSteps = [];
	try { navSteps = JSON.parse($root.find('[data-sme-nav-steps]').attr('data-sme-nav-steps') || '[]'); } catch (e) {}
	function isLastStep() { return cur === order.length - 1; }
	function primarySaveLabel() {
		return $.trim($root.find('[data-sme-save]').first().text()) || cfg.updateTxt || 'Update';
	}
	function syncNavbar() {
		var info = navSteps[cur] || {};
		var stepOf = (cfg.stepOfTxt || 'Step %1$d of %2$d').replace('%1$d', cur + 1).replace('%2$d', total);
		$root.find('[data-sme-nav-num]').text(cur + 1);
		$root.find('[data-sme-nav-name]').text(info.label || '');
		$root.find('[data-sme-stepof]').text(info.hint ? stepOf + ' \u00b7 ' + info.hint : stepOf);
		$root.find('[data-sme-nav-progress]').css('width', ((cur + 1) / total * 100) + '%');
		$root.find('[data-sme-prev]').prop('disabled', cur === 0).prop('hidden', cur === 0);
		var nextInfo = navSteps[cur + 1] || {};
		var label = isLastStep() ? primarySaveLabel()
			: (nextInfo.label ? (cfg.nextToTxt || 'Next: %s').replace('%s', nextInfo.label) : (cfg.nextTxt || 'Next Step'));
		$root.find('[data-sme-next]').toggleClass('is-save', isLastStep()).find('[data-sme-next-label]').text(label);
	}

	function goStep(index) {
		if (index < 0) { index = 0; }
		if (index > order.length - 1) { index = order.length - 1; }
		cur = index;
		var name = order[cur];
		try { sessionStorage.setItem(smeStepStorageKey(), name); } catch (e) {}
		// Expose the current step so CSS can gate the rail's progressive
		// summary cards per step.
		$root.attr('data-step', name);

		$root.find('.mpwpb-sme__panel').each(function () {
			$(this).toggleClass('active', $(this).data('sme-panel') === name);
		});
		$steps.each(function () {
			var i = parseInt($(this).data('sme-index'), 10);
			$(this).toggleClass('active', i === cur).toggleClass('done', i < cur);
		});
		$root.find('.mpwpb-sme__conn').each(function () {
			var ci = parseInt($(this).data('sme-conn'), 10);
			$(this).toggleClass('done', ci <= cur);
		});
		syncNavbar();

		var top = $root.offset() ? $root.offset().top - 60 : 0;
		$('html, body').animate({ scrollTop: top }, 200);

		// Repaint the relocated content editor when returning to General.
		if (name === 'general') {
			setTimeout(function () {
				if (window.tinymce) {
					var ed = tinymce.get('content');
					if (ed) { ed.execCommand('mceRepaint'); }
				}
			}, 50);
		}

	}

	$steps.on('click', function () {
		goStep(parseInt($(this).data('sme-index'), 10));
	});
	$root.on('click', '[data-sme-prev]', function () {
		if (cur > 0) { goStep(cur - 1); }
	});
	$root.on('click', '[data-sme-next]', function () {
		if (cur < order.length - 1) {
			goStep(cur + 1);
		} else {
			submitForm();
		}
	});

	/* ---------------------------------------------------------------- *
	 *  Frontend Preview rail card (Pricing/Availability/Advanced) — a
	 *  static mock built from the current Services & Pricing list, kept
	 *  live without any network round trip: mpwpb-categories-services-
	 *  modern.js fires 'mpwpb:csm-services-changed' (bubbling up from
	 *  #mpwpb-csm) on every add/edit/delete/reorder in that list, and
	 *  this just redraws the rows from the array it passes along.
	 *
	 *  Tapping a row's + sets its quantity and reveals a qty stepper, same
	 *  as the real tree (mpwpb-booking-tree.js); once any quantity is set
	 *  the CTA enables and advances this mock to a Date & Time step, the
	 *  way the real popup's own step flow does — all still cosmetic only,
	 *  nothing here is a real booking.
	 * ---------------------------------------------------------------- */
	var fpQty = {}; // service id -> quantity, preserved across re-renders by id
	var fpServices = []; // latest services array, for the Checkout step's summary
	var fpExtraQty = {}; // extra-service id -> quantity
	var fpSelectedDate = ''; // display label of the picked date/time mock, for Checkout
	var fpSelectedTime = '';

	function fpQtyStepperHtml() {
		return '<div class="mpwpb-sme__fp-qty">'
			+ '<button type="button" class="mpwpb-sme__fp-qty-btn" data-sme-fp-qty-minus>−</button>'
			+ '<span class="mpwpb-sme__fp-qty-val" data-sme-fp-qty-val></span>'
			+ '<button type="button" class="mpwpb-sme__fp-qty-btn" data-sme-fp-qty-plus>+</button>'
			+ '</div>';
	}
	function fpRenderRowSide($card, id) {
		var qty = fpQty[id] || 0;
		var $row = $card.find('[data-sme-fp-row="' + id + '"]');
		var $side = $row.find('[data-sme-fp-row-side]');
		$row.toggleClass('is-added', qty > 0);
		if (qty > 0) {
			if (!$side.find('[data-sme-fp-qty-val]').length) {
				$side.html(fpQtyStepperHtml());
			}
			$side.find('[data-sme-fp-qty-val]').text(qty);
		} else {
			var name = $row.find('.mpwpb-sme__fp-row-name').text();
			$side.html('<button type="button" class="mpwpb-sme__fp-row-add" data-sme-fp-add="' + id + '"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg><span></span></button>');
			$side.find('[data-sme-fp-add]').attr('aria-label', (cfg.previewAddTxt || 'Add') + ' ' + name);
			$side.find('[data-sme-fp-add] span').text(cfg.previewAddLabel || 'Add');
		}
	}
	function fpTotalQty() {
		var total = 0;
		Object.keys(fpQty).forEach(function (id) { total += fpQty[id] || 0; });
		return total;
	}
	function fpUpdateCta($card) {
		$card.find('[data-sme-fp-cta]').prop('disabled', fpTotalQty() === 0);
	}
	// Mirrors fpRenderRowSide for the extra-services list -- same
	// +/qty-stepper markup, just keyed by data-sme-fp-extra-row/-add instead.
	function fpRenderExtraRowSide($card, id) {
		var qty = fpExtraQty[id] || 0;
		var $row = $card.find('[data-sme-fp-extra-row="' + id + '"]');
		var $side = $row.find('[data-sme-fp-row-side]');
		$row.toggleClass('is-added', qty > 0);
		if (qty > 0) {
			if (!$side.find('[data-sme-fp-qty-val]').length) {
				$side.html(fpQtyStepperHtml());
			}
			$side.find('[data-sme-fp-qty-val]').text(qty);
		} else {
			var name = $row.find('.mpwpb-sme__fp-row-name').text();
			$side.html('<button type="button" class="mpwpb-sme__fp-row-add" data-sme-fp-extra-add="' + id + '"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg><span></span></button>');
			$side.find('[data-sme-fp-extra-add]').attr('aria-label', (cfg.previewAddTxt || 'Add') + ' ' + name);
			$side.find('[data-sme-fp-extra-add] span').text(cfg.previewAddLabel || 'Add');
		}
	}
	// Live "X selected, Total Y" summary above the Services step's CTA --
	// reuses fpSummaryRow (below), the same row builder the Checkout step
	// uses for its own order summary. Includes extra-service selections too.
	function fpUpdateServicesSummary($card) {
		var $box = $card.find('[data-sme-fp-services-summary]');
		$box.empty();
		var total = 0;
		var any = false;
		fpServices.forEach(function (svc) {
			var qty = fpQty[svc.id] || 0;
			if (qty === 0) { return; }
			any = true;
			var price = parseFloat(svc.price) || 0;
			total += price * qty;
			var label = svc.name + (qty > 1 ? ' × ' + qty : '');
			var val = (cfg.previewCurrency || '') + (price * qty).toFixed(2);
			$box.append(fpSummaryRow(label, val));
		});
		$card.find('[data-sme-fp-extra-row]').each(function () {
			var id = $(this).data('sme-fp-extra-row');
			var qty = fpExtraQty[id] || 0;
			if (qty === 0) { return; }
			any = true;
			var price = parseFloat($(this).find('[data-sme-fp-row-price]').attr('data-sme-fp-row-price')) || 0;
			total += price * qty;
			var label = $(this).find('.mpwpb-sme__fp-row-name').text() + (qty > 1 ? ' × ' + qty : '');
			var val = (cfg.previewCurrency || '') + (price * qty).toFixed(2);
			$box.append(fpSummaryRow(label, val));
		});
		if (any) {
			$box.append(fpSummaryRow(cfg.previewTotalTxt || 'Total', (cfg.previewCurrency || '') + total.toFixed(2), true));
		}
		$box.toggle(any);
		// Services step bar: "N services selected" + running total.
		var count = fpTotalQty();
		$card.find('[data-sme-fp-bar-count]').text(count === 0 ? (cfg.previewNothingTxt || 'Nothing selected yet')
			: (count === 1 ? (cfg.previewOneSelected || '1 service selected') : String(cfg.previewManySelected || '%d services selected').replace('%d', count)));
		$card.find('[data-sme-fp-bar-total]').text(any ? (cfg.previewCurrency || '') + total.toFixed(2) : '');
		$card.find('[data-sme-fp-bar]').toggleClass('is-ready', count > 0);
	}
	// "2 added" on each category / subcategory head that holds picked rows.
	function fpUpdateGroupBadges($card) {
		$card.find('.mpwpb-sme__fp-group-head, .mpwpb-sme__fp-subgroup-head').each(function () {
			var $head = $(this);
			var n = $head.next('[data-sme-fp-group-body]').find('.mpwpb-sme__fp-row.is-added').length;
			var $badge = $head.find('.mpwpb-sme__fp-group-added');
			if (!$badge.length) {
				$badge = $('<span class="mpwpb-sme__fp-group-added"></span>').insertBefore($head.find('.mpwpb-sme__fp-group-count'));
			}
			$badge.text(String(cfg.previewGroupAdded || '%d added').replace('%d', n)).toggle(n > 0);
		});
	}
	// Extra services are page-wide, not linked to any one regular service
	// (see MPWPB_Settings_Modern.php's render comment) -- the list simply
	// appears once ANY regular service is selected, and hides again if the
	// last one is removed.
	function fpUpdateExtrasVisibility($card) {
		var $extras = $card.find('[data-sme-fp-extras]');
		if (!$extras.length) { return; }
		var active = $root.find('input[name="mpwpb_extra_service_active"]').is(':checked');
		$extras.toggle(active && $extras.find('[data-sme-fp-extra-row]').length > 0 && fpTotalQty() > 0);
	}
	function fpSetQty($card, id, qty) {
		fpQty[id] = Math.max(0, qty);
		if (fpQty[id] === 0) { delete fpQty[id]; }
		fpRenderRowSide($card, id);
		fpUpdateCta($card);
		fpUpdateServicesSummary($card);
		fpUpdateExtrasVisibility($card);
		fpUpdateGroupBadges($card);
	}
	function fpSetExtraQty($card, id, qty) {
		fpExtraQty[id] = Math.max(0, qty);
		if (fpExtraQty[id] === 0) { delete fpExtraQty[id]; }
		fpRenderExtraRowSide($card, id);
		fpUpdateServicesSummary($card);
	}

	function buildPreviewRow(svc) {
		var $row = $('<div class="mpwpb-sme__fp-row"></div>').attr('data-sme-fp-row', svc.id);
		var $main = $('<div class="mpwpb-sme__fp-row-main"></div>').appendTo($row);
		$('<div class="mpwpb-sme__fp-row-name"></div>').text(svc.name || '').appendTo($main);
		var $meta = $('<div class="mpwpb-sme__fp-row-meta"></div>').appendTo($main);
		var hasDur = $.trim(String(svc.duration || '')) !== '';
		$('<span class="mpwpb-sme__fp-row-dur"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><polyline points="12,7 12,12 15,14"></polyline></svg></span>')
			.toggleClass('is-missing', !hasDur)
			.append(document.createTextNode(hasDur ? String(svc.duration) : (cfg.previewNoDurationTxt || 'No duration set')))
			.appendTo($meta);
		$('<span class="mpwpb-sme__fp-row-price"></span>')
			.text((cfg.previewCurrency || '') + (svc.price || '0'))
			.appendTo($meta);
		$('<div class="mpwpb-sme__fp-row-side"></div>').attr('data-sme-fp-row-side', '').appendTo($row);
		return $row;
	}
	/* Groups the flat services array into category -> subcategory -> service,
	   mirroring the PHP grouping in MPWPB_Settings_Modern.php exactly (same
	   fallback: no/unknown category -> uncategorized, rendered flat). */
	function groupPreviewServices(services, categories, subCategories) {
		var catById = {};
		categories.forEach(function (c) { catById[c.id] = c; });
		var subById = {};
		subCategories.forEach(function (s) { subById[s.id] = s; });

		var directByCat = {};
		var byCat = {}; // subId -> [svc,...]
		var uncategorized = [];
		services.forEach(function (svc) {
			var catId = svc.parentCat || '';
			var subId = svc.subCat || '';
			if (!catId || !catById[catId]) {
				uncategorized.push(svc);
			} else if (subId && subById[subId] && String(subById[subId].catId) === String(catId)) {
				// subId alone isn't enough -- it's also the default/empty
				// value for "no subcategory", so a subId that belongs to a
				// DIFFERENT category must not misfile this service into it;
				// it belongs directly under its own catId instead.
				(byCat[subId] = byCat[subId] || []).push(svc);
			} else {
				(directByCat[catId] = directByCat[catId] || []).push(svc);
			}
		});
		var subsByCat = {};
		subCategories.forEach(function (s) {
			if (s.catId && catById[s.catId]) {
				(subsByCat[s.catId] = subsByCat[s.catId] || []).push(s);
			}
		});

		var groups = categories.map(function (cat) {
			var direct = directByCat[cat.id] || [];
			var subs = (subsByCat[cat.id] || [])
				.map(function (sub) { return { id: sub.id, name: sub.name, services: byCat[sub.id] || [] }; })
				.filter(function (sub) { return sub.services.length > 0; });
			var count = direct.length;
			subs.forEach(function (sub) { count += sub.services.length; });
			return { id: cat.id, name: cat.name, direct: direct, subs: subs, count: count };
		}).filter(function (g) { return g.count > 0; });

		return { groups: groups, uncategorized: uncategorized };
	}
	function fpChevronSvg() {
		return '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="mpwpb-sme__fp-group-chev"><polyline points="18,15 12,9 6,15"></polyline></svg>';
	}
	function buildPreviewSubgroup(sub) {
		var $sub = $('<div class="mpwpb-sme__fp-subgroup"></div>').attr('data-sme-fp-group', sub.id);
		var $head = $(
			'<button type="button" class="mpwpb-sme__fp-subgroup-head" data-sme-fp-group-toggle>' +
				'<span class="mpwpb-sme__fp-group-icon" aria-hidden="true"><span class="dashicons dashicons-admin-generic"></span></span>' +
				'<span class="mpwpb-sme__fp-group-name"></span>' +
				'<span class="mpwpb-sme__fp-group-count"></span>' +
				fpChevronSvg() +
			'</button>'
		).appendTo($sub);
		$head.find('.mpwpb-sme__fp-group-name').text(sub.name || '');
		$head.find('.mpwpb-sme__fp-group-count').text(sub.services.length);
		var $body = $('<div class="mpwpb-sme__fp-subgroup-body"></div>').attr('data-sme-fp-group-body', '').appendTo($sub);
		sub.services.forEach(function (svc) { $body.append(buildPreviewRow(svc)); });
		return $sub;
	}
	function buildPreviewGroup(group) {
		var $group = $('<div class="mpwpb-sme__fp-group"></div>').attr('data-sme-fp-group', group.id);
		var $head = $(
			'<button type="button" class="mpwpb-sme__fp-group-head" data-sme-fp-group-toggle>' +
				'<span class="mpwpb-sme__fp-group-icon" aria-hidden="true"><span class="dashicons dashicons-category"></span></span>' +
				'<span class="mpwpb-sme__fp-group-name"></span>' +
				'<span class="mpwpb-sme__fp-group-count"></span>' +
				fpChevronSvg() +
			'</button>'
		).appendTo($group);
		$head.find('.mpwpb-sme__fp-group-name').text(group.name || '');
		$head.find('.mpwpb-sme__fp-group-count').text(group.count);
		var $body = $('<div class="mpwpb-sme__fp-group-body"></div>').attr('data-sme-fp-group-body', '').appendTo($group);
		group.direct.forEach(function (svc) { $body.append(buildPreviewRow(svc)); });
		group.subs.forEach(function (sub) { $body.append(buildPreviewSubgroup(sub)); });
		return $group;
	}
	function renderPreviewList(services, categories, subCategories) {
		var $card = $root.find('.mpwpb-sme__preview-card');
		if (!$card.length) { return; }
		services = services || [];
		fpServices = services;
		var ids = {};
		var $rows = $card.find('[data-sme-fp-rows]');
		$rows.empty();
		var grouped = groupPreviewServices(services, categories || [], subCategories || []);
		grouped.groups.forEach(function (group) { $rows.append(buildPreviewGroup(group)); });
		grouped.uncategorized.forEach(function (svc) { $rows.append(buildPreviewRow(svc)); });
		services.forEach(function (svc) {
			ids[svc.id] = true;
			fpRenderRowSide($card, svc.id);
		});
		// Drop quantities for services that were removed from the list.
		Object.keys(fpQty).forEach(function (id) {
			if (!ids[id]) { delete fpQty[id]; }
		});
		var hasServices = services.length > 0;
		$rows.toggle(hasServices);
		$card.find('[data-sme-fp-empty]').toggle(!hasServices);
		$card.find('[data-sme-fp-cta]').toggle(hasServices);
		$card.find('[data-sme-fp-trust]').toggle(hasServices);
		$card.find('[data-sme-fp-bar]').toggle(hasServices);
		fpUpdateCta($card);
		fpUpdateServicesSummary($card);
		fpUpdateExtrasVisibility($card);
		fpUpdateGroupBadges($card);
	}
	function buildPreviewExtraRow(item) {
		var $row = $('<div class="mpwpb-sme__fp-row"></div>').attr('data-sme-fp-extra-row', item.id);
		var $main = $('<div class="mpwpb-sme__fp-row-main"></div>').appendTo($row);
		$('<div class="mpwpb-sme__fp-row-name"></div>').text(item.name || '').appendTo($main);
		var $meta = $('<div class="mpwpb-sme__fp-row-meta"></div>').appendTo($main);
		$('<span class="mpwpb-sme__fp-row-price"></span>')
			.attr('data-sme-fp-row-price', item.price || '0')
			.text((cfg.previewCurrency || '') + (item.price || '0'))
			.appendTo($meta);
		$('<div class="mpwpb-sme__fp-row-side"></div>').attr('data-sme-fp-row-side', '').appendTo($row);
		return $row;
	}
	// Rebuilds the extra-services list when it's edited live in the Pricing
	// step, same pattern as renderPreviewList for regular services. The
	// whole block only exists in the DOM if "Enable Extra Service" was on at
	// page load (see MPWPB_Settings_Modern.php) -- editing items live can't
	// make the block appear from nothing, only refresh what's already there.
	function renderPreviewExtras(items) {
		var $card = $root.find('.mpwpb-sme__preview-card');
		var $block = $card.find('[data-sme-fp-extras]');
		if (!$block.length) { return; }
		items = items || [];
		var ids = {};
		var $rows = $block.find('[data-sme-fp-extra-rows]');
		$rows.empty();
		items.forEach(function (item) {
			ids[item.id] = true;
			$rows.append(buildPreviewExtraRow(item));
			fpRenderExtraRowSide($card, item.id);
		});
		Object.keys(fpExtraQty).forEach(function (id) {
			if (!ids[id]) { delete fpExtraQty[id]; }
		});
		fpUpdateExtrasVisibility($card);
		fpUpdateServicesSummary($card);
	}
	// Extra Service switch: show/hide the preview's add-ons live, and drop
	// any picked add-ons from the preview total once it's switched off.
	$root.on('change', 'input[name="mpwpb_extra_service_active"]', function () {
		var $card = $root.find('.mpwpb-sme__preview-card');
		if (!this.checked) {
			Object.keys(fpExtraQty).forEach(function (id) {
				delete fpExtraQty[id];
				fpRenderExtraRowSide($card, id);
			});
			fpUpdateServicesSummary($card);
		}
		fpUpdateExtrasVisibility($card);
	});
	$root.on('click', '[data-sme-fp-group-toggle]', function () {
		$(this).closest('.mpwpb-sme__fp-group, .mpwpb-sme__fp-subgroup').toggleClass('is-collapsed');
	});

	$root.on('click', '[data-sme-fp-add]', function () {
		var $card = $(this).closest('.mpwpb-sme__preview-card');
		fpSetQty($card, $(this).data('sme-fp-add'), 1);
	});
	$root.on('click', '[data-sme-fp-extra-add]', function () {
		var $card = $(this).closest('.mpwpb-sme__preview-card');
		fpSetExtraQty($card, $(this).data('sme-fp-extra-add'), 1);
	});
	$root.on('click', '[data-sme-fp-qty-plus]', function () {
		var $card = $(this).closest('.mpwpb-sme__preview-card');
		var $extraRow = $(this).closest('[data-sme-fp-extra-row]');
		if ($extraRow.length) {
			var exId = $extraRow.data('sme-fp-extra-row');
			fpSetExtraQty($card, exId, (fpExtraQty[exId] || 0) + 1);
			return;
		}
		var id = $(this).closest('[data-sme-fp-row]').data('sme-fp-row');
		fpSetQty($card, id, (fpQty[id] || 0) + 1);
	});
	$root.on('click', '[data-sme-fp-qty-minus]', function () {
		var $card = $(this).closest('.mpwpb-sme__preview-card');
		var $extraRow = $(this).closest('[data-sme-fp-extra-row]');
		if ($extraRow.length) {
			var exId = $extraRow.data('sme-fp-extra-row');
			fpSetExtraQty($card, exId, (fpExtraQty[exId] || 0) - 1);
			return;
		}
		var id = $(this).closest('[data-sme-fp-row]').data('sme-fp-row');
		fpSetQty($card, id, (fpQty[id] || 0) - 1);
	});

	/* ---------------------------------------------------------------- *
	 *  Step 2: Date & Time — built once on first visit, lazily, since
	 *  "Today"/"Tomorrow" need the real current date and there's nothing
	 *  else server-side worth pre-rendering for a cosmetic-only step.
	 * ---------------------------------------------------------------- */
	var FP_TIME_SLOTS = ['10:00 am', '11:00 am', '12:00 pm', '3:00 pm', '4:00 pm', '5:00 pm'];
	var FP_WEEKDAYS = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
	var fpDateOffset = 0; // days forward from today the visible 6-day window starts
	function fpBuildDateStrip() {
		var $wrap = $('<div class="mpwpb-sme__fp-dates-wrap"></div>');
		var $head = $('<div class="mpwpb-sme__fp-dates-head"></div>').appendTo($wrap);
		$('<span class="mpwpb-sme__fp-section-lab"></span>').text(cfg.previewDateLabelTxt || 'Date').appendTo($head);
		$('<button type="button" class="mpwpb-sme__fp-dates-next"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="9,6 15,12 9,18"></polyline></svg></button>')
			.attr('aria-label', cfg.previewMoreDatesTxt || 'See more dates')
			.on('click', function () {
				fpDateOffset += 6;
				fpSelectedDate = '';
				fpSelectedDay = null;
				var $step = $wrap.closest('.mpwpb-sme__fp-step--datetime');
				$wrap.replaceWith(fpBuildDateStrip());
				fpUpdateDatetimeCta($step);
			})
			.appendTo($head);

		var $strip = $('<div class="mpwpb-sme__fp-dates"></div>').appendTo($wrap);
		for (var i = 0; i < 6; i++) {
			var dayNum = fpDateOffset + i;
			var d = new Date();
			d.setDate(d.getDate() + dayNum);
			var label = dayNum === 0 ? (cfg.previewTodayTxt || 'Today') : dayNum === 1 ? (cfg.previewTomorrowTxt || 'Tomorrow') : FP_WEEKDAYS[d.getDay()];
			var $btn = $('<button type="button" class="mpwpb-sme__fp-date' + (dayNum === 0 ? ' is-selected' : '') + '"></button>')
				.attr('aria-label', label + ' ' + d.getDate())
				.attr('data-fp-day', dayNum);
			// Only "Tomorrow" skips the visible label -- Today and the weekday
			// abbreviations (Wed/Thu/…) still show theirs. aria-label is set
			// for every card regardless, so Tomorrow keeps its context for
			// screen readers even without the on-card text. Past the first
			// page (fpDateOffset > 0), dayNum is never 0 or 1, so every card
			// on later pages is a plain weekday label.
			if (dayNum !== 1) {
				$btn.append($('<span class="mpwpb-sme__fp-date-lab"></span>').text(label));
			}
			$btn.append($('<strong class="mpwpb-sme__fp-date-num"></strong>').text(d.getDate())).appendTo($strip);
			if (dayNum === 0) { fpSelectedDate = label + ' ' + d.getDate(); fpSelectedDay = 0; } // "Today" starts pre-selected
		}
		$strip.on('click', 'button', function () {
			$strip.find('button').removeClass('is-selected');
			$(this).addClass('is-selected');
			fpSelectedDate = $(this).attr('aria-label');
			fpSelectedDay = parseInt($(this).attr('data-fp-day'), 10) || 0;
			fpUpdateDatetimeCta($strip.closest('.mpwpb-sme__fp-step--datetime'));
			fpRenderRecur($strip.closest('.mpwpb-sme__fp-step--datetime'));
		});
		return $wrap;
	}
	function fpBuildTimeGrid() {
		var $grid = $('<div class="mpwpb-sme__fp-times"></div>');
		FP_TIME_SLOTS.forEach(function (slot) {
			$('<button type="button" class="mpwpb-sme__fp-time"></button>').text(slot).appendTo($grid);
		});
		$grid.on('click', 'button', function () {
			$grid.find('button').removeClass('is-selected');
			$(this).addClass('is-selected');
			fpSelectedTime = $(this).text();
			fpUpdateDatetimeCta($grid.closest('.mpwpb-sme__fp-step--datetime'));
			fpRenderRecur($grid.closest('.mpwpb-sme__fp-step--datetime'));
		});
		return $grid;
	}
	function fpUpdateDatetimeCta($step) {
		var hasDate = $step.find('.mpwpb-sme__fp-date.is-selected').length > 0;
		var hasTime = $step.find('.mpwpb-sme__fp-time.is-selected').length > 0;
		$step.find('[data-sme-fp-dt-cta]').prop('disabled', !(hasDate && hasTime));
	}
	function fpBuildDatetimeStep($step) {
		$('<div class="mpwpb-sme__fp-heading"></div>').text(cfg.previewStep2HeadingTxt || 'Choose date & time').appendTo($step);
		$('<p class="mpwpb-sme__fp-sub"></p>').text(cfg.previewStep2SubTxt || '').appendTo($step);
		$step.append(fpBuildDateStrip()); // the "Date" label + carousel-next icon are built inside the wrap now
		$('<div class="mpwpb-sme__fp-section-lab"></div>').text(cfg.previewTimeLabelTxt || 'Time').appendTo($step);
		$step.append(fpBuildTimeGrid());
		// Recurring block -- only filled while "Enable Recurring Bookings" is
		// on (fpRenderRecur re-reads the live settings on every call).
		$('<div class="mpwpb-sme__fp-recur" data-sme-fp-recur hidden></div>').appendTo($step);
		// Same mount point attribute as the Services step's summary --
		// fpUpdateServicesSummary() already updates every element matching
		// it inside the card, so this one just rides along automatically.
		$('<div class="mpwpb-sme__fp-summary" data-sme-fp-services-summary style="display:none"></div>').appendTo($step);
		$('<button type="button" class="mpwpb-sme__fp-cta" data-sme-fp-dt-cta disabled></button>')
			.text(cfg.previewContinueTxt || 'Continue to Checkout')
			.appendTo($step);
		fpRenderRecur($step);
		fpSyncDatetimeCtaLabel($step);
		// This step is only built once (lazily, on first visit) and reused
		// after that, so populate it immediately with whatever was already
		// selected on the Services step before the user ever got here.
		fpUpdateServicesSummary($step.closest('.mpwpb-sme__preview-card'));
	}
	/* ---------------------------------------------------------------- *
	 *  Step 3: Checkout — an order summary built from the live qty
	 *  selections (fpQty/fpServices) plus whatever date/time was picked
	 *  in step 2. Rebuilt fresh on every visit (unlike step 2, which is
	 *  only built once) since the selection can change between visits.
	 * ---------------------------------------------------------------- */
	function fpSummaryRow(label, val, isTotal) {
		return $('<div class="mpwpb-sme__fp-summary-row' + (isTotal ? ' mpwpb-sme__fp-summary-row--total' : '') + '"></div>')
			.append($('<span class="mpwpb-sme__fp-summary-row-label"></span>').text(label))
			.append($('<span class="mpwpb-sme__fp-summary-row-val"></span>').text(val));
	}
	function fpBuildCheckoutStep($step) {
		$step.empty();
		$('<div class="mpwpb-sme__fp-heading"></div>').text(cfg.previewCheckoutHeadingTxt || 'Review & confirm').appendTo($step);
		$('<p class="mpwpb-sme__fp-sub"></p>').text(cfg.previewCheckoutSubTxt || '').appendTo($step);

		var $summary = $('<div class="mpwpb-sme__fp-summary"></div>').appendTo($step);
		var total = 0;
		fpServices.forEach(function (svc) {
			var qty = fpQty[svc.id] || 0;
			if (qty === 0) { return; }
			var price = parseFloat(svc.price) || 0;
			total += price * qty;
			var label = svc.name + (qty > 1 ? ' × ' + qty : '');
			var val = (cfg.previewCurrency || '') + (price * qty).toFixed(2);
			$summary.append(fpSummaryRow(label, val));
		});
		// Extra services aren't tracked in a parallel data array like
		// fpServices -- read straight off the Services step's rows (same
		// source fpUpdateServicesSummary uses) since they're still in the
		// DOM regardless of which step is currently shown.
		$root.find('[data-sme-fp-extra-row]').each(function () {
			var id = $(this).data('sme-fp-extra-row');
			var qty = fpExtraQty[id] || 0;
			if (qty === 0) { return; }
			var price = parseFloat($(this).find('[data-sme-fp-row-price]').attr('data-sme-fp-row-price')) || 0;
			total += price * qty;
			var label = $(this).find('.mpwpb-sme__fp-row-name').text() + (qty > 1 ? ' × ' + qty : '');
			var val = (cfg.previewCurrency || '') + (price * qty).toFixed(2);
			$summary.append(fpSummaryRow(label, val));
		});
		$summary.append(fpSummaryRow(cfg.previewDateTimeLabelTxt || 'Date & Time', fpSelectedDate + (fpSelectedTime ? ', ' + fpSelectedTime : '')));
		if (fpStaffOn()) {
			$summary.append(fpSummaryRow(cfg.previewStaffLabelTxt || 'Staff', fpSelectedStaff || (cfg.previewAnyStaffTxt || 'Any Staff')));
		}
		var recur = fpRecurSettings();
		if (recur.enabled && fpRecur.on && fpRecur.type) {
			var types = cfg.previewRecurTypes || {};
			$summary.append(fpSummaryRow(cfg.previewRecurLabelTxt || 'Recurring', (types[fpRecur.type] || fpRecur.type) + ' × ' + fpRecur.count));
			total = total * fpRecur.count;
			if (recur.discount > 0) {
				var cut = total * recur.discount / 100;
				total -= cut;
				$summary.append(fpSummaryRow((cfg.previewRecurDiscountTxt || 'Recurring discount') + ' (' + recur.discount + '%)', '−' + (cfg.previewCurrency || '') + cut.toFixed(2)));
			}
		}
		$summary.append(fpSummaryRow(cfg.previewTotalTxt || 'Total', (cfg.previewCurrency || '') + total.toFixed(2), true));

		$('<button type="button" class="mpwpb-sme__fp-cta" data-sme-fp-confirm></button>')
			.text(cfg.previewConfirmTxt || 'Confirm Booking')
			.appendTo($step);
	}
	function fpStepOrder() {
		return fpStaffOn() ? ['services', 'datetime', 'staff', 'checkout'] : ['services', 'datetime', 'checkout'];
	}
	function fpUpdateSteps($mock, name) {
		var order = fpStepOrder();
		var idx = order.indexOf(name);
		$mock.find('[data-sme-fp-steps-item]').each(function () {
			var i = order.indexOf($(this).data('sme-fp-steps-item'));
			$(this).toggleClass('is-active', i === idx).toggleClass('is-done', i < idx);
		});
	}
	// One shared Back button lives in the card footer (left of "Open full
	// preview") rather than one per step, so it needs to know where "back"
	// goes from wherever the mock currently is.
	var fpCurrentStep = 'services';
	function fpGoToStep($mock, name) {
		var $services = $mock.find('[data-sme-fp-step="services"]');
		var $datetime = $mock.find('[data-sme-fp-step="datetime"]');
		var $staff = $mock.find('[data-sme-fp-step="staff"]');
		var $checkout = $mock.find('[data-sme-fp-step="checkout"]');
		if (name === 'staff' && !fpStaffOn()) { name = 'checkout'; }
		if (name === 'datetime' && !$datetime.children().length) {
			fpBuildDatetimeStep($datetime);
		}
		if (name === 'staff') {
			fpBuildStaffStep($staff); // always fresh: the staff selection is edited live
		}
		if (name === 'checkout') {
			fpBuildCheckoutStep($checkout); // always fresh: selections can change between visits
		}
		$services.toggleClass('is-active', name === 'services').prop('hidden', name !== 'services');
		$datetime.toggleClass('is-active', name === 'datetime').prop('hidden', name !== 'datetime');
		$staff.toggleClass('is-active', name === 'staff').prop('hidden', name !== 'staff');
		$checkout.toggleClass('is-active', name === 'checkout').prop('hidden', name !== 'checkout');
		fpUpdateSteps($mock, name);
		fpCurrentStep = name;
		$mock.closest('.mpwpb-sme__preview-card').find('[data-sme-fp-footer-back]').prop('hidden', name === 'services');
	}
	$root.on('click', '[data-sme-fp-cta]', function (e) {
		e.preventDefault();
		if ($(this).prop('disabled')) { return; }
		fpGoToStep($(this).closest('.mpwpb-sme__fp-mock'), 'datetime');
	});
	$root.on('click', '[data-sme-fp-dt-cta]', function (e) {
		e.preventDefault();
		if ($(this).prop('disabled')) { return; }
		fpGoToStep($(this).closest('.mpwpb-sme__fp-mock'), fpStaffOn() ? 'staff' : 'checkout');
	});
	$root.on('click', '[data-sme-fp-staff-cta]', function (e) {
		e.preventDefault();
		fpGoToStep($(this).closest('.mpwpb-sme__fp-mock'), 'checkout');
	});
	$root.on('click', '[data-sme-fp-confirm]', function (e) {
		e.preventDefault();
		toast(cfg.previewOnlyTxt || 'Preview only — real bookings continue on the live page.');
	});
	$root.on('click', '[data-sme-fp-footer-back]', function () {
		var $mock = $(this).closest('.mpwpb-sme__preview-card').find('[data-sme-fp-mock]');
		var order = fpStepOrder();
		var idx = order.indexOf(fpCurrentStep);
		fpGoToStep($mock, idx > 0 ? order[idx - 1] : 'services');
	});

	/* ---------------------------------------------------------------- *
	 *  Staff + Recurring in the preview -- both read the live (unsaved)
	 *  settings on this screen, so toggling "Staff Member" / "Enable
	 *  Recurring Bookings", picking staff tiles or changing the recurring
	 *  types/limits/discount updates the mock straight away.
	 * ---------------------------------------------------------------- */
	function fpStaffOn() {
		return $root.find('input[name="mpwpb_staff_member_add"]').is(':checked');
	}
	function fpRecurSettings() {
		var types = [];
		$root.find('input[name="mpwpb_recurring_types[]"]:checked').each(function () { types.push(this.value); });
		var max = parseInt($root.find('input[name="mpwpb_max_recurring_count"]').val(), 10) || 26;
		return {
			enabled: $root.find('input[name="mpwpb_enable_recurring"]').is(':checked') && types.length > 0,
			types: types,
			max: Math.max(2, Math.min(52, max)),
			discount: Math.max(0, Math.min(100, parseInt($root.find('input[name="mpwpb_recurring_discount"]').val(), 10) || 0))
		};
	}
	function fpStaffList() {
		var list = [];
		$root.find('.mpwpb-staff-tile.is-selected').each(function () {
			list.push({ name: $(this).find('.mpwpb-staff-tile-name').text(), avatar: $(this).find('.mpwpb-staff-tile-avatar').attr('src') || '' });
		});
		return list;
	}
	var fpSelectedStaff = ''; // '' = Any Staff
	var fpRecur = { on: false, type: '', count: 2 };
	var fpSelectedDay = 0; // days from today of the picked date card

	// Show/hide the "Staff" progress item and renumber the dots.
	function fpSyncSteps($mock) {
		$mock.find('[data-sme-fp-staff-only]').prop('hidden', !fpStaffOn());
		fpStepOrder().forEach(function (name, i) {
			$mock.find('[data-sme-fp-steps-item="' + name + '"] .mpwpb-sme__fp-steps-dot').text(i + 1);
		});
		fpUpdateSteps($mock, fpCurrentStep);
	}
	function fpSyncDatetimeCtaLabel($step) {
		$step.find('[data-sme-fp-dt-cta]').text(fpStaffOn() ? (cfg.previewNextStaffTxt || 'Next: Staff Member') : (cfg.previewContinueTxt || 'Continue to Checkout'));
	}

	var FP_MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
	function fpRecurDates() {
		var out = [];
		for (var i = 0; i < fpRecur.count; i++) {
			var d = new Date();
			d.setHours(0, 0, 0, 0);
			d.setDate(d.getDate() + (fpSelectedDay || 0));
			if (fpRecur.type === 'daily') { d.setDate(d.getDate() + i); }
			else if (fpRecur.type === 'weekly') { d.setDate(d.getDate() + 7 * i); }
			else if (fpRecur.type === 'bi-weekly') { d.setDate(d.getDate() + 14 * i); }
			else { d.setMonth(d.getMonth() + i); }
			out.push(d);
		}
		return out;
	}
	function fpFmtDate(d, withYear) {
		return FP_MONTHS[d.getMonth()] + ' ' + d.getDate() + (withYear ? ', ' + d.getFullYear() : '');
	}
	function fpRenderRecur($step) {
		var $box = $step.find('[data-sme-fp-recur]');
		if (!$box.length) { return; }
		var s = fpRecurSettings();
		$box.empty().prop('hidden', !s.enabled);
		if (!s.enabled) { return; }
		if (s.types.indexOf(fpRecur.type) === -1) { fpRecur.type = s.types[0]; }
		fpRecur.count = Math.max(2, Math.min(s.max, fpRecur.count));
		var labels = cfg.previewRecurTypes || {};

		var $head = $('<div class="mpwpb-sme__fp-recur-head"></div>').appendTo($box);
		$('<span class="mpwpb-sme__fp-recur-icon" aria-hidden="true"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 2l3 3-3 3"></path><path d="M4 11V9a4 4 0 0 1 4-4h12"></path><path d="M7 22l-3-3 3-3"></path><path d="M20 13v2a4 4 0 0 1-4 4H4"></path></svg></span>').appendTo($head);
		var $txt = $('<div class="mpwpb-sme__fp-recur-text"></div>').appendTo($head);
		$('<strong></strong>').text(cfg.previewRecurTitleTxt || 'Recurring booking').appendTo($txt);
		$('<span></span>').text(cfg.previewRecurSubTxt || '').appendTo($txt);
		$('<button type="button" role="switch" class="mpwpb-sme__fp-switch" data-sme-fp-recur-toggle></button>')
			.attr('aria-checked', fpRecur.on ? 'true' : 'false')
			.attr('aria-label', cfg.previewRecurTitleTxt || 'Recurring booking')
			.toggleClass('is-on', fpRecur.on)
			.appendTo($head);
		if (!fpRecur.on) { return; }

		var $body = $('<div class="mpwpb-sme__fp-recur-body"></div>').appendTo($box);
		var $chips = $('<div class="mpwpb-sme__fp-recur-chips" role="radiogroup"></div>').appendTo($body);
		s.types.forEach(function (t) {
			$('<button type="button" role="radio" class="mpwpb-sme__fp-recur-chip"></button>')
				.attr('data-sme-fp-recur-type', t)
				.attr('aria-checked', t === fpRecur.type ? 'true' : 'false')
				.toggleClass('is-active', t === fpRecur.type)
				.text(labels[t] || t)
				.appendTo($chips);
		});

		var $count = $('<div class="mpwpb-sme__fp-recur-count"></div>').appendTo($body);
		$('<span class="mpwpb-sme__fp-recur-count-text"></span>').text(cfg.previewRecurCountTxt || 'Number of occurrences').appendTo($count);
		var $stepper = $('<span class="mpwpb-sme__fp-recur-stepper"></span>').appendTo($count);
		$('<button type="button" class="mpwpb-sme__fp-recur-step" data-sme-fp-recur-step="-1" aria-label="-">−</button>').prop('disabled', fpRecur.count <= 2).appendTo($stepper);
		$('<span class="mpwpb-sme__fp-recur-val"></span>').text(fpRecur.count).appendTo($stepper);
		$('<button type="button" class="mpwpb-sme__fp-recur-step mpwpb-sme__fp-recur-step--plus" data-sme-fp-recur-step="1" aria-label="+">+</button>').prop('disabled', fpRecur.count >= s.max).appendTo($stepper);

		var dates = fpRecurDates();
		var $list = $('<div class="mpwpb-sme__fp-recur-dates"></div>').appendTo($body);
		var $lhead = $('<div class="mpwpb-sme__fp-recur-dates-head"></div>').appendTo($list);
		$('<strong></strong>').text(dates.length + ' ' + (cfg.previewRecurBookingsTxt || 'bookings')).appendTo($lhead);
		$('<span></span>').text(fpFmtDate(dates[0]) + ' – ' + fpFmtDate(dates[dates.length - 1], true)).appendTo($lhead);
		var $rows = $('<div class="mpwpb-sme__fp-recur-rows"></div>').appendTo($list);
		dates.forEach(function (d, i) {
			var $r = $('<div class="mpwpb-sme__fp-recur-row"></div>').appendTo($rows);
			$('<span class="mpwpb-sme__fp-recur-num"></span>').text(i + 1).appendTo($r);
			$('<span class="mpwpb-sme__fp-recur-date"></span>').text(FP_WEEKDAYS[d.getDay()] + ', ' + fpFmtDate(d, true) + (fpSelectedTime ? ' · ' + fpSelectedTime : '')).appendTo($r);
		});
		if (s.discount > 0) {
			$('<div class="mpwpb-sme__fp-recur-discount"></div>').text(s.discount + '% ' + (cfg.previewRecurDiscountTxt || 'Recurring discount').toLowerCase()).appendTo($body);
		}
	}
	$root.on('click', '[data-sme-fp-recur-toggle]', function () {
		fpRecur.on = !fpRecur.on;
		fpRenderRecur($(this).closest('.mpwpb-sme__fp-step--datetime'));
	});
	$root.on('click', '[data-sme-fp-recur-type]', function () {
		fpRecur.type = String($(this).attr('data-sme-fp-recur-type'));
		fpRenderRecur($(this).closest('.mpwpb-sme__fp-step--datetime'));
	});
	$root.on('click', '[data-sme-fp-recur-step]', function () {
		if ($(this).prop('disabled')) { return; }
		fpRecur.count += parseInt($(this).attr('data-sme-fp-recur-step'), 10) || 0;
		fpRenderRecur($(this).closest('.mpwpb-sme__fp-step--datetime'));
	});

	function fpBuildStaffStep($step) {
		$step.empty();
		$('<div class="mpwpb-sme__fp-heading"></div>').text(cfg.previewStaffHeadingTxt || 'Choose a staff member').appendTo($step);
		$('<p class="mpwpb-sme__fp-sub"></p>').text(cfg.previewStaffSubTxt || '').appendTo($step);
		var list = fpStaffList();
		if (fpSelectedStaff && !list.some(function (p) { return p.name === fpSelectedStaff; })) { fpSelectedStaff = ''; }
		var $grid = $('<div class="mpwpb-sme__fp-staff"></div>').appendTo($step);
		var people = [{ name: '', label: cfg.previewAnyStaffTxt || 'Any Staff', avatar: '' }].concat(list.map(function (p) {
			return { name: p.name, label: p.name, avatar: p.avatar };
		}));
		people.forEach(function (p) {
			var $card = $('<button type="button" class="mpwpb-sme__fp-staff-card"></button>')
				.attr('data-sme-fp-staff', p.name)
				.toggleClass('is-selected', p.name === fpSelectedStaff)
				.appendTo($grid);
			var $av = $('<span class="mpwpb-sme__fp-staff-avatar" aria-hidden="true"></span>').appendTo($card);
			if (p.avatar) {
				$('<img alt="">').attr('src', p.avatar).appendTo($av);
			} else {
				$av.html('<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"></path><circle cx="10" cy="7" r="4"></circle><path d="M21 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>');
			}
			$('<span class="mpwpb-sme__fp-staff-name"></span>').text(p.label).appendTo($card);
		});
		if (!list.length) {
			$('<p class="mpwpb-sme__fp-staff-note"></p>').text(cfg.previewNoStaffTxt || 'No staff selected yet.').appendTo($step);
		}
		$('<button type="button" class="mpwpb-sme__fp-cta" data-sme-fp-staff-cta></button>')
			.text(cfg.previewContinueTxt || 'Continue to Checkout')
			.appendTo($step);
	}
	$root.on('click', '[data-sme-fp-staff]', function () {
		fpSelectedStaff = String($(this).attr('data-sme-fp-staff') || '');
		$(this).addClass('is-selected').siblings().removeClass('is-selected');
	});

	// Live: re-sync the mock whenever the staff / recurring settings change.
	function fpOnSettingsChange() {
		var $mock = $root.find('[data-sme-fp-mock]');
		if (!$mock.length) { return; }
		if (fpCurrentStep === 'staff' && !fpStaffOn()) {
			fpGoToStep($mock, 'datetime');
		}
		fpSyncSteps($mock);
		var $dt = $mock.find('[data-sme-fp-step="datetime"]');
		if ($dt.children().length) {
			fpRenderRecur($dt);
			fpSyncDatetimeCtaLabel($dt);
		}
		if (fpCurrentStep === 'staff') { fpBuildStaffStep($mock.find('[data-sme-fp-step="staff"]')); }
		if (fpCurrentStep === 'checkout') { fpBuildCheckoutStep($mock.find('[data-sme-fp-step="checkout"]')); }
	}
	$root.on('change input', 'input[name="mpwpb_staff_member_add"], input[name="mpwpb_enable_recurring"], input[name="mpwpb_recurring_types[]"], input[name="mpwpb_max_recurring_count"], input[name="mpwpb_recurring_discount"], #mpwpb_staff_selector', fpOnSettingsChange);
	// Staff tiles toggle .is-selected in their own click handler -- read it after that ran.
	$root.on('click', '.mpwpb-staff-tile', function () { setTimeout(fpOnSettingsChange, 0); });
	fpSyncSteps($root.find('[data-sme-fp-mock]'));

	// Delegated from $root (always present at bind time) rather than bound
	// directly to #mpwpb-csm, which mpwpb-categories-services-modern.js
	// creates its content for after this file has already run.
	$root.on('mpwpb:csm-services-changed', '#mpwpb-csm', function (e, services, categories, subCategories) {
		renderPreviewList(services, categories, subCategories);
	});
	// Same delegation reasoning as above, for mpwpb-extra-service-modern.js's
	// own list (#mpwpb-esm) -- keeps the preview's extra-services list in
	// sync with live add/edit/delete there too.
	$root.on('mpwpb:esm-items-changed', '#mpwpb-esm', function (e, items) {
		renderPreviewExtras(items);
	});

	/* ---------------------------------------------------------------- *
	 *  Required-field guard, run before the real Publish/Update proxy
	 *  below. mpwpb_shortcode_title has no visible field in this editor
	 *  (see relocateServiceTitleFields() further down — it's discarded in
	 *  favour of the WP post title, which mpwpb_shortcode_title no longer
	 *  tracks), so the real WP title (#title, mirrored by the topbar
	 *  "Service name" input) stands in for it here instead. Featured Image
	 *  lives in the sticky rail and is checked directly.
	 * ---------------------------------------------------------------- */
	function goStepForPanel($panel) {
		if (!$panel.length) { return; }
		var stepName = $panel.data('sme-panel');
		var idx = order.indexOf(stepName);
		if (idx > -1 && idx !== cur) { goStep(idx); }
	}
	function focusInvalidField($field) {
		goStepForPanel($field.closest('.mpwpb-sme__panel[data-sme-panel]'));
		setTimeout(function () {
			var top = ($field.offset() ? $field.offset().top : 0) - 100;
			$('html, body').animate({ scrollTop: top }, 200);
			if ($field.is('input,textarea,select')) { $field.trigger('focus'); }
		}, 60);
	}
	function validateRequiredFields() {
		var $firstInvalid = null;
		var $titleField = ($svcNameInline && $svcNameInline.length) ? $svcNameInline : $svcName;
		var titleOk = !!$.trim($title.length ? ($title.val() || '') : '');
		if ($titleField && $titleField.length) {
			$titleField.toggleClass('mpwpb-sme__field-invalid', !titleOk);
			if (!titleOk && !$firstInvalid) { $firstInvalid = $titleField; }
		}
		var $thumbCard = $root.find('.mpwpb-sme__feat-card');
		var thumbOk = !!$('#mpwpb-sme-thumbnail').val();
		$thumbCard.toggleClass('mpwpb-sme__field-invalid', !thumbOk);
		if (!thumbOk && !$firstInvalid) { $firstInvalid = $thumbCard; }

		if ($firstInvalid) {
			toast(cfg.requiredTxt || 'Please fill in the required fields.');
			focusInvalidField($firstInvalid);
			return false;
		}
		return true;
	}

	/* ---------------------------------------------------------------- *
	 *  Save the complete native WordPress post form over AJAX. The server
	 *  passes it through core edit_post(), preserving every save_post hook
	 *  without navigating away from the current wizard step.
	 * ---------------------------------------------------------------- */
	var isSaving = false;
	var formDirty = false;
	var localDraftTimer = null;
	var localDraftPersisted = false;
	var localDraftRestoring = false;
	var localDraftReady = false;
	var localDraftMaxAge = 30 * 24 * 60 * 60 * 1000;
	var localDraftStorageKey = '';
	var changedDuringServerSave = false;
	var localDraftLastSignature = '';

	function eachEditor(callback) {
		if (!window.tinymce) { return; }
		var editors = window.tinymce.editors || (window.tinymce.EditorManager && window.tinymce.EditorManager.editors) || [];
		$.each(editors, function (key, editor) {
			if (editor) { callback(editor); }
		});
	}

	function editorsAreDirty() {
		var dirty = false;
		eachEditor(function (editor) {
			if (editor.isDirty && editor.isDirty()) { dirty = true; }
		});
		return dirty;
	}

	function markSaved() {
		formDirty = false;
		eachEditor(function (editor) {
			if (editor.setDirty) { editor.setDirty(false); }
		});
		clearLocalDraft();
	}

	function setSaveButtonsDisabled(disabled) {
		$root.find('[data-sme-save], [data-sme-next], [data-sme-save-as]')
			.prop('disabled', disabled)
			.toggleClass('disabled', disabled);
	}

	function applySavedState(data) {
		if (!data) { return; }
		if (data.postRevision) { cfg.postRevision = data.postRevision; }
		$('#post_status, #original_post_status').val(data.postStatus || '');
		if (data.postNonce) { $('#_wpnonce').val(data.postNonce); }
		if (data.formNonce) { $('input[name="mpwpb_nonce"]').val(data.formNonce); }
		if (data.editLock) { $('#active_post_lock').val(data.editLock); }
		$('#auto_draft').val('');

		$root.find('.mpwpb-sme__status-pill')
			.text(data.statusLabel || '')
			.toggleClass('is-published', !!data.isPublished)
			.toggleClass('is-draft', !data.isPublished);
		$root.find('[data-sme-save]').text(data.primaryLabel || (cfg.updateTxt || 'Update'));
		$root.find('[data-sme-save-as="draft"]').text(data.draftLabel || 'Save Draft');
		if (cur === order.length - 1) {
			$root.find('[data-sme-next-label]').text(data.primaryLabel || (cfg.updateTxt || 'Update'));
		}

		if (data.editUrl && window.history && window.history.replaceState) {
			window.history.replaceState({}, document.title, data.editUrl);
			document.body.classList.remove('post-new-php');
			document.body.classList.add('post-php');
		}
	}

	/* ---------------------------------------------------------------- *
	 *  Browser-only draft autosave
	 *
	 *  Persists the complete modern-editor form in localStorage. Nothing
	 *  in this path calls admin-ajax.php: WordPress' server autosave stays
	 *  suspended, while Publish/Update remains the explicit server write.
	 * ---------------------------------------------------------------- */
	function localDraftKey() {
		if (localDraftStorageKey) { return localDraftStorageKey; }
		var postId = cfg.postId || $('#post_ID').val() || $('input[name="mpwpb_sme_post_id"]').val() || 0;
		var identity = 'post-' + postId;
		if (document.body.classList.contains('post-new-php')) {
			var state = (window.history && window.history.state) || {};
			var token = state.mpwpbSmeDraftToken;
			if (!token) {
				token = Date.now().toString(36) + '-' + Math.random().toString(36).slice(2, 10);
				if (window.history && window.history.replaceState) {
					var nextState = $.extend({}, state, { mpwpbSmeDraftToken: token });
					window.history.replaceState(nextState, document.title, window.location.href);
				}
			}
			identity = 'new-' + token;
		}
		var sitePath = location.pathname.split('/wp-admin/')[0] || '/';
		localDraftStorageKey = 'mpwpb_sme_local_draft_v1::' + location.host + sitePath + '::' + (cfg.userId || 0) + '::' + identity;
		return localDraftStorageKey;
	}

	function setLocalDraftStatus(state, text) {
		var $status = $root.find('[data-sme-local-save]');
		if (!$status.length) { return; }
		$status.removeClass('is-ready is-saving is-saved is-error').addClass('is-' + state);
		$status.find('[data-sme-local-save-text]').text(text || '');
	}

	function localDraftFieldExcluded(field) {
		var type = (field.type || '').toLowerCase();
		var name = field.name || '';
		return !name ||
			type === 'button' || type === 'submit' || type === 'reset' ||
			type === 'file' || type === 'password' ||
			name === '_wpnonce' || name === '_wp_http_referer' || name === 'mpwpb_nonce' ||
			name === 'action' || name.indexOf('mpwpb_payment_method_settings') === 0;
	}

	function collectLocalDraftFields() {
		var occurrences = Object.create(null);
		var fields = [];
		$root.find(':input[name]').each(function () {
			if (localDraftFieldExcluded(this)) { return; }
			var name = this.name;
			var type = (this.type || this.tagName || '').toLowerCase();
			var index = occurrences[name] || 0;
			occurrences[name] = index + 1;
			var item = { name: name, index: index, type: type };
			if (type === 'checkbox' || type === 'radio') {
				item.checked = !!this.checked;
				item.value = $(this).val();
			} else if (this.tagName === 'SELECT' && this.multiple) {
				item.value = $(this).val() || [];
			} else {
				item.value = $(this).val();
			}
			fields.push(item);
		});
		return fields;
	}

	function collectLocalDraftEditors() {
		var editors = {};
		eachEditor(function (editor) {
			var element = editor.getElement ? editor.getElement() : null;
			if (element && $.contains($root[0], element) && editor.getContent) {
				editors[editor.id] = editor.getContent();
			}
		});
		return editors;
	}

	function collectLocalDraftRepeaters() {
		return $root.find('.mp_item_insert.mp_sortable_area').map(function (index) {
			return {
				index: index,
				count: $(this).children('.mp_remove_area').length
			};
		}).get();
	}

	function currentLocalDraftSignature() {
		return JSON.stringify({
			fields: collectLocalDraftFields(),
			editors: collectLocalDraftEditors(),
			repeaters: collectLocalDraftRepeaters(),
			heroUrl: $('#mpwpb-sme-hero-img').attr('src') || ''
		});
	}

	function writeLocalDraft() {
		if (!localDraftReady || localDraftRestoring || isSaving) { return; }
		var draft = {
			version: 1,
			timestamp: Date.now(),
			revision: cfg.postRevision || '',
			isNew: document.body.classList.contains('post-new-php'),
			fields: collectLocalDraftFields(),
			editors: collectLocalDraftEditors(),
			repeaters: collectLocalDraftRepeaters(),
			heroUrl: $('#mpwpb-sme-hero-img').attr('src') || ''
		};
		try {
			localStorage.setItem(localDraftKey(), JSON.stringify(draft));
			localDraftLastSignature = JSON.stringify({
				fields: draft.fields,
				editors: draft.editors,
				repeaters: draft.repeaters,
				heroUrl: draft.heroUrl
			});
			localDraftPersisted = true;
			setLocalDraftStatus('saved', cfg.localSavedTxt || 'Draft saved locally');
		} catch (error) {
			localDraftPersisted = false;
			setLocalDraftStatus('error', cfg.localUnavailableTxt || 'Local autosave unavailable');
		}
	}

	function scheduleLocalDraft() {
		if (!localDraftReady || localDraftRestoring || isSaving) { return; }
		localDraftPersisted = false;
		setLocalDraftStatus('saving', cfg.localSavingTxt || 'Saving locally…');
		clearTimeout(localDraftTimer);
		localDraftTimer = setTimeout(writeLocalDraft, 650);
	}

	function clearLocalDraft() {
		clearTimeout(localDraftTimer);
		localDraftPersisted = false;
		try { localStorage.removeItem(localDraftKey()); } catch (error) {}
		localDraftLastSignature = currentLocalDraftSignature();
		setLocalDraftStatus('ready', cfg.localReadyTxt || 'Local autosave ready');
	}

	function restoreLocalDraftRepeaters(repeaters) {
		if (!$.isArray(repeaters)) { return; }
		var $containers = $root.find('.mp_item_insert.mp_sortable_area');
		$.each(repeaters, function (_, repeater) {
			var $container = $containers.eq(parseInt(repeater.index, 10));
			var wanted = Math.max(0, parseInt(repeater.count, 10) || 0);
			if (!$container.length) { return; }
			var current = $container.children('.mp_remove_area').length;
			if (wanted < current) {
				$container.children('.mp_remove_area').slice(wanted).remove();
				return;
			}
			var $add = $container.closest('.mp_settings_area').find('.mp_add_item').first();
			while ($add.length && current < wanted) {
				var previous = current;
				$add.trigger('click');
				current = $container.children('.mp_remove_area').length;
				// A malformed/missing repeater template must never create a loop.
				if (current >= wanted || current <= previous) { break; }
			}
		});
	}

	function restoreLocalDraftFields(fields) {
		if (!$.isArray(fields)) { return; }
		$.each(fields, function (_, item) {
			var $field = $root.find(':input[name]').filter(function () {
				return this.name === item.name;
			}).eq(parseInt(item.index, 10) || 0);
			if (!$field.length || localDraftFieldExcluded($field[0])) { return; }
			if (item.type === 'checkbox' || item.type === 'radio') {
				$field.prop('checked', !!item.checked);
			} else {
				$field.val(item.value);
			}
		});
	}

	function restoreLocalDraftEditors(editors, attempt) {
		if (!editors || typeof editors !== 'object') { return; }
		if (!window.tinymce) {
			if (attempt < 12) {
				setTimeout(function () { restoreLocalDraftEditors(editors, attempt + 1); }, 250);
			}
			return;
		}
		var pending = false;
		$.each(editors, function (id, content) {
			var editor = tinymce.get(id);
			if (editor && editor.setContent) {
				editor.setContent(content || '');
				if (editor.setDirty) { editor.setDirty(true); }
			} else {
				pending = true;
			}
		});
		if (pending && attempt < 12) {
			setTimeout(function () { restoreLocalDraftEditors(editors, attempt + 1); }, 250);
		}
	}

	function syncRestoredLocalDraftUi(draft) {
		var restoredTitle = $('#mpwpb-sme-title').val() || $('#mpwpb-sme-title-inline').val() || '';
		$('#title, #mpwpb-sme-title, #mpwpb-sme-title-inline').val(restoredTitle);

		$root.find('input[type="checkbox"][data-collapse-target]').each(function () {
			var $field = $(this);
			var $target = $root.find('[data-collapse="' + $field.data('collapse-target') + '"]');
			$target.toggleClass('mActive', this.checked).toggle(this.checked);
		});
		$root.find('select[data-collapse-target]').each(function () {
			$(this).find('option[data-option-target], option[data-option-target-multi]').each(function () {
				var option = this;
				var targets = $(option).attr('data-option-target-multi') || $(option).attr('data-option-target') || '';
				$.each(targets.toString().split(/\s+/), function (_, target) {
					if (!target) { return; }
					$root.find('[data-collapse="' + target + '"]')
						.toggleClass('mActive', option.selected).toggle(option.selected);
				});
			});
		});
		$root.find('select.select2-hidden-accessible').trigger('change.select2');
		$root.find('.mpwpb-staff-tile input[type="checkbox"]').each(function () {
			$(this).closest('.mpwpb-staff-tile').toggleClass('is-selected', this.checked);
		});

		var thumbnailId = $('#mpwpb-sme-thumbnail').val();
		if (thumbnailId || draft.heroUrl) {
			setHero(thumbnailId, draft.heroUrl || $('#mpwpb-sme-hero-img').attr('src') || '');
		} else {
			setHero('', '');
		}
	}

	function restoreLocalDraft() {
		var raw;
		try { raw = localStorage.getItem(localDraftKey()); } catch (error) {
			setLocalDraftStatus('error', cfg.localUnavailableTxt || 'Local autosave unavailable');
			return false;
		}
		if (!raw) { return false; }
		try {
			var draft = JSON.parse(raw);
			if (!draft || draft.version !== 1 || !draft.timestamp || Date.now() - draft.timestamp > localDraftMaxAge) {
				clearLocalDraft();
				return false;
			}
			if (!draft.isNew && draft.revision && cfg.postRevision && draft.revision !== cfg.postRevision) {
				clearLocalDraft();
				return false;
			}
			localDraftRestoring = true;
			restoreLocalDraftRepeaters(draft.repeaters);
			restoreLocalDraftFields(draft.fields);
			restoreLocalDraftEditors(draft.editors, 0);
			syncRestoredLocalDraftUi(draft);
			localDraftRestoring = false;
			localDraftPersisted = true;
			formDirty = true;
			setLocalDraftStatus('saved', cfg.localRestoredTxt || 'Local draft restored');
			toast(cfg.localRestoredTxt || 'Local draft restored');
			return true;
		} catch (error) {
			localDraftRestoring = false;
			clearLocalDraft();
			return false;
		}
	}

	function suspendServerAutosave(attempt) {
		attempt = attempt || 0;
		if (window.wp && wp.autosave && wp.autosave.server && wp.autosave.server.suspend) {
			wp.autosave.server.suspend();
			return;
		}
		if (attempt < 20) {
			setTimeout(function () { suspendServerAutosave(attempt + 1); }, 250);
		}
	}

	function saveForm(mode) {
		if (isSaving || !cfg.ajax) { return; }
		if (mode !== 'draft' && !validateRequiredFields()) { return; }

		var form = document.getElementById('post');
		if (!form || !window.FormData) { return; }
		if (window.tinyMCE) { window.tinyMCE.triggerSave(); }

		var data = new FormData(form);
		data.set('action', 'mpwpb_save_service_editor');
		data.set('save_mode', mode === 'draft' ? 'draft' : 'primary');
		isSaving = true;
		changedDuringServerSave = false;
		setSaveButtonsDisabled(true);
		toast(cfg.savingTxt || 'Saving…');
		if (window.wp && wp.autosave && wp.autosave.server) {
			wp.autosave.server.suspend();
		}

		$.ajax({
			url: cfg.ajax,
			type: 'POST',
			data: data,
			processData: false,
			contentType: false,
			dataType: 'json'
		}).done(function (resp) {
			if (resp && resp.success) {
				applySavedState(resp.data);
				markSaved();
				if (resp.data && resp.data.postId) {
					cfg.postId = resp.data.postId;
					localDraftStorageKey = '';
				}
				toast((resp.data && resp.data.message) || cfg.savedTxt || 'Saved');
				$root.trigger('mpwpb:sme-saved', [resp.data]);
			} else {
				toast((resp && resp.data && resp.data.message) || cfg.saveErrorTxt || 'The service could not be saved.');
			}
		}).fail(function (xhr) {
			var message = xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message;
			toast(message || cfg.saveErrorTxt || 'The service could not be saved.');
		}).always(function () {
			isSaving = false;
			setSaveButtonsDisabled(false);
			suspendServerAutosave();
			if (changedDuringServerSave) {
				formDirty = true;
				scheduleLocalDraft();
			}
		});
	}

	function submitForm() {
		saveForm('primary');
	}
	$root.on('click', '[data-sme-save]', function (e) {
		e.preventDefault();
		submitForm();
	});

	/* ---------------------------------------------------------------- *
	 *  Split-button dropdown — one extra option, always the opposite of
	 *  whatever the primary button already does ("Update"/"Publish"),
	 *  plus "Classic editor". "Save as Draft"/"Switch to Draft" sends
	 *  WordPress' core 'saveasdraft' intent through the same AJAX save, so
	 *  post_status ends up 'draft' regardless of the primary action.
	 * ---------------------------------------------------------------- */
	var $split = $root.find('[data-sme-split]');
	var $splitToggle = $split.find('[data-sme-split-toggle]');
	var $splitMenu = $split.find('[data-sme-split-menu]');

	function closeSplitMenu() {
		$splitMenu.attr('hidden', true);
		$splitToggle.attr('aria-expanded', 'false');
	}
	function openSplitMenu() {
		$splitMenu.removeAttr('hidden');
		$splitToggle.attr('aria-expanded', 'true');
	}

	$splitToggle.on('click', function (e) {
		e.preventDefault();
		e.stopPropagation();
		if ($splitMenu.attr('hidden')) { openSplitMenu(); } else { closeSplitMenu(); }
	});
	$(document).on('click', function (e) {
		if ($split.length && !$split.is(e.target) && $split.has(e.target).length === 0) {
			closeSplitMenu();
		}
	});
	$(document).on('keydown', function (e) {
		if (e.key === 'Escape' || e.keyCode === 27) { closeSplitMenu(); }
	});

	function submitFormAs(status) {
		saveForm(status === 'draft' ? 'draft' : 'primary');
	}
	$splitMenu.on('click', '[data-sme-save-as]', function (e) {
		e.preventDefault();
		closeSplitMenu();
		submitFormAs($(this).data('sme-save-as'));
	});
	$splitMenu.on('click', '[data-sme-ui]', function () {
		closeSplitMenu();
	});

	/* ---------------------------------------------------------------- *
	 *  Preview intentionally has no JavaScript click proxy. The visible
	 *  anchor already contains WordPress' canonical preview URL and target;
	 *  allowing the browser's trusted navigation keeps left-, middle- and
	 *  keyboard activation consistent and avoids popup blocking.
	 * ---------------------------------------------------------------- */

	/* ---------------------------------------------------------------- *
	 *  Service name <-> hidden WP #title sync (title box is CSS-hidden).
	 *  Two visual proxies (topbar + the inline "Title" field in the Basic
	 *  Information card) both mirror the one real #title input.
	 * ---------------------------------------------------------------- */
	var $title = $('#title');
	var $svcName = $('#mpwpb-sme-title'); // the editable topbar title
	var $svcNameInline = $('#mpwpb-sme-title-inline'); // inline "Title" field
	if ($title.length && ($svcName.length || $svcNameInline.length)) {
		if ($svcName.length && !$svcName.val() && $title.val()) {
			$svcName.val($title.val());
		}
		if ($svcNameInline.length && !$svcNameInline.val() && $title.val()) {
			$svcNameInline.val($title.val());
		}
		$svcName.add($svcNameInline).on('input', function () {
			var val = $(this).val();
			$title.val(val);
			$svcName.add($svcNameInline).not(this).val(val);
			$('#title-prompt-text').addClass('screen-reader-text');
			$svcName.add($svcNameInline).toggleClass('mpwpb-sme__field-invalid', !$.trim(val));
		});
	}

	/* ---------------------------------------------------------------- *
	 *  Relocate the real "Service Overview" editor (rendered normally,
	 *  once, by the reused MPWPB_Service_Details::service_details() further
	 *  down this same step) up into the Basic Information card — a real DOM
	 *  node moved via appendTo(), not a duplicate render, so
	 *  mpwpb_service_overview_content still submits exactly once. The
	 *  now-empty heading + toggle wrapper left behind in the Service
	 *  Details card are discarded entirely (including the real classic
	 *  on/off checkbox) — the modern editor always submits Service Overview
	 *  as "on" via a hidden field placed in Basic Information instead
	 *  (Classic mode's own toggle is untouched and still fully functional
	 *  there).
	 * ---------------------------------------------------------------- */
	(function relocateServiceOverview() {
		var $overviewSection = $root.find('[data-sme-section="MPWPB_Service_Details"] .mpwpb-service-overview');
		var $contentSlot = $root.find('[data-sme-overview-slot]');
		if (!$overviewSection.length || !$contentSlot.length) {
			return;
		}
		var $toggleSection = $overviewSection.prev();
		var $headingSection = $toggleSection.prev();

		$overviewSection.appendTo($contentSlot);
		$toggleSection.remove();
		$headingSection.remove();
	})();

	/* ---------------------------------------------------------------- *
	 *  Relocate "Service Sub Title" (rendered normally, once, by the reused
	 *  MPWPB_General_Settings::general_settings() further down this same
	 *  step) up into the Basic Information card — only the real <textarea>
	 *  is moved (so mpwpb_shortcode_sub_title still submits exactly once);
	 *  the classic section wrapper + its own label text are decorative-only
	 *  once the field is gone, so it's removed to avoid an empty leftover
	 *  row. "Service Title" is discarded outright, not relocated — the
	 *  modern editor doesn't show it at all (Basic Information carries a
	 *  hidden field preserving its existing value instead, so nothing is
	 *  lost on save); Classic mode's own field is untouched.
	 * ---------------------------------------------------------------- */
	(function relocateServiceTitleFields() {
		var $card = $root.find('[data-sme-section="MPWPB_General_Settings"]');
		if (!$card.length) {
			return;
		}
		$card.find('.service-title').remove();

		var $subTitleSection = $card.find('.service-sub-title');
		var $subTitleSlot = $root.find('[data-sme-service-subtitle-slot]');
		if ($subTitleSection.length && $subTitleSlot.length) {
			$subTitleSection.find('textarea[name="mpwpb_shortcode_sub_title"]').appendTo($subTitleSlot);
			$subTitleSection.remove();
		}
	})();

	/* ---------------------------------------------------------------- *
	 *  Service details card: live character counts (name / tagline) and
	 *  the overview "Show on page" switch (real mpwpb_service_overview_
	 *  status checkbox) hiding the editor behind a short note.
	 * ---------------------------------------------------------------- */
	(function serviceDetailsCard() {
		var $card = $root.find('.mpwpb-sme__details');
		if (!$card.length) {
			return;
		}
		$card.find('textarea[name="mpwpb_shortcode_sub_title"]').attr({ id: 'mpwpb_shortcode_sub_title', rows: 2 });
		$card.find('[data-sme-count-for]').each(function () {
			var $count = $(this);
			var $field = $card.find($count.attr('data-sme-count-for'));
			var max = parseInt($count.attr('data-max'), 10) || 0;
			if (!$field.length) {
				$count.remove();
				return;
			}
			function update() {
				var n = String($field.val() || '').length;
				$count.text(n + ' / ' + max).toggleClass('is-over', max > 0 && n > max);
			}
			$field.on('input', update);
			update();
		});
		var $toggle = $card.find('[data-sme-overview-toggle]');
		function syncOverview() {
			var on = $toggle.is(':checked');
			$card.find('[data-sme-overview-slot]').toggle(on);
			$card.find('[data-sme-overview-off]').prop('hidden', on);
		}
		$toggle.on('change', syncOverview);
		syncOverview();
	})();

	/* ---------------------------------------------------------------- *
	 *  Relocate a reused section's own on/off switch (e.g. Extra Service's
	 *  real "mpwpb_extra_service_active" checkbox) up into the card header,
	 *  next to the title — moving the real .roundSwitchLabel node (not a
	 *  copy), so the checkbox is still submitted exactly once regardless
	 *  of where in the DOM it visually sits.
	 * ---------------------------------------------------------------- */
	/* ---------------------------------------------------------------- *
	 *  The shared collapse script slideToggles a switch's panel on every
	 *  click of its .roundSwitch span without looking at the checkbox, so a
	 *  click on the rest of the label (or a fast double click) leaves the
	 *  switch "off" with its panel open, or the reverse. Once that toggle
	 *  animation has finished, settle each panel to its checkbox state.
	 * ---------------------------------------------------------------- */
	(function keepSwitchPanelsInSync() {
		function settle(input) {
			var target = $(input).siblings('[data-collapse-target]').data('collapse-target');
			if (!target) { return; }
			var on = input.checked;
			$root.find('[data-collapse="' + target + '"]').each(function () {
				var $p = $(this);
				$p.stop(true, true);
				$p.toggleClass('mActive', on);
				// Judge by the panel's own display: on a step that isn't showing,
				// :visible is false for every panel, open or not.
				if (($p.css('display') !== 'none') === on) { return; }
				if (!$p.parent().is(':visible')) {
					$p.css('display', on ? 'block' : 'none');
				} else if (on) {
					$p.slideDown(200);
				} else {
					$p.slideUp(200);
				}
			});
		}
		$root.on('change.mpwpb-sme-switch', '.roundSwitchLabel input[type="checkbox"]', function () {
			var input = this;
			setTimeout(function () { settle(input); }, 300);
		});
		$root.on('click.mpwpb-sme-switch', '.roundSwitchLabel', function () {
			var input = $(this).find('input[type="checkbox"]')[0];
			if (input) { setTimeout(function () { settle(input); }, 300); }
		});
	})();

	/* ---------------------------------------------------------------- *
	 *  Extra Service card: its switch had no effect on the card itself.
	 *  Hide the list and show a short note while it's off (the list is
	 *  kept and still saved), same as the other switch cards.
	 * ---------------------------------------------------------------- */
	(function extraServiceOffState() {
		var $card = $root.find('[data-sme-section="MPWPB_Extra_Service_Modern"]');
		var $list = $card.find('#mpwpb-esm');
		var $toggle = $card.find('input[name="mpwpb_extra_service_active"]');
		if (!$list.length || !$toggle.length) { return; }
		var $off = $('<p class="mpwpb-sme__extra-off"></p>').text(cfg.extraOffNote || 'Extra services are off.').insertAfter($list);
		function sync() {
			var on = $toggle.is(':checked');
			$list.toggle(on);
			$off.prop('hidden', on);
		}
		$toggle.on('change.mpwpb-sme-extra', sync);
		sync();
	})();

	(function relocateHeaderToggles() {
		var sectionsWithHeaderToggle = {
			'MPWPB_Extra_Service_Modern': 'mpwpb_extra_service_active',
			'MPWPB_Service_Details': 'mpwpb_service_details_status',
			'MPWPB_Faq_Settings': 'mpwpb_faq_active',
			'Tax_Settings': 'mpwpb_tax_enabled',
			'MPWPB_Happy_Hours_Settings': 'mpwpb_happy_hours_enabled'
		};
		Object.keys(sectionsWithHeaderToggle).forEach(function (sectionClass) {
			var $card = $root.find('[data-sme-section="' + sectionClass + '"]');
			var $slot = $card.find('[data-sme-header-actions]').first();
			var $toggle = $card.find('input[name="' + sectionsWithHeaderToggle[sectionClass] + '"]').closest('.roundSwitchLabel');
			if ($slot.length && $toggle.length) {
				$toggle.appendTo($slot);
			}
		});
	})();

	/* ---------------------------------------------------------------- *
	 *  Clean up what's left in the Service Details card after Service
	 *  Features and Service Review were relocated elsewhere and its own
	 *  toggle moved to the card header above:
	 *   1) the classic "Enable Service Details" row (label text + toggle,
	 *      now toggle-less) — only removed once confirmed empty (no
	 *      checkbox left inside), so this is a no-op if the header-toggle
	 *      relocation didn't run.
	 *   2) the "Service Details" sub-heading — now redundant with this
	 *      card's own title, since the editor below is the only content
	 *      left in the card. Safe to remove even though it's a
	 *      section.section: Service Features' (hidden, further up, never
	 *      removed) remains the true first-of-type in this card either way.
	 * ---------------------------------------------------------------- */
	(function cleanupServiceDetailsCard() {
		var $detailsSection = $root.find('[data-sme-section="MPWPB_Service_Details"] .mpwpb-service-details');
		if (!$detailsSection.length) {
			return;
		}
		var $toggleRow = $detailsSection.prev();
		if ($toggleRow.length && !$toggleRow.find('input[type="checkbox"]').length) {
			$toggleRow.remove();
		}
		var $headingSection = $detailsSection.prev();
		if ($headingSection.length && $headingSection.is('section.section')) {
			$headingSection.remove();
		}
	})();

	/* ---------------------------------------------------------------- *
	 *  Full description card (MPWPB_Service_Details): icon, a "Show on
	 *  page" label beside the header switch (the real mpwpb_service_
	 *  details_status checkbox, already in the header), the classic
	 *  leftover headings removed, and a note while it's switched off.
	 *  The editor is the real WP editor -- only its frame is restyled.
	 * ---------------------------------------------------------------- */
	(function fullDescriptionCard() {
		var $card = $root.find('[data-sme-section="MPWPB_Service_Details"]');
		if (!$card.length) {
			return;
		}
		$card.addClass('mpwpb-sme__desc-card');
		$card.find('.tabsItem > header').remove();
		// The leftover section.section headings stay: the first ("Service Features")
		// is already hidden by CSS, and the "Service Review" one is the anchor the
		// review-fields relocation below uses to find its fields.
		var $head = $card.find('.mpwpb-sme__postfields-header').first();
		if (!$head.find('.mpwpb-sme__desc-icon').length) {
			$head.prepend('<span class="mpwpb-sme__desc-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16M4 10h16M4 14h10M4 18h7"/></svg></span>');
		}
		var $toggle = $card.find('input[name="mpwpb_service_details_status"]');
		var $actions = $card.find('[data-sme-header-actions]').first();
		if ($toggle.length && !$actions.find('.mpwpb-sme__desc-switch-label').length) {
			$actions.prepend($('<span class="mpwpb-sme__desc-switch-label"></span>').text(cfg.showOnPageTxt || 'Show on page'));
			$toggle.attr('aria-label', cfg.showOnPageTxt || 'Show on page');
		}
		var $note = $('<p class="mpwpb-sme__desc-off"></p>')
			.text(cfg.descriptionOffTxt || 'The full description is hidden on the service page. Your text is kept; switch it on to show it again.');
		// In the card body, outside .tabsItem (other cleanup re-parents/strips that area).
		$card.find('.mpwpb-sme__postfields-body').first().append($note);
		function sync() {
			$note.prop('hidden', !$toggle.length || $toggle.is(':checked'));
		}
		$toggle.on('change', sync);
		// The shared .roundSwitch handler flips the checkbox without a change event.
		$toggle.closest('.roundSwitchLabel').on('click', function () { setTimeout(sync, 0); });
		sync();
	})();

	/* ---------------------------------------------------------------- *
	 *  The classic "Enable FAQ Section" row (label text + the toggle just
	 *  relocated to the FAQ card header above) would otherwise be left
	 *  behind as an empty leftover line. Only removed once confirmed empty
	 *  (no checkbox left inside), so it's a no-op if the header-toggle
	 *  relocation didn't run.
	 * ---------------------------------------------------------------- */
	(function cleanupFaqToggleRow() {
		var $faqSection = $root.find('[data-sme-section="MPWPB_Faq_Settings"] .mpwpb-faq-section');
		if (!$faqSection.length) {
			return;
		}
		var $toggleRow = $faqSection.prev();
		if ($toggleRow.length && !$toggleRow.find('input[type="checkbox"]').length) {
			$toggleRow.remove();
		}
	})();

	/* ---------------------------------------------------------------- *
	 *  FAQ card: numbered question rows (drag handle, expandable answer,
	 *  edit/delete buttons), a list head with the count and "Add question",
	 *  an empty state, and a clearer Add/Edit dialog with a required
	 *  question. Every add/update/delete/sort still goes through
	 *  mpwpb_admin.js's AJAX handlers, which re-render .mpwpb-faq-items
	 *  with the classic fragment -- decorate() re-runs on each re-render.
	 * ---------------------------------------------------------------- */
	(function modernizeFaq() {
		var $card = $root.find('[data-sme-section="MPWPB_Faq_Settings"]');
		var $section = $card.find('.mpwpb-faq-section');
		var $list = $section.find('.mpwpb-faq-items');
		if (!$list.length || $card.data('sme-faq')) { return; }
		$card.data('sme-faq', true).addClass('mpwpb-sme__faq-card');
		var t = function (key, fallback) { return cfg[key] || fallback; };
		var svg = {
			grip: '<svg width="12" height="16" viewBox="0 0 12 16" fill="currentColor" aria-hidden="true"><circle cx="3" cy="3" r="1.4"/><circle cx="9" cy="3" r="1.4"/><circle cx="3" cy="8" r="1.4"/><circle cx="9" cy="8" r="1.4"/><circle cx="3" cy="13" r="1.4"/><circle cx="9" cy="13" r="1.4"/></svg>',
			chev: '<svg class="mpwpb-faq2__chev" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>',
			edit: '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/></svg>',
			bin: '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/></svg>',
			plus: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>'
		};

		var $head = $card.find('.mpwpb-sme__postfields-header').first();
		if (!$head.find('.mpwpb-sme__faq-icon').length) {
			$head.prepend('<span class="mpwpb-sme__faq-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M9.5 9a2.5 2.5 0 1 1 3.5 2.3c-.6.3-1 .9-1 1.6v.6"/><path d="M12 17h.01"/></svg></span>');
		}

		// List head: title, count + hint, and the real "Add" button (it keeps
		// its data-modal / .mpwpb-faq-item-new hooks).
		var $listHead = $('<div class="mpwpb-faq2__head"><div><div class="mpwpb-faq2__title"></div><div class="mpwpb-faq2__sub"></div></div></div>');
		$listHead.find('.mpwpb-faq2__title').text(t('faqListTitle', 'Questions'));
		var $addBtn = $section.find('button.mpwpb-faq-item-new').first();
		$addBtn.empty().append(svg.plus).append($('<span></span>').text(t('faqAddTxt', 'Add question'))).appendTo($listHead);
		$section.prepend($listHead);
		var $empty = $('<div class="mpwpb-faq2__empty"><strong></strong><span></span></div>');
		$empty.find('strong').text(t('faqEmptyTitle', 'No questions yet'));
		$empty.find('span').text(t('faqEmptyText', ''));
		$list.after($empty);

		var $toggle = $card.find('input[name="mpwpb_faq_active"]');
		var $off = $('<p class="mpwpb-faq2__off"></p>').text(t('faqOffNote', '')).insertAfter($section);
		function syncOn() { $off.prop('hidden', $toggle.is(':checked')); }
		$toggle.on('change.mpwpb-sme-faq', syncOn);
		syncOn();

		var decorating = false;
		function decorate() {
			if (decorating) { return; }
			decorating = true;
			var $rows = $list.children('.mpwpb-faq-item');
			$rows.each(function (i) {
				var $row = $(this);
				if (!$row.data('sme-faq-row')) {
					$row.data('sme-faq-row', true).addClass('mpwpb-faq2__item');
					var id = $row.attr('data-id');
					var $h = $row.children('.faq-header');
					var $c = $row.children('.faq-content');
					var title = $.trim($h.find('p').first().text());
					var answerId = 'mpwpb-faq-answer-' + id;
					// Expanding is handled here, not by the shared collapse script.
					$h.removeAttr('data-collapse-target');
					$c.removeAttr('data-collapse').removeClass('mActive').attr('id', answerId).hide();
					var $bar = $('<div class="mpwpb-faq2__bar"></div>');
					$('<span class="mpwpb-faq2__handle"></span>').attr('title', t('faqDragTxt', 'Drag to reorder')).html(svg.grip).appendTo($bar);
					$('<span class="mpwpb-faq2__num" aria-hidden="true"></span>').appendTo($bar);
					var $open = $('<button type="button" class="mpwpb-faq2__toggle" aria-expanded="false"></button>').attr('aria-controls', answerId);
					$('<span class="mpwpb-faq2__q" data-faq-title></span>').text(title).appendTo($open);
					$open.append(svg.chev).appendTo($bar);
					$('<button type="button" class="mpwpb-faq2__icon mpwpb-faq-item-edit" data-modal="mpwpb-faq-item-new"></button>').attr('aria-label', t('faqEditTxt', 'Edit question')).html(svg.edit).appendTo($bar);
					$('<button type="button" class="mpwpb-faq2__icon mpwpb-faq2__icon--danger mpwpb-faq-item-delete"></button>').attr('aria-label', t('faqDeleteTxt', 'Delete question')).html(svg.bin).appendTo($bar);
					$h.empty().append($bar);
				}
				$row.find('.mpwpb-faq2__num').text(i + 1);
			});
			var n = $rows.length;
			$listHead.find('.mpwpb-faq2__sub').text((n === 1 ? t('faqCountOne', '1 question') : String(t('faqCountMany', '%d questions')).replace('%d', n)) + (n > 1 ? ' · ' + t('faqDragHint', '') : ''));
			$list.toggle(n > 0);
			$empty.toggle(n === 0);
			decorating = false;
		}
		decorate();
		if (window.MutationObserver) {
			new MutationObserver(decorate).observe($list[0], { childList: true });
		}
		$list.on('click', '.mpwpb-faq2__toggle', function () {
			var $row = $(this).closest('.mpwpb-faq-item');
			var open = !$row.hasClass('is-open');
			$row.toggleClass('is-open', open);
			$(this).attr('aria-expanded', open ? 'true' : 'false');
			$row.children('.faq-content').stop(true, true)[open ? 'slideDown' : 'slideUp'](150);
		});
		// Drag only from the handle, so the row's buttons stay clickable.
		function sortHandle() {
			if ($list.hasClass('ui-sortable')) {
				$list.sortable('option', 'handle', '.mpwpb-faq2__handle');
				return true;
			}
			return false;
		}
		if (!sortHandle()) { $(window).on('load', sortHandle); }

		// Add / Edit dialog.
		var $modal = $card.find('[data-modal-target="mpwpb-faq-item-new"]');
		if (!$modal.length) { return; }
		$modal.addClass('mpwpb-faq2-modal');
		var $dialog = $modal.find('.mpwpb-modal-content').first().attr({ role: 'dialog', 'aria-modal': 'true', 'aria-labelledby': 'mpwpb-faq-modal-title' });
		var $h3 = $modal.find('.title h3').first().attr('id', 'mpwpb-faq-modal-title');
		$('<p class="mpwpb-faq2__modal-sub"></p>').text(t('faqModalSub', '')).insertAfter($h3);
		$modal.find('.mpwpb-modal-close').attr({ role: 'button', tabindex: '0', 'aria-label': t('faqCancelTxt', 'Cancel') });
		var $title = $modal.find('input[name="mpwpb_faq_title"]').attr({ id: 'mpwpb-faq-q', placeholder: t('faqQuestionPh', ''), autocomplete: 'off' });
		var $labels = $modal.find('.content > label');
		// Retitle the two classic labels (their first text node only; the
		// hidden inputs inside the first one stay where they are).
		function relabel($label, text, forId) {
			$label.contents().filter(function () { return this.nodeType === 3 && $.trim(this.nodeValue); }).first().replaceWith($('<span class="mpwpb-faq2__label"></span>').text(text));
			if (forId) { $label.attr('for', forId); }
		}
		relabel($labels.eq(0), t('faqQuestionLabel', 'Question'), 'mpwpb-faq-q');
		relabel($labels.eq(1), t('faqAnswerLabel', 'Answer'));
		var $qErr = $('<p class="mpwpb-faq2__error" role="alert"></p>').text(t('faqQuestionError', '')).hide().insertAfter($title);
		$modal.find('#mpwpb_faq_save').text(t('faqSaveAnother', 'Save & add another')).removeClass('button-primary');
		$modal.find('#mpwpb_faq_save_close').text(t('faqSaveTxt', 'Save question'));
		$modal.find('#mpwpb_faq_update').text(t('faqSaveChanges', 'Save changes'));
		var $foot = $('<div class="mpwpb-faq2__foot"></div>');
		$('<button type="button" class="mpwpb-faq2__cancel"></button>').text(t('faqCancelTxt', 'Cancel')).appendTo($foot);
		$foot.append($modal.find('.mpwpb_faq_save_buttons'), $modal.find('.mpwpb_faq_update_buttons'));
		$dialog.append($foot);
		$modal.find('#mpwpb-faq-msg').insertBefore($foot);

		function setMode(isEdit) {
			$h3.text(isEdit ? t('faqModalEdit', 'Edit question') : t('faqModalAdd', 'Add question'));
			$title.removeClass('is-error');
			$qErr.hide();
			setTimeout(function () { $title.trigger('focus'); }, 50);
		}
		$(document).on('click.mpwpb-sme-faq', '.mpwpb-faq-item-new', function () { setMode(false); });
		$(document).on('click.mpwpb-sme-faq', '.mpwpb-faq-item-edit', function () { setMode(true); });
		// Runs before mpwpb_admin.js's document-level save handlers: block an
		// empty question there.
		$modal.on('click', '#mpwpb_faq_save, #mpwpb_faq_save_close, #mpwpb_faq_update', function (e) {
			if ($.trim($title.val())) { return; }
			e.preventDefault();
			e.stopPropagation();
			$title.addClass('is-error').trigger('focus');
			$qErr.show();
		});
		$title.on('input', function () {
			if ($.trim($title.val())) { $title.removeClass('is-error'); $qErr.hide(); }
		});
		$modal.on('click', '.mpwpb-faq2__cancel', function () { $modal.removeClass('open'); });
		$modal.on('keydown', function (e) {
			if (e.key === 'Escape') { $modal.removeClass('open'); }
			if (e.key === 'Enter' && $(e.target).is('.mpwpb-modal-close')) { $modal.removeClass('open'); }
		});
	})();

	/* ---------------------------------------------------------------- *
	 *  The classic "Enable Tax" row (label text + the toggle just
	 *  relocated to the Tax Settings card header above) would otherwise be
	 *  left behind as an empty leftover line. Only removed once confirmed
	 *  empty (no checkbox left inside), so it's a no-op if the
	 *  header-toggle relocation didn't run.
	 * ---------------------------------------------------------------- */
	(function cleanupTaxToggleRow() {
		var $taxClassRow = $root.find('[data-sme-section="Tax_Settings"] #mpwpb_tax_class_row');
		if (!$taxClassRow.length) {
			return;
		}
		var $toggleRow = $taxClassRow.prev();
		if ($toggleRow.length && !$toggleRow.find('input[type="checkbox"]').length) {
			$toggleRow.remove();
		}
	})();

	/* ---------------------------------------------------------------- *
	 *  Same cleanup as cleanupTaxToggleRow(), for the "Enable Happy
	 *  Hours" row after its toggle is relocated to the card header.
	 * ---------------------------------------------------------------- */
	(function cleanupHappyHoursToggleRow() {
		var $row = $root.find('[data-sme-section="MPWPB_Happy_Hours_Settings"] #mpwpb_happy_hours_row');
		if (!$row.length) {
			return;
		}
		var $toggleRow = $row.prev();
		if ($toggleRow.length && !$toggleRow.find('input[type="checkbox"]').length) {
			$toggleRow.remove();
		}
	})();

	/* ---------------------------------------------------------------- *
	 *  Happy Hours card: time-window steppers with a 24h bar, a
	 *  Percentage / Fixed amount switch, value presets and a plain-language
	 *  "Customers see" line. The real start/end/type/value inputs are moved
	 *  in (never copied), so the classic save handler reads them as before.
	 * ---------------------------------------------------------------- */
	(function modernizeHappyHours() {
		var $card = $root.find('[data-sme-section="MPWPB_Happy_Hours_Settings"]');
		var $row = $card.find('#mpwpb_happy_hours_row');
		var $start = $row.find('[name="mpwpb_happy_hours_start_time"]');
		var $end = $row.find('[name="mpwpb_happy_hours_end_time"]');
		var $type = $row.find('[name="mpwpb_happy_hours_discount_type"]');
		var $value = $row.find('[name="mpwpb_happy_hours_discount_value"]');
		if (!$row.length || !$start.length || !$end.length || !$type.length || !$value.length || $row.data('sme-modernized')) { return; }
		$row.data('sme-modernized', true).addClass('mpwpb-hh2');
		$card.addClass('mpwpb-sme__hh-card');
		var t = function (key, fallback) { return cfg[key] || fallback; };
		var fmt = function (tpl) { var args = [].slice.call(arguments, 1); return String(tpl).replace(/%(\d)\$s/g, function (m, n) { return args[n - 1]; }).replace(/%s/, args[0]); };
		var cur = cfg.hhCurrencySymbol || '';

		var $head = $card.find('.mpwpb-sme__postfields-header').first();
		if (!$head.find('.mpwpb-sme__hh-icon').length) {
			$head.prepend('<span class="mpwpb-sme__hh-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></span>');
		}

		// Time window: -/+ 30 min steppers around the real <input type="time">.
		function stepper($input, id, label) {
			var $f = $('<div class="mpwpb-hh2__field"></div>');
			$('<label class="mpwpb-hh2__label"></label>').attr('for', id).text(label).appendTo($f);
			var $c = $('<div class="mpwpb-hh2__stepper"></div>').appendTo($f);
			$('<button type="button" data-hh-step="-30">−</button>').attr('aria-label', label + ': ' + t('hhEarlierTxt', '30 minutes earlier')).appendTo($c);
			$input.attr('id', id).removeAttr('style').appendTo($c);
			$('<button type="button" data-hh-step="30">+</button>').attr('aria-label', label + ': ' + t('hhLaterTxt', '30 minutes later')).appendTo($c);
			return $f;
		}
		var $win = $('<div class="mpwpb-hh2__box"></div>');
		var $winHead = $('<div class="mpwpb-hh2__box-head"><div><div class="mpwpb-hh2__title"></div><div class="mpwpb-hh2__sub"></div></div><span class="mpwpb-hh2__len" data-hh-len></span></div>').appendTo($win);
		$winHead.find('.mpwpb-hh2__title').text(t('hhWindowTitle', 'Time window'));
		$winHead.find('.mpwpb-hh2__sub').text(t('hhWindowSub', ''));
		var $startField = stepper($start, 'mpwpb-hh-start', t('hhStartLabel', 'Starts at'));
		var $endField = stepper($end, 'mpwpb-hh-end', t('hhEndLabel', 'Ends at'));
		$('<div class="mpwpb-hh2__times"></div>').append($startField, $endField).appendTo($win);
		var $bar = $('<div class="mpwpb-hh2__bar" aria-hidden="true"><span class="mpwpb-hh2__bar-fill"></span></div>').appendTo($win);
		var am = t('hhAmTxt', 'AM'), pm = t('hhPmTxt', 'PM');
		$('<div class="mpwpb-hh2__ticks" aria-hidden="true"></div>')
			.append($('<span></span>').text('12 ' + am), $('<span></span>').text('6 ' + am), $('<span></span>').text('12 ' + pm), $('<span></span>').text('6 ' + pm), $('<span></span>').text('12 ' + am))
			.appendTo($win);
		var $rangeErr = $('<p class="mpwpb-hh2__error" role="alert"></p>').text(t('hhRangeError', '')).appendTo($win);

		// Discount: segmented switch drives the real (hidden) select.
		var $offer = $('<div class="mpwpb-hh2__box"></div>');
		$('<div class="mpwpb-hh2__title"></div>').text(t('hhOfferTitle', 'Discount')).appendTo($offer);
		$('<div class="mpwpb-hh2__sub"></div>').text(t('hhOfferSub', '')).appendTo($offer);
		var $offerRow = $('<div class="mpwpb-hh2__offer"></div>').appendTo($offer);
		var $seg = $('<div class="mpwpb-hh2__seg" role="radiogroup"></div>').attr('aria-label', t('hhTypeLabel', 'Discount type')).appendTo($offerRow);
		$('<button type="button" role="radio" data-hh-type="percent"></button>').text(t('hhPercentTxt', 'Percentage')).appendTo($seg);
		$('<button type="button" role="radio" data-hh-type="fixed"></button>').text(t('hhFixedTxt', 'Fixed amount')).appendTo($seg);
		$type.addClass('mpwpb-hh2__type-select').removeAttr('style').attr('tabindex', '-1').attr('aria-hidden', 'true').appendTo($seg);
		var $valBox = $('<div class="mpwpb-hh2__value"></div>').appendTo($offerRow);
		var $prefix = $('<span class="mpwpb-hh2__affix" aria-hidden="true"></span>').text(cur).appendTo($valBox);
		$value.attr('id', 'mpwpb-hh-value').attr('aria-label', t('hhValueLabel', 'Discount value')).removeAttr('style').appendTo($valBox);
		var $suffix = $('<span class="mpwpb-hh2__affix" aria-hidden="true">%</span>').appendTo($valBox);
		var $presets = $('<div class="mpwpb-hh2__presets"></div>').appendTo($offerRow);
		var $valErr = $('<p class="mpwpb-hh2__error" role="alert"></p>').appendTo($offer);

		var $summary = $('<div class="mpwpb-hh2__summary"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.6 13.4l-7.2 7.2a2 2 0 0 1-2.8 0L3 13V3h10l7.6 7.6a2 2 0 0 1 0 2.8z"/><circle cx="7.5" cy="7.5" r="1.5"/></svg><div><strong></strong> <span data-hh-summary></span></div></div>');
		$summary.find('strong').text(t('hhSeeLabel', 'Customers see:'));

		// Everything left in the row is classic label chrome: replace it.
		$row.children().remove();
		$row.append($win, $offer, $summary);

		var $toggle = $card.find('[name="mpwpb_happy_hours_enabled"]');
		var $off = $('<p class="mpwpb-hh2__off"></p>').insertAfter($row);
		function syncOn() {
			var on = $toggle.is(':checked');
			$off.text($toggle.is(':disabled') ? t('hhProNote', '') : t('hhOffNote', '')).prop('hidden', on);
			$row.attr('aria-hidden', on ? 'false' : 'true');
		}
		$toggle.on('change.mpwpb-sme-hh', syncOn);
		$toggle.closest('.roundSwitchLabel').on('click.mpwpb-sme-hh', function () { setTimeout(syncOn, 0); });
		syncOn();

		function toMin(v) {
			var m = /^(\d{1,2}):(\d{2})/.exec(v || '');
			return m ? Math.min(1439, parseInt(m[1], 10) * 60 + parseInt(m[2], 10)) : null;
		}
		function toVal(min) { var h = Math.floor(min / 60), m = min % 60; return (h < 10 ? '0' : '') + h + ':' + (m < 10 ? '0' : '') + m; }
		function label(min) {
			var h = Math.floor(min / 60), m = min % 60;
			return (h % 12 || 12) + ':' + (m < 10 ? '0' : '') + m + ' ' + (h < 12 ? am : pm);
		}
		function money(v) { return cur ? cur + v : String(v); }

		var lastType = null;
		function update() {
			var s = toMin($start.val()), e = toMin($end.val());
			var badRange = s === null || e === null || e <= s;
			var len = badRange ? 0 : e - s;
			var lenTxt = len >= 60 ? (Math.round(len / 6) / 10) + ' ' + t('hhHoursUnit', 'h') : len + ' ' + t('hhMinsUnit', 'min');
			$win.find('[data-hh-len]').text(badRange ? t('hhWindowInvalid', 'Invalid window') : fmt(t('hhWindowLen', '%s window'), lenTxt)).toggleClass('is-error', badRange);
			$endField.toggleClass('is-error', badRange);
			$rangeErr.toggle(badRange);
			$bar.find('.mpwpb-hh2__bar-fill').css({ left: (s || 0) / 1440 * 100 + '%', width: len / 1440 * 100 + '%' });

			var type = $type.val() === 'fixed' ? 'fixed' : 'percent';
			$seg.find('[data-hh-type]').each(function () {
				var on = $(this).attr('data-hh-type') === type;
				$(this).toggleClass('is-active', on).attr('aria-checked', on ? 'true' : 'false');
			});
			$prefix.toggle(type === 'fixed' && !!cur);
			$valBox.toggleClass('is-prefix', type === 'fixed' && !!cur);
			$suffix.toggle(type === 'percent');
			if (type !== lastType) {
				lastType = type;
				$presets.empty();
				(type === 'percent' ? [10, 15, 20, 25] : [5, 10, 15, 20]).forEach(function (v) {
					$('<button type="button" class="mpwpb-hh2__preset"></button>').attr('data-hh-preset', v)
						.text(type === 'percent' ? v + '%' : money(v)).appendTo($presets);
				});
			}
			var raw = $.trim($value.val()), v = parseFloat(raw);
			$presets.find('[data-hh-preset]').each(function () { $(this).toggleClass('is-active', parseFloat($(this).attr('data-hh-preset')) === v); });
			var valErr = (isNaN(v) || v <= 0) ? t('hhValueEmpty', '') : (type === 'percent' && v > 100 ? t('hhValueOver', '') : '');
			$valBox.toggleClass('is-error', !!valErr);
			$valErr.text(valErr).toggle(!!valErr);

			var amount = type === 'percent' ? v + '%' : money(v);
			$summary.find('[data-hh-summary]').text(badRange || valErr
				? t('hhSummaryInvalid', '')
				: fmt(t('hhSummary', ''), label(s), label(e), fmt(t('hhOffTxt', '%s off'), amount)));
		}

		$win.on('click', '[data-hh-step]', function () {
			var $in = $(this).closest('.mpwpb-hh2__stepper').find('input');
			var m = toMin($in.val()), step = parseInt($(this).attr('data-hh-step'), 10);
			if (m === null) { m = $in.is($start) ? 840 : 960; }
			// Snap to the 30-minute grid; the last step up lands on 23:59.
			m = step > 0 ? Math.min(1439, Math.floor(m / 30) * 30 + 30) : Math.max(0, Math.ceil(m / 30) * 30 - 30);
			$in.val(toVal(m)).trigger('change');
		});
		$seg.on('click', '[data-hh-type]', function () {
			var type = $(this).attr('data-hh-type');
			if ($type.val() === type) { return; }
			$type.val(type).trigger('change');
		});
		$presets.on('click', '[data-hh-preset]', function () {
			$value.val($(this).attr('data-hh-preset')).trigger('change');
		});
		$row.on('input change', 'input, select', update);
		update();
	})();

	/* ---------------------------------------------------------------- *
	 *  Relocate the "Enable Recurring Bookings" / "Enable Staff Member Add"
	 *  toggles (Pro-only cards, rendered normally by the reused classic
	 *  methods) up into their own card headers, next to the title — same
	 *  pattern as the other header-toggle relocations above, just written
	 *  as dedicated functions since neither card has a uniquely-classed
	 *  content section to anchor a .prev() cleanup off of. Instead, the
	 *  toggle's original wrapping <section> is captured by reference
	 *  *before* the toggle itself is moved out of it, so it can be safely
	 *  discarded afterwards regardless of DOM order (it never held anything
	 *  but decorative label text once the switch is gone).
	 * ---------------------------------------------------------------- */
	function relocateToggleToCardHeader(sectionClass, inputName) {
		var $card = $root.find('[data-sme-section="' + sectionClass + '"]');
		var $slot = $card.find('[data-sme-header-actions]').first();
		if (!$card.length || !$slot.length) {
			return;
		}
		var $toggleInput = $card.find('input[name="' + inputName + '"]');
		var $toggleRow = $toggleInput.closest('section');
		var $toggle = $toggleInput.closest('.roundSwitchLabel');
		if ($toggle.length) {
			$toggle.appendTo($slot);
		}
		if ($toggleRow.length) {
			$toggleRow.remove();
		}
	}
	relocateToggleToCardHeader('MPWPB_Recurring_Booking_Settings', 'mpwpb_enable_recurring');
	relocateToggleToCardHeader('Staff_Member', 'mpwpb_staff_member_add');

	/* ---------------------------------------------------------------- *
	 *  Recurring Booking — convert the remaining classic rows into a repeat
	 *  pattern picker plus a limits/discount panel. Original controls move;
	 *  names and values are never duplicated.
	 * ---------------------------------------------------------------- */
	(function modernizeRecurringBooking() {
		var $card = $root.find('[data-sme-section="MPWPB_Recurring_Booking_Settings"]');
		var $tab = $card.find('.tabsItem').first();
		var $types = $tab.find('input[name="mpwpb_recurring_types[]"]');
		var $max = $tab.find('input[name="mpwpb_max_recurring_count"]');
		var $disc = $tab.find('input[name="mpwpb_recurring_discount"]');
		if (!$tab.length || !$types.length || !$max.length || !$disc.length || $tab.data('sme-modernized')) { return; }
		$tab.data('sme-modernized', true).addClass('mpwpb-recur');
		$card.addClass('mpwpb-sme__recur-card');
		var t = function (key, fallback) { return cfg[key] || fallback; };
		var fmt = function (tpl) { var args = [].slice.call(arguments, 1); return String(tpl).replace(/%(\d)\$[sd]/g, function (m, n) { return args[n - 1]; }).replace(/%[sd]/, args[0]).replace('%%', '%'); };

		var $head = $card.find('.mpwpb-sme__postfields-header').first();
		if (!$head.find('.mpwpb-sme__recur-icon').length) {
			$head.prepend('<span class="mpwpb-sme__recur-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 2l3 3-3 3"/><path d="M4 11V9a4 4 0 0 1 4-4h12"/><path d="M7 22l-3-3 3-3"/><path d="M20 13v2a4 4 0 0 1-4 4H4"/></svg></span>');
		}

		// Repeat options: one tile per real mpwpb_recurring_types[] checkbox.
		var meta = {
			'daily': ['1d', t('recurHintDaily', 'Same time every day')],
			'weekly': ['7d', t('recurHintWeekly', 'Same weekday each week')],
			'bi-weekly': ['14d', t('recurHintBiweekly', 'Every other week')],
			'monthly': ['1m', t('recurHintMonthly', 'Same date each month')]
		};
		var $optHead = $('<div class="mpwpb-recur2__row-head"><div><div class="mpwpb-recur2__title"></div><div class="mpwpb-recur2__sub"></div></div><span class="mpwpb-recur2__count" data-recur-on-count></span></div>');
		$optHead.find('.mpwpb-recur2__title').text(t('recurOptionsTitle', 'Repeat options'));
		$optHead.find('.mpwpb-recur2__sub').text(t('recurOptionsSub', ''));
		var $tiles = $('<div class="mpwpb-recur2__tiles"></div>');
		$types.each(function () {
			var $in = $(this);
			var name = $.trim($in.next('.customCheckbox').text()) || $in.val();
			var m = meta[$in.val()] || ['', ''];
			var $tile = $('<label class="mpwpb-recur2__tile"></label>');
			$tile.append($('<span class="mpwpb-recur2__badge" aria-hidden="true"></span>').text(m[0]))
				.append($('<span class="mpwpb-recur2__name"></span>').text(name))
				.append($('<span class="mpwpb-recur2__hint"></span>').text(m[1]))
				.append('<span class="mpwpb-recur2__check" aria-hidden="true"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12l5 5 9-10"/></svg></span>');
			$tile.prepend($in.addClass('mpwpb-recur2__input'));
			$tiles.append($tile);
		});
		var $warn = $('<p class="mpwpb-recur2__warn" role="alert"></p>').text(t('recurOptionsNone', ''));
		var $options = $('<div class="mpwpb-recur2__block"></div>').append($optHead, $tiles, $warn);

		// Maximum bookings: -/+ counter around the real number input (2..52).
		var $maxBox = $('<div class="mpwpb-recur2__box"></div>');
		$('<label class="mpwpb-recur2__title" for="mpwpb-recur-max"></label>').text(t('recurMaxTitle', 'Maximum bookings in a series')).appendTo($maxBox);
		$('<div class="mpwpb-recur2__sub"></div>').text(t('recurMaxSub', 'Between 2 and 52.')).appendTo($maxBox);
		var $maxRow = $('<div class="mpwpb-recur2__inline"></div>').appendTo($maxBox);
		var $counter = $('<div class="mpwpb-recur2__counter"></div>').appendTo($maxRow);
		$('<button type="button" data-recur-max-step="-1">−</button>').attr('aria-label', t('recurFewerTxt', 'Fewer')).appendTo($counter);
		$max.attr('id', 'mpwpb-recur-max').appendTo($counter);
		$('<button type="button" data-recur-max-step="1">+</button>').attr('aria-label', t('recurMoreTxt', 'More')).appendTo($counter);
		$('<span class="mpwpb-recur2__unit"></span>').text(t('recurMaxUnit', 'bookings')).appendTo($maxRow);

		// Series discount: the real % input plus None / 5 / 10 / 15 presets.
		var $discBox = $('<div class="mpwpb-recur2__box"></div>');
		$('<label class="mpwpb-recur2__title" for="mpwpb-recur-disc"></label>').text(t('recurDiscTitle', 'Series discount')).appendTo($discBox);
		$('<div class="mpwpb-recur2__sub"></div>').text(t('recurDiscSub', '')).appendTo($discBox);
		var $discRow = $('<div class="mpwpb-recur2__inline mpwpb-recur2__inline--wrap"></div>').appendTo($discBox);
		var $pct = $('<div class="mpwpb-recur2__pct"></div>').appendTo($discRow);
		$disc.attr('id', 'mpwpb-recur-disc').appendTo($pct);
		$pct.append('<span aria-hidden="true">%</span>');
		[0, 5, 10, 15].forEach(function (v) {
			$('<button type="button" class="mpwpb-recur2__preset"></button>').attr('data-recur-disc', v)
				.text(v === 0 ? t('recurDiscNone', 'None') : v + '%').appendTo($discRow);
		});
		var $limits = $('<div class="mpwpb-recur2__boxes"></div>').append($maxBox, $discBox);

		var $summary = $('<div class="mpwpb-recur2__summary"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/></svg><div><strong></strong> <span data-recur-summary></span></div></div>');
		$summary.find('strong').text(t('recurSeeLabel', 'Customers see:'));

		var $toggle = $card.find('[name="mpwpb_enable_recurring"]');
		var isEnabled = $toggle.is(':checked');
		var $body = $('<div class="mpwpb-recur__body mpwpb-recur2" data-collapse="#mpwpb_enable_recurring"></div>')
			.toggleClass('mActive', isEnabled)
			.css('display', isEnabled ? 'block' : 'none')
			.attr('aria-hidden', isEnabled ? 'false' : 'true')
			.append($options, $limits, $summary);
		var $off = $('<p class="mpwpb-recur2__off"></p>').text(t('recurOffTxt', 'Recurring booking is off.')).prop('hidden', isEnabled);
		// Everything left in the tab is classic chrome (headers, empty label rows): replace it.
		$tab.children().remove();
		$tab.append($body, $off);
		function syncOn() {
			var enabled = $toggle.is(':checked');
			$body.attr('aria-hidden', enabled ? 'false' : 'true');
			$off.prop('hidden', enabled);
		}
		$toggle.on('change.mpwpb-sme-recurring', syncOn);
		$toggle.closest('.roundSwitchLabel').on('click.mpwpb-sme-recurring', function () { setTimeout(syncOn, 0); });

		function clampInput($in, lo, hi) {
			var v = parseInt($in.val(), 10);
			if (isNaN(v)) { v = lo; }
			v = Math.max(lo, Math.min(hi, v));
			if (String(v) !== String($in.val())) { $in.val(v); }
			return v;
		}
		function update() {
			var picked = [];
			$types.each(function () {
				var on = this.checked;
				$(this).closest('.mpwpb-recur2__tile').toggleClass('is-on', on);
				if (on) { picked.push($.trim($(this).siblings('.mpwpb-recur2__name').text()).toLowerCase()); }
			});
			$optHead.find('[data-recur-on-count]').text(fmt(t('recurOptionsOn', '%1$d of %2$d on'), picked.length, $types.length)).toggleClass('is-none', !picked.length);
			$warn.toggle(!picked.length);
			var max = parseInt($max.val(), 10) || 2;
			var disc = parseInt($disc.val(), 10) || 0;
			$counter.find('[data-recur-max-step="-1"]').prop('disabled', max <= 2);
			$counter.find('[data-recur-max-step="1"]').prop('disabled', max >= 52);
			$discRow.find('[data-recur-disc]').each(function () {
				$(this).toggleClass('is-active', parseInt($(this).attr('data-recur-disc'), 10) === disc);
			});
			var text;
			if (!picked.length) {
				text = t('recurSeeNone', 'no repeat options yet');
			} else {
				var list = picked.length > 1 ? picked.slice(0, -1).join(', ') + ' ' + t('recurOr', 'or') + ' ' + picked[picked.length - 1] : picked[0];
				text = fmt(t('recurSeeText', 'repeat %1$s, up to %2$d bookings'), list, max) + (disc > 0 ? fmt(t('recurSeeDiscount', ', with %d%% off the series'), disc) : '');
			}
			$summary.find('[data-recur-summary]').text(text + '.');
		}
		$counter.on('click', '[data-recur-max-step]', function () {
			var v = (parseInt($max.val(), 10) || 2) + parseInt($(this).attr('data-recur-max-step'), 10);
			$max.val(Math.max(2, Math.min(52, v))).trigger('change');
		});
		$discRow.on('click', '[data-recur-disc]', function () {
			$disc.val($(this).attr('data-recur-disc')).trigger('change');
		});
		$max.on('blur', function () { clampInput($max, 2, 52); update(); });
		$disc.on('blur', function () { clampInput($disc, 0, 100); update(); });
		$tab.on('input change', 'input', update);
		update();
	})();

	/* ---------------------------------------------------------------- *
	 *  Tax card: class tiles (driving the real, hidden select), the real
	 *  rate input with a shared-rate note, and a plain-language "Customers
	 *  see" line. With WooCommerce a class has ONE rate, so picking a class
	 *  loads that class's current rate (data-tax-info from Tax_Settings).
	 * ---------------------------------------------------------------- */
	(function modernizeTaxSettings() {
		var $card = $root.find('[data-sme-section="Tax_Settings"]');
		var $row = $card.find('#mpwpb_tax_class_row');
		var $select = $row.find('[name="mpwpb_tax_class"]');
		var $rate = $row.find('[name="mpwpb_tax_rate"]');
		if (!$row.length || !$select.length || !$rate.length || $row.data('sme-modernized')) { return; }
		$row.data('sme-modernized', true).addClass('mpwpb-tax2');
		$card.addClass('mpwpb-sme__tax-card');
		var t = function (key, fallback) { return cfg[key] || fallback; };
		var fmt = function (tpl) {
			var args = [].slice.call(arguments, 1), i = 0;
			return String(tpl).replace(/%(\d)\$[sd]|%[sd]|%%/g, function (m, n) {
				if (m === '%%') { return '%'; }
				return n ? args[n - 1] : args[i++];
			});
		};
		var info = {};
		try { info = JSON.parse($row.attr('data-tax-info') || '{}') || {}; } catch (e) { info = {}; }
		var shared = !!info.shared, knownRates = info.rates || {}, others = info.others || {};
		var cur = cfg.hhCurrencySymbol || '';
		var decimals = parseInt(cfg.taxDecimals, 10);
		if (isNaN(decimals)) { decimals = 2; }
		var money = function (v) { return cur + v.toFixed(decimals); };
		var pct = function (v) { return (Math.round(v * 10000) / 10000) + '%'; };

		var $head = $card.find('.mpwpb-sme__postfields-header').first();
		if (!$head.find('.mpwpb-sme__tax-icon').length) {
			$head.prepend('<span class="mpwpb-sme__tax-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 3h14v18l-3-2-2 2-2-2-2 2-2-2-3 2z"/><path d="M9 14l6-6"/><circle cx="9.5" cy="8.5" r=".8"/><circle cx="14.5" cy="13.5" r=".8"/></svg></span>');
		}

		// Tax class tiles, one per real <option>.
		var $classBlock = $('<div class="mpwpb-tax2__block"></div>');
		$('<div class="mpwpb-tax2__title"></div>').text(t('taxClassTitle', 'Tax class')).appendTo($classBlock);
		$('<div class="mpwpb-tax2__sub"></div>').text(shared ? t('taxClassSub', '') : t('taxClassSubOwn', '')).appendTo($classBlock);
		var $tiles = $('<div class="mpwpb-tax2__tiles" role="radiogroup"></div>').attr('aria-label', t('taxClassTitle', 'Tax class')).appendTo($classBlock);
		$select.find('option').each(function () {
			var $tile = $('<button type="button" role="radio" class="mpwpb-tax2__tile"></button>').attr('data-tax-class', $(this).val());
			$('<span class="mpwpb-tax2__tile-name"></span>').text($(this).text()).appendTo($tile);
			if (shared) { $('<span class="mpwpb-tax2__tile-rate"></span>').appendTo($tile); }
			$tile.append('<span class="mpwpb-tax2__radio" aria-hidden="true"></span>');
			$tiles.append($tile);
		});
		$select.addClass('mpwpb-tax2__select').removeAttr('style').attr({ tabindex: '-1', 'aria-hidden': 'true' }).appendTo($classBlock);

		// Rate: the real number input with a % suffix.
		var $rateBox = $('<div class="mpwpb-tax2__box"></div>');
		var $rateTitle = $('<label class="mpwpb-tax2__title" for="mpwpb-tax-rate"></label>').appendTo($rateBox);
		$('<div class="mpwpb-tax2__sub"></div>').text(t('taxRateSub', '')).appendTo($rateBox);
		var $field = $('<div class="mpwpb-tax2__rate"></div>').appendTo($rateBox);
		$rate.attr('id', 'mpwpb-tax-rate').removeAttr('style').appendTo($field);
		$field.append('<span aria-hidden="true">%</span>');
		var $err = $('<p class="mpwpb-tax2__error" role="alert"></p>').text(t('taxRateError', '')).appendTo($rateBox);
		var $note = $('<div class="mpwpb-tax2__shared"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16.5v.5"/></svg><span></span></div>').appendTo($rateBox);

		var $summary = $('<div class="mpwpb-tax2__summary"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M7 9h10M7 13h6M7 17h4"/></svg><div><strong></strong> <span data-tax-summary></span></div></div>');
		$summary.find('strong').text(t('taxSeeLabel', 'Customers see:'));

		// Everything left in the row is classic label chrome: replace it.
		$row.children().remove();
		$row.append($classBlock, $rateBox, $summary);

		var $toggle = $card.find('input[name="mpwpb_tax_enabled"]');
		var $off = $('<p class="mpwpb-tax2__off"></p>').text(t('taxOffNote', '')).insertAfter($row);
		function syncOn() { $off.prop('hidden', $toggle.is(':checked')); }
		$toggle.on('change.mpwpb-sme-tax', syncOn);
		syncOn();

		// What this service's form currently holds for each class, so
		// switching classes and back doesn't lose an edit.
		var edited = {};
		edited[$select.val()] = $rate.val();

		function readRate() {
			var raw = $.trim($rate.val());
			var v = parseFloat(raw);
			return raw === '' || isNaN(v) ? null : v;
		}
		function update() {
			var cls = $select.val();
			var name = $select.find('option:selected').text();
			$tiles.find('[data-tax-class]').each(function () {
				var c = $(this).attr('data-tax-class'), on = c === cls;
				$(this).toggleClass('is-active', on).attr('aria-checked', on ? 'true' : 'false');
				if (shared) {
					var r = c === cls ? readRate() : (edited[c] !== undefined ? parseFloat(edited[c]) : knownRates[c]);
					$(this).find('.mpwpb-tax2__tile-rate').text(r === null || r === undefined || isNaN(r) ? t('taxClassNoRate', 'No rate set yet') : fmt(t('taxClassRate', '%s rate'), pct(r)));
				}
			});
			$rateTitle.text(fmt(t('taxRateTitle', 'Tax rate (%s)'), name));
			var v = readRate();
			var bad = v === null || v < 0 || v > 100;
			$field.toggleClass('is-error', bad);
			$err.toggle(bad);
			var n = others[cls] || 0;
			$note.toggle(shared && !bad).find('span').text(fmt(t('taxSharedNote', ''), name) + (n ? ' ' + (n === 1 ? t('taxSharedOne', '') : fmt(t('taxSharedMany', '%d'), n)) : ''));
			var text;
			if (bad) {
				text = t('taxSummaryInvalid', '');
			} else if (v === 0) {
				text = fmt(t('taxSummaryZero', ''), name);
			} else if (info.pricesIncludeTax) {
				text = fmt(t('taxSummaryIncl', ''), pct(v), money(100), money(100 - 100 / (1 + v / 100)));
			} else {
				text = fmt(t('taxSummary', ''), pct(v), money(100), money(100 + v), money(v));
			}
			$summary.find('[data-tax-summary]').text(text);
		}

		$tiles.on('click', '[data-tax-class]', function () {
			var c = $(this).attr('data-tax-class'), from = $select.val();
			if (c === from) { return; }
			edited[from] = $rate.val();
			$select.val(c).trigger('change');
			// With a shared class rate, show what the class is charging now.
			if (edited[c] !== undefined) {
				$rate.val(edited[c]);
			} else if (shared && knownRates[c] !== undefined) {
				$rate.val(knownRates[c]);
			}
			update();
		});
		$rate.on('input change', function () { edited[$select.val()] = $rate.val(); update(); });
		$select.on('change', update);
		update();
	})();

	/* ---------------------------------------------------------------- *
	 *  Staff Member — retain the existing avatar tile selector and AJAX save,
	 *  adding modern card integration plus live selection feedback.
	 * ---------------------------------------------------------------- */
	(function modernizeStaffSelection() {
		var $card = $root.find('[data-sme-section="Staff_Member"]');
		var $container = $card.find('#mpwpb_add_staff_container');
		var $grid = $container.find('.mpwpb-staff-grid');
		var $select = $card.find('#mpwpb_staff_selector');
		if (!$container.length || $container.data('sme-modernized')) { return; }
		$container.data('sme-modernized', true).addClass('mpwpb-staff-modern');
		$card.addClass('mpwpb-sme__staff-card');
		var t = function (key, fallback) { return cfg[key] || fallback; };

		var $cardHead = $card.find('.mpwpb-sme__postfields-header').first();
		if (!$cardHead.find('.mpwpb-sme__staff-icon').length) {
			$cardHead.prepend('<span class="mpwpb-sme__staff-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"/><circle cx="10" cy="7" r="4"/><path d="M21 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></span>');
		}
		$card.find('.tabsItem > header').remove();
		$container.find('.mpwpb_add_staff_container > label').remove();

		var manageUrl = t('staffManageUrl', '');
		var $bar = $('<div class="mpwpb-staff2__bar"><div><div class="mpwpb-staff2__title"></div><div class="mpwpb-staff2__count" data-staff-count></div></div><div class="mpwpb-staff2__actions"></div></div>');
		$bar.find('.mpwpb-staff2__title').text(t('staffWhoTitle', 'Who can be booked'));
		var $actions = $bar.find('.mpwpb-staff2__actions');
		$('<button type="button" class="mpwpb-staff2__link" data-staff-all></button>').text(t('staffSelectAllTxt', 'Select all')).appendTo($actions);
		$('<button type="button" class="mpwpb-staff2__link mpwpb-staff2__link--muted" data-staff-none></button>').text(t('staffClearTxt', 'Clear')).appendTo($actions);
		if (manageUrl) {
			$('<a class="mpwpb-staff2__manage" target="_blank" rel="noopener"></a>').attr('href', manageUrl)
				.text(t('staffManageTxt', 'Manage staff') + ' ')
				.append('<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 4h6v6M20 4L10 14"/><path d="M18 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5"/></svg>')
				.appendTo($actions);
		}
		$grid.before($bar);

		// Staff tiles become horizontal cards: photo, name, status, checkbox.
		// The tile click handler (Admin/settings/Staff_Member.php) is untouched.
		$grid.find('.mpwpb-staff-tile').each(function () {
			var $tile = $(this);
			$tile.attr('role', 'checkbox');
			if (!$tile.find('.mpwpb-staff-tile-text').length) {
				var $name = $tile.find('.mpwpb-staff-tile-name');
				var $text = $('<span class="mpwpb-staff-tile-text"></span>').append($name).append('<span class="mpwpb-staff-tile-status"></span>');
				$tile.find('.mpwpb-staff-tile-avatar').after($text);
				$tile.append($tile.find('.mpwpb-staff-tile-check'));
				$tile.find('.mpwpb-staff-tile-check').html('<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12l5 5 9-10"/></svg>');
			}
		});
		if (manageUrl) {
			$('<a class="mpwpb-staff2__add" target="_blank" rel="noopener"></a>').attr('href', manageUrl)
				.append('<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>')
				.append($('<span></span>').text(t('staffAddTxt', 'Add staff member')))
				.appendTo($grid);
		}
		var hasStaff = $grid.find('.mpwpb-staff-tile').length > 0;
		var $empty = $('<p class="mpwpb-staff2__empty"></p>').text(t('staffEmptyTxt', 'No staff members yet.'));
		var $warn = $('<p class="mpwpb-staff2__warn" role="alert"></p>').text(t('staffPickWarn', ''));
		var $summary = $('<div class="mpwpb-staff2__summary"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg><div><strong></strong> <span data-staff-summary></span></div></div>');
		$summary.find('strong').text(t('staffSeeLabel', 'Customers see:'));
		$grid.after($summary).after($warn).after($empty);
		$empty.toggle(!hasStaff);

		var $toggle = $card.find('input[name="mpwpb_staff_member_add"]');
		var $off = $('<p class="mpwpb-staff2__off"></p>').text(t('staffOffNote', 'Staff choice is off.'));
		$container.after($off);
		function syncOn() { $off.prop('hidden', $toggle.is(':checked')); }
		$toggle.on('change.mpwpb-sme-staff', syncOn);
		$toggle.closest('.roundSwitchLabel').on('click.mpwpb-sme-staff', function () { setTimeout(syncOn, 0); });
		syncOn();

		function update() {
			var $tiles = $grid.find('.mpwpb-staff-tile');
			var names = [];
			$tiles.each(function () {
				var on = $(this).hasClass('is-selected');
				$(this).attr('aria-checked', on ? 'true' : 'false')
					.find('.mpwpb-staff-tile-status').text(on ? t('staffOnTxt', 'Can be booked') : t('staffOffTxt', 'Not offered'));
				if (on) { names.push($.trim($(this).find('.mpwpb-staff-tile-name').text())); }
			});
			$bar.find('[data-staff-count]').text(String(t('staffCountTxt', '%1$d of %2$d selected')).replace('%1$d', names.length).replace('%2$d', $tiles.length));
			$warn.toggle(hasStaff && !names.length);
			var list = names.length > 1 ? names.slice(0, -1).join(', ') + ' ' + t('staffOr', 'or') + ' ' + names[names.length - 1] : names[0];
			$summary.find('[data-staff-summary]').text(names.length ? String(t('staffSeeText', '"Any Staff" or %s.')).replace('%s', list) : t('staffSeeNone', 'no one to book with yet.'));
			$actions.find('[data-staff-all]').prop('disabled', !hasStaff || names.length === $tiles.length);
			$actions.find('[data-staff-none]').prop('disabled', !names.length);
		}
		// Bulk select / clear: flip every tile + option, then ONE change (one AJAX save).
		function setAll(on) {
			$grid.find('.mpwpb-staff-tile').toggleClass('is-selected', on);
			$select.find('option').prop('selected', on);
			$select.trigger('change');
			update();
		}
		$actions.on('click', '[data-staff-all]', function () { setAll(true); });
		$actions.on('click', '[data-staff-none]', function () { setAll(false); });
		$select.on('change.mpwpb-sme-staff', function () { setTimeout(update, 0); });
		$grid.on('click', '.mpwpb-staff-tile', function () { setTimeout(update, 0); });
		update();
	})();

	/* ---------------------------------------------------------------- *
	 *  Relocate the real "Service Features" repeater (rendered normally,
	 *  once, by the reused MPWPB_Service_Details::service_details()) out of
	 *  the Service Details card into its own "Service Feature Details"
	 *  card — a real DOM node moved via appendTo(), not a duplicate render,
	 *  so mpwpb_features[] still submits exactly once. Its "Enable Service
	 *  Features" toggle moves to the new card's header (next to the title)
	 *  instead of staying with Service Details, since it now gates this
	 *  card's content, not that one. The now-empty toggle row left behind
	 *  is discarded (decorative only, no form fields); its heading row is
	 *  left in place (already hidden via CSS's section.section:first-of-
	 *  type rule) rather than removed — removing it would make the *next*
	 *  section.section (Service Details' own heading) become the new
	 *  first-of-type and get hidden by that same rule instead.
	 * ---------------------------------------------------------------- */
	(function relocateServiceFeatures() {
		var $featuresSection = $root.find('[data-sme-section="MPWPB_Service_Details"] .mpwpb-service-features');
		var $contentSlot = $root.find('[data-sme-features-slot]');
		if (!$featuresSection.length || !$contentSlot.length) {
			return;
		}
		var $toggleRow = $featuresSection.prev();
		var $toggle = $toggleRow.find('input[name="mpwpb_features_status"]').closest('.roundSwitchLabel');
		var $headerSlot = $root.find('[data-sme-section="MPWPB_Service_Features_Modern"] [data-sme-header-actions]').first();

		if ($toggle.length && $headerSlot.length) {
			$toggle.appendTo($headerSlot);
		}
		$featuresSection.appendTo($contentSlot);
		$toggleRow.remove();
	})();

	/* ---------------------------------------------------------------- *
	 *  Features card: checklist rows (grip handle, tick, inline text,
	 *  bin), a count chip, an "Add a feature" bar and an empty state.
	 *  Rows stay the classic repeater (mpwpb_features[] inputs, the
	 *  .mp_add_item / .mpwpb_item_remove handlers in mpwpb_admin_
	 *  settings.js, its confirm on remove); the add bar clicks the real
	 *  (hidden) .mp_add_item and fills the new row, and an empty draft adds
	 *  nothing, so no blank features get saved.
	 * ---------------------------------------------------------------- */
	(function modernizeFeatures() {
		var $slot = $root.find('[data-sme-features-slot]');
		var $section = $slot.find('.mpwpb-service-features');
		if (!$section.length) {
			return;
		}
		var $card = $slot.closest('[data-sme-section]');
		var $list = $section.find('.mp_item_insert').first();
		var $realAdd = $section.find('.mp_add_item').first();
		var t = function (key, fallback) { return $slot.attr('data-' + key) || fallback; };
		$card.addClass('mpwpb-sme__features-card');

		var $head = $card.find('.mpwpb-sme__postfields-header').first();
		if ($head.length && !$head.find('.mpwpb-sme__features-icon').length) {
			$head.prepend('<span class="mpwpb-sme__features-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3 8-8"/><path d="M20 12v6a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h9"/></svg></span>');
		}
		var $chip = $('<span class="mpwpb-sme__features-count"></span>');
		$card.find('[data-sme-header-actions]').first().prepend($chip);

		var $empty = $('<p class="mpwpb-sme__features-empty"></p>').text(t('empty', 'No features yet.'));
		var $bar = $('<div class="mpwpb-sme__features-add"></div>')
			.append('<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>')
			.append($('<input type="text" class="mpwpb-sme__features-draft">').attr({ placeholder: t('placeholder', 'Add a feature'), 'aria-label': t('placeholder', 'Add a feature') }))
			.append($('<button type="button" class="mpwpb-sme__features-add-btn"></button>').text(t('add', 'Add')));
		var $hint = $('<p class="mpwpb-sme__features-hint"></p>').text(t('hint', ''));
		$list.after($empty);
		$realAdd.before($bar).before($hint).addClass('mpwpb-sme__features-real-add');

		var gripSvg = '<svg width="12" height="16" viewBox="0 0 12 16" fill="currentColor" aria-hidden="true"><circle cx="3" cy="3" r="1.5"/><circle cx="9" cy="3" r="1.5"/><circle cx="3" cy="8" r="1.5"/><circle cx="9" cy="8" r="1.5"/><circle cx="3" cy="13" r="1.5"/><circle cx="9" cy="13" r="1.5"/></svg>';
		var binSvg = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M6 6l1 14h10l1-14"/></svg>';
		var tickSvg = '<span class="mpwpb-sme__features-tick" aria-hidden="true"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12l5 5 9-10"/></svg></span>';

		function decorate() {
			$list.children('.mp_remove_area').each(function () {
				var $row = $(this);
				if ($row.data('sme-feature')) {
					return;
				}
				$row.data('sme-feature', true).addClass('mpwpb-sme__feature-row');
				var $inner = $row.children('.justifyBetween').first();
				var $handle = $row.find('.mpwpb_sortable_button').first();
				$handle.html(gripSvg).attr({ role: 'button', tabindex: '-1', 'aria-label': t('drag', 'Drag to reorder'), title: t('drag', 'Drag to reorder') });
				$inner.prepend(tickSvg).prepend($handle);
				$row.find('.mpwpb_item_remove').first().html(binSvg).attr({ 'aria-label': t('remove', 'Remove feature'), title: t('remove', 'Remove feature') });
				$row.find('input[name="mpwpb_features[]"]').attr('aria-label', t('feature', 'Feature'));
			});
		}
		function sync() {
			var n = $list.children('.mp_remove_area').length;
			$chip.text(n === 1 ? t('one', '1 feature') : String(t('many', '%d features')).replace('%d', n));
			$empty.toggle(n === 0);
		}
		function add() {
			var $draft = $bar.find('.mpwpb-sme__features-draft');
			var text = $.trim($draft.val());
			if (!text) {
				$draft.trigger('focus');
				return;
			}
			$realAdd.trigger('click'); // appends the classic empty row synchronously
			var $input = $list.children('.mp_remove_area').last().find('input[name="mpwpb_features[]"]');
			$input.val(text).trigger('change');
			$draft.val('').trigger('focus');
			decorate();
			sync();
		}
		$bar.on('click', '.mpwpb-sme__features-add-btn', add);
		$bar.on('keydown', '.mpwpb-sme__features-draft', function (e) {
			if (e.key === 'Enter') {
				e.preventDefault(); // never submit the whole post form
				add();
			}
		});
		if ($.fn.sortable) {
			if ($list.data('ui-sortable')) {
				$list.sortable('option', { handle: '.mpwpb_sortable_button', items: '> .mp_remove_area', axis: 'y' });
			} else {
				$list.sortable({ handle: '.mpwpb_sortable_button', items: '> .mp_remove_area', axis: 'y' });
			}
		}
		if (window.MutationObserver) {
			new MutationObserver(function () { decorate(); sync(); }).observe($list[0], { childList: true });
		}
		decorate();
		sync();
	})();

	/* ---------------------------------------------------------------- *
	 *  Relocate the real "Service Review" heading + its 3 fields (rating,
	 *  scale, text — rendered normally, once, by the reused
	 *  MPWPB_Service_Details::service_details()) into the General
	 *  Settings card (now titled "Customer Reviews", since Shortcode and
	 *  Service template are relocated out of it below) — whole <section>
	 *  nodes moved via appendTo() (each is entirely self-contained: one
	 *  label + one input, nothing else to leave behind), so
	 *  mpwpb_service_review_ratings / _rating_scale / _rating_text still
	 *  submit exactly once. Appended at the end of that card's body, after
	 *  its own (CSS-hidden) first section.section, so that heading stays
	 *  correctly "first" there — same first-of-type reasoning as the
	 *  Service Features relocation above.
	 * ---------------------------------------------------------------- */
	(function relocateServiceReview() {
		var $card = $root.find('[data-sme-section="MPWPB_Service_Details"]');
		var $generalBody = $root.find('[data-sme-section="MPWPB_General_Settings"] .mpwpb-sme__postfields-body');
		if (!$card.length || !$generalBody.length) {
			return;
		}
		var $ratingSection = $card.find('input[name="mpwpb_service_review_ratings"]').closest('section');
		var $scaleSection = $card.find('input[name="mpwpb_service_rating_scale"]').closest('section');
		var $textSection = $card.find('input[name="mpwpb_service_rating_text"]').closest('section');
		if (!$ratingSection.length) {
			return;
		}
		var $headingSection = $ratingSection.prev();

		$headingSection.appendTo($generalBody);
		$ratingSection.appendTo($generalBody);
		$scaleSection.appendTo($generalBody);
		$textSection.appendTo($generalBody);
	})();

	/* ---------------------------------------------------------------- *
	 *  Relocate the real "Add To Cart Form Shortcode" display + "Service
	 *  template" <select> (rendered normally, once, by the reused
	 *  MPWPB_General_Settings::general_settings()) into their own rail
	 *  card in the sticky sidebar, right after Featured Image — whole
	 *  <section> nodes moved via appendTo() (.shortcode / .service-template
	 *  are each self-contained: one label + one control), so mpwpb_template
	 *  still submits exactly once.
	 * ---------------------------------------------------------------- */
	(function relocateShortcodeAndTemplate() {
		var $card = $root.find('[data-sme-section="MPWPB_General_Settings"]');
		var $shortcodeSlot = $root.find('[data-sme-shortcode-slot]');
		var $templateSlot = $root.find('[data-sme-template-slot]');
		if (!$card.length) {
			return;
		}
		var $shortcodeSection = $card.find('.shortcode');
		var $templateSection = $card.find('.service-template');
		if ($shortcodeSection.length && $shortcodeSlot.length) {
			$shortcodeSection.appendTo($shortcodeSlot);
		} else if ($shortcodeSection.length) {
			// The sidebar card renders its own shortcode strip now (display-only, no field).
			$shortcodeSection.remove();
		}
		if ($templateSection.length && $templateSlot.length) {
			// Only the real <select name="mpwpb_template"> moves; the card has its own label.
			var $select = $templateSection.find('select[name="mpwpb_template"]');
			if ($select.length) {
				$select.attr('id', 'mpwpb-sme-template').appendTo($templateSlot);
				$templateSection.remove();
			} else {
				$templateSection.appendTo($templateSlot);
			}
		}
	})();

	// Copy the booking-form shortcode (Clipboard API, with a select-the-text fallback).
	$root.on('click', '[data-sme-copy-shortcode]', function () {
		var $btn = $(this);
		var $code = $btn.closest('.mpwpb-sme__code-strip').find('[data-sme-shortcode]');
		var text = $.trim($code.text());
		var $label = $btn.find('[data-sme-copy-label]');
		if (!$btn.data('label')) { $btn.data('label', $label.text()); }
		function done(ok) {
			$btn.toggleClass('is-copied', ok);
			$label.text(ok ? $btn.attr('data-copied') : $btn.attr('data-failed'));
			clearTimeout($btn.data('timer'));
			$btn.data('timer', setTimeout(function () {
				$btn.removeClass('is-copied');
				$label.text($btn.data('label'));
			}, 1600));
		}
		function fallback() {
			var range = document.createRange();
			range.selectNodeContents($code[0]);
			var sel = window.getSelection();
			sel.removeAllRanges();
			sel.addRange(range);
			var ok = false;
			try { ok = document.execCommand('copy'); } catch (e) { ok = false; }
			done(ok);
		}
		if (navigator.clipboard && window.isSecureContext) {
			navigator.clipboard.writeText(text).then(function () { done(true); }, fallback);
		} else {
			fallback();
		}
	});

	/* ---------------------------------------------------------------- *
	 *  Customer reviews card: the three real inputs (mpwpb_service_review_
	 *  ratings / _rating_scale / _rating_text, just relocated above) are
	 *  moved into a fields column -- rating gets a -/+ counter (0..5, 0.1
	 *  steps) and live stars -- beside a live badge preview. An empty or 0
	 *  rating hides the badge on the booking page (MPWPB_Static_Template::
	 *  show_ratings()), which the preview mirrors.
	 * ---------------------------------------------------------------- */
	(function modernizeReviewsCard() {
		var $card = $root.find('[data-sme-section="MPWPB_General_Settings"]');
		var $body = $card.find('.mpwpb-sme__postfields-body').first();
		var $rating = $body.find('input[name="mpwpb_service_review_ratings"]');
		var $scale = $body.find('input[name="mpwpb_service_rating_scale"]');
		var $text = $body.find('input[name="mpwpb_service_rating_text"]');
		if (!$rating.length || !$scale.length || !$text.length) {
			return;
		}
		var txt = function (key, fallback) { return cfg[key] || fallback; };
		$card.addClass('mpwpb-sme__reviews-card');
		var $head = $card.find('.mpwpb-sme__postfields-header').first();
		if (!$head.find('.mpwpb-sme__reviews-icon').length) {
			$head.prepend('<span class="mpwpb-sme__reviews-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"><path d="M12 3l2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1-4.4-4.3 6.1-.9z"/></svg></span>');
		}
		var starsHtml = '<span class="mpwpb-sme__stars" role="img"><span class="mpwpb-sme__stars-base" aria-hidden="true">★★★★★</span><span class="mpwpb-sme__stars-fill" aria-hidden="true">★★★★★</span></span>';

		var $wrap = $('<div class="mpwpb-sme__reviews"></div>');
		var $fields = $('<div class="mpwpb-sme__reviews-fields"></div>').appendTo($wrap);

		var $ratingField = $('<div class="mpwpb-sme__reviews-field"></div>').appendTo($fields);
		$('<label for="mpwpb-sme-review-rating"></label>').text(txt('reviewRatingTxt', 'Rating')).appendTo($ratingField);
		var $ratingRow = $('<div class="mpwpb-sme__reviews-rating-row"></div>').appendTo($ratingField);
		var $counter = $('<div class="mpwpb-sme__reviews-counter"></div>').appendTo($ratingRow);
		$('<button type="button" data-review-step="-1">−</button>').attr('aria-label', txt('reviewLowerTxt', 'Lower rating')).appendTo($counter);
		$rating.attr({ id: 'mpwpb-sme-review-rating', inputmode: 'decimal', autocomplete: 'off', placeholder: '–' }).appendTo($counter);
		$('<button type="button" data-review-step="1">+</button>').attr('aria-label', txt('reviewRaiseTxt', 'Raise rating')).appendTo($counter);
		$ratingRow.append($(starsHtml).addClass('mpwpb-sme__stars--lg'));
		$('<p class="mpwpb-sme__reviews-hint"></p>').text(txt('reviewRatingHintTxt', '')).appendTo($ratingField);

		var $pair = $('<div class="mpwpb-sme__reviews-pair"></div>').appendTo($fields);
		var $scaleField = $('<div class="mpwpb-sme__reviews-field"></div>').appendTo($pair);
		$('<label for="mpwpb-sme-review-scale"></label>').text(txt('reviewScaleTxt', 'Scale text')).appendTo($scaleField);
		$scale.attr('id', 'mpwpb-sme-review-scale').appendTo($scaleField);
		var $textField = $('<div class="mpwpb-sme__reviews-field"></div>').appendTo($pair);
		$('<label for="mpwpb-sme-review-text"></label>').text(txt('reviewCountTxt', 'Review count text')).appendTo($textField);
		$text.attr({ id: 'mpwpb-sme-review-text', placeholder: txt('reviewCountPlaceholderTxt', $text.attr('placeholder') || '') }).appendTo($textField);
		$('<p class="mpwpb-sme__reviews-hint"></p>').text(txt('reviewHideHintTxt', '')).appendTo($fields);

		var $preview = $('<div class="mpwpb-sme__reviews-preview"></div>').appendTo($wrap);
		$('<div class="mpwpb-sme__reviews-preview-label"></div>').text(txt('reviewPreviewTxt', 'Preview')).appendTo($preview);
		var $badge = $('<div class="mpwpb-sme__reviews-badge"><div class="mpwpb-sme__reviews-badge-num"></div><div class="mpwpb-sme__reviews-badge-meta"></div></div>').appendTo($preview);
		$badge.find('.mpwpb-sme__reviews-badge-meta').append($(starsHtml)).append('<span class="mpwpb-sme__reviews-badge-scale"></span><span class="mpwpb-sme__reviews-badge-text"></span>');
		var $none = $('<p class="mpwpb-sme__reviews-none"></p>').text(txt('reviewNoneTxt', 'No rating set, so the badge is hidden.')).appendTo($preview);

		// Everything else in this body is now empty classic chrome (the
		// leftover headers / label rows): replace it with the new layout,
		// keeping anything that still holds a form field.
		$body.children().filter(function () {
			return !$(this).find('input, select, textarea').length;
		}).remove();
		$body.append($wrap);

		function ratingValue() {
			var v = parseFloat(String($rating.val()).replace(',', '.'));
			return isNaN(v) ? null : Math.max(0, Math.min(5, v));
		}
		function sync() {
			var v = ratingValue();
			var pct = (v === null ? 0 : v / 5 * 100) + '%';
			$wrap.find('.mpwpb-sme__stars-fill').css('width', pct);
			$wrap.find('.mpwpb-sme__stars').attr('aria-label', v === null ? '' : v + ' / 5');
			var show = v !== null && v > 0;
			$badge.toggle(show);
			$none.toggle(!show);
			$badge.find('.mpwpb-sme__reviews-badge-num').text(v === null ? '' : String($rating.val()).trim());
			$badge.find('.mpwpb-sme__reviews-badge-scale').text($scale.val());
			$badge.find('.mpwpb-sme__reviews-badge-text').text($text.val());
			$counter.find('[data-review-step="-1"]').prop('disabled', v !== null && v <= 0);
			$counter.find('[data-review-step="1"]').prop('disabled', v !== null && v >= 5);
		}
		$counter.on('click', '[data-review-step]', function () {
			var v = ratingValue();
			v = (v === null ? 0 : v) + parseInt($(this).attr('data-review-step'), 10) / 10;
			$rating.val((Math.round(Math.max(0, Math.min(5, v)) * 10) / 10).toFixed(1)).trigger('change');
			sync();
		});
		// Keep a typed value inside 0..5 (one decimal) once the field is left.
		$rating.on('blur', function () {
			var raw = String($rating.val()).trim();
			if (raw === '') { sync(); return; }
			var v = ratingValue();
			$rating.val(v === null ? '' : String(Math.round(v * 10) / 10));
			sync();
		});
		$rating.add($scale).add($text).on('input change', sync);
		sync();
	})();

	/* ---------------------------------------------------------------- *
	 *  Live toast feedback
	 * ---------------------------------------------------------------- */
	var toastTimer;
	function toast(msg) {
		var $t = $root.find('[data-sme-toast]');
		if (!$t.length) { return; }
		$t.find('[data-sme-toast-msg]').text(msg);
		$t.addClass('show');
		clearTimeout(toastTimer);
		toastTimer = setTimeout(function () { $t.removeClass('show'); }, 2200);
	}

	/* ---------------------------------------------------------------- *
	 *  Booking Widget Settings -- display mode (real off-screen <select>,
	 *  saved through the normal post save like every other field on this
	 *  screen) and theme color (a site-wide option, saved immediately via
	 *  its own AJAX call -- ajax_save_theme_color() in
	 *  MPWPB_Settings_Modern.php -- since it isn't this post's own data).
	 * ---------------------------------------------------------------- */
	$root.on('click', '[data-sme-display-mode-value]', function () {
		var $btn = $(this);
		var value = $btn.data('sme-display-mode-value');
		$root.find('[data-sme-display-mode-value]').removeClass('is-active').attr('aria-checked', 'false');
		$btn.addClass('is-active').attr('aria-checked', 'true');
		$root.find('[data-sme-display-mode-tip] span').text($btn.attr('data-tip') || '');
		$root.find('[data-sme-display-mode-real]').val(value).trigger('change');
	});

	function saveThemeColor(color, $swatch) {
		if (!cfg.ajax) { return; }
		$.post(cfg.ajax, {
			action: 'mpwpb_save_theme_color',
			nonce: cfg.nonce,
			color: color
		}).done(function (resp) {
			if (resp && resp.success) {
				var $custom = $root.find('.mpwpb-sme__bw-swatch--custom');
				$root.find('[data-sme-theme-swatches] .mpwpb-sme__bw-swatch').removeClass('is-active');
				if ($swatch) {
					// A preset won -- the custom swatch goes back to its idle
					// colour-wheel "+" look.
					$swatch.addClass('is-active');
					$custom.removeClass('has-color');
				} else {
					setCustomSwatchColor($custom, color);
					$custom.addClass('is-active');
				}
				$custom.find('[data-sme-theme-color-custom]').val(color);
				setPreviewThemeColor(color);
				savedThemeColor = color;
				broadcastThemeColor(color);
				toast(cfg.themeColorSavedTxt || 'Theme color updated');
			} else {
				if (savedThemeColor) { setPreviewThemeColor(savedThemeColor); }
				toast(cfg.themeColorErrorTxt || 'Could not save theme color');
			}
		}).fail(function () {
			if (savedThemeColor) { setPreviewThemeColor(savedThemeColor); }
			toast(cfg.themeColorErrorTxt || 'Could not save theme color');
		});
	}
	$root.on('click', '[data-sme-theme-color]', function () {
		saveThemeColor($(this).data('sme-theme-color'), $(this));
	});
	// Live-preview the custom swatch while dragging in the native picker;
	// only save once the user actually commits a value (change), so picking
	// a color doesn't fire an AJAX call per pixel of drag.
	function setCustomSwatchColor($custom, color) {
		$custom.addClass('has-color');
		if ($custom[0]) { $custom[0].style.setProperty('--swatch', color); }
	}
	// Frontend Preview follows the Theme Color (--fp-theme, read by
	// .mpwpb-sme__fp-mock in mpwpb-service-edit-modern.css).
	function setPreviewThemeColor(color) {
		$root.find('[data-sme-fp-mock], .mpwpb-sme__bw-card').each(function () {
			this.style.setProperty(this.hasAttribute('data-sme-fp-mock') ? '--fp-theme' : '--bw-accent', color);
		});
		// The whole plugin backend follows too: the editor's --brand and the
		// classic screens' accents all read these :root variables.
		if (/^#[0-9a-f]{6}$/i.test(color)) {
			var rs = document.documentElement.style;
			rs.setProperty('--mpwpb_color_theme', color);
			['ee', 'cc', 'aa', '88', '77'].forEach(function (a) { rs.setProperty('--mpwpb_color_theme_' + a, color + a); });
			rs.setProperty('--mage-primary', color);
		}
	}
	// Last color the server accepted -- restored if a save fails.
	var savedThemeColor = String($root.find('[data-sme-theme-color-custom]').val() || '');
	// Tell any open frontend tab (same site) to repaint -- see the storage
	// listener MPWPB_Global_Style prints on booking pages.
	function broadcastThemeColor(color) {
		try { window.localStorage.setItem('mpwpb_theme_color', JSON.stringify({ color: color, t: Date.now() })); } catch (e) {}
	}
	$root.on('input', '[data-sme-theme-color-custom]', function () {
		setCustomSwatchColor($(this).closest('.mpwpb-sme__bw-swatch--custom'), $(this).val());
		setPreviewThemeColor($(this).val());
	});
	$root.on('change', '[data-sme-theme-color-custom]', function () {
		$root.find('[data-sme-theme-swatches] .mpwpb-sme__bw-swatch').not('.mpwpb-sme__bw-swatch--custom').removeClass('is-active');
		saveThemeColor($(this).val(), null);
	});

	/* ---------------------------------------------------------------- *
	 *  Featured image (WP post thumbnail) uploader in the preview rail
	 * ---------------------------------------------------------------- */
	function setHero(id, url) {
		var $img = $('#mpwpb-sme-hero-img');
		var $ph = $root.find('.mpwpb-sme__rail-hero-ph');
		$('#mpwpb-sme-thumbnail').val(id || '');
		if (id) {
			$root.find('.mpwpb-sme__feat-card').removeClass('mpwpb-sme__field-invalid');
		}
		if (url) {
			$img.attr('src', url).show();
			$ph.hide();
			$root.find('[data-sme-feat-remove]').show();
		} else {
			$img.attr('src', '').hide();
			$ph.show();
			$root.find('[data-sme-feat-remove]').hide();
		}
		$root.find('[data-sme-feat-set]').text(url ? 'Change image' : 'Set image');
		scheduleLocalDraft();
	}
	var featFrame;
	$root.on('click', '[data-sme-feat-set]', function (e) {
		e.preventDefault();
		if (typeof wp === 'undefined' || !wp.media) { return; }
		if (featFrame) { featFrame.open(); return; }
		featFrame = wp.media({ title: (cfg.featTitle || 'Select featured image'), button: { text: (cfg.featBtn || 'Use image') }, library: { type: 'image' }, multiple: false });
		featFrame.on('select', function () {
			var a = featFrame.state().get('selection').first().toJSON();
			var url = (a.sizes && a.sizes.medium) ? a.sizes.medium.url : a.url;
			setHero(a.id, url);
			toast(cfg.featSet || 'Featured image set');
		});
		featFrame.open();
	});
	$root.on('click', '[data-sme-feat-remove]', function (e) {
		e.preventDefault();
		setHero('', '');
		toast(cfg.featRemoved || 'Featured image removed');
	});

	/* ---------------------------------------------------------------- *
	 *  Skeleton / shimmer placeholder loaders for each panel/rail card.
	 *  Skeleton HTML is server-rendered in PHP so it shows immediately on
	 *  page load — JS only removes the --loading class per panel.
	 * ---------------------------------------------------------------- */
	(function initSkeletons() {
		function removeLoading(el) {
			var ov = el.querySelector('.mpwpb-sme__skel-ov');
			if (ov) {
				ov.classList.add('out');
				setTimeout(function () {
					el.classList.remove('mpwpb-sme__panel--loading', 'mpwpb-sme__rail-card--loading');
				}, 260);
			} else {
				el.classList.remove('mpwpb-sme__panel--loading', 'mpwpb-sme__rail-card--loading');
			}
		}

		$root.find('.mpwpb-sme__panel').each(function () {
			var panel = this;
			var done = false;
			var obs = new MutationObserver(function () {
				if (!done && panel.classList.contains('active')) {
					done = true;
					obs.disconnect();
					setTimeout(function () { removeLoading(panel); }, 400);
				}
			});
			obs.observe(panel, { attributes: true, attributeFilter: ['class'] });
		});

		setTimeout(function () {
			var panel = $root.find('.mpwpb-sme__panel.active')[0];
			if (panel) { removeLoading(panel); }

			$root.find('.mpwpb-sme__rail-card--loading').each(function () {
				removeLoading(this);
			});
		}, 500);
	})();

	/* ---------------------------------------------------------------- *
	 *  Payment Method modal -- reuses the real Settings > Payment Method
	 *  panel (rendered server-side into #mpwpb-sme-payment-modal-body).
	 *  Its own toggle/accordion/gateway-save JS is part of that reused
	 *  markup and needs no wiring here. The one thing it can't do on its
	 *  own is save: its "Save Changes" button is a plain submit_button()
	 *  expecting a real <form action="options.php"> ancestor (which it
	 *  has on the actual Settings page) -- here it would otherwise submit
	 *  this screen's #post form instead, so that click is intercepted and
	 *  redirected to a dedicated AJAX action.
	 * ---------------------------------------------------------------- */
	var $paymentModal = $('#mpwpb-sme-payment-modal');
	if ($paymentModal.length) {
		$(document).on('click', '[data-sme-payment-modal-open]', function (e) {
			e.preventDefault();
			$paymentModal.css('display', 'flex');
		});
		$(document).on('click', '[data-sme-payment-modal-close]', function () {
			$paymentModal.hide();
		});
		$paymentModal.on('click', function (e) {
			if (e.target === this) { $paymentModal.hide(); }
		});
		$(document).on('keydown', function (e) {
			if ((e.key === 'Escape' || e.keyCode === 27) && $paymentModal.is(':visible')) {
				$paymentModal.hide();
			}
		});
		$(document).on('click', '#mpwpb-sme-payment-modal-body #submit', function (e) {
			e.preventDefault();
			if (!cfg.ajax) { return; }
			var $btn = $(this);
			var $status = $btn.next('.mpwpb-sme-payment-save-status');
			if (!$status.length) {
				$status = $('<span class="mpwpb-sme-payment-save-status" style="margin-left:10px;"></span>');
				$btn.after($status);
			}
			var data = $('#mpwpb-sme-payment-modal-body').find(':input[name^="mpwpb_payment_method_settings"]').serializeArray();
			data.push({ name: 'action', value: 'mpwpb_save_payment_method_settings' });
			data.push({ name: 'nonce', value: cfg.paymentNonce });
			$btn.prop('disabled', true);
			$status.css('color', '').text(cfg.savingTxt || 'Saving…');
			$.post(cfg.ajax, data).done(function (resp) {
				if (resp && resp.success) {
					$status.css('color', '#1c9a5b').text((resp.data && resp.data.message) || cfg.paymentSaved || 'Saved.');
					setTimeout(function () { window.location.reload(); }, 700);
				} else {
					$status.css('color', '#b32d2e').text((resp && resp.data && resp.data.message) || cfg.paymentError || 'Something went wrong.');
				}
			}).fail(function () {
				$status.css('color', '#b32d2e').text(cfg.paymentError || 'Something went wrong.');
			}).always(function () {
				$btn.prop('disabled', false);
			});
		});
	}

	// Initialise client-only draft recovery before interaction tracking starts.
	suspendServerAutosave();
	restoreLocalDraft();
	localDraftReady = true;
	localDraftLastSignature = currentLocalDraftSignature();
	if (!localDraftPersisted) {
		setLocalDraftStatus('ready', cfg.localReadyTxt || 'Local autosave ready');
	}

	// TinyMCE edits happen inside an iframe and do not bubble through #post,
	// so attach their change stream separately once each editor is ready.
	(function bindEditorDraftEvents(attempt) {
		var found = false;
		eachEditor(function (editor) {
			var element = editor.getElement ? editor.getElement() : null;
			if (!element || !$.contains($root[0], element) || editor._mpwpbLocalDraftBound) { return; }
			editor._mpwpbLocalDraftBound = true;
			editor.on('input change undo redo', function () {
				formDirty = true;
				if (isSaving) { changedDuringServerSave = true; }
				scheduleLocalDraft();
			});
			found = true;
		});
		if ((!found || attempt < 4) && attempt < 20) {
			setTimeout(function () { bindEditorDraftEvents(attempt + 1); }, 300);
		}
	})(0);

	// Repeater add/remove operations mutate the DOM rather than changing an
	// input immediately. A child-list observer captures those structural edits.
	if (window.MutationObserver) {
		(new MutationObserver(function (mutations) {
			var changed = mutations.some(function (mutation) {
				return (mutation.addedNodes.length || mutation.removedNodes.length) &&
					$(mutation.target).closest('.mp_item_insert.mp_sortable_area').length;
			});
			if (changed) {
				formDirty = true;
				if (isSaving) { changedDuringServerSave = true; }
				scheduleLocalDraft();
			}
		})).observe($root[0], { childList: true, subtree: true });
	}

	// Some legacy media/icon widgets update hidden inputs with .val() and do
	// not emit an input/change event. This lightweight fallback detects those
	// programmatic changes without making any network request.
	setInterval(function () {
		if (!localDraftReady || localDraftRestoring || isSaving) { return; }
		var signature = currentLocalDraftSignature();
		if (signature !== localDraftLastSignature) {
			localDraftLastSignature = signature;
			formDirty = true;
			scheduleLocalDraft();
		}
	}, 2000);

	// Resume the last-viewed step for this post, if any.
	var smeStartIndex = 0;
	try {
		var smeRememberedIndex = order.indexOf(sessionStorage.getItem(smeStepStorageKey()));
		if (smeRememberedIndex > -1) { smeStartIndex = smeRememberedIndex; }
	} catch (e) {}
	goStep(smeStartIndex);

	// WordPress' classic-editor warning compares against values captured at
	// page load, which remain stale after an AJAX save. Replace it with a
	// baseline that is reset after each successful in-place save.
	$('#post').on('input.mpwpb-sme-dirty change.mpwpb-sme-dirty', ':input', function () {
		formDirty = true;
		if (isSaving) { changedDuringServerSave = true; }
		scheduleLocalDraft();
	});
	$(window).off('beforeunload.edit-post').on('beforeunload.mpwpb-sme', function (event) {
		if ((formDirty || editorsAreDirty()) && !localDraftPersisted) {
			event.preventDefault();
			return 'The changes you made will be lost if you navigate away from this page.';
		}
	});

	/* ---------------------------------------------------------------- *
	 *  Keeps the sticky rail's (Frontend Preview card etc.) stuck
	 *  position in sync with the sticky stepper bar's REAL rendered
	 *  height -- the step number+label can wrap to two lines at some
	 *  widths, so a hardcoded `top` risked the rail sticking too high
	 *  and rendering under the stepper (which sits at a higher z-index)
	 *  instead of cleanly below it.
	 * ---------------------------------------------------------------- */
	function syncRailStickyTop() {
		var $stepper = $('.mpwpb-sme__stepper');
		var $rail = $('.mpwpb-sme__rail');
		if (!$stepper.length || !$rail.length) {
			return;
		}
		var stepperTop = parseFloat($stepper.css('top')) || 0;
		var gap = 16;
		$rail.css('top', (stepperTop + $stepper.outerHeight() + gap) + 'px');
	}
	syncRailStickyTop();
	var railStickyResizeTimer = null;
	$(window).on('resize.mpwpb-sme-rail-top', function () {
		clearTimeout(railStickyResizeTimer);
		railStickyResizeTimer = setTimeout(syncRailStickyTop, 150);
	});

	/* ---------------------------------------------------------------- *
	 *  Gallery images card: thumbnail grid with the add button as the
	 *  last tile (a large "Add photos" area while empty), photo count,
	 *  drag to reorder, and an "off" note. The classic markup/handlers
	 *  (mpwpb_admin_settings.js: .add_multi_image / .mpwpb_remove_multi_image
	 *  and the hidden comma-separated mpwpb_slider_images) are untouched;
	 *  reordering rewrites that hidden value in the new order.
	 * ---------------------------------------------------------------- */
	(function modernizeGallery() {
		var $card = $root.find('.mpwpb-sme__gallery-card');
		var $body = $card.find('.mpwpb-sme__gallery-body');
		var $area = $body.find('.mpwpb_multi_image_area');
		if (!$area.length) { return; }
		var $grid = $area.find('.mpwpb_multi_image');
		var $btn = $area.find('.add_multi_image');
		var $foot = $('<div class="mpwpb-sme__gallery-foot"><span data-sme-gallery-count></span><span class="mpwpb-sme__gallery-reorder"></span></div>');
		$foot.find('.mpwpb-sme__gallery-reorder').text($body.attr('data-reorder') || 'Drag to reorder');
		$area.append($foot);
		var syncing = false;

		function writeOrder() {
			var ids = $grid.children('.mpwpb_multi_image_item').map(function () { return $(this).attr('data-image-id'); }).get()
				.filter(function (id) { return !!id; });
			$area.find('.mpwpb_multi_image_value').val(ids.join(','));
		}
		function sync() {
			if (syncing) { return; }
			syncing = true;
			var count = $grid.children('.mpwpb_multi_image_item').length;
			var empty = count === 0;
			$area.toggleClass('is-empty', empty);
			$btn.empty().attr('aria-label', $body.attr('data-add-title') || 'Add photos');
			if (empty) {
				$btn.append('<span class="mpwpb-sme__gallery-add-icon" aria-hidden="true"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 16V4"/><path d="M7 9l5-5 5 5"/><path d="M4 16v3a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-3"/></svg></span>')
					.append($('<strong></strong>').text($body.attr('data-add-title') || 'Add photos'))
					.append($('<small></small>').text($body.attr('data-add-hint') || ''));
				if ($btn.next()[0] !== $foot[0]) { $btn.insertBefore($foot); }
			} else {
				$btn.append('<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>')
					.append($('<span></span>').text($body.attr('data-add-tile') || 'Add'));
				// Always the last tile, also after a newly added photo (only moved when
				// it isn't already last, so the observer below doesn't loop).
				if ($grid.children().last()[0] !== $btn[0]) { $grid.append($btn); }
			}
			$grid.children('.mpwpb_multi_image_item').find('.mpwpb_remove_multi_image')
				.attr({ role: 'button', tabindex: '0', 'aria-label': $body.attr('data-remove') || 'Remove photo' });
			var label = count === 1 ? ($body.attr('data-one') || '1 photo') : String($body.attr('data-many') || '%d photos').replace('%d', count);
			$foot.find('[data-sme-gallery-count]').text(label);
			$foot.toggle(!empty);
			$foot.find('.mpwpb-sme__gallery-reorder').toggle(count > 1);
			syncing = false;
		}
		if ($.fn.sortable) {
			$grid.sortable({
				items: '> .mpwpb_multi_image_item',
				tolerance: 'pointer',
				placeholder: 'mpwpb-sme__gallery-placeholder',
				forcePlaceholderSize: true,
				update: function () { writeOrder(); sync(); }
			});
		}
		// Keyboard users can remove too (the remove icon is a span).
		$grid.on('keydown', '.mpwpb_remove_multi_image', function (e) {
			if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); $(this).trigger('click'); }
		});
		if (window.MutationObserver) {
			new MutationObserver(function () { setTimeout(sync, 0); }).observe($grid[0], { childList: true });
		}
		var $toggle = $card.find('input[name="mpwpb_display_slider"]');
		function syncToggle() {
			var on = $toggle.is(':checked');
			var $sub = $card.find('[data-sme-gallery-sub]');
			$sub.text(on ? $sub.attr('data-on') : $sub.attr('data-off'));
			$card.find('[data-sme-gallery-off]').prop('hidden', on);
		}
		$toggle.on('change', syncToggle);
		sync();
		syncToggle();
	})();

})(jQuery);
