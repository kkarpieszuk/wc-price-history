jQuery(document).ready(function($) {

	// Store original price from JavaScript object instead of HTML
	let originalPrice = null;

	// Get original price from variations data or current product price
	function getOriginalPrice() {
		// For variable products, get from variations data
		const variationsData = $('.variations_form').data('product_variations');
		const productId = $('.variations_form').data('product_id');
		if (variationsData && variationsData.length > 0) {
			// Use the first variation's display_price as original price
			return variationsData[0].display_price;
		}

		// For simple products, try to get from current price element
		const currentPriceElement = $('.price .woocommerce-Price-amount');
		if (currentPriceElement.length) {
			return currentPriceElement.first().text().replace(/[^\d.,]/g, '');
		}

		// Fallback to HTML method if JavaScript object not available
		return $('[data-product-id="' + productId + '"] .wc-price-history.prior-price-value .wc-price-history-lowest-raw-value').text();
	}

	// Initialize original price
	originalPrice = getOriginalPrice();

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