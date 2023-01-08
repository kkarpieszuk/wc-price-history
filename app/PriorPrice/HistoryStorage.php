<?php

namespace PriorPrice;

/**
 * HistoryStorage class
 *
 * @since 1.1
 */
class HistoryStorage {

	/**
	 * Custom field key.
	 *
	 * @since 1.1
	 *
	 * @var string
	 */
	private const cf_key = '_wc_price_history';

	/**
	 * Get minimal price for $product_id in last $days.
	 *
	 * @since 1.1
	 *
	 * @param int $product_id Product ID.
	 * @param int $days       Days span.
	 *
	 * @return float
	 */
	public function get_minimal( int $product_id, int $days = 30 ) : float {

		$history = $this->get_history( $product_id );

		// Get only $days last items.
		$the_last = array_filter(
			$history,
			static function( $timestamp ) use ( $days ) {
				return $timestamp >= ( time() - ( $days * DAY_IN_SECONDS ) );
			},
			ARRAY_FILTER_USE_KEY
		);

		// Reduce to the float with minimal value (but bigger than zero).
		return (float) array_reduce(
			$the_last,
			static function( $carry, $item ) {

				if ( (float) $item > 0 && $carry > 0 ) {
					return min( (float) $carry, (float) $item );
				}

				return (float) $item;
			}
		);
	}

	/**
	 * Get minimal price for $product_id in last $days from sale start.
	 *
	 * @since 1.2
	 *
	 * @param \WC_Product $wc_product WC Product.
	 * @param int         $days       Days span.
	 *
	 * @return float
	 */
	public function get_minimal_from_sale_start( \WC_Product $wc_product, int $days = 30 ) : float {

		$sale_start = $wc_product->get_date_on_sale_from();

		if ( ! $sale_start ) {
			$logger = wc_get_logger();
			$link   = get_edit_post_link( $wc_product->get_id() );

			$logger->error(
				/* translators: %d product id, %s link to product edit screen. */
				sprintf( esc_html__( 'Product #%1$d is on sale but has no sale start date. Please edit this product and set starting date for sale: %2$s', 'wc-price-history' ), $wc_product->get_id() , $link),
				[
					'source' => 'wc-price-history',
				]
			);

			return $this->get_minimal( $wc_product->get_id(), $days );
		}

		$sale_start_timestamp = $sale_start->getTimestamp();
		$history              = $this->get_history( $wc_product->get_id() );

		// Get only $days last items.
		$the_last = array_filter(
			$history,
			static function( $timestamp ) use ( $days, $sale_start_timestamp ) {
				return $timestamp >= ( $sale_start_timestamp - ( $days * DAY_IN_SECONDS ) ) && $timestamp <= $sale_start_timestamp;
			},
			ARRAY_FILTER_USE_KEY
		);

		// Reduce to the float with minimal value (but bigger than zero).
		return (float) array_reduce(
			$the_last,
			static function( $carry, $item ) {

				if ( (float) $item > 0 && $carry > 0 ) {
					return min( (float) $carry, (float) $item );
				}

				return (float) $item;
			}
		);
	}

	/**
	 * Add price to the history.
	 *
	 * @since 1.1
	 *
	 * @param int   $product_id Product ID.
	 * @param float $price      Price.
	 *
	 * @return int
	 */
	public function add_price( int $product_id, float $price, bool $on_change_only ): int {

		$history = $this->get_history( $product_id );

		if ( $on_change_only && end( $history ) === $price ) {
			return 0;
		}

		$history[ time() ] = $price;

		return $this->save_history( $product_id, $history );
	}

	/**
	 * Add price to the history at given timestamp.
	 *
	 * @since 1.1
	 *
	 * @param int   $product_id
	 * @param float $price
	 * @param int   $timestamp
	 *
	 * @return int
	 */
	public function add_historical_price( int $product_id, float $price, int $timestamp ): int {

		$history = $this->get_history( $product_id );

		$history[ $timestamp ] = $price;

		return $this->save_history( $product_id, $history );
	}

	/**
	 * Get pricing history for $product_id.
	 *
	 * @since 1.1
	 *
	 * @param int $product_id Product ID.
	 *
	 * @return array<int, float>
	 */
	public function get_history( int $product_id ) : array {

		$meta = get_post_meta( $product_id, self::cf_key, true );

		return empty( $meta ) ? [] : $meta;
	}

	/**
	 * Save history.
	 *
	 * @since 1.1
	 *
	 * @param int               $product_id
	 * @param array<int, float> $history
	 *
	 * @return int
	 */
	public function save_history( int $product_id, array $history ): int {
		return (int) update_post_meta( $product_id, self::cf_key, $history );
	}
}
