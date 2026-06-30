<?php

ini_set('session.save_handler', 'redis');
ini_set('session.save_path', 'tcp://' . getenv('REDIS_HOST') . ':' . getenv('REDIS_PORT'));

session_start();

$_SESSION['hits'] = ($_SESSION['hits'] ?? 0) + 1;

header('Content-Type: text/plain; charset=utf-8');

echo "=== Обработчик запроса ===\n\n";

printf("php-fpm container : %s\n", gethostname());

echo "\n=== Сессия ===\n\n";
printf("Session ID        : %s\n", session_id());
printf("hist : %d\n", $_SESSION['hits']);