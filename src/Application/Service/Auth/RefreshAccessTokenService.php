<?php

declare(strict_types=1);

namespace GameItemsList\Application\Service\Auth;

use GameItemsList\Application\Security\JwtTokenService;

final class RefreshAccessTokenService
{
    public function __construct(
        private readonly RefreshTokenService $refreshTokenService,
        private readonly JwtTokenService $jwtTokenService
    ) {
    }

    public function refresh(string $refreshToken): AuthResult
    {
        $rotation = $this->refreshTokenService->rotate($refreshToken);
        $issuedAccessToken = $this->jwtTokenService->issueForAccount($rotation->account());
        $issuedRefreshToken = $rotation->refreshToken();

        return new AuthResult(
            $rotation->account(),
            $issuedAccessToken->token(),
            $issuedAccessToken->expiresIn(),
            $issuedRefreshToken->token(),
            $issuedRefreshToken->expiresIn()
        );
    }
}
