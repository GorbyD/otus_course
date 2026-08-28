<?php

namespace Health;

interface HealthCheckInterface
{
    public function name(): string;

    public function check(): string;
}
