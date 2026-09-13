<?php

namespace FastFood\Order;

use FastFood\Product\ProductInterface;

final class SingleOrderItem implements OrderItemInterface
{
    public function __construct(
        private readonly ProductInterface $product,
    ) {
    }

    public function price(): float
    {
        return $this->product->basePrice();
    }

    public function prepTimeMinutes(): int
    {
        return $this->product->baseTimeMinutes();
    }

    public function printReceipt(int $indent = 0): string
    {
        return str_repeat('  ', $indent)
            . sprintf('%s — %.2f ₽ (%d мин)', $this->product->name(), $this->price(), $this->prepTimeMinutes())
            . "\n";
    }
}
