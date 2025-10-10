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

    public function findByStatus(?string $status = null): array
    {
        try {
            if ($status === null) {
                $statement = $this->pdo->query('SELECT * FROM list_changes ORDER BY created_at DESC');
            } else {
                $statement = $this->pdo->prepare(
                    'SELECT * FROM list_changes WHERE status = :status ORDER BY created_at DESC'
                );
                $statement->execute(['status' => $status]);
            }
        } catch (PDOException $exception) {
            throw new RuntimeException('Failed to fetch list changes', 0, $exception);
        }

        $rows = $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return array_map(static fn(array $row): ListChange => ListChange::fromDatabaseRow($row), $rows);
    }

    public function findById(string $changeId): ?ListChange
    {
        $statement = $this->pdo->prepare('SELECT * FROM list_changes WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $changeId]);

        $row = $statement->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        return ListChange::fromDatabaseRow($row);
    }

    public function findPendingByIdForUpdate(string $changeId): ?ListChange
    {
        $statement = $this->pdo->prepare(
            'SELECT * FROM list_changes WHERE id = :id AND status = :status LIMIT 1 FOR UPDATE'
        );
        $statement->execute([
            'id' => $changeId,
            'status' => ListChange::STATUS_PENDING,
        ]);

        $row = $statement->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        return ListChange::fromDatabaseRow($row);
    }

    public function markApproved(string $changeId, string $reviewerAccountId): ListChange
    {
        try {
            $statement = $this->pdo->prepare(
                'UPDATE list_changes '
                . 'SET status = :status, reviewed_by = :reviewed_by, reviewed_at = NOW() '
                . 'WHERE id = :id AND status = :current_status '
                . 'RETURNING *'
            );
            $statement->execute([
                'status' => ListChange::STATUS_APPROVED,
                'reviewed_by' => $reviewerAccountId,
                'id' => $changeId,
                'current_status' => ListChange::STATUS_PENDING,
            ]);
        } catch (PDOException $exception) {
            throw new RuntimeException('Failed to approve list change', 0, $exception);
        }

        $row = $statement->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            throw new RuntimeException('Failed to fetch approved list change');
        }

        return ListChange::fromDatabaseRow($row);
    }

    public function markRejected(string $changeId, string $reviewerAccountId): ListChange
    {
        try {
            $statement = $this->pdo->prepare(
                'UPDATE list_changes '
                . 'SET status = :status, reviewed_by = :reviewed_by, reviewed_at = NOW() '
                . 'WHERE id = :id AND status = :current_status '
                . 'RETURNING *'
            );
            $statement->execute([
                'status' => ListChange::STATUS_REJECTED,
                'reviewed_by' => $reviewerAccountId,
                'id' => $changeId,
                'current_status' => ListChange::STATUS_PENDING,
            ]);
        } catch (PDOException $exception) {
            throw new RuntimeException('Failed to reject list change', 0, $exception);
        }

        $row = $statement->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            throw new RuntimeException('Failed to fetch rejected list change');
        }

        return ListChange::fromDatabaseRow($row);
    }
}
