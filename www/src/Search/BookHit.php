<?php

namespace Search;

final class BookHit
{
    public function __construct(
        public readonly string $sku,
        public readonly string $title,
        public readonly string $category,
        public readonly int $price,
        public readonly int $totalStock,
        public readonly float $score,
    ) {
    }
}
