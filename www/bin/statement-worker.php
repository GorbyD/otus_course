#!/usr/bin/env php
<?php

require __DIR__ . '/../vendor/autoload.php';

use PhpAmqpLib\Message\AMQPMessage;
use Queue\RabbitMqConnectionFactory;
use Statement\StatementGenerator;
use Statement\StatementMailer;
use Statement\StatementRequest;

$queue = RabbitMqConnectionFactory::QUEUE_STATEMENTS;
$delay = (int) (getenv('STATEMENT_PROCESSING_DELAY') ?: 5);

$generator = new StatementGenerator();
$mailer = StatementMailer::createFromEnv();

$connection = RabbitMqConnectionFactory::createFromEnv();
$channel = $connection->channel();
$channel->queue_declare($queue, false, true, false, false);
$channel->basic_qos(null, 1, null);

$handler = function (AMQPMessage $message) use ($generator, $mailer, $delay): void {
    try {
        $data = json_decode($message->getBody(), true, flags: JSON_THROW_ON_ERROR);
        echo 'Получен запрос: ' . $message->getBody() . PHP_EOL;

        $request = StatementRequest::fromArray($data);

        sleep($delay);
        $mailer->send($request, $generator->generateCsv($request));

        echo "Запрос {$request->id} обработан, письмо отправлено на {$request->email}" . PHP_EOL;
        $message->ack();
    } catch (Throwable $e) {
        echo "Ошибка обработки: " . $e->getMessage() . PHP_EOL;
        $message->nack();
    }
};

$channel->basic_consume($queue, '', false, false, false, false, $handler);
echo "Ожидаю сообщения из очереди '{$queue}'..." . PHP_EOL;

while ($channel->is_consuming()) {
    $channel->wait();
}
