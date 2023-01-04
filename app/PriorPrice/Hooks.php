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

		$migrations = new Migrations( $history_storage );
		$migrations->register_hooks();

		$prices = new Prices( $history_storage );
		$prices->register_hooks();

		$updates = new ProductUpdates( $history_storage );
		$updates->register_hooks();
	}
}
