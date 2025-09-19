/* global wc_price_history_frontend */
var WCPriceHistoryFrontend = (function (document, window, $) {
    var app = {
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
        init: function () {
            app.data.originalPrices = app.methods.getOriginalPrices();
            $('form.variations_form').on('found_variation', app.methods.onFoundVariation);
            $('form.variations_form').on('reset_data', app.methods.onResetData);
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
            formatPrice: function (price) {
                var formattedPrice = parseFloat(price).toFixed(window.wc_price_history_frontend.decimals);
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
            getOriginalPrices: function () {
                var $lowestPriceModules = $(app.selectors.lowestPriceModule);
                if ($lowestPriceModules.length === 0) {
                    return [];
                }
                var originalPrices = [];
                $lowestPriceModules.each(function () {
                    var productId = $(this).data('product-id');
                    var originalPrice = $(this).data('original-price');
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
            onFoundVariation: function (event, variation) {
                var $this = $(event.currentTarget), productId = $this.data('product_id'), lowestInVariation = variation._wc_price_history_lowest_price;
                var $lowestPriceModule = $(app.selectors.lowestPriceModule + '[data-product-id="' + productId + '"]');
                $lowestPriceModule.find(app.selectors.rawPrice).text(app.methods.formatPrice(lowestInVariation));
            },
            /**
             * On reset data woocommerce event.
             *
             * @since {VERSION}
             *
             * @param {object} event Event.
             * @param {object} variation Variation.
             */
            onResetData: function (event, variation) {
                var $this = $(event.currentTarget), productId = $this.data('product_id'), originalPrice = app.data.originalPrices[productId];
                var $lowestPriceModule = $(app.selectors.lowestPriceModule + '[data-product-id="' + productId + '"]');
                $lowestPriceModule.find(app.selectors.rawPrice).text(app.methods.formatPrice(originalPrice));
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
})(document, window, jQuery);
WCPriceHistoryFrontend.init();
