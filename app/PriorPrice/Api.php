<?php

namespace PriorPrice;

use PriorPrice\Taxes;

/**
 * Api class.
 *
 * @since {VERSION}
 */
class Api {

	/**
	 * @var \PriorPrice\Taxes
	 */
	private $taxes;

	/**
	 * Constructor.
	 *
	 * @since {VERSION}
	 *
	 * @param Taxes $taxes Taxes.
	 */
	public function __construct( Taxes $taxes ) {

		$this->taxes = $taxes;
	}

	/**
	 * Register API hooks.
	 *
	 * @since {VERSION}
	 *
	 * @return void
	 */
	public function register_hooks(): void {

		add_filter( 'wcpricehistory/api/get_lowest_price', [ $this, 'get_lowest_price' ], 10, 2 );
	}

	/**
	 * Get lowest price for a given product.
	 *
	 * @since {VERSION}
	 *
	 * @param float $price Product price.
	 * @param int   $id    Product id.
	 *
	 * @return float
	 */
	public function get_lowest_price( $price, $id = 0 ): float {

		$product = wc_get_product( $id );

		if ( ! $product instanceof \WC_Product ) {
			return $price;
		}

		return $this->taxes->get_lowest_price_raw_non_taxed( $product );
	}
}
