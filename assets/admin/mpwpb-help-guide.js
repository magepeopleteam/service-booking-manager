(function () {
	'use strict';

	var guide = document.querySelector('.mpwpb-guide');
	if (!guide) return;

	var search = document.getElementById('mpwpb-guide-search');
	var topics = Array.prototype.slice.call(guide.querySelectorAll('.mpwpb-guide__topic'));
	var sections = Array.prototype.slice.call(guide.querySelectorAll('.mpwpb-guide__section'));
	var filters = Array.prototype.slice.call(guide.querySelectorAll('.mpwpb-guide__filters button'));
	var navLinks = Array.prototype.slice.call(guide.querySelectorAll('.mpwpb-guide__nav a'));
	var resultText = document.getElementById('mpwpb-guide-results');
	var live = document.getElementById('mpwpb-guide-live');
	var empty = guide.querySelector('.mpwpb-guide__empty');
	var expand = guide.querySelector('.mpwpb-guide__expand');
	var sectionSelect = document.getElementById('mpwpb-guide-section-select');
	var activeTier = 'all';

	guide.classList.add('is-enhanced');

	function normalize(value) {
		return String(value || '').toLocaleLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/\s+/g, ' ').trim();
	}

	function words() {
		return normalize(search.value).split(' ').filter(Boolean);
	}

	function setOpen(topic, open) {
		var button = topic.querySelector('.mpwpb-guide__topic-toggle');
		topic.classList.toggle('is-open', open);
		if (button) button.setAttribute('aria-expanded', open ? 'true' : 'false');
	}

	function update() {
		var query = words();
		var visibleCount = 0;

		topics.forEach(function (topic) {
			var tierMatch = activeTier === 'all' || topic.dataset.tier === activeTier;
			var haystack = normalize(topic.dataset.search);
			var searchMatch = query.every(function (word) { return haystack.indexOf(word) !== -1; });
			var show = tierMatch && searchMatch;
			topic.hidden = !show;
			if (show) {
				visibleCount += 1;
				if (query.length) setOpen(topic, true);
			}
		});

		sections.forEach(function (section) {
			var hasVisible = !!section.querySelector('.mpwpb-guide__topic:not([hidden])');
			section.hidden = !hasVisible;
			var nav = guide.querySelector('.mpwpb-guide__nav a[href="#' + section.id + '"]');
			if (nav) nav.hidden = !hasVisible;
		});

		if (resultText) {
			resultText.textContent = visibleCount === 1 ? '1 topic found' : visibleCount + ' topics found';
		}
		if (empty) empty.hidden = visibleCount !== 0;
	}

	topics.forEach(function (topic) {
		var toggle = topic.querySelector('.mpwpb-guide__topic-toggle');
		if (toggle) {
			toggle.addEventListener('click', function () {
				setOpen(topic, !topic.classList.contains('is-open'));
			});
		}
	});

	filters.forEach(function (button) {
		button.addEventListener('click', function () {
			activeTier = button.dataset.tier || 'all';
			filters.forEach(function (item) {
				var selected = item === button;
				item.classList.toggle('is-active', selected);
				item.setAttribute('aria-pressed', selected ? 'true' : 'false');
			});
			update();
		});
	});

	if (search) search.addEventListener('input', update);

	if (expand) {
		expand.addEventListener('click', function () {
			var shouldExpand = expand.dataset.expanded !== 'true';
			topics.filter(function (topic) { return !topic.hidden; }).forEach(function (topic) { setOpen(topic, shouldExpand); });
			expand.dataset.expanded = shouldExpand ? 'true' : 'false';
			expand.textContent = shouldExpand ? 'Collapse all' : 'Expand all';
		});
	}

	if (sectionSelect) {
		sectionSelect.addEventListener('change', function () {
			if (!sectionSelect.value) return;
			var target = document.querySelector(sectionSelect.value);
			if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
		});
	}

	guide.querySelectorAll('.mpwpb-guide__copy').forEach(function (button) {
		button.addEventListener('click', function () {
			var url = window.location.href.split('#')[0] + '#' + button.dataset.topic;
			var done = function (message) { if (live) live.textContent = message; };
			if (navigator.clipboard && window.isSecureContext) {
				navigator.clipboard.writeText(url).then(function () { done(window.mpwpbHelpGuide.copied); }).catch(function () { done(window.mpwpbHelpGuide.copyFailed); });
			} else {
				done(window.mpwpbHelpGuide.copyFailed);
			}
		});
	});

	if (empty) {
		var clear = empty.querySelector('button');
		if (clear) clear.addEventListener('click', function () { search.value = ''; activeTier = 'all'; filters[0].click(); search.focus(); });
	}

	document.addEventListener('keydown', function (event) {
		var target = event.target;
		var typing = /^(INPUT|TEXTAREA|SELECT|BUTTON|A)$/.test(target.tagName) || target.isContentEditable;
		if (event.key === '/' && !typing) {
			event.preventDefault();
			search.focus();
		}
		if (event.key === 'Escape' && document.activeElement === search && search.value) {
			search.value = '';
			update();
		}
	});

	function openHash() {
		var id = window.location.hash.slice(1);
		if (!id) return;
		var target = document.getElementById(id);
		if (target && target.classList.contains('mpwpb-guide__topic')) {
			setOpen(target, true);
			setTimeout(function () {
				target.scrollIntoView({ behavior: 'smooth', block: 'start' });
				var heading = target.querySelector('.mpwpb-guide__topic-toggle');
				if (heading) heading.focus({ preventScroll: true });
			}, 50);
		} else if (id.indexOf('pro-') === 0 && guide.dataset.hasPro !== '1') {
			var notice = document.getElementById('mpwpb-guide-pro-notice');
			if (notice) {
				notice.textContent = window.mpwpbHelpGuide.proRequired;
				notice.hidden = false;
			}
		}
	}

	if ('IntersectionObserver' in window) {
		var observer = new IntersectionObserver(function (entries) {
			entries.forEach(function (entry) {
				if (!entry.isIntersecting) return;
				navLinks.forEach(function (link) {
					var active = link.getAttribute('href') === '#' + entry.target.id;
					link.classList.toggle('is-active', active);
					if (active) link.setAttribute('aria-current', 'location'); else link.removeAttribute('aria-current');
				});
			});
		}, { rootMargin: '-15% 0px -70% 0px' });
		sections.forEach(function (section) { observer.observe(section); });
	}

	window.addEventListener('hashchange', openHash);
	update();
	openHash();
}());
