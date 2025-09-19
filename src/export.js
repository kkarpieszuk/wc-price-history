jQuery( document ).ready( function($) {

	const $exportButton = $( '#wc-price-history-export-product-with-price-history' );

	$exportButton.on( 'click', function() {
		const productId = $( this ).data( 'product-id' );

		$.post(
			ajaxurl,
			{
				action: 'wc_price_history_export_product_with_price_history',
				security: wc_price_history_export.nonce,
				product_id: productId,
			},
			function( response ) {
				if ( response.success ) {
					// trigger downloading the file.
					const blob = new Blob( [ JSON.stringify( response.data ) ], { type: 'application/json' } );
					const url = window.URL.createObjectURL( blob );
					const link = document.createElement( 'a' );
					link.href = url;
					link.download = `${ response.data.product_name }.json`;
					document.body.appendChild( link );
					link.click();
					document.body.removeChild( link );
				} else {
					$exportButton.after( `<p class="wc-price-history-export-error">${ response.data.message }</p>` );
				}
			}
		);
	} );
} );