<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use PriorPrice\ProductUpdates;
use PriorPrice\HistoryStorage;

class ProductUpdatesTest extends TestCase
{
    private $productUpdates;

    protected function setUp(): void
    {
        $historyStorage = $this->createMock(HistoryStorage::class);
        $this->productUpdates = new ProductUpdates($historyStorage);
    }

    public function testStartPriceHistory(): void
    {
        $productId = 1;
        $productPrice = 100.0;

        update_post_meta($productId, '_price', $productPrice);

        $this->productUpdates->start_price_history($productId);

        $this->assertEquals($productPrice, get_post_meta($productId, '_price', true));
    }
}
