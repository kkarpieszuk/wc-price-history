<?php

namespace PriorPrice\PriceDisplayStrategy;

use PriorPrice\SettingsData;
use PriorPrice\Taxes;

abstract class PriceDisplayStrategy {

	/**
	 * @var \PriorPrice\Taxes
	 */
	protected $taxes;

	/**
	 * @var \PriorPrice\SettingsData
	 */
	protected $settings_data;

	/**
	 * Constructor.
	 *
	 * @since {VERSION}
	 *
	 * @param Taxes $taxes Taxes object.
	 * @param SettingsData $settings_data Settings data object.
	 */
	public function __construct( Taxes $taxes, SettingsData $settings_data ) {

		$this->taxes = $taxes;
		$this->settings_data = $settings_data;
	}

	/**
	 * Lowest price HTML.
	 *
	 * @since {VERSION}
	 *
	 * @param \WC_Product $product WC Product.
	 */
	abstract public function lowest_price_html( \WC_Product $product ) : string;
}