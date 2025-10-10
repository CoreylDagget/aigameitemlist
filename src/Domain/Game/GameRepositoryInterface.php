<?php
declare(strict_types=1);

namespace GameItemsList\Domain\Game;

interface GameRepositoryInterface
{
    public function findById(string $id): ?Game;

    /**
     * @return Game[]
     */
    public function findAll(): array;
}
