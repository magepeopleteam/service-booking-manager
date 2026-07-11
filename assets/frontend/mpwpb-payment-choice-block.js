/**
 * Pay in Full / Pay Deposit toggle for the WooCommerce Cart & Checkout
 * Blocks' Order Summary. Registered as a plain, no-build Fill for the
 * ExperimentalOrderMeta slot -- see MPWPB_Partial_Payment::register_store_api_extension()
 * (PHP side: the 'mpwpb' Store API cart extension this reads/writes).
 */
( function () {
	var wp = window.wp;
	var wc = window.wc;
	if ( ! wp || ! wp.plugins || ! wp.element || ! wp.data || ! wc || ! wc.blocksCheckout ) {
		return;
	}

	var registerPlugin = wp.plugins.registerPlugin;
	var createElement = wp.element.createElement;
	var useState = wp.element.useState;
	var useSelect = wp.data.useSelect;
	var ExperimentalOrderMeta = wc.blocksCheckout.ExperimentalOrderMeta;
	var extensionCartUpdate = wc.blocksCheckout.extensionCartUpdate;
	var i18n = window.mpwpbPaymentChoiceI18n || {
		fullEyebrow: 'Standard',
		fullLabel: 'Pay in Full',
		depositEyebrow: 'Flexible',
		depositLabel: 'Pay Deposit',
		todaySuffix: 'today'
	};

	function PaymentChoiceFill() {
		var mpwpb = useSelect( function ( select ) {
			var store = select( 'wc/store/cart' );
			var cartData = store && store.getCartData ? store.getCartData() : null;
			return cartData && cartData.extensions ? cartData.extensions.mpwpb : null;
		}, [] );
		var updatingState = useState( false );
		var isUpdating = updatingState[ 0 ];
		var setIsUpdating = updatingState[ 1 ];

		if ( ! mpwpb || ! mpwpb.enabled ) {
			return null;
		}

		function select( value ) {
			if ( isUpdating || value === mpwpb.choice ) {
				return;
			}
			setIsUpdating( true );
			extensionCartUpdate( {
				namespace: 'mpwpb',
				data: { choice: value }
			} ).then( function () {
				setIsUpdating( false );
			} ).catch( function () {
				setIsUpdating( false );
			} );
		}

		function renderCard( value, eyebrow, title, priceText, priceSuffix ) {
			var isSelected = mpwpb.choice === value;

			function onKeyDown( event ) {
				if ( event.key === 'Enter' || event.key === ' ' ) {
					event.preventDefault();
					select( value );
				}
			}

			return createElement(
				'div',
				{
					className: 'mpwpb-payment-choice-card' + ( isSelected ? ' is-selected' : '' ),
					role: 'radio',
					'aria-checked': isSelected,
					tabIndex: 0,
					onClick: function () { select( value ); },
					onKeyDown: onKeyDown
				},
				createElement( 'span', { className: 'mpwpb-payment-choice-eyebrow' }, eyebrow ),
				createElement( 'div', { className: 'mpwpb-payment-choice-title' }, title ),
				createElement(
					'div',
					{ className: 'mpwpb-payment-choice-price' },
					priceText,
					priceSuffix
						? createElement( 'span', { className: 'mpwpb-payment-choice-price-suffix' }, ' ' + priceSuffix )
						: null
				)
			);
		}

		return createElement(
			ExperimentalOrderMeta,
			null,
			createElement(
				'div',
				{
					className: 'mpwpb-payment-choice-cards' + ( isUpdating ? ' is-updating' : '' ),
					role: 'radiogroup'
				},
				renderCard( 'full', i18n.fullEyebrow, i18n.fullLabel, mpwpb.full_amount_formatted, null ),
				renderCard( 'partial', i18n.depositEyebrow, i18n.depositLabel, mpwpb.deposit_amount_formatted, i18n.todaySuffix )
			)
		);
	}

	registerPlugin( 'mpwpb-payment-choice-block', {
		render: PaymentChoiceFill,
		scope: 'woocommerce-checkout'
	} );
} )();
