<?php

namespace PriorPrice;

use  \WP_Mock\Tools\TestCase;

class HistoryStorageTest extends TestCase {

	public function setUp() : void {
		\WP_Mock::setUp();

		// Mock $wpdb as an anonymous class with actual methods.
		global $wpdb;
		$wpdb = new class() {
			public $prefix = 'wp_';

			public function prepare( $query, ...$args ) {
				return sprintf( $query, ...$args );
			}

			public function get_var( $query ) {
				return null; // Tables don't exist.
			}
		};
	}

	public function tearDown() : void {
		\WP_Mock::tearDown();
	}

	private function get_subject() {
		return new HistoryStorage();
	}

	/**
	 * @dataProvider data_provider_get_minimal
	 */
	public function test_get_minimal( $history, $expected_minimal ) {

		$product_id = 1;

		$subject = $this->get_subject();

		// Mock migration status to use post_meta (legacy mode).
		\WP_Mock::userFunction( 'get_option', [
			'args' => [ 'wc_price_history_migration_status', \WP_Mock\Functions::type( 'string' ) ],
			'return' => 'not_needed'
		] );

		\WP_Mock::userFunction( 'get_post_meta', [
			'times' => 1,
			'args' => [ $product_id, '_wc_price_history', true ],
			'return' => $history
		] );

		\WP_Mock::userFunction( 'get_option', [
			'args' => [ 'gmt_offset' ],
			'return' => 0
		] );

		\WP_Mock::userFunction( 'get_option', [
			'args' => [ 'wc_price_history_migration_status' ],
			'return' => 'not_needed'
		] );


		$minimal = $subject->get_minimal( $product_id, 30 );

		$this->assertEquals( $expected_minimal, $minimal );
	}

	/**
	 * @dataProvider data_provider_get_minimal_when_not_set
	 */
	public function test_get_minimal_when_minimal_not_set( $history, $expected_minimal ) {

		$product_id = 1;

		$subject = $this->get_subject();

		// Mock migration status to use post_meta (legacy mode).
		\WP_Mock::userFunction( 'get_option', [
			'args' => [ 'wc_price_history_migration_status', \WP_Mock\Functions::type( 'string' ) ],
			'return' => 'not_needed'
		] );

		\WP_Mock::userFunction( 'get_option', [
			'args' => [ 'gmt_offset' ],
			'return' => 0
		] );

		$product = $this->getMockBuilder( 'WC_Product' )
			->disableOriginalConstructor()
			->setMethods( [ 'get_price' ] )
			->getMock();
		$product->method( 'get_price' )
			->willReturn( $expected_minimal );

		\WP_Mock::userFunction( 'get_post_meta', [
			'times' => 1,
			'args' => [ $product_id, '_wc_price_history', true ],
			'return' => $history
		] );

		\WP_Mock::userFunction( 'wc_get_product', [
			'times' => 1,
			'args' => [ $product_id ],
			'return' => $product
		] );

		\WP_Mock::userFunction( 'update_post_meta', [
			'times' => 1,
			'args'  => [ $product_id, '_wc_price_history', \WP_Mock\Functions::type( 'array' ) ],
			'return' => 1,
		] );

		$minimal = $subject->get_minimal( $product_id, 30 );

		$this->assertEquals( $expected_minimal, $minimal );
	}

	public function test_save_history() {

		$product_id = 2;
		$history = [ time() => '200' ];

		\WP_Mock::userFunction( 'update_post_meta', [
			'times' => 1,
			'args'  => [ $product_id, '_wc_price_history', $history ],
			'return' => 1,
		] );

		$subject = $this->get_subject();

		$result = $subject->save_history(  $product_id, $history );

		$this->assertEquals( 1, $result );
	}

	/**
	 * @dataProvider data_provider_get_minimal_from_sale_start_fallback
	 */
	public function test_get_minimal_from_sale_start_falls_back_before_window( $history, $expected_minimal ) {

		$product_id = 1;
		$sale_start_timestamp = strtotime( '2026-06-18 00:00:00' );

		$subject = $this->mock_legacy_storage();

		\WP_Mock::userFunction( 'get_post_meta', [
			'times' => 1,
			'args' => [ $product_id, '_wc_price_history', true ],
			'return' => $history,
		] );

		$minimal = $subject->get_minimal_from_sale_start(
			$this->mock_product_with_sale_start( $product_id, $sale_start_timestamp ),
			30,
			'sale_start'
		);

		$this->assertEquals( $expected_minimal, $minimal );
	}

	private function mock_legacy_storage(): HistoryStorage {

		\WP_Mock::userFunction( 'get_option', [
			'args' => [ 'wc_price_history_migration_status', \WP_Mock\Functions::type( 'string' ) ],
			'return' => 'not_needed',
		] );

		\WP_Mock::userFunction( 'get_option', [
			'args' => [ 'gmt_offset' ],
			'return' => 0,
		] );

		\WP_Mock::userFunction( 'get_option', [
			'args' => [ 'wc_price_history_migration_status' ],
			'return' => 'not_needed',
		] );

		return $this->get_subject();
	}

	private function mock_product_with_sale_start( int $product_id, int $sale_start_timestamp ): \WC_Product {

		$sale_start = new class( $sale_start_timestamp ) {
			private int $timestamp;

			public function __construct( int $timestamp ) {
				$this->timestamp = $timestamp;
			}

			public function getOffsetTimestamp(): int {
				return $this->timestamp;
			}
		};

		$product = $this->getMockBuilder( \WC_Product::class )
			->disableOriginalConstructor()
			->addMethods( [ 'get_id', 'get_date_on_sale_from' ] )
			->getMock();
		$product->method( 'get_id' )
			->willReturn( $product_id );
		$product->method( 'get_date_on_sale_from' )
			->willReturn( $sale_start );

		return $product;
	}

	public function data_provider_get_minimal_from_sale_start_fallback() {

		$sale_start_timestamp = strtotime( '2026-06-18 00:00:00' );
		$cutoff_timestamp     = $sale_start_timestamp - ( 30 * DAY_IN_SECONDS );

		$history_only_before_window = [
			strtotime( '2025-06-23 10:00:00' ) => 44.99,
			strtotime( '2025-11-04 01:00:00' ) => 59.99,
		];

		$history_with_entry_in_window = $history_only_before_window + [
			$cutoff_timestamp + DAY_IN_SECONDS => 59.99,
		];

		return [
			'empty window falls back to lowest before cutoff' => [ $history_only_before_window, 44.99 ],
			'non-empty window does not use fallback'          => [ $history_with_entry_in_window, 59.99 ],
		];
	}

	public function data_provider_get_minimal() {

		$history = [
			time() - ( 31 * DAY_IN_SECONDS - 10 ) => '100',
			time() - ( 30 * DAY_IN_SECONDS - 10 ) => '200',
			time() - ( 29 * DAY_IN_SECONDS - 10 ) => '300',
		];

		$history_older_than_month = [
			time() - ( 31 * DAY_IN_SECONDS - 10 ) => '110',
			time() - ( 32 * DAY_IN_SECONDS - 10 ) => '220',
			time() - ( 33 * DAY_IN_SECONDS - 10 ) => '330',
		];

		return [
			[ [ time() => '0' ], 0 ],
			[ [ time() => '100' ], 100 ],
			[ $history, 200 ],
			[ $history_older_than_month, 0 ],
		];
	}

	public function data_provider_get_minimal_when_not_set() {

		return [
			[ null, 20 ],
			[ [], 20 ],
			[ '', 20 ],
			[ false, 20 ],
		];
	}
}
