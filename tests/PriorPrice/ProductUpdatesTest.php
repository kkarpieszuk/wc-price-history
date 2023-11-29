<?php

namespace Tests\PriorPrice;

use PHPUnit\Framework\TestCase;
use PriorPrice\ProductUpdates;
use PriorPrice\ProductDuplicate;
use PriorPrice\HistoryStorage;
use WC_Product;

class ProductUpdatesTest extends TestCase
{
    public function testStartPriceHistory()
    {
        $historyStorageMock = $this->createMock(HistoryStorage::class);
        $productMock = $this->createMock(DummyProduct::class);
        $productMock->method('get_id')->willReturn(1);
        $productMock->method('get_price')->willReturn('100');

        $productUpdates = new ProductUpdates($historyStorageMock);

        $historyStorageMock->expects($this->once())
            ->method('add_first_price')
            ->with($this->equalTo(1), $this->equalTo(100.0));

        $productUpdates->start_price_history(1);
    }
}
