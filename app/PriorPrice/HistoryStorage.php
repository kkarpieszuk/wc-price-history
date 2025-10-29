<?php

namespace PriorPrice;

use PriorPrice\Database\Install;

/**
 * HistoryStorage class - adapter between old (post_meta) and new (tables) implementations.
 *
 * @since 1.1
 */
class HistoryStorage {

	/**
	 * Custom field key.
	 *
	 * @since 1.1
	 * @since 2.0 Changed from private to public.
	 *
	 * @var string
	 */
	public const cf_key = '_wc_price_history';

	/**
	 * Table storage instance.
	 *
	 * @since {VERSION}
	 *
	 * @var HistoryStorageTable
	 */
	private $table_storage;

	/**
	 * Constructor.
	 *
	 * @since {VERSION}
	 */
	public function __construct() {
		$this->table_storage = new HistoryStorageTable();
	}

	/**
	 * Check if should use tables.
	 *
	 * @since {VERSION}
	 *
	 * @return bool
	 */
	private function should_use_tables(): bool {

		static $use_tables = null;
		if ( $use_tables !== null ) {
			return $use_tables;
		}

		// Check if migration is completed or not needed.
		$migration_status = get_option( 'wc_price_history_migration_status', 'not_needed' );

		// Check if tables exist using Install class method.
		$table_exists = Install::tables_exist();

		// If status is completed and tables exist, use tables.
		if ( $migration_status === 'completed' && $table_exists ) {
			$use_tables = true;
			return $use_tables;
		}

		// Otherwise use post_meta (backward compatibility).
		$use_tables = false;
		return $use_tables;
	}

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
	public function get_minimal( int $product_id, int $days = 30 ): float {
		if ( $this->should_use_tables() ) {
			return $this->table_storage->get_minimal( $product_id, $days );
		}

		// Legacy post_meta implementation.
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
	public function get_minimal_from_sale_start( \WC_Product $wc_product, int $days = 30, string $count_from = 'sale_start' ): float {
		if ( $this->should_use_tables() ) {
			return $this->table_storage->get_minimal_from_sale_start( $wc_product, $days, $count_from );
		}

		// Legacy post_meta implementation.
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
			$sale_start_timestamp = $sale_start->getOffsetTimestamp()  + DAY_IN_SECONDS;
		} else {
			$sale_start_timestamp = $sale_start->getOffsetTimestamp();
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
		if ( $this->should_use_tables() ) {
			return $this->table_storage->add_price( $product_id, $new_price, $on_change_only );
		}

		// Legacy post_meta implementation.
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
	 * Do not add price if it is zero.
	 *
	 * @since 1.7.4
	 *
	 * @param int   $product_id Product ID.
	 * @param float $price      Price.
	 *
	 * @return int
	 */
	public function add_first_price( int $product_id, float $price ) {
		if ( $this->should_use_tables() ) {
			return $this->table_storage->add_first_price( $product_id, $price );
		}

		// Legacy post_meta implementation.
		if ( $price <= 0 ) {
			return 0;
		}

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
		if ( $this->should_use_tables() ) {
			return $this->table_storage->add_historical_price( $product_id, $price, $timestamp );
		}

		// Legacy post_meta implementation.
		$history = $this->get_history( $product_id );

		$history[ $timestamp ] = $price;

		return $this->save_history( $product_id, $history );
	}

	/**
	 * Get pricing history for $product_id.
	 *
	 * @since 1.1
	 * @since 2.1.3 Added $fill_empty parameter.
	 *
	 * @param int  $product_id Product ID.
	 * @param bool $fill_empty Fill empty history.
	 *
	 * @return array<int, float>
	 */
	public function get_history( int $product_id, bool $fill_empty = true ): array {
		if ( $this->should_use_tables() ) {
			$history = $this->table_storage->get_history( $product_id );
			if ( $fill_empty && empty( $history ) ) {
				$history = $this->table_storage->fill_empty_history( $product_id, [] );
			}
			return $history;
		}

		// Legacy post_meta implementation.
		$meta = get_post_meta( $product_id, self::cf_key, true );
		$meta = is_array( $meta ) ? $meta : [];

		if ( ! $fill_empty ) {
			return $meta;
		}

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

		if ( ! empty( $history ) ) {
			return $history;
		}

		$product = wc_get_product( $product_id );

		if ( ! $product ) {
			return [];
		}

		$price = (float) $product->get_price();

		if ( $price <= 0 ) {
			// Don't create history for products with zero or negative prices
			// This prevents issues with reduce_to_minimal() returning 0
			return $history;
		}

		$current_time = $this->get_time_with_offset();

		$history[ $current_time ]                  = $price;
		$history[ $current_time - DAY_IN_SECONDS ] = $price; // Set the same price for 1 day earlier.

		$this->save_history( $product_id, $history );

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
	 * Delete price from history.
	 *
	 * @since 2.1
	 *
	 * @param int   $product_id Product ID.
	 * @param int   $timestamp  Timestamp.
	 *
	 * @return bool
	 */
	public function delete_price( int $product_id, int $timestamp ): bool {
		if ( $this->should_use_tables() ) {
			return $this->table_storage->delete_price( $product_id, $timestamp );
		}

		// Legacy post_meta implementation.
		$history = $this->get_history( $product_id );

		if ( ! isset( $history[ $timestamp ] ) ) {
			return false;
		}

		unset( $history[ $timestamp ] );

		return (bool) $this->save_history( $product_id, $history );
	}

	/**
	 * Clean history.
	 *
	 * @since 2.0
	 *
	 * @return void
	 */
	public function clean_history(): void {
		if ( $this->should_use_tables() ) {
			$this->table_storage->clean_history();
			return;
		}

		// Legacy post_meta implementation.
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->query(
			$wpdb->prepare(
				"DELETE
				FROM {$wpdb->postmeta}
				WHERE meta_key = %s",
				self::cf_key
			)
		);
	}

	public function fix_history() : void {

		if ( $this->should_use_tables() ) {
			return;
		}

		$this->extend_all_histories_before( 1 );
	}

	private function extend_all_histories_before( int $days = 1 ) : void {

		$products = get_posts([
			'post_type' => 'product',
			'meta_key' => self::cf_key, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'posts_per_page' => -1,
		]);

		// Foreach product, get the earliest history timestamp and price, prepend this history with timestamp for $days ago and the same price.
		foreach ( $products as $product ) {
			$history = $this->get_history( $product->ID );

			$earliest_timestamp = min( array_keys( $history ) );
			$earliest_price     = $history[ $earliest_timestamp ];

			$history[ $earliest_timestamp - ( $days * DAY_IN_SECONDS ) ] = $earliest_price;

			$this->save_history( $product->ID, $history );
		}
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

		if (empty($prices)) {
			return 0.0;
		}

		$valid_prices = array_filter($prices, function($price) {
			return (float) $price > 0;
		});

		if (empty($valid_prices)) {
			return 0.0;
		}

		return (float) min($valid_prices);
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
