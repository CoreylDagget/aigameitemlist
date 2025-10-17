<?php

declare(strict_types=1);

namespace GameItemsList\Infrastructure\Persistence\Lists;

use GameItemsList\Domain\Lists\ListShareToken;
use GameItemsList\Domain\Lists\ListShareTokenRepositoryInterface;
use GameItemsList\Infrastructure\Persistence\UuidGenerator;
use PDO;
use PDOException;
use RuntimeException;

final class PdoListShareTokenRepository implements ListShareTokenRepositoryInterface
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findActiveByList(string $listId): ?ListShareToken
    {
        $statement = $this->pdo->prepare(
            'SELECT * FROM list_share_tokens '
            . 'WHERE list_id = :list_id AND revoked_at IS NULL '
            . 'ORDER BY created_at DESC LIMIT 1'
        );
        $statement->execute(['list_id' => $listId]);

        $row = $statement->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        return $this->hydrate($row);
    }

    public function create(string $listId, string $token): ListShareToken
    {
        $id = UuidGenerator::v4();

        try {
            $statement = $this->pdo->prepare(
                'INSERT INTO list_share_tokens (id, list_id, token, created_at) '
                . 'VALUES (:id, :list_id, :token, CURRENT_TIMESTAMP(6))'
            );
            $statement->execute([
                'id' => $id,
                'list_id' => $listId,
                'token' => $token,
            ]);
        } catch (PDOException $exception) {
            throw new RuntimeException('Failed to create list share token', 0, $exception);
        }

        $statement = $this->pdo->prepare('SELECT * FROM list_share_tokens WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            throw new RuntimeException('Created list share token not found');
        }

        return $this->hydrate($row);
    }

    public function revokeAllForList(string $listId): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE list_share_tokens SET revoked_at = CURRENT_TIMESTAMP(6) '
            . 'WHERE list_id = :list_id AND revoked_at IS NULL'
        );
        $statement->execute(['list_id' => $listId]);
    }

    public function findByToken(string $token): ?ListShareToken
    {
        $statement = $this->pdo->prepare(
            'SELECT * FROM list_share_tokens WHERE token = :token LIMIT 1'
        );
        $statement->execute(['token' => $token]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        return $this->hydrate($row);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrate(array $row): ListShareToken
    {
        return new ListShareToken(
            $row['id'],
            $row['list_id'],
            $row['token'],
            new \DateTimeImmutable($row['created_at']),
            isset($row['revoked_at']) && $row['revoked_at'] !== null
                ? new \DateTimeImmutable($row['revoked_at'])
                : null,
        );
    }
}
