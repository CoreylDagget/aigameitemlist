<?php

declare(strict_types=1);

namespace GameItemsList\Application\Service\Auth;

use DateInterval;
use GameItemsList\Application\Clock\ClockInterface;
use GameItemsList\Application\Security\SecurityAlertServiceInterface;
use GameItemsList\Application\Service\Auth\Exception\ExpiredRefreshTokenException;
use GameItemsList\Application\Service\Auth\Exception\InvalidRefreshTokenException;
use GameItemsList\Application\Service\Auth\Exception\RefreshTokenReuseDetectedException;
use GameItemsList\Domain\Account\Account;
use GameItemsList\Domain\Account\AccountRepositoryInterface;
use GameItemsList\Domain\Auth\RefreshToken;
use GameItemsList\Domain\Auth\RefreshTokenRepositoryInterface;
use GameItemsList\Domain\Auth\RefreshTokenSession;

final class RefreshTokenService
{
    private const TOKEN_TTL_SECONDS = 14 * 24 * 60 * 60;
    private const SESSION_TTL_SECONDS = 30 * 24 * 60 * 60;
    private const TOKEN_BYTES = 64;

    public function __construct(
        private readonly RefreshTokenRepositoryInterface $tokens,
        private readonly AccountRepositoryInterface $accounts,
        private readonly ClockInterface $clock,
        private readonly SecurityAlertServiceInterface $alerts
    ) {
    }

    public function issueForAccount(Account $account): IssuedRefreshToken
    {
        $now = $this->clock->now();
        $sessionExpiresAt = $this->addSeconds($now, self::SESSION_TTL_SECONDS);
        $session = $this->tokens->createSession($account->id(), $sessionExpiresAt);

        return $this->issueTokenForSession($session, $account->id(), $now);
    }

    public function rotate(string $refreshToken): RefreshTokenRotationResult
    {
        if ($refreshToken === '') {
            throw new InvalidRefreshTokenException('Refresh token is required.');
        }

        $hash = hash('sha256', $refreshToken);
        $record = $this->tokens->findByTokenHash($hash);

        if (!$record instanceof RefreshToken) {
            throw new InvalidRefreshTokenException('Refresh token is invalid.');
        }

        $now = $this->clock->now();

        $session = $record->session();

        if ($record->revokedAt() !== null || $session->revokedAt() !== null) {
            $this->tokens->revokeSession($session->id(), $now);
            $this->alerts->notifyRefreshTokenReuse($session->accountId());

            throw new RefreshTokenReuseDetectedException('Refresh token has been revoked.');
        }

        if ($record->usedAt() !== null) {
            $this->tokens->revokeSession($session->id(), $now);
            $this->alerts->notifyRefreshTokenReuse($session->accountId());

            throw new RefreshTokenReuseDetectedException('Refresh token reuse detected.');
        }

        if ($record->expiresAt() <= $now) {
            $this->tokens->revokeToken($record->id(), $now);

            throw new ExpiredRefreshTokenException('Refresh token expired.');
        }

        if ($session->expiresAt() <= $now) {
            $this->tokens->revokeSession($session->id(), $now);

            throw new ExpiredRefreshTokenException('Refresh session expired.');
        }

        $this->tokens->markTokenUsed($record->id(), $now);

        $account = $this->accounts->findById($session->accountId());

        if (!$account instanceof Account) {
            throw new InvalidRefreshTokenException('Refresh token is invalid.');
        }

        $issuedRefreshToken = $this->issueTokenForSession($session, $account->id(), $now);

        return new RefreshTokenRotationResult($account, $issuedRefreshToken);
    }

    private function issueTokenForSession(RefreshTokenSession $session, string $accountId, \DateTimeImmutable $now): IssuedRefreshToken
    {
        $expiresAt = $this->minDateTime(
            $session->expiresAt(),
            $this->addSeconds($now, self::TOKEN_TTL_SECONDS)
        );

        $token = bin2hex(random_bytes(self::TOKEN_BYTES));
        $hash = hash('sha256', $token);

        $this->tokens->storeToken($session->id(), $accountId, $hash, $expiresAt, $now);

        $expiresIn = max(0, $expiresAt->getTimestamp() - $now->getTimestamp());

        return new IssuedRefreshToken($token, $expiresAt, $expiresIn);
    }

    private function addSeconds(\DateTimeImmutable $dateTime, int $seconds): \DateTimeImmutable
    {
        return $dateTime->add(new DateInterval(sprintf('PT%dS', $seconds)));
    }

    private function minDateTime(\DateTimeImmutable $first, \DateTimeImmutable $second): \DateTimeImmutable
    {
        return $first <= $second ? $first : $second;
    }
}
