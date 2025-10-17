<?php

declare(strict_types=1);

namespace GameItemsList\Infrastructure\Persistence\Game;

use GameItemsList\Domain\Game\GameItemTemplate;
use GameItemsList\Domain\Game\GameItemTemplateRepositoryInterface;
use PDO;

final class PdoGameItemTemplateRepository implements GameItemTemplateRepositoryInterface
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findByGame(string $gameId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT * FROM game_item_templates WHERE game_id = :game_id ORDER BY name'
        );
        $statement->execute(['game_id' => $gameId]);

        $rows = $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return array_map(static fn (array $row): GameItemTemplate => GameItemTemplate::fromDatabaseRow($row), $rows);
    }

    public function findByIdForGame(string $templateId, string $gameId): ?GameItemTemplate
    {
        $statement = $this->pdo->prepare(
            'SELECT * FROM game_item_templates WHERE id = :id AND game_id = :game_id LIMIT 1'
        );
        $statement->execute([
            'id' => $templateId,
            'game_id' => $gameId,
        ]);

        $row = $statement->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        return GameItemTemplate::fromDatabaseRow($row);
    }
}
