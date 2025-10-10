<?php

declare(strict_types=1);

namespace GameItemsList\Application\Http;

use Psr\Http\Message\ResponseInterface;
use Slim\Psr7\Response;

final class JsonResponder
{
    /**
     * @param array<string, mixed> $data
     */
    public function respond(array $data, int $status = 200): ResponseInterface
    {
        $response = new Response($status);
        $response->getBody()->write((string) json_encode($data, JSON_THROW_ON_ERROR));

        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * @param array<string, mixed> $additional
     */
    public function problem(
        int $status,
        string $title,
        string $detail,
        string $type = 'about:blank',
        array $additional = []
    ): ResponseInterface {
        /** @var array<string, mixed> $additional */
        $payload = array_merge($additional, [
            'type' => $type,
            'title' => $title,
            'status' => $status,
            'detail' => $detail,
        ]);

        $response = new Response($status);
        $response->getBody()->write((string) json_encode($payload, JSON_THROW_ON_ERROR));

        return $response->withHeader('Content-Type', 'application/problem+json');
    }
}
