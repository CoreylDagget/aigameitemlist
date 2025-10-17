<?php

declare(strict_types=1);

namespace GameItemsList\Tests\Fixtures;

use GameItemsList\Domain\Auth\RefreshToken;
use GameItemsList\Domain\Auth\RefreshTokenRepositoryInterface;
use GameItemsList\Domain\Auth\RefreshTokenSession;

final class InMemoryRefreshTokenRepository implements RefreshTokenRepositoryInterface
{
    /**
     * @var array<string, array{session: RefreshTokenSession, tokens: array<string, RefreshToken>}> 
     */
    private array $sessions = [];

    /**
     * @var array<string, RefreshToken>
     */
    private array $tokensById = [];

    /**
     * @var array<string, RefreshToken>
     */
    private array $tokensByHash = [];

    public function createSession(string $accountId, \DateTimeImmutable $expiresAt): RefreshTokenSession
    {
        $id = $this->uuid();
        $session = new RefreshTokenSession(
            $id,
            $accountId,
            new \DateTimeImmutable('@' . time()),
            $expiresAt,
            null
        );

        $this->sessions[$id] = ['session' => $session, 'tokens' => []];

        return $session;
    }

    public function storeToken(
        string $sessionId,
        string $accountId,
        string $tokenHash,
        \DateTimeImmutable $expiresAt,
        \DateTimeImmutable $createdAt
    ): void {
        $session = $this->requireSession($sessionId);
        $token = new RefreshToken(
            $this->uuid(),
            $sessionId,
            $accountId,
            $tokenHash,
            $createdAt,
            $expiresAt,
            null,
            null,
            $session
        );

        $this->tokensById[$token->id()] = $token;
        $this->tokensByHash[$tokenHash] = $token;
        $this->sessions[$sessionId]['tokens'][$token->id()] = $token;
    }

    public function findByTokenHash(string $tokenHash): ?RefreshToken
    {
        return $this->tokensByHash[$tokenHash] ?? null;
    }

    public function markTokenUsed(string $tokenId, \DateTimeImmutable $usedAt): void
    {
        if (!isset($this->tokensById[$tokenId])) {
            return;
        }

        $token = $this->tokensById[$tokenId];
        $session = $this->sessions[$token->sessionId()]['session'];
        $updated = new RefreshToken(
            $token->id(),
            $token->sessionId(),
            $token->accountId(),
            $token->tokenHash(),
            $token->createdAt(),
            $token->expiresAt(),
            $usedAt,
            $token->revokedAt(),
            $session
        );

        $this->replaceToken($updated);
    }

    public function revokeToken(string $tokenId, \DateTimeImmutable $revokedAt): void
    {
        if (!isset($this->tokensById[$tokenId])) {
            return;
        }

        $token = $this->tokensById[$tokenId];
        $session = $this->sessions[$token->sessionId()]['session'];
        $updated = new RefreshToken(
            $token->id(),
            $token->sessionId(),
            $token->accountId(),
            $token->tokenHash(),
            $token->createdAt(),
            $token->expiresAt(),
            $token->usedAt(),
            $revokedAt,
            $session
        );

        $this->replaceToken($updated);
    }

    public function revokeSession(string $sessionId, \DateTimeImmutable $revokedAt): void
    {
        if (!isset($this->sessions[$sessionId])) {
            return;
        }

        $sessionData = $this->sessions[$sessionId];
        $session = $sessionData['session'];
        $updatedSession = new RefreshTokenSession(
            $session->id(),
            $session->accountId(),
            $session->createdAt(),
            $session->expiresAt(),
            $revokedAt
        );
        $this->sessions[$sessionId]['session'] = $updatedSession;

        foreach ($sessionData['tokens'] as $token) {
            $this->revokeToken($token->id(), $revokedAt);
        }
    }

    private function replaceToken(RefreshToken $token): void
    {
        $this->tokensById[$token->id()] = $token;
        $this->tokensByHash[$token->tokenHash()] = $token;
        $this->sessions[$token->sessionId()]['tokens'][$token->id()] = $token;
    }

    private function requireSession(string $sessionId): RefreshTokenSession
    {
        if (!isset($this->sessions[$sessionId])) {
            throw new \RuntimeException('Unknown refresh token session.');
        }

        return $this->sessions[$sessionId]['session'];
    }

    private function uuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

        $hex = bin2hex($data);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split($hex, 4));
    }
}
