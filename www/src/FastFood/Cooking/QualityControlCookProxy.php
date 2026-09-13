<?php

namespace FastFood\Cooking;

use FastFood\Product\ProductInterface;

/**
 * Прокси имплементирует тот же интерфейс, и может выполнять дополнительные проверки перед вызовом реального повара.
 */
final class QualityControlCookProxy implements CookInterface
{
    public function __construct(
        private readonly CookInterface $realCook,
        private readonly IngredientStockInterface $stock,
        private readonly QualityStandardInterface $qualityStandard,
        private readonly DisposalInterface $disposal,
    ) {
    }

    public function cook(ProductInterface $product): CookingResult
    {
        if (!$this->stock->hasEnoughFor($product)) {
            return CookingResult::rejected(sprintf('не хватает ингредиентов для «%s»', $product->name()));
        }

        $result = $this->realCook->cook($product);

        if ($result->isServed() && $result->product !== null && !$this->qualityStandard->passes($result->product)) {
            $this->disposal->dispose($result->product, 'не прошёл контроль качества');

            return CookingResult::rejected(sprintf('«%s» не прошёл контроль качества и утилизирован', $product->name()));
        }

        return $result;
    }
}
