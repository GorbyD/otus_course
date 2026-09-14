<?php

namespace FastFood\Order;

use FastFood\Product\ProductInterface;

final class OrderItemFactory implements OrderItemFactoryInterface
{
    public function single(ProductInterface $product): OrderItemInterface
    {
        return new SingleOrderItem($product);
    }

    public function combo(string $name, array $items, float $discount = 0.0): OrderItemInterface
    {
        return new ComboOrderItem($name, $items, $discount);
    }
}
