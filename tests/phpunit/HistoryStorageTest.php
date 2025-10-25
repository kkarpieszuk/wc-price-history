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
