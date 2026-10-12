<?php

namespace Queue;

use PhpAmqpLib\Connection\AMQPStreamConnection;

final class RabbitMqConnectionFactory
{
    public const QUEUE_STATEMENTS = 'statement_requests';

    public static function createFromEnv(): AMQPStreamConnection
    {
        return new AMQPStreamConnection(
            getenv('RABBITMQ_HOST') ?: 'localhost',
            (int) (getenv('RABBITMQ_PORT') ?: 5672),
            getenv('RABBITMQ_USER'),
            getenv('RABBITMQ_PASSWORD'),
        );
    }
}
