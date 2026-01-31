<?php

namespace PriorPrice;

use PriorPrice\C11y\WPSheetEditor;
use WC_Product;
use WC_Product_Variable;

class ProductUpdates {

	/**
	 * @var \PriorPrice\HistoryStorage
	 */
	private $history_storage;

	/**
	 * @var WPSheetEditor
	 */
	private $wpse;

	public function __construct( HistoryStorage $history_storage, WPSheetEditor $wpse ) {

		$this->history_storage = $history_storage;
		$this->wpse            = $wpse;
	}

	/**
	 * Register hooks.
	 *
	 * @since 1.1
	 */
	public function register_hooks(): void {

		add_action( 'woocommerce_new_product', [ $this, 'start_price_history' ] );
		add_action( 'woocommerce_new_product_variation', [ $this, 'start_price_history' ] );
		add_action( 'woocommerce_update_product', [ $this, 'update_price_history' ] );
		add_action( 'woocommerce_save_product_variation', [ $this, 'update_price_history' ] );
	}

	/**
	 * Update price history.
	 *
	 * @since 1.1
	 *
	 * @param int $product_id Product ID.
	 */
	public function update_price_history( int $product_id ): void {

		if ( get_post_status( $product_id ) === 'draft' ) {
			return;
		}

		$product = wc_get_product( $product_id );

		if ( ! $product ) {
			return;
		}

		$price = (float) $product->get_price();
		$skip  = $this->wpse->should_skip_price_recording( $product_id, $price );
		$skip  = (bool) apply_filters( 'wc_price_history_skip_recording', $skip, $product_id, $price );

		if ( $skip ) {
			return;
		}

		remove_action( 'woocommerce_update_product', [ $this, 'update_price_history' ] );
		$this->history_storage->add_price( $product_id, $price, false );
		if ( $product->is_type( 'variable' ) ) {
			/** @var WC_Product_Variable $product */
			$this->maybe_update_price_history_for_variation( $product );
		}
		add_action( 'woocommerce_update_product', [ $this, 'update_price_history' ] );
	}

	/**
	 * Start price history.
	 *
	 * @since 1.7.4
	 *
	 * @param int $product_id Product ID.
	 */
	public function start_price_history( int $product_id ): void {

		if ( ProductDuplicate::$is_during_duplication ) {
			return;
		}

		if ( get_post_status( $product_id ) === 'draft' ) {
			return;
		}

		$product = wc_get_product( $product_id );

		if ( ! $product ) {
			return;
		}

		$price = (float) $product->get_price();
		if ( $this->wpse->should_skip_price_recording( $product_id, $price ) ) {
			return;
		}
		$this->history_storage->add_first_price( $product_id, $price );
	}

	/**
	 * Update price history for variations.
	 *
	 * @since 2.1.3
	 *
	 * @param WC_Product_Variable $product Product.
	 */
	private function maybe_update_price_history_for_variation( WC_Product_Variable $product ): void {

		$variations = $product->get_available_variations( 'objects' );
		foreach ( $variations as $variation ) {
			/** @var WC_Product $variation */
			$price = (float) $variation->get_price();
			if ( ! $this->wpse->should_skip_price_recording( $variation->get_id(), $price ) ) {
				$this->history_storage->add_price( $variation->get_id(), $price, false );
			}
		}
	}
}
