<?php

namespace Search;

use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;

final class EsClientFactory
{
    public static function createFromEnv(): Client
    {
        $host = getenv('ES_HOST') ?: 'localhost';
        $port = getenv('ES_PORT') ?: '9200';

        return ClientBuilder::create()
            ->setHosts(["http://{$host}:{$port}"])
            ->build();
    }
}
