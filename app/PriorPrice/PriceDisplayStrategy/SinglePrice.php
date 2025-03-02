<?php

namespace PriorPrice\PriceDisplayStrategy;

class SinglePrice extends PriceDisplayStrategy {

	/**
	 * Get the lowest price HTML.
	 *
	 * @since {VERSION}
	 *
	 * @param \WC_Product $wc_product WC Product.
	 *
	 * @return string
	 */
	public function lowest_price_html( \WC_Product $wc_product ): string {

		/**
		 * Filter the lowest price HTML before displaying it.
		 *
		 * @since {VERSION}
		 *
		 * @param bool|float $lowest_pre Lowest price HTML.
		 * @param \WC_Product $wc_product WC Product.
		 */
		$lowest_pre = apply_filters( 'wc_price_history_lowest_price_html_pre', false, $wc_product );

		$days_number = $this->settings_data->get_days_number();

		if ( $lowest_pre !== false && is_numeric( $lowest_pre ) ) {
			return $this->display_from_template( $lowest_pre, $days_number );
		}


		$lowest = $this->taxes->get_lowest_price_raw_taxed( $wc_product );
		/**
		 * Filter the lowest price raw value before displaying it as HTML (taxes already applied).
		 *
		 * @since {VERSION}
		 *
		 * @param float       $lowest     Lowest price.
		 * @param \WC_Product $wc_product WC Product.
		 */
		$lowest = apply_filters( 'wc_price_history_lowest_price_html_raw_value_taxed', $lowest, $wc_product );

		if ( (float) $lowest <= 0 ) {
			return $this->handle_old_history( $wc_product, $days_number );
		}

		return $this->display_from_template( $lowest, $days_number );
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
			return $this->display_from_template( (float) $wc_product->get_price(), $days_number );
		}

		$old_history_custom_text = $this->settings_data->get_old_history_custom_text();

		$old_history_custom_text = str_replace(
			[ '{price}', '{days}' ],
			[ $this->display_price_value_html( (float) $wc_product->get_price() ), $days_number ],
			$old_history_custom_text
		);

		return '<div class="wc-price-history prior-price lowest">' . $old_history_custom_text . '</div>';
	}

	/**
	 * Display full price HTML from template.
	 *
	 * @since {VERSION}
	 *
	 * @param float $lowest     Lowest price.
	 * @param int   $days_number Days number.
	 *
	 * @return string
	 */
	private function display_from_template( float $lowest, int $days_number ) : string {

		$display_text = $this->settings_data->get_display_text();

		$display_text = str_replace( '{price}', $this->display_price_value_html( $lowest ), $display_text );
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
		$display_text = apply_filters( 'wc_price_history_display_from_template', $display_text, $lowest, $days_number );

		return '<div class="wc-price-history prior-price lowest">' . $display_text . '</div>';
	}

	/**
	 * Display price value HTML.
	 *
	 * Optionally adds CSS classes to style it.
	 *
	 * @since {VERSION}
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