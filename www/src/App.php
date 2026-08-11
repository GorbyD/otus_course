<?php

use Events\Event;
use Events\RedisClientFactory;
use Events\RedisEventRepository;

class App
{
    public function run(): string
    {
        Session::start();

        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

        return match ($path) {
            '/bracket' => $this->bracket(),
            '/checkemail' => $this->email(),
            '/whoami' => $this->whoami(),
            '/healthcheck' => $this->healthcheck(),
            '/mergelist' => $this->mergeList(),
            '/events' => $this->events(),
            '/events/match' => $this->eventsMatch(),
            default => $this->notFound(),
        };
    }

    private function bracket(): string
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            return "Method Not Allowed. Необходим POST / с параметром 'string'.\n";
        }

        $string = $_POST['string'] ?? null;

        if ($string === null) {
            http_response_code(400);
            return "Bad Request: необходим параметр 'string'.\n";
        }

        try {
            (new BracketValidator())->validate($string);
            http_response_code(200);
            return "OK: Все в порядке.\n";
        } catch (\InvalidArgumentException $e) {
            http_response_code(400);
            return 'Bad Request: ' . $e->getMessage() . "\n";
        }
    }

    private function email(): string
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            return "Method Not Allowed. Необходим POST /email с параметром 'emails' (список строк, каждая на новой строке).\n";
        }

        $raw = $_POST['emails'] ?? null;

        if ($raw === null || trim($raw) === '') {
            http_response_code(400);
            return "Bad Request: необходим параметр 'emails' (список строк, каждая на новой строке).\n";
        }

        $lines = preg_split('/[\r\n,;]+/', $raw);
        $results = (new EmailValidator())->validateList($lines);

        if ($results === []) {
            http_response_code(400);
            return "Bad Request: список пуст.\n";
        }

        $output = [];
        foreach ($results as $result) {
            $output[] = $result['valid']
                ? "OK: {$result['email']}"
                : "NOT OK: {$result['email']} ({$result['reason']})";
        }

        http_response_code(200);
        return implode("\n", $output) . "\n";
    }

    private function healthcheck(): string
    {
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
        return json_encode($checks, JSON_PRETTY_PRINT);
    }

    private function whoami(): string
    {
        $_SESSION['hits'] = ($_SESSION['hits'] ?? 0) + 1;

        return implode("\n", [
                '=== Обработчик запроса ===',
                'php-fpm container : ' . gethostname(),
                '',
                '=== Сессия ===',
                'Session ID : ' . session_id(),
                'hits       : ' . $_SESSION['hits'],
            ]) . "\n";
    }

    private function notFound(): string
    {
        http_response_code(404);
        return "Not Found\n";
    }

    private function events(): string
    {
        return match ($_SERVER['REQUEST_METHOD']) {
            'POST' => $this->addEvent(),
            'DELETE' => $this->clearEvents(),
            default => $this->methodNotAllowed('POST, DELETE'),
        };
    }

    /**
     * POST /events
     * Тело запроса (JSON): {"priority": 1000, "conditions": {"param1": "1"}, "event": {...}}
     */
    private function addEvent(): string
    {
        header('Content-Type: application/json; charset=utf-8');

        $data = json_decode((string) file_get_contents('php://input'), true);

        if (!is_array($data) || !isset($data['priority'], $data['conditions'], $data['event']) || !is_array($data['conditions'])) {
            http_response_code(400);
            return json_encode(['error' => "Ожидается JSON вида {priority, conditions, event}"], JSON_UNESCAPED_UNICODE) . "\n";
        }

        $conditions = [];
        foreach ($data['conditions'] as $param => $value) {
            $conditions[(string) $param] = (string) $value;
        }

        $repository = new RedisEventRepository(RedisClientFactory::createFromEnv());
        $event = $repository->add(new Event(
            id: null,
            priority: (int) $data['priority'],
            conditions: $conditions,
            payload: $data['event'],
        ));

        http_response_code(201);
        return json_encode(['id' => $event->id, 'priority' => $event->priority], JSON_UNESCAPED_UNICODE) . "\n";
    }

    /**
     * DELETE /events — очищает всё хранилище событий.
     */
    private function clearEvents(): string
    {
        header('Content-Type: application/json; charset=utf-8');

        $repository = new RedisEventRepository(RedisClientFactory::createFromEnv());
        $repository->clear();

        http_response_code(200);
        return json_encode(['status' => 'cleared'], JSON_UNESCAPED_UNICODE) . "\n";
    }

    /**
     * GET /events/match?param1=1&param2=2
     */
    private function eventsMatch(): string
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            return $this->methodNotAllowed('GET');
        }

        $params = [];
        foreach ($_GET as $param => $value) {
            $params[(string) $param] = (string) $value;
        }

        $repository = new RedisEventRepository(RedisClientFactory::createFromEnv());
        $event = $repository->findBestMatch($params);

        if ($event === null) {
            http_response_code(404);
            return json_encode(['error' => 'Подходящих событий не найдено'], JSON_UNESCAPED_UNICODE) . "\n";
        }

        http_response_code(200);
        return json_encode([
                'id' => $event->id,
                'priority' => $event->priority,
                'event' => $event->payload,
            ], JSON_UNESCAPED_UNICODE) . "\n";
    }

    private function methodNotAllowed(string $allowed): string
    {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(405);
        return json_encode(['error' => "Method Not Allowed. Допустимо: {$allowed}"], JSON_UNESCAPED_UNICODE) . "\n";
    }

    private function mergeList(): string
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            return "Method Not Allowed. Необходим POST /mergelist с параметрами 'list1' и 'list2'.\n";
        }

        $raw1 = $_POST['list1'] ?? null;
        $raw2 = $_POST['list2'] ?? null;

        if ($raw1 === null || $raw2 === null) {
            http_response_code(400);
            return "Bad Request: необходимы параметры 'list1' и 'list2'.\n";
        }

        $parse = static function (string $raw): array {
            $raw = trim($raw);
            if ($raw === '') {
                return [];
            }
            $result = explode(',', $raw);
            $result = array_map(fn($i) => (int)$i, $result);
            return $result;
        };

        $merger = new ListMerger();
        $merged = $merger->mergeTwoLists(
            $merger->fromArray($parse($raw1)),
            $merger->fromArray($parse($raw2))
        );

        http_response_code(200);
        return implode(',', $merger->toArray($merged)) . "\n";
    }
}
