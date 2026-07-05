/**
 * Extra Service (Pricing step) — a flat list of optional paid add-ons,
 * ported to match the "Services & Pricing" list's design. Persists through
 * the EXISTING classic AJAX endpoints (Admin/settings/Extra_service.php) —
 * this file only owns presentation + local optimistic state; the classic
 * card-grid UI is untouched and keeps working in Classic mode.
 */
(function ($) {
	'use strict';

	var cfg = window.mpwpbEsm;
	var $root = $('#mpwpb-esm');
	if (!cfg || !$root.length) {
		return;
	}
	var t = cfg.i18n || {};

	var state = {
		items: (cfg.items || []).slice(),
		query: ''
	};

	/* ---------------------------------------------------------------- *
	 *  Helpers
	 * ---------------------------------------------------------------- */
	function esc(s) {
		return $('<div>').text(s === null || s === undefined ? '' : String(s)).html();
	}
	function nextId(list) {
		var max = -1;
		list.forEach(function (item) {
			var n = parseInt(item.id, 10);
			if (!isNaN(n) && n > max) { max = n; }
		});
		return String(max + 1);
	}
	function post(action, data, done, fail) {
		var payload = $.extend({ action: action, nonce: cfg.nonce }, data);
		$.post(cfg.ajaxUrl, payload).done(function (resp) {
			if (resp && resp.success) {
				done(resp.data || {});
			} else {
				(fail || function () {})((resp && resp.data) || {});
			}
		}).fail(function () {
			(fail || function () {})({});
		});
	}
	function visibleItems() {
		var list = state.items;
		if (state.query.trim()) {
			var q = state.query.trim().toLowerCase();
			list = list.filter(function (it) {
				return it.name.toLowerCase().indexOf(q) > -1 || (it.desc || '').toLowerCase().indexOf(q) > -1;
			});
		}
		return list;
	}

	/* ---------------------------------------------------------------- *
	 *  Render
	 * ---------------------------------------------------------------- */
	function rowHtml(item) {
		return '<div class="mpwpb-csm__row" data-esm-row="' + esc(item.id) + '">'
			+ '<div class="mpwpb-csm__row-main">'
			+ '<div class="mpwpb-csm__row-top"><span class="mpwpb-csm__row-name">' + esc(item.name) + '</span></div>'
			+ (item.desc ? '<p class="mpwpb-csm__row-desc">' + esc(item.desc) + '</p>' : '')
			+ '</div>'
			+ '<div class="mpwpb-csm__row-nums">'
			+ '<span class="mpwpb-csm__row-price">' + esc(cfg.currencySymbol || '$') + esc(item.price) + '</span>'
			+ '<span class="mpwpb-csm__row-dur"><span class="dashicons dashicons-archive"></span>' + esc(item.qty) + '</span>'
			+ '</div>'
			+ '<div class="mpwpb-csm__row-acts">'
			+ '<button type="button" class="mpwpb-csm__icon-btn" title="Duplicate" data-esm-duplicate="' + esc(item.id) + '"><span class="dashicons dashicons-admin-page"></span></button>'
			+ '<button type="button" class="mpwpb-csm__icon-btn" title="Edit" data-esm-edit="' + esc(item.id) + '"><span class="dashicons dashicons-edit"></span></button>'
			+ '<button type="button" class="mpwpb-csm__icon-btn is-danger" title="Delete" data-esm-delete="' + esc(item.id) + '"><span class="dashicons dashicons-trash"></span></button>'
			+ '</div>'
			+ '</div>';
	}

	function renderHeader() {
		$root.find('[data-esm-part="count"]').text(visibleItems().length);
	}

	function renderList() {
		var list = visibleItems();
		var html;
		if (!list.length) {
			html = '<div class="mpwpb-csm__empty"><span class="dashicons dashicons-cart"></span>'
				+ '<div class="mpwpb-csm__empty-title">' + esc(t.noneYet) + '</div>'
				+ '<p class="mpwpb-csm__empty-sub">' + esc(state.query ? t.tryDifferentSearch : t.addHint) + '</p>'
				+ '</div>';
		} else {
			html = '<div class="mpwpb-csm__list">' + list.map(rowHtml).join('') + '</div>';
		}
		$root.find('[data-esm-part="list"]').html(html);
	}

	function render() {
		renderHeader();
		renderList();
	}

	/* ---------------------------------------------------------------- *
	 *  Shell (built once)
	 * ---------------------------------------------------------------- */
	$root.html(
		'<div class="mpwpb-csm__header">' +
		'<div class="mpwpb-csm__crumbs"><span class="mpwpb-csm__crumb is-last">' + esc(t.addService) + '</span>' +
		'<span class="mpwpb-csm__count-badge" data-esm-part="count"></span></div>' +
		'<div class="mpwpb-csm__search"><span class="dashicons dashicons-search"></span><input type="text" data-esm-search placeholder="' + esc(t.searchPlaceholder) + '"/></div>' +
		'<button type="button" class="mpwpb-csm__add-btn" data-esm-add><span class="dashicons dashicons-plus-alt2"></span>' + esc(t.addService) + '</button>' +
		'</div>' +
		'<div data-esm-part="list"></div>'
	);
	render();

	$root.on('input', '[data-esm-search]', function () {
		state.query = $(this).val();
		render();
	});

	/* ---------------------------------------------------------------- *
	 *  Modal plumbing — mirrors mpwpb-categories-services-modern.js:
	 *  appended inside #mpwpb-sme (not <body>) so it inherits that shell's
	 *  CSS custom properties (--brand, --line, --ink, etc.).
	 * ---------------------------------------------------------------- */
	function closeModal() {
		$('.mpwpb-csm__overlay').remove();
	}
	function openModal(innerHtml) {
		closeModal();
		var $ov = $('<div class="mpwpb-csm__overlay"><div class="mpwpb-csm__modal">' + innerHtml + '</div></div>');
		$('#mpwpb-sme').append($ov);
		$ov.on('mousedown', function (e) { if (e.target === this) { closeModal(); } });
		return $ov;
	}
	$(document).on('click', '[data-esm-modal-close]', closeModal);
	$(document).on('keydown', function (e) { if (e.key === 'Escape') { closeModal(); } });

	/* ---------------------------------------------------------------- *
	 *  Add/Edit modal
	 * ---------------------------------------------------------------- */
	function openItemModal(item) {
		var isNew = !item.id;
		var $ov = openModal(
			'<div class="mpwpb-csm__modal-head"><span class="mpwpb-csm__modal-title">' + esc(isNew ? t.addService : t.editService) + '</span>'
			+ '<button type="button" class="mpwpb-csm__modal-close" data-esm-modal-close><span class="dashicons dashicons-no-alt"></span></button></div>'
			+ '<div class="mpwpb-csm__modal-body">'
			+ '<div class="mpwpb-csm__field-error" style="display:none" data-esm-error></div>'
			+ '<label class="mpwpb-csm__field"><span class="mpwpb-csm__field-label">Name</span>'
			+ '<input type="text" autofocus data-esm-name value="' + esc(item.name) + '" placeholder="e.g. Interior Vacuum"/></label>'
			+ '<label class="mpwpb-csm__field"><span class="mpwpb-csm__field-label">Description <span class="mpwpb-csm__field-optional">(optional)</span></span>'
			+ '<textarea rows="2" data-esm-desc placeholder="What does this add-on include?">' + esc(item.desc) + '</textarea></label>'
			+ '<div class="mpwpb-csm__grid2">'
			+ '<label class="mpwpb-csm__field"><span class="mpwpb-csm__field-label">Price (' + esc(cfg.currencySymbol || '$') + ')</span>'
			+ '<input type="number" min="0" data-esm-price value="' + esc(item.price) + '" placeholder="10"/></label>'
			+ '<label class="mpwpb-csm__field"><span class="mpwpb-csm__field-label">Quantity</span>'
			+ '<input type="number" min="0" data-esm-qty value="' + esc(item.qty) + '" placeholder="1"/></label>'
			+ '</div>'
			+ '</div>'
			+ '<div class="mpwpb-csm__modal-foot">'
			+ '<button type="button" class="mpwpb-csm__modal-btn" data-esm-modal-close>Cancel</button>'
			+ '<button type="button" class="mpwpb-csm__modal-btn mpwpb-csm__modal-btn--primary" data-esm-save>' + esc(isNew ? t.addService : 'Save changes') + '</button>'
			+ '</div>'
		);

		function validate() {
			var name = $ov.find('[data-esm-name]').val().trim();
			var price = $ov.find('[data-esm-price]').val();
			return name && price !== '' && Number(price) >= 0;
		}
		$ov.on('input', '[data-esm-name],[data-esm-price]', function () {
			$ov.find('[data-esm-save]').prop('disabled', !validate());
		});
		$ov.find('[data-esm-save]').prop('disabled', !validate());

		$ov.find('[data-esm-save]').on('click', function () {
			if (!validate()) { return; }
			var $btn = $(this).prop('disabled', true);
			var name = $ov.find('[data-esm-name]').val().trim();
			var desc = $ov.find('[data-esm-desc]').val();
			var price = $ov.find('[data-esm-price]').val();
			var qty = $ov.find('[data-esm-qty]').val();
			var data = {
				service_postID: cfg.postId,
				service_name: name,
				service_description: desc,
				service_price: price,
				service_qty: qty
			};
			function onFail(data2) {
				$btn.prop('disabled', false);
				$ov.find('[data-esm-error]').text(data2.message || 'Something went wrong.').show();
			}
			var localItem = { id: isNew ? nextId(state.items) : item.id, name: name, desc: desc, price: price, qty: qty };
			if (isNew) {
				post('mpwpb_save_ex_service', data, function () {
					state.items.push(localItem);
					closeModal();
					render();
				}, onFail);
			} else {
				data.service_itemId = item.id;
				post('mpwpb_ext_service_update', data, function () {
					state.items = state.items.map(function (it) { return it.id === item.id ? localItem : it; });
					closeModal();
					render();
				}, onFail);
			}
		});
	}
	$root.on('click', '[data-esm-add]', function () {
		openItemModal({ id: '', name: '', desc: '', price: '', qty: '' });
	});
	$root.on('click', '[data-esm-edit]', function () {
		var id = $(this).data('esm-edit').toString();
		var item = state.items.filter(function (it) { return it.id === id; })[0];
		if (item) { openItemModal($.extend({}, item)); }
	});
	$root.on('click', '[data-esm-duplicate]', function () {
		var id = $(this).data('esm-duplicate').toString();
		var item = state.items.filter(function (it) { return it.id === id; })[0];
		if (!item) { return; }
		var data = {
			service_postID: cfg.postId,
			service_name: item.name + ' (copy)',
			service_description: item.desc,
			service_price: item.price,
			service_qty: item.qty
		};
		post('mpwpb_save_ex_service', data, function () {
			state.items.push($.extend({}, item, { id: nextId(state.items), name: item.name + ' (copy)' }));
			render();
		});
	});

	/* ---------------------------------------------------------------- *
	 *  Confirm delete
	 * ---------------------------------------------------------------- */
	$root.on('click', '[data-esm-delete]', function () {
		var id = $(this).data('esm-delete').toString();
		var item = state.items.filter(function (it) { return it.id === id; })[0];
		var name = item ? item.name : '';
		var $ov = openModal(
			'<div class="mpwpb-csm__modal-body" style="padding-top:20px;">'
			+ '<span class="mpwpb-csm__modal-title">' + esc(t.deleteTitle) + '</span>'
			+ '<p>“' + esc(name) + '” will be permanently removed.</p></div>'
			+ '<div class="mpwpb-csm__modal-foot">'
			+ '<button type="button" class="mpwpb-csm__modal-btn" data-esm-modal-close>Cancel</button>'
			+ '<button type="button" class="mpwpb-csm__modal-btn mpwpb-csm__modal-btn--danger" data-esm-confirm-delete>Delete</button>'
			+ '</div>'
		);
		$ov.find('[data-esm-confirm-delete]').on('click', function () {
			var $btn = $(this).prop('disabled', true);
			post('mpwpb_ext_service_delete_item', { service_postID: cfg.postId, itemId: id }, function () {
				state.items = state.items.filter(function (it) { return it.id !== id; });
				closeModal();
				render();
			}, function () { $btn.prop('disabled', false); });
		});
	});

})(jQuery);
