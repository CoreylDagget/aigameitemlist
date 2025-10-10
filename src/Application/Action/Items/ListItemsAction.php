<?php

declare(strict_types=1);

namespace GameItemsList\Application\Action\Items;

use DomainException;
use GameItemsList\Application\Http\JsonResponder;
use GameItemsList\Application\Service\Lists\ItemDefinitionService;
use GameItemsList\Domain\Lists\ItemDefinition;
use GameItemsList\Domain\Lists\Tag;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ListItemsAction
{
    public function __construct(
        private readonly ItemDefinitionService $itemService,
        private readonly JsonResponder $responder,
    ) {
    }

    public function __invoke(ServerRequestInterface $request, array $args): ResponseInterface
    {
        $listId = isset($args['listId']) ? (string) $args['listId'] : '';

        if ($listId === '') {
            return $this->responder->problem(400, 'Invalid request', 'Missing listId parameter.');
        }

        $query = $request->getQueryParams();
        $tagId = isset($query['tag']) && $query['tag'] !== '' ? (string) $query['tag'] : null;

        $ownedParam = $query['owned'] ?? null;
        $owned = null;

        if ($ownedParam !== null) {
            if (is_string($ownedParam)) {
                $lower = strtolower($ownedParam);

                if (in_array($lower, ['true', '1', 'yes'], true)) {
                    $owned = true;
                } elseif (in_array($lower, ['false', '0', 'no'], true)) {
                    $owned = false;
                } else {
                    return $this->responder->problem(400, 'Invalid request', 'owned must be a boolean value.');
                }
            } elseif (is_bool($ownedParam)) {
                $owned = $ownedParam;
            } else {
                return $this->responder->problem(400, 'Invalid request', 'owned must be a boolean value.');
            }
        }

        $search = isset($query['search']) && is_string($query['search'])
            ? trim($query['search'])
            : null;

        $accountId = (string) $request->getAttribute('account_id');

        try {
            $items = $this->itemService->listItems($accountId, $listId, $tagId, $owned, $search);
        } catch (DomainException $exception) {
            return $this->responder->problem(403, 'Forbidden', $exception->getMessage());
        } catch (InvalidArgumentException $exception) {
            return $this->responder->problem(404, 'Not Found', $exception->getMessage());
        }

        $data = array_map(function (ItemDefinition $item): array {
            return [
                'id' => $item->id(),
                'listId' => $item->listId(),
                'name' => $item->name(),
                'description' => $item->description(),
                'imageUrl' => $item->imageUrl(),
                'storageType' => $item->storageType(),
                'tags' => array_map(static function (Tag $tag): array {
                    return [
                        'id' => $tag->id(),
                        'listId' => $tag->listId(),
                        'name' => $tag->name(),
                        'color' => $tag->color(),
                    ];
                }, $item->tags()),
            ];
        }, $items);

        return $this->responder->respond(['data' => $data]);
    }
}

