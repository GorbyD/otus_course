<?php

namespace FastFood\Kitchen;

/**
 * Очередь заказов (тикетов)
 */
interface TicketQueueInterface extends \IteratorAggregate
{
    public function add(Ticket $ticket): void;
}
