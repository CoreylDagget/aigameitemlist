<?php

declare(strict_types=1);

namespace GameItemsList\Application\Middleware;

use GameItemsList\Application\Http\JsonResponder;
use GameItemsList\Application\Security\JwtTokenService;
use GameItemsList\Domain\Account\AccountRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

final class AuthenticationMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly JwtTokenService $jwtTokenService,
        private readonly AccountRepositoryInterface $accounts,
        private readonly JsonResponder $responder
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $header = $request->getHeaderLine('Authorization');

        if ($header === '' || !str_starts_with($header, 'Bearer ')) {
            return $this->responder->problem(401, 'Unauthorized', 'Missing bearer token.');
        }

        $token = substr($header, 7);

        if ($token === false || $token === '') {
            return $this->responder->problem(401, 'Unauthorized', 'Missing bearer token.');
        }

        try {
            $payload = $this->jwtTokenService->parseToken($token);
        } catch (RuntimeException $exception) {
            return $this->responder->problem(401, 'Unauthorized', 'Invalid token.');
        }

        $accountId = $payload['sub'] ?? null;

        if (!is_string($accountId)) {
            return $this->responder->problem(401, 'Unauthorized', 'Invalid token payload.');
        }

        $account = $this->accounts->findById($accountId);

        if ($account === null) {
            return $this->responder->problem(401, 'Unauthorized', 'Account not found.');
        }

        return $handler->handle($request->withAttribute('account_id', $account->id()));
    }
}
