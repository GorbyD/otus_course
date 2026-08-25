<?php

use Controller\HealthcheckController;
use Health\MemcachedHealthCheck;
use Health\PostgresHealthCheck;
use Health\RedisHealthCheck;
use Http\Request;
use Http\Router;

/**
 * Composition root: собирает маршруты и зависимости контроллеров,
 * передаёт HTTP-запрос в роутер и материализует Response в реальный ответ.
 */
class App
{
    public function run(): string
    {
        Session::start();

        $request = Request::fromGlobals();
        $response = $this->router()->dispatch($request);

        http_response_code($response->status);
        foreach ($response->headers as $name => $value) {
            header("{$name}: {$value}");
        }

        return $response->body;
    }

    private function router(): Router
    {
        $router = new Router();

        $router->get('/healthcheck', fn (Request $r) => (new HealthcheckController([
            new PostgresHealthCheck(),
            new RedisHealthCheck(),
            new MemcachedHealthCheck(),
        ]))->handle($r));

        // TODO реализовать остальные методы:
        // /bracket - post
        // /checkemail - post
        // /whoami - get
        // /mergelist - post
        // /events - post
        // /events - delete
        // /events/match - get
        // /movies - get
        // /movies/sjow - get
        // /movies/genres - get






        return $router;
    }
}
