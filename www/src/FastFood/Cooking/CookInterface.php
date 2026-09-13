<?php

namespace FastFood\Cooking;

use FastFood\Product\ProductInterface;

/**
 * Общий интерфейс "кто готовит"
 */
interface CookInterface
{
    public function cook(ProductInterface $product): CookingResult;
}
