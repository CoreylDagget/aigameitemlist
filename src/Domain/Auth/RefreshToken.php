<?php

declare(strict_types=1);

namespace GameItemsList\Domain\Auth;

final class RefreshToken
{
    public function __construct(
        private readonly string $id,
        private readonly string $sessionId,
        private readonly string $accountId,
        private readonly string $tokenHash,
        private readonly \DateTimeImmutable $createdAt,
        private readonly \DateTimeImmutable $expiresAt,
        private readonly ?\DateTimeImmutable $usedAt,
        private readonly ?\DateTimeImmutable $revokedAt,
        private readonly RefreshTokenSession $session
    ) {
    }

    public function id(): string
    {
        return $this->id;
    }

    public function sessionId(): string
    {
        return $this->sessionId;
    }

    public function accountId(): string
    {
        return $this->accountId;
    }

    public function tokenHash(): string
    {
        return $this->tokenHash;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function expiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function usedAt(): ?\DateTimeImmutable
    {
        return $this->usedAt;
    }

    public function revokedAt(): ?\DateTimeImmutable
    {
        return $this->revokedAt;
    }

    public function session(): RefreshTokenSession
    {
        return $this->session;
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromDatabaseRow(array $row): self
    {
        return new self(
            $row['id'],
            $row['session_id'],
            $row['account_id'],
            $row['token_hash'],
            new \DateTimeImmutable($row['created_at']),
            new \DateTimeImmutable($row['expires_at']),
            isset($row['used_at']) && $row['used_at'] !== null
                ? new \DateTimeImmutable($row['used_at'])
                : null,
            isset($row['token_revoked_at']) && $row['token_revoked_at'] !== null
                ? new \DateTimeImmutable($row['token_revoked_at'])
                : null,
            RefreshTokenSession::fromDatabaseRow($row),
        );
    }
}
