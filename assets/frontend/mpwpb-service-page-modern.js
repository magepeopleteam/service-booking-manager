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

	// "Our Past Work" gallery. Options come from Settings > Slider Settings,
	// serialised into data-mpwpb-slider by
	// MPWPB_Static_Template::show_service_gallery() -- previously this called
	// mpwpb_active_carousel() with hard-coded values, so none of those settings
	// had any visible effect. Falls back to exactly those old hard-coded values
	// when the attribute is missing (older cached markup).
	var $gallerySection = $('.mpwpb-static-template .mpwpb-gallery-section');
	if ($gallerySection.length) {
		var sliderCfg = $gallerySection.data('mpwpb-slider') || {};
		var $track = $gallerySection.find('.mpwpb-owl-carousel');
		var itemsFor = function (key, fallback) {
			var n = parseInt(sliderCfg[key], 10);
			return n > 0 ? n : fallback;
		};

		// Only present for slider_type = "slider"; "Post Thumbnail" renders
		// .mpwpb-gallery-static-grid instead and needs no JS at all.
		if ($track.length && $.fn.owlCarousel) {
			var owlNav = sliderCfg.indicator_visible !== 'off' && sliderCfg.indicator_type !== 'image';
			var perView = itemsFor('items_desktop', 4);
			$track.owlCarousel({
				loop: sliderCfg.loop === 'on',
				margin: itemsFor('margin', 2),
				nav: owlNav,
				dots: false,
				autoplay: sliderCfg.autoplay === 'on',
				autoplayTimeout: itemsFor('autoplay_speed', 5000),
				autoplayHoverPause: true,
				// style_2 is the "showcase" look: one large slide at a time.
				items: sliderCfg.slider_style === 'style_2' ? 1 : perView,
				responsiveClass: true,
				responsive: {
					0: { items: sliderCfg.slider_style === 'style_2' ? 1 : itemsFor('items_mobile', 2) },
					600: { items: sliderCfg.slider_style === 'style_2' ? 1 : itemsFor('items_tablet', 2) },
					1000: { items: sliderCfg.slider_style === 'style_2' ? 1 : perView }
				}
			});
			$gallerySection.find('.mpwpb-gallery-nav .next').on('click', function () {
				$track.trigger('next.owl.carousel');
			});
			$gallerySection.find('.mpwpb-gallery-nav .prev').on('click', function () {
				$track.trigger('prev.owl.carousel');
			});

			// Thumbnail strip / dot indicators -- both jump the carousel and are
			// kept in sync with it as it moves (autoplay, drag, arrows).
			var $thumbs = $gallerySection.find('.mpwpb-gallery-thumb');
			var $dotsWrap = $gallerySection.find('.mpwpb-gallery-dots');
			if ($dotsWrap.length) {
				$gallerySection.find('.mpwpb-gallery-item').each(function (i) {
					$dotsWrap.append($('<button type="button" class="mpwpb-gallery-dot"></button>').attr('data-slide-target', i));
				});
			}
			var $dots = $dotsWrap.find('.mpwpb-gallery-dot');
			$gallerySection.on('click', '.mpwpb-gallery-thumb, .mpwpb-gallery-dot', function () {
				$track.trigger('to.owl.carousel', [parseInt($(this).attr('data-slide-target'), 10) || 0, 300]);
			});
			$track.on('changed.owl.carousel', function (e) {
				// e.item.index is the raw index inside owl's cloned item list;
				// relative() maps it back to a real slide number when loop is on.
				var current = e.relatedTarget ? e.relatedTarget.relative(e.item.index) : e.item.index;
				$thumbs.removeClass('is-active').eq(current).addClass('is-active');
				$dots.removeClass('is-active').eq(current).addClass('is-active');
			});
			$dots.first().addClass('is-active');
		}
	}

	// "Our Past Work" click-to-zoom -- opening/closing the lightbox itself is
	// the plugin's existing generic popup mechanism (data-target-popup/
	// data-popup, document-delegated in mp_global/assets/mp_style/
	// mpwpb_plugin_global.js); this only swaps the lightbox <img> to whichever
	// thumbnail was clicked and cycles prev/next through the same image set.
	// Clones are excluded: owl duplicates slides when loop is on, and counting
	// them would both inflate the "3 / 12" counter and misalign prev/next.
	var $galleryItems = $('.mpwpb-static-template .mpwpb-gallery-item').filter(function () {
		return !$(this).closest('.owl-item.cloned').length;
	});
	if ($galleryItems.length) {
		var $lightboxImg = $('.mpwpb-gallery-lightbox-img');
		var $lightboxCounter = $('.mpwpb-gallery-lightbox-counter');
		var $lightboxCaption = $('.mpwpb-gallery-lightbox-caption');
		var $lightboxThumbs = $('.mpwpb-gallery-lightbox-thumb');
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
			// .text() on the slide's own caption node -- never .html() -- so the
			// escaped server-rendered caption stays escaped.
			$lightboxCaption.text($item.find('.mpwpb-gallery-caption').text().trim());
			$lightboxThumbs.removeClass('is-active').eq(galleryIndex).addClass('is-active');
		};

		// Delegated so cloned slides (owl loop mode) open the lightbox too;
		// data-slide-index is authoritative because a clone's DOM position
		// does not match the image it shows.
		$(document).on('click', '.mpwpb-static-template .mpwpb-gallery-item', function () {
			var idx = parseInt($(this).attr('data-slide-index'), 10);
			showGalleryImage(isNaN(idx) ? $galleryItems.index(this) : idx);
		});
		$('.mpwpb-gallery-lightbox-next').on('click', function (e) {
			e.stopPropagation();
			showGalleryImage(galleryIndex + 1);
		});
		$('.mpwpb-gallery-lightbox-prev').on('click', function (e) {
			e.stopPropagation();
			showGalleryImage(galleryIndex - 1);
		});
		$('.mpwpb-gallery-lightbox-thumb').on('click', function (e) {
			e.stopPropagation();
			showGalleryImage(parseInt($(this).attr('data-slide-target'), 10) || 0);
		});
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
