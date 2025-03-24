jQuery(document).ready(function($) {

	let lowestPricePlaceholderHtml = null;
	let lowestPricePlaceholderHtmlInShortcode = null;

	maybeHideLowestPrice();

	$('form.variations_form').on('found_variation', function(event, variation) {
		const $form = $( this ),
			productId = $form.data( 'product_id' ),
			$wrapper = $form.siblings( '.wc-price-history.prior-price.lowest' ),
			$lowestPricePlaceholder =
			wc_price_history_frontend.variant_before_selection === 'lowest_range' ?
				$wrapper.find( '.wc-price-history.prior-price-value .wc-price-history-lowest-raw-value') :
				$wrapper.find( '.wc-price-history.prior-price-value .woocommerce-Price-amount.amount'),
			lowestInVariation = variation._wc_price_history_lowest_price;

		const $shortcodes = $( '.wc-price-history-shortcode[data-product_id="' + productId + '"] .wc-price-history-lowest-raw-value' );
		if ( $shortcodes.length ) {
			$shortcodes.each(function() {
				const $shortcode = $( this );
				$shortcode.html( lowestInVariation );
			});
		}

		$wrapper.show();

		 if ( $lowestPricePlaceholder.length ) {
			 $lowestPricePlaceholder.html( lowestInVariation );
		 }
	});

	$('form.variations_form').on('reset_data', function(event) {

		maybeHideLowestPrice();
	});

	function formatPrice(price) {

		let formattedPrice = parseFloat( price ).toFixed( wc_price_history_frontend.decimals );

		formattedPrice = formattedPrice.replace(',', wc_price_history_frontend.thousand_separator);
		formattedPrice = formattedPrice.replace('.', wc_price_history_frontend.decimal_separator);

		return formattedPrice;
	}

	function maybeHideLowestPrice() {

		$( '.wc-price-history.prior-price.lowest ' ).each(function() {

			const $lowestPricePlaceholder = $( this );

			if ( ! lowestPricePlaceholderHtml ) {
				lowestPricePlaceholderHtml = $lowestPricePlaceholder.html();
			}

			if ( $lowestPricePlaceholder.data( 'product-type' ) !== 'variable' ) {
				return;
			}

			if ( wc_price_history_frontend.variant_before_selection === 'lowest_hide' ) {
				$lowestPricePlaceholder.hide();

				return;
			}

			$lowestPricePlaceholder.html( lowestPricePlaceholderHtml );
		} );

		$( '.wc-price-history-shortcode' ).each(function() {
			const $lowestPricePlaceholder = $( this );
			const $onlyPrice = $lowestPricePlaceholder.find( '.wc-price-history-lowest-raw-value' );

			if ( ! lowestPricePlaceholderHtmlInShortcode ) {
				lowestPricePlaceholderHtmlInShortcode = $onlyPrice.html();
			}

			if ( $lowestPricePlaceholder.data( 'product-type' ) !== 'variable' ) {
				return;
			}

			if ( wc_price_history_frontend.variant_before_selection === 'lowest_hide' ) {
				$lowestPricePlaceholder.hide();

				return;
			}

			$onlyPrice.html( lowestPricePlaceholderHtmlInShortcode );
		} );
	}
});