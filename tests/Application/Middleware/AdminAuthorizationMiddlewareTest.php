<?php

declare(strict_types=1);

namespace GameItemsList\Tests\Application\Middleware;

use DateTimeImmutable;
use GameItemsList\Application\Http\JsonResponder;
use GameItemsList\Application\Middleware\AdminAuthorizationMiddleware;
use GameItemsList\Domain\Account\Account;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Response;

final class AdminAuthorizationMiddlewareTest extends TestCase
{
    public function testNonAdminAccountReceivesForbiddenProblem(): void
    {
        $middleware = new AdminAuthorizationMiddleware(new JsonResponder());
        $request = (new ServerRequestFactory())
            ->createServerRequest('GET', '/v1/admin/changes')
            ->withAttribute('account', new Account(
                'account-1',
                'user@example.com',
                'hash',
                false,
                new DateTimeImmutable('2024-01-01T00:00:00Z'),
            ));

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::never())->method('handle');

        $response = $middleware->process($request, $handler);

        self::assertSame(403, $response->getStatusCode());
        self::assertSame('application/problem+json', $response->getHeaderLine('Content-Type'));
        $payload = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('Admin access required.', $payload['detail']);
    }

    public function testAdminAccountDelegatesToHandler(): void
    {
        $middleware = new AdminAuthorizationMiddleware(new JsonResponder());
        $request = (new ServerRequestFactory())
            ->createServerRequest('GET', '/v1/admin/changes')
            ->withAttribute('account', new Account(
                'admin-1',
                'admin@example.com',
                'hash',
                true,
                new DateTimeImmutable('2024-01-02T00:00:00Z'),
            ));

        $handler = new class implements RequestHandlerInterface {
            public ?ServerRequestInterface $captured = null;

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $this->captured = $request;

                return new Response(204);
            }
        };

        $response = $middleware->process($request, $handler);

        self::assertSame(204, $response->getStatusCode());
        self::assertInstanceOf(\Psr\Http\Message\ServerRequestInterface::class, $handler->captured);
    }
}
