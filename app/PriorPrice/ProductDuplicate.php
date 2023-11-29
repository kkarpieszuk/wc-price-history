<?php

namespace PriorPrice;

use WC_Product;

/**
 * Class ProductDuplicate
 *
 * Handles product duplication logic.
 *
 * @since 1.8.0
 */
class ProductDuplicate {

	/**
	 * @var bool
	 */
	public static $is_during_duplication = false;

	/**
	 * Register hooks.
	 *
	 * @since 1.8.0
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'woocommerce_product_duplicate_before_save', [ $this, 'flag_as_duplication_process' ], 10, 1 );

		add_action( 'woocommerce_product_duplicate', [ $this, 'delete_history_from_duplicate' ], 10, 1 );
	}

	/**
	 * Flag as duplication process.
	 *
	 * @since 1.8.0
	 *
	 * @param WC_Product $duplicate
	 *
	 * @return void
	 */
	public function flag_as_duplication_process( $duplicate ): void {

		self::$is_during_duplication = true;
	}

	/**
	 * Delete history from duplicate.
	 *
	 * @since 1.8.0
	 *
	 * @param int $duplicate_id
	 *
	 * @return void
	 */
	public function delete_history_from_duplicate( $duplicate_id ): void {

		$duplicate = wc_get_product( $duplicate_id );

		if ( ! $duplicate ) {
			return;
		}

		delete_post_meta( $duplicate->get_id(), '_wc_price_history' );
	}
}
