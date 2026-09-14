<?php

namespace FastFood\Decorator;

use FastFood\Product\ProductInterface;

/**
 * Применяет набор добавок к продукту
 */
final class RecipeApplier
{
    public function __construct(
        private readonly ToppingFactoryInterface $toppingFactory,
    ) {
    }

    /**
     * @param ToppingChoice[] $recipe
     */
    public function apply(ProductInterface $product, array $recipe): ProductInterface
    {
        foreach ($recipe as $choice) {
            $product = $this->toppingFactory->getTopping($choice->type, $product, $choice->paramBag);
        }

        return $product;
    }
}
