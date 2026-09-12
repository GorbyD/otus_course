<?php

namespace FastFood\Product;

final class HotDog implements ProductInterface
{
    public function __construct(
        private readonly string $name = 'Хот-дог',
        private readonly float $basePrice = 100.0,
        private readonly int $baseTimeMinutes = 3,
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
