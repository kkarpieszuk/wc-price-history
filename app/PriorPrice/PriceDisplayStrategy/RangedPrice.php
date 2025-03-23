<?php

namespace PriorPrice\PriceDisplayStrategy;

class RangedPrice extends PriceDisplayStrategy {

	/**
	 * Get the lowest price HTML.
	 *
	 * @since {VERSION}
	 *
	 * @param \WC_Product_Variable $wc_product WC Product.
	 *
	 * @return string
	 */
	public function lowest_price_html(  $wc_product ): string {

		/**
		 * Filter the lowest price HTML before displaying it.
		 *
		 * @since {VERSION}
		 *
		 * @param bool|float           $lowest_pre Lowest price HTML.
		 * @param \WC_Product_Variable $wc_product WC Product.
		 */
		$lowest_pre = apply_filters( 'wc_price_history_lowest_price_html_pre', false, $wc_product );

		$days_number = $this->settings_data->get_days_number();

		if ( $lowest_pre !== false && is_numeric( $lowest_pre ) ) {
			return $this->display_from_template( [ $lowest_pre ], $days_number, $wc_product );
		}

		return $this->lowest_price_html_as_range( $wc_product, $days_number );
	}

	private function lowest_price_html_as_range( \WC_Product_Variable $wc_product, int $days_number ) : string {

		list( $lowest_price, $highest_price ) = $this->get_lowest_price_only( $wc_product );

		if ( $lowest_price <= 0 && $highest_price <= 0 ) {
			return $this->handle_old_history( $wc_product, $days_number );
		}

		return $this->display_from_template( [ $lowest_price, $highest_price ], $days_number, $wc_product );
	}

	/**
	 * Get the lowest price only.
	 *
	 * @since {VERSION}
	 *
	 * @param \WC_Product_Variable $wc_product WC Product.
	 *
	 * @return array<int, float>
	 */
	public function get_lowest_price_only( $wc_product ): array {

		$all_variations = $wc_product->get_available_variations( 'objects' );

		// Among all variations, find pair of prices: the lowest price and the highest using get_lowest_price_raw_taxed.
		$lowest_price = 0;
		$highest_price = 0;

		foreach ( $all_variations as $variation ) {

			if ( ! $variation instanceof \WC_Product_Variation ) {
				continue;
			}

			$price = $this->taxes->get_lowest_price_raw_taxed( $variation );

			if ( $price < $lowest_price || $lowest_price === 0 ) {
				$lowest_price = $price;
			}

			if ( $price > $highest_price || $highest_price === 0 ) {
				$highest_price = $price;
			}
		}

		return [ $lowest_price, $highest_price ];
	}

	/**
	 * Handle history older than x days (returned price is 0).
	 *
	 * @since {VERSION}
	 *
	 * @param \WC_Product $wc_product WC Product.
	 * @param int         $days_number Days number.
	 *
	 * @return string
	 */
	private function handle_old_history( \WC_Product $wc_product, int $days_number ) : string {

		$old_history = $this->settings_data->get_old_history();

		if ( $old_history === 'hide' ) {
			return '';
		}

		if ( $old_history === 'current_price' ) {
			return $this->display_from_template( [ (float) $wc_product->get_price() ], $days_number, $wc_product );
		}

		$old_history_custom_text = $this->settings_data->get_old_history_custom_text();

		$old_history_custom_text = str_replace(
			[ '{price}', '{days}' ],
			[ $this->display_price_value_html( [ (float) $wc_product->get_price() ] ), $days_number ],
			$old_history_custom_text
		);

		return '<div class="wc-price-history prior-price lowest">' . $old_history_custom_text . '</div>';
	}

	/**
	 * Display full price HTML from template.
	 *
	 * @since {VERSION}
	 *
	 * @param array<int, float> $lowest     Lowest price.
	 * @param int          $days_number Days number.
	 *
	 * @return string
	 */
	private function display_from_template( array $lowest, int $days_number, \WC_Product $wc_product ) : string {

		$display_text = $this->settings_data->get_display_text();
		$formatted    = [];

		$formatted = $this->display_price_value_html( $lowest );

		$display_text = str_replace( '{price}', $formatted, $display_text );
		$display_text = str_replace( '{days}', (string) $days_number, $display_text );

		/**
		 * Filter the display text from template.
		 *
		 * @since {VERSION}
		 *
		 * @param string    $display_text Display text.
		 * @param float|int $lowest       Lowest price.
		 * @param int       $days_number  Days number.
		 */
		$display_text = apply_filters( 'wc_price_history_display_from_template', $display_text, $lowest[0], $days_number );

		return '<div class="wc-price-history prior-price lowest"
					data-product-id="' . $wc_product->get_id() . '"
					data-product-type="' . $wc_product->get_type() . '"
					>' . $display_text . '</div>';
	}

	/**
	 * Display price value HTML.
	 *
	 * Optionally adds CSS classes to style it.
	 *
	 * @since {VERSION}
	 *
	 * @param array<int, float> $price Price.
	 *
	 * @return string
	 */
	public function display_price_value_html( array $price ) : string {

		$line_through_class = $this->settings_data->get_display_line_through() ? 'line-through' : '';
		$price_format       = get_woocommerce_price_format();

		$formatted = [];

		foreach ( $price as $p ) {
			$formatted[] = wc_price(
				$p,
				[
					'price_format' => $price_format,
				]
			);
		}

		$wc_price = implode( ' - ', $formatted );

		return '<span class="wc-price-history prior-price-value ' . $line_through_class .'"><span class="wc-price-history-lowest-raw-value">' . $wc_price . '</span></span>';
	}

}