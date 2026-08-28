<?php

namespace Health;

use Events\RedisClientFactory;

final class RedisHealthCheck implements HealthCheckInterface
{
    public function name(): string
    {
        return 'redis';
    }

    public function check(): string
    {
        try {
            RedisClientFactory::createFromEnv()->ping();
            return 'OK';
        } catch (\Throwable $e) {
            return 'ERROR: ' . $e->getMessage();
        }
    }
}
