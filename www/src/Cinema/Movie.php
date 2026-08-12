<?php

namespace Cinema;

final class Movie
{
    /** @var Genre[]|null */
    private ?array $genres = null;

    private readonly \Closure $genreLoader;

    /**
     * @param callable(int): Genre[] $genreLoader (Lazy Load
     */
    public function __construct(
        public readonly int $id,
        public readonly string $title,
        public readonly ?string $description,
        public readonly int $durationMinutes,
        public readonly ?string $releaseDate,
        public readonly ?string $ageRating,
        public readonly ?string $country,
        callable $genreLoader,
    ) {
        $this->genreLoader = \Closure::fromCallable($genreLoader);
    }

    /**
     * @return Genre[]
     */
    public function getGenres(): array
    {
        if ($this->genres === null) {
            $this->genres = ($this->genreLoader)($this->id);
        }

        return $this->genres;
    }

}
