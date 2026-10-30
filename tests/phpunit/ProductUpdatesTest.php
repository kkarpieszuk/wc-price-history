<?php

namespace PriorPrice;

use WP_Mock\Tools\TestCase;

class ProductUpdatesTest extends TestCase {

	/**
	 * @var HistoryStorage&\PHPUnit\Framework\MockObject\MockObject
	 */
	private $history_storage;

	/**
	 * @var ProductUpdates
	 */
	private $subject;

	public function setUp() : void {
		\WP_Mock::setUp();

		$this->history_storage = $this->createMock( HistoryStorage::class );
		$this->subject         = new ProductUpdates( $this->history_storage );
	}

	public function tearDown() : void {
		\WP_Mock::tearDown();
	}

	public function test_update_price_history_handles_multiple_products_in_one_request(): void {
		$first  = $this->create_simple_product_mock( 10.0 );
		$second = $this->create_simple_product_mock( 20.0 );

		\WP_Mock::userFunction( 'get_post_status', [
			'args'   => [ 101 ],
			'return' => 'publish',
		] );
		\WP_Mock::userFunction( 'get_post_status', [
			'args'   => [ 102 ],
			'return' => 'publish',
		] );

		\WP_Mock::userFunction( 'wc_get_product', [
			'args'   => [ 101 ],
			'return' => $first,
		] );
		\WP_Mock::userFunction( 'wc_get_product', [
			'args'   => [ 102 ],
			'return' => $second,
		] );

		\WP_Mock::onFilter( 'wc_price_history_price_raw_non_taxed' )
			->with( 10.0, $first )
			->reply( 10.0 );
		\WP_Mock::onFilter( 'wc_price_history_price_raw_non_taxed' )
			->with( 20.0, $second )
			->reply( 20.0 );

		$this->history_storage
			->expects( $this->exactly( 2 ) )
			->method( 'add_price' )
			->withConsecutive(
				[ 101, 10.0, false ],
				[ 102, 20.0, false ]
			);

		$this->subject->update_price_history( 101 );
		$this->subject->update_price_history( 102 );
	}

	public function test_draft_early_return_does_not_block_later_published_product(): void {
		$published = $this->create_simple_product_mock( 15.5 );

		\WP_Mock::userFunction( 'get_post_status', [
			'args'   => [ 201 ],
			'return' => 'draft',
		] );
		\WP_Mock::userFunction( 'get_post_status', [
			'args'   => [ 202 ],
			'return' => 'publish',
		] );

		\WP_Mock::userFunction( 'wc_get_product', [
			'args'   => [ 202 ],
			'return' => $published,
		] );

		\WP_Mock::onFilter( 'wc_price_history_price_raw_non_taxed' )
			->with( 15.5, $published )
			->reply( 15.5 );

		$this->history_storage
			->expects( $this->once() )
			->method( 'add_price' )
			->with( 202, 15.5, false );

		$this->subject->update_price_history( 201 );
		$this->subject->update_price_history( 202 );
	}

	public function test_same_product_reentry_is_skipped(): void {
		$product = $this->create_simple_product_mock( 9.99 );
		$subject = $this->subject;

		\WP_Mock::userFunction( 'get_post_status', [
			'args'   => [ 301 ],
			'return' => 'publish',
		] );

		\WP_Mock::userFunction( 'wc_get_product', [
			'args'   => [ 301 ],
			'return' => $product,
		] );

		\WP_Mock::onFilter( 'wc_price_history_price_raw_non_taxed' )
			->with( 9.99, $product )
			->reply( 9.99 );

		$this->history_storage
			->expects( $this->once() )
			->method( 'add_price' )
			->with( 301, 9.99, false )
			->willReturnCallback(
				static function () use ( $subject ): int {
					// Nested re-entry for the same product must be ignored.
					$subject->update_price_history( 301 );

					return 1;
				}
			);

		$subject->update_price_history( 301 );
	}

	public function test_processing_lock_is_released_after_draft_so_same_id_can_run_again(): void {
		$product = $this->create_simple_product_mock( 7.0 );

		\WP_Mock::userFunction( 'get_post_status', [
			'args'            => [ 401 ],
			'return_in_order' => [ 'draft', 'publish' ],
		] );

		\WP_Mock::userFunction( 'wc_get_product', [
			'args'   => [ 401 ],
			'return' => $product,
		] );

		\WP_Mock::onFilter( 'wc_price_history_price_raw_non_taxed' )
			->with( 7.0, $product )
			->reply( 7.0 );

		$this->history_storage
			->expects( $this->once() )
			->method( 'add_price' )
			->with( 401, 7.0, false );

		$this->subject->update_price_history( 401 );
		$this->subject->update_price_history( 401 );
	}

	/**
	 * @return \WC_Product&\PHPUnit\Framework\MockObject\MockObject
	 */
	private function create_simple_product_mock( float $price ) {
		$product = $this->getMockBuilder( 'WC_Product' )
			->disableOriginalConstructor()
			->setMethods( [ 'get_price', 'is_type' ] )
			->getMock();

		$product->method( 'get_price' )->willReturn( $price );
		$product->method( 'is_type' )->with( 'variable' )->willReturn( false );

		return $product;
	}
}
