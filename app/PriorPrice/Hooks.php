<?php

namespace PriorPrice;

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

		$admin_assets = new AdminAssets();
		$admin_assets->register_hooks();
		
		$shortcode = new Shortcode( $history_storage, new Taxes() );
		$shortcode->register_hooks();

		$marketing = new Marketing();
		$marketing->register_hooks();
	}
}
