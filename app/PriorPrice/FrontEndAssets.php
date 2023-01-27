<?php

namespace PriorPrice;

/**
 * Front end assets class.
 *
 * @since 1.7
 */
class FrontEndAssets {

	/**
	 * Register hooks.
	 *
	 * @since 1.7
	 */
	public function register_hooks() : void {
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
	}

	/**
	 * Enqueue scripts.
	 *
	 * @since 1.7
	 */
	public function enqueue_scripts() : void {
		wp_enqueue_style( 'wc-price-history-frontend', WC_PRICE_HISTORY_PLUGIN_URL . 'assets/css/frontend.css', [], '1.7' );
	}
}
