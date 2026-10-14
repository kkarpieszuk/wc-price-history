<?php

namespace PriorPrice;

use WP_Mock\Tools\TestCase;

class SaleStartWarningTest extends TestCase {

	/**
	 * @var SettingsData&\PHPUnit\Framework\MockObject\MockObject
	 */
	private $settings_data;

	/**
	 * @var SaleStartDate&\PHPUnit\Framework\MockObject\MockObject
	 */
	private $sale_start_date;

	/**
	 * @var SaleStartWarning
	 */
	private $subject;

	public function setUp() : void {
		\WP_Mock::setUp();

		$this->settings_data   = $this->createMock( SettingsData::class );
		$this->sale_start_date = $this->createMock( SaleStartDate::class );
		$this->subject         = new SaleStartWarning( $this->settings_data, $this->sale_start_date );
	}

	public function tearDown() : void {
		\WP_Mock::tearDown();
	}

	public function test_needs_warning_when_sale_start_mode_on_sale_without_date(): void {
		$this->settings_data->method( 'get_count_from' )->willReturn( 'sale_start' );
		$this->sale_start_date->method( 'resolve' )->willReturn( null );

		$product = $this->create_product_mock( true );

		$this->assertTrue( $this->invoke_needs_warning( $product ) );
	}

	public function test_needs_warning_when_sale_start_inclusive_mode(): void {
		$this->settings_data->method( 'get_count_from' )->willReturn( 'sale_start_inclusive' );
		$this->sale_start_date->method( 'resolve' )->willReturn( null );

		$product = $this->create_product_mock( true );

		$this->assertTrue( $this->invoke_needs_warning( $product ) );
	}

	public function test_no_warning_when_count_from_current_day(): void {
		$this->settings_data->method( 'get_count_from' )->willReturn( 'current_day' );

		$product = $this->create_product_mock( true );

		$this->assertFalse( $this->invoke_needs_warning( $product ) );
	}

	public function test_no_warning_when_product_not_on_sale(): void {
		$this->settings_data->method( 'get_count_from' )->willReturn( 'sale_start' );

		$product = $this->create_product_mock( false );

		$this->assertFalse( $this->invoke_needs_warning( $product ) );
	}

	public function test_no_warning_when_sale_start_date_resolves(): void {
		$this->settings_data->method( 'get_count_from' )->willReturn( 'sale_start' );

		$sale_start = new class() {
			public function getTimestamp(): int {
				return strtotime( '2026-06-18 00:00:00' );
			}
		};

		$this->sale_start_date->method( 'resolve' )->willReturn( $sale_start );

		$product = $this->create_product_mock( true );

		$this->assertFalse( $this->invoke_needs_warning( $product ) );
	}

	public function test_register_hooks_adds_notice_and_post_states(): void {
		\WP_Mock::expectActionAdded( 'admin_notices', [ $this->subject, 'maybe_display_product_edit_notice' ] );
		\WP_Mock::expectFilterAdded( 'display_post_states', [ $this->subject, 'maybe_add_product_list_state' ], 10, 2 );

		$this->subject->register_hooks();

		$this->assertConditionsMet();
	}

	public function test_product_list_state_renders_badge_markup(): void {
		$this->ensure_wp_post_stub();

		$this->settings_data->method( 'get_count_from' )->willReturn( 'sale_start' );
		$this->sale_start_date->method( 'resolve' )->willReturn( null );

		$product = $this->create_product_mock( true );

		\WP_Mock::userFunction( 'wc_get_product', [
			'args'   => [ 11 ],
			'return' => $product,
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

	public function test_product_list_state_skips_wc_get_product_when_current_day(): void {
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
	 * @param bool $on_sale Whether the product is on sale.
	 *
	 * @return \WC_Product&\PHPUnit\Framework\MockObject\MockObject
	 */
	private function create_product_mock( bool $on_sale ) {
		$product = $this->getMockBuilder( \WC_Product::class )
			->disableOriginalConstructor()
			->addMethods( [ 'is_on_sale' ] )
			->getMock();
		$product->method( 'is_on_sale' )->willReturn( $on_sale );

		return $product;
	}

	/**
	 * Invoke private needs_warning via reflection.
	 *
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
	 * Provide a minimal WP_Post stub for unit tests.
	 *
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
