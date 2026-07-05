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
