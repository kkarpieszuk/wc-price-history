<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use PriorPrice\ProductUpdates;
use function get_post_meta;

class ProductUpdatesTest extends TestCase
{
    public function testUpdatePriceHistory()
    {
        $productUpdates = new ProductUpdates();
        $productId = 1;
        $productUpdates->update_price_history($productId);

        $this->assertEquals('100', get_post_meta($productId, '_price', true));
    }

    public function testStartPriceHistory()
    {
        $productUpdates = new ProductUpdates();
        $productId = 1;
        $productUpdates->start_price_history($productId);

        $this->assertEquals('100', get_post_meta($productId, '_price', true));
    }
}
