<?php

namespace PriorPrice;

class AdminAssets {

	/**
	 * Register hooks.
	 *
	 * @since 1.2
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
	}

	/**
	 * Enqueue scripts.
	 *
	 * @since 1.2
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		if ( ! $this->is_settings_page() ) {
			return;
		}
		wp_enqueue_style( 'wc-price-history-admin', WC_PRICE_HISTORY_PLUGIN_URL . 'assets/css/admin.css', [], '1.2' );
		wp_enqueue_script( 'wc-price-history-admin', WC_PRICE_HISTORY_PLUGIN_URL . 'assets/js/admin.js', [ 'jquery' ], '1.2', true );
	}

	private function is_settings_page() {
		return isset( $_GET['page'] ) && $_GET['page'] === 'wc-price-history';
	}

}