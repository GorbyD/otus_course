<?php

$checks = [];

try {
    $pdo = new PDO(
        sprintf('pgsql:host=%s;dbname=%s', getenv('PG_HOST'), getenv('PG_DB')),
        getenv('PG_USER'),
        getenv('PG_PASSWORD')
    );
    $checks['postgres'] = 'OK';
} catch (Throwable $e) {
    $checks['postgres'] = 'ERROR: ' . $e->getMessage();
}

try {
    $redis = new Redis();
    $redis->connect(getenv('REDIS_HOST'), (int) getenv('REDIS_PORT'));
    $redis->ping();
    $checks['redis'] = 'OK';
} catch (Throwable $e) {
    $checks['redis'] = 'ERROR: ' . $e->getMessage();
}

try {
    $mc = new Memcached();
    $mc->addServer(getenv('MEMCACHED_HOST'), (int) getenv('MEMCACHED_PORT'));
    $mc->set('ping', 'pong');
    $checks['memcached'] = $mc->get('ping') === 'pong' ? 'OK' : 'ERROR: set/get mismatch';
} catch (Throwable $e) {
    $checks['memcached'] = 'ERROR: ' . $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($checks, JSON_PRETTY_PRINT);
