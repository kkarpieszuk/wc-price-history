<?php

namespace PriorPrice;

use WC_Product_Variable;
use WC_Product_Variation;

/**
 * Variations class.
 *
 * @since 2.1
 */
class Variations {
	/**
	 * @var \PriorPrice\Taxes
	 */
	private $taxes;

	/**
	 * Constructor.
	 *
	 * @since 2.1
	 *
	 * @param Prices $prices Prices object.
	 * @param Taxes  $taxes  Taxes object.
	 */
	/** @phpstan-ignore constructor.unusedParameter */
	public function __construct( Prices $_prices, Taxes $taxes ) { // phpcs:ignore Generic.CodeAnalysis.UnusedParameters.UnusedParameter

		$this->taxes  = $taxes;
	}

	/**
	 * Register hooks.
	 *
	 * @since 2.1
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_filter( 'woocommerce_available_variation', [ $this, 'add_history' ], 10, 3 );
	}

	/**
	 * Add history to variation.
	 *
	 * @since 2.1
	 *
	 * @param array<string>        $variation_attributes Attributes.
	 * @param WC_Product_Variable  $product_variable     Parent variable product.
	 * @param WC_Product_Variation $variation            Processed variation.
	 *
	 * @return array<float|string>
	 */
	public function add_history( array $variation_attributes, WC_Product_Variable $product_variable, WC_Product_Variation $variation ) : array {

		$price_format       = get_woocommerce_price_format();

		$price = (float) $this->taxes->get_lowest_price_raw_taxed( $variation );

		$wc_price = wc_price(
			$price,
			[
				'price_format' => $price_format,
			]
		);

		$variation_attributes['_wc_price_history_lowest_price'] = $wc_price;

		return $variation_attributes;
	}
}
