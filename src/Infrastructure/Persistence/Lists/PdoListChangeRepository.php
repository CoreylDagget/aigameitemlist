<?php

declare(strict_types=1);

namespace GameItemsList\Infrastructure\Persistence\Lists;

use GameItemsList\Domain\Lists\ListChange;
use GameItemsList\Domain\Lists\ListChangeRepositoryInterface;
use JsonException;
use PDO;
use PDOException;
use RuntimeException;

final class PdoListChangeRepository implements ListChangeRepositoryInterface
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function create(string $listId, string $actorAccountId, string $type, array $payload): ListChange
    {
        try {
            $payloadJson = json_encode($payload, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Failed to encode change payload', 0, $exception);
        }

        try {
            $statement = $this->pdo->prepare(
                'INSERT INTO list_changes (list_id, actor_account_id, type, payload, status) '
                . 'VALUES (:list_id, :actor_account_id, :type, :payload, :status) '
                . 'RETURNING *'
            );

            $statement->execute([
                'list_id' => $listId,
                'actor_account_id' => $actorAccountId,
                'type' => $type,
                'payload' => $payloadJson,
                'status' => ListChange::STATUS_PENDING,
            ]);
        } catch (PDOException $exception) {
            throw new RuntimeException('Failed to create list change', 0, $exception);
        }

        $row = $statement->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            throw new RuntimeException('Failed to fetch created list change');
        }

        return ListChange::fromDatabaseRow($row);
    }
}

