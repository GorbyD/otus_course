<?php

namespace FastFood\Factory;

use FastFood\Packaging\BurgerBox;
use FastFood\Packaging\PackagingInterface;
use FastFood\Product\Burger;
use FastFood\Product\ProductInterface;


final class BurgerFactory implements ProductFactoryInterface
{
    public function createProduct(): ProductInterface
    {
        return new Burger();
    }

    public function createPackaging(): PackagingInterface
    {
        return new BurgerBox();
    }
}
