<?php

declare(strict_types=1);

namespace GameItemsList\Application\Action\Games;

use GameItemsList\Application\Http\JsonResponder;
use GameItemsList\Application\Service\Game\GameCatalogService;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ListGameItemTemplatesAction
{
    public function __construct(
        private readonly GameCatalogService $games,
        private readonly JsonResponder $responder,
    ) {
    }

    /**
     * @param array<string, string> $args
     */
    public function __invoke(ServerRequestInterface $request, array $args): ResponseInterface
    {
        $gameId = isset($args['gameId']) ? (string) $args['gameId'] : '';

        if ($gameId === '') {
            return $this->responder->problem(400, 'Invalid request', 'Missing gameId parameter.');
        }

        try {
            $templates = $this->games->templatesForGame($gameId);
        } catch (InvalidArgumentException $exception) {
            return $this->responder->problem(404, 'Not Found', $exception->getMessage());
        }

        $payload = array_map(
            static fn ($template): array => [
                'id' => $template->id(),
                'gameId' => $template->gameId(),
                'name' => $template->name(),
                'description' => $template->description(),
                'imageUrl' => $template->imageUrl(),
                'storageType' => $template->storageType(),
            ],
            $templates
        );

        return $this->responder->respond(['templates' => $payload]);
    }
}
