(function () {
	var wp = window.wp;
	var wc = window.wc;
	if (!wp || !wp.plugins || !wp.element || !wp.data || !wc || !wc.blocksCheckout) {
		return;
	}

	var createElement = wp.element.createElement;
	var useState = wp.element.useState;
	var useSelect = wp.data.useSelect;
	var ExperimentalDiscountsMeta = wc.blocksCheckout.ExperimentalDiscountsMeta;
	var extensionCartUpdate = wc.blocksCheckout.extensionCartUpdate;
	var i18n = window.mpwpbCouponBlockI18n || {};
	if (!ExperimentalDiscountsMeta || !extensionCartUpdate) {
		return;
	}

	function CouponFill() {
		var coupon = useSelect(function (select) {
			var store = select('wc/store/cart');
			var cart = store && store.getCartData ? store.getCartData() : null;
			return cart && cart.extensions ? cart.extensions.mpwpb_coupon : null;
		}, []);
		var codeState = useState('');
		var code = codeState[0];
		var setCode = codeState[1];
		var busyState = useState(false);
		var busy = busyState[0];
		var setBusy = busyState[1];
		var errorState = useState('');
		var error = errorState[0];
		var setError = errorState[1];

		if (!coupon || !coupon.enabled) {
			return null;
		}

		function update(action) {
			if (busy || (action === 'apply' && !code.trim())) {
				return;
			}
			setBusy(true);
			setError('');
			extensionCartUpdate({
				namespace: 'mpwpb_coupon',
				data: {action: action, code: code.trim()}
			}).then(function () {
				setCode('');
				setBusy(false);
			}).catch(function (reason) {
				setError((reason && reason.message) || i18n.error || 'Unable to update coupon.');
				setBusy(false);
			});
		}

		function submit(event) {
			event.preventDefault();
			update('apply');
		}

		return createElement('section', {className: 'mpwpb-coupon-block'},
			createElement('h3', null, i18n.title || 'Booking coupon'),
			coupon.code ?
				createElement('div', {className: 'mpwpb-coupon-block__applied'},
					createElement('div', null,
						createElement('strong', null, coupon.code),
						createElement('span', null, i18n.applied || 'Applied')
					),
					createElement('button', {type: 'button', disabled: busy, onClick: function () { update('remove'); }}, i18n.remove || 'Remove')
				) :
				createElement('form', {className: 'mpwpb-coupon-block__form', onSubmit: submit},
					createElement('input', {type: 'text', value: code, disabled: busy, placeholder: i18n.placeholder || 'Coupon code', 'aria-label': i18n.placeholder || 'Coupon code', onChange: function (event) { setCode(event.target.value); }}),
					createElement('button', {type: 'submit', disabled: busy || !code.trim()}, busy ? (i18n.applying || 'Applying…') : (i18n.apply || 'Apply'))
				),
			error ? createElement('p', {className: 'mpwpb-coupon-block__error', role: 'alert'}, error) : null
		);
	}

	wp.plugins.registerPlugin('mpwpb-booking-coupon', {
		render: function () {
			return createElement(ExperimentalDiscountsMeta, null, createElement(CouponFill));
		},
		scope: 'woocommerce-checkout'
	});
})();
