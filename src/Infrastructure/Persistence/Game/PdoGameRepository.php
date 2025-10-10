<?php
declare(strict_types=1);

namespace GameItemsList\Infrastructure\Persistence\Game;

use GameItemsList\Domain\Game\Game;
use GameItemsList\Domain\Game\GameRepositoryInterface;
use PDO;
use RuntimeException;

final class PdoGameRepository implements GameRepositoryInterface
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findById(string $id): ?Game
    {
        $statement = $this->pdo->prepare('SELECT * FROM games WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        return Game::fromDatabaseRow($row);
    }

    public function findAll(): array
    {
        $statement = $this->pdo->query('SELECT * FROM games ORDER BY name');

        if ($statement === false) {
            throw new RuntimeException('Failed to fetch games');
        }

        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

        return array_map(static fn(array $row) => Game::fromDatabaseRow($row), $rows ?: []);
    }
}
