<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use PriorPrice\ProductDuplicate;
use function delete_post_meta;
use function add_post_meta;

class ProductDuplicateTest extends TestCase
{
    public function testFlagAsDuplicationProcess()
    {
        $productDuplicate = new ProductDuplicate();
        $productDuplicate->flag_as_duplication_process(new \WC_Product());

        $this->assertTrue(ProductDuplicate::$is_during_duplication);
    }

    public function testDeleteHistoryFromDuplicate()
    {
        $productDuplicate = new ProductDuplicate();
        $productId = 1;
        add_post_meta($productId, '_wc_price_history', '100');
        $productDuplicate->delete_history_from_duplicate($productId);

        $this->assertFalse(get_post_meta($productId, '_wc_price_history', true));
    }
}
