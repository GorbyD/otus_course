<?php

namespace FastFood\Factory;

use FastFood\Packaging\PackagingInterface;
use FastFood\Packaging\SandwichWrap;
use FastFood\Product\ProductInterface;
use FastFood\Product\Sandwich;

final class SandwichFactory implements ProductFactoryInterface
{
    public function createProduct(): ProductInterface
    {
        return new Sandwich();
    }

    public function createPackaging(): PackagingInterface
    {
        return new SandwichWrap();
    }
}
