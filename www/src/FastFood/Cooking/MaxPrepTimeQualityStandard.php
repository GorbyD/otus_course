<?php

namespace FastFood\Cooking;

use FastFood\Product\ProductInterface;

final class MaxPrepTimeQualityStandard implements QualityStandardInterface
{
    public function __construct(
        private readonly int $maxMinutes,
    ) {
    }

    public function passes(ProductInterface $product): bool
    {
        return $product->baseTimeMinutes() <= $this->maxMinutes;
    }
}
