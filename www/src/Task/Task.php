<?php

namespace Task;

final class Task
{
    public function __construct(
        public readonly int $id,
        public readonly string $type,
        public readonly array $payload,
        public readonly TaskStatus $status,
        public readonly ?array $result = null,
        public readonly ?string $error = null,
    ) {
    }

    public function toArray(): array
    {
        return array_filter(
            [
                'id' => $this->id,
                'type' => $this->type,
                'status' => $this->status->value,
                'result' => $this->result,
                'error' => $this->error,
            ],
            static fn ($value) => $value !== null,
        );
    }
}
