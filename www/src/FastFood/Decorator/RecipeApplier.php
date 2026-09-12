<?php

namespace FastFood\Decorator;

use FastFood\Product\ProductInterface;

/**
 * Применяет набор добавок к продукту
 */
final class RecipeApplier
{
    /**
     * @param list<callable(ProductInterface): ProductInterface> $toppings
     */
    public function apply(ProductInterface $product, array $toppings): ProductInterface
    {
        foreach ($toppings as $addTopping) {
            $product = $addTopping($product);
        }

        return $product;
    }
}
