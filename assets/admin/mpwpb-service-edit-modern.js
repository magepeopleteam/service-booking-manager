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

	function goStep(index) {
		if (index < 0) { index = 0; }
		if (index > order.length - 1) { index = order.length - 1; }
		cur = index;
		var name = order[cur];
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
	 *  Relocate the REAL WP content editor (#postdivrich) into the Basic
	 *  Information card's content slot — reusing the same editor instance
	 *  (TinyMCE, Add Media, Visual/Text tabs) rather than a duplicate, so
	 *  #content is submitted exactly once.
	 * ---------------------------------------------------------------- */
	(function relocateContentEditor() {
		var $slot = $root.find('[data-sme-content-slot]');
		var $editor = $('#postdivrich');
		if ($slot.length && $editor.length) {
			$editor.appendTo($slot);
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
			'MPWPB_Extra_Service_Modern': 'mpwpb_extra_service_active'
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

	// Initialise.
	goStep(0);

})(jQuery);
