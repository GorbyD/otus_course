<?php

namespace FastFood\Kitchen;

use FastFood\Order\OrderItemInterface;

interface TicketFactoryInterface
{
    public function createQueue(): TicketQueueInterface;

    public function createTicket(int $id, OrderItemInterface $item, bool $urgent, \DateTimeImmutable $queuedAt): Ticket;
}
