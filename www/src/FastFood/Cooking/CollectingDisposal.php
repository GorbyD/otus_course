<?php

namespace FastFood\Cooking;

use FastFood\Product\ProductInterface;

final class CollectingDisposal implements DisposalInterface
{
    /** @var string[] */
    private array $messages = [];

    public function dispose(ProductInterface $product, string $reason): void
    {
        $this->messages[] = sprintf('[утиль] «%s»: %s', $product->name(), $reason);
    }

    /** @return string[] */
    public function messages(): array
    {
        return $this->messages;
    }
}
