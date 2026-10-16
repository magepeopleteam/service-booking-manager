/**
 * Categories & Services (Pricing step) — sidebar category tree + service
 * list, ported from a design mockup. Persists through the EXISTING classic
 * AJAX endpoints (Admin/settings/Category.php / Service.php) — this file
 * only owns presentation + local optimistic state; the classic card-grid UI
 * (MPWPB_Price_Settings) is untouched and keeps working in Classic mode.
 */
(function ($) {
	'use strict';

	var cfg = window.mpwpbCsm;
	var $root = $('#mpwpb-csm');
	if (!cfg || !$root.length) {
		return;
	}
	var t = cfg.i18n || {};

	// Purely decorative colour/icon rotation (no stored field — the
	// per-service icon picker was deliberately dropped from the Add/Edit
	// modal as unused). Assigned deterministically by position so the same
	// category/service always gets the same swatch across re-renders.
	var CAT_PALETTE = [
		{ bg: '#e8f1ff', fg: '#2563eb' },
		{ bg: '#e8f9ef', fg: '#16a34a' },
		{ bg: '#fff4e0', fg: '#d97706' },
		{ bg: '#fdeaf3', fg: '#db2777' },
		{ bg: '#f1ecff', fg: '#7c3aed' }
	];
	var SVC_ICONS = [
		{ icon: 'dashicons-store', bg: '#e8f1ff', fg: '#2563eb' },
		{ icon: 'dashicons-art', bg: '#f1ecff', fg: '#7c3aed' },
		{ icon: 'dashicons-star-filled', bg: '#fff4e0', fg: '#d97706' },
		{ icon: 'dashicons-shield-alt', bg: '#e8f9ef', fg: '#16a34a' },
		{ icon: 'dashicons-flag', bg: '#e8f9ef', fg: '#0d9488' },
		{ icon: 'dashicons-heart', bg: '#fdeaf3', fg: '#db2777' },
		{ icon: 'dashicons-admin-tools', bg: '#fff4e0', fg: '#c2410c' },
		{ icon: 'dashicons-camera', bg: '#f1ecff', fg: '#6d28d9' }
	];
	function catSwatch(i) {
		return CAT_PALETTE[i % CAT_PALETTE.length];
	}
	function svcSwatch(i) {
		return SVC_ICONS[i % SVC_ICONS.length];
	}

	var state = {
		categories: (cfg.categories || []).slice(),
		subCategories: (cfg.subCategories || []).slice(),
		services: (cfg.services || []).slice(),
		selected: 'all', // 'all' | 'none' | category id | subcategory id
		expanded: {},
		query: ''
	};

	/* ---------------------------------------------------------------- *
	 *  Small helpers
	 * ---------------------------------------------------------------- */
	function esc(s) {
		return $('<div>').text(s === null || s === undefined ? '' : String(s)).html();
	}
	function fmtDur(dur) {
		// Duration is free text in storage (e.g. "10min", "30m", "1h" — see the
		// classic add/edit form's plain <input type="text">), never guaranteed
		// to be a bare number of minutes. Display exactly what's stored rather
		// than reparsing it — parseInt("1h") would wrongly read as "1 minute".
		return dur ? String(dur) : '—';
	}
	// Reuses the wizard shell's own toast element (mpwpb-service-edit-modern.js
	// owns its markup/CSS, but that file's `toast()` is private to its own
	// closure) rather than building a second toast UI just for this file.
	var toastTimer;
	function toast(msg) {
		var $t = $('[data-sme-toast]');
		if (!$t.length) { return; }
		$t.find('[data-sme-toast-msg]').text(msg);
		$t.addClass('show');
		clearTimeout(toastTimer);
		toastTimer = setTimeout(function () { $t.removeClass('show'); }, 2200);
	}
	function findCat(id) {
		return state.categories.filter(function (c) { return c.id === id; })[0];
	}
	function findSub(id) {
		return state.subCategories.filter(function (s) { return s.id === id; })[0];
	}
	// Categories and subcategories are two SEPARATE arrays, each independently
	// numbered from 0 (real PHP array-indices) — a subcategory id can equal a
	// category id (e.g. both "0") without being the same thing. Anywhere a
	// selection/pick needs to be compared or stored, it's namespaced as
	// 'cat:<id>' or 'sub:<id>' so the two id spaces can never collide. The
	// underlying data objects themselves (state.categories/subCategories)
	// keep their plain ids, matching what the server expects.
	function catKey(id) { return 'cat:' + id; }
	function subKey(id) { return 'sub:' + id; }
	function keyIsSub(key) { return typeof key === 'string' && key.indexOf('sub:') === 0; }
	function keyId(key) { return typeof key === 'string' ? key.replace(/^(cat|sub):/, '') : key; }
	function childrenOf(catId) {
		return state.subCategories.filter(function (s) { return s.catId === catId; });
	}
	function countFor(key) {
		if (keyIsSub(key)) {
			var subId = keyId(key);
			return state.services.filter(function (s) { return s.subCat === subId; }).length;
		}
		var catId = keyId(key);
		return state.services.filter(function (s) { return s.parentCat === catId; }).length;
	}
	function uncatCount() {
		return state.services.filter(function (s) { return !s.parentCat; }).length;
	}
	function pathOfService(svc) {
		var path = [];
		if (svc.parentCat) {
			var cat = findCat(svc.parentCat);
			if (cat) { path.push(cat); }
		}
		if (svc.subCat) {
			var sub = findSub(svc.subCat);
			if (sub) { path.push(sub); }
		}
		return path;
	}
	function pathOfSelected() {
		if (state.selected === 'all' || state.selected === 'none') { return []; }
		if (keyIsSub(state.selected)) {
			var sub = findSub(keyId(state.selected));
			var cat = sub ? findCat(sub.catId) : null;
			var out = [];
			if (cat) { out.push(cat); }
			if (sub) { out.push(sub); }
			return out;
		}
		var cat2 = findCat(keyId(state.selected));
		return cat2 ? [cat2] : [];
	}
	function visibleServices() {
		var list = state.services;
		if (state.selected === 'none') {
			list = list.filter(function (s) { return !s.parentCat; });
		} else if (state.selected !== 'all') {
			if (keyIsSub(state.selected)) {
				var subId = keyId(state.selected);
				list = list.filter(function (s) { return s.subCat === subId; });
			} else {
				var catId = keyId(state.selected);
				list = list.filter(function (s) { return s.parentCat === catId; });
			}
		}
		if (state.query.trim()) {
			var q = state.query.trim().toLowerCase();
			list = list.filter(function (s) {
				var pathText = pathOfService(s).map(function (p) { return p.name.toLowerCase(); }).join(' ');
				return s.name.toLowerCase().indexOf(q) > -1 ||
					(s.desc || '').toLowerCase().indexOf(q) > -1 ||
					pathText.indexOf(q) > -1;
			});
		}
		return list;
	}
	function nextId(list) {
		var max = -1;
		list.forEach(function (item) {
			var n = parseInt(item.id, 10);
			if (!isNaN(n) && n > max) { max = n; }
		});
		return String(max + 1);
	}

	/* ---------------------------------------------------------------- *
	 *  AJAX (existing classic endpoints — see Admin/settings/Category.php
	 *  and Admin/settings/Service.php)
	 * ---------------------------------------------------------------- */
	function post(action, data, done, fail) {
		var payload = $.extend({ action: action, nonce: cfg.nonce }, data);
		$.post(cfg.ajaxUrl, payload).done(function (resp) {
			if (resp && resp.success && !(resp.data && resp.data.status === false)) {
				done(resp && resp.data ? resp.data : {});
			} else {
				(fail || function () {})((resp && resp.data) || {});
			}
		}).fail(function () {
			(fail || function () {})({});
		});
	}

	/* ---------------------------------------------------------------- *
	 *  Sidebar
	 * ---------------------------------------------------------------- */
	function treeNodeHtml(cat, depth, catIndex) {
		var isTop = depth === 0;
		var key = isTop ? catKey(cat.id) : subKey(cat.id);
		var kids = childrenOf(cat.id);
		var isOpen = !!state.expanded[cat.id];
		var active = state.selected === key;
		var count = countFor(key);
		var sw = catSwatch(catIndex);
		var html = '<div class="mpwpb-csm__tree-row' + (active ? ' is-active' : '') + (isTop ? '' : ' is-sub-row') + '" style="padding-left:' + (depth * 18) + 'px" data-csm-select="' + esc(key) + '">';
		if (isTop) {
			// Always expandable, even with zero subcategories yet — expanding
			// an empty category is how you reach its "Add subcategory" button.
			html += '<button type="button" class="mpwpb-csm__tree-toggle" data-csm-toggle="' + esc(cat.id) + '">'
				+ '<span class="dashicons dashicons-arrow-' + (isOpen ? 'down' : 'right') + '-alt2"></span></button>';
		} else {
			html += '<span class="mpwpb-csm__tree-corner" aria-hidden="true"></span>';
		}
		html += '<span class="mpwpb-csm__tree-btn">'
			+ (isTop ? '<span class="mpwpb-csm__tree-icon-badge" style="background:' + sw.bg + ';color:' + sw.fg + '"><span class="dashicons dashicons-category"></span></span>' : '')
			+ '<span class="mpwpb-csm__tree-name' + (isTop ? '' : ' is-sub') + '">' + esc(cat.name) + '</span>'
			+ '</span>';
		html += '<span class="mpwpb-csm__tree-acts">';
		if (isTop) {
			html += '<button type="button" class="mpwpb-csm__tree-icon-btn" title="' + esc(t.addSubcategory) + '" data-csm-add-sub="' + esc(cat.id) + '"><span class="dashicons dashicons-plus-alt2"></span></button>';
		}
		html += '<button type="button" class="mpwpb-csm__tree-icon-btn" title="Edit" data-csm-edit-cat="' + esc(cat.id) + '" data-csm-is-sub="' + (isTop ? '0' : '1') + '"><span class="dashicons dashicons-edit"></span></button>';
		html += '<button type="button" class="mpwpb-csm__tree-icon-btn is-danger" title="Delete" data-csm-delete-cat="' + esc(cat.id) + '" data-csm-is-sub="' + (isTop ? '0' : '1') + '"><span class="dashicons dashicons-trash"></span></button>';
		html += '</span>';
		html += '<span class="mpwpb-csm__tree-count' + (!isTop && count > 0 ? ' has-value' : '') + '">' + count + '</span>';
		html += '</div>';
		if (isTop && isOpen) {
			html += '<div class="mpwpb-csm__tree-guide">';
			kids.forEach(function (k) { html += treeNodeHtml(k, depth + 1, catIndex); });
			html += '<button type="button" class="mpwpb-csm__add-sub-btn" style="margin-left:' + ((depth + 1) * 18) + 'px;border-color:' + sw.fg + ';color:' + sw.fg + '" data-csm-add-sub="' + esc(cat.id) + '">'
				+ '<span class="dashicons dashicons-plus-alt2"></span>' + esc(t.addSubcategory) + '</button>';
			html += '</div>';
		}
		return html;
	}

	function renderSidebar() {
		var html = '<div class="mpwpb-csm__sidebar-inner">';
		html += '<button type="button" class="mpwpb-csm__side-row' + (state.selected === 'all' ? ' is-active' : '') + '" data-csm-select="all"' + (state.selected === 'all' ? ' aria-current="page"' : '') + '>'
			+ '<span class="mpwpb-csm__side-icon"><span class="dashicons dashicons-layout"></span></span><span class="mpwpb-csm__side-label">' + esc(t.allServices) + '</span>'
			+ '<span class="mpwpb-csm__side-count">' + state.services.length + '</span></button>';
		var uc = uncatCount();
		html += '<button type="button" class="mpwpb-csm__side-row' + (state.selected === 'none' ? ' is-active' : '') + (uc === 0 ? ' is-muted' : '') + '" data-csm-select="none"' + (state.selected === 'none' ? ' aria-current="page"' : '') + '>'
			+ '<span class="mpwpb-csm__side-icon"><span class="dashicons dashicons-archive"></span></span><span class="mpwpb-csm__side-label">' + esc(t.uncategorized) + '</span>'
			+ '<span class="mpwpb-csm__side-count">' + uc + '</span></button>';

		var topCats = state.categories;
		html += '<div class="mpwpb-csm__side-divider" aria-hidden="true"></div>';
		html += '<div class="mpwpb-csm__side-head"><span class="mpwpb-csm__side-head-label">Categories' + (topCats.length ? ' · ' + topCats.length : '') + '</span>'
			+ '<button type="button" class="mpwpb-csm__side-head-add" data-csm-add-cat title="' + esc(t.newCategory) + '" aria-label="' + esc(t.newCategory) + '"><span class="dashicons dashicons-plus-alt2"></span></button></div>';

		if (!topCats.length) {
			html += '<p class="mpwpb-csm__side-empty">' + esc(t.noCategoriesYet) + '</p>';
		} else {
			topCats.forEach(function (c, i) { html += treeNodeHtml(c, 0, i); });
		}
		html += '<button type="button" class="mpwpb-csm__side-add" data-csm-add-cat><span class="dashicons dashicons-plus-alt2"></span>' + esc(t.newCategory) + '</button>';
		html += '</div>';
		html += '<div class="mpwpb-csm__hint"><span class="dashicons dashicons-info-outline" aria-hidden="true"></span><p>' + t.countsHint + '</p></div>';
		$root.find('[data-csm-part="sidebar"]').html(html);
	}

	/* ---------------------------------------------------------------- *
	 *  Main (header + list)
	 * ---------------------------------------------------------------- */
	function headerLabel() {
		if (state.selected === 'all') { return t.allServices; }
		if (state.selected === 'none') { return t.uncategorized; }
		var item = keyIsSub(state.selected) ? findSub(keyId(state.selected)) : findCat(keyId(state.selected));
		return item ? item.name : '';
	}

	function renderHeader() {
		// pathOfSelected()/pathOfService() always return [category] or
		// [category, subcategory] in that order, so position (not a shared id
		// space) is what tells us which namespace each entry belongs to.
		var path = pathOfSelected();
		// Icon tile: a grid for "All services", a folder once inside a category.
		var crumbs = '<span class="mpwpb-csm__header-icon" aria-hidden="true">' + (path.length
			? '<svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M3.5 7.5A2 2 0 0 1 5.5 5.5h4l2 2.5h7a2 2 0 0 1 2 2v7.5a2 2 0 0 1-2 2h-13a2 2 0 0 1-2-2z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>'
			: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none"><rect x="3.5" y="3.5" width="7" height="7" rx="1.8" stroke="currentColor" stroke-width="2"/><rect x="13.5" y="3.5" width="7" height="7" rx="1.8" stroke="currentColor" stroke-width="2"/><rect x="3.5" y="13.5" width="7" height="7" rx="1.8" stroke="currentColor" stroke-width="2"/><rect x="13.5" y="13.5" width="7" height="7" rx="1.8" stroke="currentColor" stroke-width="2"/></svg>') + '</span>';
		if (path.length) {
			path.forEach(function (p, i) {
				if (i > 0) { crumbs += '<span class="mpwpb-csm__crumb-sep dashicons dashicons-arrow-right-alt2"></span>'; }
				var key = i === 0 ? catKey(p.id) : subKey(p.id);
				crumbs += '<button type="button" class="mpwpb-csm__crumb' + (i === path.length - 1 ? ' is-last' : '') + '" data-csm-select="' + esc(key) + '">' + esc(p.name) + '</button>';
			});
		} else {
			crumbs += '<span class="mpwpb-csm__crumb is-last">' + esc(headerLabel()) + '</span>';
		}
		var shown = visibleServices();
		crumbs += '<span class="mpwpb-csm__count-badge">' + shown.length + '</span>';
		if (state.selected !== 'all') {
			crumbs += '<button type="button" class="mpwpb-csm__clear-filter" data-csm-select="all">' + esc(t.clearFilter || 'Clear filter')
				+ '<svg width="10" height="10" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2.8" stroke-linecap="round"/></svg></button>';
		}

		$root.find('[data-csm-part="crumbs"]').html(crumbs);
	}

	var CHEV = '<svg class="mpwpb-csm__path-sep" width="10" height="10" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M9 6l6 6-6 6" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/></svg>';
	var ICON = {
		edit: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/></svg>',
		dup: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="12" height="12" rx="2"/><path d="M5 15V5a2 2 0 0 1 2-2h10"/></svg>',
		bin: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/></svg>',
		clock: '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>',
		plus: '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>'
	};
	function hasDuration(svc) {
		return $.trim(String(svc.duration || '')) !== '';
	}
	function rowHtml(svc) {
		var name = svc.name || '';
		if (state.confirmDelete === svc.id) {
			return '<div class="mpwpb-csm__row is-confirming" data-csm-row="' + esc(svc.id) + '" role="alertdialog" aria-label="' + esc(t.deleteServiceTitle || 'Delete service?') + '">'
				+ '<span class="mpwpb-csm__confirm-icon" aria-hidden="true">' + ICON.bin + '</span>'
				+ '<span class="mpwpb-csm__confirm-text"><strong>' + esc(String(t.deleteServiceInline || 'Delete “%s”?').replace('%s', name)) + '</strong> ' + esc(t.deleteServiceNote || '') + '</span>'
				+ '<button type="button" class="mpwpb-csm__confirm-btn" data-csm-delete-cancel>' + esc(t.cancel || 'Cancel') + '</button>'
				+ '<button type="button" class="mpwpb-csm__confirm-btn is-danger" data-csm-delete-confirm="' + esc(svc.id) + '">' + esc(t['delete'] || 'Delete') + '</button>'
				+ '</div>';
		}
		var path = pathOfService(svc);
		var crumbs;
		if (path.length) {
			crumbs = path.map(function (p, i) {
				var key = i === 0 ? catKey(p.id) : subKey(p.id);
				return (i > 0 ? CHEV : '') + '<button type="button" class="mpwpb-csm__path" data-csm-select="' + esc(key) + '" title="' + esc(t.showCategory || '') + '">' + esc(p.name) + '</button>';
			}).join('');
		} else {
			crumbs = '<button type="button" class="mpwpb-csm__path is-none" data-csm-select="none">' + esc(t.noCategory) + '</button>';
		}
		// Keyed by the service's own (stable, never-reused) array-index id
		// rather than its position in the current (possibly filtered/
		// searched) list, so a given service always keeps the same swatch.
		var sw = svcSwatch(parseInt(svc.id, 10) || 0);
		var price = svc.price === '' || svc.price === null || svc.price === undefined ? '—' : (cfg.currencySymbol || '') + ' ' + svc.price;
		var dur = hasDuration(svc)
			? '<span class="mpwpb-csm__row-dur">' + ICON.clock + esc(fmtDur(svc.duration)) + '</span>'
			: '<button type="button" class="mpwpb-csm__add-dur" data-csm-edit="' + esc(svc.id) + '">' + ICON.plus + esc(t.addDuration || 'Add duration') + '</button>';
		return '<div class="mpwpb-csm__row' + (state.flash === svc.id ? ' is-new' : '') + '" data-csm-row="' + esc(svc.id) + '">'
			+ '<span class="mpwpb-csm__row-icon" style="background:' + sw.bg + ';color:' + sw.fg + '"><span class="dashicons ' + sw.icon + '"></span></span>'
			+ '<div class="mpwpb-csm__row-main">'
			+ '<button type="button" class="mpwpb-csm__row-name" data-csm-edit="' + esc(svc.id) + '">' + esc(name) + '</button>'
			+ '<div class="mpwpb-csm__row-meta">' + crumbs
			+ (svc.desc ? '<span class="mpwpb-csm__row-dot" aria-hidden="true">·</span><span class="mpwpb-csm__row-desc">' + esc(svc.desc) + '</span>' : '')
			+ '</div>'
			+ '</div>'
			+ '<div class="mpwpb-csm__row-nums">'
			+ '<span class="mpwpb-csm__row-price">' + esc(price) + '</span>' + dur
			+ '</div>'
			+ '<div class="mpwpb-csm__row-acts">'
			+ '<button type="button" class="mpwpb-csm__icon-btn" aria-label="' + esc((t.editTxt || 'Edit') + ' ' + name) + '" title="' + esc(t.editTxt || 'Edit') + '" data-csm-edit="' + esc(svc.id) + '">' + ICON.edit + '</button>'
			+ '<button type="button" class="mpwpb-csm__icon-btn" aria-label="' + esc((t.duplicateTxt || 'Duplicate') + ' ' + name) + '" title="' + esc(t.duplicateTxt || 'Duplicate') + '" data-csm-duplicate="' + esc(svc.id) + '">' + ICON.dup + '</button>'
			+ '<button type="button" class="mpwpb-csm__icon-btn is-danger" aria-label="' + esc((t['delete'] || 'Delete') + ' ' + name) + '" title="' + esc(t['delete'] || 'Delete') + '" data-csm-delete-svc="' + esc(svc.id) + '">' + ICON.bin + '</button>'
			+ '</div>'
			+ '</div>';
	}

	function renderList() {
		var list = visibleServices();
		var html;
		if (!list.length) {
			html = '<div class="mpwpb-csm__empty"><span class="dashicons dashicons-portfolio"></span>'
				+ '<div class="mpwpb-csm__empty-title">' + esc(t.noServicesHere) + '</div>'
				+ '<p class="mpwpb-csm__empty-sub">' + esc(state.query ? t.tryDifferentSearch : t.addServiceHint) + '</p>'
				+ '</div>';
		} else {
			var missing = list.filter(function (s) { return !hasDuration(s); }).length;
			html = (missing ? '<p class="mpwpb-csm__missing-dur">' + ICON.clock + '<span>' + esc(missing === 1 ? (t.missingOne || '1 service has no duration') : String(t.missingMany || '%d services have no duration').replace('%d', missing)) + '</span></p>' : '')
				+ '<div class="mpwpb-csm__list">' + list.map(rowHtml).join('') + '</div>';
		}
		$root.find('[data-csm-part="list"]').html(html);
	}

	function render() {
		renderSidebar();
		renderHeader();
		renderList();
		// Lets the wizard shell's Frontend Preview rail card (mpwpb-service-
		// edit-modern.js) mirror the current service list without polling or
		// re-reading this module's private `state` — bubbles up to #mpwpb-sme.
		// Categories/subCategories ride along too so the preview's own
		// category tree stays in sync with adds/renames/deletes here.
		$root.trigger('mpwpb:csm-services-changed', [state.services, state.categories, state.subCategories]);
	}

	/* ---------------------------------------------------------------- *
	 *  Shell (built once)
	 * ---------------------------------------------------------------- */
	var SEARCH_CLEAR_BTN = '<button type="button" class="mpwpb-csm__search-clear" data-csm-search-clear aria-label="Clear search" hidden>' +
		'<svg width="11" height="11" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2.6" stroke-linecap="round"/></svg></button>';
	$root.html(
		'<div class="mpwpb-csm__sidebar" data-csm-part="sidebar"></div>' +
		'<div class="mpwpb-csm__main">' +
		'<div class="mpwpb-csm__header">' +
		'<div class="mpwpb-csm__crumbs" data-csm-part="crumbs"></div>' +
		'<div class="mpwpb-csm__search"><span class="dashicons dashicons-search"></span><input type="text" data-csm-search placeholder="' + esc(t.searchPlaceholder) + '"/>' + SEARCH_CLEAR_BTN + '</div>' +
		'<button type="button" class="mpwpb-csm__add-btn mpwpb-csm__add-btn--outline" data-csm-use-template><span class="dashicons dashicons-lightbulb"></span>Use Template</button>' +
		'<button type="button" class="mpwpb-csm__add-btn" data-csm-add-svc><span class="dashicons dashicons-plus-alt2"></span>' + esc(t.addService) + '</button>' +
		'</div>' +
		'<div data-csm-part="list"></div>' +
		'</div>'
	);
	render();

	/* ---------------------------------------------------------------- *
	 *  Sidebar / list interactions
	 * ---------------------------------------------------------------- */
	$root.on('click', '[data-csm-select]', function () {
		var key = $(this).data('csm-select').toString();
		state.selected = key;
		// Selecting a category that has subcategories also expands it in the
		// tree, mirroring the chevron's own effect — no separate click needed
		// to see what's inside the one you just picked.
		if (key !== 'all' && key !== 'none' && !keyIsSub(key)) {
			var catId = keyId(key);
			if (childrenOf(catId).length) {
				state.expanded[catId] = true;
			}
		}
		render();
	});
	$root.on('click', '[data-csm-toggle]', function (e) {
		e.stopPropagation();
		var id = $(this).data('csm-toggle').toString();
		state.expanded[id] = !state.expanded[id];
		renderSidebar();
	});
	$root.on('input', '[data-csm-search]', function () {
		state.query = $(this).val();
		$(this).siblings('[data-csm-search-clear]').prop('hidden', !state.query);
		renderHeader();
		renderList();
	});
	$root.on('click', '[data-csm-search-clear]', function () {
		$(this).siblings('[data-csm-search]').val('').trigger('input').trigger('focus');
	});

	/* ---------------------------------------------------------------- *
	 *  Modal plumbing — one at a time, appended to <body> so it's never
	 *  clipped by the wizard's scroll containers.
	 * ---------------------------------------------------------------- */
	function closeModal() {
		$('.mpwpb-csm__overlay').remove();
	}
	function openModal(innerHtml, extraClass) {
		closeModal();
		var $ov = $('<div class="mpwpb-csm__overlay"><div class="mpwpb-csm__modal' + (extraClass ? ' ' + extraClass : '') + '">' + innerHtml + '</div></div>');
		// Appended inside #mpwpb-sme (not <body>) so the modal inherits that
		// shell's CSS custom properties (--brand, --line, --ink, etc.) — it
		// still overlays correctly since .mpwpb-csm__overlay is position:fixed,
		// which is viewport-relative regardless of DOM nesting depth.
		$('#mpwpb-sme').append($ov);
		$ov.on('mousedown', function (e) { if (e.target === this) { closeModal(); } });
		return $ov;
	}
	$(document).on('click', '[data-csm-modal-close]', closeModal);
	$(document).on('keydown', function (e) { if (e.key === 'Escape') { closeModal(); } });

	/* ---------------------------------------------------------------- *
	 *  Add Category / Add Subcategory modal — the "Nest under" picker lets
	 *  the admin change the parent interactively (defaulting to whichever
	 *  category's "Add subcategory" button was clicked, if any), rather
	 *  than the parent being fixed for the life of the modal.
	 * ---------------------------------------------------------------- */
	function categoryParentPickHtml(selectedParentId) {
		var html = '<button type="button" class="mpwpb-csm__pick-row' + (!selectedParentId ? ' is-active' : '') + '" data-csm-cat-parent-pick="">'
			+ '<span class="dashicons dashicons-layout"></span><span>Top-level category</span>'
			+ (!selectedParentId ? '<span class="mpwpb-csm__pick-check dashicons dashicons-yes"></span>' : '') + '</button>';
		state.categories.forEach(function (c) {
			var active = selectedParentId === c.id;
			html += '<button type="button" class="mpwpb-csm__pick-row' + (active ? ' is-active' : '') + '" data-csm-cat-parent-pick="' + esc(c.id) + '">'
				+ '<span class="dashicons dashicons-category"></span><span>' + esc(c.name) + '</span>'
				+ (active ? '<span class="mpwpb-csm__pick-check dashicons dashicons-yes"></span>' : '') + '</button>';
		});
		return html;
	}
	function openCategoryModal(initialParentId) {
		var pickedParentId = initialParentId || '';
		function titleText() {
			var p = pickedParentId ? findCat(pickedParentId) : null;
			return p ? (t.addSubcategory + ' in "' + p.name + '"') : t.addCategory;
		}
		var $ov = openModal(
			'<div class="mpwpb-csm__modal-head"><span class="mpwpb-csm__modal-title" data-csm-cat-title>' + esc(titleText()) + '</span>'
			+ '<button type="button" class="mpwpb-csm__modal-close" data-csm-modal-close><span class="dashicons dashicons-no-alt"></span></button></div>'
			+ '<div class="mpwpb-csm__modal-body">'
			+ '<div class="mpwpb-csm__field-error" style="display:none" data-csm-cat-error></div>'
			+ '<label class="mpwpb-csm__field"><span class="mpwpb-csm__field-label">Nest under</span>'
			+ '<div class="mpwpb-csm__pick-list" data-csm-cat-parent-list>' + categoryParentPickHtml(pickedParentId) + '</div>'
			+ '<span class="mpwpb-csm__field-hint">Choose a parent to make this a subcategory, or keep it top-level.</span>'
			+ '</label>'
			+ '<label class="mpwpb-csm__field"><span class="mpwpb-csm__field-label">Category name</span>'
			+ '<input type="text" autofocus data-csm-cat-name placeholder="' + esc(pickedParentId ? 'e.g. SUV' : 'e.g. Car Detailing') + '"/></label>'
			+ '</div>'
			+ '<div class="mpwpb-csm__modal-foot">'
			+ '<button type="button" class="mpwpb-csm__modal-btn" data-csm-modal-close>Cancel</button>'
			+ '<button type="button" class="mpwpb-csm__modal-btn mpwpb-csm__modal-btn--primary" disabled data-csm-cat-save>Create category</button>'
			+ '</div>'
		);
		var $input = $ov.find('[data-csm-cat-name]');
		var $save = $ov.find('[data-csm-cat-save]');
		$input.on('input', function () { $save.prop('disabled', !$(this).val().trim()); }).focus();
		$ov.on('click', '[data-csm-cat-parent-pick]', function () {
			pickedParentId = $(this).data('csm-cat-parent-pick').toString();
			$ov.find('[data-csm-cat-parent-list]').html(categoryParentPickHtml(pickedParentId));
			$ov.find('[data-csm-cat-title]').text(titleText());
			$input.attr('placeholder', pickedParentId ? 'e.g. SUV' : 'e.g. Car Detailing');
		});
		function submit() {
			var name = $input.val().trim();
			if (!name) { return; }
			$save.prop('disabled', true);
			var data = { category_postID: cfg.postId, category_name: name };
			if (pickedParentId) {
				data.use_sub_category = 'on';
				data.parent_category = pickedParentId;
			}
			post('mpwpb_save_category_service', data, function () {
				if (pickedParentId) {
					var newSub = { id: nextId(state.subCategories), name: name, catId: pickedParentId };
					state.subCategories.push(newSub);
					state.expanded[pickedParentId] = true;
				} else {
					state.categories.push({ id: nextId(state.categories), name: name });
				}
				closeModal();
				renderSidebar();
			}, function (data2) {
				$save.prop('disabled', false);
				$ov.find('[data-csm-cat-error]').text(data2.message || 'Something went wrong.').show();
			});
		}
		$ov.find('[data-csm-cat-save]').on('click', submit);
		$input.on('keydown', function (e) { if (e.key === 'Enter') { submit(); } });
	}
	$root.on('click', '[data-csm-add-cat]', function () { openCategoryModal(null); });
	$root.on('click', '[data-csm-add-sub]', function (e) {
		e.stopPropagation();
		openCategoryModal($(this).data('csm-add-sub').toString());
	});

	/* ---------------------------------------------------------------- *
	 *  Confirm delete (category / subcategory / service)
	 * ---------------------------------------------------------------- */
	function openConfirmDelete(kind, id) {
		var isCat = kind === 'category' || kind === 'subcategory';
		var name = isCat ? ((findCat(id) || findSub(id) || {}).name) : ((state.services.filter(function (s) { return s.id === id; })[0] || {}).name);
		var body = isCat
			? '“' + esc(name) + '” and its subcategories will be removed. Services inside will move to <strong>Uncategorized</strong> — they won’t be deleted.'
			: '“' + esc(name) + '” will be permanently removed.';
		var $ov = openModal(
			'<div class="mpwpb-csm__modal-body" style="padding-top:20px;">'
			+ '<span class="mpwpb-csm__modal-title">' + esc(isCat ? t.deleteCategoryTitle : t.deleteServiceTitle) + '</span>'
			+ '<p>' + body + '</p></div>'
			+ '<div class="mpwpb-csm__modal-foot">'
			+ '<button type="button" class="mpwpb-csm__modal-btn" data-csm-modal-close>Cancel</button>'
			+ '<button type="button" class="mpwpb-csm__modal-btn mpwpb-csm__modal-btn--danger" data-csm-confirm-delete>Delete</button>'
			+ '</div>'
		);
		$ov.find('[data-csm-confirm-delete]').on('click', function () {
			var $btn = $(this).prop('disabled', true);
			if (kind === 'service') {
				post('mpwpb_service_delete_item', { service_postID: cfg.postId, itemId: id }, function () {
					state.services = state.services.filter(function (s) { return s.id !== id; });
					closeModal();
					render();
				}, function () { $btn.prop('disabled', false); });
			} else if (kind === 'subcategory') {
				post('mpwpb_sub_category_delete', { category_postID: cfg.postId, itemId: id }, function () {
					applyLocalDeleteCategoryOrSub(false, id);
					closeModal();
					render();
				}, function () { $btn.prop('disabled', false); });
			} else {
				post('mpwpb_category_service_delete_item', { category_postID: cfg.postId, itemId: id }, function () {
					applyLocalDeleteCategoryOrSub(true, id);
					closeModal();
					render();
				}, function () { $btn.prop('disabled', false); });
			}
		});
	}
	// Mirrors the server-side cascade added in Category.php: clear affected
	// services' category fields locally too, so the UI matches storage
	// without waiting on a full reload.
	function applyLocalDeleteCategoryOrSub(isTopCategory, deletedId) {
		var deletedSubIds = [];
		if (isTopCategory) {
			deletedSubIds = state.subCategories.filter(function (s) { return s.catId === deletedId; }).map(function (s) { return s.id; });
			state.subCategories = state.subCategories.filter(function (s) { return s.catId !== deletedId; });
			state.categories = state.categories.filter(function (c) { return c.id !== deletedId; });
			var selectedIsDeletedSub = keyIsSub(state.selected) && deletedSubIds.indexOf(keyId(state.selected)) > -1;
			if (state.selected === catKey(deletedId) || selectedIsDeletedSub) { state.selected = 'all'; }
		} else {
			deletedSubIds = [deletedId];
			state.subCategories = state.subCategories.filter(function (s) { return s.id !== deletedId; });
			if (state.selected === subKey(deletedId)) { state.selected = 'all'; }
		}
		state.services = state.services.map(function (s) {
			if (s.parentCat === deletedId || deletedSubIds.indexOf(s.subCat) > -1) {
				return $.extend({}, s, { parentCat: '', subCat: '' });
			}
			return s;
		});
	}
	$root.on('click', '[data-csm-delete-cat]', function (e) {
		e.stopPropagation();
		var isSub = $(this).data('csm-is-sub') == '1';
		openConfirmDelete(isSub ? 'subcategory' : 'category', $(this).data('csm-delete-cat').toString());
	});
	// Services are confirmed inside their own row (categories keep the modal,
	// since their message needs more room).
	$root.on('click', '[data-csm-delete-svc]', function () {
		state.confirmDelete = $(this).data('csm-delete-svc').toString();
		renderList();
		$root.find('[data-csm-delete-cancel]').trigger('focus');
	});
	$root.on('click', '[data-csm-delete-cancel]', function () {
		var id = state.confirmDelete;
		state.confirmDelete = null;
		renderList();
		$root.find('[data-csm-row="' + id + '"] [data-csm-delete-svc]').trigger('focus');
	});
	$root.on('click', '[data-csm-delete-confirm]', function () {
		var id = $(this).data('csm-delete-confirm').toString();
		var $btns = $(this).closest('.mpwpb-csm__row').find('button').prop('disabled', true);
		post('mpwpb_service_delete_item', { service_postID: cfg.postId, itemId: id }, function () {
			state.services = state.services.filter(function (s) { return s.id !== id; });
			state.confirmDelete = null;
			render();
		}, function () { $btns.prop('disabled', false); });
	});
	$root.on('keydown', '.mpwpb-csm__row.is-confirming', function (e) {
		if (e.key === 'Escape') { $(this).find('[data-csm-delete-cancel]').trigger('click'); }
	});

	/* ---------------------------------------------------------------- *
	 *  Edit Category / Subcategory (rename only — reparenting a subcategory
	 *  is intentionally out of scope here to avoid accidentally orphaning
	 *  it: mpwpb_update_sub_category clears cat_id to '' if it's not sent,
	 *  so the existing parent is always resent unchanged below).
	 * ---------------------------------------------------------------- */
	function openEditCategoryModal(id, isSub) {
		var item = isSub ? findSub(id) : findCat(id);
		if (!item) { return; }
		var $ov = openModal(
			'<div class="mpwpb-csm__modal-head"><span class="mpwpb-csm__modal-title">Rename ' + esc(isSub ? 'subcategory' : 'category') + '</span>'
			+ '<button type="button" class="mpwpb-csm__modal-close" data-csm-modal-close><span class="dashicons dashicons-no-alt"></span></button></div>'
			+ '<div class="mpwpb-csm__modal-body">'
			+ '<div class="mpwpb-csm__field-error" style="display:none" data-csm-cat-error></div>'
			+ '<label class="mpwpb-csm__field"><span class="mpwpb-csm__field-label">Category name</span>'
			+ '<input type="text" autofocus data-csm-cat-name value="' + esc(item.name) + '"/></label>'
			+ '</div>'
			+ '<div class="mpwpb-csm__modal-foot">'
			+ '<button type="button" class="mpwpb-csm__modal-btn" data-csm-modal-close>Cancel</button>'
			+ '<button type="button" class="mpwpb-csm__modal-btn mpwpb-csm__modal-btn--primary" data-csm-cat-save>Save changes</button>'
			+ '</div>'
		);
		var $input = $ov.find('[data-csm-cat-name]');
		var $save = $ov.find('[data-csm-cat-save]');
		$input.on('input', function () { $save.prop('disabled', !$(this).val().trim()); }).focus().select();
		function submit() {
			var name = $input.val().trim();
			if (!name) { return; }
			$save.prop('disabled', true);
			var data = { category_postID: cfg.postId, category_name: name, category_itemId: id };
			if (isSub) { data.category_parentId = item.catId; }
			post(isSub ? 'mpwpb_update_sub_category' : 'mpwpb_update_category_service', data, function () {
				if (isSub) {
					state.subCategories = state.subCategories.map(function (s) { return s.id === id ? $.extend({}, s, { name: name }) : s; });
				} else {
					state.categories = state.categories.map(function (c) { return c.id === id ? $.extend({}, c, { name: name }) : c; });
				}
				closeModal();
				renderSidebar();
			}, function (data2) {
				$save.prop('disabled', false);
				$ov.find('[data-csm-cat-error]').text(data2.message || 'Something went wrong.').show();
			});
		}
		$save.on('click', submit);
		$input.on('keydown', function (e) { if (e.key === 'Enter') { submit(); } });
	}
	$root.on('click', '[data-csm-edit-cat]', function (e) {
		e.stopPropagation();
		var isSub = $(this).data('csm-is-sub') == '1';
		openEditCategoryModal($(this).data('csm-edit-cat').toString(), isSub);
	});

	/* ---------------------------------------------------------------- *
	 *  Add/Edit Service modal
	 * ---------------------------------------------------------------- */
	function categoryPickHtml(selectedKey) {
		var html = '<button type="button" class="mpwpb-csm__pick-row' + (!selectedKey ? ' is-active' : '') + '" data-csm-pick="">'
			+ '<span class="dashicons dashicons-archive"></span><span class="mpwpb-csm__pick-name">' + esc(t.noCategory) + '</span>'
			+ (!selectedKey ? '<span class="mpwpb-csm__pick-check dashicons dashicons-yes"></span>' : '') + '</button>';
		state.categories.forEach(function (c) {
			var key = catKey(c.id);
			var active = selectedKey === key;
			html += '<button type="button" class="mpwpb-csm__pick-row' + (active ? ' is-active' : '') + '" data-csm-pick="' + esc(key) + '">'
				+ '<span class="dashicons dashicons-category"></span><span class="mpwpb-csm__pick-name mpwpb-csm__pick-name--top">' + esc(c.name) + '</span>'
				+ (active ? '<span class="mpwpb-csm__pick-check dashicons dashicons-yes"></span>' : '') + '</button>';
			childrenOf(c.id).forEach(function (sc) {
				var subKeyVal = subKey(sc.id);
				var subActive = selectedKey === subKeyVal;
				html += '<button type="button" class="mpwpb-csm__pick-row mpwpb-csm__pick-row--sub' + (subActive ? ' is-active' : '') + '" data-csm-pick="' + esc(subKeyVal) + '">'
					+ '<span class="mpwpb-csm__pick-corner" aria-hidden="true"></span><span class="mpwpb-csm__pick-name">' + esc(sc.name) + '</span>'
					+ (subActive ? '<span class="mpwpb-csm__pick-check dashicons dashicons-yes"></span>' : '') + '</button>';
			});
		});
		return html;
	}

	function openServiceModal(svc) {
		var isNew = !svc.id;
		var picked = svc.subCat ? subKey(svc.subCat) : (svc.parentCat ? catKey(svc.parentCat) : '');
		var $ov = openModal(
			'<div class="mpwpb-csm__modal-head"><span class="mpwpb-csm__modal-title">' + esc(isNew ? t.addService : t.editService) + '</span>'
			+ '<button type="button" class="mpwpb-csm__modal-close" data-csm-modal-close><span class="dashicons dashicons-no-alt"></span></button></div>'
			+ '<div class="mpwpb-csm__modal-body">'
			+ '<div class="mpwpb-csm__field-error" style="display:none" data-csm-svc-error></div>'
			+ '<label class="mpwpb-csm__field"><span class="mpwpb-csm__field-label">Service name</span>'
			+ '<input type="text" autofocus data-csm-svc-name value="' + esc(svc.name) + '" placeholder="e.g. Hand Wash + Wax"/></label>'
			+ '<label class="mpwpb-csm__field"><span class="mpwpb-csm__field-label">Description <span class="mpwpb-csm__field-optional">(optional)</span></span>'
			+ '<textarea rows="2" data-csm-svc-desc placeholder="What’s included in this service?">' + esc(svc.desc) + '</textarea></label>'
			+ '<div class="mpwpb-csm__grid2">'
			+ '<label class="mpwpb-csm__field"><span class="mpwpb-csm__field-label">Price (' + esc(cfg.currencySymbol || '$') + ')</span>'
			+ '<input type="number" min="0" data-csm-svc-price value="' + esc(svc.price) + '" placeholder="450"/></label>'
			+ '<label class="mpwpb-csm__field"><span class="mpwpb-csm__field-label">Duration</span>'
			+ '<input type="text" data-csm-svc-duration value="' + esc(svc.duration) + '" placeholder="10min"/></label>'
			+ '</div>'
			+ '<label class="mpwpb-csm__field"><span class="mpwpb-csm__field-label">Category</span>'
			+ '<div class="mpwpb-csm__pick-list" data-csm-pick-list>' + categoryPickHtml(picked) + '</div>'
			+ '<span class="mpwpb-csm__field-hint">Optional — services can live without a category.</span>'
			+ '</label>'
			+ '</div>'
			+ '<div class="mpwpb-csm__modal-foot">'
			+ '<button type="button" class="mpwpb-csm__modal-btn" data-csm-modal-close>Cancel</button>'
			+ '<button type="button" class="mpwpb-csm__modal-btn mpwpb-csm__modal-btn--primary" data-csm-svc-save>' + esc(isNew ? t.addService : 'Save changes') + '</button>'
			+ '</div>'
		);
		var pickedId = picked;
		$ov.on('click', '[data-csm-pick]', function () {
			pickedId = $(this).data('csm-pick').toString();
			$ov.find('[data-csm-pick-list]').html(categoryPickHtml(pickedId));
		});

		function fieldValidity() {
			var name = $ov.find('[data-csm-svc-name]').val().trim();
			var price = $ov.find('[data-csm-svc-price]').val();
			var duration = $ov.find('[data-csm-svc-duration]').val().trim();
			return {
				name: !!name,
				price: price !== '' && Number(price) >= 0,
				duration: !!duration
			};
		}
		// Required-field highlighting: rather than silently disabling Save
		// (which gives no clue WHICH field is the problem), Save stays
		// clickable and, on click, invalid fields get a red border + focus
		// goes to the first one -- clears again as soon as that field is
		// fixed.
		function highlightInvalid() {
			var v = fieldValidity();
			$ov.find('[data-csm-svc-name]').toggleClass('mpwpb-csm__field-invalid', !v.name);
			$ov.find('[data-csm-svc-price]').toggleClass('mpwpb-csm__field-invalid', !v.price);
			$ov.find('[data-csm-svc-duration]').toggleClass('mpwpb-csm__field-invalid', !v.duration);
			return v;
		}
		$ov.on('input', '[data-csm-svc-name],[data-csm-svc-price],[data-csm-svc-duration]', function () {
			$(this).removeClass('mpwpb-csm__field-invalid');
		});

		$ov.find('[data-csm-svc-save]').on('click', function () {
			var v = highlightInvalid();
			if (!v.name || !v.price || !v.duration) {
				$ov.find('[data-csm-svc-error]').text('Please fill in the required fields.').show();
				$ov.find('.mpwpb-csm__field-invalid').first().trigger('focus');
				return;
			}
			var $btn = $(this).prop('disabled', true);
			var name = $ov.find('[data-csm-svc-name]').val().trim();
			var desc = $ov.find('[data-csm-svc-desc]').val();
			var price = $ov.find('[data-csm-svc-price]').val();
			var duration = $ov.find('[data-csm-svc-duration]').val();
			var data = {
				service_postID: cfg.postId,
				service_name: name,
				service_description: desc,
				service_price: price,
				service_duration: duration,
				service_unit: svc.unit || ''
			};
			if (pickedId) {
				data.service_category_status = 'on';
				if (keyIsSub(pickedId)) {
					var subId = keyId(pickedId);
					var sub = findSub(subId);
					data.service_parent_cat = sub ? sub.catId : '';
					data.service_sub_cat = subId;
				} else {
					data.service_parent_cat = keyId(pickedId);
					data.service_sub_cat = '';
				}
			}
			function onFail(data2) {
				$btn.prop('disabled', false);
				$ov.find('[data-csm-svc-error]').text(data2.message || 'Something went wrong.').show();
			}
			var localRow = {
				id: isNew ? nextId(state.services) : svc.id,
				name: name,
				desc: desc,
				price: price,
				unit: svc.unit || '',
				duration: duration,
				catStatus: pickedId ? 'on' : '',
				parentCat: data.service_parent_cat || '',
				subCat: data.service_sub_cat || ''
			};
			if (isNew) {
				post('mpwpb_save_service', data, function () {
					state.services.push(localRow);
					closeModal();
					render();
				}, onFail);
			} else {
				data.service_itemId = svc.id;
				post('mpwpb_service_update', data, function () {
					state.services = state.services.map(function (s) { return s.id === svc.id ? localRow : s; });
					closeModal();
					render();
				}, onFail);
			}
		});
	}
	$root.on('click', '[data-csm-add-svc]', function () {
		var preset = (state.selected !== 'all' && state.selected !== 'none') ? state.selected : '';
		var presetIsSub = preset && keyIsSub(preset);
		var presetId = preset ? keyId(preset) : '';
		var presetSub = presetIsSub ? findSub(presetId) : null;
		openServiceModal({
			id: '', name: '', desc: '', price: '', unit: '', duration: '',
			catStatus: preset ? 'on' : '',
			parentCat: presetIsSub ? (presetSub ? presetSub.catId : '') : presetId,
			subCat: presetIsSub ? presetId : ''
		});
	});
	$root.on('click', '[data-csm-edit]', function () {
		var id = $(this).data('csm-edit').toString();
		var svc = state.services.filter(function (s) { return s.id === id; })[0];
		if (svc) { openServiceModal($.extend({}, svc)); }
	});
	$root.on('click', '[data-csm-duplicate]', function () {
		var id = $(this).data('csm-duplicate').toString();
		var svc = state.services.filter(function (s) { return s.id === id; })[0];
		if (!svc) { return; }
		var data = {
			service_postID: cfg.postId,
			service_name: svc.name + ' (copy)',
			service_description: svc.desc,
			service_price: svc.price,
			service_duration: svc.duration,
			service_unit: svc.unit || ''
		};
		if (svc.parentCat) {
			data.service_category_status = 'on';
			data.service_parent_cat = svc.parentCat;
			data.service_sub_cat = svc.subCat || '';
		}
		post('mpwpb_save_service', data, function () {
			var copy = $.extend({}, svc, { id: nextId(state.services), name: svc.name + ' (copy)' });
			state.services.push(copy);
			// Briefly highlight the copy and bring it into view.
			state.flash = copy.id;
			render();
			var $new = $root.find('[data-csm-row="' + copy.id + '"]');
			if ($new.length && $new[0].scrollIntoView) { $new[0].scrollIntoView({ block: 'nearest', behavior: 'smooth' }); }
			setTimeout(function () {
				if (state.flash === copy.id) { state.flash = null; $root.find('[data-csm-row="' + copy.id + '"]').removeClass('is-new'); }
			}, 1800);
		});
	});

	/* ---------------------------------------------------------------- *
	 *  "Use Template" picker — quick-add ready-made services from
	 *  window.mpwpbServiceTemplates (mpwpb-service-templates-data.js).
	 *  Two panels in the same modal: pick a business type, then check off
	 *  which of its suggested services to insert. Each checked item is
	 *  saved through the exact same mpwpb_save_service endpoint the manual
	 *  Add Service modal uses (one POST per item, sent in sequence so ids
	 *  assigned by nextId() don't collide) -- price/duration/description
	 *  are only starting points, fully editable afterwards like any other
	 *  service.
	 * ---------------------------------------------------------------- */
	var TEMPLATES = window.mpwpbServiceTemplates || {};
	// tpl.type is the currently-selected business type (a key into
	// TEMPLATES) -- kept distinct from each template's own optional
	// `.category` field (the real category name to auto-create), which is
	// a different concept.
	var tpl = { type: null, checked: {} };

	// Same "which category/subcategory is this new service pre-assigned
	// to" rule the manual Add Service button already uses (whatever's
	// currently selected in the sidebar, if any). Used as the fallback for
	// templates that don't define their own `.category`.
	function currentCategoryPreset() {
		var preset = (state.selected !== 'all' && state.selected !== 'none') ? state.selected : '';
		var presetIsSub = preset && keyIsSub(preset);
		var presetId = preset ? keyId(preset) : '';
		var presetSub = presetIsSub ? findSub(presetId) : null;
		var parentCat = presetIsSub ? (presetSub ? presetSub.catId : '') : presetId;
		var subCat = presetIsSub ? presetId : '';
		return {
			service_category_status: preset ? 'on' : '',
			service_parent_cat: parentCat,
			service_sub_cat: subCat
		};
	}
	function findCategoryByName(name) {
		var lower = name.toLowerCase();
		return state.categories.filter(function (c) { return c.name.toLowerCase() === lower; })[0];
	}
	// Reuses an existing top-level category with this exact name if one
	// already exists (so re-using a template twice doesn't create
	// duplicates), otherwise creates it via the same endpoint/shape
	// openCategoryModal()'s own save uses.
	function ensureCategory(name, done) {
		var existing = findCategoryByName(name);
		if (existing) { done(existing.id); return; }
		post('mpwpb_save_category_service', { category_postID: cfg.postId, category_name: name }, function () {
			var newCat = { id: nextId(state.categories), name: name };
			state.categories.push(newCat);
			done(newCat.id);
		}, function () {
			// Category creation failed -- fall back to no category rather
			// than blocking the whole batch of services.
			done('');
		});
	}

	// Groups for the business-type chips. Any template not listed here
	// (e.g. one added to mpwpb-service-templates-data.js later) lands in
	// "Other", which only shows up when something is actually in it.
	var TPL_GROUPS = [
		{ id: 'auto', label: 'Automotive', types: ['Car Wash & Auto Detailing', 'Auto Repair & Vehicle Maintenance', 'Car / Vehicle Rental'] },
		{ id: 'beauty', label: 'Beauty & Wellness', types: ['Hair Salon & Barbershop', 'Spa, Massage & Beauty Treatments', 'Nail Salon', 'Fitness Training & Gym Sessions', 'Yoga & Wellness Classes'] },
		{ id: 'health', label: 'Health', types: ['Medical & Dental Clinics', 'Physiotherapy & Chiropractic'] },
		{ id: 'home', label: 'Home & Pets', types: ['Home Cleaning Services', 'Pest Control Services', 'Appliance & Electronics Repair', 'Plumbing, Electrical & Handyman Services', 'Pool & Spa Maintenance Services', 'Pet Grooming & Pet Sitting'] },
		{ id: 'learning', label: 'Learning', types: ['Tutoring & Private Lessons', 'Music Lessons & Instrument Classes', 'Driving Lessons'] },
		{ id: 'events', label: 'Events & Hospitality', types: ['Photography & Videography Sessions', 'Event Planning & Equipment Rental', 'Restaurant Table Reservations', 'Tour & Activity Booking'] },
		{ id: 'business', label: 'Business & Spaces', types: ['Consulting (Legal, Financial, Business)', 'Co-working Space / Room Booking', 'Recurring Subscription-Based Services'] }
	];
	function templateGroupOf(name) {
		for (var i = 0; i < TPL_GROUPS.length; i++) {
			if (TPL_GROUPS[i].types.indexOf(name) !== -1) { return TPL_GROUPS[i].id; }
		}
		return 'other';
	}
	// Search text + selected group chip on the business-type panel.
	var tplFilter = { group: 'all', query: '' };

	function templateToolbarHtml() {
		var names = Object.keys(TEMPLATES);
		var groups = TPL_GROUPS.filter(function (g) {
			return names.some(function (n) { return templateGroupOf(n) === g.id; });
		});
		if (names.some(function (n) { return templateGroupOf(n) === 'other'; })) {
			groups = groups.concat([{ id: 'other', label: 'Other' }]);
		}
		var chips = [{ id: 'all', label: 'All' }].concat(groups).map(function (g) {
			var on = tplFilter.group === g.id;
			return '<button type="button" class="mpwpb-csm__tpl-chip' + (on ? ' is-active' : '') + '" data-tpl-group="' + esc(g.id) + '" data-tpl-group-label="' + esc(g.label) + '" aria-pressed="' + (on ? 'true' : 'false') + '">' + esc(g.label) + '</button>';
		}).join('');
		return '<div class="mpwpb-csm__tpl-toolbar">'
			+ '<label class="mpwpb-csm__tpl-search"><span class="dashicons dashicons-search" aria-hidden="true"></span>'
			+ '<input type="text" data-tpl-search value="' + esc(tplFilter.query) + '" placeholder="Search business types" aria-label="Search business types"/></label>'
			+ '<div class="mpwpb-csm__tpl-chips" role="group" aria-label="Filter by group">' + chips + '</div>'
			+ '</div>';
	}

	function templateCategoryListHtml() {
		var names = Object.keys(TEMPLATES);
		if (!names.length) {
			return '<p class="mpwpb-csm__tpl-empty">No templates available.</p>';
		}
		return '<div class="mpwpb-csm__tpl-list-head"><span data-tpl-list-title></span><span data-tpl-list-count></span></div>'
			+ '<div class="mpwpb-csm__tpl-cat-grid">' + names.map(function (name) {
			var def = TEMPLATES[name];
			var count = (def.items || []).length;
			return '<button type="button" class="mpwpb-csm__tpl-cat-btn" data-tpl-cat="' + esc(name) + '" data-tpl-cat-group="' + esc(templateGroupOf(name)) + '">'
				+ '<span class="mpwpb-csm__tpl-cat-icon"><span class="dashicons ' + esc(def.icon || 'dashicons-portfolio') + '"></span></span>'
				+ '<span class="mpwpb-csm__tpl-cat-text">'
				+ '<span class="mpwpb-csm__tpl-cat-name">' + esc(name) + '</span>'
				+ '<span class="mpwpb-csm__tpl-cat-count">' + count + (count === 1 ? ' service' : ' services') + '</span>'
				+ '</span>'
				+ '<span class="mpwpb-csm__tpl-cat-arrow dashicons dashicons-arrow-right-alt2" aria-hidden="true"></span>'
				+ '</button>';
		}).join('') + '</div>'
			+ '<p class="mpwpb-csm__tpl-empty" data-tpl-no-match hidden></p>';
	}

	// Filters the already-rendered cards in place (no re-render, so the
	// search box keeps focus while typing).
	function applyTemplateFilter($ov) {
		var q = $.trim(tplFilter.query || '').toLowerCase();
		var shown = 0;
		$ov.find('[data-tpl-cat]').each(function () {
			var $card = $(this);
			var name = $card.data('tpl-cat').toString().toLowerCase();
			var match = (tplFilter.group === 'all' || $card.data('tpl-cat-group') === tplFilter.group) && (!q || name.indexOf(q) !== -1);
			$card.prop('hidden', !match);
			if (match) { shown++; }
		});
		$ov.find('[data-tpl-group]').each(function () {
			var on = $(this).data('tpl-group') === tplFilter.group;
			$(this).toggleClass('is-active', on).attr('aria-pressed', on ? 'true' : 'false');
		});
		var groupLabel = $ov.find('[data-tpl-group="' + tplFilter.group + '"]').data('tpl-group-label');
		$ov.find('[data-tpl-list-title]').text(q ? 'Search results' : (tplFilter.group === 'all' ? 'All business types' : groupLabel));
		$ov.find('[data-tpl-list-count]').text(shown + (shown === 1 ? ' type' : ' types'));
		$ov.find('[data-tpl-no-match]').prop('hidden', shown > 0)
			.text('No business type matches "' + $.trim(tplFilter.query) + '". Try a shorter word, or pick another group.');
	}

	function templateItemListHtml() {
		var def = TEMPLATES[tpl.type] || { items: [] };
		var items = def.items || [];
		var checkedCount = items.filter(function (item, i) { return !!tpl.checked[i]; }).length;
		var rows = items.map(function (item, i) {
			var checked = !!tpl.checked[i];
			return '<label class="mpwpb-csm__tpl-item' + (checked ? ' is-checked' : '') + '">'
				+ '<input type="checkbox" data-tpl-item="' + i + '"' + (checked ? ' checked' : '') + '/>'
				+ '<span class="mpwpb-csm__tpl-item-main">'
				+ '<span class="mpwpb-csm__tpl-item-name">' + esc(item.name) + '</span>'
				+ '<span class="mpwpb-csm__tpl-item-desc">' + esc(item.desc) + '</span>'
				+ '</span>'
				+ '<span class="mpwpb-csm__tpl-item-meta">'
				+ '<span class="mpwpb-csm__tpl-item-price">' + esc(cfg.currencySymbol || '$') + esc(item.price) + '</span>'
				+ '<span class="mpwpb-csm__tpl-item-dur"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>' + esc(item.duration) + '</span>'
				+ '</span>'
				+ '</label>';
		}).join('');
		var catNote = def.category
			? '<p class="mpwpb-csm__tpl-cat-note"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg><span>Added services go into a new <strong>' + esc(def.category) + '</strong> category.</span></p>'
			: '';
		return catNote
			+ '<div class="mpwpb-csm__tpl-selectbar">'
			+ '<label class="mpwpb-csm__tpl-selectall"><input type="checkbox" data-tpl-select-all' + (checkedCount === items.length && items.length ? ' checked' : '') + '/>Select all</label>'
			+ '<span class="mpwpb-csm__tpl-selected-count">' + checkedCount + ' of ' + items.length + ' selected</span>'
			+ '</div>'
			+ '<div class="mpwpb-csm__tpl-item-list">' + rows + '</div>';
	}

	// "Adds ৳ 75 worth of services · 1h 15min total" for the picked items.
	function templateSelectionSummary() {
		var items = (TEMPLATES[tpl.type] || {}).items || [];
		var total = 0, mins = 0, count = 0;
		items.forEach(function (item, i) {
			if (!tpl.checked[i]) { return; }
			count++;
			total += parseFloat(String(item.price).replace(/[^0-9.]/g, '')) || 0;
			var d = String(item.duration || '');
			var h = d.match(/(\d+(?:\.\d+)?)\s*h/i), m = d.match(/(\d+)\s*m/i);
			mins += (h ? parseFloat(h[1]) * 60 : 0) + (m ? parseInt(m[1], 10) : 0);
		});
		if (!count) { return 'Nothing selected yet'; }
		var hh = Math.floor(mins / 60), mm = Math.round(mins % 60);
		var dur = (hh ? hh + 'h' : '') + (hh && mm ? ' ' : '') + (mm ? mm + 'min' : '');
		var money = (cfg.currencySymbol || '$') + (Math.round(total * 100) / 100);
		return 'Adds ' + money + ' worth of services' + (dur ? ' · ' + dur + ' total' : '');
	}

	function renderTemplateModal() {
		var onTypePanel = !tpl.type;
		var head = onTypePanel
			? ('<span class="mpwpb-csm__tpl-head-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M9 18h6M10 21h4M12 3a6 6 0 0 0-3.6 10.8c.6.5 1 1.2 1.1 2h5c.1-.8.5-1.5 1.1-2A6 6 0 0 0 12 3z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>'
				+ '<span class="mpwpb-csm__tpl-head-text"><span class="mpwpb-csm__modal-title">Start from a template</span>'
				+ '<span class="mpwpb-csm__tpl-head-sub">Pick a business type to see ready-made services you can add in one click, then adjust the name, price, or duration as needed.</span></span>')
			: ('<button type="button" class="mpwpb-csm__tpl-back" data-tpl-back aria-label="Back to templates"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 18l-6-6 6-6"/></svg></button>'
				+ '<span class="mpwpb-csm__tpl-head-icon" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M3 12h18"/></svg></span>'
				+ '<span class="mpwpb-csm__tpl-head-text"><span class="mpwpb-csm__modal-title">' + esc(tpl.type) + '</span>'
				+ '<span class="mpwpb-csm__tpl-head-sub">Pick the services to add. You can edit prices and times after.</span></span>');
		var body = onTypePanel ? templateCategoryListHtml() : templateItemListHtml();
		var toolbar = onTypePanel && Object.keys(TEMPLATES).length ? templateToolbarHtml() : '';
		var checkedCount = onTypePanel ? 0 : Object.keys(tpl.checked).filter(function (i) { return tpl.checked[i]; }).length;
		var foot = onTypePanel ? '' :
			('<div class="mpwpb-csm__modal-foot mpwpb-csm__tpl-foot">'
				+ '<span class="mpwpb-csm__tpl-summary">' + esc(templateSelectionSummary()) + '</span>'
				+ '<button type="button" class="mpwpb-csm__modal-btn" data-csm-modal-close>Cancel</button>'
				+ '<button type="button" class="mpwpb-csm__modal-btn mpwpb-csm__modal-btn--primary" data-tpl-add' + (checkedCount ? '' : ' disabled') + '>'
				+ (checkedCount ? 'Add ' + checkedCount + (checkedCount === 1 ? ' service' : ' services') : 'Add services') + '</button>'
				+ '</div>');
		var $ov = openModal(
			'<div class="mpwpb-csm__modal-head' + (onTypePanel ? ' mpwpb-csm__tpl-head' : '') + '">' + head
			+ '<button type="button" class="mpwpb-csm__modal-close" data-csm-modal-close><span class="dashicons dashicons-no-alt"></span></button></div>'
			+ toolbar
			+ '<div class="mpwpb-csm__modal-body mpwpb-csm__tpl-body' + (onTypePanel ? ' mpwpb-csm__tpl-body--types' : '') + '">' + body + '</div>'
			+ foot,
			'mpwpb-csm__modal--wide' + (onTypePanel ? '' : ' mpwpb-csm__modal--tpl-items')
		);
		// "Select all" shows a dash while only some services are picked.
		var tplItems = onTypePanel ? [] : ((TEMPLATES[tpl.type] || {}).items || []);
		$ov.find('[data-tpl-select-all]').prop('indeterminate', checkedCount > 0 && checkedCount < tplItems.length);
		if (onTypePanel) {
			applyTemplateFilter($ov);
			$ov.find('[data-tpl-search]').on('input', function () {
				tplFilter.query = $(this).val();
				applyTemplateFilter($ov);
			});
			$ov.find('[data-tpl-group]').on('click', function () {
				tplFilter.group = $(this).data('tpl-group').toString();
				applyTemplateFilter($ov);
			});
		}
		$ov.find('[data-tpl-cat]').on('click', function () {
			tpl.type = $(this).data('tpl-cat').toString();
			tpl.checked = {};
			renderTemplateModal();
		});
		$ov.find('[data-tpl-back]').on('click', function () {
			tpl.type = null;
			tpl.checked = {};
			renderTemplateModal();
		});
		$ov.find('[data-tpl-item]').on('change', function () {
			var i = $(this).data('tpl-item');
			tpl.checked[i] = this.checked;
			renderTemplateModal();
		});
		$ov.find('[data-tpl-select-all]').on('change', function () {
			var items = (TEMPLATES[tpl.type] || {}).items || [];
			var checkAll = this.checked;
			items.forEach(function (item, i) { tpl.checked[i] = checkAll; });
			renderTemplateModal();
		});
		$ov.find('[data-tpl-add]').on('click', function () {
			var def = TEMPLATES[tpl.type] || { items: [] };
			var items = def.items || [];
			var selected = items.filter(function (item, i) { return !!tpl.checked[i]; });
			if (!selected.length) { return; }
			var $btn = $(this).prop('disabled', true).text('Adding…');
			var addedCount = 0;
			function addAllWithPreset(preset) {
				(function addNext(i) {
					if (i >= selected.length) {
						closeModal();
						render();
						toast(addedCount + (addedCount === 1 ? ' service' : ' services') + ' added');
						return;
					}
					var item = selected[i];
					var data = $.extend({
						service_postID: cfg.postId,
						service_name: item.name,
						service_description: item.desc,
						service_price: item.price,
						service_duration: item.duration,
						service_unit: ''
					}, preset.service_category_status ? preset : {});
					post('mpwpb_save_service', data, function () {
						state.services.push({
							id: nextId(state.services),
							name: item.name,
							desc: item.desc,
							price: item.price,
							unit: '',
							duration: item.duration,
							catStatus: preset.service_category_status || '',
							parentCat: preset.service_parent_cat || '',
							subCat: preset.service_sub_cat || ''
						});
						addedCount++;
						addNext(i + 1);
					}, function () {
						// Skip anything that fails to save rather than aborting
						// the whole batch -- the rest are independent inserts.
						addNext(i + 1);
					});
				}(0));
			}
			if (def.category) {
				// This template defines its own category -- create/reuse it
				// first, then assign every added service to it (overrides
				// whatever's selected in the sidebar, since the template's
				// own grouping is more specific/intentional).
				ensureCategory(def.category, function (catId) {
					renderSidebar();
					addAllWithPreset(catId ? {
						service_category_status: 'on',
						service_parent_cat: catId,
						service_sub_cat: ''
					} : currentCategoryPreset());
				});
			} else {
				addAllWithPreset(currentCategoryPreset());
			}
		});
	}
	$root.on('click', '[data-csm-use-template]', function () {
		tpl.type = null;
		tpl.checked = {};
		tplFilter = { group: 'all', query: '' };
		renderTemplateModal();
	});

})(jQuery);
