<?php

namespace Health;

use Cache\MemcachedClientFactory;

final class MemcachedHealthCheck implements HealthCheckInterface
{
    public function name(): string
    {
        return 'memcached';
    }

    public function check(): string
    {
        try {
            $mc = MemcachedClientFactory::createFromEnv();
            $mc->set('ping', 'pong');
            return $mc->get('ping') === 'pong' ? 'OK' : 'ERROR: set/get mismatch';
        } catch (\Throwable $e) {
            return 'ERROR: ' . $e->getMessage();
        }
    }
}
