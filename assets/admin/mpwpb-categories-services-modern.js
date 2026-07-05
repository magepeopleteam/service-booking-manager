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
	function treeNodeHtml(cat, depth) {
		var isTop = depth === 0;
		var key = isTop ? catKey(cat.id) : subKey(cat.id);
		var kids = childrenOf(cat.id);
		var isOpen = !!state.expanded[cat.id];
		var active = state.selected === key;
		var count = countFor(key);
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
			+ (isTop ? '<span class="dashicons dashicons-category"></span>' : '')
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
			kids.forEach(function (k) { html += treeNodeHtml(k, depth + 1); });
			html += '<button type="button" class="mpwpb-csm__add-sub-btn" style="margin-left:' + ((depth + 1) * 18) + 'px" data-csm-add-sub="' + esc(cat.id) + '">'
				+ '<span class="dashicons dashicons-plus-alt2"></span>' + esc(t.addSubcategory) + '</button>';
			html += '</div>';
		}
		return html;
	}

	function renderSidebar() {
		var html = '<div class="mpwpb-csm__sidebar-inner">';
		html += '<button type="button" class="mpwpb-csm__side-row' + (state.selected === 'all' ? ' is-active' : '') + '" data-csm-select="all">'
			+ '<span class="dashicons dashicons-layout"></span><span class="mpwpb-csm__side-label">' + esc(t.allServices) + '</span>'
			+ '<span class="mpwpb-csm__side-count">' + state.services.length + '</span></button>';
		var uc = uncatCount();
		html += '<button type="button" class="mpwpb-csm__side-row' + (state.selected === 'none' ? ' is-active' : '') + (uc === 0 ? ' is-muted' : '') + '" data-csm-select="none">'
			+ '<span class="dashicons dashicons-archive"></span><span class="mpwpb-csm__side-label">' + esc(t.uncategorized) + '</span>'
			+ '<span class="mpwpb-csm__side-count">' + uc + '</span></button>';

		html += '<div class="mpwpb-csm__side-head"><span class="mpwpb-csm__side-head-label">Categories</span>'
			+ '<button type="button" class="mpwpb-csm__side-add" data-csm-add-cat><span class="dashicons dashicons-plus-alt2"></span>' + esc(t.newCategory) + '</button></div>';

		var topCats = state.categories;
		if (!topCats.length) {
			html += '<p class="mpwpb-csm__side-empty">' + esc(t.noCategoriesYet) + '</p>';
		} else {
			topCats.forEach(function (c) { html += treeNodeHtml(c, 0); });
		}
		html += '</div>';
		html += '<p class="mpwpb-csm__hint">' + t.countsHint + '</p>';
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
		var crumbs = '';
		if (path.length) {
			path.forEach(function (p, i) {
				if (i > 0) { crumbs += '<span class="mpwpb-csm__crumb-sep dashicons dashicons-arrow-right-alt2"></span>'; }
				var key = i === 0 ? catKey(p.id) : subKey(p.id);
				crumbs += '<button type="button" class="mpwpb-csm__crumb' + (i === path.length - 1 ? ' is-last' : '') + '" data-csm-select="' + esc(key) + '">' + esc(p.name) + '</button>';
			});
		} else {
			crumbs = '<span class="mpwpb-csm__crumb is-last">' + esc(headerLabel()) + '</span>';
		}
		crumbs += '<span class="mpwpb-csm__count-badge">' + visibleServices().length + '</span>';
		$root.find('[data-csm-part="crumbs"]').html(crumbs);
	}

	function rowHtml(svc) {
		var path = pathOfService(svc);
		var chips;
		if (path.length) {
			chips = path.map(function (p, i) {
				var key = i === 0 ? catKey(p.id) : subKey(p.id);
				return (i > 0 ? '<span class="mpwpb-csm__chip-sep dashicons dashicons-arrow-right-alt2"></span>' : '')
					+ '<button type="button" class="mpwpb-csm__chip" data-csm-select="' + esc(key) + '">' + esc(p.name) + '</button>';
			}).join('');
		} else {
			chips = '<button type="button" class="mpwpb-csm__chip mpwpb-csm__chip--none" data-csm-select="none">' + esc(t.noCategory) + '</button>';
		}
		return '<div class="mpwpb-csm__row" data-csm-row="' + esc(svc.id) + '">'
			+ '<div class="mpwpb-csm__row-main">'
			+ '<div class="mpwpb-csm__row-top"><span class="mpwpb-csm__row-name">' + esc(svc.name) + '</span>' + chips + '</div>'
			+ (svc.desc ? '<p class="mpwpb-csm__row-desc">' + esc(svc.desc) + '</p>' : '')
			+ '</div>'
			+ '<div class="mpwpb-csm__row-nums">'
			+ '<span class="mpwpb-csm__row-price">' + esc(svc.price) + '</span>'
			+ '<span class="mpwpb-csm__row-dur"><span class="dashicons dashicons-clock"></span>' + esc(fmtDur(svc.duration)) + '</span>'
			+ '</div>'
			+ '<div class="mpwpb-csm__row-acts">'
			+ '<button type="button" class="mpwpb-csm__icon-btn" title="Duplicate" data-csm-duplicate="' + esc(svc.id) + '"><span class="dashicons dashicons-admin-page"></span></button>'
			+ '<button type="button" class="mpwpb-csm__icon-btn" title="Edit" data-csm-edit="' + esc(svc.id) + '"><span class="dashicons dashicons-edit"></span></button>'
			+ '<button type="button" class="mpwpb-csm__icon-btn is-danger" title="Delete" data-csm-delete-svc="' + esc(svc.id) + '"><span class="dashicons dashicons-trash"></span></button>'
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
			html = '<div class="mpwpb-csm__list">' + list.map(rowHtml).join('') + '</div>';
		}
		$root.find('[data-csm-part="list"]').html(html);
	}

	function render() {
		renderSidebar();
		renderHeader();
		renderList();
	}

	/* ---------------------------------------------------------------- *
	 *  Shell (built once)
	 * ---------------------------------------------------------------- */
	$root.html(
		'<div class="mpwpb-csm__sidebar" data-csm-part="sidebar"></div>' +
		'<div class="mpwpb-csm__main">' +
		'<div class="mpwpb-csm__header">' +
		'<div class="mpwpb-csm__crumbs" data-csm-part="crumbs"></div>' +
		'<div class="mpwpb-csm__search"><span class="dashicons dashicons-search"></span><input type="text" data-csm-search placeholder="' + esc(t.searchPlaceholder) + '"/></div>' +
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
		renderHeader();
		renderList();
	});

	/* ---------------------------------------------------------------- *
	 *  Modal plumbing — one at a time, appended to <body> so it's never
	 *  clipped by the wizard's scroll containers.
	 * ---------------------------------------------------------------- */
	function closeModal() {
		$('.mpwpb-csm__overlay').remove();
	}
	function openModal(innerHtml) {
		closeModal();
		var $ov = $('<div class="mpwpb-csm__overlay"><div class="mpwpb-csm__modal">' + innerHtml + '</div></div>');
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
	$root.on('click', '[data-csm-delete-svc]', function () {
		openConfirmDelete('service', $(this).data('csm-delete-svc').toString());
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

		function validate() {
			var name = $ov.find('[data-csm-svc-name]').val().trim();
			var price = $ov.find('[data-csm-svc-price]').val();
			return name && price !== '' && Number(price) >= 0;
		}
		$ov.on('input', '[data-csm-svc-name],[data-csm-svc-price]', function () {
			$ov.find('[data-csm-svc-save]').prop('disabled', !validate());
		});
		$ov.find('[data-csm-svc-save]').prop('disabled', !validate());

		$ov.find('[data-csm-svc-save]').on('click', function () {
			if (!validate()) { return; }
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
			state.services.push($.extend({}, svc, { id: nextId(state.services), name: svc.name + ' (copy)' }));
			render();
		});
	});

})(jQuery);
