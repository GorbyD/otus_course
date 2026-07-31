<?php

namespace Events;

interface EventRepositoryInterface
{
    /**
     * Добавляет новое событие в хранилище, возвращает событие с присвоенным id.
     */
    public function add(Event $event): Event;

    /**
     * Полностью очищает хранилище событий.
     */
    public function clear(): void;

    /**
     * Находит событие с наибольшим priority среди тех, чьи условия
     * полностью удовлетворяются переданными параметрами запроса.
     *
     * @param array<string, string> $params
     */
    public function findBestMatch(array $params): ?Event;
}
