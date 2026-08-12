<?php

namespace Cinema;

final class Genre
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
    ) {
    }
}
