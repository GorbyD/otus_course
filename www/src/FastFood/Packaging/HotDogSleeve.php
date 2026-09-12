<?php

namespace FastFood\Packaging;

final class HotDogSleeve implements PackagingInterface
{
    public function name(): string
    {
        return 'бумажный рукав для хот-дога';
    }
}
