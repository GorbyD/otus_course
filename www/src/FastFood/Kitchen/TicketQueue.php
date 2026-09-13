<?php

namespace FastFood\Kitchen;


final class TicketQueue implements TicketQueueInterface
{
    /** @var Ticket[] */
    private array $tickets = [];

    public function __construct(
        private readonly ClockInterface $clock,
    ) {
    }

    public function add(Ticket $ticket): void
    {
        $this->tickets[] = $ticket;
    }

    public function getIterator(): \Iterator
    {
        return new PriorityTicketIterator($this->tickets, $this->clock);
    }
}
