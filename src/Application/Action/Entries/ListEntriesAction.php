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

final class ListEntriesAction
{
    public function __construct(
        private readonly ItemEntryService $entryService,
        private readonly JsonResponder $responder,
    ) {
    }

    /**
     * @param array<string, string> $args
     */
    public function __invoke(ServerRequestInterface $request, array $args): ResponseInterface
    {
        $listId = isset($args['listId']) ? (string) $args['listId'] : '';

        if ($listId === '') {
            return $this->responder->problem(400, 'Invalid request', 'Missing listId parameter.');
        }

        $accountId = (string) $request->getAttribute('account_id');

        try {
            $entries = $this->entryService->listEntries($accountId, $listId);
        } catch (DomainException $exception) {
            return $this->responder->problem(403, 'Forbidden', $exception->getMessage());
        } catch (InvalidArgumentException $exception) {
            return $this->responder->problem(404, 'Not Found', $exception->getMessage());
        }

        $data = array_map(static function (ItemEntry $entry): array {
            return [
                'id' => $entry->id(),
                'listId' => $entry->listId(),
                'itemDefinitionId' => $entry->itemDefinitionId(),
                'accountId' => $entry->accountId(),
                'value' => $entry->value(),
                'updatedAt' => $entry->updatedAt()->format(DATE_ATOM),
            ];
        }, $entries);

        return $this->responder->respond(['data' => $data]);
    }
}
