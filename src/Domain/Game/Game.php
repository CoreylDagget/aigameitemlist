<?php

declare(strict_types=1);

namespace GameItemsList\Domain\Game;

final class Game
{
    public function __construct(
        private readonly string $id,
        private readonly string $name,
    ) {
    }

    public function id(): string
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromDatabaseRow(array $row): self
    {
        return new self($row['id'], $row['name']);
    }
}
