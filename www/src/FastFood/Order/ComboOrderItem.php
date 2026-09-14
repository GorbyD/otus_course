<?php

namespace FastFood\Order;

final class ComboOrderItem implements OrderItemInterface
{
    /**
     * @param OrderItemInterface[] $items
     */
    public function __construct(
        private readonly string $name,
        private readonly array $items,
        private readonly float $discount = 0.0,
    ) {
    }

    public function price(): float
    {
        $total = 0.0;
        foreach ($this->items as $item) {
            $total += $item->price();
        }

        return max(0.0, $total - $this->discount);
    }

    public function prepTimeMinutes(): int
    {
        $total = 0;
        foreach ($this->items as $item) {
            $total += $item->prepTimeMinutes();
        }

        return $total;
    }

    public function printReceipt(int $indent = 0): string
    {
        $pad = str_repeat('  ', $indent);
        $receipt = $pad . sprintf('%s (комбо):', $this->name) . "\n";

        foreach ($this->items as $item) {
            $receipt .= $item->printReceipt($indent + 1);
        }

        $receipt .= $pad . sprintf('Итого по комбо: %.2f ₽ (%d мин)', $this->price(), $this->prepTimeMinutes()) . "\n";

        return $receipt;
    }
}
