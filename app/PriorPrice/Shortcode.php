<?php

namespace PriorPrice;

use PriorPrice\PriceDisplayStrategy\PriceContext;

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
	 * @param \PriorPrice\HistoryStorage $_history_storage Prices object. Not used.
	 * @param \PriorPrice\Taxes          $taxes            Taxes object.
	 * @param \PriorPrice\SettingsData   $settings_data    Settings data object.
	 */
	/** @phpstan-ignore constructor.unusedParameter */
	public function __construct( HistoryStorage $_history_storage, Taxes $taxes, SettingsData $settings_data ) {

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

		$context = new PriceContext( $product, $this->taxes, $this->settings_data );
		$lowest  = $context->lowest_price_no_text( $product );
		$class   = $this->settings_data->get_display_line_through() ? 'line-through' : '';

		return sprintf(
			'<div class="wc-price-history-shortcode %2$s"
				data-product_id="%3$d"
				data-product-type="%4$s"
				data-product-default-lowest-price="%5$s"
				>%1$s</div>',
			$lowest,
			$class,
			$id,
			$product->get_type(),
			esc_html( $lowest )
		);
	}
}
