<?php

declare(strict_types=1);

namespace GameItemsList\Tests\Application\Service\Auth;

use DateTimeImmutable;
use GameItemsList\Application\Service\Auth\Exception\ExpiredRefreshTokenException;
use GameItemsList\Application\Service\Auth\Exception\InvalidRefreshTokenException;
use GameItemsList\Application\Service\Auth\Exception\RefreshTokenReuseDetectedException;
use GameItemsList\Application\Service\Auth\IssuedRefreshToken;
use GameItemsList\Application\Service\Auth\RefreshTokenService;
use GameItemsList\Domain\Account\Account;
use GameItemsList\Domain\Account\AccountRepositoryInterface;
use GameItemsList\Tests\Fixtures\FrozenClock;
use GameItemsList\Tests\Fixtures\InMemoryRefreshTokenRepository;
use GameItemsList\Tests\Fixtures\SpySecurityAlertService;
use PHPUnit\Framework\TestCase;

final class RefreshTokenServiceTest extends TestCase
{
    private InMemoryRefreshTokenRepository $repository;

    private FrozenClock $clock;

    private SpySecurityAlertService $alerts;

    private RefreshTokenService $service;

    private Account $account;

    private AccountRepositoryInterface $accountRepository;

    protected function setUp(): void
    {
        $this->repository = new InMemoryRefreshTokenRepository();
        $this->clock = new FrozenClock(new DateTimeImmutable('2024-01-01T00:00:00Z'));
        $this->alerts = new SpySecurityAlertService();

        $this->account = new Account(
            '11111111-1111-1111-1111-111111111111',
            'demo@example.com',
            password_hash('password-123', PASSWORD_BCRYPT),
            false,
            new DateTimeImmutable('2023-12-01T00:00:00Z')
        );

        $this->accountRepository = new class($this->account) implements AccountRepositoryInterface {
            public function __construct(private Account $account)
            {
            }

            public function findByEmail(string $email): ?Account
            {
                return null;
            }

            public function findById(string $id): ?Account
            {
                return $id === $this->account->id() ? $this->account : null;
            }

            public function create(string $email, string $passwordHash): Account
            {
                throw new \RuntimeException('Not implemented');
            }
        };

        $this->service = new RefreshTokenService(
            $this->repository,
            $this->accountRepository,
            $this->clock,
            $this->alerts
        );
    }

    public function testIssueForAccountCreatesRefreshToken(): void
    {
        $issued = $this->service->issueForAccount($this->account);

        self::assertInstanceOf(IssuedRefreshToken::class, $issued);
        self::assertNotEmpty($issued->token());
        self::assertSame(14 * 24 * 60 * 60, $issued->expiresIn());
    }

    public function testRotateReturnsNewTokenAndMarksOldAsUsed(): void
    {
        $issued = $this->service->issueForAccount($this->account);

        $rotation = $this->service->rotate($issued->token());

        self::assertSame($this->account->id(), $rotation->account()->id());
        self::assertNotSame($issued->token(), $rotation->refreshToken()->token());
        self::assertSame(0, count($this->alerts->alerts()));

        try {
            $this->service->rotate($issued->token());
            self::fail('Expected reuse detection exception.');
        } catch (RefreshTokenReuseDetectedException $exception) {
            // Expected
        }

        self::assertSame([$this->account->id()], $this->alerts->alerts());
    }

    public function testRotateFailsForExpiredToken(): void
    {
        $issued = $this->service->issueForAccount($this->account);
        $this->clock->advanceSeconds(15 * 24 * 60 * 60);

        $this->expectException(ExpiredRefreshTokenException::class);
        $this->service->rotate($issued->token());
    }

    public function testRotateFailsForExpiredSession(): void
    {
        $issued = $this->service->issueForAccount($this->account);
        $this->clock->advanceSeconds(31 * 24 * 60 * 60);

        $this->expectException(ExpiredRefreshTokenException::class);
        $this->service->rotate($issued->token());
    }

    public function testRotateFailsForUnknownToken(): void
    {
        $this->expectException(InvalidRefreshTokenException::class);
        $this->service->rotate('invalid-token');
    }
}
