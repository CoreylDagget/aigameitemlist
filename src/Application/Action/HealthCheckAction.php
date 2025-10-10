<?php

declare(strict_types=1);

namespace GameItemsList\Application\Action;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Response;

final class HealthCheckAction
{
    /**
     * Invoke healthcheck action.
     */
    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $response = new Response();
        $payload = [
            'status' => 'ok',
            'service' => 'gameitemslist-api',
            'timestamp' => (new \DateTimeImmutable())->format(DATE_ATOM),
        ];

        $response->getBody()->write((string) json_encode($payload, JSON_THROW_ON_ERROR));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }
}
