<?php

namespace PriorPrice;

class Prices {

	/**
	 * @var \PriorPrice\HistoryStorage
	 */
	private $history_storage;

	/**
	 * @var \PriorPrice\SettingsData
	 */
	private $settings_data;

	/**
	 * @var \PriorPrice\Taxes
	 */
	private $taxes;

	/**
	 * Constructor.
	 *
	 * @since 1.0
	 * @since 1.6.2 uses Taxes class.
	 *
	 * @param \PriorPrice\HistoryStorage $history_storage Prices object.
	 * @param \PriorPrice\SettingsData   $settings_data   Settings data object.
	 * @param \PriorPrice\Taxes          $taxes           Taxes object.
	 */
	public function __construct( HistoryStorage $history_storage, SettingsData $settings_data, Taxes $taxes ) {

		$this->history_storage = $history_storage;
		$this->settings_data   = $settings_data;
		$this->taxes           = $taxes;
	}

	/**
	 * Register hooks.
	 *
	 * @since 1.1
	 */
	public function register_hooks(): void {

		add_filter( 'woocommerce_get_price_html', [ $this, 'get_price_html' ], 10, 2 );
	}

	/**
	 * Get price HTML filter.
	 *
	 * Display under the price in front-end the lowest price information.
	 *
	 * @since 1.0
	 * @since 1.2 Check display conditions.
	 *
	 * @param string      $html       HTML code which displays product price on front-end.
	 * @param \WC_Product $wc_product WC Product.
	 *
	 * @return string
	 */
	public function get_price_html( string $html, \WC_Product $wc_product ) : string {

		if ( ! $this->is_correct_place( $wc_product ) ) {
			return $html;
		}

		if ( $this->is_not_correct_when( $wc_product ) ) {
			return $html;
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
	public function lowest_price_html( \WC_Product $wc_product ): string {

		$days_number = $this->settings_data->get_days_number();
		$count_from  = $this->settings_data->get_count_from();

		if ( in_array( $count_from, [ 'sale_start', 'sale_start_inclusive' ] ) && $wc_product->is_on_sale() ) {
			$lowest = $this->history_storage->get_minimal_from_sale_start( $wc_product, $days_number, $count_from );
		} else {
			$lowest = $this->history_storage->get_minimal( $wc_product->get_id(), $days_number );
		}

		$lowest = $this->taxes->apply_taxes( $lowest, $wc_product );
		/**
		 * Filter the lowest price raw value before displaying it as HTML (taxes already applied).
		 *
		 * @since 1.7.1
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
	private function display_price_value_html( float $price ) : string {

		$line_through_class = $this->settings_data->get_display_line_through() ? 'line-through' : '';

		return '<span class="wc-price-history prior-price-value ' . $line_through_class .'">' . wc_price( $price ) . '</span>';
	}

	/**
	 * Check the current screen if the price HTML should be displayed.
	 *
	 * @since 1.2
	 *
	 * @return bool
	 */
	private function is_correct_place( \WC_Product $wc_product ) : bool {

		$display_on = $this->settings_data->get_display_on();

		$is_correct = (
			( isset( $display_on['shop_page'] ) && is_shop() ) ||
			( isset( $display_on['product_page'] ) && is_product() && ( isset( $display_on['related_products'] ) || $this->is_main_product( $wc_product ) ) ) ||
			( isset( $display_on['category_page'] ) && is_product_category() ) ||
			( isset( $display_on['tag_page'] ) && is_product_tag() )
		);

		/**
		 * Filter if the price HTML should be displayed on the current screen.
		 *
		 * @since 1.8.0
		 *
		 * @param bool        $is_correct Is correct place.
		 * @param \WC_Product $wc_product WC Product.
		 *
		 * @return bool
		 */
		return apply_filters( 'wc_price_history_is_correct_place', $is_correct, $wc_product );
	}

	/**
	 * Check if product is on sale and site is set to display products on sale.
	 *
	 * @since 1.2
	 *
	 * @param \WC_Product $wc_product WC Product.
	 *
	 * @return bool
	 */
	private function is_not_correct_when( \WC_Product $wc_product ) : bool {

		$display_when = $this->settings_data->get_display_when();

		$is_not_correct_when = $display_when === 'on_sale' && ! $wc_product->is_on_sale();

		/**
		 * Filter if the price HTML should not be displayed when conditions are not met (eg. it is set to display only on sale and product is not on sale).
		 *
		 * @since 1.8.0
		 *
		 * @param bool        $is_not_correct_when Is not correct when.
		 * @param \WC_Product $wc_product          WC Product.
		 *
		 * @return bool
		 */
		return apply_filters( 'wc_price_history_is_not_correct_when', $is_not_correct_when, $wc_product );
	}

	/**
	 * Check if the product is the main product on the page.
	 *
	 * @since 1.6
	 *
	 * @param \WC_Product $wc_product WC Product.
	 *
	 * @return bool
	 */
	private function is_main_product( \WC_Product $wc_product ) : bool {

		global $wp_query;

		return isset( $wp_query->queried_object_id ) && $wp_query->queried_object_id === $wc_product->get_id();
	}

	/**
	 * Handle history older than x days (returned price is 0).
	 *
	 * @since 1.9.0
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
	 * @since 1.9.0
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

		return '<div class="wc-price-history prior-price lowest">' . $display_text . '</div>';
	}
}
