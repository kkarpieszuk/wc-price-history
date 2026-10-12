<?php

namespace PriorPrice;

/**
 * Resolve the effective sale start date used for Omnibus lowest-price calculation.
 *
 * @since 3.2.7
 */
class SaleStartDate {

	/**
	 * Resolve sale start date for a product.
	 *
	 * Variable products often store schedule dates on variations only. When the parent
	 * has no sale start date, use the earliest get_date_on_sale_from() among on-sale
	 * child variations so parent/listing/shortcode paths do not fall back to the last
	 * 30 days (which includes the promotional price).
	 *
	 * @since 3.2.7
	 *
	 * @param \WC_Product $product WC Product.
	 *
	 * @return \WC_DateTime|null
	 */
	public function resolve( \WC_Product $product ) {

		$sale_start = $product->get_date_on_sale_from();

		if ( $sale_start ) {
			return $sale_start;
		}

		if ( ! $product instanceof \WC_Product_Variable ) {
			return null;
		}

		$earliest = null;

		foreach ( $product->get_available_variations( 'objects' ) as $variation ) {
			if ( ! $variation instanceof \WC_Product ) {
				continue;
			}

			if ( ! $variation->is_on_sale() ) {
				continue;
			}

			$variation_sale_start = $variation->get_date_on_sale_from();

			if ( ! $variation_sale_start ) {
				continue;
			}

			$earliest_ts  = $earliest ? (int) $earliest->getTimestamp() : null;
			$candidate_ts = (int) $variation_sale_start->getTimestamp();

			if ( $earliest === null || $candidate_ts < $earliest_ts ) {
				$earliest = $variation_sale_start;
			}
		}

		return $earliest;
	}
}
