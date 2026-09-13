<?php

namespace FastFood\Order;

interface OrderItemInterface
{
    public function price(): float;

    public function prepTimeMinutes(): int;

    public function printReceipt(int $indent = 0): string;
}
