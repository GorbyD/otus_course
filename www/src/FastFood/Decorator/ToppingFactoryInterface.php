<?php

namespace FastFood\Decorator;

use FastFood\Product\ProductInterface;

interface ToppingFactoryInterface
{
    public const LETTUCE = 'lettuce';
    public const ONION = 'onion';
    public const PEPPER = 'pepper';
    public const SAUCE = 'sauce';

    /**
     * @param array<string, mixed> $paramBag
     */
    public function getTopping(string $type, ProductInterface $product, array $paramBag = []): ProductInterface;
}
