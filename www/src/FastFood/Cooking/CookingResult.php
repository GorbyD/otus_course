<?php

namespace FastFood\Cooking;

use FastFood\Product\ProductInterface;

final class CookingResult
{
    public const STATUS_SERVED = 'served';
    public const STATUS_REJECTED = 'rejected';

    private function __construct(
        public readonly string $status,
        public readonly ?ProductInterface $product,
        public readonly ?string $reason,
    ) {
    }

    public static function served(ProductInterface $product): self
    {
        return new self(self::STATUS_SERVED, $product, null);
    }

    public static function rejected(string $reason): self
    {
        return new self(self::STATUS_REJECTED, null, $reason);
    }

    public function isServed(): bool
    {
        return $this->status === self::STATUS_SERVED;
    }
}
