<?php

namespace Task;

final class RedisTaskRepository implements TaskRepositoryInterface
{
    private const SEQ_KEY = 'tasks:seq';

    public function __construct(
        private readonly \Redis $redis,
    ) {
    }

    public function create(string $type, array $payload): Task
    {
        $id = (int) $this->redis->incr(self::SEQ_KEY);

        $this->redis->hMSet("task:{$id}", [
            'type' => $type,
            'status' => TaskStatus::Pending->value,
            'payload' => json_encode($payload, JSON_THROW_ON_ERROR),
        ]);

        return new Task($id, $type, $payload, TaskStatus::Pending);
    }

    public function find(int $id): ?Task
    {
        $data = $this->redis->hGetAll("task:{$id}");
        if (!is_array($data) || $data === []) {
            return null;
        }

        return new Task(
            $id,
            $data['type'],
            json_decode($data['payload'], true, 512, JSON_THROW_ON_ERROR),
            TaskStatus::from($data['status']),
            isset($data['result']) ? json_decode($data['result'], true, 512, JSON_THROW_ON_ERROR) : null,
            $data['error'] ?? null,
        );
    }

    public function markProcessing(int $id): void
    {
        $this->redis->hSet("task:{$id}", 'status', TaskStatus::Processing->value);
    }

    public function markCompleted(int $id, array $result): void
    {
        $this->redis->hMSet("task:{$id}", [
            'status' => TaskStatus::Completed->value,
            'result' => json_encode($result, JSON_THROW_ON_ERROR),
        ]);
    }

    public function markFailed(int $id, string $error): void
    {
        $this->redis->hMSet("task:{$id}", [
            'status' => TaskStatus::Failed->value,
            'error' => $error,
        ]);
    }
}
