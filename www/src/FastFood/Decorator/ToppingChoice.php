<?php

namespace FastFood\Decorator;

/**
 * Обертка для составления рецепта
 */
final class ToppingChoice
{
    /**
     * @param array<string, mixed> $paramBag
     */
    public function __construct(
        public readonly string $type,
        public readonly array $paramBag = [],
    ) {
    }
}
