<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

use Controller\TaskController;
use Events\RedisClientFactory;
use Queue\RabbitMqConnectionFactory;
use Queue\RabbitMqPublisher;
use Slim\Factory\AppFactory;
use Task\RedisTaskRepository;

$app = AppFactory::create();
$app->addBodyParsingMiddleware();
$app->addErrorMiddleware(false, true, true);

$taskController = new TaskController(
    new RedisTaskRepository(RedisClientFactory::createFromEnv()),
    new RabbitMqPublisher(RabbitMqConnectionFactory::QUEUE_TASKS),
);

$app->post('/tasks', [$taskController, 'create']);
$app->get('/tasks/{id}', [$taskController, 'show']);

$app->run();
