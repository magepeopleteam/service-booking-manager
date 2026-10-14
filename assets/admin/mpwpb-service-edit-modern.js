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
		$root.find('[data-sme-stepof]').text('Step ' + (cur + 1) + ' of ' + total);
		$root.find('[data-sme-prev]').prop('disabled', cur === 0);

		var $next = $root.find('[data-sme-next]');
		$next.text(cur === order.length - 1 ? (cfg.updateTxt || 'Update') : (cfg.nextTxt || 'Next Step'));

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
			$side.html('<button type="button" class="mpwpb-sme__fp-row-add" data-sme-fp-add="' + id + '"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg></button>');
			$side.find('[data-sme-fp-add]').attr('aria-label', (cfg.previewAddTxt || 'Add') + ' ' + name);
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
			$side.html('<button type="button" class="mpwpb-sme__fp-row-add" data-sme-fp-extra-add="' + id + '"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg></button>');
			$side.find('[data-sme-fp-extra-add]').attr('aria-label', (cfg.previewAddTxt || 'Add') + ' ' + name);
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
	}
	// Extra services are page-wide, not linked to any one regular service
	// (see MPWPB_Settings_Modern.php's render comment) -- the list simply
	// appears once ANY regular service is selected, and hides again if the
	// last one is removed.
	function fpUpdateExtrasVisibility($card) {
		var $extras = $card.find('[data-sme-fp-extras]');
		if (!$extras.length) { return; }
		$extras.toggle($extras.find('[data-sme-fp-extra-row]').length > 0 && fpTotalQty() > 0);
	}
	function fpSetQty($card, id, qty) {
		fpQty[id] = Math.max(0, qty);
		if (fpQty[id] === 0) { delete fpQty[id]; }
		fpRenderRowSide($card, id);
		fpUpdateCta($card);
		fpUpdateServicesSummary($card);
		fpUpdateExtrasVisibility($card);
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
		$('<span class="mpwpb-sme__fp-row-dur"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><polyline points="12,7 12,12 15,14"></polyline></svg></span>')
			.append(document.createTextNode(svc.duration ? String(svc.duration) : '—'))
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
		fpUpdateCta($card);
		fpUpdateServicesSummary($card);
		fpUpdateExtrasVisibility($card);
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
				.attr('aria-label', label + ' ' + d.getDate());
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
			if (dayNum === 0) { fpSelectedDate = label + ' ' + d.getDate(); } // "Today" starts pre-selected
		}
		$strip.on('click', 'button', function () {
			$strip.find('button').removeClass('is-selected');
			$(this).addClass('is-selected');
			fpSelectedDate = $(this).attr('aria-label');
			fpUpdateDatetimeCta($strip.closest('.mpwpb-sme__fp-step--datetime'));
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
		// Same mount point attribute as the Services step's summary --
		// fpUpdateServicesSummary() already updates every element matching
		// it inside the card, so this one just rides along automatically.
		$('<div class="mpwpb-sme__fp-summary" data-sme-fp-services-summary style="display:none"></div>').appendTo($step);
		$('<button type="button" class="mpwpb-sme__fp-cta" data-sme-fp-dt-cta disabled></button>')
			.text(cfg.previewContinueTxt || 'Continue to Checkout')
			.appendTo($step);
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
		$summary.append(fpSummaryRow(cfg.previewTotalTxt || 'Total', (cfg.previewCurrency || '') + total.toFixed(2), true));

		$('<button type="button" class="mpwpb-sme__fp-cta" data-sme-fp-confirm></button>')
			.text(cfg.previewConfirmTxt || 'Confirm Booking')
			.appendTo($step);
	}
	function fpUpdateSteps($mock, name) {
		var order = ['services', 'datetime', 'checkout'];
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
	var FP_PREV_STEP = { datetime: 'services', checkout: 'datetime' };
	function fpGoToStep($mock, name) {
		var $services = $mock.find('[data-sme-fp-step="services"]');
		var $datetime = $mock.find('[data-sme-fp-step="datetime"]');
		var $checkout = $mock.find('[data-sme-fp-step="checkout"]');
		if (name === 'datetime' && !$datetime.children().length) {
			fpBuildDatetimeStep($datetime);
		}
		if (name === 'checkout') {
			fpBuildCheckoutStep($checkout); // always fresh: selections can change between visits
		}
		$services.toggleClass('is-active', name === 'services').prop('hidden', name !== 'services');
		$datetime.toggleClass('is-active', name === 'datetime').prop('hidden', name !== 'datetime');
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
		fpGoToStep($(this).closest('.mpwpb-sme__fp-mock'), 'checkout');
	});
	$root.on('click', '[data-sme-fp-confirm]', function (e) {
		e.preventDefault();
		toast(cfg.previewOnlyTxt || 'Preview only — real bookings continue on the live page.');
	});
	$root.on('click', '[data-sme-fp-footer-back]', function () {
		var $mock = $(this).closest('.mpwpb-sme__preview-card').find('[data-sme-fp-mock]');
		fpGoToStep($mock, FP_PREV_STEP[fpCurrentStep] || 'services');
	});

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
			$root.find('[data-sme-next]').text(data.primaryLabel || (cfg.updateTxt || 'Update'));
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
	 *  Relocate a reused section's own on/off switch (e.g. Extra Service's
	 *  real "mpwpb_extra_service_active" checkbox) up into the card header,
	 *  next to the title — moving the real .roundSwitchLabel node (not a
	 *  copy), so the checkbox is still submitted exactly once regardless
	 *  of where in the DOM it visually sits.
	 * ---------------------------------------------------------------- */
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
	 *  Happy Hours rule builder. Move the four original classic labels into
	 *  two modern groups, keeping every real input/name intact. The summary
	 *  below updates immediately as the rule changes.
	 * ---------------------------------------------------------------- */
	(function modernizeHappyHours() {
		var $card = $root.find('[data-sme-section="MPWPB_Happy_Hours_Settings"]');
		var $row = $card.find('#mpwpb_happy_hours_row');
		var $fields = $row.children('label.label');
		if (!$row.length || $fields.length < 4 || $row.data('sme-modernized')) {
			return;
		}
		$row.data('sme-modernized', true).addClass('mpwpb-hh');

		function group(icon, title, subtitle, modifier) {
			return $('<div class="mpwpb-hh__group ' + modifier + '">' +
				'<div class="mpwpb-hh__group-head">' +
					'<span class="dashicons ' + icon + ' mpwpb-hh__group-icon" aria-hidden="true"></span>' +
					'<div><h4></h4><p></p></div>' +
				'</div>' +
				'<div class="mpwpb-hh__fields"></div>' +
			'</div>')
				.find('h4').text(title).end()
				.find('.mpwpb-hh__group-head p').text(subtitle).end();
		}

		var $layout = $('<div class="mpwpb-hh__layout"></div>');
		var $windowGroup = group('dashicons-clock', cfg.hhWindowTitle || 'Time Window', cfg.hhWindowSub || 'Choose when the special price applies.', 'mpwpb-hh__group--window');
		var $offerGroup = group('dashicons-tickets-alt', cfg.hhOfferTitle || 'Discount Offer', cfg.hhOfferSub || 'Set the discount customers receive.', 'mpwpb-hh__group--offer');

		$fields.eq(0).addClass('mpwpb-hh__field mpwpb-hh__field--start').appendTo($windowGroup.find('.mpwpb-hh__fields'));
		$fields.eq(1).addClass('mpwpb-hh__field mpwpb-hh__field--end').appendTo($windowGroup.find('.mpwpb-hh__fields'));
		$fields.eq(2).addClass('mpwpb-hh__field mpwpb-hh__field--type').appendTo($offerGroup.find('.mpwpb-hh__fields'));
		$fields.eq(3).addClass('mpwpb-hh__field mpwpb-hh__field--value').appendTo($offerGroup.find('.mpwpb-hh__fields'));

		var $valueControl = $offerGroup.find('.mpwpb-hh__field--value > div').last().addClass('mpwpb-hh__value-control');
		var $suffix = $('<span class="mpwpb-hh__value-suffix" aria-hidden="true"></span>').appendTo($valueControl);
		var $preview = $('<div class="mpwpb-hh__preview">' +
			'<span class="dashicons dashicons-megaphone mpwpb-hh__preview-icon" aria-hidden="true"></span>' +
			'<div class="mpwpb-hh__preview-copy"><span class="mpwpb-hh__preview-label"></span><strong><span data-hh-preview-value></span> <span class="mpwpb-hh__preview-discount"></span> &middot; <span data-hh-preview-time></span></strong></div>' +
			'<span class="mpwpb-hh__preview-badge"></span>' +
		'</div>');
		$preview.find('.mpwpb-hh__preview-label').text(cfg.hhRuleLabel || 'Active pricing rule');
		$preview.find('.mpwpb-hh__preview-discount').text(cfg.hhDiscountTxt || 'discount');
		$preview.find('.mpwpb-hh__preview-badge').text(cfg.hhAutomaticTxt || 'Automatic');
		$layout.append($windowGroup, $offerGroup);
		$row.append($layout, $preview);

		function updateRulePreview() {
			var start = $row.find('[name="mpwpb_happy_hours_start_time"]').val() || '--:--';
			var end = $row.find('[name="mpwpb_happy_hours_end_time"]').val() || '--:--';
			var type = $row.find('[name="mpwpb_happy_hours_discount_type"]').val();
			var value = $row.find('[name="mpwpb_happy_hours_discount_value"]').val() || '0';
			var suffix = type === 'percent' ? '%' : (cfg.hhCurrencySymbol || '');
			$valueControl.toggleClass('is-prefix', type === 'fixed' && !!cfg.hhCurrencySymbol);
			$suffix.text(suffix).toggle(!!suffix);
			$preview.find('[data-hh-preview-value]').text(type === 'fixed' && cfg.hhCurrencySymbol ? cfg.hhCurrencySymbol + value : value + (type === 'percent' ? '%' : ''));
			$preview.find('[data-hh-preview-time]').text(start + ' – ' + end);
		}

		$row.on('input change', 'input, select', updateRulePreview);
		updateRulePreview();
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
		var $sections = $tab.children('section').not('.section');
		if (!$tab.length || $sections.length < 3 || $tab.data('sme-modernized')) { return; }
		$tab.data('sme-modernized', true).addClass('mpwpb-recur');

		var $pattern = $('<div class="mpwpb-recur__group mpwpb-recur__group--pattern"><div class="mpwpb-recur__head"><span class="dashicons dashicons-update" aria-hidden="true"></span><div><h4></h4><p></p></div></div><div class="mpwpb-recur__pattern-body"></div></div>');
		var $limits = $('<div class="mpwpb-recur__group mpwpb-recur__group--limits"><div class="mpwpb-recur__head"><span class="dashicons dashicons-chart-bar" aria-hidden="true"></span><div><h4></h4><p></p></div></div><div class="mpwpb-recur__limit-fields"></div></div>');
		$pattern.find('h4').text(cfg.recurPatternTitle || 'Repeat Pattern');
		$pattern.find('.mpwpb-recur__head p').text(cfg.recurPatternSub || 'Choose the recurrence options customers can use.');
		$limits.find('h4').text(cfg.recurLimitsTitle || 'Limits & Incentive');
		$limits.find('.mpwpb-recur__head p').text(cfg.recurLimitsSub || 'Control the maximum series length and optional discount.');

		$sections.eq(0).addClass('mpwpb-recur__patterns').appendTo($pattern.find('.mpwpb-recur__pattern-body'));
		$sections.eq(1).addClass('mpwpb-recur__field mpwpb-recur__field--count').appendTo($limits.find('.mpwpb-recur__limit-fields'));
		$sections.eq(2).addClass('mpwpb-recur__field mpwpb-recur__field--discount').appendTo($limits.find('.mpwpb-recur__limit-fields'));
		$limits.find('[name="mpwpb_recurring_discount"]').wrap('<div class="mpwpb-recur__discount-control"></div>').after('<span aria-hidden="true">%</span>');

		var $layout = $('<div class="mpwpb-recur__layout"></div>').append($pattern, $limits);
		var $summary = $('<div class="mpwpb-recur__summary"><span class="dashicons dashicons-calendar-alt" aria-hidden="true"></span><div><small></small><strong><span data-recur-pattern></span> &middot; <span data-recur-count></span> &middot; <span data-recur-discount></span></strong></div></div>');
		$summary.find('small').text(cfg.recurRuleLabel || 'Booking series');
		var $toggle = $card.find('[name="mpwpb_enable_recurring"]');
		var isEnabled = $toggle.is(':checked');
		var $body = $('<div class="mpwpb-recur__body" data-collapse="#mpwpb_enable_recurring"></div>')
			.toggleClass('mActive', isEnabled)
			.css('display', isEnabled ? 'block' : 'none')
			.attr('aria-hidden', isEnabled ? 'false' : 'true')
			.append($layout, $summary);
		$tab.append($body);
		$toggle.on('change.mpwpb-sme-recurring', function () {
			var enabled = $(this).is(':checked');
			$body.attr('aria-hidden', enabled ? 'false' : 'true');
		});

		function updateRecurringSummary() {
			var patterns = [];
			$tab.find('[name="mpwpb_recurring_types[]"]:checked').each(function () {
				patterns.push($(this).next('.customCheckbox').text());
			});
			var count = $tab.find('[name="mpwpb_max_recurring_count"]').val() || '0';
			var discount = $tab.find('[name="mpwpb_recurring_discount"]').val() || '0';
			$summary.find('[data-recur-pattern]').text(patterns.join(', ') || cfg.recurNoneTxt || 'Select a repeat pattern');
			$summary.find('[data-recur-count]').text(count + ' ' + (cfg.recurOccurrencesTxt || 'occurrences maximum'));
			$summary.find('[data-recur-discount]').text(discount + '% ' + (cfg.recurDiscountTxt || 'recurring discount'));
		}
		$tab.on('input change', 'input', updateRecurringSummary);
		updateRecurringSummary();
	})();

	/* ---------------------------------------------------------------- *
	 *  Tax Settings — modern two-field configuration with a live class/rate
	 *  summary, using the original select and number input.
	 * ---------------------------------------------------------------- */
	(function modernizeTaxSettings() {
		var $card = $root.find('[data-sme-section="Tax_Settings"]');
		var $row = $card.find('#mpwpb_tax_class_row');
		var $fields = $row.children('label.label');
		if (!$row.length || $fields.length < 2 || $row.data('sme-modernized')) { return; }
		$row.data('sme-modernized', true).addClass('mpwpb-tax-modern');
		$fields.addClass('mpwpb-tax-modern__field').wrapAll('<div class="mpwpb-tax-modern__fields"></div>');
		var $rateControl = $row.find('#mpwpb_tax_rate_row > div').last().addClass('mpwpb-tax-modern__rate-control');
		$rateControl.append('<span aria-hidden="true">%</span>');
		var $summary = $('<div class="mpwpb-tax-modern__summary"><span class="dashicons dashicons-shield" aria-hidden="true"></span><div><small></small><strong><span data-tax-class></span> &middot; <span data-tax-rate></span></strong></div><span class="mpwpb-tax-modern__badge"></span></div>');
		$summary.find('small').text(cfg.taxRuleLabel || 'Applied tax rule');
		$summary.find('.mpwpb-tax-modern__badge').text(cfg.taxAutomaticTxt || 'Automatic');
		$row.append($summary);
		function updateTaxSummary() {
			var className = $row.find('[name="mpwpb_tax_class"] option:selected').text();
			var rate = $row.find('[name="mpwpb_tax_rate"]').val() || '0';
			$summary.find('[data-tax-class]').text(className);
			$summary.find('[data-tax-rate]').text(rate + '%');
		}
		$row.on('input change', 'input, select', updateTaxSummary);
		updateTaxSummary();
	})();

	/* ---------------------------------------------------------------- *
	 *  Staff Member — retain the existing avatar tile selector and AJAX save,
	 *  adding modern card integration plus live selection feedback.
	 * ---------------------------------------------------------------- */
	(function modernizeStaffSelection() {
		var $card = $root.find('[data-sme-section="Staff_Member"]');
		var $container = $card.find('#mpwpb_add_staff_container');
		var $grid = $container.find('.mpwpb-staff-grid');
		if (!$container.length || $container.data('sme-modernized')) { return; }
		$container.data('sme-modernized', true).addClass('mpwpb-staff-modern');
		var $head = $('<div class="mpwpb-staff-modern__head"><div><span class="dashicons dashicons-groups" aria-hidden="true"></span><div><h4></h4><p></p></div></div><strong data-staff-count></strong></div>');
		$head.find('h4').text(cfg.staffSelectTitle || 'Select Staff');
		$head.find('p').text(cfg.staffSelectSub || 'Choose the team members customers can book.');
		$grid.attr('data-empty', cfg.staffNoneTxt || 'No staff members available.');
		$container.find('.mpwpb_add_staff_container > label').hide();
		$container.find('.mpwpb_add_staff_container').prepend($head);
		function updateStaffCount() {
			var count = $grid.find('.mpwpb-staff-tile.is-selected').length;
			$head.find('[data-staff-count]').text(count + ' ' + (cfg.staffSelectedTxt || 'staff selected'));
		}
		$card.find('#mpwpb_staff_selector').on('change.mpwpb-sme-staff', function () {
			setTimeout(updateStaffCount, 0);
		});
		updateStaffCount();
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
		}
		if ($templateSection.length && $templateSlot.length) {
			$templateSection.appendTo($templateSlot);
		}
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
		$root.find('[data-sme-display-mode] .mpwpb-sme__bw-seg-btn').removeClass('is-active');
		$btn.addClass('is-active');
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
				toast(cfg.themeColorSavedTxt || 'Theme color updated');
			} else {
				toast(cfg.themeColorErrorTxt || 'Could not save theme color');
			}
		}).fail(function () {
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
	$root.on('input', '[data-sme-theme-color-custom]', function () {
		setCustomSwatchColor($(this).closest('.mpwpb-sme__bw-swatch--custom'), $(this).val());
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

})(jQuery);
