<?php

namespace FastFood\Cooking;

use FastFood\Product\ProductInterface;

final class InMemoryIngredientStock implements IngredientStockInterface
{
    /**
     * @param string[] $outOfStockProductNames
     */
    public function __construct(
        private readonly array $outOfStockProductNames = [],
    ) {
    }

    public function hasEnoughFor(ProductInterface $product): bool
    {
        foreach ($this->outOfStockProductNames as $outOfStock) {
            if (str_contains($product->name(), $outOfStock)) {
                return false;
            }
        }

        return true;
    }
}
