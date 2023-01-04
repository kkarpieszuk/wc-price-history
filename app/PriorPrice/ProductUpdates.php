<?php

namespace PriorPrice;

class ProductUpdates {

	/**
	 * @var \PriorPrice\HistoryStorage
	 */
	private $history_storage;

	public function __construct( HistoryStorage $history_storage ) {

		$this->history_storage = $history_storage;
	}

	/**
	 * Register hooks.
	 *
	 * @since 1.1
	 */
	public function register_hooks(): void {

		add_action( 'woocommerce_new_product', [ $this, 'update_price_history' ] );
		add_action( 'woocommerce_update_product', [ $this, 'update_price_history' ] );
	}

	/**
	 * Update price history.
	 *
	 * @since 1.1
	 *
	 * @param int $product_id Product ID.
	 */
	public function update_price_history( int $product_id ): void {

		$product_price = get_post_meta( $product_id, '_price', true );

		$this->history_storage->add_price( $product_id, (float) $product_price, true );
	}
}
