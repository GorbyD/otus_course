<?php

namespace FastFood\Product;

/**
 */
interface ProductInterface
{
    public function name(): string;

    public function basePrice(): float;

    public function baseTimeMinutes(): int;
}
