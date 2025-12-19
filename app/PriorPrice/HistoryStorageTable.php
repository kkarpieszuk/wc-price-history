<?php

namespace PriorPrice;

/**
 * HistoryStorageTable class - new implementation using database tables.
 *
 * @since {VERSION}
 */
class HistoryStorageTable {

	/**
	 * Get minimal price for $product_id in last $days.
	 *
	 * @since {VERSION}
	 *
	 * @param int $product_id Product ID.
	 * @param int $days       Days span.
	 *
	 * @return float
	 */
	public function get_minimal( int $product_id, int $days = 30 ): float {
		global $wpdb;

		// Use time() (UTC timestamp) instead of get_time_with_offset() because:
		// - date_gmt in database is stored as UTC datetime
		// - gmdate() interprets timestamp as UTC
		// - get_time_with_offset() returns offset-adjusted timestamp, which would cause incorrect cutoff date
		$cutoff_date = gmdate( 'Y-m-d H:i:s', time() - ( $days * DAY_IN_SECONDS ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.DirectQuery
		$result = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT MIN(price)
				FROM {$wpdb->prefix}wc_price_history
				WHERE product_id = %d
				AND date_gmt >= %s
				AND include_in_history = 1
				AND price > 0",
				$product_id,
				$cutoff_date
			)
		);

		return (float) ( $result ?? 0.0 );
	}

	/**
	 * Get minimal price for $product_id in last $days from sale start.
	 *
	 * @since {VERSION}
	 *
	 * @param \WC_Product $wc_product WC Product.
	 * @param int         $days       Days span.
	 * @param string      $count_from Count from option.
	 *
	 * @return float
	 */
	public function get_minimal_from_sale_start( \WC_Product $wc_product, int $days = 30, string $count_from = 'sale_start' ): float {

		$sale_start = $wc_product->get_date_on_sale_from();

		if ( ! $sale_start ) {
			$logger = wc_get_logger();
			$link   = get_edit_post_link( $wc_product->get_id() );

			$logger->error(
				/* translators: %d product id, %s link to product edit screen. */
				sprintf( esc_html__( 'Product #%1$d is on sale but has no sale start date. Please edit this product and set starting date for sale: %2$s', 'wc-price-history' ), $wc_product->get_id(), $link ),
				[
					'source' => 'wc-price-history',
				]
			);

			return $this->get_minimal( $wc_product->get_id(), $days );
		}

		if ( $count_from === 'sale_start_inclusive' ) {
			$sale_start_timestamp = $sale_start->getOffsetTimestamp() + DAY_IN_SECONDS;
		} else {
			$sale_start_timestamp = $sale_start->getOffsetTimestamp();
		}

		// Convert offset-adjusted timestamps to UTC before formatting.
		// getOffsetTimestamp() returns offset-adjusted timestamp, but date_gmt in database is stored as UTC.
		$sale_start_timestamp_utc = $this->convert_to_utc_timestamp( $sale_start_timestamp );
		$cutoff_timestamp_utc = $this->convert_to_utc_timestamp( $sale_start_timestamp - ( $days * DAY_IN_SECONDS ) );

		$sale_start_date = gmdate( 'Y-m-d H:i:s', $sale_start_timestamp_utc );
		$cutoff_date     = gmdate( 'Y-m-d H:i:s', $cutoff_timestamp_utc );

		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.DirectQuery
		$result = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT MIN(price)
				FROM {$wpdb->prefix}wc_price_history
				WHERE product_id = %d
				AND date_gmt >= %s
				AND date_gmt <= %s
				AND include_in_history = 1
				AND price > 0",
				$wc_product->get_id(),
				$cutoff_date,
				$sale_start_date
			)
		);

		return (float) ( $result ?? 0.0 );
	}

	/**
	 * Add price to the history.
	 *
	 * @since {VERSION}
	 *
	 * @param int   $product_id     Product ID.
	 * @param float $regular_price  Price.
	 * @param bool  $on_change_only Save only if price changed.
	 *
	 * @return int Number of rows affected.
	 */
	public function add_price( int $product_id, float $regular_price, bool $on_change_only ): int {
		global $wpdb;

		$product = wc_get_product( $product_id );

		if ( ! $product ) {
			return 0;
		}

		$sale_price = $this->get_sale_price( $product );

		// Get previous prices.
		$previous_prices = $this->get_previous_prices( $product_id );
		$previous_price  = $previous_prices['price'] ?? $regular_price;
		$previous_sale_price = $previous_prices['sale_price'] ?? null;

		// If on_change_only, check if prices actually changed.
		// Check both regular_price and sale_price changes.
		if ( $on_change_only
			&& $regular_price === $previous_price
			&& $sale_price === $previous_sale_price
		) {
			return 0;
		}

		$date_gmt = current_time( 'mysql', true );
		$date     = current_time( 'mysql' );

		// Insert with IGNORE to prevent duplicates.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.DirectQuery
		$result = $wpdb->query(
			$wpdb->prepare(
				"INSERT IGNORE INTO {$wpdb->prefix}wc_price_history
				(product_id, price, sale_price, previous_price, previous_sale_price, date, date_gmt, include_in_history)
				VALUES (%d, %s, %s, %s, %s, %s, %s, 1)",
				$product_id,
				$regular_price,
				$sale_price,
				$previous_price,
				$previous_sale_price,
				$date,
				$date_gmt
			)
		);

		if ( $result ) {
			$history_id = $wpdb->insert_id;

			// Add meta if on sale.
			if ( $product->is_on_sale() ) {
				$this->insert_meta( $history_id, 'was_on_sale', '1' );
			}
		}

		return $result ? 1 : 0;
	}

	/**
	 * Add first price to the history.
	 *
	 * @since {VERSION}
	 *
	 * @param int   $product_id    Product ID.
	 * @param float $regular_price Price.
	 *
	 * @return int
	 */
	public function add_first_price( int $product_id, float $regular_price ): int {
		if ( $regular_price <= 0 ) {
			return 0;
		}

		$product = wc_get_product( $product_id );

		if ( ! $product ) {
			return 0;
		}

		$sale_price = $this->get_sale_price( $product );

		$date_gmt = current_time( 'mysql', true );
		$date     = current_time( 'mysql' );

		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.DirectQuery
		$result = $wpdb->query(
			$wpdb->prepare(
				"INSERT IGNORE INTO {$wpdb->prefix}wc_price_history
				(product_id, price, sale_price, previous_price, previous_sale_price, date, date_gmt, include_in_history)
				VALUES (%d, %s, %s, NULL, NULL, %s, %s, 1)",
				$product_id,
				$regular_price,
				$sale_price,
				$date,
				$date_gmt
			)
		);

		return $result ? 1 : 0;
	}

	/**
	 * Add historical price at given timestamp.
	 *
	 * @since {VERSION}
	 *
	 * @param int   $product_id Product ID.
	 * @param float $price      Price.
	 * @param int   $timestamp Unix timestamp (offset-adjusted, matching legacy post_meta format).
	 *
	 * @return int
	 */
	public function add_historical_price( int $product_id, float $price, int $timestamp ): int {
		$timestamp_utc = $this->convert_to_utc_timestamp( $timestamp );
		$date_gmt = gmdate( 'Y-m-d H:i:s', $timestamp_utc );
		$date     = get_date_from_gmt( $date_gmt );

		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.DirectQuery
		return $wpdb->query(
			$wpdb->prepare(
				"INSERT IGNORE INTO {$wpdb->prefix}wc_price_history
				(product_id, price, sale_price, previous_price, previous_sale_price, date, date_gmt, include_in_history)
				VALUES (%d, %s, NULL, NULL, NULL, %s, %s, 1)",
				$product_id,
				$price,
				$date,
				$date_gmt
			)
		);
	}

	/**
	 * Get pricing history for $product_id.
	 *
	 * @since {VERSION}
	 *
	 * @param int $product_id Product ID.
	 *
	 * @return array<int, float> Array of timestamp => price.
	 */
	public function get_history( int $product_id ): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.DirectQuery
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT date_gmt, price
				FROM {$wpdb->prefix}wc_price_history
				WHERE product_id = %d
				AND include_in_history = 1
				ORDER BY date_gmt ASC",
				$product_id
			)
		);

		$history = [];

		foreach ( $results as $row ) {
			$timestamp = $this->convert_from_utc_datetime( $row->date_gmt );
			$history[ $timestamp ] = (float) $row->price;
		}

		return $history;
	}

	/**
	 * Delete price from history.
	 *
	 * @since {VERSION}
	 *
	 * @param int $product_id Product ID.
	 * @param int $timestamp  Timestamp.
	 *
	 * @return bool
	 */
	public function delete_price( int $product_id, int $timestamp ): bool {
		global $wpdb;

		$timestamp_utc = $this->convert_to_utc_timestamp( $timestamp );
		$date_gmt = gmdate( 'Y-m-d H:i:s', $timestamp_utc );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.DirectQuery
		$result = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->prefix}wc_price_history
				WHERE product_id = %d
				AND date_gmt = %s",
				$product_id,
				$date_gmt
			)
		);

		return $result > 0;
	}

	/**
	 * Clean history (truncate table).
	 *
	 * @since {VERSION}
	 *
	 * @return void
	 */
	public function clean_history(): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}wc_price_history" );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}wc_price_history_meta" );
	}

	/**
	 * Get previous prices for product.
	 *
	 * @since {VERSION}
	 *
	 * @param int $product_id Product ID.
	 *
	 * @return array{
	 *   price: float|null,
	 *   sale_price: float|null
	 * }
	 */
	private function get_previous_prices( int $product_id ): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.DirectQuery
		$result = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT price, sale_price
				FROM {$wpdb->prefix}wc_price_history
				WHERE product_id = %d
				ORDER BY date_gmt DESC
				LIMIT 1",
				$product_id
			)
		);

		if ( ! $result ) {
			return [
				'price'      => null,
				'sale_price' => null,
			];
		}

		return [
			'price'      => (float) $result->price,
			'sale_price' => $result->sale_price ? (float) $result->sale_price : null,
		];
	}

	/**
	 * Insert meta.
	 *
	 * @since {VERSION}
	 *
	 * @param int    $history_id History ID.
	 * @param string $meta_key   Meta key.
	 * @param string $meta_value Meta value.
	 *
	 * @return void
	 */
	private function insert_meta( int $history_id, string $meta_key, string $meta_value ): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->query(
			$wpdb->prepare(
				"INSERT INTO {$wpdb->prefix}wc_price_history_meta (price_history_id, meta_key, meta_value)
				VALUES (%d, %s, %s)",
				$history_id,
				$meta_key,
				$meta_value
			)
		);
	}

	/**
	 * Get time with offset.
	 *
	 * @since {VERSION}
	 *
	 * @return int
	 */
	private function get_time_with_offset(): int {
		return time() + $this->get_gmt_offset_seconds();
	}

	/**
	 * Get GMT offset in seconds.
	 *
	 * @since {VERSION}
	 *
	 * @return int GMT offset in seconds.
	 */
	private function get_gmt_offset_seconds(): int {
		return (int) get_option( 'gmt_offset' ) * HOUR_IN_SECONDS;
	}

	/**
	 * Convert offset-adjusted timestamp to UTC timestamp.
	 *
	 * Legacy post_meta format uses offset-adjusted timestamps (time() + offset),
	 * but date_gmt in database is stored as UTC, so we need to subtract the offset.
	 *
	 * @since {VERSION}
	 *
	 * @param int $offset_timestamp Offset-adjusted timestamp (matching legacy format).
	 *
	 * @return int UTC timestamp.
	 */
	private function convert_to_utc_timestamp( int $offset_timestamp ): int {
		return $offset_timestamp - $this->get_gmt_offset_seconds();
	}

	/**
	 * Convert UTC datetime string to offset-adjusted timestamp.
	 *
	 * Converts UTC datetime from database to offset-adjusted timestamp
	 * matching legacy post_meta format (for get_history() return value).
	 *
	 * @since {VERSION}
	 *
	 * @param string $date_gmt UTC datetime string (Y-m-d H:i:s format).
	 *
	 * @return int Offset-adjusted timestamp (matching legacy format).
	 */
	private function convert_from_utc_datetime( string $date_gmt ): int {
		// strtotime() with ' UTC' suffix forces UTC interpretation instead of server timezone.
		$timestamp_utc = strtotime( $date_gmt . ' UTC' );
		// Add offset to match legacy post_meta format (offset-adjusted timestamps).
		return $timestamp_utc + $this->get_gmt_offset_seconds();
	}

	/**
	 * Helper method - not used in new implementation.
	 *
	 * @since {VERSION}
	 *
	 * @param int               $product_id Product ID.
	 * @param array<int, float> $history    History array.
	 *
	 * @return int
	 */
	public function save_history( int $product_id, array $history ): int {
		// Deprecated - kept for backward compatibility.
		return 0;
	}

	/**
	 * Fill empty history with current price.
	 *
	 * It saves current price with the current timestamp and timestamp for date 24 hours ago.
	 * This matches the legacy post_meta implementation behavior.
	 *
	 * @since {VERSION}
	 *
	 * @param int          $product_id Product ID.
	 * @param array<mixed> $history    Empty history array.
	 *
	 * @return array<int, float>
	 */
	public function fill_empty_history( int $product_id, array $history ): array {
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

		// Add current price with current timestamp.
		$this->add_first_price( $product_id, $price );

		// Add same price for 24 hours earlier (matching legacy behavior).
		$current_time = $this->get_time_with_offset();
		$previous_timestamp = $current_time - DAY_IN_SECONDS;
		$this->add_historical_price( $product_id, $price, $previous_timestamp );

		return $this->get_history( $product_id );
	}

	/**
	 * Fix history by extending all histories backward.
	 *
	 * @since {VERSION}
	 *
	 * @return void
	 */
	public function fix_history(): void {
		// Not applicable for table-based implementation.
		// This is a legacy method.
	}

	/**
	 * Get sale price for product.
	 *
	 * @since {VERSION}
	 *
	 * @param \WC_Product $product Product.
	 *
	 * @return float|null
	 */
	private function get_sale_price( \WC_Product $product ) {
		$sale_price = $product->get_sale_price();

		return $sale_price ? (float) $sale_price : null;
	}
}
