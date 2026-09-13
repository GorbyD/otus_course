<?php

namespace FastFood\Cooking;

use FastFood\Product\ProductInterface;

interface QualityStandardInterface
{
    public function passes(ProductInterface $product): bool;
}
