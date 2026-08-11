<?php

namespace Search;

use Elastic\Elasticsearch\Client;

final class ElasticsearchBookSearchRepository
{
    public function __construct(
        private readonly Client $client,
        private readonly string $index,
    ) {
    }


    /**
     * Создаёт индекс с маппингом.
     * Если индекс уже существует и $recreate = true — пересоздаёт его.
     */
    public function createIndex(bool $recreate = false): void
    {
        try {
            $exists = $this->client->indices()->exists(['index' => $this->index])->asBool();

            if ($exists) {
                if (!$recreate) {
                    return;
                }
                $this->client->indices()->delete(['index' => $this->index]);
            }

            $this->client->indices()->create([
                'index' => $this->index,
                'body' => [
                    'mappings' => [
                        'properties' => [
                            'title' => [
                                'type' => 'text',
                                'analyzer' => 'russian',
                            ],
                            'sku' => ['type' => 'keyword'],
                            'category' => ['type' => 'keyword'],
                            'price' => ['type' => 'integer'],
                            'stock' => [
                                'type' => 'nested',
                                'properties' => [
                                    'shop' => ['type' => 'keyword'],
                                    'stock' => ['type' => 'integer'],
                                ],
                            ],
                        ],
                    ],
                ],
            ]);
        } catch (\Throwable $e) {
            throw new SearchException(
                "Не удалось создать индекс '{$this->index}' в Elasticsearch: {$e->getMessage()}",
                previous: $e,
            );
        }
    }

    /**
     * @return array{indexed: int, errors: array}
     */
    public function bulkIndexFromFile(string $path): array
    {
        $handle = fopen($path, 'r');
        if ($handle === false) {
            throw new \RuntimeException("Не удалось открыть файл: {$path}");
        }

        $indexed = 0;
        $errors = [];
        $body = [];

        try {
            while (($line = fgets($handle)) !== false) {
                $line = trim($line);
                if ($line === '') {
                    continue;
                }

                $row = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
                $body[] = $row;

                // каждый документ — это пара строк (action + source),
                // отправляем пачками по 1000 документов (2000 строк body)
                if (count($body) >= 2000) {
                    [$indexed, $errors] = $this->flushBulk($body, $indexed, $errors);
                    $body = [];
                }
            }

            if ($body !== []) {
                [$indexed, $errors] = $this->flushBulk($body, $indexed, $errors);
            }
        } finally {
            fclose($handle);
        }

        return ['indexed' => $indexed, 'errors' => $errors];
    }

    private function flushBulk(array $body, int $indexed, array $errors): array
    {
        try {
            $response = $this->client->bulk(['index' => $this->index, 'body' => $body])->asArray();
        } catch (\Throwable $e) {
            throw new SearchException(
                "Ошибка при загрузке данных в Elasticsearch: {$e->getMessage()}",
                previous: $e,
            );
        }

        foreach ($response['items'] ?? [] as $item) {
            $action = $item['create'] ?? $item['index'] ?? null;
            if ($action === null) {
                continue;
            }
            if (isset($action['error'])) {
                $errors[] = $action['error'];
            } else {
                $indexed++;
            }
        }

        return [$indexed, $errors];
    }


    /**
     * @return BookHit[]
     */
    public function search(SearchCriteria $criteria): array
    {
        $must = [];
        $filter = [];

        if ($criteria->query !== null && $criteria->query !== '') {
            $must[] = [
                'match' => [
                    'title' => [
                        'query' => $criteria->query,
                        'fuzziness' => 'AUTO',
                        'operator' => 'or',
                    ],
                ],
            ];
        } else {
            $must[] = ['match_all' => new \stdClass()];
        }

        if ($criteria->category !== null && $criteria->category !== '') {
            $filter[] = [
                'term' => [
                    'category' => $criteria->category,
                ],
            ];
        }

        if ($criteria->maxPrice !== null) {
            $filter[] = [
                'range' => [
                    'price' => ['lte' => $criteria->maxPrice],
                ],
            ];
        }

        if ($criteria->inStockOnly) {
            $filter[] = [
                'nested' => [
                    'path' => 'stock',
                    'query' => [
                        'range' => ['stock.stock' => ['gt' => 0]],
                    ],
                ],
            ];
        }

        try {
            $response = $this->client->search([
                'index' => $this->index,
                'body' => [
                    'size' => $criteria->limit,
                    'query' => [
                        'bool' => [
                            'must' => $must,
                            'filter' => $filter,
                        ],
                    ],
                ],
            ])->asArray();
        } catch (\Throwable $e) {
            throw new SearchException(
                "Ошибка при выполнении поиска в Elasticsearch: {$e->getMessage()}",
                previous: $e,
            );
        }

        $hits = [];
        foreach ($response['hits']['hits'] ?? [] as $hit) {
            $source = $hit['_source'];
            $totalStock = array_sum(array_column($source['stock'] ?? [], 'stock'));

            $hits[] = new BookHit(
                sku: $source['sku'],
                title: $source['title'],
                category: $source['category'],
                price: (int) $source['price'],
                totalStock: (int) $totalStock,
                score: (float) ($hit['_score'] ?? 0.0),
            );
        }

        return $hits;
    }
}
