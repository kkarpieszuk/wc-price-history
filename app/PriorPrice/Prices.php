<?php

namespace PriorPrice;

use PriorPrice\PriceDisplayStrategy\PriceContext;

class Prices {

	/**
	 * @var \PriorPrice\SettingsData
	 */
	private $settings_data;

	/**
	 * @var \PriorPrice\Taxes
	 */
	private $taxes;

	/**
	 * @var PriceContext
	 */
	private $price_context;

	/**
	 * Constructor.
	 *
	 * @since 1.0
	 * @since 1.6.2 uses Taxes class.
	 *
	 * @param \PriorPrice\HistoryStorage $_history_storage Prices object. Not used.
	 * @param \PriorPrice\SettingsData   $settings_data    Settings data object.
	 * @param \PriorPrice\Taxes          $taxes            Taxes object.
	 */
	/** @phpstan-ignore constructor.unusedParameter */
	public function __construct( HistoryStorage $_history_storage, SettingsData $settings_data, Taxes $taxes ) { // phpcs:ignore Generic.CodeAnalysis.UnusedParameters.UnusedParameter

		$this->settings_data   = $settings_data;
		$this->taxes           = $taxes;
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

		if ( ! $this->is_correct_place( $wc_product ) ) {
			return $html;
		}

		if ( $this->is_not_correct_when( $wc_product ) ) {
			return $html;
		}

		$this->price_context = new PriceContext( $wc_product, $this->taxes, $this->settings_data );

		return $html . $this->price_context->lowest_price_html( $wc_product );
	}

	/**
	 * Get the lowest price HTML.
	 *
	 * @since 1.0
	 *
	 * @param \WC_Product|\WC_Product_Variable $wc_product WC Product.
	 *
	 * @return string
	 */
	public function lowest_price_html(  $wc_product ): string {

		_deprecated_function( __METHOD__, '{VERSION}', 'PriorPrice\PriceContext::lowest_price_html' );

		return $this->price_context->lowest_price_html( $wc_product );
	}

	/**
	 * Get the lowest price raw value (non-taxed).
	 *
	 * @since 2.1
	 *
	 * @param \WC_Product $wc_product WC Product.
	 *
	 * @return float
	 */
	public function get_lowest_price_raw_non_taxed( \WC_Product $wc_product ): float {

		_deprecated_function( __METHOD__, '{VERSION}', 'PriorPrice\Taxes::get_lowest_price_raw_non_taxed' );

		return $this->taxes->get_lowest_price_raw_non_taxed( $wc_product );
	}

	/**
	 * Get the lowest price raw value (taxed).
	 *
	 * @since 2.1
	 *
	 * @param \WC_Product $wc_product WC Product.
	 *
	 * @return float
	 */
	public function get_lowest_price_raw_taxed( \WC_Product $wc_product ): float {

		_deprecated_function( __METHOD__, '{VERSION}', 'PriorPrice\Taxes::get_lowest_price_raw_taxed' );

		return $this->taxes->get_lowest_price_raw_taxed( $wc_product );
	}

	/**
	 * Check the current screen if the price HTML should be displayed.
	 *
	 * @since 1.2
	 *
	 * @return bool
	 */
	private function is_correct_place( \WC_Product $wc_product ) : bool {

		$display_on = $this->settings_data->get_display_on();

		$is_correct = (
			( isset( $display_on['shop_page'] ) && is_shop() ) ||
			( isset( $display_on['product_page'] ) && is_product() && ( isset( $display_on['related_products'] ) || $this->is_main_product( $wc_product ) ) ) ||
			( isset( $display_on['category_page'] ) && is_product_category() ) ||
			( isset( $display_on['tag_page'] ) && is_product_tag() )
		);

		/**
		 * Filter if the price HTML should be displayed on the current screen.
		 *
		 * @since 1.8.0
		 *
		 * @param bool        $is_correct Is correct place.
		 * @param \WC_Product $wc_product WC Product.
		 *
		 * @return bool
		 */
		return apply_filters( 'wc_price_history_is_correct_place', $is_correct, $wc_product );
	}

	/**
	 * Check if product is on sale and site is set to display products on sale.
	 *
	 * @since 1.2
	 *
	 * @param \WC_Product $wc_product WC Product.
	 *
	 * @return bool
	 */
	private function is_not_correct_when( \WC_Product $wc_product ) : bool {

		$display_when = $this->settings_data->get_display_when();

		$is_not_correct_when = $display_when === 'on_sale' && ! $wc_product->is_on_sale();

		/**
		 * Filter if the price HTML should not be displayed when conditions are not met (eg. it is set to display only on sale and product is not on sale).
		 *
		 * @since 1.8.0
		 *
		 * @param bool        $is_not_correct_when Is not correct when.
		 * @param \WC_Product $wc_product          WC Product.
		 *
		 * @return bool
		 */
		return apply_filters( 'wc_price_history_is_not_correct_when', $is_not_correct_when, $wc_product );
	}

	/**
	 * Check if the product is the main product on the page.
	 *
	 * @since 1.6
	 *
	 * @param \WC_Product $wc_product WC Product.
	 *
	 * @return bool
	 */
	private function is_main_product( \WC_Product $wc_product ) : bool {

		global $wp_query;

		return isset( $wp_query->queried_object_id ) && $wp_query->queried_object_id === $wc_product->get_id();
	}
}
