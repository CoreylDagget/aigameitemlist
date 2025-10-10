<?php

declare(strict_types=1);

namespace GameItemsList\Application\Action\Admin;

use DomainException;
use GameItemsList\Application\Http\JsonResponder;
use GameItemsList\Application\Service\Admin\AdminListChangeService;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ApproveChangeAction
{
    use ListChangeResponseFormatter;

    public function __construct(
        private readonly AdminListChangeService $service,
        private readonly JsonResponder $responder,
    ) {
    }

    public function __invoke(ServerRequestInterface $request, array $args): ResponseInterface
    {
        $changeId = isset($args['changeId']) ? (string) $args['changeId'] : '';

        if ($changeId === '') {
            return $this->responder->problem(400, 'Invalid request', 'Missing changeId parameter.');
        }

        $reviewerAccountId = (string) $request->getAttribute('account_id');

        try {
            $change = $this->service->approveChange($changeId, $reviewerAccountId);
        } catch (DomainException $exception) {
            return $this->responder->problem(403, 'Forbidden', $exception->getMessage());
        } catch (InvalidArgumentException $exception) {
            $notFoundMessages = ['Change not found.', 'List not found for change.'];
            $status = in_array($exception->getMessage(), $notFoundMessages, true) ? 404 : 400;

            return $this->responder->problem(
                $status,
                $status === 404 ? 'Not Found' : 'Invalid request',
                $exception->getMessage()
            );
        }

        return $this->responder->respond($this->formatChange($change));
    }
}
