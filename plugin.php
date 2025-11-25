<?php
/*
 * Plugin Name: WC Price History
 * Description: Track WooCommerce Products prior prices history and display the lowest price in the last 30 days (fully configurable). This plugin allows your WC shop to be compliant with European Commission Omnibus Directive 98/6/EC Article 6a which specifies price reduction announcement policy.
 * Author: Konrad Karpieszuk
 * Author URI: https://wcpricehistory.com
 * Version: {VERSION}
 * Text Domain: wc-price-history
 * Domain Path: /languages/
 * Requires at least: 5.8
 * Requires PHP: 7.2
 * Plugin URI: https://github.com/kkarpieszuk/wc-price-history
 * Requires Plugins: woocommerce
 * License: Expat
 */

use PriorPrice\Hooks;
use PriorPrice\Database\Install;

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/constants.php';

define( 'WC_PRICE_HISTORY_VERSION', '{VERSION}' );
define( 'WC_PRICE_HISTORY_DB_VERSION', '2.0.0' );

/**
 * Get the plugin version.
 *
 * @since 2.0.1
 *
 * @return string
 */
function get_wc_price_history_version(): string {
	return WC_PRICE_HISTORY_VERSION;
}

// Register activation hook.
register_activation_hook( __FILE__, [ Install::class, 'install' ] );

// Handle missing WooCommerce and ensure database tables exist.
add_action( 'plugins_loaded', function () {
	if ( ! function_exists( 'WC' ) ) {
		add_action( 'admin_notices', function () {
			?>
			<div class="notice notice-error">
				<p><?php esc_html_e( 'WooCommerce Price History plugin requires WooCommerce to be installed and active.', 'wc-price-history' ); ?></p>
			</div>
			<?php
		} );
		return;
	}

	// Ensure database tables exist (handles manual FTP updates without reactivation).
	// This is a safety check for cases where plugin files are updated via FTP
	// without deactivating/reactivating, which would skip the activation hook.
	if ( ! Install::tables_exist() || Install::get_db_version() !== Install::DB_VERSION ) {
		Install::create_tables();
		Install::update_db_version();
	}
} );

$hooks = new Hooks();
$hooks->register_hooks();
