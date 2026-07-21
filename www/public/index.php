<?php

require_once dirname(__DIR__) . '/src/BracketValidator.php';
require_once dirname(__DIR__) . '/src/Session.php';
require_once dirname(__DIR__) . '/src/App.php';

header('Content-Type: text/plain; charset=utf-8');

$app = new App();
echo $app->run();
