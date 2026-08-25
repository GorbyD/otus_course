<?php

namespace Cinema;

final class MovieMapper
{
    private const BASE_COLUMNS = 'id, title, description, duration_minutes, release_date, age_rating, country';
    private const DEFAULT_LIMIT = 50;
    private const MAX_LIMIT = 200;

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
    public function findAll(int $limit = self::DEFAULT_LIMIT, int $offset = 0): array
    {
        $limit = max(1, min($limit, self::MAX_LIMIT));
        $offset = max(0, $offset);

        $stmt = $this->pdo->prepare('SELECT ' . self::BASE_COLUMNS . ' FROM movies ORDER BY id LIMIT :limit OFFSET :offset');
        $stmt->bindValue('limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();

        $movies = [];
        foreach ($stmt as $row) {
            $movies[] = $this->hydrate($row);
        }

        return $movies;
    }

    /**
     * @return \Generator<int, Movie>
     */
    public function findAllCursor(int $batchSize = 500): \Generator
    {
        $batchSize = max(1, $batchSize);
        $lastId = 0;

        $stmt = $this->pdo->prepare(
            'SELECT ' . self::BASE_COLUMNS . ' FROM movies WHERE id > :last_id ORDER BY id LIMIT :limit'
        );

        while (true) {
            $stmt->bindValue('last_id', $lastId, \PDO::PARAM_INT);
            $stmt->bindValue('limit', $batchSize, \PDO::PARAM_INT);
            $stmt->execute();

            $rows = $stmt->fetchAll();
            if ($rows === []) {
                return;
            }

            foreach ($rows as $row) {
                yield $this->hydrate($row);
                $lastId = (int) $row['id'];
            }
        }
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
