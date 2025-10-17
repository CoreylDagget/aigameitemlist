<?php

declare(strict_types=1);

namespace GameItemsList\Application\Action\Auth;

use GameItemsList\Application\Http\JsonResponder;
use GameItemsList\Application\Service\Auth\Exception\ExpiredRefreshTokenException;
use GameItemsList\Application\Service\Auth\Exception\InvalidRefreshTokenException;
use GameItemsList\Application\Service\Auth\Exception\RefreshTokenReuseDetectedException;
use GameItemsList\Application\Service\Auth\RefreshAccessTokenService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class RefreshTokenAction
{
    public function __construct(
        private readonly RefreshAccessTokenService $refreshAccessTokenService,
        private readonly JsonResponder $responder
    ) {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $data = (array) $request->getParsedBody();
        $refreshToken = isset($data['refreshToken']) ? trim((string) $data['refreshToken']) : '';

        if ($refreshToken === '') {
            return $this->responder->problem(400, 'Invalid request', 'Refresh token is required.');
        }

        try {
            $result = $this->refreshAccessTokenService->refresh($refreshToken);
        } catch (InvalidRefreshTokenException | RefreshTokenReuseDetectedException | ExpiredRefreshTokenException $exception) {
            return $this->responder->problem(401, 'Unauthorized', 'Invalid refresh token.');
        }

        $tokens = [
            'accessToken' => $result->accessToken(),
            'expiresIn' => $result->expiresIn(),
        ];

        if ($result->refreshToken() !== null) {
            $tokens['refreshToken'] = $result->refreshToken();
        }

        if ($result->refreshTokenExpiresIn() !== null) {
            $tokens['refreshTokenExpiresIn'] = $result->refreshTokenExpiresIn();
        }

        return $this->responder->respond([
            'account' => [
                'id' => $result->account()->id(),
                'email' => $result->account()->email(),
                'createdAt' => $result->account()->createdAt()->format(DATE_ATOM),
            ],
            'tokens' => $tokens,
        ]);
    }
}
