<?php

declare(strict_types=1);

namespace GameItemsList\Domain\Game;

interface GameItemTemplateRepositoryInterface
{
    /**
     * @return GameItemTemplate[]
     */
    public function findByGame(string $gameId): array;

    public function findByIdForGame(string $templateId, string $gameId): ?GameItemTemplate;
}
