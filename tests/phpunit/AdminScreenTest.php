<?php

namespace PriorPrice;

use PriorPrice\Helpers\AdminScreen;
use WP_Mock\Tools\TestCase;

class AdminScreenTest extends TestCase {

	public function setUp() : void {
		\WP_Mock::setUp();
		$_GET = [];

		\WP_Mock::userFunction( 'wp_unslash', [
			'return_arg' => 0,
		] );
		\WP_Mock::userFunction( 'absint', [
			'return' => static function ( $value ) {
				return abs( (int) $value );
			},
		] );
	}

	public function tearDown() : void {
		$_GET = [];
		\WP_Mock::tearDown();
	}

	public function test_is_wc_admin_page(): void {
		$_GET['page'] = 'wc-admin';

		$this->assertTrue( AdminScreen::is_wc_admin_page() );
	}

	public function test_get_edited_product_id_from_block_path(): void {
		$_GET['page'] = 'wc-admin';
		$_GET['path'] = '/product/11';

		$this->assertSame( 11, AdminScreen::get_edited_product_id() );
	}

	public function test_get_edited_product_id_accepts_urlencoded_path(): void {
		$_GET['path'] = rawurlencode( '/product/99' );

		$this->assertSame( 99, AdminScreen::get_edited_product_id() );
	}

	public function test_get_edited_product_id_zero_for_add_product_path(): void {
		$_GET['path'] = '/add-product';

		$this->assertSame( 0, AdminScreen::get_edited_product_id() );
	}

	public function test_get_edited_product_id_prefers_classic_post(): void {
		$_GET['post'] = '7';
		$_GET['path'] = '/product/11';

		$this->assertSame( 7, AdminScreen::get_edited_product_id() );
	}
}
