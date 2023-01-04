<?php

namespace PriorPrice;

class Prices {

	/**
	 * @var \PriorPrice\HistoryStorage
	 */
	private $history_storage;

	public function __construct( HistoryStorage $history_storage ) {

		$this->history_storage = $history_storage;
	}

	/**
	 * Register hooks.
	 *
	 * @since 1.1
	 */
	public function register_hooks(): void {

		add_filter( 'woocommerce_get_price_html', [ $this, 'get_price_html' ], 10, 2 );
	}

	/**
	 * Get price HTML filter.
	 *
	 * Display under the price in front-end the lowest price information.
	 *
	 * @since 1.0
	 * @since 1.1 Check display conditions.
	 *
	 * @param string      $html       HTML code which displays product price on front-end.
	 * @param \WC_Product $wc_product WC Product.
	 *
	 * @return string
	 */
	public function get_price_html( string $html, \WC_Product $wc_product ) : string {

		if ( $this->do_not_display_price_html() ) {
			return $html;
		}

		return $html . $this->lowest_price_html( $wc_product );
	}

	/**
	 * Get the lowest price HTML.
	 *
	 * @since 1.0
	 *
	 * @param \WC_Product $wc_product WC Product.
	 *
	 * @return string
	 */
	public function lowest_price_html( $wc_product ): string {

		$lowest = $this->history_storage->get_minimal( $wc_product->get_id() );

		if ( (float) $lowest < 1 ) {
			return '';
		}

		$lowest_html   = '<div class="wc-price-history prior-price lowest">%s</div>';
		/* translators: %s - the lowest price in the last 30 days. */
		$lowest_text   = __( '30-day low: %s', 'wc-price-history' );
		$with_currency = sprintf( get_woocommerce_price_format(), get_woocommerce_currency_symbol(), $lowest );
		$final         = sprintf( $lowest_html, sprintf( $lowest_text, $with_currency ) );

		return $final;
	}

	/**
	 * Check if the price HTML should be displayed.
	 *
	 * @since 1.1
	 *
	 * @return bool False if should not display, true otherwise.
	 */
	private function do_not_display_price_html() : bool {

		$display_on = $this->get_display_on();

		return (
			( ! isset( $display_on['shop_page'] ) && is_shop() ) ||
			( ! isset( $display_on['product_page'] ) && is_product() )
		);
	}

	/**
	 * Get the display on settings.
	 *
	 * @since 1.1
	 *
	 * @return array<array<bool>>
	 */
	private function get_display_on() : array {
		$settings = get_option( 'wc_price_history_settings' );
		if ( ! isset( $settings['display_on'] ) ) {
			return [];
		}
		return $settings['display_on'];
	}
}
