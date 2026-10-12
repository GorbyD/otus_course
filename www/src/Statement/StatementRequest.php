<?php

namespace Statement;

final class StatementRequest
{
    public function __construct(
        public readonly int $id,
        public readonly string $email,
        public readonly string $dateFrom,
        public readonly string $dateTo,
    ) {
    }

    public static function create(string $email, string $dateFrom, string $dateTo): self
    {
        return new self(
            rand(1, 999999999),
            $email,
            $dateFrom,
            $dateTo,
        );
    }

    public static function fromArray(array $data): self
    {
        return new self(
            (int) $data['id'],
            (string) $data['email'],
            (string) $data['date_from'],
            (string) $data['date_to'],
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
        ];
    }
}
