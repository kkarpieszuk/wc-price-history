<?php

namespace PriorPrice;

use  \WP_Mock\Tools\TestCase;

class HistoryStorageTest extends TestCase {

	public function setUp() : void {
		\WP_Mock::setUp();
	}

	public function tearDown() : void {
		\WP_Mock::tearDown();
	}

	/**
	 *
	 * @dataProvider data_provider_get_minimal
	 */
	public function test_get_minimal( $history, $expected_minimal ) {

		$product_id = 1;

		$subject = $this->get_subject();

		\WP_Mock::userFunction( 'get_post_meta', [
			'times' => 1,
			'args' => [ $product_id, '_wc_price_history', true ],
			'return' => $history
		] );


		$minimal = $subject->get_minimal( $product_id, 30 );

		$this->assertEquals( $expected_minimal, $minimal );
	}

	private function get_subject() {
		return new HistoryStorage();
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
			[ null, 0 ],
			[ [], 0 ],
			[ [ time() => '0' ], 0 ],
			[ [ time() => '100' ], 100 ],
			[ $history, 200 ],
			[ $history_older_than_month, 0 ],
		];
	}
}
