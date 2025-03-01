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

	abstract public function lowest_price_html( \WC_Product $product ) : string;

	public function __construct( Taxes $taxes, SettingsData $settings_data ) {

		$this->taxes = $taxes;
		$this->settings_data = $settings_data;
	}

		/**
	 * Display price value HTML.
	 *
	 * Optionally adds CSS classes to style it.
	 *
	 * @since 1.7
	 *
	 * @param float $price Price.
	 *
	 * @return string
	 */
	protected function display_price_value_html( float $price ) : string {

		$line_through_class = $this->settings_data->get_display_line_through() ? 'line-through' : '';
		$price_format       = get_woocommerce_price_format();
		$price_format       = str_replace( '%2$s', '<span class="wc-price-history-lowest-raw-value">%2$s</span>', $price_format );

		$wc_price = wc_price(
			$price,
			[
				'price_format' => $price_format,
			]
		);

		return '<span class="wc-price-history prior-price-value ' . $line_through_class .'">' . $wc_price . '</span>';
	}
}