<?php

declare(strict_types=1);

namespace GameItemsList\Infrastructure\Persistence\Auth;

use GameItemsList\Domain\Auth\RefreshToken;
use GameItemsList\Domain\Auth\RefreshTokenRepositoryInterface;
use GameItemsList\Domain\Auth\RefreshTokenSession;
use GameItemsList\Infrastructure\Persistence\UuidGenerator;
use PDO;
use PDOException;
use RuntimeException;

final class PdoRefreshTokenRepository implements RefreshTokenRepositoryInterface
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function createSession(string $accountId, \DateTimeImmutable $expiresAt): RefreshTokenSession
    {
        $id = UuidGenerator::v4();

        try {
            $statement = $this->pdo->prepare(
                'INSERT INTO refresh_token_sessions (id, account_id, expires_at) VALUES (:id, :account_id, :expires_at)'
            );
            $statement->execute([
                'id' => $id,
                'account_id' => $accountId,
                'expires_at' => $expiresAt->format('Y-m-d H:i:s.u'),
            ]);
        } catch (PDOException $exception) {
            throw new RuntimeException('Failed to create refresh token session', 0, $exception);
        }

        return $this->findSessionById($id);
    }

    public function storeToken(
        string $sessionId,
        string $accountId,
        string $tokenHash,
        \DateTimeImmutable $expiresAt,
        \DateTimeImmutable $createdAt
    ): void {
        $id = UuidGenerator::v4();

        try {
            $statement = $this->pdo->prepare(
                'INSERT INTO refresh_tokens (id, session_id, account_id, token_hash, created_at, expires_at) '
                . 'VALUES (:id, :session_id, :account_id, :token_hash, :created_at, :expires_at)'
            );
            $statement->execute([
                'id' => $id,
                'session_id' => $sessionId,
                'account_id' => $accountId,
                'token_hash' => $tokenHash,
                'created_at' => $createdAt->format('Y-m-d H:i:s.u'),
                'expires_at' => $expiresAt->format('Y-m-d H:i:s.u'),
            ]);
        } catch (PDOException $exception) {
            throw new RuntimeException('Failed to store refresh token', 0, $exception);
        }
    }

    public function findByTokenHash(string $tokenHash): ?RefreshToken
    {
        $statement = $this->pdo->prepare(
            'SELECT t.id, t.session_id, t.account_id, t.token_hash, t.created_at, t.expires_at, '
            . 't.used_at, t.revoked_at AS token_revoked_at, '
            . 's.id AS session_id, s.account_id AS session_account_id, s.created_at AS session_created_at, '
            . 's.expires_at AS session_expires_at, s.revoked_at AS session_revoked_at '
            . 'FROM refresh_tokens t '
            . 'JOIN refresh_token_sessions s ON s.id = t.session_id '
            . 'WHERE t.token_hash = :token_hash '
            . 'LIMIT 1'
        );
        $statement->execute(['token_hash' => $tokenHash]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        return RefreshToken::fromDatabaseRow($row);
    }

    public function markTokenUsed(string $tokenId, \DateTimeImmutable $usedAt): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE refresh_tokens SET used_at = :used_at WHERE id = :id'
        );
        $statement->execute([
            'used_at' => $usedAt->format('Y-m-d H:i:s.u'),
            'id' => $tokenId,
        ]);
    }

    public function revokeToken(string $tokenId, \DateTimeImmutable $revokedAt): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE refresh_tokens SET revoked_at = :revoked_at WHERE id = :id'
        );
        $statement->execute([
            'revoked_at' => $revokedAt->format('Y-m-d H:i:s.u'),
            'id' => $tokenId,
        ]);
    }

    public function revokeSession(string $sessionId, \DateTimeImmutable $revokedAt): void
    {
        $this->pdo->beginTransaction();

        try {
            $sessionStatement = $this->pdo->prepare(
                'UPDATE refresh_token_sessions SET revoked_at = :revoked_at WHERE id = :id'
            );
            $sessionStatement->execute([
                'revoked_at' => $revokedAt->format('Y-m-d H:i:s.u'),
                'id' => $sessionId,
            ]);

            $tokenStatement = $this->pdo->prepare(
                'UPDATE refresh_tokens SET revoked_at = :revoked_at WHERE session_id = :session_id '
                . 'AND revoked_at IS NULL'
            );
            $tokenStatement->execute([
                'revoked_at' => $revokedAt->format('Y-m-d H:i:s.u'),
                'session_id' => $sessionId,
            ]);

            $this->pdo->commit();
        } catch (PDOException $exception) {
            $this->pdo->rollBack();

            throw new RuntimeException('Failed to revoke refresh token session', 0, $exception);
        }
    }

    private function findSessionById(string $id): RefreshTokenSession
    {
        $statement = $this->pdo->prepare(
            'SELECT id AS session_id, account_id AS session_account_id, created_at AS session_created_at, '
            . 'expires_at AS session_expires_at, revoked_at AS session_revoked_at '
            . 'FROM refresh_token_sessions WHERE id = :id LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            throw new RuntimeException('Refresh token session not found after creation.');
        }

        return RefreshTokenSession::fromDatabaseRow($row);
    }
}
