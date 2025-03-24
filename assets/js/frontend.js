jQuery(document).ready(function($) {
	class PriceHistoryManager {
		constructor() {
			this.variantBeforeSelection = wc_price_history_frontend.variant_before_selection;

			this.init();
		}

		init() {
			this.handleInitialState();
			this.bindEvents();
		}

		bindEvents() {
			$('form.variations_form')
				.on('found_variation', (event, variation) => this.handleVariationChange(event, variation))
				.on('reset_data', () => this.handleInitialState());
		}

		handleVariationChange(event, variation) {
			const $form = $(event.currentTarget);
			const productId = $form.data('product_id');
			const lowestPrice = variation._wc_price_history_lowest_price;

			this.updateMainPrice($form, lowestPrice);
			this.updateShortcodes(productId, lowestPrice);
		}

		updateMainPrice($form, lowestPrice) {
			const $wrapper = $form.siblings('.wc-price-history.prior-price.lowest');
			const $priceElement = this.getPriceElement($wrapper);

			$wrapper.show();
			if ($priceElement.length) {
				$priceElement.html(lowestPrice);
			}
		}

		updateShortcodes(productId, lowestPrice) {
			const $shortcodes = $(`.wc-price-history-shortcode[data-product_id="${productId}"]`);

			$shortcodes.each((_, shortcode) => {
				const $shortcode = $(shortcode);
				const $priceElement = this.getPriceElement($shortcode);

				$priceElement.html(lowestPrice);
				$shortcode.show();
			});
		}

		getPriceElement($container) {
			return this.variantBeforeSelection === 'lowest_range'
				? $container.find('.wc-price-history-lowest-raw-value')
				: $container.find('.woocommerce-Price-amount.amount');
		}

		handleInitialState() {
			this.handleMainPriceDisplay();
			this.handleShortcodesDisplay();
		}

		handleMainPriceDisplay() {
			$('.wc-price-history.prior-price.lowest').each((_, element) => {
				const $element = $(element);

				if ($element.data('product-type') !== 'variable') {
					return;
				}

				if (this.variantBeforeSelection === 'lowest_hide') {
					$element.hide();
					return;
				}

				$element.html( this._getDefaultPricePlaceholder( $element ) );
			});
		}

		handleShortcodesDisplay() {
			$('.wc-price-history-shortcode').each((_, element) => {
				const $shortcode = $(element);
				const $priceElement = this.getPriceElement($shortcode);

				if ($shortcode.data('product-type') !== 'variable') {
					return;
				}

				if (this.variantBeforeSelection === 'lowest_hide') {
					$shortcode.hide();
					return;
				}

				$priceElement.html( this._getDefaultPricePlaceholder( $shortcode ) );
			});
		}

		_getDefaultPricePlaceholder( $element ) {

			return this._decodeHTMLEntities( $element.data('product-default-lowest-price') );
		}

		_decodeHTMLEntities(text) {
			const textarea = document.createElement('textarea');
			textarea.innerHTML = text;
			return textarea.value;
		}
	}

	// Initialize the price history manager
	new PriceHistoryManager();
});