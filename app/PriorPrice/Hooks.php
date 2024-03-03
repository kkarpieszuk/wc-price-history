<?php

namespace PriorPrice;

use PriorPrice\FirstRun\{AdminNotice, ProductScan, SettingsPage};

/**
 * Hooks class.
 *
 * @since 1.0
 */
class Hooks {

	/**
	 * Register hooks.
	 *
	 * @since 1.0
	 */
	public function register_hooks(): void {

		add_action( 'plugins_loaded', [ $this, 'plugins_loaded' ] );
	}

	/**
	 * Plugins loaded action.
	 *
	 * @since 1.6.4
	 */
	public function plugins_loaded() : void {

		$history_storage = new HistoryStorage();

		$settings_data = new SettingsData();
		$settings_data->register_hooks();

		$settings = new SettingsPage();
		$settings->register_hooks();

		$migrations = new Migrations( $history_storage );
		$migrations->register_hooks();

		$prices = new Prices( $history_storage, $settings_data, new Taxes() );
		$prices->register_hooks();

		$updates = new ProductUpdates( $history_storage );
		$updates->register_hooks();

		$duplicate = new ProductDuplicate();
		$duplicate->register_hooks();

		$admin_assets = new AdminAssets();
		$admin_assets->register_hooks();

		$frontend_assets = new FrontEndAssets();
		$frontend_assets->register_hooks();

		$shortcode = new Shortcode( $history_storage, new Taxes(), $settings_data );
		$shortcode->register_hooks();

		$marketing = new Marketing();
		$marketing->register_hooks();

		$this->register_first_run_hooks( $settings_data );
	}

	/**
	 * Register first run hooks.
	 *
	 * @since 2.0
	 *
	 * @param SettingsData $settings_data Settings data.
	 */
	private function register_first_run_hooks( SettingsData $settings_data ) : void {

		$firstRunAdminNotice = new FirstRun\AdminNotice( $settings_data );
		$firstRunAdminNotice->register_hooks();

		$firstRunSettingsPage = new FirstRun\SettingsPage();
		$firstRunSettingsPage->register_hooks();

		$firstRunProductScan = new FirstRun\ProductScan();
		$firstRunProductScan->register_hooks();
	}
}
