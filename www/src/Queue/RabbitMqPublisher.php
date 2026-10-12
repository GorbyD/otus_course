<?php

namespace Queue;

use PhpAmqpLib\Message\AMQPMessage;

final class RabbitMqPublisher implements MessagePublisherInterface
{
    public function __construct(
        private readonly string $queue = RabbitMqConnectionFactory::QUEUE_STATEMENTS,
    ) {
    }

    public function publish(array $payload): void
    {
        try {
            $connection = RabbitMqConnectionFactory::createFromEnv();
            $channel = $connection->channel();
            $channel->queue_declare($this->queue, false, true, false, false);

            $channel->basic_publish(
                new AMQPMessage(
                    json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                    [
                        'content_type' => 'application/json',
                        'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                    ],
                ),
                '',
                $this->queue,
            );

            $channel->close();
            $connection->close();
        } catch (\Throwable $e) {
            throw new QueueException('Не удалось поставить сообщение в очередь: ' . $e->getMessage(), 0, $e);
        }
    }
}
