<?php

namespace FastFood\Packaging;

final class BurgerBox implements PackagingInterface
{
    public function name(): string
    {
        return 'коробка для бургера';
    }
}
