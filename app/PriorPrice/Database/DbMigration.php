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

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.DirectQuery
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT post_id)
				FROM {$wpdb->postmeta}
				WHERE meta_key = %s
				AND meta_value IS NOT NULL
				AND meta_value != ''",
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

		$migrated_products = get_option( self::OPTION_MIGRATED_PRODUCTS, [] );
		$migrated_products = is_array( $migrated_products ) ? $migrated_products : [];

		$query = "SELECT DISTINCT post_id
			FROM {$wpdb->postmeta}
			WHERE meta_key = %s
			AND meta_value IS NOT NULL
			AND meta_value != ''";

		if ( ! empty( $migrated_products ) ) {
			$placeholders = implode( ',', array_fill( 0, count( $migrated_products ), '%d' ) );
			// Build array of arguments for prepare: meta_key + migrated products array.
			$prepare_args = array_merge( [ HistoryStorage::cf_key ], $migrated_products );
			$query        = $wpdb->prepare(
				"{$query} AND post_id NOT IN ($placeholders)",
				$prepare_args
			);
		} else {
			$query = $wpdb->prepare( $query, HistoryStorage::cf_key );
		}

		$query = $wpdb->prepare( "{$query} LIMIT %d", $limit );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.NotPrepared
		return $wpdb->get_col( $query );
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

		if ( $status !== self::STATUS_IN_PROGRESS && $status !== self::STATUS_PENDING ) {
			return [
				'processed'  => 0,
				'total'     => 0,
				'percentage' => 0,
				'completed' => true,
				'message'   => esc_html__( 'Migration not needed.', 'wc-price-history' ),
			];
		}

		// Initialize migration if this is the first batch.
		if ( $status === self::STATUS_PENDING ) {
			$total_products = self::get_total_products_to_migrate();
			update_option( self::OPTION_MIGRATION_TOTAL, $total_products );
			update_option( self::OPTION_MIGRATION_PROCESSED, 0 );
			update_option( self::OPTION_MIGRATED_PRODUCTS, [] );
			update_option( self::OPTION_MIGRATION_STATUS, self::STATUS_IN_PROGRESS );
		}

		$products = self::get_products_to_migrate( self::BATCH_SIZE );
		$total    = (int) get_option( self::OPTION_MIGRATION_TOTAL, 0 );
		$processed = (int) get_option( self::OPTION_MIGRATION_PROCESSED, 0 );

		if ( empty( $products ) ) {
			update_option( self::OPTION_MIGRATION_STATUS, self::STATUS_COMPLETED );
			Install::update_db_version();

			return [
				'processed'  => $total,
				'total'     => $total,
				'percentage' => 100,
				'completed' => true,
				'message'   => esc_html__( 'Migration completed successfully.', 'wc-price-history' ),
			];
		}

		// Load migrated products list once per batch.
		$migrated_products = get_option( self::OPTION_MIGRATED_PRODUCTS, [] );
		$migrated_products = is_array( $migrated_products ) ? $migrated_products : [];

		foreach ( $products as $product_id ) {
			if ( ! self::migrate_product( $product_id ) ) {
				continue;
			}

			$migrated_products[] = $product_id;
			$processed++;
		}

		// Save migrated products list once per batch.
		update_option( self::OPTION_MIGRATED_PRODUCTS, array_unique( $migrated_products ) );
		update_option( self::OPTION_MIGRATION_PROCESSED, $processed );

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

		if ( empty( $history ) ) {
			return false;
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
			$wpdb->query(
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

			// Store historical price as previous for next iteration.
			$previous_price = $historical_price;
			// Sale price from post_meta history is not available, so leave it null.
			$previous_sale_price = null;
		}

		return true;
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
}
