<?php

namespace FastFood\Decorator;

final class OnionTopping extends ToppingDecorator
{
    protected function toppingName(): string
    {
        return 'лук';
    }

    protected function toppingPrice(): float
    {
        return 8.0;
    }

    protected function toppingTimeMinutes(): int
    {
        return 1;
    }
}
