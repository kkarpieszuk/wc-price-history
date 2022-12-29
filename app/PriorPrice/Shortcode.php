<?php

namespace PriorPrice;

/**
 * Shortcode class.
 *
 * @since 1.1
 */
class Shortcode {

	/**
	 * @since 1.1
	 *
	 * @var \PriorPrice\Prices
	 */
	private $prices;

	/**
	 * Constructor.
	 *
	 * @since 1.1
	 *
	 * @param \PriorPrice\Prices $prices Prices object.
	 */
	public function __construct( Prices $prices ) {

		$this->prices = $prices;
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
	 * @since 1.1
	 */
	public function register_shortcode(): void {
		add_shortcode( 'wc_price_history', [ $this, 'shortcode_callback' ] );
	}

	/**
	 * Shortcode callback.
	 *
	 * @since 1.1
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

		$lowest = $this->prices->get_lowest( $id );
		$lowest = (bool) $atts['show_currency'] ? sprintf( get_woocommerce_price_format(), get_woocommerce_currency_symbol(), $lowest ) : $lowest;

		return sprintf( '<div class="wc-price-history-shortcode">%s</div>', $lowest );
	}
}
