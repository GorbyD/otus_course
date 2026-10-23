<?php

namespace Task;

final class TaskProcessor
{
    public const TYPES = ['uppercase', 'reverse', 'sum'];

    public function process(string $type, array $payload): array
    {
        return match ($type) {
            'uppercase' => ['value' => mb_strtoupper($this->requireString($payload))],
            'reverse' => ['value' => $this->mbStrRev($this->requireString($payload))],
            'sum' => ['value' => array_sum($this->requireNumbers($payload))],
            default => throw new \InvalidArgumentException("Неизвестный тип задачи '{$type}'."),
        };
    }

    private function requireString(array $payload): string
    {
        if (!isset($payload['value']) || !is_string($payload['value']) || $payload['value'] === '') {
            throw new \InvalidArgumentException("Поле 'value' обязательно.");
        }

        return $payload['value'];
    }

    private function requireNumbers(array $payload): array
    {
        $numbers = $payload['numbers'] ?? null;
        if (!is_array($numbers) || $numbers === [] || !array_is_list($numbers)) {
            throw new \InvalidArgumentException("Поле 'numbers' обязательно.");
        }

        foreach ($numbers as $number) {
            if (!is_int($number) && !is_float($number)) {
                throw new \InvalidArgumentException("Поле 'numbers' должно содержать только числа.");
            }
        }

        return $numbers;
    }

    private function mbStrRev(string $value): string
    {
        return implode('', array_reverse(mb_str_split($value)));
    }
}
