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

	// "Our Past Work" gallery -- reuses the same owl-carousel init helper
	// (mpwpb.js, mpwpb_active_carousel) and .prev/.next -> .owl-prev/.owl-next
	// click-proxy pattern already used for the date/time carousel elsewhere
	// in the plugin. No-ops via .each() if the section isn't rendered
	// (gallery off or no images -- see MPWPB_Static_Template::show_service_gallery()).
	if (typeof mpwpb_active_carousel === 'function') {
		mpwpb_active_carousel($('.mpwpb-static-template .mpwpb-gallery-section'), 4);
	}

	// "Our Past Work" click-to-zoom -- opening/closing the lightbox itself is
	// the plugin's existing generic popup mechanism (data-target-popup/
	// data-popup, document-delegated in mp_global/assets/mp_style/
	// mpwpb_plugin_global.js); this only swaps the lightbox <img> to whichever
	// thumbnail was clicked and cycles prev/next through the same image set.
	var $galleryItems = $('.mpwpb-static-template .mpwpb-gallery-item');
	if ($galleryItems.length) {
		var $lightboxImg = $('.mpwpb-gallery-lightbox-img');
		var $lightboxCounter = $('.mpwpb-gallery-lightbox-counter');
		var galleryIndex = 0;

		var showGalleryImage = function (index) {
			var total = $galleryItems.length;
			galleryIndex = (index + total) % total;
			var $item = $galleryItems.eq(galleryIndex);
			$lightboxImg.attr({
				src: $item.data('full'),
				alt: $item.find('img').attr('alt') || ''
			});
			$lightboxCounter.text((galleryIndex + 1) + ' / ' + total);
		};

		$galleryItems.on('click', function () {
			showGalleryImage($galleryItems.index(this));
		});
		$('.mpwpb-gallery-lightbox-next').on('click', function (e) {
			e.stopPropagation();
			showGalleryImage(galleryIndex + 1);
		});
		$('.mpwpb-gallery-lightbox-prev').on('click', function (e) {
			e.stopPropagation();
			showGalleryImage(galleryIndex - 1);
		});
	}

	var $tabNav = $('.mpwpb-static-template .mpwpb-details-page-tab, .mpwpb-static-template nav.mpwpb-details-page-tab');
	if (!$tabNav.length) {
		return;
	}

	// How far down the viewport the site's own sticky header (and a fixed
	// admin bar) reach once stuck -- the phone tab bar and the desktop sidebar
	// stick just below that (--mpwpb-stuck-offset in the CSS). Measured from
	// the real elements: a theme's published --mpwpb-sticky-offset is a fixed
	// guess (Bookingly says 75px) that left the bar hanging well below a
	// header that rendered shorter in the visitor's browser, or ignored that
	// the admin bar scrolls away on phones. Candidates are wide, top-anchored
	// fixed/sticky elements outside this plugin's own markup; their stuck
	// bottom is their `top` plus their height. Nothing found leaves the CSS
	// fallback (the theme's variable) in charge.
	var stickyRoots = $('.mpwpb-static-template').toArray();
	var topBars = [];
	function collectTopBars() {
		topBars = [];
		var seen = [];
		$('body > *, body > * > *, header, [role="banner"]').each(function () {
			if (seen.indexOf(this) > -1 || $(this).closest('.mpwpb_registration, .mpwpb_popup').length) {
				return;
			}
			seen.push(this);
			var position = window.getComputedStyle(this).position;
			if (position === 'fixed' || position === 'sticky') {
				topBars.push(this);
			}
		});
	}
	function stuckOffset() {
		var bottom = null;
		topBars.forEach(function (el) {
			var style = window.getComputedStyle(el);
			var rect = el.getBoundingClientRect();
			var top = parseFloat(style.top);
			if (style.display === 'none' || style.visibility === 'hidden' || !rect.height
				|| rect.width < window.innerWidth * 0.5 || rect.height > window.innerHeight * 0.4
				|| isNaN(top) || top > 200) {
				return;
			}
			bottom = Math.max(bottom || 0, top + rect.height);
		});
		return bottom;
	}
	var lastStuckOffset;
	function syncStickyOffset() {
		var offset = stuckOffset();
		if (offset === lastStuckOffset) {
			return;
		}
		lastStuckOffset = offset;
		stickyRoots.forEach(function (root) {
			if (offset === null) {
				root.style.removeProperty('--mpwpb-stuck-offset');
			} else {
				root.style.setProperty('--mpwpb-stuck-offset', Math.round(offset) + 'px');
			}
		});
	}
	collectTopBars();
	syncStickyOffset();
	var stickyResizeTimer = null;
	$(window).on('resize.mpwpb-sticky-offset', function () {
		clearTimeout(stickyResizeTimer);
		stickyResizeTimer = setTimeout(function () {
			collectTopBars();
			syncStickyOffset();
		}, 150);
	});
	// Headers that shrink or hide on scroll change height without a resize.
	var stickyScrollQueued = false;
	$(window).on('scroll.mpwpb-sticky-offset', function () {
		if (stickyScrollQueued) {
			return;
		}
		stickyScrollQueued = true;
		window.requestAnimationFrame(function () {
			stickyScrollQueued = false;
			syncStickyOffset();
		});
	});

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
		revealTab(li);
	}

	// On phones the tab list scrolls sideways (mpwpb-service-page-modern.css),
	// so keep the pill the scroll-spy just activated inside the visible strip.
	// scrollLeft only -- scrollIntoView() would also scroll the page.
	function revealTab(li) {
		var ul = li && li.parentNode;
		if (!ul || ul.scrollWidth <= ul.clientWidth) {
			return;
		}
		var listRect = ul.getBoundingClientRect();
		var tabRect = li.getBoundingClientRect();
		if (tabRect.left < listRect.left) {
			ul.scrollLeft -= listRect.left - tabRect.left + 8;
		} else if (tabRect.right > listRect.right) {
			ul.scrollLeft += tabRect.right - listRect.right + 8;
		}
	}

	$links.on('click', function (e) {
		e.preventDefault();
		var target = $(this).attr('href');
		var $target = $(target);
		if (!$target.length) {
			return;
		}
		setActive($(this).parent('li')[0]);
		// Land the section just below whatever covers the top of the viewport:
		// the stuck tab bar on phones, otherwise only the theme header. A
		// fixed 90px left headings hidden behind the header + bar.
		var covered = lastStuckOffset || 0;
		if ($tabNav.css('position') === 'sticky') {
			covered = (parseFloat($tabNav.css('top')) || 0) + $tabNav.outerHeight();
		}
		$('html, body').animate({ scrollTop: $target.offset().top - covered - 12 }, 350);
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

	// ── Review star-rating input + submission (#service-reviews) ──────────
	// Click-to-select 1-5 stars: fills solid up to the clicked value, empty
	// after it -- same fas/far icon-swap technique as the read-only hero
	// rating (MPWPB_Static_Template::render_star_icons()), just interactive.
	var $starInput = $('.mpwpb-star-input');
	function paintStars($wrap, value) {
		$wrap.find('i').each(function (i) {
			var starValue = i + 1;
			$(this)
				.toggleClass('fas', starValue <= value)
				.toggleClass('far', starValue > value);
		});
	}
	$starInput.on('click', 'i', function () {
		var $wrap = $(this).closest('.mpwpb-star-input');
		var value = $(this).data('value');
		$wrap.attr('data-rating', value);
		$wrap.closest('form').find('.mpwpb-review-rating-value').val(value);
		paintStars($wrap, value);
	});
	$starInput.on('mouseenter', 'i', function () {
		paintStars($(this).closest('.mpwpb-star-input'), $(this).data('value'));
	}).on('mouseleave', function () {
		paintStars($(this), parseInt($(this).attr('data-rating'), 10) || 0);
	});

	$(document).on('submit', '#mpwpb-review-form', function (e) {
		e.preventDefault();
		var $form = $(this);
		var $msg = $form.find('.mpwpb-review-msg');
		var $btn = $form.find('.mpwpb-review-submit');
		var rating = parseInt($form.find('.mpwpb-review-rating-value').val(), 10) || 0;

		if (typeof mpwpb_ajax === 'undefined') {
			return;
		}
		if (rating < 1) {
			$msg.removeClass('success').addClass('error').text('Please select a star rating.');
			return;
		}

		$btn.prop('disabled', true);
		$msg.removeClass('success error').text('');

		$.ajax({
			url: mpwpb_ajax.ajax_url,
			type: 'POST',
			data: {
				action: 'mpwpb_submit_review',
				nonce: mpwpb_ajax.nonce,
				service_id: $form.data('service-id'),
				rating: rating,
				title: $form.find('[name="title"]').val(),
				content: $form.find('[name="content"]').val()
			},
			success: function (response) {
				if (response.success) {
					$msg.removeClass('error').addClass('success').text(response.data.message);
					$form.find('[name="title"]').val('');
					$form.find('[name="content"]').val('');
				} else {
					$msg.removeClass('success').addClass('error').text(response.data.message || 'Something went wrong.');
				}
			},
			error: function () {
				$msg.removeClass('success').addClass('error').text('Something went wrong. Please try again.');
			},
			complete: function () {
				$btn.prop('disabled', false);
			}
		});
	});

})(jQuery);
