<?php

namespace PriorPrice\PriceDisplayStrategy;

interface PriceDisplayStrategy {

	public function lowest_price_html( \WC_Product $product ) : string;
}