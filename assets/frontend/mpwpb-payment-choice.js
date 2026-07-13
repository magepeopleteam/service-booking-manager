jQuery(function ($) {
	$(document).on('change', '[data-mpwpb-payment-choice-radio]', function () {
		var $radio = $(this);
		$radio.closest('[data-mpwpb-payment-choice-wrap]').find('[data-mpwpb-payment-choice-radio]').prop('disabled', true);
		$.post(mpwpb_ajax.ajax_url, {
			action: 'mpwpb_set_payment_choice',
			nonce: mpwpb_ajax.nonce,
			choice: $radio.val()
		}).done(function (response) {
			if (response && response.success) {
				if (response.data && response.data.html) {
					$('[data-mpwpb-recap-root]').first().replaceWith(response.data.html);
				} else {
					// Real WooCommerce checkout: let WC recalculate the cart/totals
					// (and its own re-rendered order review, including this radio
					// box) through the updated mpwpb_payment_choice picked up by
					// before_calculate_totals().
					$(document.body).trigger('update_checkout');
				}
			}
		}).always(function () {
			$('[data-mpwpb-payment-choice-radio]').prop('disabled', false);
		});
	});
});
