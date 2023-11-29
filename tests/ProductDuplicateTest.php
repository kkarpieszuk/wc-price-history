<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use PriorPrice\ProductDuplicate;
use WC_Product;

class ProductDuplicateTest extends TestCase
{
    private $productDuplicate;

    protected function setUp(): void
    {
        $this->productDuplicate = new ProductDuplicate();
    }

    public function testRegisterHooks(): void
    {
        $this->productDuplicate->register_hooks();

        $this->assertNotFalse(has_action('woocommerce_product_duplicate_before_save', [$this->productDuplicate, 'flag_as_duplication_process']));
        $this->assertNotFalse(has_action('woocommerce_product_duplicate', [$this->productDuplicate, 'delete_history_from_duplicate']));
    }

    public function testDeleteHistoryFromDuplicate(): void
    {
        $mockProduct = $this->createMock(WC_Product::class);
        $mockProduct->method('get_id')->willReturn(1);

        $this->productDuplicate->delete_history_from_duplicate($mockProduct);

        $this->assertFalse(metadata_exists('post', 1, '_wc_price_history'));
    }
}
