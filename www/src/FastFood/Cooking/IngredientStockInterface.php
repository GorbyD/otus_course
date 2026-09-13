<?php

namespace FastFood\Cooking;

use FastFood\Product\ProductInterface;

interface IngredientStockInterface
{
    public function hasEnoughFor(ProductInterface $product): bool;
}
