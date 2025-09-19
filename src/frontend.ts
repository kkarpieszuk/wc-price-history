/* global wc_price_history_frontend */

/**
 * Frontend script for WC Price History plugin.
*
* @since {VERSION}
*
* @package WC_Price_History
* @subpackage Frontend
* @author Konrad Karpieszuk
*/

interface WCPriceHistoryFrontend {
	init(): void;
}

let WCPriceHistoryFrontend: WCPriceHistoryFrontend = ( function( document, window, $ ): WCPriceHistoryFrontend {
	const app = {
		/**
		 * Selectors.
		 */
		selectors: {
			rawPrice: '.wc-price-history.prior-price-value .woocommerce-Price-amount.amount .wc-price-history-lowest-raw-value',
			lowestPriceModule: '.wc-price-history.prior-price.lowest',
		},

		/**
		 * Initialize the app.
		 */
		init() {
			app.data.originalPrices = app.methods.getOriginalPrices();

			$( 'form.variations_form' ).on( 'found_variation', app.methods.onFoundVariation );
			$( 'form.variations_form' ).on( 'reset_data', app.methods.onResetData );
		},

		/**
		 * Methods.
		 */
		methods: {
			/**
			 * Format price.
			 *
			 * @since {VERSION}
			 *
			 * @param {number} price Price.
			 *
			 * @return {string} Formatted price.
			 */
			formatPrice: ( price ) => {
				let formattedPrice = parseFloat( price ).toFixed( window.wc_price_history_frontend.decimals );

				formattedPrice = formattedPrice.replace(',', window.wc_price_history_frontend.thousand_separator);
				formattedPrice = formattedPrice.replace('.', window.wc_price_history_frontend.decimal_separator);

				return formattedPrice;
			},

			/**
			 * Get original prices.
			 *
			 * @since {VERSION}
			 *
			 * @return {array} Original prices.
			 */
			getOriginalPrices: () => {

				const $lowestPriceModules = $( app.selectors.lowestPriceModule );

				if ( $lowestPriceModules.length === 0 ) {
					return [];
				}

				let originalPrices = [];

				$lowestPriceModules.each(function() {
					const productId = $(this).data('product-id');
					const originalPrice = $(this).data('original-price');

					originalPrices[productId] = originalPrice;
				});

				return originalPrices;
			},

			/**
			 * On found variation woocommerce event.
			 *
			 * @since {VERSION}
			 *
			 * @param {object} event Event.
			 * @param {object} variation Variation.
			 */
			onFoundVariation: (event, variation) => {

				const $this = $(event.currentTarget),
					productId = $this.data( 'product_id' ),
					lowestInVariation = variation._wc_price_history_lowest_price;

				const $lowestPriceModule = $( app.selectors.lowestPriceModule + '[data-product-id="' + productId + '"]');

				$lowestPriceModule.find( app.selectors.rawPrice ).text( app.methods.formatPrice( lowestInVariation ) );
			},

			/**
			 * On reset data woocommerce event.
			 *
			 * @since {VERSION}
			 *
			 * @param {object} event Event.
			 * @param {object} variation Variation.
			 */
			onResetData: (event, variation) => {

				const $this = $(event.currentTarget),
					productId = $this.data( 'product_id' ),
					originalPrice = app.data.originalPrices[productId];

				const $lowestPriceModule = $( app.selectors.lowestPriceModule + '[data-product-id="' + productId + '"]');

				$lowestPriceModule.find( app.selectors.rawPrice ).text( app.methods.formatPrice( originalPrice ) );
			},
		},

		/**
		 * Data store.
		 */
		data: {
			originalPrices: [],
		},
	};

	return app;
} )( document, window, jQuery );

WCPriceHistoryFrontend.init();
