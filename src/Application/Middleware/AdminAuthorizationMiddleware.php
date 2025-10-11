<?php

declare(strict_types=1);

namespace GameItemsList\Application\Middleware;

use GameItemsList\Application\Http\JsonResponder;
use GameItemsList\Domain\Account\Account;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class AdminAuthorizationMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly JsonResponder $responder)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $account = $request->getAttribute('account');

        if ($account instanceof Account && $account->isAdmin()) {
            return $handler->handle($request);
        }

        $isAdminAttribute = $request->getAttribute('is_admin');

        if (is_bool($isAdminAttribute) && $isAdminAttribute === true) {
            return $handler->handle($request);
        }

        return $this->responder->problem(403, 'Forbidden', 'Admin access required.');
    }
}
