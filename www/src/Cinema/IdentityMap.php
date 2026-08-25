<?php

namespace Cinema;

final class IdentityMap
{
    /** @var array<int, object> */
    private array $map = [];

    public function get(int $id): ?object
    {
        return $this->map[$id] ?? null;
    }

    public function set(int $id, object $entity): void
    {
        $this->map[$id] = $entity;
    }
}
