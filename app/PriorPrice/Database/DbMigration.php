<?php

namespace PriorPrice\Database;

use PriorPrice\HistoryStorage;

/**
 * DbMigration class for migrating data from post_meta to tables.
 *
 * @since {VERSION}
 */
class DbMigration {

	/**
	 * Option names.
	 *
	 * @since {VERSION}
	 */
	public const OPTION_DB_VERSION = 'wc_price_history_db_version';
	public const OPTION_MIGRATION_STATUS = 'wc_price_history_migration_status';
	public const OPTION_MIGRATION_TOTAL = 'wc_price_history_migration_total';
	public const OPTION_MIGRATION_PROCESSED = 'wc_price_history_migration_processed';
	public const OPTION_MIGRATED_PRODUCTS = 'wc_price_history_migrated_products';
	public const OPTION_MIGRATION_LOCK = 'wc_price_history_migration_lock';

	/**
	 * Migration statuses.
	 *
	 * @since {VERSION}
	 */
	public const STATUS_NOT_NEEDED = 'not_needed';
	public const STATUS_PENDING = 'pending';
	public const STATUS_IN_PROGRESS = 'in_progress';
	public const STATUS_COMPLETED = 'completed';

	/**
	 * Batch size.
	 *
	 * @since {VERSION}
	 *
	 * @var int
	 */
	private const BATCH_SIZE = 20;

	/**
	 * Lock timeout in seconds.
	 *
	 * Maximum time a migration batch should take. If lock is older than this,
	 * it's considered stale and can be acquired by another request.
	 *
	 * @since {VERSION}
	 *
	 * @var int
	 */
	private const LOCK_TIMEOUT = 60;

	/**
	 * Check if migration is needed.
	 *
	 * @since {VERSION}
	 *
	 * @return bool
	 */
	public static function needs_migration(): bool {
		global $wpdb;

		$current_status = self::get_migration_status();

		// If already completed or in progress, no need to check again.
		if ( in_array( $current_status, [ self::STATUS_COMPLETED, self::STATUS_IN_PROGRESS ], true ) ) {
			return false;
		}

		$db_version = get_option( self::OPTION_DB_VERSION, '0.0.0' );

		// Check if there are products with _wc_price_history meta FIRST.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.DirectQuery
		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT post_id)
				FROM {$wpdb->postmeta}
				WHERE meta_key = %s
				AND meta_value IS NOT NULL
				AND meta_value != ''",
				HistoryStorage::cf_key
			)
		);

		// If DB version is already up to date AND there are no products with history in post_meta, no migration needed.
		if ( version_compare( $db_version, Install::DB_VERSION, '>=' ) && (int) $count === 0 ) {
			update_option( self::OPTION_MIGRATION_STATUS, self::STATUS_NOT_NEEDED );
			return false;
		}

		// If there are products with post_meta history, migration is needed.
		if ( $count > 0 ) {
			return true;
		}

		// No products to migrate, mark as not needed.
		update_option( self::OPTION_MIGRATION_STATUS, self::STATUS_NOT_NEEDED );

		return false;
	}

	/**
	 * Get total number of products that need migration.
	 *
	 * @since {VERSION}
	 *
	 * @return int
	 */
	public static function get_total_products_to_migrate(): int {
		global $wpdb;

		// Count all products with _wc_price_history meta (including empty ones)
		// that don't already have entries in the new table.
		// Only include products that actually exist in the posts table (including trash).
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.DirectQuery
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT pm.post_id)
				FROM {$wpdb->postmeta} pm
				INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
				LEFT JOIN {$wpdb->prefix}wc_price_history ph ON pm.post_id = ph.product_id
				WHERE pm.meta_key = %s
				AND ph.product_id IS NULL",
				HistoryStorage::cf_key
			)
		);
	}

	/**
	 * Get products to migrate.
	 *
	 * @since {VERSION}
	 *
	 * @param int $limit Limit.
	 *
	 * @return array<int>
	 */
	public static function get_products_to_migrate( int $limit = self::BATCH_SIZE ): array {
		global $wpdb;

		// Get all products with _wc_price_history meta (including empty ones).
		// Exclude products that already have entries in the new table.
		// Only include products that actually exist in the posts table (including trash).
		$query = "SELECT DISTINCT pm.post_id
			FROM {$wpdb->postmeta} pm
			INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
			LEFT JOIN {$wpdb->prefix}wc_price_history ph ON pm.post_id = ph.product_id
			WHERE pm.meta_key = %s
			AND ph.product_id IS NULL
			LIMIT %d";

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.NotPrepared
		return $wpdb->get_col( $wpdb->prepare( $query, HistoryStorage::cf_key, $limit ) );
	}

	/**
	 * Migrate batch of products.
	 *
	 * @since {VERSION}
	 *
	 * @return array{
	 *   processed: int,
	 *   total: int,
	 *   percentage: float,
	 *   completed: bool,
	 *   message: string
	 * }
	 */
	public static function migrate_batch(): array {
		$status = self::get_migration_status( true );

		// Handle completed migration separately.
		if ( $status === self::STATUS_COMPLETED ) {
			$total    = (int) get_option( self::OPTION_MIGRATION_TOTAL, 0 );
			$processed = (int) get_option( self::OPTION_MIGRATION_PROCESSED, 0 );

			return [
				'processed'  => $processed,
				'total'     => $total,
				'percentage' => 100,
				'completed' => true,
				'message'   => esc_html__( 'Migration completed successfully.', 'wc-price-history' ),
			];
		}

		// Handle not needed status.
		if ( $status === self::STATUS_NOT_NEEDED ) {
			return [
				'processed'  => 0,
				'total'     => 0,
				'percentage' => 0,
				'completed' => true,
				'message'   => esc_html__( 'Migration not needed.', 'wc-price-history' ),
			];
		}

		// Only proceed if status is IN_PROGRESS or PENDING.
		if ( $status !== self::STATUS_IN_PROGRESS && $status !== self::STATUS_PENDING ) {
			return [
				'processed'  => 0,
				'total'     => 0,
				'percentage' => 0,
				'completed' => true,
				'message'   => esc_html__( 'Migration not needed.', 'wc-price-history' ),
			];
		}

		// Acquire lock to prevent concurrent batch processing.
		if ( ! self::acquire_lock() ) {
			// Another request is already processing a batch.
			// Return current progress without processing.
			$total    = (int) get_option( self::OPTION_MIGRATION_TOTAL, 0 );
			$processed = (int) get_option( self::OPTION_MIGRATION_PROCESSED, 0 );
			$percentage = $total > 0 ? round( ( $processed / $total ) * 100, 2 ) : 0;

			self::log(
				sprintf(
					'Lock already acquired by another request. Skipping batch. Current progress: %d/%d (%.2f%%)',
					$processed,
					$total,
					$percentage
				)
			);

			return [
				'processed'  => $processed,
				'total'     => $total,
				'percentage' => $percentage,
				'completed' => false,
				'message'   => sprintf(
					/* translators: %1$d: processed products, %2$d: total products, %3$.2f: percentage */
					esc_html__( 'Migrated %1$d of %2$d products (%3$.2f%%)', 'wc-price-history' ),
					$processed,
					$total,
					$percentage
				),
			];
		}

		try {
			// Initialize migration if this is the first batch.
			if ( $status === self::STATUS_PENDING ) {
				$total_products = self::get_total_products_to_migrate();
				update_option( self::OPTION_MIGRATION_TOTAL, $total_products );
				update_option( self::OPTION_MIGRATION_PROCESSED, 0 );
				update_option( self::OPTION_MIGRATED_PRODUCTS, [] );
				update_option( self::OPTION_MIGRATION_STATUS, self::STATUS_IN_PROGRESS );

				// Log migration start.
				self::log(
					sprintf(
						'Migration started. Total products to migrate: %d',
						$total_products
					)
				);
			}

			$products = self::get_products_to_migrate( self::BATCH_SIZE );
			$total    = (int) get_option( self::OPTION_MIGRATION_TOTAL, 0 );
			$processed = (int) get_option( self::OPTION_MIGRATION_PROCESSED, 0 );

			if ( empty( $products ) ) {
				update_option( self::OPTION_MIGRATION_STATUS, self::STATUS_COMPLETED );
				Install::update_db_version();

				// Log migration completion.
				self::log(
					sprintf(
						'Migration completed successfully. Total products migrated: %d',
						$total
					)
				);

				return [
					'processed'  => $total,
					'total'     => $total,
					'percentage' => 100,
					'completed' => true,
					'message'   => esc_html__( 'Migration completed successfully.', 'wc-price-history' ),
				];
			}

			// Log batch start.
			self::log(
				sprintf(
					'Batch started. Processing %d products (batch size: %d). Current progress: %d/%d (%.2f%%)',
					count( $products ),
					self::BATCH_SIZE,
					$processed,
					$total,
					$total > 0 ? round( ( $processed / $total ) * 100, 2 ) : 0
				)
			);

			// Load migrated products list once per batch.
			$migrated_products = get_option( self::OPTION_MIGRATED_PRODUCTS, [] );
			$migrated_products = is_array( $migrated_products ) ? $migrated_products : [];

			$batch_success_count = 0;
			$batch_error_count   = 0;
			$batch_migrated_ids  = [];

			foreach ( $products as $product_id ) {
				if ( ! self::migrate_product( $product_id ) ) {
					$batch_error_count++;
					continue;
				}

				$migrated_products[] = $product_id;
				$batch_migrated_ids[] = $product_id;
				$processed++;
				$batch_success_count++;
			}

			// Save migrated products list once per batch.
			update_option( self::OPTION_MIGRATED_PRODUCTS, array_unique( $migrated_products ) );
			update_option( self::OPTION_MIGRATION_PROCESSED, $processed );

			// Log batch completion with product IDs.
			$migrated_ids_str = ! empty( $batch_migrated_ids ) ? implode( ', ', $batch_migrated_ids ) : 'none';
			self::log(
				sprintf(
					'Batch completed. Successfully migrated: %d, Errors: %d. Total progress: %d/%d (%.2f%%).',
					$batch_success_count,
					$batch_error_count,
					$processed,
					$total,
					$total > 0 ? round( ( $processed / $total ) * 100, 2 ) : 0
				)
			);
			self::log(
				sprintf(
					'Migrated product IDs: [%s]',
					$migrated_ids_str
				)
			);

			$percentage = $total > 0 ? round( ( $processed / $total ) * 100, 2 ) : 0;

			return [
				'processed'  => $processed,
				'total'     => $total,
				'percentage' => $percentage,
				'completed' => false,
				'message'   => sprintf(
					/* translators: %1$d: processed products, %2$d: total products, %3$.2f: percentage */
					esc_html__( 'Migrated %1$d of %2$d products (%3$.2f%%)', 'wc-price-history' ),
					$processed,
					$total,
					$percentage
				),
			];
		} finally {
			// Always release lock, even if an error occurs.
			self::release_lock();
		}
	}

	/**
	 * Migrate single product.
	 *
	 * @since {VERSION}
	 *
	 * @param int $product_id Product ID.
	 *
	 * @return bool True if product was migrated, false otherwise.
	 */
	private static function migrate_product( int $product_id ): bool {
		global $wpdb;

		// Get old history from post_meta.
		$history = get_post_meta( $product_id, HistoryStorage::cf_key, true );
		$history = is_array( $history ) ? $history : [];

		$has_error = false;

		// If no history in post_meta, add current product price to table.
		if ( empty( $history ) ) {
			$product = wc_get_product( $product_id );

			if ( ! $product ) {
				self::log(
					sprintf(
						'ERROR: Product %d not found. Skipping.',
						$product_id
					)
				);
				return false;
			}

			$current_price = (float) $product->get_price();
			$current_sale_price = $product->is_on_sale() ? (float) $product->get_sale_price() : null;

			// Use current time for the entry.
			$timestamp_utc = time();
			$date_gmt = gmdate( 'Y-m-d H:i:s', $timestamp_utc );
			$date     = get_date_from_gmt( $date_gmt );

			// Insert current price as first entry.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.DirectQuery
			$result = $wpdb->query(
				$wpdb->prepare(
					"INSERT IGNORE INTO {$wpdb->prefix}wc_price_history
					(product_id, price, sale_price, previous_price, previous_sale_price, date, date_gmt, include_in_history)
					VALUES (%d, %s, %s, NULL, NULL, %s, %s, 1)",
					$product_id,
					$current_price,
					$current_sale_price,
					$date,
					$date_gmt
				)
			);

			// Check for database errors.
			if ( $result === false || ! empty( $wpdb->last_error ) ) {
				$has_error = true;
				self::log(
					sprintf(
						'ERROR: Migration failed for product %d (no history, adding current price). Database error: %s',
						$product_id,
						$wpdb->last_error
					)
				);
			} else {
				self::log(
					sprintf(
						'Product %d has no price history in post_meta. Added current price: %s (sale: %s)',
						$product_id,
						$current_price,
						$current_sale_price ?? 'NULL'
					)
				);
			}

			return ! $has_error;
		}

		// Sort by timestamp.
		ksort( $history );

		$previous_price       = null;
		$previous_sale_price  = null;

		foreach ( $history as $timestamp => $price ) {
			// Convert offset-adjusted timestamp (from post_meta legacy format) to UTC timestamp.
			// Timestamps in post_meta are offset-adjusted (time() + offset), but date_gmt in database must be UTC.
			$timestamp_utc = self::convert_to_utc_timestamp( $timestamp );
			$date_gmt = gmdate( 'Y-m-d H:i:s', $timestamp_utc );
			$date     = get_date_from_gmt( $date_gmt );

			// Use the historical price from post_meta as the actual price.
			// The price stored in _wc_price_history represents the price at that timestamp.
			$historical_price = (float) $price;

			// Insert or ignore if duplicate (UNIQUE constraint).
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.DirectQuery
			$result = $wpdb->query(
				$wpdb->prepare(
					"INSERT IGNORE INTO {$wpdb->prefix}wc_price_history
					(product_id, price, sale_price, previous_price, previous_sale_price, date, date_gmt, include_in_history)
					VALUES (%d, %s, NULL, %s, %s, %s, %s, 1)",
					$product_id,
					$historical_price,
					$previous_price,
					$previous_sale_price,
					$date,
					$date_gmt
				)
			);

			// Check for database errors.
			if ( $result === false || ! empty( $wpdb->last_error ) ) {
				$has_error = true;
				// Log error but continue processing other records.
				self::log(
					sprintf(
						'ERROR: Migration failed for product %d, timestamp %d. Database error: %s',
						$product_id,
						$timestamp,
						$wpdb->last_error
					)
				);
			}
			// If $result === 0, it means INSERT IGNORE skipped a duplicate, which is OK.

			// Store historical price as previous for next iteration.
			$previous_price = $historical_price;
			// Sale price from post_meta history is not available, so leave it null.
			$previous_sale_price = null;
		}

		// Return false if there were database errors.
		// Return true if at least one record was inserted, or if all were duplicates (already migrated).
		return ! $has_error;
	}

	/**
	 * Get migration progress.
	 *
	 * @since {VERSION}
	 *
	 * @return array{
	 *   processed: int,
	 *   total: int,
	 *   percentage: 0|float,
	 *   status: string|false
	 * }
	 */
	public static function get_progress(): array {
		$processed = (int) get_option( self::OPTION_MIGRATION_PROCESSED, 0 );
		$total     = (int) get_option( self::OPTION_MIGRATION_TOTAL, 0 );
		$status    = self::get_migration_status( true );
		$percentage = $total > 0 ? round( ( $processed / $total ) * 100, 2 ) : 0;

		return [
			'processed'  => $processed,
			'total'     => $total,
			'percentage' => $percentage,
			'status'    => $status,
		];
	}

	/**
	 * Initialize migration.
	 *
	 * @since {VERSION}
	 *
	 * @return void
	 */
	public static function init_migration(): void {
		update_option( self::OPTION_MIGRATION_STATUS, self::STATUS_PENDING );
	}

	/**
	 * Get migration status.
	 *
	 * @since {VERSION}
	 *
	 * @param bool $use_default Use default value if option not set. Default false.
	 *
	 * @return string|false Status string, or false if not set and $use_default is false.
	 */
	public static function get_migration_status( bool $use_default = false ) {
		$status = get_option( self::OPTION_MIGRATION_STATUS );

		if ( $status === false && $use_default ) {
			return self::STATUS_NOT_NEEDED;
		}

		return $status;
	}

	/**
	 * Check if migration should be checked on this request.
	 *
	 * @since {VERSION}
	 *
	 * @return bool
	 */
	public static function should_check_migration(): bool {
		// Only check on admin pages.
		if ( ! is_admin() ) {
			return false;
		}

		// Skip AJAX requests entirely (notice is not displayed during AJAX).
		if ( wp_doing_ajax() ) {
			return false;
		}

		return true;
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
	private static function convert_to_utc_timestamp( int $offset_timestamp ): int {
		$gmt_offset = (int) get_option( 'gmt_offset' ) * HOUR_IN_SECONDS;
		return $offset_timestamp - $gmt_offset;
	}

	/**
	 * Acquire lock for batch migration to prevent concurrent execution.
	 *
	 * @since {VERSION}
	 *
	 * @return bool True if lock was acquired, false if another request is already processing.
	 */
	private static function acquire_lock(): bool {
		$lock_timestamp = get_option( self::OPTION_MIGRATION_LOCK, 0 );

		// If lock exists and is not stale, another request is processing.
		if ( $lock_timestamp > 0 && ( time() - $lock_timestamp ) < self::LOCK_TIMEOUT ) {
			return false;
		}

		// Acquire lock by setting current timestamp.
		update_option( self::OPTION_MIGRATION_LOCK, time() );
		return true;
	}

	/**
	 * Release lock for batch migration.
	 *
	 * @since {VERSION}
	 *
	 * @return void
	 */
	private static function release_lock(): void {
		delete_option( self::OPTION_MIGRATION_LOCK );
	}

	/**
	 * Log message to debug.log if WP_DEBUG_LOG is enabled.
	 *
	 * @since {VERSION}
	 *
	 * @param string $message Message to log.
	 * @return void
	 */
	private static function log( string $message ): void {
		if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
			error_log( '[wc pricehistory db migration] ' . $message );
		}
	}
}
