/**
 * "Add a service" chooser (template or from scratch). Opened from the
 * Services page's add buttons and from the admin menu's "Add Service" on any
 * admin screen, so it sits outside the Services-page-only code below.
 */
(function ($) {
	'use strict';
	var t = window.mpwpbServiceManage || {};
	/* ---------- "Add service" chooser: template or from scratch ---------- */
	var addReturn = null;
	function esc(str) { return $('<div>').text(str == null ? '' : str).html(); }
	function closeAdd() {
		$('#mpwpb-sv-add').remove();
		if (addReturn && addReturn.focus) { addReturn.focus(); }
	}
	function openAdd(href) {
		addReturn = document.activeElement;
		var hasTemplates = ($('#mpwpb-open-business-templates').length && typeof window.mpwpbBt !== 'undefined') || !!t.templatesUrl;
		var icoTpl = '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>';
		var icoNew = '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><path d="M14 3v6h6M12 12v6M9 15h6"/></svg>';
		var html = '<div class="mpwpb-sv-add" id="mpwpb-sv-add">'
			+ '<div class="mpwpb-sv-add__modal" role="dialog" aria-modal="true" aria-labelledby="mpwpb-sv-add-title">'
			+ '<div class="mpwpb-sv-add__head"><div><h2 id="mpwpb-sv-add-title">' + esc(t.addTitle || 'Add a service') + '</h2><p>' + esc(t.addIntro || '') + '</p></div>'
			+ '<button type="button" class="mpwpb-sv-add__close" data-sv-add-close aria-label="' + esc(t.close || 'Close') + '">&times;</button></div>'
			+ '<div class="mpwpb-sv-add__choices">'
			+ (hasTemplates ? '<button type="button" class="mpwpb-sv-add__choice" data-sv-add-template><span class="mpwpb-sv-add__ico">' + icoTpl + '</span><span class="mpwpb-sv-add__text"><strong>' + esc(t.fromTemplate) + '</strong><span>' + esc(t.fromTemplateSub) + '</span></span></button>' : '')
			+ '<a class="mpwpb-sv-add__choice" href="' + esc(href) + '"><span class="mpwpb-sv-add__ico">' + icoNew + '</span><span class="mpwpb-sv-add__text"><strong>' + esc(t.fromScratch) + '</strong><span>' + esc(t.fromScratchSub) + '</span></span></a>'
			+ '</div></div></div>';
		$('body').append(html);
		$('#mpwpb-sv-add .mpwpb-sv-add__choice').first().trigger('focus');
	}
	$(document).on('click', '[data-sv-add]', function (e) {
		e.preventDefault();
		openAdd($(this).attr('href'));
	});
	$(document).on('click', '#mpwpb-sv-add [data-sv-add-close]', closeAdd);
	$(document).on('click', '#mpwpb-sv-add', function (e) { if (e.target === this) { closeAdd(); } });
	$(document).on('keydown', function (e) { if (e.key === 'Escape' && $('#mpwpb-sv-add').length) { closeAdd(); } });
	$(document).on('click', '#mpwpb-sv-add [data-sv-add-template]', function () {
		$('#mpwpb-sv-add').remove();
		if ($('#mpwpb-open-business-templates').length && typeof window.mpwpbBt !== 'undefined') {
			$('#mpwpb-open-business-templates').trigger('click');
		} else if (t.templatesUrl) {
			// The picker lives on the Services page; open it there.
			window.location.href = t.templatesUrl;
		}
	});
	// WordPress admin menu: Service Booking > "Add Service".
	$(document).on('click', '#adminmenu a[href$="post-new.php?post_type=mpwpb_item"]', function (e) {
		e.preventDefault();
		openAdd($(this).attr('href'));
	});

})(jQuery);

/**
 * Services page (templates/layout/service_list.php): list + detail panel.
 * Status tabs and search filter the list; picking a service shows its
 * detail panel (rendered server-side, one per service); "Copy" puts the
 * shortcode on the clipboard. All actions are plain links, unchanged.
 */
(function ($) {
	'use strict';

	var $root = $('[data-mpwpb-sv]');
	if (!$root.length) {
		return;
	}
	var t = window.mpwpbServiceManage || {};
	var tab = 'all';
	var current = '';

	function visibleItems() {
		return $root.find('[data-sv-item]').filter(function () { return !this.hidden; });
	}
	function select(id) {
		current = id ? String(id) : '';
		$root.find('[data-sv-item]').each(function () {
			var on = $(this).attr('data-sv-item') === current;
			$(this).toggleClass('is-active', on).find('[data-sv-pick]').attr('aria-current', on ? 'true' : null);
		});
		$root.find('[data-sv-detail]').each(function () {
			this.hidden = $(this).attr('data-sv-detail') !== current;
		});
		$root.find('[data-sv-detail-empty]').prop('hidden', !!current);
	}
	function filter() {
		var q = $.trim($root.find('[data-sv-search]').val() || '').toLowerCase();
		$root.find('[data-sv-item]').each(function () {
			var st = $(this).attr('data-status');
			var inTab = tab === 'all' ? st !== 'trash' : st === tab;
			this.hidden = !(inTab && (!q || String($(this).attr('data-title')).indexOf(q) > -1));
		});
		var $vis = visibleItems();
		$root.find('[data-sv-empty]').prop('hidden', $vis.length > 0);
		// Keep the open service if it is still listed, else open the first one.
		if (!$vis.filter('[data-sv-item="' + current + '"]').length) {
			select($vis.first().attr('data-sv-item') || '');
		}
	}

	$root.on('click', '[data-sv-tab]', function () {
		tab = $(this).attr('data-sv-tab');
		$root.find('[data-sv-tab]').attr('aria-pressed', 'false');
		$(this).attr('aria-pressed', 'true');
		filter();
	});
	$root.on('input', '[data-sv-search]', filter);
	$root.on('click', '[data-sv-pick]', function () {
		select($(this).attr('data-sv-pick'));
		// On a narrow screen the panel sits below the list: bring it into view.
		var panel = document.getElementById('mpwpb-sv-detail-' + current);
		if (panel && panel.getBoundingClientRect().top > window.innerHeight * 0.8) {
			panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
		}
	});
	$root.on('click', '[data-sv-copy]', function () {
		var $btn = $(this), text = $btn.attr('data-sv-copy');
		function done() {
			$btn.text(t.copied || 'Copied').addClass('is-copied');
			setTimeout(function () { $btn.text(t.copy || 'Copy').removeClass('is-copied'); }, 1800);
		}
		if (navigator.clipboard && window.isSecureContext) {
			navigator.clipboard.writeText(text).then(done, function () { fallback(text); done(); });
		} else {
			fallback(text);
			done();
		}
	});
	function fallback(text) {
		var $tmp = $('<textarea readonly></textarea>').val(text).css({ position: 'fixed', left: '-9999px' }).appendTo('body');
		$tmp[0].select();
		try { document.execCommand('copy'); } catch (e) {}
		$tmp.remove();
	}

	// On load the first listed service is open -- or the one named in the URL
	// (?mpwpb_service=ID, e.g. right after the setup wizard created it).
	var wanted = (window.location.search.match(/[?&]mpwpb_service=(\d+)/) || [])[1];
	if (wanted && $root.find('[data-sv-item="' + wanted + '"]').length) {
		current = wanted;
	}
	filter();
	if (wanted && current === wanted) {
		select(wanted);
		var item = $root.find('[data-sv-item="' + wanted + '"]')[0];
		if (item && item.scrollIntoView) { item.scrollIntoView({ block: 'nearest' }); }
	}
})(jQuery);
