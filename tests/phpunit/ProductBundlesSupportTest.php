<?php

namespace WcPriceHistoryPro;

use WP_Mock\Tools\TestCase;

/**
 * Tests for Product Bundles Pro integration helpers.
 *
 * @group pro
 */
class ProductBundlesSupportTest extends TestCase {

	public static function setUpBeforeClass(): void {
		$pro_autoload = dirname( __DIR__, 2 ) . '/../wc-price-history-pro/vendor/autoload.php';
		if ( file_exists( $pro_autoload ) ) {
			require_once $pro_autoload;
		}
	}

	public function setUp(): void {
		\WP_Mock::setUp();
	}

	public function tearDown(): void {
		\WP_Mock::tearDown();
	}

	public function test_apply_bundle_discount_reduces_price_by_percentage(): void {
		if ( ! class_exists( ProductBundlesSupport::class ) ) {
			$this->markTestSkipped( 'WC Price History Pro is not available.' );
		}

		\WP_Mock::userFunction( 'wc_get_price_decimals' )->andReturn( 2 );

		$this->assertSame( 85.0, ProductBundlesSupport::apply_bundle_discount( 100.0, 15.0 ) );
		$this->assertSame( 100.0, ProductBundlesSupport::apply_bundle_discount( 100.0, 0.0 ) );
		$this->assertSame( 0.0, ProductBundlesSupport::apply_bundle_discount( 0.0, 15.0 ) );
	}
}
