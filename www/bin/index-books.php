#!/usr/bin/env php
<?php

require __DIR__ . '/../vendor/autoload.php';

use Search\ElasticsearchBookSearchRepository;
use Search\EsClientFactory;

$options = getopt('', ['recreate']);
$args = array_values(
        array_filter($argv, fn($v, $i) => $i > 0 && $v[0] !== '-', ARRAY_FILTER_USE_BOTH)
);
$file = $args[0] ?? null;

if ($file === null || !is_file($file)) {
    fwrite(STDERR, "Использование: php bin/index-books.php [--recreate] /db/es/<файл>.json \n");
    exit(1);
}

$index = getenv('ES_INDEX');
$repository = new ElasticsearchBookSearchRepository(EsClientFactory::createFromEnv(), $index);
$repository->createIndex(recreate: isset($options['recreate']));
$result = $repository->bulkIndexFromFile($file);

echo "Проиндексировано документов: {$result['indexed']}\n";
if ($result['errors'] !== []) {
    echo 'Ошибок: ' . count($result['errors']) . "\n";
    foreach (array_slice($result['errors'], 0, 5) as $error) {
        echo '  - ' . json_encode($error, JSON_UNESCAPED_UNICODE) . "\n";
    }
}
