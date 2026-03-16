<?php

namespace PriorPrice;

use PriorPrice\C11y\WPSheetEditor;

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

		$wpse = new WPSheetEditor();
		WPSheetEditor::register_response_filter_flag();
		$history_storage  = new HistoryStorage( $wpse );

		$settings_data = new SettingsData();
		$settings_data->register_hooks();

		$settings = new SettingsPage();
		$settings->register_hooks();

		$migrations = new Migrations( $history_storage );
		$migrations->register_hooks();

		$prices = new Prices( $history_storage, $settings_data, new Taxes() );
		$prices->register_hooks();

		$variations = new Variations( $prices );
		$variations->register_hooks();

		$updates = new ProductUpdates( $history_storage, $wpse );
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

		$first_scan = new FirstScan( $settings_data, $history_storage );
		$first_scan->register_hooks();

		$ajax = new Ajax( $first_scan );
		$ajax->register_hooks();

		$export = new Export( $history_storage, $settings_data );
		$export->register_hooks();

		$educational_tab = new EducationalTab();
		$educational_tab->register_hooks();

		// Register database migration classes.
		$db_migration_notice = new \PriorPrice\Database\AdminNotice();
		$db_migration_notice->register_hooks();
	}
}
