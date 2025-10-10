<?php

declare(strict_types=1);

namespace GameItemsList\Infrastructure\Persistence\Lists;

use GameItemsList\Domain\Lists\Tag;
use GameItemsList\Domain\Lists\TagRepositoryInterface;
use PDO;
use PDOException;
use RuntimeException;

final class PdoTagRepository implements TagRepositoryInterface
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findByList(string $listId): array
    {
        $statement = $this->pdo->prepare('SELECT * FROM list_tags WHERE list_id = :list_id ORDER BY name');
        $statement->execute(['list_id' => $listId]);

        $rows = $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return array_map(static fn (array $row): Tag => Tag::fromDatabaseRow($row), $rows);
    }

    public function findByIds(string $listId, array $tagIds): array
    {
        if ($tagIds === []) {
            return [];
        }

        $placeholders = [];
        $parameters = ['list_id' => $listId];

        foreach (array_values($tagIds) as $index => $tagId) {
            $placeholder = ':tag_' . $index;
            $placeholders[] = $placeholder;
            $parameters[$placeholder] = $tagId;
        }

        $sql = sprintf(
            'SELECT * FROM list_tags WHERE list_id = :list_id AND id IN (%s)',
            implode(',', $placeholders)
        );

        $statement = $this->pdo->prepare($sql);
        $statement->execute($parameters);

        $rows = $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return array_map(static fn (array $row): Tag => Tag::fromDatabaseRow($row), $rows);
    }

    public function create(string $listId, string $name, ?string $color): Tag
    {
        try {
            $statement = $this->pdo->prepare(
                'INSERT INTO list_tags (list_id, name, color) VALUES (:list_id, :name, :color) RETURNING *'
            );
            $statement->execute([
                'list_id' => $listId,
                'name' => $name,
                'color' => $color,
            ]);
        } catch (PDOException $exception) {
            throw new RuntimeException('Failed to create tag', 0, $exception);
        }

        $row = $statement->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            throw new RuntimeException('Failed to fetch created tag');
        }

        return Tag::fromDatabaseRow($row);
    }
}
