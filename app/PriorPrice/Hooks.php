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
	public function registerHooks(): void {

		add_filter( 'woocommerce_register_post_type_product', [ new Revisions(), 'enable_product_revisions' ] );
		add_filter( 'wp_save_post_revision_post_has_changed', [ new Revisions(), 'post_has_changed' ], 10, 3 );
		add_action( 'save_post', [ new Revisions(), 'save_price_revision' ] );
		add_filter( 'woocommerce_get_price_html', [ new Prices(), 'get_price_html' ], 10, 2 );
	}
}
