<?php

namespace FastFood\Kitchen;

/**
 * чтобы не звать new DateTime в итераторе
 */
interface ClockInterface
{
    public function now(): \DateTimeImmutable;
}
