<?php

declare(strict_types=1);

namespace GameItemsList\Tests\Application\Middleware;

use DateTimeImmutable;
use Firebase\JWT\JWT;
use GameItemsList\Application\Http\JsonResponder;
use GameItemsList\Application\Middleware\AuthenticationMiddleware;
use GameItemsList\Application\Security\JwtTokenService;
use GameItemsList\Domain\Account\Account;
use GameItemsList\Domain\Account\AccountRepositoryInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Response;

final class AuthenticationMiddlewareTest extends TestCase
{
    private JwtTokenService $jwtTokens;

    private JsonResponder $responder;

    protected function setUp(): void
    {
        $this->jwtTokens = new JwtTokenService('test-secret', 'HS256', 'issuer', 'audience', 3600);
        $this->responder = new JsonResponder();
    }

    public function testMissingAuthorizationHeaderReturnsUnauthorizedProblem(): void
    {
        $middleware = new AuthenticationMiddleware(
            $this->jwtTokens,
            $this->createStub(AccountRepositoryInterface::class),
            $this->responder,
        );

        $request = (new ServerRequestFactory())->createServerRequest('GET', '/lists');
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::never())->method('handle');

        $response = $middleware->process($request, $handler);

        self::assertSame(401, $response->getStatusCode());
        self::assertSame('application/problem+json', $response->getHeaderLine('Content-Type'));
        $body = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('Missing bearer token.', $body['detail']);
    }

    public function testInvalidSchemeReturnsUnauthorizedProblem(): void
    {
        $middleware = new AuthenticationMiddleware(
            $this->jwtTokens,
            $this->createStub(AccountRepositoryInterface::class),
            $this->responder,
        );

        $request = (new ServerRequestFactory())
            ->createServerRequest('GET', '/lists')
            ->withHeader('Authorization', 'Basic abc123');
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::never())->method('handle');

        $response = $middleware->process($request, $handler);

        self::assertSame(401, $response->getStatusCode());
        $body = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('Missing bearer token.', $body['detail']);
    }

    public function testEmptyBearerValueReturnsUnauthorizedProblem(): void
    {
        $middleware = new AuthenticationMiddleware(
            $this->jwtTokens,
            $this->createStub(AccountRepositoryInterface::class),
            $this->responder,
        );

        $request = (new ServerRequestFactory())
            ->createServerRequest('GET', '/lists')
            ->withHeader('Authorization', 'Bearer ');
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::never())->method('handle');

        $response = $middleware->process($request, $handler);

        self::assertSame(401, $response->getStatusCode());
        $body = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('Missing bearer token.', $body['detail']);
    }

    public function testInvalidTokenReturnsUnauthorizedProblem(): void
    {
        $middleware = new AuthenticationMiddleware(
            $this->jwtTokens,
            $this->createStub(AccountRepositoryInterface::class),
            $this->responder,
        );

        $request = (new ServerRequestFactory())
            ->createServerRequest('GET', '/lists')
            ->withHeader('Authorization', 'Bearer not-a-token');
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::never())->method('handle');

        $response = $middleware->process($request, $handler);

        self::assertSame(401, $response->getStatusCode());
        $body = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('Invalid token.', $body['detail']);
    }

    public function testMissingSubjectReturnsUnauthorizedProblem(): void
    {
        $middleware = new AuthenticationMiddleware(
            $this->jwtTokens,
            $this->createStub(AccountRepositoryInterface::class),
            $this->responder,
        );

        $token = JWT::encode(['iss' => 'issuer'], 'test-secret', 'HS256');
        $request = (new ServerRequestFactory())
            ->createServerRequest('GET', '/lists')
            ->withHeader('Authorization', 'Bearer ' . $token);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::never())->method('handle');

        $response = $middleware->process($request, $handler);

        self::assertSame(401, $response->getStatusCode());
        $body = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('Invalid token payload.', $body['detail']);
    }

    public function testUnknownAccountReturnsUnauthorizedProblem(): void
    {
        $middleware = new AuthenticationMiddleware(
            $this->jwtTokens,
            $this->createMock(AccountRepositoryInterface::class),
            $this->responder,
        );

        $token = JWT::encode(['sub' => 'account-123'], 'test-secret', 'HS256');
        $request = (new ServerRequestFactory())
            ->createServerRequest('GET', '/lists')
            ->withHeader('Authorization', 'Bearer ' . $token);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::never())->method('handle');

        $response = $middleware->process($request, $handler);

        self::assertSame(401, $response->getStatusCode());
        $body = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('Account not found.', $body['detail']);
    }

    public function testValidTokenDelegatesToHandlerWithAccountAttribute(): void
    {
        $accounts = $this->createMock(AccountRepositoryInterface::class);
        $accounts->expects(self::once())
            ->method('findById')
            ->with('account-123')
            ->willReturn(new Account(
                'account-123',
                'user@example.com',
                'hash',
                new DateTimeImmutable('2024-01-01T00:00:00Z'),
            ));

        $middleware = new AuthenticationMiddleware(
            $this->jwtTokens,
            $accounts,
            $this->responder,
        );

        $token = JWT::encode(['sub' => 'account-123'], 'test-secret', 'HS256');
        $request = (new ServerRequestFactory())
            ->createServerRequest('GET', '/lists')
            ->withHeader('Authorization', 'Bearer ' . $token);

        $capturedRequest = null;
        $handler = new class ($capturedRequest) implements RequestHandlerInterface {
            public ?ServerRequestInterface $captured = null;

            public function __construct(&$captured)
            {
                $this->captured = &$captured;
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $this->captured = $request;

                return new Response(204);
            }
        };

        $response = $middleware->process($request, $handler);

        self::assertSame(204, $response->getStatusCode());
        self::assertInstanceOf(\Psr\Http\Message\ServerRequestInterface::class, $handler->captured);
        self::assertSame('account-123', $handler->captured->getAttribute('account_id'));
    }
}
