/**
 * [mpwpb-service-list]: Grid/List switch (remembered per browser), search
 * and sort. No dependencies. Markup: Frontend/MPWPB_Service_Directory.php.
 */
(function () {
	'use strict';
	var KEY = 'mpwpb_service_list_view';

	function store(get, value) {
		try {
			if (get) { return window.localStorage.getItem(KEY); }
			window.localStorage.setItem(KEY, value);
		} catch (e) {}
		return null;
	}

	function init(root) {
		var list = root.querySelector('[data-mpwpb-sl-items]');
		if (!list) { return; }
		var items = Array.prototype.slice.call(list.children);
		var search = root.querySelector('[data-mpwpb-sl-search]');
		var sort = root.querySelector('[data-mpwpb-sl-sort]');
		var count = root.querySelector('[data-mpwpb-sl-count]');
		var none = root.querySelector('[data-mpwpb-sl-none]');
		var buttons = root.querySelectorAll('[data-mpwpb-sl-view]');

		function setView(view) {
			if (view !== 'grid' && view !== 'list') { return; }
			root.setAttribute('data-view', view);
			Array.prototype.forEach.call(buttons, function (b) {
				b.setAttribute('aria-pressed', b.getAttribute('data-mpwpb-sl-view') === view ? 'true' : 'false');
			});
		}
		setView(store(true) || root.getAttribute('data-view'));
		Array.prototype.forEach.call(buttons, function (b) {
			b.addEventListener('click', function () {
				var view = b.getAttribute('data-mpwpb-sl-view');
				setView(view);
				store(false, view);
			});
		});

		function num(el, attr) {
			var v = parseFloat(el.getAttribute(attr));
			return isNaN(v) ? null : v;
		}
		function apply() {
			var q = search ? search.value.trim().toLowerCase() : '';
			var shown = 0;
			items.forEach(function (el) {
				var match = !q || el.getAttribute('data-search').indexOf(q) > -1;
				el.hidden = !match;
				if (match) { shown++; }
			});
			var mode = sort ? sort.value : 'default';
			var sorted = items.slice().sort(function (a, b) {
				if (mode === 'price-asc' || mode === 'price-desc') {
					var pa = num(a, 'data-price'), pb = num(b, 'data-price');
					// Services without a price always go last.
					if (pa === null && pb === null) { return 0; }
					if (pa === null) { return 1; }
					if (pb === null) { return -1; }
					return mode === 'price-asc' ? pa - pb : pb - pa;
				}
				if (mode === 'rating') { return (num(b, 'data-rating') || 0) - (num(a, 'data-rating') || 0); }
				if (mode === 'name') { return a.getAttribute('data-name').localeCompare(b.getAttribute('data-name')); }
				return num(a, 'data-order') - num(b, 'data-order');
			});
			sorted.forEach(function (el) { list.appendChild(el); });
			if (count) {
				count.textContent = (shown === 1 ? root.getAttribute('data-one') : root.getAttribute('data-many') || '%d').replace('%d', shown);
			}
			if (none) { none.hidden = shown > 0; }
			list.hidden = shown === 0;
		}
		if (search) { search.addEventListener('input', apply); }
		if (sort) { sort.addEventListener('change', apply); }
	}

	function boot() {
		Array.prototype.forEach.call(document.querySelectorAll('[data-mpwpb-sl]'), init);
	}
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}
}());
