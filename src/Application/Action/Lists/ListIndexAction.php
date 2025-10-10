<?php

declare(strict_types=1);

namespace GameItemsList\Application\Action\Lists;

use GameItemsList\Application\Http\JsonResponder;
use GameItemsList\Application\Service\Lists\ListServiceInterface;
use GameItemsList\Domain\Lists\GameList;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ListIndexAction
{
    public function __construct(
        private readonly ListServiceInterface $listService,
        private readonly JsonResponder $responder
    ) {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $accountId = (string) $request->getAttribute('account_id');
        $lists = $this->listService->listsForOwner($accountId);

        $data = array_map(static function (GameList $list): array {
            return [
                'id' => $list->id(),
                'ownerAccountId' => $list->ownerAccountId(),
                'game' => [
                    'id' => $list->game()->id(),
                    'name' => $list->game()->name(),
                ],
                'name' => $list->name(),
                'description' => $list->description(),
                'isPublished' => $list->isPublished(),
                'createdAt' => $list->createdAt()->format(DATE_ATOM),
            ];
        }, $lists);

        return $this->responder->respond(['data' => $data]);
    }
}
