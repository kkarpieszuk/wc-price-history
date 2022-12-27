<?php

namespace PriorPrice;

/**
 * Hooks class.
 * 
 * @since 1.0
 */
class Hooks {

	/**
	 * @since 1.0
	 * 
	 * @var Factory
	 */
	private $factory;

	/**
	 * Class constructor.
	 * 
	 * @since 1.0
	 * 
	 * @param Factory $factory
	 */
	public function __construct( Factory $factory ) {
		$this->factory = $factory;
	}

	/**
	 * Register hooks.
	 * 
	 * @since 1.0
	 */
	public function registerHooks() {

		\add_action( 'init', [ $this->factory->createShortcode(), 'registerShortcode' ], 11 );

		add_filter( 'woocommerce_register_post_type_product', [ $this->factory->createRevisions(), 'enable_product_revisions' ] );
		add_action( 'save_post', [ $this->factory->createRevisions(), 'save_price_revision' ] );

		add_filter( 'woocommerce_get_price_html', [ new Prices(), 'price_html' ], 10, 2 );

	}
}
