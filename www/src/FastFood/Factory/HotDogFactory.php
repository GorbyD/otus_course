<?php

namespace FastFood\Factory;

use FastFood\Packaging\HotDogSleeve;
use FastFood\Packaging\PackagingInterface;
use FastFood\Product\HotDog;
use FastFood\Product\ProductInterface;

final class HotDogFactory implements ProductFactoryInterface
{
    public function createProduct(): ProductInterface
    {
        return new HotDog();
    }

    public function createPackaging(): PackagingInterface
    {
        return new HotDogSleeve();
    }
}
