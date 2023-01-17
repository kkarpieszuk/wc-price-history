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

	/**
	 * @var \PriorPrice\Taxes
	 */
	private $taxes;

	/**
	 * Constructor.
	 *
	 * @since 1.0
	 * @since 1.6.2 uses Taxes class.
	 *
	 * @param \PriorPrice\HistoryStorage $history_storage Prices object.
	 * @param \PriorPrice\SettingsData   $settings_data   Settings data object.
	 * @param \PriorPrice\Taxes          $taxes           Taxes object.
	 */
	public function __construct( HistoryStorage $history_storage, SettingsData $settings_data, Taxes $taxes ) {

		$this->history_storage = $history_storage;
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

		$lowest = $this->taxes->apply_taxes( $lowest, $wc_product );

		if ( (float) $lowest <= 0 ) {
			return '';
		}

		$lowest_html     = '<div class="wc-price-history prior-price lowest">%s</div>';
		$lowest_template = str_replace( [ '{price}', '{days}' ], [ wc_price( $lowest ), $days_number ], $this->settings_data->get_display_text() );

		return sprintf( $lowest_html, $lowest_template );
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

		return (
			( isset( $display_on['shop_page'] ) && is_shop() ) ||
			( isset( $display_on['product_page'] ) && is_product() && ( isset( $display_on['related_products'] ) || $this->is_main_product( $wc_product ) ) ) ||
			( isset( $display_on['category_page'] ) && is_product_category() ) ||
			( isset( $display_on['tag_page'] ) && is_product_tag() )
		);
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

		if ( $display_when === 'on_sale' && ! $wc_product->is_on_sale() ) {
			return true;
		}

		return false;
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
