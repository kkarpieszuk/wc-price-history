<?php

namespace PriorPrice;

use WP_Mock\Tools\TestCase;

class SaleStartDateTest extends TestCase {

	/**
	 * @var SaleStartDate
	 */
	private $subject;

	public function setUp() : void {
		\WP_Mock::setUp();
		$this->subject = new SaleStartDate();
	}

	public function tearDown() : void {
		\WP_Mock::tearDown();
	}

	public function test_resolve_returns_product_sale_start_when_set(): void {

		$sale_start = $this->create_sale_start( strtotime( '2026-06-18 00:00:00' ) );

		$product = $this->getMockBuilder( \WC_Product::class )
			->disableOriginalConstructor()
			->addMethods( [ 'get_date_on_sale_from' ] )
			->getMock();
		$product->method( 'get_date_on_sale_from' )
			->willReturn( $sale_start );

		$this->assertSame( $sale_start, $this->subject->resolve( $product ) );
	}

	public function test_resolve_returns_null_for_simple_product_without_sale_start(): void {

		$product = $this->getMockBuilder( \WC_Product::class )
			->disableOriginalConstructor()
			->addMethods( [ 'get_date_on_sale_from' ] )
			->getMock();
		$product->method( 'get_date_on_sale_from' )
			->willReturn( null );

		$this->assertNull( $this->subject->resolve( $product ) );
	}

	public function test_resolve_uses_earliest_on_sale_variation_date_for_variable_parent(): void {

		$earlier = $this->create_sale_start( strtotime( '2026-06-10 00:00:00' ) );
		$later   = $this->create_sale_start( strtotime( '2026-06-18 00:00:00' ) );

		$variation_earlier = $this->create_variation_mock( true, $earlier );
		$variation_later   = $this->create_variation_mock( true, $later );
		$variation_off     = $this->create_variation_mock( false, $this->create_sale_start( strtotime( '2026-05-01 00:00:00' ) ) );

		$product = $this->getMockBuilder( \WC_Product_Variable::class )
			->disableOriginalConstructor()
			->addMethods( [ 'get_date_on_sale_from', 'get_available_variations' ] )
			->getMock();
		$product->method( 'get_date_on_sale_from' )
			->willReturn( null );
		$product->method( 'get_available_variations' )
			->with( 'objects' )
			->willReturn( [ $variation_later, $variation_off, $variation_earlier ] );

		$this->assertSame( $earlier, $this->subject->resolve( $product ) );
	}

	public function test_resolve_returns_null_when_variable_children_have_no_sale_dates(): void {

		$variation = $this->create_variation_mock( true, null );

		$product = $this->getMockBuilder( \WC_Product_Variable::class )
			->disableOriginalConstructor()
			->addMethods( [ 'get_date_on_sale_from', 'get_available_variations' ] )
			->getMock();
		$product->method( 'get_date_on_sale_from' )
			->willReturn( null );
		$product->method( 'get_available_variations' )
			->with( 'objects' )
			->willReturn( [ $variation ] );

		$this->assertNull( $this->subject->resolve( $product ) );
	}

	/**
	 * @param int $timestamp Sale start timestamp.
	 *
	 * @return object
	 */
	private function create_sale_start( int $timestamp ) {

		return new class( $timestamp ) {
			private int $timestamp;

			public function __construct( int $timestamp ) {
				$this->timestamp = $timestamp;
			}

			public function getTimestamp(): int {
				return $this->timestamp;
			}

			public function getOffsetTimestamp(): int {
				return $this->timestamp;
			}
		};
	}

	/**
	 * @param bool        $on_sale    Whether variation is on sale.
	 * @param object|null $sale_start Sale start date.
	 *
	 * @return \WC_Product_Variation
	 */
	private function create_variation_mock( bool $on_sale, $sale_start ): \WC_Product_Variation {

		$variation = $this->getMockBuilder( \WC_Product_Variation::class )
			->disableOriginalConstructor()
			->addMethods( [ 'is_on_sale', 'get_date_on_sale_from' ] )
			->getMock();
		$variation->method( 'is_on_sale' )
			->willReturn( $on_sale );
		$variation->method( 'get_date_on_sale_from' )
			->willReturn( $sale_start );

		return $variation;
	}
}
