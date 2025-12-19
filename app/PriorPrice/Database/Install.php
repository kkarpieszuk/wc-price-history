<?php

namespace PriorPrice\Database;

/**
 * Install class for creating database tables.
 *
 * @since {VERSION}
 */
class Install {

	/**
	 * Database version.
	 *
	 * @since {VERSION}
	 *
	 * @var string
	 */
	public const DB_VERSION = '2.0.0';

	/**
	 * Get database schema SQL.
	 *
	 * @since {VERSION}
	 *
	 * @return string
	 */
	public static function get_schema(): string {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		$tables = "
CREATE TABLE {$wpdb->prefix}wc_price_history (
	id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
	product_id bigint(20) unsigned NOT NULL,
	price decimal(19,4) NOT NULL,
	sale_price decimal(19,4) DEFAULT NULL,
	previous_price decimal(19,4) DEFAULT NULL,
	previous_sale_price decimal(19,4) DEFAULT NULL,
	date datetime NOT NULL,
	date_gmt datetime NOT NULL,
	include_in_history tinyint(1) DEFAULT 1,
	PRIMARY KEY (id),
	KEY product_id (product_id),
	KEY date_gmt (date_gmt),
	KEY product_date (product_id, date_gmt),
	UNIQUE KEY product_date_unique (product_id, date_gmt)
) $charset_collate;

CREATE TABLE {$wpdb->prefix}wc_price_history_meta (
	meta_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
	price_history_id bigint(20) unsigned NOT NULL,
	meta_key varchar(255) NOT NULL,
	meta_value longtext,
	PRIMARY KEY (meta_id),
	KEY price_history_id (price_history_id),
	KEY meta_key (meta_key)
) $charset_collate;
";

		return $tables;
	}

	/**
	 * Create tables.
	 *
	 * @since {VERSION}
	 *
	 * @return void
	 */
	public static function create_tables(): void {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$schema = self::get_schema();
		dbDelta( $schema );
	}

	/**
	 * Install database tables and set version.
	 *
	 * This method creates tables and updates the database version option.
	 * Used during plugin activation to ensure complete initialization.
	 *
	 * @since {VERSION}
	 *
	 * @return void
	 */
	public static function install(): void {
		self::create_tables();
		self::update_db_version();
	}

	/**
	 * Get database version from options.
	 *
	 * @since {VERSION}
	 *
	 * @return string|null
	 */
	public static function get_db_version(): ?string {
		return get_option( 'wc_price_history_db_version' );
	}

	/**
	 * Update database version in options.
	 *
	 * @since {VERSION}
	 *
	 * @return void
	 */
	public static function update_db_version(): void {
		update_option( 'wc_price_history_db_version', self::DB_VERSION );
	}

	/**
	 * Check if tables exist.
	 *
	 * @since {VERSION}
	 *
	 * @return bool
	 */
	public static function tables_exist(): bool {
		global $wpdb;

		$table_name = $wpdb->prefix . 'wc_price_history';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.DirectQuery
		$result = $wpdb->get_var(
			$wpdb->prepare(
				'SHOW TABLES LIKE %s',
				$table_name
			)
		);

		return $result === $table_name;
	}
}
