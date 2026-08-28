<?php

namespace Health;

use Db\PdoFactory;

final class PostgresHealthCheck implements HealthCheckInterface
{
    public function name(): string
    {
        return 'postgres';
    }

    public function check(): string
    {
        try {
            PdoFactory::createFromEnv();
            return 'OK';
        } catch (\Throwable $e) {
            return 'ERROR: ' . $e->getMessage();
        }
    }
}
