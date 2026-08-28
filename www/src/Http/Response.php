<?php

namespace Http;

final class Response
{
    private function __construct(
        public readonly string $body,
        public readonly int $status,
        public readonly array $headers,
    ) {
    }

    public static function text(string $body, int $status = 200): self
    {
        return new self($body, $status, []);
    }

    public static function json(array $data, int $status = 200, int $flags = JSON_UNESCAPED_UNICODE): self
    {
        return new self(
            json_encode($data, $flags) . "\n",
            $status,
            ['Content-Type' => 'application/json; charset=utf-8'],
        );
    }
}
