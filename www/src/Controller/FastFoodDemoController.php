<?php

namespace Controller;

use FastFood\Cooking\CollectingDisposal;
use FastFood\Cooking\CookInterface;
use FastFood\Decorator\LettuceTopping;
use FastFood\Decorator\OnionTopping;
use FastFood\Decorator\PepperTopping;
use FastFood\Decorator\RecipeApplier;
use FastFood\Decorator\SauceTopping;
use FastFood\Factory\ProductFactoryInterface;
use FastFood\Product\ProductInterface;
use Http\Request;
use Http\Response;

/**
 * Демо для паттернов
 */
final class FastFoodDemoController
{
    /** @param array<string, ProductFactoryInterface> $productFactories */
    public function __construct(
        private readonly array $productFactories,
        private readonly RecipeApplier $recipeApplier,
        private readonly CookInterface $cook,
        private readonly CollectingDisposal $disposal,
    ) {
    }

    public function handle(Request $request): Response
    {
        $out = "\n1. Абстрактная фабрика\n";
        foreach ($this->productFactories as $key => $factory) {
            $product = $factory->createProduct();
            $packaging = $factory->createPackaging();
            $out .= sprintf(
                "%-8s -> %s, %.2f ₽, %d мин | упаковка: %s\n",
                $key,
                $product->name(),
                $product->basePrice(),
                $product->baseTimeMinutes(),
                $packaging->name(),
            );
        }

        $out .= "\n2. Декоратор\n";

        $cheeseburgerRecipe = [
            static fn (ProductInterface $p): ProductInterface => new OnionTopping($p),
            static fn (ProductInterface $p): ProductInterface => new SauceTopping($p, 'сырный соус'),
        ];
        $recipeBurger = $this->recipeApplier->apply(
            $this->productFactories['burger']->createProduct(),
            $cheeseburgerRecipe,
        );
        $out .= sprintf(
            "Фикс. рецепт: %s — %.2f ₽ (%d мин)\n",
            $recipeBurger->name(),
            $recipeBurger->basePrice(),
            $recipeBurger->baseTimeMinutes(),
        );

        $customChoice = [
            static fn (ProductInterface $p): ProductInterface => new LettuceTopping($p),
            static fn (ProductInterface $p): ProductInterface => new PepperTopping($p),
            static fn (ProductInterface $p): ProductInterface => new OnionTopping($p),
            static fn (ProductInterface $p): ProductInterface => new SauceTopping($p, 'острый соус'),
        ];
        $customBurger = $this->recipeApplier->apply(
            $this->productFactories['burger']->createProduct(),
            $customChoice,
        );
        $out .= sprintf(
            "Свой набор:   %s — %.2f ₽ (%d мин)\n",
            $customBurger->name(),
            $customBurger->basePrice(),
            $customBurger->baseTimeMinutes(),
        );

        $out .= "\n3. Прокси\n";
        foreach ([$recipeBurger, $customBurger, $this->productFactories['hotdog']->createProduct()] as $toCook) {
            $result = $this->cook->cook($toCook);
            $out .= $result->isServed()
                ? sprintf("Готово: клиенту выдан «%s»\n", $result->product?->name())
                : sprintf("Отказ: %s\n", $result->reason);
        }
        foreach ($this->disposal->messages() as $message) {
            $out .= '  ' . $message . "\n";
        }



        return Response::text($out, 200, ['Content-Type' => 'text/plain; charset=utf-8']);
    }
}
