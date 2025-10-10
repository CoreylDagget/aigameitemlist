<?php

declare(strict_types=1);

namespace GameItemsList\Tests\Application\Action\Admin;

use DateTimeImmutable;
use DomainException;
use GameItemsList\Application\Action\Admin\RejectChangeAction;
use GameItemsList\Application\Http\JsonResponder;
use GameItemsList\Application\Service\Admin\AdminListChangeService;
use GameItemsList\Domain\Lists\ListChange;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ServerRequestFactory;

final class RejectChangeActionTest extends TestCase
{
    public function testRejectChangeReturnsRejectedChange(): void
    {
        $change = $this->createChange(
            id: 'change-9',
            status: ListChange::STATUS_REJECTED,
            reviewedBy: 'reviewer-9',
        );

        /** @var MockObject&AdminListChangeService $service */
        $service = $this->createMock(AdminListChangeService::class);
        $service->expects(self::once())
            ->method('rejectChange')
            ->with('change-9', 'reviewer-9')
            ->willReturn($change);

        $action = new RejectChangeAction($service, new JsonResponder());

        $request = (new ServerRequestFactory())
            ->createServerRequest('POST', '/v1/admin/changes/change-9/reject')
            ->withAttribute('account_id', 'reviewer-9');

        $response = $action($request, ['changeId' => 'change-9']);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('application/json', $response->getHeaderLine('Content-Type'));

        $payload = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('change-9', $payload['id']);
        self::assertSame('list-2', $payload['listId']);
        self::assertSame('actor-2', $payload['actorAccountId']);
        self::assertSame(ListChange::TYPE_ADD_ITEM, $payload['type']);
        self::assertSame([
            'name' => 'Greatsword',
            'storageType' => 'count',
        ], $payload['payload']);
        self::assertSame(ListChange::STATUS_REJECTED, $payload['status']);
        self::assertSame('reviewer-9', $payload['reviewedBy']);
        self::assertSame('2024-05-02T12:00:00+00:00', $payload['reviewedAt']);
        self::assertSame('2024-05-01T12:00:00+00:00', $payload['createdAt']);
    }

    public function testRejectChangeRequiresChangeId(): void
    {
        /** @var MockObject&AdminListChangeService $service */
        $service = $this->createMock(AdminListChangeService::class);
        $service->expects(self::never())->method('rejectChange');

        $action = new RejectChangeAction($service, new JsonResponder());
        $request = (new ServerRequestFactory())
            ->createServerRequest('POST', '/v1/admin/changes//reject')
            ->withAttribute('account_id', 'reviewer-1');

        $response = $action($request, []);

        self::assertSame(400, $response->getStatusCode());
        self::assertSame('application/problem+json', $response->getHeaderLine('Content-Type'));

        $payload = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('Invalid request', $payload['title']);
        self::assertSame('Missing changeId parameter.', $payload['detail']);
        self::assertSame(400, $payload['status']);
    }

    public function testRejectChangeMapsDomainExceptionToForbidden(): void
    {
        /** @var MockObject&AdminListChangeService $service */
        $service = $this->createMock(AdminListChangeService::class);
        $service->expects(self::once())
            ->method('rejectChange')
            ->willThrowException(new DomainException('Reviewers may not reject their own changes.'));

        $action = new RejectChangeAction($service, new JsonResponder());
        $request = (new ServerRequestFactory())
            ->createServerRequest('POST', '/v1/admin/changes/change-2/reject')
            ->withAttribute('account_id', 'reviewer-2');

        $response = $action($request, ['changeId' => 'change-2']);

        self::assertSame(403, $response->getStatusCode());
        self::assertSame('application/problem+json', $response->getHeaderLine('Content-Type'));

        $payload = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('Forbidden', $payload['title']);
        self::assertSame('Reviewers may not reject their own changes.', $payload['detail']);
        self::assertSame(403, $payload['status']);
    }

    public function testRejectChangeMapsInvalidArgumentToNotFound(): void
    {
        /** @var MockObject&AdminListChangeService $service */
        $service = $this->createMock(AdminListChangeService::class);
        $service->expects(self::once())
            ->method('rejectChange')
            ->willThrowException(new InvalidArgumentException('List not found for change.'));

        $action = new RejectChangeAction($service, new JsonResponder());
        $request = (new ServerRequestFactory())
            ->createServerRequest('POST', '/v1/admin/changes/change-3/reject')
            ->withAttribute('account_id', 'reviewer-3');

        $response = $action($request, ['changeId' => 'change-3']);

        self::assertSame(404, $response->getStatusCode());
        self::assertSame('application/problem+json', $response->getHeaderLine('Content-Type'));

        $payload = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('Not Found', $payload['title']);
        self::assertSame('List not found for change.', $payload['detail']);
        self::assertSame(404, $payload['status']);
    }

    private function createChange(
        string $id,
        string $status,
        ?string $reviewedBy = null,
    ): ListChange {
        return new ListChange(
            $id,
            'list-2',
            'actor-2',
            ListChange::TYPE_ADD_ITEM,
            ['name' => 'Greatsword', 'storageType' => 'count'],
            $status,
            new DateTimeImmutable('2024-05-01T12:00:00Z'),
            $reviewedBy,
            $reviewedBy === null ? null : new DateTimeImmutable('2024-05-02T12:00:00Z'),
        );
    }
}
