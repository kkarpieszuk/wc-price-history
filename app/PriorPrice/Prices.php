<?php

namespace PriorPrice;

class Prices {

	/**
	 * @var \PriorPrice\HistoryStorage
	 */
	private $history_storage;

	/**
	 * @var \PriorPrice\SettingsData
	 */
	private $settings_data;

	public function __construct( HistoryStorage $history_storage, SettingsData $settings_data ) {

		$this->history_storage = $history_storage;
		$this->settings_data   = $settings_data;
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
	 * @since 1.2 Check display conditions.
	 *
	 * @param string      $html       HTML code which displays product price on front-end.
	 * @param \WC_Product $wc_product WC Product.
	 *
	 * @return string
	 */
	public function get_price_html( string $html, \WC_Product $wc_product ) : string {

		if ( $this->is_not_correct_place() ) {
			return $html;
		}

		if ( $this->is_not_correct_when( $wc_product ) ) {
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
	public function lowest_price_html( \WC_Product $wc_product ): string {

		$days_number = $this->settings_data->get_days_number();
		$count_from  = $this->settings_data->get_count_from();

		if ( $count_from === 'sale_start' && $wc_product->is_on_sale() ) {
			$lowest = $this->history_storage->get_minimal_from_sale_start( $wc_product, $days_number );
		} else {
			$lowest = $this->history_storage->get_minimal( $wc_product->get_id(), $days_number );
		}

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
	 * Check the current screen if the price HTML should be displayed.
	 *
	 * @since 1.2
	 *
	 * @return bool
	 */
	private function is_not_correct_place() : bool {

		$display_on = $this->settings_data->get_display_on();

		return (
			( ! isset( $display_on['shop_page'] ) && is_shop() ) ||
			( ! isset( $display_on['product_page'] ) && is_product() )
		);
	}

	/**
	 * Check if product being on sale should be considered when displaying.
	 *
	 * @since 1.2
	 *
	 * @param \WC_Product $wc_product WC Product.
	 *
	 * @return bool
	 */
	private function is_not_correct_when( \WC_Product $wc_product ) : bool {

		$display_when = $this->settings_data->get_display_when();

		if ( $display_when === 'on_sale' && ! $wc_product->is_on_sale() ) {
			return true;
		}

		return false;
	}
}
