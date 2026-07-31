<?php

namespace Search;

final class SearchCriteria
{
    public function __construct(
        public readonly ?string $query = null,
        public readonly ?string $category = null,
        public readonly ?int $maxPrice = null,
        public readonly bool $inStockOnly = false,
        public readonly int $limit = 20,
    ) {
    }
}
