<?php

require_once __DIR__ . '/src/BracketValidator.php';

ini_set('session.save_handler', 'redis');
ini_set('session.save_path', 'tcp://' . getenv('REDIS_HOST') . ':' . getenv('REDIS_PORT'));

session_start();

$_SESSION['requests'] = ($_SESSION['requests'] ?? 0) + 1;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Method Not Allowed. Необходим POST / с параметром 'string'.\n";
    exit;
}

$string = $_POST['string'] ?? null;

if ($string === null) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Bad Request: необходим параметр 'string'.\n";
    exit;
}

$validator = new BracketValidator();

try {
    $validator->validate($string);
    http_response_code(200);
    header('Content-Type: text/plain; charset=utf-8');
    echo "OK: Все в порядке.\n";
} catch (\InvalidArgumentException $e) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Bad Request: ' . $e->getMessage() . "\n";
}
