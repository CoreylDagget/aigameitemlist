<?php
declare(strict_types=1);

namespace GameItemsList\Application\Action;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Response;

final class SwaggerUiAction
{
    public function __construct(private readonly string $specUri)
    {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>gameitemslist API – OpenAPI Explorer</title>
    <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5/swagger-ui.css" />
</head>
<body>
<div id="swagger-ui"></div>
<script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-bundle.js"></script>
<script>
window.addEventListener('load', () => {
    window.ui = SwaggerUIBundle({
        url: '{$this->specUri}',
        dom_id: '#swagger-ui',
        presets: [SwaggerUIBundle.presets.apis],
        layout: 'BaseLayout'
    });
});
</script>
</body>
</html>
HTML;

        $response = new Response();
        $response->getBody()->write($html);

        return $response
            ->withHeader('Content-Type', 'text/html; charset=utf-8')
            ->withStatus(200);
    }
}
