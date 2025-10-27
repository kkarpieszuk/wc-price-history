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

		$current_status = get_option( self::OPTION_MIGRATION_STATUS );

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
		if ( version_compare( $db_version, Install::DB_VERSION, '>=' ) && $count === 0 ) {
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

		$placeholders = implode( ',', array_fill( 0, count( $migrated_products ), '%d' ) );
		$query        = "SELECT DISTINCT post_id
			FROM {$wpdb->postmeta}
			WHERE meta_key = %s
			AND meta_value IS NOT NULL
			AND meta_value != ''";

		if ( ! empty( $migrated_products ) ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
			$query .= $wpdb->prepare(
				" AND post_id NOT IN ($placeholders)",
				$migrated_products
			);
		}

		$query .= $wpdb->prepare( ' LIMIT %d', $limit );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.NotPrepared
		return $wpdb->get_col( $wpdb->prepare( $query, HistoryStorage::cf_key ) );
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
		$status = get_option( self::OPTION_MIGRATION_STATUS, self::STATUS_NOT_NEEDED );

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

		foreach ( $products as $product_id ) {
			self::migrate_product( $product_id );
			$processed++;
		}

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
	 * @return void
	 */
	private static function migrate_product( int $product_id ): void {
		global $wpdb;

		// Get old history from post_meta.
		$history = get_post_meta( $product_id, HistoryStorage::cf_key, true );
		$history = is_array( $history ) ? $history : [];

		if ( empty( $history ) ) {
			return;
		}

		// Sort by timestamp.
		ksort( $history );

		$previous_price       = null;
		$previous_sale_price  = null;
		$migrated_products = get_option( self::OPTION_MIGRATED_PRODUCTS, [] );
		$migrated_products = is_array( $migrated_products ) ? $migrated_products : [];

		foreach ( $history as $timestamp => $price ) {
			$date_gmt = gmdate( 'Y-m-d H:i:s', $timestamp );
			$date     = get_date_from_gmt( $date_gmt );

			// Get product to determine if it was on sale.
			$product = wc_get_product( $product_id );
			$is_on_sale = $product && $product->is_on_sale() ? 1 : 0;

			// Determine current prices from product.
			$current_price      = $product ? $product->get_regular_price() : null;
			$current_sale_price = $product ? $product->get_sale_price() : null;

			// Insert or ignore if duplicate (UNIQUE constraint).
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->query(
				$wpdb->prepare(
					"INSERT IGNORE INTO {$wpdb->prefix}wc_price_history
					(product_id, price, sale_price, previous_price, previous_sale_price, date, date_gmt, include_in_history)
					VALUES (%d, %s, %s, %s, %s, %s, %s, 1)",
					$product_id,
					$current_price ?? $price,
					$current_sale_price,
					$previous_price,
					$previous_sale_price,
					$date,
					$date_gmt
				)
			);

			// Store previous prices for next iteration.
			$previous_price      = $current_price ?? $price;
			$previous_sale_price = $current_sale_price;

			// Insert meta for was_on_sale.
			if ( ! empty( $is_on_sale ) ) {
				$history_id = $wpdb->insert_id;
				if ( $history_id ) {
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.DirectQuery
					$wpdb->query(
						$wpdb->prepare(
							"INSERT INTO {$wpdb->prefix}wc_price_history_meta (price_history_id, meta_key, meta_value)
							VALUES (%d, 'was_on_sale', %d)",
							$history_id,
							$is_on_sale
						)
					);
				}
			}
		}

		// Add product ID to migrated list.
		$migrated_products[] = $product_id;
		update_option( self::OPTION_MIGRATED_PRODUCTS, array_unique( $migrated_products ) );
	}

	/**
	 * Get migration progress.
	 *
	 * @since {VERSION}
	 *
	 * @return array{
	 *   processed: int,
	 *   total: int,
	 *   percentage: float,
	 *   status: string
	 * }
	 */
	public static function get_progress(): array {
		$processed = (int) get_option( self::OPTION_MIGRATION_PROCESSED, 0 );
		$total     = (int) get_option( self::OPTION_MIGRATION_TOTAL, 0 );
		$status    = get_option( self::OPTION_MIGRATION_STATUS, self::STATUS_NOT_NEEDED );
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
}
