<?php

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

        $lines = preg_split('/\r\n|\r|\n/', $raw);
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
}
