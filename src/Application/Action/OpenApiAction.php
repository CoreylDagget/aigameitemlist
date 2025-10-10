<?php
declare(strict_types=1);

namespace GameItemsList\Application\Action;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use Slim\Psr7\Response;

final class OpenApiAction
{
    public function __construct(private readonly string $openApiFile)
    {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $contents = @file_get_contents($this->openApiFile);

        if ($contents === false) {
            throw new RuntimeException(sprintf('Unable to read OpenAPI specification from %s', $this->openApiFile));
        }

        $response = new Response();
        $response->getBody()->write($contents);

        return $response
            ->withHeader('Content-Type', 'application/yaml')
            ->withStatus(200);
    }
}
