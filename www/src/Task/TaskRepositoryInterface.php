<?php

namespace Task;

interface TaskRepositoryInterface
{
    public function create(string $type, array $payload): Task;

    public function find(int $id): ?Task;

    public function markProcessing(int $id): void;

    public function markCompleted(int $id, array $result): void;

    public function markFailed(int $id, string $error): void;
}
