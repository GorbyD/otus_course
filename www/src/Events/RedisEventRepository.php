<?php

namespace Events;

final class RedisEventRepository implements EventRepositoryInterface
{
    private const SEQ_KEY = 'events:seq';
    private const IDS_KEY = 'events:ids';
    private const INDEX_KEYS_KEY = 'events:cond_index_keys';

    public function __construct(
        private readonly \Redis $redis,
    ) {
    }

    public function add(Event $event): Event
    {
        $id = (int) $this->redis->incr(self::SEQ_KEY);
        $stored = $event->withId($id);

        $this->redis->hMSet("event:{$id}", [
            'priority' => $stored->priority,
            'conditions' => json_encode($stored->conditions, JSON_THROW_ON_ERROR),
            'payload' => json_encode($stored->payload, JSON_THROW_ON_ERROR),
        ]);
        $this->redis->sAdd(self::IDS_KEY, (string) $id);

        foreach ($stored->conditions as $param => $value) {
            $indexKey = $this->conditionIndexKey($param, $value);
            $this->redis->sAdd($indexKey, (string) $id);
            $this->redis->sAdd(self::INDEX_KEYS_KEY, $indexKey);
        }

        return $stored;
    }

    public function clear(): void
    {
        $ids = $this->redis->sMembers(self::IDS_KEY);
        $indexKeys = $this->redis->sMembers(self::INDEX_KEYS_KEY);

        $keysToDelete = array_map(static fn($id) => "event:{$id}", $ids);
        $keysToDelete = [...$keysToDelete, ...$indexKeys, self::IDS_KEY, self::INDEX_KEYS_KEY, self::SEQ_KEY];

        if ($keysToDelete !== []) {
            $this->redis->del($keysToDelete);
        }
    }

    public function findBestMatch(array $params): ?Event
    {
        $candidateIds = array_unique($this->collectCandidateIds($params));

        $best = null;
        foreach ($this->loadEvents($candidateIds) as $candidate) {
            if (!$candidate->matches($params)) {
                continue;
            }

            if ($best === null || $candidate->priority > $best->priority) {
                $best = $candidate;
            }
        }

        return $best;
    }

    /**
     * @param array<string, string> $params
     * @return list<string>
     */
    private function collectCandidateIds(array $params): array
    {
        if ($params === []) {
            return [];
        }

        /** @var \Redis $pipe */
        $pipe = $this->redis->multi(\Redis::PIPELINE);
        foreach ($params as $param => $value) {
            $pipe->sMembers($this->conditionIndexKey((string) $param, (string) $value));
        }
        $resultsPerParam = $pipe->exec();

        $candidateIds = [];
        foreach ($resultsPerParam as $idsForOneParam) {
            $candidateIds = [...$candidateIds, ...$idsForOneParam];
        }

        return $candidateIds;
    }

    /**
     * @param list<string> $ids
     * @return list<Event>
     */
    private function loadEvents(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        /** @var \Redis $pipe */
        $pipe = $this->redis->multi(\Redis::PIPELINE);
        foreach ($ids as $id) {
            $pipe->hGetAll("event:{$id}");
        }
        $results = $pipe->exec();

        $events = [];
        foreach ($ids as $i => $id) {
            $data = $results[$i] ?? null;
            if (!is_array($data) || $data === []) {
                continue;
            }

            $events[] = new Event(
                (int) $id,
                (int) $data['priority'],
                json_decode($data['conditions'], true, 512, JSON_THROW_ON_ERROR),
                json_decode($data['payload'], true, 512, JSON_THROW_ON_ERROR),
            );
        }

        return $events;
    }

    private function conditionIndexKey(string $param, string $value): string
    {
        return "cond:{$param}:{$value}";
    }
}
