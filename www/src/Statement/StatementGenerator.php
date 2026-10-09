<?php

namespace Statement;

/**
 * Демо-выписка за период
 */
final class StatementGenerator
{
    private const DESCRIPTIONS = ['Супермаркет', 'Такси', 'Кафе', 'Зарплата', 'Перевод', 'Аптека', 'Коммунальные услуги'];

    public function generateCsv(StatementRequest $request): string
    {
        $lines = ['date,description,amount'];
        $end = new \DateTimeImmutable($request->dateTo);

        for ($day = new \DateTimeImmutable($request->dateFrom); $day <= $end; $day = $day->modify('+1 day')) {
            for ($i = rand(0, 3); $i > 0; $i--) {
                $lines[] = sprintf(
                    '%s,%s,%.2f',
                    $day->format('Y-m-d'),
                    self::DESCRIPTIONS[rand(0, count(self::DESCRIPTIONS) - 1)],
                    rand(-500000, 500000) / 100,
                );
            }
        }

        return implode("\n", $lines) . "\n";
    }
}
