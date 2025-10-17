<?php

declare(strict_types=1);

namespace GameItemsList\Application\Action\Games;

use GameItemsList\Application\Http\JsonResponder;
use GameItemsList\Application\Service\Game\GameCatalogService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ListGamesAction
{
    public function __construct(
        private readonly GameCatalogService $games,
        private readonly JsonResponder $responder,
    ) {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $games = $this->games->listGames();

        $payload = array_map(
            static fn ($game): array => [
                'id' => $game->id(),
                'name' => $game->name(),
            ],
            $games
        );

        return $this->responder->respond(['games' => $payload]);
    }
}
