/**
 * Visual-reskin behavior, plus one real bug fix this reskin surfaced
 * (see the second block below) -- neither is a selection/cart/checkout
 * mechanism of its own; the actual booking/order flow stays entirely
 * in mpwpb_registration.js.
 *
 * Collapses the inline checkout's coupon box
 * (Frontend/MPWPB_Inline_WC_Checkout.php) to a plain text trigger by
 * default, matching WooCommerce's own cart-page "Have a coupon?"
 * convention -- see the matching CSS in mpwpb-booking-widget-reskin.css
 * for why this needed real JS rather than a pure CSS override: the
 * panel has no existing open/closed state to key off, and this markup
 * is AJAX-injected into the drawer each time the checkout step loads,
 * so the click handler below is delegated on document rather than
 * bound directly to an element that may not exist yet.
 */
(function ($) {
	'use strict';

	$(document).on('click', '.mpwpb-inline-coupon > label', function (e) {
		// The coupon <input> itself is NOT inside this <label> (it's a
		// sibling in the following <div>), so a real click on the label
		// text is never "for" the input and never needs to fall through.
		e.preventDefault();
		$(this).closest('.mpwpb-inline-coupon').toggleClass('is-open');
	});

	/**
	 * Bug fix: "Continue" on the billing stage
	 * (mpwpb_registration.js:1235-1244) always reported every required
	 * field as incomplete, no matter what was actually typed.
	 *
	 * Root cause (WooCommerce core, assets/js/frontend/checkout.js):
	 * `wc_checkout_form.$checkout_form` is set ONCE, from
	 * `$('form.checkout')`, at the moment checkout.js first parses --
	 * on this booking-widget page, long before the drawer's own
	 * `form.checkout` exists at all (it's AJAX-injected later, when
	 * the Checkout step loads). Its field-validation listener
	 * (`'input validate change focusout'` -> `validate_field`, which is
	 * what actually clears `.woocommerce-invalid` once a field is
	 * filled in) gets bound to that now-permanently-empty reference.
	 * The `init_checkout` event this plugin already triggers after
	 * injecting the form (mpwpb_registration.js:1166) only recalculates
	 * totals (`checkout.js`'s own `init_checkout` handler just fires
	 * `update_checkout`) -- it never re-attaches that listener to the
	 * new form. So every required field keeps whatever `.woocommerce-
	 * invalid` state it was rendered with on page load, forever, and
	 * `.trigger('validate')` reaches no working handler at all.
	 *
	 * Fix: bind the same four events ourselves, delegated on `document`
	 * (so it's immune to the empty-reference problem), replicating just
	 * the required-field and email-format checks that actually gate
	 * "Continue" -- not WC's full validate_field (phone-pattern
	 * strictness etc. isn't needed here since billing_phone isn't a
	 * required field in this form). jQuery fires every handler bound
	 * to an event+selector, from any file, so this runs in addition to
	 * (not instead of) WC's own inert one, and completes synchronously
	 * before mpwpb_registration.js's own .woocommerce-invalid:visible
	 * check on the very next line.
	 */
	var EMAIL_PATTERN = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

	function mpwpbValidateInlineBillingField(e) {
		var $field = $(this);
		var $row = $field.closest('.form-row');
		if (!$row.length) {
			return;
		}

		if (e.type === 'input') {
			$field.removeAttr('aria-invalid');
			$row.removeClass(
				'woocommerce-invalid woocommerce-invalid-required-field woocommerce-invalid-email woocommerce-validated'
			);
			return;
		}

		if (e.type !== 'validate' && e.type !== 'change' && e.type !== 'focusout') {
			return;
		}

		var isCheckbox = $field.attr('type') === 'checkbox';
		var value = isCheckbox ? null : $.trim($field.val());
		var validated = true;

		if ($row.is('.validate-required')) {
			var empty = isCheckbox ? !$field.is(':checked') : value === '';
			if (empty) {
				validated = false;
			}
		}

		if (validated && $row.is('.validate-email') && value) {
			if (!EMAIL_PATTERN.test(value)) {
				validated = false;
			}
		}

		if (validated) {
			$field.removeAttr('aria-invalid');
			$row
				.removeClass('woocommerce-invalid woocommerce-invalid-required-field woocommerce-invalid-email')
				.addClass('woocommerce-validated');
		} else {
			$field.attr('aria-invalid', 'true');
			$row
				.removeClass('woocommerce-validated')
				.addClass('woocommerce-invalid woocommerce-invalid-required-field');
		}
	}

	$(document).on(
		'input validate change focusout',
		'.mpwpb-inline-wc-checkout-content .input-text, .mpwpb-inline-wc-checkout-content select, .mpwpb-inline-wc-checkout-content input:checkbox',
		mpwpbValidateInlineBillingField
	);
})(jQuery);

/**
 * Checkout payment block (inline WooCommerce checkout in the booking
 * drawer): heading, payment methods as cards (icon, name, short line,
 * the gateway's description inside the picked card), "Total to pay",
 * "Place order · <total>" and a trust line. WooCommerce's own radios,
 * gateway fields, terms checkbox and #place_order are kept as they are
 * -- this only adds structure around them. WooCommerce re-renders
 * #payment on every update_checkout, so decoratePayment() re-runs then.
 */
(function ($) {
	'use strict';
	var t = window.mpwpbPay || {};
	var tags = t.tags || {};
	var ICONS = {
		cash: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/><path d="M6 10v4M18 10v4"/></svg>',
		bank: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 10l9-6 9 6"/><path d="M5 10v8M10 10v8M14 10v8M19 10v8"/><path d="M3 20h18"/></svg>',
		doc: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="6" width="18" height="12" rx="2"/><path d="M7 14h6M7 10h10"/></svg>',
		wallet: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 7a2 2 0 0 1 2-2h12v4"/><rect x="3" y="7" width="18" height="12" rx="2"/><circle cx="16" cy="13" r="1.2"/></svg>',
		card: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20M6 15h4"/></svg>'
	};
	function gatewayKind(id) {
		if (id === 'cod') { return { icon: 'cash', tag: tags.cod }; }
		if (id === 'bacs') { return { icon: 'bank', tag: tags.bacs }; }
		if (id === 'cheque') { return { icon: 'doc', tag: tags.cheque }; }
		if (/paypal|ppcp/.test(id)) { return { icon: 'wallet', tag: tags.paypal }; }
		if (/stripe|card|square|braintree|authorize/.test(id)) { return { icon: 'card', tag: tags.card }; }
		return { icon: 'card', tag: '' };
	}
	function orderTotal($form) {
		var $amt = $form.find('.order-total .woocommerce-Price-amount').last();
		return $.trim($amt.text());
	}
	function syncSelected($pay) {
		$pay.find('li.wc_payment_method').each(function () {
			$(this).toggleClass('is-selected', $(this).find('input[name="payment_method"]').is(':checked'));
		});
	}
	function syncButton($pay) {
		var $btn = $pay.find('#place_order');
		if (!$btn.length) { return; }
		var $checked = $pay.find('input[name="payment_method"]:checked');
		var base = $checked.attr('data-order_button_text') || $btn.attr('data-value') || t.placeOrder || 'Place order';
		var total = orderTotal($pay.closest('form'));
		var text = total ? base + ' · ' + total : base;
		if ($btn.text() !== text) { $btn.text(text); }
		$pay.find('[data-mpwpb-pay-total]').text(total);
		$pay.find('.mpwpb-pay2__total').toggle(!!total);
	}
	function decoratePayment() {
		$('.mpwpb-inline-wc-checkout-content #payment').each(function () {
			var $pay = $(this);
			if (!$pay.hasClass('mpwpb-pay2')) {
				$pay.addClass('mpwpb-pay2');
			}
			if (!$pay.children('.mpwpb-pay2__head').length) {
				$('<div class="mpwpb-pay2__head"><span class="mpwpb-pay2__eyebrow"></span><span class="mpwpb-pay2__title"></span></div>')
					.find('.mpwpb-pay2__eyebrow').text(t.eyebrow || 'Payment').end()
					.find('.mpwpb-pay2__title').text(t.heading || 'How would you like to pay?').end()
					.prependTo($pay);
			}
			$pay.find('li.wc_payment_method').each(function () {
				var $li = $(this);
				if ($li.data('mpwpbPay')) { return; }
				$li.data('mpwpbPay', true);
				var id = String($li.find('input[name="payment_method"]').val() || '');
				var kind = gatewayKind(id);
				var $label = $li.children('label').first();
				var $name = $('<span class="mpwpb-pay2__name"></span>').append($label.contents());
				var $text = $('<span class="mpwpb-pay2__text"></span>').append($name);
				if (kind.tag) { $('<span class="mpwpb-pay2__tag"></span>').text(kind.tag).appendTo($text); }
				$label.append('<span class="mpwpb-pay2__radio" aria-hidden="true"></span>')
					.append($('<span class="mpwpb-pay2__icon" aria-hidden="true"></span>').html(ICONS[kind.icon]))
					.append($text);
			});
			var $place = $pay.find('.form-row.place-order');
			if ($place.length && !$place.children('.mpwpb-pay2__total').length) {
				$('<div class="mpwpb-pay2__total"><span></span><strong data-mpwpb-pay-total></strong></div>')
					.find('span').text(t.totalLabel || 'Total to pay').end()
					.prependTo($place);
			}
			var $btn = $pay.find('#place_order');
			if ($btn.length && !$btn.next('.mpwpb-pay2__trust').length) {
				$('<div class="mpwpb-pay2__trust"></div>')
					.append($('<span></span>').text(t.trustSecure || 'Secure checkout'))
					.append('<span aria-hidden="true">·</span>')
					.append($('<span></span>').text(t.trustReschedule || 'Free rescheduling up to 24h'))
					.insertAfter($btn);
			}
			// Privacy note goes below the button -- unless the terms checkbox
			// lives in that wrapper, which must stay before it.
			var $terms = $pay.find('.woocommerce-terms-and-conditions-wrapper');
			if ($terms.length && !$terms.find('input[type="checkbox"]').length && !$terms.hasClass('is-moved')) {
				$terms.addClass('is-moved').insertAfter($pay.find('.mpwpb-pay2__trust'));
			}
			syncSelected($pay);
			syncButton($pay);
		});
	}

	var timer = null;
	function schedule() {
		clearTimeout(timer);
		timer = setTimeout(decoratePayment, 30);
	}
	$(document.body).on('updated_checkout payment_method_selected', schedule);
	$(document).on('change', '.mpwpb-inline-wc-checkout-content input[name="payment_method"]', schedule);
	if (window.MutationObserver) {
		new MutationObserver(function (records) {
			for (var i = 0; i < records.length; i++) {
				var r = records[i];
				if (r.target.closest && r.target.closest('.mpwpb-inline-wc-checkout-content')) {
					// Ignore our own edits to #place_order's text.
					if (r.target.id === 'place_order' || (r.target.parentNode && r.target.parentNode.id === 'place_order')) { continue; }
					schedule();
					return;
				}
			}
		}).observe(document.body, { childList: true, subtree: true });
	}
	$(schedule);
}(jQuery));
