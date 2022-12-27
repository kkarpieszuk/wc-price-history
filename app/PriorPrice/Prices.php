<?php

namespace PriorPrice;

class Prices {

	/**
	 * Get price HTML filter.
	 *
	 * Display under the price in front-end lowest price information or compatibility warning.
	 *
	 * @since 1.0
	 *
	 * @param string      $html       HTML code which displays product price on front-end.
	 * @param \WC_Product $wc_product WC Product.
	 *
	 * @return string
	 */
	public function get_price_html( $html, $wc_product ) {

		if ( ! ( new Compatibility() )->check_revisions_settings( $wc_product ) ) {
			return $html . ( new Compatibility() )->get_revisions_warning();
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
	public function lowest_price_html( $wc_product ): string {

		$lowest = $this->get_lowest( $wc_product->get_id() );

		if ( (float) $lowest < 1 ) {
			return '';
		}

		$lowest_html = '<div class="wc-price-history prior-price lowest">%s</div>';
		/* translators: %s - the lowest price in the last 30 days. */
		$lowest_text = __( '30-day low: %s', 'wc-price-history' );
		$with_currency = sprintf( get_woocommerce_price_format(), get_woocommerce_currency_symbol(), $lowest );
		$final = sprintf( $lowest_html, sprintf( $lowest_text, $with_currency ) );

		return $final;
	}

	/**
	 * Get the lowest price in time span.
	 *
	 * @since 1.0
	 *
	 * @param int $product_id Product ID.
	 * @param int $days       Time span since today in days, default 30.
	 *
	 * @return float
	 */
	private function get_lowest( $product_id, $days = 30 ): float {

		$args = [
			'numberposts' => -1,
			'date_query'  => [
				'after'     => sprintf( '%d days ago', $days ),
				'inclusive' => true,
			],
		];

		$product_revisions   = wp_get_post_revisions( $product_id, $args );
		$product_revisions[] = get_post( $product_id ); // Include current product.

		foreach( $product_revisions as $revision ) {
			$_price = get_post_meta( $revision->ID, '_price', true );
			if ( (float) $_price > 0 ) {
				$prices[] = $_price;
			}
		}

		$min = array_reduce( $prices, function( $carry, $item ) {

			if ( (float) $item > 0 && $carry > 0 ) {
				return min( (float) $carry, (float) $item );
			}

			return $item;
		} );

		return $min;
	}
}
