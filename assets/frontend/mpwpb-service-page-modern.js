/**
 * Front-end single service page — modern redesign behavior.
 *
 * Adds real smooth-scroll + active-tab highlighting for the Overview/
 * FAQ/Details tab bar (assets/frontend/mpwpb_registration.js has a
 * pre-existing handler for this that references an undefined `tabId`
 * variable and never actually runs — left untouched here since it's
 * already inert, rather than editing that large shared file).
 *
 * Does not touch the booking widget/popup or its AJAX in any way.
 */
(function ($) {
	'use strict';

	// "View more!" (Features Heighlight) popup is rendered inside <header>
	// (Frontend/MPWPB_Static_Template.php::popup_feature_lists(), fired from
	// templates/themes/static.php's header markup) -- that header has
	// overflow:hidden (to crop its background image), which traps this
	// popup's position:fixed under the sidebar/menu despite its own
	// z-index:9999, a well-known overflow:hidden-clips-fixed-descendant
	// browser quirk. Moving it to be a direct child of <body> once on load
	// breaks it out of that containment; the popup's own open/close
	// mechanism (mp_global/assets/mp_style/mpwpb_plugin_global.js,
	// data-target-popup/data-popup, attribute-based and document-delegated)
	// keeps working unchanged regardless of where in the DOM it now lives.
	// Matched by its actual class/data-popup value -- nothing in the markup
	// has id="mpwpb_view_more_popup" (that string is only ever a data-popup/
	// data-target-popup attribute VALUE, used by the generic popup opener to
	// match trigger <-> popup).
	var $viewMorePopup = $('.popup-features[data-popup="#mpwpb_view_more_popup"]');
	if ($viewMorePopup.length) {
		$('body').append($viewMorePopup);
	}

	var $tabNav = $('.mpwpb-static-template .mpwpb-details-page-tab, .mpwpb-static-template nav.mpwpb-details-page-tab');
	if (!$tabNav.length) {
		return;
	}

	var $links = $tabNav.find('a[href^="#"]');
	var sections = [];
	$links.each(function () {
		var $target = $($(this).attr('href'));
		if ($target.length) {
			sections.push({ link: this, li: $(this).parent('li')[0], el: $target[0] });
		}
	});
	if (!sections.length) {
		return;
	}

	function setActive(li) {
		sections.forEach(function (s) {
			$(s.li).toggleClass('active', s.li === li);
			$(s.link).toggleClass('mpwpb-tab-active', s.li === li);
		});
	}

	$links.on('click', function (e) {
		e.preventDefault();
		var target = $(this).attr('href');
		var $target = $(target);
		if (!$target.length) {
			return;
		}
		setActive($(this).parent('li')[0]);
		$('html, body').animate({ scrollTop: $target.offset().top - 90 }, 350);
	});

	// Scroll-spy: highlight whichever section is currently most in view.
	if (window.IntersectionObserver) {
		var observer = new IntersectionObserver(function (entries) {
			var visible = entries.filter(function (en) { return en.isIntersecting; });
			if (!visible.length) {
				return;
			}
			visible.sort(function (a, b) { return b.intersectionRatio - a.intersectionRatio; });
			var match = sections.filter(function (s) { return s.el === visible[0].target; })[0];
			if (match) {
				setActive(match.li);
			}
		}, { rootMargin: '-100px 0px -60% 0px', threshold: [0, 0.25, 0.5, 0.75, 1] });
		sections.forEach(function (s) { observer.observe(s.el); });
	}

	setActive(sections[0].li);

})(jQuery);
