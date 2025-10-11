<?php

declare(strict_types=1);

namespace GameItemsList\Tests\Application\Security;

use DateTimeImmutable;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use GameItemsList\Application\Security\IssuedToken;
use GameItemsList\Application\Security\JwtTokenService;
use GameItemsList\Domain\Account\Account;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class JwtTokenServiceTest extends TestCase
{
    private const SECRET = 'unit-test-secret';
    private const ALGORITHM = 'HS256';
    private const ISSUER = 'https://gameitemslist.test';
    private const AUDIENCE = 'gameitemslist-clients';
    private const TTL = 3600;

    private JwtTokenService $service;

    protected function setUp(): void
    {
        $this->service = new JwtTokenService(
            self::SECRET,
            self::ALGORITHM,
            self::ISSUER,
            self::AUDIENCE,
            self::TTL,
        );
    }

    public function testIssueForAccountProducesSignedTokenWithClaims(): void
    {
        $account = new Account(
            'account-123',
            'user@example.com',
            'hash',
            false,
            new DateTimeImmutable('2024-01-01T00:00:00Z'),
        );

        $before = time();
        $issued = $this->service->issueForAccount($account);
        $after = time();

        self::assertInstanceOf(IssuedToken::class, $issued);
        self::assertSame(self::TTL, $issued->expiresIn());

        $payload = (array) JWT::decode($issued->token(), new Key(self::SECRET, self::ALGORITHM));

        self::assertSame(self::ISSUER, $payload['iss']);
        self::assertSame(self::AUDIENCE, $payload['aud']);
        self::assertSame('account-123', $payload['sub']);
        self::assertSame('user@example.com', $payload['email']);
        self::assertGreaterThanOrEqual($before, $payload['iat']);
        self::assertLessThanOrEqual($after, $payload['iat']);
        self::assertSame($payload['iat'], $payload['nbf']);
        self::assertSame($payload['iat'] + self::TTL, $payload['exp']);
    }

    public function testParseTokenReturnsDecodedPayload(): void
    {
        $expected = [
            'iss' => self::ISSUER,
            'aud' => self::AUDIENCE,
            'sub' => 'account-abc',
        ];
        $token = JWT::encode($expected, self::SECRET, self::ALGORITHM);

        $decoded = $this->service->parseToken($token);

        self::assertSame($expected['iss'], $decoded['iss']);
        self::assertSame($expected['aud'], $decoded['aud']);
        self::assertSame($expected['sub'], $decoded['sub']);
    }

    public function testParseTokenWrapsDecodingErrors(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid token');

        $this->service->parseToken('not-a-valid-token');
    }
}
