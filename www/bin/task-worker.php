#!/usr/bin/env php
<?php

require __DIR__ . '/../vendor/autoload.php';

use Events\RedisClientFactory;
use PhpAmqpLib\Message\AMQPMessage;
use Queue\RabbitMqConnectionFactory;
use Task\RedisTaskRepository;
use Task\TaskProcessor;

const PROCESSING_DELAY_SECONDS = 5;

$queue = RabbitMqConnectionFactory::QUEUE_TASKS;

$repository = new RedisTaskRepository(RedisClientFactory::createFromEnv());
$processor = new TaskProcessor();

$connection = RabbitMqConnectionFactory::createFromEnv();
$channel = $connection->channel();
$channel->queue_declare($queue, false, true, false, false);
$channel->basic_qos(null, 1, null);

$handler = function (AMQPMessage $message) use ($repository, $processor): void {
    try {
        $data = json_decode($message->getBody(), true, flags: JSON_THROW_ON_ERROR);
        $id = (int) $data['id'];
        echo "Получена задача #{$id}: " . $message->getBody() . PHP_EOL;

        $repository->markProcessing($id);
        sleep(PROCESSING_DELAY_SECONDS);

        $result = $processor->process((string) $data['type'], (array) $data['payload']);

        $repository->markCompleted($id, $result);
        echo "Задача #{$id} выполнена." . PHP_EOL;
    } catch (Throwable $e) {
        if (isset($id)) {
            $repository->markFailed($id, $e->getMessage());
        }
        echo 'Ошибка обработки: ' . $e->getMessage() . PHP_EOL;
    } finally {
        $message->ack();
    }
};

$channel->basic_consume($queue, '', false, false, false, false, $handler);
echo "Ожидаю сообщения из очереди '{$queue}'..." . PHP_EOL;

while ($channel->is_consuming()) {
    $channel->wait();
}
