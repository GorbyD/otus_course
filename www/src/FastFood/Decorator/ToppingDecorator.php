<?php

namespace FastFood\Decorator;

use FastFood\Product\ProductInterface;

/**
 * Обертка готового ProductInterface, которая добавляет к нему одну добавку
 */
abstract class ToppingDecorator implements ProductInterface
{
    public function __construct(
        protected readonly ProductInterface $product,
    ) {
    }

    public function name(): string
    {
        return $this->product->name() . ' + ' . $this->toppingName();
    }

    public function basePrice(): float
    {
        return $this->product->basePrice() + $this->toppingPrice();
    }

    public function baseTimeMinutes(): int
    {
        return $this->product->baseTimeMinutes() + $this->toppingTimeMinutes();
    }

    abstract protected function toppingName(): string;

    abstract protected function toppingPrice(): float;

    abstract protected function toppingTimeMinutes(): int;
}
