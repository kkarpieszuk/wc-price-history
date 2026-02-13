/* global wc_price_history_frontend */

/**
 * Frontend script for WC Price History plugin.
 *
 * @since 3.0.0
 *
 * @package WC_Price_History
 * @subpackage Frontend
 * @author Konrad Karpieszuk
 */

let WCPriceHistory = {};

WCPriceHistory.Frontend = WCPriceHistory.Frontend || ( function( document, window, $ ) {
	const app = {
		/**
		 * Selectors.
		 */
		selectors: {
			rawPrice: '.wc-price-history-lowest-raw-value',
			lowestPriceModule: '.wc-price-history.prior-price.lowest',
			shortcodeModule: '.wc-price-history-shortcode',
			shortcodeDefer: '.wc-price-history-shortcode--defer',
			shortcodePlaceholder: '.wc-price-history-shortcode-placeholder',
			shortcodePrice: '.wc-price-history-shortcode-price',
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
			 * @since 3.0.0
			 *
			 * @param {number} price Price.
			 *
			 * @return {string} Formatted price.
			 */
			formatPrice: ( price ) => {
				let formattedPrice = parseFloat( price ).toFixed( wc_price_history_frontend.decimals );

				formattedPrice = formattedPrice.replace(',', wc_price_history_frontend.thousand_separator);
				formattedPrice = formattedPrice.replace('.', wc_price_history_frontend.decimal_separator);

				return formattedPrice;
			},

			/**
			 * Get original prices.
			 *
			 * @since 3.0.0
			 *
			 * @return {array} Original prices.
			 */
			getOriginalPrices: () => {

				const $lowestPriceModules = $( app.selectors.lowestPriceModule );
				const $shortcodeModules = $( app.selectors.shortcodeModule );

				if ( $lowestPriceModules.length === 0 && $shortcodeModules.length === 0 ) {
					return [];
				}

				let originalPrices = [];

				$lowestPriceModules.each(function() {
					const productId = $(this).data('product-id');
					const originalPrice = $(this).data('original-price');

					originalPrices[productId] = originalPrice;
				});

				$shortcodeModules.each(function() {
					const productId = $(this).data('product-id');
					const originalPrice = $(this).data('original-price');

					originalPrices[productId] = originalPrice;
				});

				return originalPrices;
			},

			/**
			 * On found variation woocommerce event.
			 *
			 * @since 3.0.0
			 *
			 * @param {object} event Event.
			 * @param {object} variation Variation.
			 */
			onFoundVariation: (event, variation) => {

				const $this = $(event.currentTarget),
					productId = $this.data( 'product_id' ),
					lowestInVariation = variation._wc_price_history_lowest_price;

				const $lowestPriceModule = $( app.selectors.lowestPriceModule + '[data-product-id="' + productId + '"]');
				const $shortcodeModule = $( app.selectors.shortcodeModule + '[data-product-id="' + productId + '"]');

				$lowestPriceModule.find( app.selectors.rawPrice ).text( app.methods.formatPrice( lowestInVariation ) );
				$shortcodeModule.each( function() {
					const $shortcode = $( this );
					if ( $shortcode.hasClass( 'wc-price-history-shortcode--defer' ) ) {
						$shortcode.find( app.selectors.shortcodePlaceholder ).hide();
						$shortcode.find( app.selectors.shortcodePrice ).show().find( app.selectors.rawPrice ).text( app.methods.formatPrice( lowestInVariation ) );
					} else {
						$shortcode.find( app.selectors.rawPrice ).text( app.methods.formatPrice( lowestInVariation ) );
					}
				} );
			},

			/**
			 * On reset data woocommerce event.
			 *
			 * @since 3.0.0
			 *
			 * @param {object} event Event.
			 * @param {object} variation Variation.
			 */
			onResetData: (event, variation) => {

				const $this = $(event.currentTarget),
					productId = $this.data( 'product_id' ),
					originalPrice = app.data.originalPrices[productId];

				const $lowestPriceModule = $( app.selectors.lowestPriceModule + '[data-product-id="' + productId + '"]');
				const $shortcodeModule = $( app.selectors.shortcodeModule + '[data-product-id="' + productId + '"]');

				$lowestPriceModule.find( app.selectors.rawPrice ).text( app.methods.formatPrice( originalPrice ) );
				$shortcodeModule.each( function() {
					const $shortcode = $( this );
					if ( $shortcode.hasClass( 'wc-price-history-shortcode--defer' ) ) {
						$shortcode.find( app.selectors.shortcodePlaceholder ).show();
						$shortcode.find( app.selectors.shortcodePrice ).hide().find( app.selectors.rawPrice ).text( '' );
					} else {
						$shortcode.find( app.selectors.rawPrice ).text( app.methods.formatPrice( originalPrice ) );
					}
				} );
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

WCPriceHistory.Frontend.init();
