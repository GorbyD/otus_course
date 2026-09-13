<?php

namespace FastFood\Cooking;

use FastFood\Product\ProductInterface;


interface DisposalInterface
{
    public function dispose(ProductInterface $product, string $reason): void;
}
