<?php

namespace Tests\PriorPrice;

use PHPUnit\Framework\TestCase;
use PriorPrice\ProductDuplicate;
use WC_Product;

class ProductDuplicateTest extends TestCase
{
    public function testFlagAsDuplicationProcess()
    {
        $productDuplicate = new ProductDuplicate();
        $product = $this->createMock(DummyProduct::class);

        $productDuplicate->flag_as_duplication_process($product);

        $this->assertTrue(ProductDuplicate::$is_during_duplication);
    }

    public function testDeleteHistoryFromDuplicate()
    {
        $productDuplicate = new ProductDuplicate();
        $product = $this->createMock(\WC_Product::class);
        $product->method('get_id')->willReturn(1);

        delete_post_meta(1, '_wc_price_history');
        add_post_meta(1, '_wc_price_history', 'test');

        $productDuplicate->delete_history_from_duplicate(1);

        $this->assertEmpty(get_post_meta(1, '_wc_price_history', true));
    }
}
class DummyProduct {}
