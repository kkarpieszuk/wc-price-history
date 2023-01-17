<?php

namespace PriorPrice;

class Taxes {

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
}
