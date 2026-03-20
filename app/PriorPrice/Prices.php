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

		/**
		 * Filter the lowest price HTML before displaying it.
		 *
		 * @since 3.0.0
		 *
		 * @param bool|float $lowest_pre Lowest price HTML.
		 * @param \WC_Product $wc_product WC Product.
		 */
		$lowest_pre = apply_filters( 'wc_price_history_lowest_price_html_pre', false, $wc_product );

		$days_number = $this->settings_data->get_days_number();

		if ( $lowest_pre !== false && is_numeric( $lowest_pre ) ) {
			return $this->display_from_template( $lowest_pre, $days_number, $wc_product );
		}

		// Variable product (main product, not a variation): show placeholder until variant is selected.
		if ( $wc_product instanceof \WC_Product_Variable
			&& $this->settings_data->get_variable_product_defer_lowest_price() ) {
			$placeholder = $this->settings_data->get_variable_product_defer_placeholder_text();
			if ( $placeholder !== '' ) {
				return sprintf(
					'<div class="wc-price-history prior-price lowest" data-product-id="%s"><span class="wc-price-history-lowest-inner">%s</span></div>',
					$wc_product->get_id(),
					$placeholder
				);
			}
		}

		$lowest = $this->get_lowest_price_raw_non_taxed( $wc_product );
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

		return $this->display_from_template( $lowest, $days_number, $wc_product );
	}

	/**
	 * Get the lowest price raw value (non-taxed).
	 *
	 * @since 2.1
	 *
	 * @param \WC_Product $wc_product WC Product.
	 *
	 * @return float
	 */
	public function get_lowest_price_raw_non_taxed( \WC_Product $wc_product ): float {

		$days_number = $this->settings_data->get_days_number();
		$count_from  = $this->settings_data->get_count_from();

		if ( in_array( $count_from, [ 'sale_start', 'sale_start_inclusive' ] ) && $wc_product->is_on_sale() ) {
			return $this->history_storage->get_minimal_from_sale_start( $wc_product, $days_number, $count_from );
		}

		return (float) $this->history_storage->get_minimal( $wc_product->get_id(), $days_number );
	}

	/**
	 * Get the lowest price raw value (taxed).
	 *
	 * @since 2.1
	 *
	 * @param \WC_Product $wc_product WC Product.
	 *
	 * @return float
	 */
	public function get_lowest_price_raw_taxed( \WC_Product $wc_product ): float {

		$price = $this->get_lowest_price_raw_non_taxed( $wc_product );

		return $this->taxes->apply_taxes( $price, $wc_product );
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
	 * For variable products, the main product is the queried object.
	 * For variations, the main product is the parent variable product (so variation
	 * price_html in get_available_variations() includes lowest price on Blocks template).
	 *
	 * @since 1.6
	 *
	 * @param \WC_Product $wc_product WC Product.
	 *
	 * @return bool
	 */
	private function is_main_product( \WC_Product $wc_product ) : bool {

		global $wp_query;

		if ( ! isset( $wp_query->queried_object_id ) ) {
			return false;
		}

		$queried_id = $wp_query->queried_object_id;

		if ( $wc_product->get_id() === $queried_id ) {
			return true;
		}

		// Variation on single product page: show lowest price for variant in Blocks template.
		if ( $wc_product instanceof \WC_Product_Variation && $wc_product->get_parent_id() === $queried_id ) {
			return true;
		}

		return false;
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
			$price_raw_non_taxed = apply_filters(
				'wc_price_history_price_raw_non_taxed',
				(float) $wc_product->get_price(),
				$wc_product
			);

			return $this->display_from_template( $price_raw_non_taxed, $days_number, $wc_product );
		}

		$old_history_custom_text = $this->settings_data->get_old_history_custom_text();

		$old_history_custom_text = str_replace(
			[ '{price}', '{days}' ],
			[
				$this->display_price_value_html( apply_filters(
					'wc_price_history_price_raw_non_taxed',
					(float) $wc_product->get_price(),
					$wc_product
				) ),
				$days_number
			],
			$old_history_custom_text
		);

		return sprintf( '<div class="wc-price-history prior-price lowest" data-product-id="%s"><span class="wc-price-history-lowest-inner">%s</span></div>', $wc_product->get_id(), $old_history_custom_text );
	}

	/**
	 * Display full price HTML from template.
	 *
	 * @since 1.9.0
	 *
	 * @param float       $lowest      Lowest price.
	 * @param int         $days_number Days number.
	 * @param \WC_Product $wc_product  WC Product.
	 *
	 * @return string
	 */
	private function display_from_template( float $lowest, int $days_number, \WC_Product $wc_product ) : string {

		$display_text = $this->settings_data->get_display_text();

		$display_text = str_replace( '{price}', $this->display_price_value_html( $lowest ), $display_text );
		$display_text = str_replace( '{days}', (string) $days_number, $display_text );

		/**
		 * Filter the display text from template.
		 *
		 * @since 3.0.0
		 *
		 * @param string    $display_text Display text.
		 * @param float|int $lowest       Lowest price.
		 * @param int       $days_number  Days number.
		 */
		$display_text = apply_filters( 'wc_price_history_display_from_template', $display_text, $lowest, $days_number );

		return sprintf( '<div class="wc-price-history prior-price lowest" data-product-id="%s" data-original-price="%s"><span class="wc-price-history-lowest-inner">%s</span></div>', $wc_product->get_id(), $lowest, $display_text );
	}
}
