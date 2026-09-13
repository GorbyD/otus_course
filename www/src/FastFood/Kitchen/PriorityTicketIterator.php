<?php

namespace FastFood\Kitchen;

/**
 * Сортировка - сначала срочные + кто дольше ждёт
 */
final class PriorityTicketIterator implements \Iterator
{
    /** @var Ticket[] */
    private readonly array $sorted;

    private int $position = 0;

    /**
     * @param Ticket[] $tickets
     */
    public function __construct(array $tickets, ClockInterface $clock)
    {
        $now = $clock->now();
        $sorted = $tickets;

        usort($sorted, static function (Ticket $a, Ticket $b) use ($now): int {
            if ($a->urgent !== $b->urgent) {
                return $a->urgent ? -1 : 1;
            }

            return $b->waitSeconds($now) <=> $a->waitSeconds($now);
        });

        $this->sorted = $sorted;
    }

    public function current(): Ticket
    {
        return $this->sorted[$this->position];
    }

    public function key(): int
    {
        return $this->position;
    }

    public function next(): void
    {
        $this->position++;
    }

    public function rewind(): void
    {
        $this->position = 0;
    }

    public function valid(): bool
    {
        return isset($this->sorted[$this->position]);
    }
}
