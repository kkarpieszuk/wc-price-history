<?php

namespace PriorPrice;

use WC_Product;
use WP_REST_Response;

/**
 * Adds optional wc_price_history data to WooCommerce REST product and variation responses.
 *
 * @since 3.2.5
 */
class ProductRestApi {

	/**
	 * @var HistoryStorage
	 */
	private $history_storage;

	/**
	 * @var Prices
	 */
	private $prices;

	/**
	 * @var SettingsData
	 */
	private $settings_data;

	/**
	 * Constructor.
	 *
	 * @since 3.2.5
	 *
	 * @param HistoryStorage $history_storage History storage.
	 * @param Prices         $prices          Prices helper.
	 * @param SettingsData   $settings_data   Plugin settings.
	 */
	public function __construct( HistoryStorage $history_storage, Prices $prices, SettingsData $settings_data ) {

		$this->history_storage = $history_storage;
		$this->prices          = $prices;
		$this->settings_data  = $settings_data;
	}

	/**
	 * Register hooks.
	 *
	 * @since 3.2.5
	 *
	 * @return void
	 */
	public function register_hooks(): void {

		add_filter( 'woocommerce_rest_prepare_product_object', [ $this, 'prepare_product_response' ], 10, 3 );
		add_filter( 'woocommerce_rest_prepare_product_variation_object', [ $this, 'prepare_product_response' ], 10, 3 );
	}

	/**
	 * Append wc_price_history to REST response when settings allow.
	 *
	 * @since 3.2.5
	 *
	 * @param WP_REST_Response         $response Response object.
	 * @param WC_Product               $product  Product or variation.
	 * @param \WP_REST_Request $request Request (unused; required by filter signature).
	 *
	 * @return WP_REST_Response
	 */
	public function prepare_product_response( WP_REST_Response $response, $product, $request ): WP_REST_Response { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found

		if ( ! $product instanceof WC_Product ) {
			return $response;
		}

		$show_lowest  = $this->settings_data->get_rest_api_show_lowest_price();
		$show_history = $this->settings_data->get_rest_api_show_full_history();

		if ( ! $show_lowest && ! $show_history ) {
			return $response;
		}

		$data             = $response->get_data();
		$wc_price_history = [];

		if ( $show_lowest ) {
			$wc_price_history['lowest'] = (float) $this->prices->get_lowest_price_raw_taxed( $product );
		}

		if ( $show_history ) {
			$history                       = $this->history_storage->get_history( $product->get_id(), false );
			$wc_price_history['history'] = [];
			foreach ( $history as $timestamp => $price ) {
				$wc_price_history['history'][ (string) (int) $timestamp ] = (float) $price;
			}
		}

		$data['wc_price_history'] = $wc_price_history;
		$response->set_data( $data );

		return $response;
	}
}
