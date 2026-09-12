<?php

namespace FastFood\Product;

final class Burger implements ProductInterface
{
    public function __construct(
        private readonly string $name = 'Бургер',
        private readonly float $basePrice = 150.0,
        private readonly int $baseTimeMinutes = 6,
    ) {
    }

    public function name(): string
    {
        return $this->name;
    }

    public function basePrice(): float
    {
        return $this->basePrice;
    }

    public function baseTimeMinutes(): int
    {
        return $this->baseTimeMinutes;
    }
}
