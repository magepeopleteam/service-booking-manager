/**
 * Modern Coupon Editor shell — proxies WordPress's own real (CSS-hidden)
 * Publish/Update/Save-Draft controls so save/publish/draft all keep
 * working exactly as before; tab switching itself needs no code here,
 * it's handled by the existing shared mp_global/assets/mp_style/
 * mpwpb_plugin_global.js delegated on [data-tabs-target].
 */
(function ($) {
	"use strict";

	$(function () {
		var $root = $('#mpwpb-cem');
		if (!$root.length) {
			return;
		}

		/* ---------------------------------------------------------- *
		 *  Coupon name <-> hidden WP #title sync (title box is
		 *  CSS-hidden, not removed, so post_title still submits).
		 * ---------------------------------------------------------- */
		var $title = $('#title');
		var $name = $('#mpwpb-cem-title');
		if ($title.length && $name.length) {
			if (!$name.val() && $title.val()) {
				$name.val($title.val());
			}
			$name.on('input', function () {
				var val = $(this).val();
				$title.val(val);
				$('#title-prompt-text').addClass('screen-reader-text');
			});
		}

		/* ---------------------------------------------------------- *
		 *  Save — proxy WordPress's own Update/Publish button so
		 *  post_status and every other hidden field stay correct.
		 * ---------------------------------------------------------- */
		function submitForm() {
			var $publish = $('#publish');
			if (!$publish.length) {
				$publish = $('#save-post');
			}
			if ($publish.length) {
				$publish.removeClass('disabled').prop('disabled', false).trigger('click');
				return;
			}
			var form = document.getElementById('post');
			if (form) {
				if (form.requestSubmit) {
					form.requestSubmit();
				} else {
					form.submit();
				}
			}
		}
		$root.on('click', '[data-cem-save]', function (e) {
			e.preventDefault();
			submitForm();
		});

		/* ---------------------------------------------------------- *
		 *  Save as Draft / Switch to Draft — submits the real #post
		 *  form directly with WordPress's own core 'saveasdraft' flag,
		 *  the same flag its native Save Draft button uses. Works
		 *  regardless of the coupon's current status (unlike #save-post,
		 *  which WordPress only renders when not already published).
		 * ---------------------------------------------------------- */
		function submitFormAsDraft() {
			var form = document.getElementById('post');
			if (!form) {
				return;
			}
			var draftField = form.querySelector('input[name="saveasdraft"]');
			if (!draftField) {
				draftField = document.createElement('input');
				draftField.type = 'hidden';
				draftField.name = 'saveasdraft';
				form.appendChild(draftField);
			}
			draftField.value = '1';
			if (form.requestSubmit) {
				form.requestSubmit();
			} else {
				form.submit();
			}
		}
		$root.on('click', '[data-cem-save-as="draft"]', function (e) {
			e.preventDefault();
			closeSplitMenu();
			submitFormAsDraft();
		});

		/* ---------------------------------------------------------- *
		 *  Split-button dropdown (Save Draft / Move to Trash).
		 * ---------------------------------------------------------- */
		var $split = $root.find('[data-cem-split]');
		var $splitToggle = $split.find('[data-cem-split-toggle]');
		var $splitMenu = $split.find('[data-cem-split-menu]');

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
			if ($splitMenu.attr('hidden')) {
				openSplitMenu();
			} else {
				closeSplitMenu();
			}
		});
		$(document).on('click', function (e) {
			if ($split.length && !$split.is(e.target) && $split.has(e.target).length === 0) {
				closeSplitMenu();
			}
		});
		$(document).on('keydown', function (e) {
			if (e.key === 'Escape' || e.keyCode === 27) {
				closeSplitMenu();
			}
		});
	});
})(jQuery);
