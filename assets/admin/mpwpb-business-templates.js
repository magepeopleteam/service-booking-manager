/**
 * "One-Click Business Templates" picker modal for the Service List admin
 * page. Localized data comes from mpwpbBt (see
 * Admin/MPWPB_Business_Templates_Import.php::enqueue_assets).
 */
(function ($) {
	'use strict';

	if (typeof mpwpbBt === 'undefined') {
		return;
	}

	function esc(str) {
		return $('<div>').text(str == null ? '' : str).html();
	}

	function cardHtml(tpl) {
		return '<button type="button" class="mpwpb-bt__tpl-card" data-bt-key="' + esc(tpl.key) + '">'
			+ '<span class="mpwpb-bt__tpl-icon" style="background:' + esc(tpl.color) + '1a;color:' + esc(tpl.color) + '"><span class="dashicons ' + esc(tpl.icon) + '"></span></span>'
			+ '<span class="mpwpb-bt__tpl-name">' + esc(tpl.label) + '</span>'
			+ '<span class="mpwpb-bt__tpl-count">' + esc(tpl.serviceCount) + ' ' + esc(mpwpbBt.i18n.servicesIncluded || 'Services Included') + '</span>'
			+ '<span class="mpwpb-bt__tpl-select"><span class="dashicons dashicons-plus-alt2"></span>' + esc(mpwpbBt.i18n.select) + '</span>'
			+ '</button>';
	}

	function renderModal() {
		var cards = mpwpbBt.templates.map(cardHtml).join('');
		var html = '<div class="mpwpb-bt__overlay" id="mpwpb-bt-overlay">'
			+ '<div class="mpwpb-bt__modal" role="dialog" aria-modal="true">'
			+ '<div class="mpwpb-bt__head">'
			+ '<span class="mpwpb-bt__title">' + esc(mpwpbBt.i18n.title) + '</span>'
			+ '<button type="button" class="mpwpb-bt__close" data-bt-close><span class="dashicons dashicons-no-alt"></span></button>'
			+ '</div>'
			+ '<div class="mpwpb-bt__body">'
			+ '<p class="mpwpb-bt__intro">' + esc(mpwpbBt.i18n.intro) + '</p>'
			+ '<div class="mpwpb-bt__grid">' + cards + '</div>'
			+ '<p class="mpwpb-bt__error" data-bt-error style="display:none;"></p>'
			+ '</div>'
			+ '</div>'
			+ '</div>';
		$('#mpwpb-bt-root').html(html);
	}

	function closeModal() {
		$('#mpwpb-bt-root').empty();
	}

	function showError(message) {
		$('[data-bt-error]').text(message).show();
	}

	function importTemplate($card) {
		var key = $card.data('bt-key');
		var $grid = $card.closest('.mpwpb-bt__grid');
		$grid.find('.mpwpb-bt__tpl-card').prop('disabled', true).addClass('is-busy');
		$card.find('.mpwpb-bt__tpl-select').html('<span class="dashicons dashicons-update mpwpb-bt__spin"></span>' + esc(mpwpbBt.i18n.importing));

		$.post(mpwpbBt.ajaxUrl, {
			action: mpwpbBt.action,
			nonce: mpwpbBt.nonce,
			template_key: key
		}).done(function (response) {
			if (response && response.success && response.data && response.data.edit_url) {
				window.location.href = response.data.edit_url;
				return;
			}
			var message = (response && response.data && response.data.message) || mpwpbBt.i18n.error;
			showError(message);
			$grid.find('.mpwpb-bt__tpl-card').prop('disabled', false).removeClass('is-busy');
			renderModal_resetCard($card, key);
		}).fail(function () {
			showError(mpwpbBt.i18n.error);
			$grid.find('.mpwpb-bt__tpl-card').prop('disabled', false).removeClass('is-busy');
			renderModal_resetCard($card, key);
		});
	}

	function renderModal_resetCard($card) {
		$card.find('.mpwpb-bt__tpl-select').html('<span class="dashicons dashicons-plus-alt2"></span>' + esc(mpwpbBt.i18n.select));
	}

	$(document).on('click', '#mpwpb-open-business-templates', function (e) {
		e.preventDefault();
		renderModal();
	});

	$(document).on('click', '[data-bt-close]', function () {
		closeModal();
	});

	$(document).on('click', '#mpwpb-bt-overlay', function (e) {
		if (e.target === this) {
			closeModal();
		}
	});

	$(document).on('keydown', function (e) {
		if (e.key === 'Escape') {
			closeModal();
		}
	});

	$(document).on('click', '.mpwpb-bt__tpl-card', function () {
		importTemplate($(this));
	});

}(jQuery));
