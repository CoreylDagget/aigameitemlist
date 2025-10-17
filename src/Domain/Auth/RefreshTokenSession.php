<?php

declare(strict_types=1);

namespace GameItemsList\Domain\Auth;

final class RefreshTokenSession
{
    public function __construct(
        private readonly string $id,
        private readonly string $accountId,
        private readonly \DateTimeImmutable $createdAt,
        private readonly \DateTimeImmutable $expiresAt,
        private readonly ?\DateTimeImmutable $revokedAt
    ) {
    }

    public function id(): string
    {
        return $this->id;
    }

    public function accountId(): string
    {
        return $this->accountId;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function expiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function revokedAt(): ?\DateTimeImmutable
    {
        return $this->revokedAt;
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromDatabaseRow(array $row): self
    {
        return new self(
            $row['session_id'],
            $row['session_account_id'],
            new \DateTimeImmutable($row['session_created_at']),
            new \DateTimeImmutable($row['session_expires_at']),
            isset($row['session_revoked_at']) && $row['session_revoked_at'] !== null
                ? new \DateTimeImmutable($row['session_revoked_at'])
                : null,
        );
    }
}
