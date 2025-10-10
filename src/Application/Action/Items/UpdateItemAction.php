<?php

declare(strict_types=1);

namespace GameItemsList\Application\Action\Items;

use DomainException;
use GameItemsList\Application\Http\JsonResponder;
use GameItemsList\Application\Service\Lists\ItemDefinitionService;
use GameItemsList\Domain\Lists\ListChange;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class UpdateItemAction
{
    public function __construct(
        private readonly ItemDefinitionService $itemService,
        private readonly JsonResponder $responder,
    ) {
    }

    /**
     * @param array<string, string> $args
     */
    public function __invoke(ServerRequestInterface $request, array $args): ResponseInterface
    {
        $listId = isset($args['listId']) ? (string) $args['listId'] : '';
        $itemId = isset($args['itemId']) ? (string) $args['itemId'] : '';

        if ($listId === '' || $itemId === '') {
            return $this->responder->problem(400, 'Invalid request', 'Missing listId or itemId parameter.');
        }

        $data = (array) $request->getParsedBody();
        $changes = [];
        $errors = [];

        if (array_key_exists('name', $data)) {
            if (!is_string($data['name'])) {
                $errors['name'] = 'name must be a string.';
            } else {
                $changes['name'] = trim($data['name']);

                if ($changes['name'] === '') {
                    $errors['name'] = 'name must be at least 1 character.';
                }
            }
        }

        if (array_key_exists('description', $data)) {
            $descriptionValue = $data['description'];

            if ($descriptionValue !== null && !is_string($descriptionValue)) {
                $errors['description'] = 'description must be a string or null.';
            } else {
                $changes['description'] = $descriptionValue;
            }
        }

        if (array_key_exists('imageUrl', $data)) {
            $imageValue = $data['imageUrl'];

            if ($imageValue !== null && !is_string($imageValue)) {
                $errors['imageUrl'] = 'imageUrl must be a string or null.';
            } else {
                $changes['imageUrl'] = $imageValue;
            }
        }

        if (array_key_exists('storageType', $data)) {
            if (!is_string($data['storageType'])) {
                $errors['storageType'] = 'storageType must be a string.';
            } else {
                $changes['storageType'] = $data['storageType'];
            }
        }

        if (array_key_exists('tagIds', $data)) {
            $tagIdsValue = $data['tagIds'];

            if ($tagIdsValue === null) {
                $changes['tagIds'] = [];
            } elseif (!is_array($tagIdsValue)) {
                $errors['tagIds'] = 'tagIds must be an array of identifiers.';
            } else {
                $tags = [];

                foreach ($tagIdsValue as $tagId) {
                    if (!is_string($tagId)) {
                        $errors['tagIds'] = 'tagIds must be an array of identifiers.';
                        break;
                    }

                    $tags[] = $tagId;
                }

                if (!isset($errors['tagIds'])) {
                    $changes['tagIds'] = $tags;
                }
            }
        }

        if ($errors !== []) {
            return $this->responder->problem(
                400,
                'Invalid request',
                'Validation failed.',
                additional: ['errors' => $errors]
            );
        }

        if ($changes === []) {
            return $this->responder->problem(400, 'Invalid request', 'At least one field must be provided.');
        }

        $accountId = (string) $request->getAttribute('account_id');

        try {
            $change = $this->itemService->proposeUpdateItem($accountId, $listId, $itemId, $changes);
        } catch (DomainException $exception) {
            return $this->responder->problem(403, 'Forbidden', $exception->getMessage());
        } catch (InvalidArgumentException $exception) {
            $message = $exception->getMessage();

            if ($message === 'List not found' || $message === 'Item not found') {
                return $this->responder->problem(404, 'Not Found', $message);
            }

            return $this->responder->problem(400, 'Invalid request', $message);
        }

        return $this->respondWithChange($change, 202);
    }

    private function respondWithChange(ListChange $change, int $status): ResponseInterface
    {
        return $this->responder->respond([
            'id' => $change->id(),
            'listId' => $change->listId(),
            'actorAccountId' => $change->actorAccountId(),
            'type' => $change->type(),
            'payload' => $change->payload(),
            'status' => $change->status(),
            'reviewedBy' => $change->reviewedBy(),
            'reviewedAt' => $change->reviewedAt()?->format(DATE_ATOM),
            'createdAt' => $change->createdAt()->format(DATE_ATOM),
        ], $status);
    }
}
