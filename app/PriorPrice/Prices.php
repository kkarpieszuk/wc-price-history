<?php

namespace PriorPrice;

class Prices {

	public function get_lowest( $product_id, $days = 30 ) {

		$args = [
			'numberposts' => -1,
			'date_query'  => [
				'after'     => '30 days ago',
				'inclusive' => true,
			],
		];

		$previous_products = wp_get_post_revisions( $product_id, $args );

		foreach( $previous_products as $previous_product ) {
			$_price = get_post_meta( $previous_product->ID, '_price', true );
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

	public function price_html( $html, $wc_product ) {

		$lowest = $this->get_lowest( $wc_product->id );

		if ( (float) $lowest < 1 ) {
			return $html;
		}

		$lowest_html = '<div class="wc-previous-prices prior-price lowest single">%s</div>';
		/* translators: %s - the lowest price in the last 30 days. */
		$lowest_text = __( '30-day low: %s', 'wc-previous-prices' );
		$with_currency = sprintf( get_woocommerce_price_format(), get_woocommerce_currency_symbol(), $lowest );
		$final = sprintf( $lowest_html, sprintf( $lowest_text, $with_currency ) );

		return $html . $final;
	}

}