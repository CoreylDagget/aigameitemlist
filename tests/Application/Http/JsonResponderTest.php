<?php

declare(strict_types=1);

namespace GameItemsList\Tests\Application\Http;

use GameItemsList\Application\Http\JsonResponder;
use PHPUnit\Framework\TestCase;

final class JsonResponderTest extends TestCase
{
    public function testRespondReturnsJsonResponse(): void
    {
        $responder = new JsonResponder();
        $response = $responder->respond(['ok' => true], 201);

        self::assertSame(201, $response->getStatusCode());
        self::assertSame(['Content-Type' => ['application/json']], $response->getHeaders());

        $response->getBody()->rewind();
        $decoded = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(['ok' => true], $decoded);
    }

    public function testProblemResponseUsesProblemJsonMediaType(): void
    {
        $responder = new JsonResponder();
        $response = $responder->problem(
            400,
            'Invalid',
            'Missing field',
            'https://example.com/problem',
            ['detail_code' => 'missing']
        );

        self::assertSame(400, $response->getStatusCode());
        self::assertSame(['Content-Type' => ['application/problem+json']], $response->getHeaders());

        $response->getBody()->rewind();
        $decoded = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame([
            'detail_code' => 'missing',
            'type' => 'https://example.com/problem',
            'title' => 'Invalid',
            'status' => 400,
            'detail' => 'Missing field',
        ], $decoded);
    }
}
