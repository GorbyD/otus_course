<?php

namespace FastFood\Decorator;

use FastFood\Product\ProductInterface;

final class ToppingFactory implements ToppingFactoryInterface
{
    public function getTopping(string $type, ProductInterface $product, array $paramBag = []): ProductInterface
    {
        return match ($type) {
            self::LETTUCE => new LettuceTopping($product),
            self::ONION => new OnionTopping($product),
            self::PEPPER => new PepperTopping($product),
            self::SAUCE => new SauceTopping($product, $paramBag['name'] ?? 'соус'),
            default => throw new \InvalidArgumentException("Неизвестный тип топпинга: {$type}"),
        };
    }
}
