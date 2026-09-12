<?php

namespace FastFood\Factory;

use FastFood\Packaging\PackagingInterface;
use FastFood\Product\ProductInterface;

/**
 * Создание продукта и подходящей упаковки
 */
interface ProductFactoryInterface
{
    public function createProduct(): ProductInterface;

    public function createPackaging(): PackagingInterface;
}
