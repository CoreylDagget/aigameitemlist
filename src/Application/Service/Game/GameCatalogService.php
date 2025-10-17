<?php

declare(strict_types=1);

namespace GameItemsList\Application\Service\Game;

use GameItemsList\Domain\Game\Game;
use GameItemsList\Domain\Game\GameItemTemplate;
use GameItemsList\Domain\Game\GameItemTemplateRepositoryInterface;
use GameItemsList\Domain\Game\GameRepositoryInterface;
use InvalidArgumentException;

final class GameCatalogService
{
    public function __construct(
        private readonly GameRepositoryInterface $games,
        private readonly GameItemTemplateRepositoryInterface $templates,
    ) {
    }

    /**
     * @return Game[]
     */
    public function listGames(): array
    {
        return $this->games->findAll();
    }

    /**
     * @return GameItemTemplate[]
     */
    public function templatesForGame(string $gameId): array
    {
        $game = $this->games->findById($gameId);

        if ($game === null) {
            throw new InvalidArgumentException('Game not found');
        }

        return $this->templates->findByGame($game->id());
    }
}
