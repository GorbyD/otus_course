<?php

namespace Events;

final class Event
{
    /**
     * @param array<string, string> $conditions
     * @param array<string, mixed> $payload
     */
    public function __construct(
        public readonly ?int $id,
        public readonly int $priority,
        public readonly array $conditions,
        public readonly array $payload,
    ) {
    }

    public function withId(int $id): self
    {
        return new self($id, $this->priority, $this->conditions, $this->payload);
    }

    /**
     * @param array<string, string> $params
     */
    public function matches(array $params): bool
    {
        foreach ($this->conditions as $param => $value) {
            if (!array_key_exists($param, $params) || (string) $params[$param] !== (string) $value) {
                return false;
            }
        }

        return true;
    }
}
