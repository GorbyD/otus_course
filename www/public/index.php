<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

header('Content-Type: text/plain; charset=utf-8');

$app = new App();
echo $app->run();
