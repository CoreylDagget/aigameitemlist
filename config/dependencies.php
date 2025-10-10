<?php

declare(strict_types=1);

use GameItemsList\Application\Action\Auth\LoginAction;
use GameItemsList\Application\Action\Auth\RegisterAction;
use GameItemsList\Application\Action\HealthCheckAction;
use GameItemsList\Application\Action\Lists\CreateListAction;
use GameItemsList\Application\Action\Lists\ListIndexAction;
use GameItemsList\Application\Action\Lists\GetListAction;
use GameItemsList\Application\Action\Lists\PublishListAction;
use GameItemsList\Application\Action\OpenApiAction;
use GameItemsList\Application\Action\SwaggerUiAction;
use GameItemsList\Application\Http\JsonResponder;
use GameItemsList\Application\Middleware\AuthenticationMiddleware;
use GameItemsList\Application\Security\JwtTokenService;
use GameItemsList\Application\Service\Auth\AuthenticateAccountService;
use GameItemsList\Application\Service\Auth\RegisterAccountService;
use GameItemsList\Application\Service\Lists\ListService;
use GameItemsList\Domain\Account\AccountRepositoryInterface;
use GameItemsList\Domain\Game\GameRepositoryInterface;
use GameItemsList\Domain\Lists\ListRepositoryInterface;
use GameItemsList\Infrastructure\Persistence\Account\PdoAccountRepository;
use GameItemsList\Infrastructure\Persistence\Game\PdoGameRepository;
use GameItemsList\Infrastructure\Persistence\Lists\PdoListRepository;
use PDO;
use Psr\Container\ContainerInterface;
use function DI\autowire;

return [
    PDO::class => static function (): PDO {
        $driver = getenv('DB_CONNECTION') ?: 'pgsql';
        $host = getenv('DB_HOST') ?: 'db';
        $port = (int) (getenv('DB_PORT') ?: ($driver === 'pgsql' ? 5432 : 3306));
        $database = getenv('DB_DATABASE') ?: 'gameitemslist';
        $username = getenv('DB_USERNAME') ?: 'gameitemslist';
        $password = getenv('DB_PASSWORD') ?: 'secret';

        $dsn = match ($driver) {
            'mysql' => sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $host, $port, $database),
            default => sprintf('pgsql:host=%s;port=%d;dbname=%s', $host, $port, $database),
        };

        $pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        return $pdo;
    },

    JsonResponder::class => static fn(): JsonResponder => new JsonResponder(),

    JwtTokenService::class => static function (): JwtTokenService {
        $secret = getenv('JWT_SECRET') ?: 'insecure-secret';
        $algorithm = getenv('JWT_ALGORITHM') ?: 'HS256';
        $issuer = getenv('JWT_ISSUER') ?: 'gameitemslist-api';
        $audience = getenv('JWT_AUDIENCE') ?: 'gameitemslist-clients';
        $ttl = (int) (getenv('JWT_TTL') ?: 3600);

        return new JwtTokenService($secret, $algorithm, $issuer, $audience, $ttl);
    },

    AccountRepositoryInterface::class => static fn(ContainerInterface $container): AccountRepositoryInterface
        => new PdoAccountRepository($container->get(PDO::class)),

    GameRepositoryInterface::class => static fn(ContainerInterface $container): GameRepositoryInterface
        => new PdoGameRepository($container->get(PDO::class)),

    ListRepositoryInterface::class => static fn(ContainerInterface $container): ListRepositoryInterface
        => new PdoListRepository($container->get(PDO::class)),

    RegisterAccountService::class => autowire(),
    AuthenticateAccountService::class => autowire(),
    ListService::class => autowire(),

    AuthenticationMiddleware::class => autowire(),

    HealthCheckAction::class => static fn(): HealthCheckAction => new HealthCheckAction(),
    OpenApiAction::class => static fn(): OpenApiAction => new OpenApiAction(dirname(__DIR__) . '/openapi.yaml'),
    SwaggerUiAction::class => static fn(): SwaggerUiAction => new SwaggerUiAction('/openapi.yaml'),
    RegisterAction::class => autowire(),
    LoginAction::class => autowire(),
    ListIndexAction::class => autowire(),
    CreateListAction::class => autowire(),
    GetListAction::class => autowire(),
    PublishListAction::class => autowire(),
];
