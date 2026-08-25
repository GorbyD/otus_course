<?php

namespace Db;

final class PdoFactory
{
    public static function createFromEnv(): \PDO
    {
        $host = getenv('PG_HOST') ?: 'localhost';
        $db = getenv('PG_DB') ?: '';
        $user = getenv('PG_USER') ?: '';
        $password = getenv('PG_PASSWORD') ?: '';

        return new \PDO(
            sprintf('pgsql:host=%s;dbname=%s', $host, $db),
            $user,
            $password,
            [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            ]
        );
    }
}
