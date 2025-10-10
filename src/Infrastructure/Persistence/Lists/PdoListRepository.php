<?php

declare(strict_types=1);

namespace GameItemsList\Infrastructure\Persistence\Lists;

use GameItemsList\Domain\Game\Game;
use GameItemsList\Domain\Lists\GameList;
use GameItemsList\Domain\Lists\ListRepositoryInterface;
use PDO;
use PDOException;
use RuntimeException;

final class PdoListRepository implements ListRepositoryInterface
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findByOwnerAccount(string $accountId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT l.*, g.id AS game_id, g.name AS game_name FROM lists l '
            . 'JOIN games g ON g.id = l.game_id '
            . 'WHERE l.account_id = :accountId ORDER BY l.created_at DESC'
        );
        $statement->execute(['accountId' => $accountId]);
        $rows = $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return array_map([$this, 'hydrateGameList'], $rows);
    }

    public function findByIdForOwner(string $listId, string $ownerAccountId): ?GameList
    {
        $statement = $this->pdo->prepare(
            'SELECT l.*, g.id AS game_id, g.name AS game_name FROM lists l '
            . 'JOIN games g ON g.id = l.game_id '
            . 'WHERE l.id = :listId AND l.account_id = :accountId '
            . 'LIMIT 1'
        );
        $statement->execute([
            'listId' => $listId,
            'accountId' => $ownerAccountId,
        ]);

        $row = $statement->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        return $this->hydrateGameList($row);
    }

    public function create(
        string $accountId,
        string $gameId,
        string $name,
        ?string $description,
        bool $isPublished
    ): GameList {
        try {
            $statement = $this->pdo->prepare(
                'INSERT INTO lists (account_id, game_id, name, description, is_published) '
                . 'VALUES (:account_id, :game_id, :name, :description, :is_published) '
                . 'RETURNING *'
            );
            $statement->execute([
                'account_id' => $accountId,
                'game_id' => $gameId,
                'name' => $name,
                'description' => $description,
                'is_published' => $isPublished,
            ]);
        } catch (PDOException $exception) {
            throw new RuntimeException('Failed to create list', 0, $exception);
        }

        $row = $statement->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            throw new RuntimeException('Failed to fetch created list');
        }

        $gameRowStatement = $this->pdo->prepare('SELECT id, name FROM games WHERE id = :id');
        $gameRowStatement->execute(['id' => $gameId]);
        $gameRow = $gameRowStatement->fetch(PDO::FETCH_ASSOC);

        if ($gameRow === false) {
            throw new RuntimeException('Associated game not found');
        }

        $row['game_id'] = $gameRow['id'];
        $row['game_name'] = $gameRow['name'];

        return $this->hydrateGameList($row);
    }

    private function hydrateGameList(array $row): GameList
    {
        return new GameList(
            $row['id'],
            $row['account_id'],
            new Game($row['game_id'], $row['game_name']),
            $row['name'],
            $row['description'] ?? null,
            $this->toBool($row['is_published'] ?? false),
            new \DateTimeImmutable($row['created_at'])
        );
    }

    private function toBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return $value === 1;
        }

        if (is_string($value)) {
            return in_array(strtolower($value), ['1', 'true', 't', 'yes'], true);
        }

        return false;
    }
}
