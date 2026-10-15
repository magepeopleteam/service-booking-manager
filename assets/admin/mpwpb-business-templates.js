/**
 * "Start from a business template" picker on the Service List admin page:
 * a searchable list grouped by industry, and a preview of what the selected
 * template creates (services and prices, hours, add-ons, staff, extras).
 * Nothing is imported until "Create …" is clicked. Localized data comes from
 * mpwpbBt (Admin/MPWPB_Business_Templates_Import.php::enqueue_assets).
 */
(function ($) {
	'use strict';

	if (typeof mpwpbBt === 'undefined') {
		return;
	}
	var t = mpwpbBt.i18n || {};
	var templates = mpwpbBt.templates || [];
	var selected = templates.length ? templates[0].key : '';
	var lastFocus = null;

	function esc(str) {
		return $('<div>').text(str == null ? '' : str).html();
	}
	function fmt(tpl, v) {
		return String(tpl || '').replace(/%(\d\$)?[sd]/, v).replace('%%', '%');
	}
	function byKey(key) {
		for (var i = 0; i < templates.length; i++) {
			if (templates[i].key === key) { return templates[i]; }
		}
		return null;
	}
	function iconHtml(tpl, size) {
		return '<span class="mpwpb-bt__ico mpwpb-bt__ico--' + size + '" style="background:' + esc(tpl.color) + '1a;color:' + esc(tpl.color) + '" aria-hidden="true"><span class="dashicons ' + esc(tpl.icon) + '"></span></span>';
	}

	function listHtml(q) {
		var needle = $.trim(q || '').toLowerCase();
		var groups = [], byGroup = {};
		templates.forEach(function (tpl) {
			if (needle && (tpl.label + ' ' + tpl.title + ' ' + tpl.group).toLowerCase().indexOf(needle) === -1) { return; }
			if (!byGroup[tpl.group]) { byGroup[tpl.group] = []; groups.push(tpl.group); }
			byGroup[tpl.group].push(tpl);
		});
		if (!groups.length) {
			return '<p class="mpwpb-bt__nomatch">' + esc(t.noMatch) + '</p>';
		}
		return groups.map(function (g) {
			return '<div class="mpwpb-bt__group" role="presentation">' + esc(g) + '</div>'
				+ byGroup[g].map(function (tpl) {
					var on = tpl.key === selected;
					return '<button type="button" class="mpwpb-bt__item' + (on ? ' is-active' : '') + '" data-bt-key="' + esc(tpl.key) + '" aria-pressed="' + (on ? 'true' : 'false') + '">'
						+ iconHtml(tpl, 'sm') + '<span class="mpwpb-bt__item-name">' + esc(tpl.label) + '</span></button>';
				}).join('');
		}).join('');
	}

	function previewHtml(tpl) {
		if (!tpl) { return ''; }
		var rows = '', group = null;
		tpl.services.forEach(function (s) {
			if (s.group !== group) {
				group = s.group;
				if (group) { rows += '<div class="mpwpb-bt__svc-group">' + esc(group) + '</div>'; }
			}
			rows += '<div class="mpwpb-bt__svc"><span class="mpwpb-bt__svc-name">' + esc(s.name) + '</span>'
				+ '<span class="mpwpb-bt__svc-dur">' + esc(s.duration || '—') + '</span>'
				+ '<span class="mpwpb-bt__svc-price">' + esc(s.price) + '</span></div>';
		});
		var also = [];
		if (tpl.faqCount) { also.push(fmt(t.faqs, tpl.faqCount)); }
		if (tpl.reviewCount) { also.push(fmt(t.reviews, tpl.reviewCount)); }
		if (tpl.recurringDiscount !== null && tpl.recurringDiscount !== undefined) { also.push(fmt(t.recurring, tpl.recurringDiscount)); }
		function fact(label, value) {
			return '<div class="mpwpb-bt__fact"><div class="mpwpb-bt__fact-label">' + esc(label) + '</div><div class="mpwpb-bt__fact-value">' + esc(value || t.none) + '</div></div>';
		}
		return '<div class="mpwpb-bt__pv-head">' + iconHtml(tpl, 'lg')
			+ '<div><div class="mpwpb-bt__pv-kind">' + esc(tpl.label) + '</div><h3 class="mpwpb-bt__pv-title" id="mpwpb-bt-pv-title">' + esc(tpl.title) + '</h3></div></div>'
			+ (tpl.subtitle ? '<p class="mpwpb-bt__pv-sub">' + esc(tpl.subtitle) + '</p>' : '')
			+ '<div class="mpwpb-bt__pv-label">' + esc(tpl.serviceCount + ' ' + (t.servicesTitle || 'Services').toLowerCase()) + '</div>'
			+ '<div class="mpwpb-bt__svcs">' + rows + '</div>'
			+ '<div class="mpwpb-bt__facts">'
			+ fact(t.hours, tpl.hours)
			+ fact(t.addons, tpl.extras.join(' · '))
			+ fact(t.staff, tpl.staff.join(', '))
			+ fact(t.also, also.join(' · '))
			+ '</div>';
	}

	function paint() {
		var tpl = byKey(selected);
		var $root = $('#mpwpb-bt-overlay');
		$root.find('[data-bt-list]').html(listHtml($root.find('[data-bt-search]').val()));
		$root.find('[data-bt-preview]').html(previewHtml(tpl)).scrollTop(0);
		$root.find('[data-bt-create]').text(tpl ? fmt(t.create, tpl.title) : '').prop('disabled', !tpl);
	}

	function renderModal() {
		lastFocus = document.activeElement;
		var html = '<div class="mpwpb-bt__overlay" id="mpwpb-bt-overlay">'
			+ '<div class="mpwpb-bt__modal" role="dialog" aria-modal="true" aria-labelledby="mpwpb-bt-title">'
			+ '<div class="mpwpb-bt__head"><h2 class="mpwpb-bt__title" id="mpwpb-bt-title">' + esc(t.title) + '</h2>'
			+ '<button type="button" class="mpwpb-bt__close" data-bt-close aria-label="' + esc(t.close) + '"><span class="dashicons dashicons-no-alt"></span></button></div>'
			+ '<div class="mpwpb-bt__split">'
			+ '<div class="mpwpb-bt__side"><div class="mpwpb-bt__search"><input type="search" data-bt-search placeholder="' + esc(fmt(t.searchCount, templates.length)) + '" aria-label="' + esc(t.search) + '"></div>'
			+ '<div class="mpwpb-bt__list" data-bt-list></div></div>'
			+ '<div class="mpwpb-bt__preview" data-bt-preview aria-live="polite"></div>'
			+ '</div>'
			+ '<div class="mpwpb-bt__foot"><p class="mpwpb-bt__note">' + esc(t.footNote) + '</p>'
			+ '<p class="mpwpb-bt__error" data-bt-error role="alert" hidden></p>'
			+ '<button type="button" class="mpwpb-bt__btn" data-bt-close>' + esc(t.cancel) + '</button>'
			+ '<button type="button" class="mpwpb-bt__btn mpwpb-bt__btn--primary" data-bt-create></button></div>'
			+ '</div></div>';
		$('#mpwpb-bt-root').html(html);
		paint();
		$('#mpwpb-bt-overlay [data-bt-search]').trigger('focus');
	}

	function closeModal() {
		if (!$('#mpwpb-bt-overlay').length) { return; }
		$('#mpwpb-bt-root').empty();
		if (lastFocus && lastFocus.focus) { lastFocus.focus(); }
	}

	function importSelected() {
		var tpl = byKey(selected);
		if (!tpl) { return; }
		var $root = $('#mpwpb-bt-overlay');
		var $btn = $root.find('[data-bt-create]');
		$root.find('[data-bt-error]').prop('hidden', true);
		$root.find('.mpwpb-bt__item, [data-bt-search]').prop('disabled', true);
		$btn.prop('disabled', true).html('<span class="dashicons dashicons-update mpwpb-bt__spin" aria-hidden="true"></span>' + esc(t.importing));
		$.post(mpwpbBt.ajaxUrl, {
			action: mpwpbBt.action,
			nonce: mpwpbBt.nonce,
			template_key: tpl.key
		}).done(function (response) {
			if (response && response.success && response.data && response.data.edit_url) {
				window.location.href = response.data.edit_url;
				return;
			}
			fail((response && response.data && response.data.message) || t.error);
		}).fail(function () {
			fail(t.error);
		});
		function fail(message) {
			$root.find('[data-bt-error]').text(message).prop('hidden', false);
			$root.find('.mpwpb-bt__item, [data-bt-search]').prop('disabled', false);
			$btn.prop('disabled', false).text(fmt(t.create, tpl.title));
		}
	}

	$(document).on('click', '#mpwpb-open-business-templates', function (e) {
		e.preventDefault();
		renderModal();
	});
	$(document).on('click', '#mpwpb-bt-overlay [data-bt-close]', closeModal);
	$(document).on('click', '#mpwpb-bt-overlay', function (e) {
		if (e.target === this) { closeModal(); }
	});
	$(document).on('keydown', function (e) {
		if (e.key === 'Escape' && $('#mpwpb-bt-overlay').length) { closeModal(); }
	});
	$(document).on('input', '#mpwpb-bt-overlay [data-bt-search]', function () {
		$('#mpwpb-bt-overlay [data-bt-list]').html(listHtml($(this).val()));
	});
	$(document).on('click', '#mpwpb-bt-overlay .mpwpb-bt__item', function () {
		selected = $(this).attr('data-bt-key');
		var $list = $('#mpwpb-bt-overlay [data-bt-list]');
		$list.find('.mpwpb-bt__item').removeClass('is-active').attr('aria-pressed', 'false');
		$(this).addClass('is-active').attr('aria-pressed', 'true');
		var tpl = byKey(selected);
		$('#mpwpb-bt-overlay [data-bt-preview]').html(previewHtml(tpl)).scrollTop(0);
		$('#mpwpb-bt-overlay [data-bt-create]').text(fmt(t.create, tpl.title));
	});
	$(document).on('click', '#mpwpb-bt-overlay [data-bt-create]', importSelected);

	// Arriving from the admin menu's "Add Service > Start from a template".
	if (/[?&]mpwpb_templates=1(&|$)/.test(window.location.search) && $('#mpwpb-open-business-templates').length) {
		renderModal();
		if (window.history && window.history.replaceState) {
			window.history.replaceState(null, '', window.location.href.replace(/([?&])mpwpb_templates=1(&|$)/, function (m, a, b) { return b ? a : ''; }));
		}
	}

}(jQuery));
