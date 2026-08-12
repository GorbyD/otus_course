<?php

namespace Cinema;

final class MovieMapper
{
    private const BASE_COLUMNS = 'id, title, description, duration_minutes, release_date, age_rating, country';

    private readonly IdentityMap $identityMap;

    public function __construct(
        private readonly \PDO $pdo,
    ) {
        $this->identityMap = new IdentityMap();
    }

    public function findById(int $id): ?Movie
    {
        $cached = $this->identityMap->get($id);
        if ($cached instanceof Movie) {
            return $cached;
        }

        $stmt = $this->pdo->prepare('SELECT ' . self::BASE_COLUMNS . ' FROM movies WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        if ($row === false) {
            return null;
        }

        return $this->hydrate($row);
    }

    /**
     * @return Movie[]
     */
    public function findAll(): array
    {
        $stmt = $this->pdo->query('SELECT ' . self::BASE_COLUMNS . ' FROM movies ORDER BY id');

        $movies = [];
        foreach ($stmt as $row) {
            $movies[] = $this->hydrate($row);
        }

        return $movies;
    }

    private function hydrate(array $row): Movie
    {
        $id = (int) $row['id'];

        $existing = $this->identityMap->get($id);
        if ($existing instanceof Movie) {
            return $existing;
        }

        $movie = new Movie(
            id: $id,
            title: $row['title'],
            description: $row['description'],
            durationMinutes: (int) $row['duration_minutes'],
            releaseDate: $row['release_date'],
            ageRating: $row['age_rating'],
            country: $row['country'],
            genreLoader: fn (int $movieId): array => $this->loadGenres($movieId),
        );

        $this->identityMap->set($id, $movie);

        return $movie;
    }

    /**
     * @return Genre[]
     */
    private function loadGenres(int $movieId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT g.id, g.name
             FROM genres g
             JOIN movie_genres mg ON mg.genre_id = g.id
             WHERE mg.movie_id = :movie_id
             ORDER BY g.name'
        );
        $stmt->execute(['movie_id' => $movieId]);

        $genres = [];
        foreach ($stmt as $row) {
            $genres[] = new Genre((int) $row['id'], $row['name']);
        }

        return $genres;
    }
}
