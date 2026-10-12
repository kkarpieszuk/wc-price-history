<?php

namespace PriorPrice;

use WP_Mock\Tools\TestCase;

class PricesTest extends TestCase {

	public function setUp() : void {
		\WP_Mock::setUp();
	}

	public function tearDown() : void {
		\WP_Mock::tearDown();
	}

	public function test_variable_parent_sale_start_uses_min_of_on_sale_variation_lowest_prices(): void {

		$history_storage = $this->createMock( HistoryStorage::class );
		$settings_data   = $this->createMock( SettingsData::class );
		$taxes           = $this->createMock( Taxes::class );

		$settings_data->method( 'get_days_number' )
			->willReturn( 30 );
		$settings_data->method( 'get_count_from' )
			->willReturn( 'sale_start' );

		$variation_a = $this->create_variation_mock( 11, true );
		$variation_b = $this->create_variation_mock( 12, true );
		$variation_c = $this->create_variation_mock( 13, false );

		$history_storage->expects( $this->exactly( 2 ) )
			->method( 'get_minimal_from_sale_start' )
			->willReturnCallback(
				static function ( $product ) {
					$id = $product->get_id();
					if ( $id === 11 ) {
						return 199.99;
					}
					if ( $id === 12 ) {
						return 149.99;
					}
					return 0.0;
				}
			);
		$history_storage->expects( $this->never() )
			->method( 'get_minimal' );

		$parent = $this->getMockBuilder( \WC_Product_Variable::class )
			->disableOriginalConstructor()
			->addMethods( [ 'get_id', 'get_available_variations', 'is_on_sale' ] )
			->getMock();
		$parent->method( 'get_id' )
			->willReturn( 10 );
		$parent->method( 'is_on_sale' )
			->willReturn( true );
		$parent->method( 'get_available_variations' )
			->with( 'objects' )
			->willReturn( [ $variation_a, $variation_b, $variation_c ] );

		$prices = new Prices( $history_storage, $settings_data, $taxes );

		$this->assertSame( 149.99, $prices->get_lowest_price_raw_non_taxed( $parent ) );
	}

	public function test_variable_parent_falls_back_to_parent_calc_when_no_variation_candidates(): void {

		$history_storage = $this->createMock( HistoryStorage::class );
		$settings_data   = $this->createMock( SettingsData::class );
		$taxes           = $this->createMock( Taxes::class );

		$settings_data->method( 'get_days_number' )
			->willReturn( 30 );
		$settings_data->method( 'get_count_from' )
			->willReturn( 'sale_start' );

		$variation = $this->create_variation_mock( 11, false );

		$history_storage->expects( $this->once() )
			->method( 'get_minimal_from_sale_start' )
			->willReturn( 88.0 );
		$history_storage->expects( $this->never() )
			->method( 'get_minimal' );

		$parent = $this->getMockBuilder( \WC_Product_Variable::class )
			->disableOriginalConstructor()
			->addMethods( [ 'get_id', 'get_available_variations', 'is_on_sale' ] )
			->getMock();
		$parent->method( 'get_id' )
			->willReturn( 10 );
		$parent->method( 'is_on_sale' )
			->willReturn( true );
		$parent->method( 'get_available_variations' )
			->with( 'objects' )
			->willReturn( [ $variation ] );

		$prices = new Prices( $history_storage, $settings_data, $taxes );

		$this->assertSame( 88.0, $prices->get_lowest_price_raw_non_taxed( $parent ) );
	}

	public function test_simple_product_sale_start_unchanged(): void {

		$history_storage = $this->createMock( HistoryStorage::class );
		$settings_data   = $this->createMock( SettingsData::class );
		$taxes           = $this->createMock( Taxes::class );

		$settings_data->method( 'get_days_number' )
			->willReturn( 30 );
		$settings_data->method( 'get_count_from' )
			->willReturn( 'sale_start' );

		$product = $this->getMockBuilder( \WC_Product::class )
			->disableOriginalConstructor()
			->addMethods( [ 'get_id', 'is_on_sale' ] )
			->getMock();
		$product->method( 'get_id' )
			->willReturn( 5 );
		$product->method( 'is_on_sale' )
			->willReturn( true );

		$history_storage->expects( $this->once() )
			->method( 'get_minimal_from_sale_start' )
			->with( $product, 30, 'sale_start' )
			->willReturn( 42.5 );

		$prices = new Prices( $history_storage, $settings_data, $taxes );

		$this->assertSame( 42.5, $prices->get_lowest_price_raw_non_taxed( $product ) );
	}

	/**
	 * @param int  $id      Variation ID.
	 * @param bool $on_sale Whether variation is on sale.
	 *
	 * @return \WC_Product_Variation
	 */
	private function create_variation_mock( int $id, bool $on_sale ): \WC_Product_Variation {

		$variation = $this->getMockBuilder( \WC_Product_Variation::class )
			->disableOriginalConstructor()
			->addMethods( [ 'get_id', 'is_on_sale' ] )
			->getMock();
		$variation->method( 'get_id' )
			->willReturn( $id );
		$variation->method( 'is_on_sale' )
			->willReturn( $on_sale );

		return $variation;
	}
}
