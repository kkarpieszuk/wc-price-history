<?php

namespace PriorPrice;

/**
 * Shortcode class.
 *
 * @since 1.2
 */
class Shortcode {

	/**
	 * @since 1.7
	 *
	 * @var \PriorPrice\SettingsData
	 */
	public $settings_data;

	/**
	 * @since 1.2
	 *
	 * @var \PriorPrice\HistoryStorage
	 */
	private $history_storage;

	/**
	 * @var \PriorPrice\Taxes
	 */
	private $taxes;

	/**
	 * Constructor.
	 *
	 * @since 1.2
	 * @since 1.6.2 uses Taxes class.
	 * @since 1.7 uses SettingsData class.
	 *
	 * @param \PriorPrice\HistoryStorage $history_storage Prices object.
	 * @param \PriorPrice\Taxes          $taxes           Taxes object.
	 * @param \PriorPrice\SettingsData   $settings_data   Settings data object.
	 */
	public function __construct( HistoryStorage $history_storage, Taxes $taxes, SettingsData $settings_data ) {

		$this->history_storage = $history_storage;
		$this->taxes           = $taxes;
		$this->settings_data   = $settings_data;
	}

	public function register_hooks() : void {
		add_action( 'init', [ $this, 'register_shortcode' ] );
	}

	/**
	 * Register shortcode wc_price_history.
	 *
	 * Usage:
	 * Display minimal price with currency symbol for the current product:
	 * [wc_price_history]
	 * Display minimal price with currency symbol for the product of given ID:
	 * [wc_price_history id=1]
	 * Display minimal price without currency symbol:
	 * [wc_price_history show_currency=0]
	 *
	 * @since 1.2
	 */
	public function register_shortcode(): void {
		add_shortcode( 'wc_price_history', [ $this, 'shortcode_callback' ] );
	}

	/**
	 * Shortcode callback.
	 *
	 * @since 1.2
	 *
	 * @param array<string> $atts Shortcode attributes.
	 */
	public function shortcode_callback( $atts ): string {

		$product = wc_get_product();
		$atts = shortcode_atts(
			[
				'id'            => $product ? $product->get_id() : null,
				'show_currency' => 1,
			],
			$atts,
			'wc_price_history'
		);

		$id = (int) $atts['id'];

		if ( $id < 1 ) {
			return '';
		}

		$product = wc_get_product( $id );

		if ( ! $product ) {
			return '';
		}

		// Variable product with defer: show placeholder until a variation is selected.
		if ( $product instanceof \WC_Product_Variable
			&& $this->settings_data->get_variable_product_defer_lowest_price() ) {
			return $this->shortcode_deferred_variable_output( $product, $atts );
		}

		$days_number = $this->settings_data->get_days_number();
		$count_from  = $this->settings_data->get_count_from();

		if ( in_array( $count_from, [ 'sale_start', 'sale_start_inclusive' ] ) && $product->is_on_sale() ) {
			$lowest = $this->history_storage->get_minimal_from_sale_start( $product, $days_number, $count_from );
		} else {
			$lowest = $this->history_storage->get_minimal( $id, $days_number );
		}

		if ( ! $lowest ) {
			return '';
		}

		$lowest = $this->taxes->apply_taxes( $lowest, $product );
		/**
		 * This filter is documented in app/PriorPrice/Prices.php.
		 */
		$lowest = apply_filters( 'wc_price_history_lowest_price_html_raw_value_taxed', $lowest, $product);

		$wc_price_args = [
			'currency' => (bool) $atts['show_currency'] ? get_woocommerce_currency() : 'none',
		];

		// Add wrapper class for raw price value to enable JavaScript updates.
		$price_format = get_woocommerce_price_format();
		$price_format = str_replace( '%2$s', '<span class="wc-price-history-lowest-raw-value">%2$s</span>', $price_format );
		$wc_price_args['price_format'] = $price_format;

		$lowest_html = wc_price( $lowest, $wc_price_args );
		$class       = $this->settings_data->get_display_line_through() ? 'line-through' : '';

		return sprintf(
			'<div class="wc-price-history-shortcode %3$s" data-product-id="%1$s" data-original-price="%2$s">%4$s</div>',
			$product->get_id(),
			$lowest,
			$class,
			$lowest_html
		);
	}

	/**
	 * Output for variable product when lowest price is deferred until variation is selected.
	 *
	 * Renders placeholder text and a hidden price block so frontend JS can show the price on found_variation.
	 *
	 * @since 3.2.1
	 *
	 * @param \WC_Product_Variable       $product Product.
	 * @param array<string, int|string|null> $atts   Shortcode attributes (from shortcode_atts).
	 *
	 * @return string
	 */
	private function shortcode_deferred_variable_output( \WC_Product_Variable $product, array $atts ): string {

		$placeholder = $this->settings_data->get_variable_product_defer_placeholder_text();
		if ( $placeholder === '' ) {
			return '';
		}

		$show_currency    = (bool) $atts['show_currency'];
		$currency_str     = $show_currency ? get_woocommerce_currency_symbol() : '';
		$price_format     = get_woocommerce_price_format();
		$price_format     = str_replace( '%2$s', '<span class="wc-price-history-lowest-raw-value"></span>', $price_format );
		$hidden_price_html = sprintf( $price_format, $currency_str, '' );

		return sprintf(
			'<div class="wc-price-history-shortcode wc-price-history-shortcode--defer" data-product-id="%1$s" data-show-currency="%2$s">' .
			'<span class="wc-price-history-shortcode-placeholder">%3$s</span>' .
			'<span class="wc-price-history-shortcode-price" style="display:none">%4$s</span></div>',
			$product->get_id(),
			$show_currency ? '1' : '0',
			$placeholder,
			$hidden_price_html
		);
	}
}
