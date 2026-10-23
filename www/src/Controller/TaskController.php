<?php

namespace Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Queue\MessagePublisherInterface;
use Queue\QueueException;
use Task\TaskProcessor;
use Task\TaskRepositoryInterface;

final class TaskController
{
    public function __construct(
        private readonly TaskRepositoryInterface $repository,
        private readonly MessagePublisherInterface $publisher,
    ) {
    }

    public function create(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $body = $request->getParsedBody();
        if (!is_array($body)) {
            return $this->json($response, ['error' => 'Тело запроса должно быть JSON-объектом.'], 400);
        }

        $type = (string) ($body['type'] ?? '');
        $payload = $body['payload'] ?? [];

        if (!in_array($type, TaskProcessor::TYPES, true)) {
            return $this->json($response, [
                'error' => "'type' обязательно и должно быть: " . implode(', ', TaskProcessor::TYPES) . '.',
            ], 400);
        }

        if (!is_array($payload)) {
            return $this->json($response, ["error" => "'payload' должно быть объектом."], 400);
        }

        $task = $this->repository->create($type, $payload);

        try {
            $this->publisher->publish(['id' => $task->id, 'type' => $type, 'payload' => $payload]);
        } catch (QueueException $e) {
            $this->repository->markFailed($task->id, 'Не удалось поставить задачу в очередь: ' . $e->getMessage());

            return $this->json($response, ['error' => 'Сервис недоступен, попробуйте позже.'], 503);
        }

        return $this->json($response, ['id' => $task->id, 'status' => $task->status->value], 202);
    }

    public function show(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (int) ($args['id'] ?? 0);
        $task = $id > 0 ? $this->repository->find($id) : null;

        if ($task === null) {
            return $this->json($response, ['error' => "Задача {$id} не найдена."], 404);
        }

        return $this->json($response, $task->toArray());
    }

    private function json(ResponseInterface $response, array $data, int $status = 200): ResponseInterface
    {
        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

        return $response->withHeader('Content-Type', 'application/json; charset=utf-8')->withStatus($status);
    }
}
