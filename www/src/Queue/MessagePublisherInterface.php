<?php

namespace Queue;

interface MessagePublisherInterface
{
    public function publish(array $payload): void;
}
