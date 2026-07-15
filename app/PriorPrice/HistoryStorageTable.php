<?php

namespace PriorPrice;

/**
 * HistoryStorageTable class - new implementation using database tables.
 *
 * @since 3.0.0
 */
class HistoryStorageTable {

	/**
	 * Get minimal price for $product_id in last $days.
	 *
	 * @since 3.0.0
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
	 * @since 3.0.0
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

		// For "sale_start" (exclude promotional price) use strict < so we only consider history before sale started.
		$end_op     = ( $count_from === 'sale_start_inclusive' ) ? '<=' : '<';
		$product_id = $wc_product->get_id();

		$candidates = [];

		$window_min = $this->query_min_price_before_sale_start( $product_id, $cutoff_date, $sale_start_date, $end_op, '>=' );

		if ( $window_min !== null ) {
			$candidates[] = (float) $window_min;
		}

		$carry_forward = $this->query_price_at_last_entry_before_cutoff( $product_id, $cutoff_date, $sale_start_date, $end_op );

		if ( $carry_forward !== null ) {
			$candidates[] = (float) $carry_forward;
		}

		if ( empty( $candidates ) ) {
			return 0.0;
		}

		/**
		 * Filter candidate prices used to compute the lowest price in the sale-start window.
		 *
		 * @since 3.2.5
		 *
		 * @param array<float> $candidates Candidate prices.
		 * @param \WC_Product  $wc_product WC Product.
		 * @param int          $cutoff_timestamp Window start timestamp.
		 * @param int          $sale_start_timestamp Window end timestamp.
		 */
		$candidates = apply_filters(
			'wc_price_history_sale_start_window_candidates',
			$candidates,
			$wc_product,
			$sale_start_timestamp - ( $days * DAY_IN_SECONDS ),
			$sale_start_timestamp
		);

		$valid_candidates = array_filter(
			$candidates,
			static fn( $price ) => (float) $price > 0
		);

		if ( empty( $valid_candidates ) ) {
			return 0.0;
		}

		return (float) min( $valid_candidates );
	}

	/**
	 * Add price to the history.
	 *
	 * @since 3.0.0
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

		// No prior history (e.g. new product): only update_product fires, not new_product.
		// Save current + 24h + 48h back so "lowest in 30 days" has data from day one.
		if ( $previous_prices['price'] === null && $previous_prices['sale_price'] === null ) {
			return $this->add_first_price( $product_id, $regular_price );
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
	 * Saves the price for the current moment and for 24 and 48 hours earlier,
	 * so that the "lowest price in last 30 days" logic has data for the first days.
	 *
	 * @since 3.0.0
	 *
	 * @param int   $product_id    Product ID.
	 * @param float $regular_price Price.
	 *
	 * @return int Number of rows inserted (1 to 3).
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

		// Current row: previous_* same as current (first save = no prior change).
		// Use NULL for sale_price when not set (0 would mean "free product").
		$sale_ph    = $sale_price !== null ? '%s' : 'NULL';
		$insert_sql = "INSERT IGNORE INTO {$wpdb->prefix}wc_price_history
				(product_id, price, sale_price, previous_price, previous_sale_price, date, date_gmt, include_in_history)
				VALUES (%d, %s, {$sale_ph}, %s, {$sale_ph}, %s, %s, 1)";
		$insert_params   = [ $product_id, $regular_price ];
		if ( $sale_price !== null ) {
			$insert_params[] = $sale_price;
		}
		$insert_params[] = $regular_price;
		if ( $sale_price !== null ) {
			$insert_params[] = $sale_price;
		}
		$insert_params[] = $date;
		$insert_params[] = $date_gmt;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.DirectQuery
		$result = $wpdb->query( $wpdb->prepare( $insert_sql, ...$insert_params ) );

		$rows = $result ? 1 : 0;

		// Save same price/sale/previous for 24h and 48h earlier (regression fix for #188).
		$now = $this->get_time_with_offset();
		$r24 = $this->add_historical_price( $product_id, $regular_price, $now - DAY_IN_SECONDS, $sale_price, $regular_price, $sale_price );
		$r48 = $this->add_historical_price( $product_id, $regular_price, $now - ( 2 * DAY_IN_SECONDS ), $sale_price, $regular_price, $sale_price );
		$rows += $r24 + $r48;

		return $rows;
	}

	/**
	 * Add historical price at given timestamp.
	 *
	 * @since 3.0.0
	 *
	 * @param int         $product_id          Product ID.
	 * @param float       $price               Price.
	 * @param int         $timestamp           Unix timestamp (offset-adjusted, matching legacy post_meta format).
	 * @param float|null  $sale_price          Optional. Sale price; NULL when product has no sale price.
	 * @param float|null  $previous_price       Optional. Previous price for the row.
	 * @param float|null  $previous_sale_price Optional. Previous sale price; NULL when none.
	 *
	 * @return int
	 */
	public function add_historical_price( int $product_id, float $price, int $timestamp, ?float $sale_price = null, ?float $previous_price = null, ?float $previous_sale_price = null ): int {
		$timestamp_utc = $this->convert_to_utc_timestamp( $timestamp );
		$date_gmt = gmdate( 'Y-m-d H:i:s', $timestamp_utc );
		$date     = get_date_from_gmt( $date_gmt );

		global $wpdb;

		// Use NULL in SQL when no sale price (0 would mean "free product").
		$sale_ph       = $sale_price !== null ? '%s' : 'NULL';
		$prev_price_ph = $previous_price !== null ? '%s' : 'NULL';
		$prev_sale_ph  = $previous_sale_price !== null ? '%s' : 'NULL';
		$sql           = "INSERT IGNORE INTO {$wpdb->prefix}wc_price_history
				(product_id, price, sale_price, previous_price, previous_sale_price, date, date_gmt, include_in_history)
				VALUES (%d, %s, {$sale_ph}, {$prev_price_ph}, {$prev_sale_ph}, %s, %s, 1)";
		$params        = [ $product_id, $price ];
		if ( $sale_price !== null ) {
			$params[] = $sale_price;
		}
		if ( $previous_price !== null ) {
			$params[] = $previous_price;
		}
		if ( $previous_sale_price !== null ) {
			$params[] = $previous_sale_price;
		}
		$params[] = $date;
		$params[] = $date_gmt;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.DirectQuery
		$q = $wpdb->query( $wpdb->prepare( $sql, ...$params ) );

		return $q;
	}

	/**
	 * Get pricing history for $product_id.
	 *
	 * @since 3.0.0
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
	 * Get pricing history for $product_id with entry IDs.
	 *
	 * @since 3.0.0
	 *
	 * @param int $product_id Product ID.
	 *
	 * @return array<int, array{price: float, entry_id: int}> Array of timestamp => ['price' => float, 'entry_id' => int].
	 */
	public function get_history_with_entry_ids( int $product_id ): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.DirectQuery
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, date_gmt, price
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
			$history[ $timestamp ] = [
				'price'    => (float) $row->price,
				'entry_id' => (int) $row->id,
			];
		}

		return $history;
	}

	/**
	 * Delete price from history.
	 *
	 * @since 3.0.0
	 *
	 * @param int $product_id Product ID.
	 * @param int $entry_id   Entry ID.
	 *
	 * @return bool
	 */
	public function delete_price( int $product_id, int $entry_id ): bool {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.DirectQuery
		$result = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->prefix}wc_price_history
				WHERE product_id = %d
				AND id = %d",
				$product_id,
				$entry_id
			)
		);

		return $result > 0;
	}

	/**
	 * Clean history (truncate table).
	 *
	 * @since 3.0.0
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
	 * @since 3.0.0
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
	 * @since 3.0.0
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
	 * @since 3.0.0
	 *
	 * @return int
	 */
	private function get_time_with_offset(): int {
		return time() + $this->get_gmt_offset_seconds();
	}

	/**
	 * Get GMT offset in seconds.
	 *
	 * @since 3.0.0
	 *
	 * @return int GMT offset in seconds (rounded to nearest second).
	 */
	private function get_gmt_offset_seconds(): int {
		// gmt_offset can be a float (e.g., 5.5 for UTC+5:30, 5.75 for UTC+5:45).
		$gmt_offset_hours = (float) get_option( 'gmt_offset' );

		return (int) round( $gmt_offset_hours * HOUR_IN_SECONDS );
	}

	/**
	 * Query MIN(price) for a product within a date range before sale start.
	 *
	 * @since 3.2.4
	 *
	 * @param int    $product_id      Product ID.
	 * @param string $cutoff_date     Cutoff date (Y-m-d H:i:s UTC).
	 * @param string $sale_start_date Sale start date (Y-m-d H:i:s UTC).
	 * @param string $end_op          Comparison before sale start ('<' or '<=').
	 * @param string $cutoff_op       Comparison against cutoff ('>=' or '<').
	 *
	 * @return string|null MIN price as string, or null when no matching rows.
	 */
	private function query_min_price_before_sale_start(
		int $product_id,
		string $cutoff_date,
		string $sale_start_date,
		string $end_op,
		string $cutoff_op
	): ?string {

		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.DirectQuery
		$result = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT MIN(price)
				FROM {$wpdb->prefix}wc_price_history
				WHERE product_id = %d
				AND date_gmt {$cutoff_op} %s
				AND date_gmt {$end_op} %s
				AND include_in_history = 1
				AND price > 0",
				$product_id,
				$cutoff_date,
				$sale_start_date
			)
		);

		if ( $result === null ) {
			return null;
		}

		return (string) $result;
	}

	/**
	 * Query price from the latest history entry before the sale-start window.
	 *
	 * @since 3.2.5
	 *
	 * @param int    $product_id      Product ID.
	 * @param string $cutoff_date     Cutoff date (Y-m-d H:i:s UTC).
	 * @param string $sale_start_date Sale start date (Y-m-d H:i:s UTC).
	 * @param string $end_op          Comparison before sale start ('<' or '<=').
	 *
	 * @return string|null Price as string, or null when no matching rows.
	 */
	private function query_price_at_last_entry_before_cutoff(
		int $product_id,
		string $cutoff_date,
		string $sale_start_date,
		string $end_op
	): ?string {

		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
		$result = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT price
				FROM {$wpdb->prefix}wc_price_history
				WHERE product_id = %d
				AND date_gmt < %s
				AND date_gmt {$end_op} %s
				AND include_in_history = 1
				AND price > 0
				ORDER BY date_gmt DESC
				LIMIT 1",
				$product_id,
				$cutoff_date,
				$sale_start_date
			)
		);

		if ( $result === null ) {
			return null;
		}

		return (string) $result;
	}

	/**
	 * Convert offset-adjusted timestamp to UTC timestamp.
	 *
	 * Legacy post_meta format uses offset-adjusted timestamps (time() + offset),
	 * but date_gmt in database is stored as UTC, so we need to subtract the offset.
	 *
	 * @since 3.0.0
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
	 * @since 3.0.0
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
	 * @since 3.0.0
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
	 * Uses add_first_price which saves current price for now, 24h and 48h earlier.
	 *
	 * @since 3.0.0
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

		$price = apply_filters(
			'wc_price_history_price_raw_non_taxed',
			(float) $product->get_price(),
			$product
		);

		if ( $price <= 0 ) {
			// Don't create history for products with zero or negative prices
			// This prevents issues with reduce_to_minimal() returning 0
			return $history;
		}

		$this->add_first_price( $product_id, $price );

		return $this->get_history( $product_id );
	}

	/**
	 * Fix history by extending all histories backward.
	 *
	 * @since 3.0.0
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
	 * @since 3.0.0
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
