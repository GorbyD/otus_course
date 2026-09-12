<?php

namespace FastFood\Packaging;

final class SandwichWrap implements PackagingInterface
{
    public function name(): string
    {
        return 'обёртка для сэндвича';
    }
}
