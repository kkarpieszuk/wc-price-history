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
	 * @var \PriorPrice\Prices
	 */
	private $prices;

	/**
	 * Constructor.
	 *
	 * @since 2.1
	 *
	 * @param Prices $prices Prices object.
	 */
	public function __construct( Prices $prices ) {

		$this->prices = $prices;
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

		$lowest = $this->prices->get_lowest_price_raw_taxed( $variation );

		if ( $lowest <= 0 ) {
			$current_price = $variation->get_price();
			$lowest = $current_price > 0 ? $this->prices->apply_taxes( (float) $current_price, $variation ) : 0;
		}

		/**
		 * Filter the lowest price for variations.
		 *
		 * @since 3.0.0
		 *
		 * @param float|string         $lowest               Lowest price.
		 * @param array                $variation_attributes Variation attributes.
		 * @param WC_Product_Variable  $product_variable     Parent variable product.
		 * @param WC_Product_Variation $variation            Processed variation.
		 *
		 * @return float
		 */
		$lowest = apply_filters( 'wc_price_history_variations_add_history_lowest_price', $lowest, $variation_attributes, $product_variable, $variation );

		$variation_attributes['_wc_price_history_lowest_price'] = (float) $lowest;

		return $variation_attributes;
	}
}
