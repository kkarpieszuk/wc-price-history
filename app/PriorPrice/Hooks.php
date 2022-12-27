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

		\add_action( 'wp_enqueue_scripts', [ $this->factory->createAjax(), 'enqueue' ] );
		\add_action( 'wp_ajax_fetch_PriorPrice_ajax', [ $this->factory->createAjax(), 'fetchForAjax' ] );
		\add_action( 'wp_ajax_nopriv_fetch_PriorPrice_ajax', [ $this->factory->createAjax(), 'fetchForAjax' ] );


		\add_action( 'init', [ $this->factory->createShortcode(), 'registerShortcode' ], 11 );

		add_filter( 'woocommerce_register_post_type_product', [ $this->factory->createRevisions(), 'enable_product_revisions' ] );
		add_action( 'save_post', [ $this->factory->createRevisions(), 'save_price_revision' ] );


	}
}
