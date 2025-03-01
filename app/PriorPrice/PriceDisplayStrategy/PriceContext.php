<?php

namespace PriorPrice\PriceDisplayStrategy;

use PriorPrice\SettingsData;
use PriorPrice\Taxes;

/**
 * PriceContext class.
 *
 * @since {VERSION}
 */
class PriceContext {

	/**
	 * @var PriceDisplayStrategy
	 */
	private $strategy;

	/**
	 * Constructor.
	 *
	 * @since {VERSION}
	 *
	 * @param \WC_Product $wc_product WC Product.
	 * @param Taxes $taxes Taxes object.
	 * @param SettingsData $settings_data Settings data object.
	 */
	public function __construct(
		\WC_Product $wc_product,
		Taxes $taxes,
		SettingsData $settings_data
	) {

		if ( $wc_product instanceof \WC_Product_Variable &&
			$settings_data->get_variable_product_before_selection() === 'lowest_range') {
			$this->strategy = new RangedPrice( $taxes, $settings_data );

			return;
		}

		$this->strategy = new SinglePrice( $taxes, $settings_data );
	}

	/**
	 * Get the lowest price HTML.
	 *
	 * @since {VERSION}
	 *
	 * @param \WC_Product $product WC Product.
	 *
	 * @return string
	 */
	public function lowest_price_html( \WC_Product $product ) : string {
		return $this->strategy->lowest_price_html( $product );
	}
}