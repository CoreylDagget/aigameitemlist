<?php
declare(strict_types=1);

namespace GameItemsList\Application\Security;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use GameItemsList\Domain\Account\Account;
use RuntimeException;

final class JwtTokenService
{
    public function __construct(
        private readonly string $secret,
        private readonly string $algorithm,
        private readonly string $issuer,
        private readonly string $audience,
        private readonly int $ttlSeconds
    ) {
    }

    public function issueForAccount(Account $account): IssuedToken
    {
        $issuedAt = time();
        $expiresAt = $issuedAt + $this->ttlSeconds;

        $payload = [
            'iss' => $this->issuer,
            'aud' => $this->audience,
            'iat' => $issuedAt,
            'nbf' => $issuedAt,
            'exp' => $expiresAt,
            'sub' => $account->id(),
            'email' => $account->email(),
        ];

        $token = JWT::encode($payload, $this->secret, $this->algorithm);

        return new IssuedToken($token, $this->ttlSeconds);
    }

    /**
     * @return array<string, mixed>
     */
    public function parseToken(string $token): array
    {
        try {
            $decoded = (array) JWT::decode($token, new Key($this->secret, $this->algorithm));
        } catch (\Throwable $exception) {
            throw new RuntimeException('Invalid token', 0, $exception);
        }

        return $decoded;
    }
}
