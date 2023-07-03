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

		global $post;

		$product = wc_get_product( $post->ID );
		$atts    = shortcode_atts(
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
		$lowest = number_format( $lowest, wc_get_price_decimals(), wc_get_price_decimal_separator(), wc_get_price_thousand_separator() );
		$lowest = (bool) $atts['show_currency'] ? sprintf( get_woocommerce_price_format(), get_woocommerce_currency_symbol(), $lowest ) : $lowest;
		$class  = $this->settings_data->get_display_line_through() ? 'line-through' : '';

		return sprintf( '<div class="wc-price-history-shortcode %2$s">%1$s</div>', $lowest, $class );
	}
}
