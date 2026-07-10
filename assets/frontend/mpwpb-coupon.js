jQuery(function ($) {
	function showMessage($box, message, isError) {
		var $msg = $box.find('[data-mpwpb-coupon-message]');
		$msg.text(message || '')
			.toggle(!!message)
			.toggleClass('mpwpb-coupon-message-error', !!isError);
	}

	function currentEmail() {
		var $field = $('input[name="mpwpb_billing_email"], #billing_email').first();
		return $field.length ? $field.val() : '';
	}

	$(document).on('click', '[data-mpwpb-apply-coupon]', function (e) {
		e.preventDefault();
		var $btn = $(this);
		var $box = $btn.closest('[data-mpwpb-coupon-box]');
		var code = $box.find('[data-mpwpb-coupon-input]').val();
		if (!code) {
			return;
		}
		$btn.prop('disabled', true);
		$.post(mpwpb_ajax.ajax_url, {
			action: 'mpwpb_apply_coupon',
			nonce: mpwpb_ajax.nonce,
			code: code,
			email: currentEmail()
		}).done(function (response) {
			if (response && response.success) {
				if (response.data && response.data.html) {
					$('[data-mpwpb-recap-root]').first().replaceWith(response.data.html);
				} else {
					// Real WooCommerce checkout: let WC recalculate the cart/totals
					// (and its own re-rendered order review) through the updated
					// mpwpb_discount_amount picked up by before_calculate_totals().
					$(document.body).trigger('update_checkout');
				}
			} else {
				showMessage($box, (response && response.data && response.data.message) || 'Unable to apply coupon.', true);
			}
		}).fail(function () {
			showMessage($box, 'Unable to apply coupon. Please try again.', true);
		}).always(function () {
			$btn.prop('disabled', false);
		});
	});

	$(document).on('click', '[data-mpwpb-remove-coupon]', function (e) {
		e.preventDefault();
		var $btn = $(this);
		var $box = $btn.closest('[data-mpwpb-coupon-box]');
		$btn.prop('disabled', true);
		$.post(mpwpb_ajax.ajax_url, {
			action: 'mpwpb_remove_coupon',
			nonce: mpwpb_ajax.nonce
		}).done(function (response) {
			if (response && response.success) {
				if (response.data && response.data.html) {
					$('[data-mpwpb-recap-root]').first().replaceWith(response.data.html);
				} else {
					$(document.body).trigger('update_checkout');
				}
			} else {
				showMessage($box, (response && response.data && response.data.message) || 'Unable to remove coupon.', true);
			}
		}).always(function () {
			$btn.prop('disabled', false);
		});
	});
});
