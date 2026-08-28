<?php

namespace Cache;

final class MemcachedClientFactory
{
    public static function createFromEnv(): \Memcached
    {
        $host = getenv('MEMCACHED_HOST') ?: 'localhost';
        $port = (int) (getenv('MEMCACHED_PORT') ?: 11211);

        $memcached = new \Memcached();
        $memcached->addServer($host, $port);

        return $memcached;
    }
}
