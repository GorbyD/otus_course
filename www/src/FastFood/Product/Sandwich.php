<?php

namespace FastFood\Product;

final class Sandwich implements ProductInterface
{
    public function __construct(
        private readonly string $name = 'Сэндвич',
        private readonly float $basePrice = 120.0,
        private readonly int $baseTimeMinutes = 4,
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
