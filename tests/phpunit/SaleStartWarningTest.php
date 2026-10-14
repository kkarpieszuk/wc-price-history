<?php

namespace PriorPrice;

use WP_Mock\Tools\TestCase;

class SaleStartWarningTest extends TestCase {

	/**
	 * @var SettingsData&\PHPUnit\Framework\MockObject\MockObject
	 */
	private $settings_data;

	/**
	 * @var SaleStartWarning
	 */
	private $subject;

	public function setUp() : void {
		\WP_Mock::setUp();

		$this->settings_data = $this->createMock( SettingsData::class );
		$this->subject       = new SaleStartWarning( $this->settings_data );
	}

	public function tearDown() : void {
		\WP_Mock::tearDown();
	}

	public function test_needs_warning_when_sale_start_mode_on_sale_without_date(): void {
		$this->settings_data->method( 'get_count_from' )->willReturn( 'sale_start' );

		$product = $this->create_simple_product_mock( true, 15 );

		\WP_Mock::userFunction( 'get_post_meta', [
			'args'   => [ 15, '_sale_price_dates_from', true ],
			'return' => '',
		] );

		$this->assertTrue( $this->invoke_needs_warning( $product ) );
	}

	public function test_needs_warning_when_sale_start_inclusive_mode(): void {
		$this->settings_data->method( 'get_count_from' )->willReturn( 'sale_start_inclusive' );

		$product = $this->create_simple_product_mock( true, 16 );

		\WP_Mock::userFunction( 'get_post_meta', [
			'args'   => [ 16, '_sale_price_dates_from', true ],
			'return' => '',
		] );

		$this->assertTrue( $this->invoke_needs_warning( $product ) );
	}

	public function test_no_warning_when_count_from_current_day(): void {
		$this->settings_data->method( 'get_count_from' )->willReturn( 'current_day' );

		$product = $this->create_simple_product_mock( true, 17 );

		$this->assertFalse( $this->invoke_needs_warning( $product ) );
	}

	public function test_no_warning_when_product_not_on_sale(): void {
		$this->settings_data->method( 'get_count_from' )->willReturn( 'sale_start' );

		$product = $this->create_simple_product_mock( false, 18 );

		$this->assertFalse( $this->invoke_needs_warning( $product ) );
	}

	public function test_no_warning_when_sale_start_date_set(): void {
		$this->settings_data->method( 'get_count_from' )->willReturn( 'sale_start' );

		$product = $this->create_simple_product_mock( true, 19 );

		\WP_Mock::userFunction( 'get_post_meta', [
			'args'   => [ 19, '_sale_price_dates_from', true ],
			'return' => (string) strtotime( '2026-06-18 00:00:00' ),
		] );

		$this->assertFalse( $this->invoke_needs_warning( $product ) );
	}

	public function test_variable_warns_when_one_on_sale_variation_lacks_date(): void {
		$this->settings_data->method( 'get_count_from' )->willReturn( 'sale_start' );

		$product = $this->create_variable_product_mock( true, 20, [ 21, 22 ] );

		\WP_Mock::userFunction( 'get_post_meta', [
			'return' => static function ( $id, $key, $single ) {
				if ( (int) $id === 21 && $key === '_sale_price' ) {
					return '10';
				}
				if ( (int) $id === 21 && $key === '_sale_price_dates_from' ) {
					return (string) strtotime( '2026-01-01' );
				}
				if ( (int) $id === 22 && $key === '_sale_price' ) {
					return '9';
				}
				if ( (int) $id === 22 && $key === '_sale_price_dates_from' ) {
					return '';
				}
				return '';
			},
		] );

		$this->assertTrue( $this->invoke_needs_warning( $product ) );
	}

	public function test_variable_no_warning_when_all_on_sale_variations_have_dates(): void {
		$this->settings_data->method( 'get_count_from' )->willReturn( 'sale_start' );

		$product = $this->create_variable_product_mock( true, 30, [ 31, 32 ] );

		\WP_Mock::userFunction( 'get_post_meta', [
			'return' => static function ( $id, $key, $single ) {
				if ( $key === '_sale_price' ) {
					return '10';
				}
				if ( $key === '_sale_price_dates_from' ) {
					return (string) strtotime( '2026-01-01' );
				}
				return '';
			},
		] );

		$this->assertFalse( $this->invoke_needs_warning( $product ) );
	}

	public function test_register_hooks_adds_notice_enqueue_ajax_and_post_states(): void {
		\WP_Mock::expectActionAdded( 'admin_notices', [ $this->subject, 'maybe_display_product_edit_notice' ] );
		\WP_Mock::expectActionAdded( 'admin_enqueue_scripts', [ $this->subject, 'maybe_enqueue_block_editor_notice' ] );
		\WP_Mock::expectActionAdded( 'wp_ajax_wc_price_history_sale_start_warning_check', [ $this->subject, 'ajax_check_product_warning' ] );
		\WP_Mock::expectFilterAdded( 'display_post_states', [ $this->subject, 'maybe_add_product_list_state' ], 10, 2 );

		$this->subject->register_hooks();

		$this->assertConditionsMet();
	}

	public function test_product_list_state_renders_badge_markup(): void {
		$this->ensure_wp_post_stub();

		$this->settings_data->method( 'get_count_from' )->willReturn( 'sale_start' );

		$product = $this->create_simple_product_mock( true, 11 );

		\WP_Mock::userFunction( 'wc_get_product', [
			'args'   => [ 11 ],
			'return' => $product,
		] );
		\WP_Mock::userFunction( 'get_post_meta', [
			'args'   => [ 11, '_sale_price_dates_from', true ],
			'return' => '',
		] );
		\WP_Mock::userFunction( 'esc_html__', [
			'return_arg' => 0,
		] );

		$post            = new \WP_Post();
		$post->ID        = 11;
		$post->post_type = 'product';

		$states = $this->subject->maybe_add_product_list_state( [], $post );

		$this->assertArrayHasKey( 'wc_price_history_sale_start', $states );
		$this->assertStringContainsString( 'wc-price-history-issue-badge', $states['wc_price_history_sale_start'] );
		$this->assertStringContainsString( 'Price history issue', $states['wc_price_history_sale_start'] );
	}

	public function test_product_list_state_skips_when_current_day(): void {
		$this->ensure_wp_post_stub();

		$this->settings_data->method( 'get_count_from' )->willReturn( 'current_day' );

		\WP_Mock::userFunction( 'wc_get_product', [
			'times' => 0,
		] );

		$post            = new \WP_Post();
		$post->ID        = 11;
		$post->post_type = 'product';

		$states = $this->subject->maybe_add_product_list_state( [], $post );

		$this->assertSame( [], $states );
	}

	/**
	 * @param bool $on_sale    Whether on sale.
	 * @param int  $product_id Product ID.
	 *
	 * @return \WC_Product&\PHPUnit\Framework\MockObject\MockObject
	 */
	private function create_simple_product_mock( bool $on_sale, int $product_id ) {
		$product = $this->getMockBuilder( \WC_Product::class )
			->disableOriginalConstructor()
			->addMethods( [ 'is_on_sale', 'is_type', 'get_id', 'get_children' ] )
			->getMock();
		$product->method( 'is_on_sale' )->willReturn( $on_sale );
		$product->method( 'is_type' )->with( 'variable' )->willReturn( false );
		$product->method( 'get_id' )->willReturn( $product_id );

		return $product;
	}

	/**
	 * @param bool  $on_sale    Whether parent on sale.
	 * @param int   $product_id Parent ID.
	 * @param int[] $children   Child IDs.
	 *
	 * @return \WC_Product&\PHPUnit\Framework\MockObject\MockObject
	 */
	private function create_variable_product_mock( bool $on_sale, int $product_id, array $children ) {
		$product = $this->getMockBuilder( \WC_Product_Variable::class )
			->disableOriginalConstructor()
			->addMethods( [ 'is_on_sale', 'is_type', 'get_id', 'get_children' ] )
			->getMock();
		$product->method( 'is_on_sale' )->willReturn( $on_sale );
		$product->method( 'is_type' )->with( 'variable' )->willReturn( true );
		$product->method( 'get_id' )->willReturn( $product_id );
		$product->method( 'get_children' )->willReturn( $children );

		return $product;
	}

	/**
	 * @param \WC_Product $product Product.
	 *
	 * @return bool
	 */
	private function invoke_needs_warning( \WC_Product $product ): bool {
		$method = new \ReflectionMethod( SaleStartWarning::class, 'needs_warning' );
		$method->setAccessible( true );

		return (bool) $method->invoke( $this->subject, $product );
	}

	/**
	 * @return void
	 */
	private function ensure_wp_post_stub(): void {
		if ( class_exists( '\WP_Post', false ) ) {
			return;
		}

		eval( // phpcs:ignore Squiz.PHP.Eval.Discouraged
			'class WP_Post { public $ID; public $post_type; }'
		);
	}
}
