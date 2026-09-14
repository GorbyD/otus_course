<?php

use Controller\FastFoodDemoController;
use Controller\HealthcheckController;
use FastFood\Cooking\CollectingDisposal;
use FastFood\Cooking\Cook;
use FastFood\Cooking\InMemoryIngredientStock;
use FastFood\Cooking\MaxPrepTimeQualityStandard;
use FastFood\Cooking\QualityControlCookProxy;
use FastFood\Decorator\RecipeApplier;
use FastFood\Decorator\ToppingFactory;
use FastFood\Factory\BurgerFactory;
use FastFood\Factory\HotDogFactory;
use FastFood\Factory\SandwichFactory;
use FastFood\Kitchen\SystemClock;
use FastFood\Kitchen\TicketFactory;
use FastFood\Order\OrderItemFactory;
use Health\MemcachedHealthCheck;
use Health\PostgresHealthCheck;
use Health\RedisHealthCheck;
use Http\Request;
use Http\Response;
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

        $router->get('/fastfood_pattern', function (Request $r): Response {
            $disposal = new CollectingDisposal();
            $clock = new SystemClock();

            return (new FastFoodDemoController(
                productFactories: [
                    'burger' => new BurgerFactory(),
                    'sandwich' => new SandwichFactory(),
                    'hotdog' => new HotDogFactory(),
                ],
                recipeApplier: new RecipeApplier(new ToppingFactory()),
                cook: new QualityControlCookProxy(
                    realCook: new Cook(),
                    stock: new InMemoryIngredientStock(outOfStockProductNames: ['Хот-дог']),
                    qualityStandard: new MaxPrepTimeQualityStandard(maxMinutes: 8),
                    disposal: $disposal,
                ),
                disposalLog: $disposal,
                orderItemFactory: new OrderItemFactory(),
                ticketFactory: new TicketFactory($clock),
                clock: $clock,
            ))->handle($r);
        });

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
