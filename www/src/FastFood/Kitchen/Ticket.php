<?php

namespace FastFood\Kitchen;

use FastFood\Order\OrderItemInterface;


final class Ticket
{
    public function __construct(
        public readonly int $id,
        public readonly OrderItemInterface $item,
        public readonly bool $urgent,
        public readonly \DateTimeImmutable $queuedAt,
    ) {
    }

    public function waitSeconds(\DateTimeImmutable $now): int
    {
        return $now->getTimestamp() - $this->queuedAt->getTimestamp();
    }
}
