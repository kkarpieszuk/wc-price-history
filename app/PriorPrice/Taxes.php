<?php

namespace PriorPrice;

class Taxes {

	/**
	 * @var \PriorPrice\SettingsData
	 */
	private $settings_data;

	/**
	 * @var \PriorPrice\HistoryStorage
	 */
	private $history_storage;

	/**
	 * Constructor.
	 *
	 * @since 2.1
	 *
	 * @param SettingsData $settings_data Settings data.
	 * @param HistoryStorage $history_storage History storage.
	 */
	public function __construct( SettingsData $settings_data, HistoryStorage $history_storage ) {

		$this->settings_data = $settings_data;
		$this->history_storage = $history_storage;
	}

	/**
	 * Apply taxes to the price.
	 *
	 * @since 1.6.2
	 *
	 * @param float       $price
	 * @param \WC_Product $wc_product
	 *
	 * @return float
	 */
	public function apply_taxes( float $price, \WC_Product $wc_product ) : float {

		return 'incl' === get_option( 'woocommerce_tax_display_shop' ) ?
			(float) wc_get_price_including_tax( $wc_product, [ 'price' => $price ] ) :
			(float) wc_get_price_excluding_tax( $wc_product, [ 'price' => $price ] );
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

		$days_number = $this->settings_data->get_days_number();
		$count_from  = $this->settings_data->get_count_from();

		if ( in_array( $count_from, [ 'sale_start', 'sale_start_inclusive' ] ) && $wc_product->is_on_sale() ) {
			return $this->history_storage->get_minimal_from_sale_start( $wc_product, $days_number, $count_from );
		}

		return (float) $this->history_storage->get_minimal( $wc_product->get_id(), $days_number );
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

		$price = $this->get_lowest_price_raw_non_taxed( $wc_product );

		return $this->apply_taxes( $price, $wc_product );
	}
}
