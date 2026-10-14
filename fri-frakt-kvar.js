/* Block cart and block checkout: prints the notice from the Store API cart response. */
( function ( wp, wc ) {
	const el = wp.element.createElement;
	const { ExperimentalOrderMeta } = wc.blocksCheckout;

	// The slot passes the current cart and its extension data, and re-renders when the cart changes.
	function FreeShippingNotice( { extensions } ) {
		const data = extensions && extensions[ 'wpu-fri-frakt-kvar' ];
		if ( ! data || ! data.message ) {
			return null;
		}

		return el(
			'div',
			// wc-block-components-totals-item gives the same side padding as the rows above it.
			{ className: 'wc-block-components-totals-item wpu-ffk wpu-ffk--' + data.state, role: 'status', style: { display: 'block' } },
			el( 'p', { style: { margin: '0 0 .4em' } }, data.message ),
			el(
				'span',
				{ 'aria-hidden': true, style: { display: 'block', height: '4px', background: 'rgba(0,0,0,.1)' } },
				el( 'span', { style: { display: 'block', height: '100%', width: data.percent + '%', background: 'currentColor' } } )
			)
		);
	}

	wp.plugins.registerPlugin( 'wpu-fri-frakt-kvar', {
		scope: 'woocommerce-checkout',
		render: () => el( ExperimentalOrderMeta, null, el( FreeShippingNotice ) ),
	} );
} )( window.wp, window.wc );
