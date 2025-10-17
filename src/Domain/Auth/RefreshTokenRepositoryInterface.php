<?php

declare(strict_types=1);

namespace GameItemsList\Domain\Auth;

interface RefreshTokenRepositoryInterface
{
    public function createSession(string $accountId, \DateTimeImmutable $expiresAt): RefreshTokenSession;

    public function storeToken(
        string $sessionId,
        string $accountId,
        string $tokenHash,
        \DateTimeImmutable $expiresAt,
        \DateTimeImmutable $createdAt
    ): void;

    public function findByTokenHash(string $tokenHash): ?RefreshToken;

    public function markTokenUsed(string $tokenId, \DateTimeImmutable $usedAt): void;

    public function revokeToken(string $tokenId, \DateTimeImmutable $revokedAt): void;

    public function revokeSession(string $sessionId, \DateTimeImmutable $revokedAt): void;
}
