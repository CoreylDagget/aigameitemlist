<?php

declare(strict_types=1);

namespace GameItemsList\Application\Action\Tags;

use DomainException;
use GameItemsList\Application\Http\JsonResponder;
use GameItemsList\Application\Service\Lists\TagService;
use GameItemsList\Domain\Lists\Tag;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ListTagsAction
{
    public function __construct(
        private readonly TagService $tagService,
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
            $tags = $this->tagService->listTags($accountId, $listId);
        } catch (DomainException $exception) {
            return $this->responder->problem(403, 'Forbidden', $exception->getMessage());
        } catch (InvalidArgumentException $exception) {
            return $this->responder->problem(404, 'Not Found', $exception->getMessage());
        }

        $data = array_map(static function (Tag $tag): array {
            return [
                'id' => $tag->id(),
                'listId' => $tag->listId(),
                'name' => $tag->name(),
                'color' => $tag->color(),
            ];
        }, $tags);

        return $this->responder->respond(['data' => $data]);
    }
}

