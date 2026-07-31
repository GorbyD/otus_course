#!/usr/bin/env php
<?php

require __DIR__ . '/../vendor/autoload.php';

use Search\ElasticsearchBookSearchRepository;
use Search\EsClientFactory;
use Search\SearchCriteria;

$options = getopt('', [
    'query:',
    'category:',
    'max-price:',
    'in-stock',
    'limit:',
]);

$criteria = new SearchCriteria(
    query: $options['query'] ?? null,
    category: $options['category'] ?? null,
    maxPrice: isset($options['max-price']) ? (int) $options['max-price'] : null,
    inStockOnly: isset($options['in-stock']),
    limit: isset($options['limit']) ? (int) $options['limit'] : 20,
);

$index = getenv('ES_INDEX');
$repository = new ElasticsearchBookSearchRepository(EsClientFactory::createFromEnv(), $index);
$hits = $repository->search($criteria);

if ($hits === []) {
    echo "Ничего не найдено.\n";
    exit(0);
}

$rows = array_map(static fn($hit) => [
    $hit->sku,
    $hit->title,
    $hit->category,
    (string) $hit->price,
    (string) $hit->totalStock,
    number_format($hit->score, 2),
], $hits);

$headers = ['SKU', 'Название', 'Категория', 'Цена', 'Остаток', 'Score'];
printTable($headers, $rows);

function printTable(array $headers, array $rows): void
{
    echo '| ' . implode(' | ', $headers) . " |\n";
    foreach ($rows as $row) {
        echo '| ' . implode(' | ', $row) . " |\n";
    }
}
