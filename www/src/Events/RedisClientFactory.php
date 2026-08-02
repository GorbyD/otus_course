<?php

namespace Events;

final class RedisClientFactory
{
    public static function createFromEnv(): \Redis
    {
        $host = getenv('REDIS_HOST') ?: 'localhost';
        $port = (int) (getenv('REDIS_PORT') ?: 6379);

        $redis = new \Redis();
        $redis->connect($host, $port);

        return $redis;
    }
}
