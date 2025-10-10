<?php

declare(strict_types=1);

namespace GameItemsList\Application\Action\Entries;

use DomainException;
use GameItemsList\Application\Http\JsonResponder;
use GameItemsList\Application\Service\Lists\ItemEntryService;
use GameItemsList\Domain\Lists\ItemEntry;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class SetEntryAction
{
    public function __construct(
        private readonly ItemEntryService $entryService,
        private readonly JsonResponder $responder,
    ) {
    }

    public function __invoke(ServerRequestInterface $request, array $args): ResponseInterface
    {
        $listId = isset($args['listId']) ? (string) $args['listId'] : '';
        $itemId = isset($args['itemId']) ? (string) $args['itemId'] : '';

        if ($listId === '' || $itemId === '') {
            return $this->responder->problem(400, 'Invalid request', 'Missing listId or itemId parameter.');
        }

        $data = (array) $request->getParsedBody();

        if (!array_key_exists('value', $data)) {
            return $this->responder->problem(400, 'Invalid request', 'value is required.');
        }

        $value = $data['value'];
        $accountId = (string) $request->getAttribute('account_id');

        try {
            $entry = $this->entryService->setEntry($accountId, $listId, $itemId, $value);
        } catch (DomainException $exception) {
            return $this->responder->problem(403, 'Forbidden', $exception->getMessage());
        } catch (InvalidArgumentException $exception) {
            $message = $exception->getMessage();

            if ($message === 'List not found' || $message === 'Item not found') {
                return $this->responder->problem(404, 'Not Found', $message);
            }

            return $this->responder->problem(400, 'Invalid request', $message);
        }

        return $this->respondWithEntry($entry);
    }

    private function respondWithEntry(ItemEntry $entry): ResponseInterface
    {
        return $this->responder->respond([
            'id' => $entry->id(),
            'listId' => $entry->listId(),
            'itemDefinitionId' => $entry->itemDefinitionId(),
            'accountId' => $entry->accountId(),
            'value' => $entry->value(),
            'updatedAt' => $entry->updatedAt()->format(DATE_ATOM),
        ]);
    }
}

