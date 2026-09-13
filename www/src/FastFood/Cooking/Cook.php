<?php

namespace FastFood\Cooking;

use FastFood\Product\ProductInterface;

final class Cook implements CookInterface
{
    public function cook(ProductInterface $product): CookingResult
    {
        return CookingResult::served($product);
    }
}
