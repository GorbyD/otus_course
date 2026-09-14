<?php

namespace Controller;

use FastFood\Cooking\CookInterface;
use FastFood\Cooking\DisposalLogInterface;
use FastFood\Decorator\RecipeApplier;
use FastFood\Decorator\ToppingChoice;
use FastFood\Decorator\ToppingFactoryInterface;
use FastFood\Factory\ProductFactoryInterface;
use FastFood\Kitchen\ClockInterface;
use FastFood\Kitchen\TicketFactoryInterface;
use FastFood\Order\OrderItemFactoryInterface;
use Http\Request;
use Http\Response;

/**
 * Демо для паттернов
 */
final class FastFoodDemoController
{
    /**
     * @param array<string, ProductFactoryInterface> $productFactories
     */
    public function __construct(
        private readonly array $productFactories,
        private readonly RecipeApplier $recipeApplier,
        private readonly CookInterface $cook,
        private readonly DisposalLogInterface $disposalLog,
        private readonly OrderItemFactoryInterface $orderItemFactory,
        private readonly TicketFactoryInterface $ticketFactory,
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

        $classicRecipe = [
            new ToppingChoice(ToppingFactoryInterface::ONION),
            new ToppingChoice(ToppingFactoryInterface::SAUCE, ['name' => 'сырный соус']),
        ];
        $recipeBurger = $this->recipeApplier->apply(
            $this->productFactories['burger']->createProduct(),
            $classicRecipe,
        );
        $out .= sprintf(
            "Фикс. рецепт: %s — %.2f ₽ (%d мин)\n",
            $recipeBurger->name(),
            $recipeBurger->basePrice(),
            $recipeBurger->baseTimeMinutes(),
        );

        $customRecipe = [
            new ToppingChoice(ToppingFactoryInterface::LETTUCE),
            new ToppingChoice(ToppingFactoryInterface::PEPPER),
            new ToppingChoice(ToppingFactoryInterface::ONION),
            new ToppingChoice(ToppingFactoryInterface::SAUCE, ['name' => 'острый соус']),
        ];
        $customBurger = $this->recipeApplier->apply(
            $this->productFactories['burger']->createProduct(),
            $customRecipe,
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
        foreach ($this->disposalLog->messages() as $message) {
            $out .= '  ' . $message . "\n";
        }

        $out .= "\n4. Компоновщик\n";
        $sandwich = $this->orderItemFactory->single($this->productFactories['sandwich']->createProduct());
        $hotDog = $this->orderItemFactory->single($this->productFactories['hotdog']->createProduct());
        $burgerItem = $this->orderItemFactory->single($recipeBurger);

        $combo = $this->orderItemFactory->combo('Комбо №1', [$burgerItem, $hotDog], 20.0);
        $wholeOrder = $this->orderItemFactory->combo('Весь заказ', [$combo, $sandwich]);
        $out .= $wholeOrder->printReceipt();

        $out .= "\n5. Итератор\n";
        $queue = $this->ticketFactory->createQueue();
        $now = $this->clock->now();
        $queue->add($this->ticketFactory->createTicket(1, $sandwich, false, $now->modify('-2 minutes')));
        $queue->add($this->ticketFactory->createTicket(2, $combo, false, $now->modify('-10 minutes')));
        $queue->add($this->ticketFactory->createTicket(3, $wholeOrder, true, $now->modify('-1 minute')));

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
