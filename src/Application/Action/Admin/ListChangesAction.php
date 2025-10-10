<?php

declare(strict_types=1);

namespace GameItemsList\Application\Action\Admin;

use DomainException;
use GameItemsList\Application\Http\JsonResponder;
use GameItemsList\Application\Service\Admin\AdminListChangeService;
use GameItemsList\Domain\Lists\ListChange;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ListChangesAction
{
    use ListChangeResponseFormatter;

    public function __construct(
        private readonly AdminListChangeService $service,
        private readonly JsonResponder $responder,
    ) {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $status = $request->getQueryParams()['status'] ?? null;

        try {
            $changes = $this->service->listChanges(is_string($status) ? $status : null);
        } catch (DomainException $exception) {
            return $this->responder->problem(403, 'Forbidden', $exception->getMessage());
        } catch (InvalidArgumentException $exception) {
            return $this->responder->problem(400, 'Invalid request', $exception->getMessage());
        }

        return $this->responder->respond([
            'data' => array_map(
                fn (ListChange $change): array => $this->formatChange($change),
                $changes
            ),
        ]);
    }
}
