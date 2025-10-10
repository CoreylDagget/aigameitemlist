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

    public function findById(string $listId): ?GameList
    {
        $statement = $this->pdo->prepare(
            'SELECT l.*, g.id AS game_id, g.name AS game_name FROM lists l '
            . 'JOIN games g ON g.id = l.game_id '
            . 'WHERE l.id = :listId '
            . 'LIMIT 1'
        );
        $statement->execute(['listId' => $listId]);

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

    public function publish(string $listId, string $ownerAccountId): ?GameList
    {
        try {
            $statement = $this->pdo->prepare(
                'UPDATE lists SET is_published = TRUE '
                . 'WHERE id = :list_id AND account_id = :account_id '
                . 'RETURNING id'
            );
            $statement->execute([
                'list_id' => $listId,
                'account_id' => $ownerAccountId,
            ]);
        } catch (PDOException $exception) {
            throw new RuntimeException('Failed to publish list', 0, $exception);
        }

        $row = $statement->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        return $this->findByIdForOwner($listId, $ownerAccountId);
    }

    public function updateMetadata(string $listId, array $changes): GameList
    {
        if ($changes === []) {
            $list = $this->findById($listId);

            if ($list === null) {
                throw new RuntimeException('List not found');
            }

            return $list;
        }

        $set = [];
        $parameters = ['list_id' => $listId];

        if (array_key_exists('name', $changes)) {
            $set[] = 'name = :name';
            $parameters['name'] = $changes['name'];
        }

        if (array_key_exists('description', $changes)) {
            $set[] = 'description = :description';
            $parameters['description'] = $changes['description'];
        }

        if ($set === []) {
            $list = $this->findById($listId);

            if ($list === null) {
                throw new RuntimeException('List not found');
            }

            return $list;
        }

        $sql = sprintf('UPDATE lists SET %s WHERE id = :list_id RETURNING *', implode(', ', $set));

        try {
            $statement = $this->pdo->prepare($sql);
            $statement->execute($parameters);
        } catch (PDOException $exception) {
            throw new RuntimeException('Failed to update list metadata', 0, $exception);
        }

        $updated = $this->findById($listId);

        if ($updated === null) {
            throw new RuntimeException('List not found after metadata update');
        }

        return $updated;
    }

    /**
     * @param array<string, mixed> $row
     */
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
