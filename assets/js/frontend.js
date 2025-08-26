jQuery(document).ready(function($) {

	// Store original price.
	const originalPrice = $( '.wc-price-history.prior-price-value .wc-price-history-lowest-raw-value').text();

	$('form.variations_form').on('found_variation', function(event, variation) {

		const $lowestPricePlaceholder = $( '.wc-price-history.prior-price-value .wc-price-history-lowest-raw-value'),
		  lowestInVariation = variation._wc_price_history_lowest_price;

		 if ( $lowestPricePlaceholder.length ) {
			 $lowestPricePlaceholder.text( formatPrice( lowestInVariation ) );
		 }

		 console.log( variation );
	});

	// On variation clear, reset to original price.
	$('form.variations_form').on('reset_data', function(event, variation) {
		$( '.wc-price-history.prior-price-value .wc-price-history-lowest-raw-value').text( originalPrice );
	});

	function formatPrice(price) {

		let formattedPrice = parseFloat( price ).toFixed( wc_price_history_frontend.decimals );

		formattedPrice = formattedPrice.replace(',', wc_price_history_frontend.thousand_separator);
		formattedPrice = formattedPrice.replace('.', wc_price_history_frontend.decimal_separator);

		return formattedPrice;
	}
});