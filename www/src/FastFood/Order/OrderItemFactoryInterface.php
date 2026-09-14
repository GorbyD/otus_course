<?php

namespace FastFood\Order;

use FastFood\Product\ProductInterface;

interface OrderItemFactoryInterface
{
    public function single(ProductInterface $product): OrderItemInterface;

    /**
     * @param OrderItemInterface[] $items
     */
    public function combo(string $name, array $items, float $discount = 0.0): OrderItemInterface;
}
