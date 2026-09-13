<?php

namespace Controller;

use FastFood\Cooking\CollectingDisposal;
use FastFood\Cooking\CookInterface;
use FastFood\Decorator\LettuceTopping;
use FastFood\Decorator\OnionTopping;
use FastFood\Decorator\PepperTopping;
use FastFood\Decorator\RecipeApplier;
use FastFood\Decorator\SauceTopping;
use FastFood\Factory\ProductFactoryInterface;
use FastFood\Kitchen\ClockInterface;
use FastFood\Kitchen\Ticket;
use FastFood\Kitchen\TicketQueue;
use FastFood\Order\ComboOrderItem;
use FastFood\Order\SingleOrderItem;
use FastFood\Product\ProductInterface;
use Http\Request;
use Http\Response;

/**
 * Демо для паттернов
 */
final class FastFoodDemoController
{
    /** @param array<string, ProductFactoryInterface> $productFactories */
    public function __construct(
        private readonly array $productFactories,
        private readonly RecipeApplier $recipeApplier,
        private readonly CookInterface $cook,
        private readonly CollectingDisposal $disposal,
        private readonly ClockInterface $clock,
    ) {
    }

    public function handle(Request $request): Response
    {
        $out = "\n1. Абстрактная фабрика\n";
        foreach ($this->productFactories as $key => $factory) {
            $product = $factory->createProduct();
            $packaging = $factory->createPackaging();
            $out .= sprintf(
                "%-8s -> %s, %.2f ₽, %d мин | упаковка: %s\n",
                $key,
                $product->name(),
                $product->basePrice(),
                $product->baseTimeMinutes(),
                $packaging->name(),
            );
        }

        $out .= "\n2. Декоратор\n";

        $cheeseburgerRecipe = [
            static fn (ProductInterface $p): ProductInterface => new OnionTopping($p),
            static fn (ProductInterface $p): ProductInterface => new SauceTopping($p, 'сырный соус'),
        ];
        $recipeBurger = $this->recipeApplier->apply(
            $this->productFactories['burger']->createProduct(),
            $cheeseburgerRecipe,
        );
        $out .= sprintf(
            "Фикс. рецепт: %s — %.2f ₽ (%d мин)\n",
            $recipeBurger->name(),
            $recipeBurger->basePrice(),
            $recipeBurger->baseTimeMinutes(),
        );

        $customChoice = [
            static fn (ProductInterface $p): ProductInterface => new LettuceTopping($p),
            static fn (ProductInterface $p): ProductInterface => new PepperTopping($p),
            static fn (ProductInterface $p): ProductInterface => new OnionTopping($p),
            static fn (ProductInterface $p): ProductInterface => new SauceTopping($p, 'острый соус'),
        ];
        $customBurger = $this->recipeApplier->apply(
            $this->productFactories['burger']->createProduct(),
            $customChoice,
        );
        $out .= sprintf(
            "Свой набор:   %s — %.2f ₽ (%d мин)\n",
            $customBurger->name(),
            $customBurger->basePrice(),
            $customBurger->baseTimeMinutes(),
        );

        $out .= "\n3. Прокси\n";
        foreach ([$recipeBurger, $customBurger, $this->productFactories['hotdog']->createProduct()] as $toCook) {
            $result = $this->cook->cook($toCook);
            $out .= $result->isServed()
                ? sprintf("Готово: клиенту выдан «%s»\n", $result->product?->name())
                : sprintf("Отказ: %s\n", $result->reason);
        }
        foreach ($this->disposal->messages() as $message) {
            $out .= '  ' . $message . "\n";
        }

        $out .= "\n4. Компоновщик\n";
        $sandwich = new SingleOrderItem($this->productFactories['sandwich']->createProduct());
        $hotDog = new SingleOrderItem($this->productFactories['hotdog']->createProduct());
        $burgerItem = new SingleOrderItem($recipeBurger);

        $combo = new ComboOrderItem(name: 'Комбо №1', items: [$burgerItem, $hotDog], discount: 20.0);
        $wholeOrder = new ComboOrderItem(name: 'Весь заказ', items: [$combo, $sandwich]);
        $out .= $wholeOrder->printReceipt();

        $out .= "\n5. Итератор\n";
        $queue = new TicketQueue($this->clock);
        $now = $this->clock->now();
        $queue->add(new Ticket(1, $sandwich, urgent: false, queuedAt: $now->modify('-2 minutes')));
        $queue->add(new Ticket(2, $combo, urgent: false, queuedAt: $now->modify('-10 minutes')));
        $queue->add(new Ticket(3, $wholeOrder, urgent: true, queuedAt: $now->modify('-1 minute')));

        foreach ($queue as $ticket) {
            $out .= sprintf(
                "Тикет #%d%s — %d мин ожидания\n",
                $ticket->id,
                $ticket->urgent ? ' [СРОЧНО]' : '',
                intdiv($ticket->waitSeconds($now), 60),
            );
        }

        return Response::text($out, 200, ['Content-Type' => 'text/plain; charset=utf-8']);
    }
}
