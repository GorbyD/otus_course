<?php

namespace FastFood\Decorator;

use FastFood\Product\ProductInterface;

final class SauceTopping extends ToppingDecorator
{
    public function __construct(
        ProductInterface $product,
        private readonly string $sauceName = 'соус',
    ) {
        parent::__construct($product);
    }

    protected function toppingName(): string
    {
        return $this->sauceName;
    }

    protected function toppingPrice(): float
    {
        return 15.0;
    }

    protected function toppingTimeMinutes(): int
    {
        return 0;
    }
}
