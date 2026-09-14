<?php

namespace FastFood\Kitchen;

use FastFood\Order\OrderItemInterface;

final class TicketFactory implements TicketFactoryInterface
{
    public function __construct(
        private readonly ClockInterface $clock,
    ) {
    }

    public function createQueue(): TicketQueueInterface
    {
        return new TicketQueue($this->clock);
    }

    public function createTicket(int $id, OrderItemInterface $item, bool $urgent, \DateTimeImmutable $queuedAt): Ticket
    {
        return new Ticket($id, $item, $urgent, $queuedAt);
    }
}
