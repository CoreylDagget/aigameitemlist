<?php

declare(strict_types=1);

namespace GameItemsList\Application\Action\Tags;

use DomainException;
use GameItemsList\Application\Http\JsonResponder;
use GameItemsList\Application\Service\Lists\TagService;
use GameItemsList\Domain\Lists\ListChange;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class CreateTagAction
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

        $data = (array) $request->getParsedBody();
        $name = isset($data['name']) ? trim((string) $data['name']) : '';
        $colorValue = $data['color'] ?? null;

        $errors = [];

        if ($name === '') {
            $errors['name'] = 'name is required.';
        }

        $color = null;

        if ($colorValue !== null) {
            if (!is_string($colorValue)) {
                $errors['color'] = 'color must be a string in #RRGGBB format.';
            } else {
                $colorCandidate = strtoupper(trim($colorValue));

                if ($colorCandidate === '' || preg_match('/^#[0-9A-F]{6}$/', $colorCandidate) !== 1) {
                    $errors['color'] = 'color must be a string in #RRGGBB format.';
                } else {
                    $color = $colorCandidate;
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

        $accountId = (string) $request->getAttribute('account_id');

        try {
            $change = $this->tagService->proposeCreateTag($accountId, $listId, $name, $color);
        } catch (DomainException $exception) {
            return $this->responder->problem(403, 'Forbidden', $exception->getMessage());
        } catch (InvalidArgumentException $exception) {
            if ($exception->getMessage() === 'List not found') {
                return $this->responder->problem(404, 'Not Found', $exception->getMessage());
            }

            return $this->responder->problem(400, 'Invalid request', $exception->getMessage());
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

