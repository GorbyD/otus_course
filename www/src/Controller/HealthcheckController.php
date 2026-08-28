<?php

namespace Controller;

use Health\HealthCheckInterface;
use Http\Request;
use Http\Response;

final class HealthcheckController
{
    /** @param HealthCheckInterface[] $checks */
    public function __construct(
        private readonly array $checks,
    ) {
    }

    public function handle(Request $request): Response
    {
        $result = [];
        foreach ($this->checks as $check) {
            $result[$check->name()] = $check->check();
        }

        return Response::json($result, 200, JSON_PRETTY_PRINT);
    }
}
