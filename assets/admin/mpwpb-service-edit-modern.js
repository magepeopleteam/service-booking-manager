/**
 * Modern service editor — shell behaviour only (stepper, save, per-user
 * switch, toast, featured image).
 *
 * IMPORTANT: This script drives ONLY the modern shell. All section
 * functionality (repeaters, AJAX-saved Service/Extra-Service/Category/FAQ
 * cards, schedule table, icon/image picker) is still driven by the existing
 * admin scripts (mpwpb_admin.js / mpwpb_admin_settings.js / mpwpb_plugin_global.js),
 * which remain enqueued. We never touch those nodes here beyond relocating
 * the real WP title/content fields (never duplicating them).
 */
(function ($) {
	'use strict';

	var cfg = window.mpwpbSme || {};

	/* ---------------------------------------------------------------- *
	 *  Per-user editor switch (works in both classic & modern screens)
	 * ---------------------------------------------------------------- */
	function setUi(ui) {
		if (!cfg.ajax) {
			return;
		}
		$.post(cfg.ajax, {
			action: 'mpwpb_set_service_edit_ui',
			nonce: cfg.nonce,
			ui: ui
		}).always(function () {
			window.location.reload();
		});
	}

	$(document).on('click', '[data-sme-ui]', function (e) {
		e.preventDefault();
		setUi($(this).data('sme-ui'));
	});

	/* ---------------------------------------------------------------- *
	 *  Everything below requires the modern shell to be present
	 * ---------------------------------------------------------------- */
	var $root = $('#mpwpb-sme');
	if (!$root.length) {
		return;
	}

	// Full-screen takeover hook for the <html> element (CSS removes the
	// admin-bar padding only when this class is present).
	document.documentElement.classList.add('mpwpb-sme-html');

	var $steps = $root.find('.mpwpb-sme__step');
	var order = $steps.map(function () { return $(this).data('sme-go'); }).get();
	var total = parseInt($root.data('total'), 10) || order.length;
	var cur = 0;

	// Remembers which wizard step was last open, keyed per edited post (the
	// URL's post= query arg already tells different services apart), so a
	// plain page refresh reopens that step instead of always restarting the
	// wizard at General.
	function smeStepStorageKey() {
		return 'mpwpb_sme_step::' + location.pathname + location.search;
	}

	function goStep(index) {
		if (index < 0) { index = 0; }
		if (index > order.length - 1) { index = order.length - 1; }
		cur = index;
		var name = order[cur];
		try { sessionStorage.setItem(smeStepStorageKey(), name); } catch (e) {}
		// Expose the current step so CSS can gate the rail's progressive
		// summary cards per step.
		$root.attr('data-step', name);

		$root.find('.mpwpb-sme__panel').each(function () {
			$(this).toggleClass('active', $(this).data('sme-panel') === name);
		});
		$steps.each(function () {
			var i = parseInt($(this).data('sme-index'), 10);
			$(this).toggleClass('active', i === cur).toggleClass('done', i < cur);
		});
		$root.find('.mpwpb-sme__conn').each(function () {
			var ci = parseInt($(this).data('sme-conn'), 10);
			$(this).toggleClass('done', ci <= cur);
		});
		$root.find('[data-sme-stepof]').text('Step ' + (cur + 1) + ' of ' + total);
		$root.find('[data-sme-prev]').prop('disabled', cur === 0);

		var $next = $root.find('[data-sme-next]');
		$next.text(cur === order.length - 1 ? (cfg.updateTxt || 'Update') : (cfg.nextTxt || 'Next Step'));

		var top = $root.offset() ? $root.offset().top - 60 : 0;
		$('html, body').animate({ scrollTop: top }, 200);

		// Repaint the relocated content editor when returning to General.
		if (name === 'general') {
			setTimeout(function () {
				if (window.tinymce) {
					var ed = tinymce.get('content');
					if (ed) { ed.execCommand('mceRepaint'); }
				}
			}, 50);
		}
	}

	$steps.on('click', function () {
		goStep(parseInt($(this).data('sme-index'), 10));
	});
	$root.on('click', '[data-sme-prev]', function () {
		if (cur > 0) { goStep(cur - 1); }
	});
	$root.on('click', '[data-sme-next]', function () {
		if (cur < order.length - 1) {
			goStep(cur + 1);
		} else {
			submitForm();
		}
	});

	/* ---------------------------------------------------------------- *
	 *  Save — reuse WordPress' own Update/Publish button so post_status
	 *  and all hidden fields stay correct.
	 * ---------------------------------------------------------------- */
	function submitForm() {
		try { sessionStorage.setItem('mpwpbSmeSaved', '1'); } catch (e) {}
		toast(cfg.savingTxt || 'Saving…');
		var $publish = $('#publish');
		if (!$publish.length) { $publish = $('#save-post'); }
		if ($publish.length) {
			$publish.removeClass('disabled').prop('disabled', false).trigger('click');
		} else {
			var form = document.getElementById('post');
			if (form) {
				if (form.requestSubmit) { form.requestSubmit(); } else { form.submit(); }
			}
		}
	}
	$root.on('click', '[data-sme-save]', function (e) {
		e.preventDefault();
		submitForm();
	});

	/* ---------------------------------------------------------------- *
	 *  Split-button dropdown — one extra option, always the opposite of
	 *  whatever the primary button already does ("Update"/"Publish"),
	 *  plus "Classic editor". "Save as Draft"/"Switch to Draft" submits
	 *  the real #post form directly with WordPress' own core
	 *  'saveasdraft' flag set — the exact same flag its native Save Draft
	 *  button uses, so post_status ends up 'draft' regardless of the
	 *  primary action.
	 * ---------------------------------------------------------------- */
	var $split = $root.find('[data-sme-split]');
	var $splitToggle = $split.find('[data-sme-split-toggle]');
	var $splitMenu = $split.find('[data-sme-split-menu]');

	function closeSplitMenu() {
		$splitMenu.attr('hidden', true);
		$splitToggle.attr('aria-expanded', 'false');
	}
	function openSplitMenu() {
		$splitMenu.removeAttr('hidden');
		$splitToggle.attr('aria-expanded', 'true');
	}

	$splitToggle.on('click', function (e) {
		e.preventDefault();
		e.stopPropagation();
		if ($splitMenu.attr('hidden')) { openSplitMenu(); } else { closeSplitMenu(); }
	});
	$(document).on('click', function (e) {
		if ($split.length && !$split.is(e.target) && $split.has(e.target).length === 0) {
			closeSplitMenu();
		}
	});
	$(document).on('keydown', function (e) {
		if (e.key === 'Escape' || e.keyCode === 27) { closeSplitMenu(); }
	});

	function submitFormAs(status) {
		try { sessionStorage.setItem('mpwpbSmeSaved', '1'); } catch (e) {}
		toast(cfg.savingTxt || 'Saving…');
		var form = document.getElementById('post');
		if (!form) { return; }
		if (status === 'draft') {
			var draftField = form.querySelector('input[name="saveasdraft"]');
			if (!draftField) {
				draftField = document.createElement('input');
				draftField.type = 'hidden';
				draftField.name = 'saveasdraft';
				form.appendChild(draftField);
			}
			draftField.value = '1';
		}
		if (form.requestSubmit) { form.requestSubmit(); } else { form.submit(); }
	}
	$splitMenu.on('click', '[data-sme-save-as]', function (e) {
		e.preventDefault();
		closeSplitMenu();
		submitFormAs($(this).data('sme-save-as'));
	});
	$splitMenu.on('click', '[data-sme-ui]', function () {
		closeSplitMenu();
	});

	/* ---------------------------------------------------------------- *
	 *  Preview — proxy to WordPress' own hidden #post-preview link, whose
	 *  core click handler (wp-admin/js/post.js) saves an autosave first and
	 *  opens the preview in a reused tab, so unsaved changes show up too.
	 * ---------------------------------------------------------------- */
	$root.on('click', '[data-sme-preview]', function (e) {
		var $native = $('#post-preview');
		if ($native.length) {
			e.preventDefault();
			$native.trigger('click');
		}
	});

	/* ---------------------------------------------------------------- *
	 *  Service name <-> hidden WP #title sync (title box is CSS-hidden).
	 *  Two visual proxies (topbar + the inline "Title" field in the Basic
	 *  Information card) both mirror the one real #title input.
	 * ---------------------------------------------------------------- */
	var $title = $('#title');
	var $svcName = $('#mpwpb-sme-title'); // the editable topbar title
	var $svcNameInline = $('#mpwpb-sme-title-inline'); // inline "Title" field
	if ($title.length && ($svcName.length || $svcNameInline.length)) {
		if ($svcName.length && !$svcName.val() && $title.val()) {
			$svcName.val($title.val());
		}
		if ($svcNameInline.length && !$svcNameInline.val() && $title.val()) {
			$svcNameInline.val($title.val());
		}
		$svcName.add($svcNameInline).on('input', function () {
			var val = $(this).val();
			$title.val(val);
			$svcName.add($svcNameInline).not(this).val(val);
			$('#title-prompt-text').addClass('screen-reader-text');
		});
	}

	/* ---------------------------------------------------------------- *
	 *  Relocate the real "Service Overview" editor (rendered normally,
	 *  once, by the reused MPWPB_Service_Details::service_details() further
	 *  down this same step) up into the Basic Information card — a real DOM
	 *  node moved via appendTo(), not a duplicate render, so
	 *  mpwpb_service_overview_content still submits exactly once. The
	 *  now-empty heading + toggle wrapper left behind in the Service
	 *  Details card are discarded entirely (including the real classic
	 *  on/off checkbox) — the modern editor always submits Service Overview
	 *  as "on" via a hidden field placed in Basic Information instead
	 *  (Classic mode's own toggle is untouched and still fully functional
	 *  there).
	 * ---------------------------------------------------------------- */
	(function relocateServiceOverview() {
		var $overviewSection = $root.find('[data-sme-section="MPWPB_Service_Details"] .mpwpb-service-overview');
		var $contentSlot = $root.find('[data-sme-overview-slot]');
		if (!$overviewSection.length || !$contentSlot.length) {
			return;
		}
		var $toggleSection = $overviewSection.prev();
		var $headingSection = $toggleSection.prev();

		$overviewSection.appendTo($contentSlot);
		$toggleSection.remove();
		$headingSection.remove();
	})();

	/* ---------------------------------------------------------------- *
	 *  Relocate "Service Sub Title" (rendered normally, once, by the reused
	 *  MPWPB_General_Settings::general_settings() further down this same
	 *  step) up into the Basic Information card — only the real <textarea>
	 *  is moved (so mpwpb_shortcode_sub_title still submits exactly once);
	 *  the classic section wrapper + its own label text are decorative-only
	 *  once the field is gone, so it's removed to avoid an empty leftover
	 *  row. "Service Title" is discarded outright, not relocated — the
	 *  modern editor doesn't show it at all (Basic Information carries a
	 *  hidden field preserving its existing value instead, so nothing is
	 *  lost on save); Classic mode's own field is untouched.
	 * ---------------------------------------------------------------- */
	(function relocateServiceTitleFields() {
		var $card = $root.find('[data-sme-section="MPWPB_General_Settings"]');
		if (!$card.length) {
			return;
		}
		$card.find('.service-title').remove();

		var $subTitleSection = $card.find('.service-sub-title');
		var $subTitleSlot = $root.find('[data-sme-service-subtitle-slot]');
		if ($subTitleSection.length && $subTitleSlot.length) {
			$subTitleSection.find('textarea[name="mpwpb_shortcode_sub_title"]').appendTo($subTitleSlot);
			$subTitleSection.remove();
		}
	})();

	/* ---------------------------------------------------------------- *
	 *  Relocate a reused section's own on/off switch (e.g. Extra Service's
	 *  real "mpwpb_extra_service_active" checkbox) up into the card header,
	 *  next to the title — moving the real .roundSwitchLabel node (not a
	 *  copy), so the checkbox is still submitted exactly once regardless
	 *  of where in the DOM it visually sits.
	 * ---------------------------------------------------------------- */
	(function relocateHeaderToggles() {
		var sectionsWithHeaderToggle = {
			'MPWPB_Extra_Service_Modern': 'mpwpb_extra_service_active',
			'MPWPB_Service_Details': 'mpwpb_service_details_status',
			'MPWPB_Faq_Settings': 'mpwpb_faq_active'
		};
		Object.keys(sectionsWithHeaderToggle).forEach(function (sectionClass) {
			var $card = $root.find('[data-sme-section="' + sectionClass + '"]');
			var $slot = $card.find('[data-sme-header-actions]').first();
			var $toggle = $card.find('input[name="' + sectionsWithHeaderToggle[sectionClass] + '"]').closest('.roundSwitchLabel');
			if ($slot.length && $toggle.length) {
				$toggle.appendTo($slot);
			}
		});
	})();

	/* ---------------------------------------------------------------- *
	 *  Clean up what's left in the Service Details card after Service
	 *  Features and Service Review were relocated elsewhere and its own
	 *  toggle moved to the card header above:
	 *   1) the classic "Enable Service Details" row (label text + toggle,
	 *      now toggle-less) — only removed once confirmed empty (no
	 *      checkbox left inside), so this is a no-op if the header-toggle
	 *      relocation didn't run.
	 *   2) the "Service Details" sub-heading — now redundant with this
	 *      card's own title, since the editor below is the only content
	 *      left in the card. Safe to remove even though it's a
	 *      section.section: Service Features' (hidden, further up, never
	 *      removed) remains the true first-of-type in this card either way.
	 * ---------------------------------------------------------------- */
	(function cleanupServiceDetailsCard() {
		var $detailsSection = $root.find('[data-sme-section="MPWPB_Service_Details"] .mpwpb-service-details');
		if (!$detailsSection.length) {
			return;
		}
		var $toggleRow = $detailsSection.prev();
		if ($toggleRow.length && !$toggleRow.find('input[type="checkbox"]').length) {
			$toggleRow.remove();
		}
		var $headingSection = $detailsSection.prev();
		if ($headingSection.length && $headingSection.is('section.section')) {
			$headingSection.remove();
		}
	})();

	/* ---------------------------------------------------------------- *
	 *  The classic "Enable FAQ Section" row (label text + the toggle just
	 *  relocated to the FAQ card header above) would otherwise be left
	 *  behind as an empty leftover line. Only removed once confirmed empty
	 *  (no checkbox left inside), so it's a no-op if the header-toggle
	 *  relocation didn't run.
	 * ---------------------------------------------------------------- */
	(function cleanupFaqToggleRow() {
		var $faqSection = $root.find('[data-sme-section="MPWPB_Faq_Settings"] .mpwpb-faq-section');
		if (!$faqSection.length) {
			return;
		}
		var $toggleRow = $faqSection.prev();
		if ($toggleRow.length && !$toggleRow.find('input[type="checkbox"]').length) {
			$toggleRow.remove();
		}
	})();

	/* ---------------------------------------------------------------- *
	 *  Relocate the "Enable Recurring Bookings" / "Enable Staff Member Add"
	 *  toggles (Pro-only cards, rendered normally by the reused classic
	 *  methods) up into their own card headers, next to the title — same
	 *  pattern as the other header-toggle relocations above, just written
	 *  as dedicated functions since neither card has a uniquely-classed
	 *  content section to anchor a .prev() cleanup off of. Instead, the
	 *  toggle's original wrapping <section> is captured by reference
	 *  *before* the toggle itself is moved out of it, so it can be safely
	 *  discarded afterwards regardless of DOM order (it never held anything
	 *  but decorative label text once the switch is gone).
	 * ---------------------------------------------------------------- */
	function relocateToggleToCardHeader(sectionClass, inputName) {
		var $card = $root.find('[data-sme-section="' + sectionClass + '"]');
		var $slot = $card.find('[data-sme-header-actions]').first();
		if (!$card.length || !$slot.length) {
			return;
		}
		var $toggleInput = $card.find('input[name="' + inputName + '"]');
		var $toggleRow = $toggleInput.closest('section');
		var $toggle = $toggleInput.closest('.roundSwitchLabel');
		if ($toggle.length) {
			$toggle.appendTo($slot);
		}
		if ($toggleRow.length) {
			$toggleRow.remove();
		}
	}
	relocateToggleToCardHeader('MPWPB_Recurring_Booking_Settings', 'mpwpb_enable_recurring');
	relocateToggleToCardHeader('Staff_Member', 'mpwpb_staff_member_add');

	/* ---------------------------------------------------------------- *
	 *  Relocate the real "Service Features" repeater (rendered normally,
	 *  once, by the reused MPWPB_Service_Details::service_details()) out of
	 *  the Service Details card into its own "Service Feature Details"
	 *  card — a real DOM node moved via appendTo(), not a duplicate render,
	 *  so mpwpb_features[] still submits exactly once. Its "Enable Service
	 *  Features" toggle moves to the new card's header (next to the title)
	 *  instead of staying with Service Details, since it now gates this
	 *  card's content, not that one. The now-empty toggle row left behind
	 *  is discarded (decorative only, no form fields); its heading row is
	 *  left in place (already hidden via CSS's section.section:first-of-
	 *  type rule) rather than removed — removing it would make the *next*
	 *  section.section (Service Details' own heading) become the new
	 *  first-of-type and get hidden by that same rule instead.
	 * ---------------------------------------------------------------- */
	(function relocateServiceFeatures() {
		var $featuresSection = $root.find('[data-sme-section="MPWPB_Service_Details"] .mpwpb-service-features');
		var $contentSlot = $root.find('[data-sme-features-slot]');
		if (!$featuresSection.length || !$contentSlot.length) {
			return;
		}
		var $toggleRow = $featuresSection.prev();
		var $toggle = $toggleRow.find('input[name="mpwpb_features_status"]').closest('.roundSwitchLabel');
		var $headerSlot = $root.find('[data-sme-section="MPWPB_Service_Features_Modern"] [data-sme-header-actions]').first();

		if ($toggle.length && $headerSlot.length) {
			$toggle.appendTo($headerSlot);
		}
		$featuresSection.appendTo($contentSlot);
		$toggleRow.remove();
	})();

	/* ---------------------------------------------------------------- *
	 *  Relocate the real "Service Review" heading + its 3 fields (rating,
	 *  scale, text — rendered normally, once, by the reused
	 *  MPWPB_Service_Details::service_details()) into the General
	 *  Settings card (now titled "Customer Reviews", since Shortcode and
	 *  Service template are relocated out of it below) — whole <section>
	 *  nodes moved via appendTo() (each is entirely self-contained: one
	 *  label + one input, nothing else to leave behind), so
	 *  mpwpb_service_review_ratings / _rating_scale / _rating_text still
	 *  submit exactly once. Appended at the end of that card's body, after
	 *  its own (CSS-hidden) first section.section, so that heading stays
	 *  correctly "first" there — same first-of-type reasoning as the
	 *  Service Features relocation above.
	 * ---------------------------------------------------------------- */
	(function relocateServiceReview() {
		var $card = $root.find('[data-sme-section="MPWPB_Service_Details"]');
		var $generalBody = $root.find('[data-sme-section="MPWPB_General_Settings"] .mpwpb-sme__postfields-body');
		if (!$card.length || !$generalBody.length) {
			return;
		}
		var $ratingSection = $card.find('input[name="mpwpb_service_review_ratings"]').closest('section');
		var $scaleSection = $card.find('input[name="mpwpb_service_rating_scale"]').closest('section');
		var $textSection = $card.find('input[name="mpwpb_service_rating_text"]').closest('section');
		if (!$ratingSection.length) {
			return;
		}
		var $headingSection = $ratingSection.prev();

		$headingSection.appendTo($generalBody);
		$ratingSection.appendTo($generalBody);
		$scaleSection.appendTo($generalBody);
		$textSection.appendTo($generalBody);
	})();

	/* ---------------------------------------------------------------- *
	 *  Relocate the real "Add To Cart Form Shortcode" display + "Service
	 *  template" <select> (rendered normally, once, by the reused
	 *  MPWPB_General_Settings::general_settings()) into their own rail
	 *  card in the sticky sidebar, right after Featured Image — whole
	 *  <section> nodes moved via appendTo() (.shortcode / .service-template
	 *  are each self-contained: one label + one control), so mpwpb_template
	 *  still submits exactly once.
	 * ---------------------------------------------------------------- */
	(function relocateShortcodeAndTemplate() {
		var $card = $root.find('[data-sme-section="MPWPB_General_Settings"]');
		var $shortcodeSlot = $root.find('[data-sme-shortcode-slot]');
		var $templateSlot = $root.find('[data-sme-template-slot]');
		if (!$card.length) {
			return;
		}
		var $shortcodeSection = $card.find('.shortcode');
		var $templateSection = $card.find('.service-template');
		if ($shortcodeSection.length && $shortcodeSlot.length) {
			$shortcodeSection.appendTo($shortcodeSlot);
		}
		if ($templateSection.length && $templateSlot.length) {
			$templateSection.appendTo($templateSlot);
		}
	})();

	/* ---------------------------------------------------------------- *
	 *  Live toast feedback
	 * ---------------------------------------------------------------- */
	var toastTimer;
	function toast(msg) {
		var $t = $root.find('[data-sme-toast]');
		if (!$t.length) { return; }
		$t.find('[data-sme-toast-msg]').text(msg);
		$t.addClass('show');
		clearTimeout(toastTimer);
		toastTimer = setTimeout(function () { $t.removeClass('show'); }, 2200);
	}

	// Confirm a successful save ONCE after WordPress reloads the editor.
	var justSaved = false;
	try { justSaved = sessionStorage.getItem('mpwpbSmeSaved') === '1'; } catch (e) {}
	if (justSaved) {
		try { sessionStorage.removeItem('mpwpbSmeSaved'); } catch (e) {}
		toast(cfg.savedTxt || 'Saved');
	}

	/* ---------------------------------------------------------------- *
	 *  Featured image (WP post thumbnail) uploader in the preview rail
	 * ---------------------------------------------------------------- */
	function setHero(id, url) {
		var $img = $('#mpwpb-sme-hero-img');
		var $ph = $root.find('.mpwpb-sme__rail-hero-ph');
		$('#mpwpb-sme-thumbnail').val(id || '');
		if (url) {
			$img.attr('src', url).show();
			$ph.hide();
			$root.find('[data-sme-feat-remove]').show();
		} else {
			$img.attr('src', '').hide();
			$ph.show();
			$root.find('[data-sme-feat-remove]').hide();
		}
		$root.find('[data-sme-feat-set]').text(url ? 'Change image' : 'Set image');
	}
	var featFrame;
	$root.on('click', '[data-sme-feat-set]', function (e) {
		e.preventDefault();
		if (typeof wp === 'undefined' || !wp.media) { return; }
		if (featFrame) { featFrame.open(); return; }
		featFrame = wp.media({ title: (cfg.featTitle || 'Select featured image'), button: { text: (cfg.featBtn || 'Use image') }, library: { type: 'image' }, multiple: false });
		featFrame.on('select', function () {
			var a = featFrame.state().get('selection').first().toJSON();
			var url = (a.sizes && a.sizes.medium) ? a.sizes.medium.url : a.url;
			setHero(a.id, url);
			toast(cfg.featSet || 'Featured image set');
		});
		featFrame.open();
	});
	$root.on('click', '[data-sme-feat-remove]', function (e) {
		e.preventDefault();
		setHero('', '');
		toast(cfg.featRemoved || 'Featured image removed');
	});

	/* ---------------------------------------------------------------- *
	 *  Skeleton / shimmer placeholder loaders for each panel/rail card.
	 *  Skeleton HTML is server-rendered in PHP so it shows immediately on
	 *  page load — JS only removes the --loading class per panel.
	 * ---------------------------------------------------------------- */
	(function initSkeletons() {
		function removeLoading(el) {
			var ov = el.querySelector('.mpwpb-sme__skel-ov');
			if (ov) {
				ov.classList.add('out');
				setTimeout(function () {
					el.classList.remove('mpwpb-sme__panel--loading', 'mpwpb-sme__rail-card--loading');
				}, 260);
			} else {
				el.classList.remove('mpwpb-sme__panel--loading', 'mpwpb-sme__rail-card--loading');
			}
		}

		$root.find('.mpwpb-sme__panel').each(function () {
			var panel = this;
			var done = false;
			var obs = new MutationObserver(function () {
				if (!done && panel.classList.contains('active')) {
					done = true;
					obs.disconnect();
					setTimeout(function () { removeLoading(panel); }, 400);
				}
			});
			obs.observe(panel, { attributes: true, attributeFilter: ['class'] });
		});

		setTimeout(function () {
			var panel = $root.find('.mpwpb-sme__panel.active')[0];
			if (panel) { removeLoading(panel); }

			$root.find('.mpwpb-sme__rail-card--loading').each(function () {
				removeLoading(this);
			});
		}, 500);
	})();

	/* ---------------------------------------------------------------- *
	 *  Payment Method modal -- reuses the real Settings > Payment Method
	 *  panel (rendered server-side into #mpwpb-sme-payment-modal-body).
	 *  Its own toggle/accordion/gateway-save JS is part of that reused
	 *  markup and needs no wiring here. The one thing it can't do on its
	 *  own is save: its "Save Changes" button is a plain submit_button()
	 *  expecting a real <form action="options.php"> ancestor (which it
	 *  has on the actual Settings page) -- here it would otherwise submit
	 *  this screen's #post form instead, so that click is intercepted and
	 *  redirected to a dedicated AJAX action.
	 * ---------------------------------------------------------------- */
	var $paymentModal = $('#mpwpb-sme-payment-modal');
	if ($paymentModal.length) {
		$(document).on('click', '[data-sme-payment-modal-open]', function (e) {
			e.preventDefault();
			$paymentModal.css('display', 'flex');
		});
		$(document).on('click', '[data-sme-payment-modal-close]', function () {
			$paymentModal.hide();
		});
		$paymentModal.on('click', function (e) {
			if (e.target === this) { $paymentModal.hide(); }
		});
		$(document).on('keydown', function (e) {
			if ((e.key === 'Escape' || e.keyCode === 27) && $paymentModal.is(':visible')) {
				$paymentModal.hide();
			}
		});
		$(document).on('click', '#mpwpb-sme-payment-modal-body #submit', function (e) {
			e.preventDefault();
			if (!cfg.ajax) { return; }
			var $btn = $(this);
			var $status = $btn.next('.mpwpb-sme-payment-save-status');
			if (!$status.length) {
				$status = $('<span class="mpwpb-sme-payment-save-status" style="margin-left:10px;"></span>');
				$btn.after($status);
			}
			var data = $('#mpwpb-sme-payment-modal-body').find(':input[name^="mpwpb_payment_method_settings"]').serializeArray();
			data.push({ name: 'action', value: 'mpwpb_save_payment_method_settings' });
			data.push({ name: 'nonce', value: cfg.paymentNonce });
			$btn.prop('disabled', true);
			$status.css('color', '').text(cfg.savingTxt || 'Saving…');
			$.post(cfg.ajax, data).done(function (resp) {
				if (resp && resp.success) {
					$status.css('color', '#1c9a5b').text((resp.data && resp.data.message) || cfg.paymentSaved || 'Saved.');
					setTimeout(function () { window.location.reload(); }, 700);
				} else {
					$status.css('color', '#b32d2e').text((resp && resp.data && resp.data.message) || cfg.paymentError || 'Something went wrong.');
				}
			}).fail(function () {
				$status.css('color', '#b32d2e').text(cfg.paymentError || 'Something went wrong.');
			}).always(function () {
				$btn.prop('disabled', false);
			});
		});
	}

	// Initialise -- resume the last-viewed step for this post, if any.
	var smeStartIndex = 0;
	try {
		var smeRememberedIndex = order.indexOf(sessionStorage.getItem(smeStepStorageKey()));
		if (smeRememberedIndex > -1) { smeStartIndex = smeRememberedIndex; }
	} catch (e) {}
	goStep(smeStartIndex);

})(jQuery);
