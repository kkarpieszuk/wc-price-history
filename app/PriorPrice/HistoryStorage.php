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
	 * @since 1.9 Changed from private to public.
	 *
	 * @var string
	 */
	public const cf_key = '_wc_price_history';

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

		$this_ = $this;

		// Get only $days last items.
		$the_last = array_filter(
			$history,
			static function( $timestamp ) use ( $days, $this_ ) {
				return $timestamp >= ( $this_->get_time_with_offset() - ( $days * DAY_IN_SECONDS ) );
			},
			ARRAY_FILTER_USE_KEY
		);

		return $this->reduce_to_minimal( $the_last );
	}

	/**
	 * Get minimal price for $product_id in last $days from sale start.
	 *
	 * @since 1.2
	 * @since 1.7 Added $count_from parameter.
	 *
	 * @param \WC_Product $wc_product WC Product.
	 * @param int         $days       Days span.
	 * @param string      $count_from Count from option.
	 *
	 * @return float
	 */
	public function get_minimal_from_sale_start( \WC_Product $wc_product, int $days = 30, string $count_from = 'sale_start' ) : float {

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

		if ( $count_from === 'sale_start_inclusive' ) {
			$sale_start_timestamp = $sale_start->getTimestamp()  + DAY_IN_SECONDS;
		} else {
			$sale_start_timestamp = $sale_start->getTimestamp();
		}

		$history = $this->get_history( $wc_product->get_id() );

		// Get only $days last items.
		$the_last = array_filter(
			$history,
			static function( $timestamp ) use ( $days, $sale_start_timestamp ) {
				return $timestamp >= ( $sale_start_timestamp - ( $days * DAY_IN_SECONDS ) ) && $timestamp <= $sale_start_timestamp;
			},
			ARRAY_FILTER_USE_KEY
		);

		return $this->reduce_to_minimal( $the_last );
	}

	/**
	 * Add price to the history.
	 *
	 * Also saves the price before change with timestamps for last midnight and for 1 second ago.
	 *
	 * @since 1.1
	 * @since 1.7.4 Start saving previous price before change.
	 *
	 * @param int   $product_id Product ID.
	 * @param float $new_price  Price.
	 *
	 * @return int
	 */
	public function add_price( int $product_id, float $new_price, bool $on_change_only ): int {

		$history    = $this->get_history( $product_id );
		$last_price = (float) end( $history );

		if ( $on_change_only && $last_price === $new_price ) {
			return 0;
		}

		$last_timestamp = (int) key( array_slice( $history, -1, 1, true ) );
		$now            = $this->get_time_with_offset();
		$second_ago     = $now - 1;
		$last_midnight  = (int) strtotime( 'midnight' );

		// Check if the last index in $history is lower than $last_midnight.
		// If so, add $price to the history for last midnight.
		if ( $last_timestamp < $last_midnight ) {
			$history[ $last_midnight ] = $last_price;
		}

		// Save also price for $second_ago timestamp.
		if ( $last_timestamp < $second_ago ) {
			$history[ $second_ago ] = $last_price;
		}

		$history[ $now ] = $new_price;

		return $this->save_history( $product_id, $history );
	}

	/**
	 * Add first price to the history.
	 *
	 * @since 1.7.4
	 *
	 * @param int   $product_id Product ID.
	 * @param float $price      Price.
	 *
	 * @return int
	 */
	public function add_first_price( int $product_id, float $price ) {

		$history[ $this->get_time_with_offset() ] = $price;

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
		$meta = is_array( $meta ) ? $meta : [];

		return $this->fill_empty_history( $product_id, $meta );
	}

	/**
	 * If the history is empty, fill it with current price and save it.
	 *
	 * It saves current price with the current timestamp and timestamp for date 24 hours ago.
	 *
	 * @since 1.5
	 *
	 * @param int   $product_id
	 * @param array<int, float> $history
	 *
	 * @return array<int, float>
	 */
	public function fill_empty_history( int $product_id, array $history ) : array {

		if ( empty( $history ) ) {
			$product = wc_get_product( $product_id );

			if ( ! $product ) {
				return [];
			}

			$price        = (float) $product->get_price();
			$current_time = $this->get_time_with_offset();

			$history[ $current_time ]                  = $price;
			$history[ $current_time - DAY_IN_SECONDS ] = $price; // Set the same price for 1 day earlier.

			$this->save_history( $product_id, $history );
		}

		return $history;
	}

	/**
	 * Save history.
	 *
	 * @since 1.1
	 * @since 1.7.4 Clean history from empty values vefore save.
	 *
	 * @param int               $product_id
	 * @param array<int, float> $history
	 *
	 * @return int
	 */
	public function save_history( int $product_id, array $history ): int {

		// clean empty values, but not zero.
		$history = array_filter(
			$history,
			static function( $value ) {
				return $value !== '';
			}
		);

		return (int) update_post_meta( $product_id, self::cf_key, $history );
	}

	/**
	 * Reduce history to minimal price (but bigger than zero).
	 *
	 * @since 1.4
	 *
	 * @param array<float> $prices Prices.
	 *
	 * @return float Minimal price, bigger than zero.
	 */
	private function reduce_to_minimal( $prices ) : float {

		return (float) array_reduce(
			$prices,
			static function( $carry, $item ) {

				if ( (float) $item > 0 && $carry > 0 ) {
					return min( (float) $carry, (float) $item );
				}

				return (float) $item;
			}
		);
	}

	/**
	 * Get time with offset.
	 *
	 * @since 1.6.1
	 *
	 * @return int
	 */
	private function get_time_with_offset() : int {

		return time() + ( (int) get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );
	}
}
