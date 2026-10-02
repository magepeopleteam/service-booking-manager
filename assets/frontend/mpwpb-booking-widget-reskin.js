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
