<?php

namespace FastFood\Decorator;

final class LettuceTopping extends ToppingDecorator
{
    protected function toppingName(): string
    {
        return 'салат';
    }

    protected function toppingPrice(): float
    {
        return 10.0;
    }

    protected function toppingTimeMinutes(): int
    {
        return 1;
    }
}
