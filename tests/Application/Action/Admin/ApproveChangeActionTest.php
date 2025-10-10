<?php

declare(strict_types=1);

namespace GameItemsList\Tests\Application\Action\Admin;

use DateTimeImmutable;
use DomainException;
use GameItemsList\Application\Action\Admin\ApproveChangeAction;
use GameItemsList\Application\Http\JsonResponder;
use GameItemsList\Application\Service\Admin\AdminListChangeServiceInterface;
use GameItemsList\Domain\Lists\ListChange;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ServerRequestFactory;

final class ApproveChangeActionTest extends TestCase
{
    public function testApproveChangeReturnsApprovedChange(): void
    {
        $change = $this->createChange(
            id: 'change-1',
            status: ListChange::STATUS_APPROVED,
            reviewedBy: 'reviewer-1',
        );

        /** @var MockObject&AdminListChangeServiceInterface $service */
        $service = $this->createMock(AdminListChangeServiceInterface::class);
        $service->expects(self::once())
            ->method('approveChange')
            ->with('change-1', 'reviewer-1')
            ->willReturn($change);

        $action = new ApproveChangeAction($service, new JsonResponder());

        $request = (new ServerRequestFactory())
            ->createServerRequest('POST', '/v1/admin/changes/change-1/approve')
            ->withAttribute('account_id', 'reviewer-1');

        $response = $action($request, ['changeId' => 'change-1']);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('application/json', $response->getHeaderLine('Content-Type'));

        $payload = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('change-1', $payload['id']);
        self::assertSame('list-1', $payload['listId']);
        self::assertSame('actor-1', $payload['actorAccountId']);
        self::assertSame(ListChange::TYPE_ADD_TAG, $payload['type']);
        self::assertSame(['name' => 'Support'], $payload['payload']);
        self::assertSame(ListChange::STATUS_APPROVED, $payload['status']);
        self::assertSame('reviewer-1', $payload['reviewedBy']);
        self::assertSame('2024-05-02T12:00:00+00:00', $payload['reviewedAt']);
        self::assertSame('2024-05-01T12:00:00+00:00', $payload['createdAt']);
    }

    public function testApproveChangeRequiresChangeId(): void
    {
        /** @var MockObject&AdminListChangeServiceInterface $service */
        $service = $this->createMock(AdminListChangeServiceInterface::class);
        $service->expects(self::never())->method('approveChange');

        $action = new ApproveChangeAction($service, new JsonResponder());
        $request = (new ServerRequestFactory())
            ->createServerRequest('POST', '/v1/admin/changes//approve')
            ->withAttribute('account_id', 'reviewer-1');

        $response = $action($request, []);

        self::assertSame(400, $response->getStatusCode());
        self::assertSame('application/problem+json', $response->getHeaderLine('Content-Type'));

        $payload = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('Invalid request', $payload['title']);
        self::assertSame('Missing changeId parameter.', $payload['detail']);
        self::assertSame(400, $payload['status']);
    }

    public function testApproveChangeMapsDomainExceptionToForbidden(): void
    {
        /** @var MockObject&AdminListChangeServiceInterface $service */
        $service = $this->createMock(AdminListChangeServiceInterface::class);
        $service->expects(self::once())
            ->method('approveChange')
            ->willThrowException(new DomainException('Reviewers may not approve their own changes.'));

        $action = new ApproveChangeAction($service, new JsonResponder());
        $request = (new ServerRequestFactory())
            ->createServerRequest('POST', '/v1/admin/changes/change-2/approve')
            ->withAttribute('account_id', 'reviewer-2');

        $response = $action($request, ['changeId' => 'change-2']);

        self::assertSame(403, $response->getStatusCode());
        self::assertSame('application/problem+json', $response->getHeaderLine('Content-Type'));

        $payload = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('Forbidden', $payload['title']);
        self::assertSame('Reviewers may not approve their own changes.', $payload['detail']);
        self::assertSame(403, $payload['status']);
    }

    public function testApproveChangeMapsInvalidArgumentToNotFound(): void
    {
        /** @var MockObject&AdminListChangeServiceInterface $service */
        $service = $this->createMock(AdminListChangeServiceInterface::class);
        $service->expects(self::once())
            ->method('approveChange')
            ->willThrowException(new InvalidArgumentException('Change not found.'));

        $action = new ApproveChangeAction($service, new JsonResponder());
        $request = (new ServerRequestFactory())
            ->createServerRequest('POST', '/v1/admin/changes/change-3/approve')
            ->withAttribute('account_id', 'reviewer-3');

        $response = $action($request, ['changeId' => 'change-3']);

        self::assertSame(404, $response->getStatusCode());
        self::assertSame('application/problem+json', $response->getHeaderLine('Content-Type'));

        $payload = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('Not Found', $payload['title']);
        self::assertSame('Change not found.', $payload['detail']);
        self::assertSame(404, $payload['status']);
    }

    private function createChange(
        string $id,
        string $status,
        ?string $reviewedBy = null,
    ): ListChange {
        return new ListChange(
            $id,
            'list-1',
            'actor-1',
            ListChange::TYPE_ADD_TAG,
            ['name' => 'Support'],
            $status,
            new DateTimeImmutable('2024-05-01T12:00:00Z'),
            $reviewedBy,
            $reviewedBy === null ? null : new DateTimeImmutable('2024-05-02T12:00:00Z'),
        );
    }
}
