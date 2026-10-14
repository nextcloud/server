<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\FederatedFileSharing\Tests;

use OCA\FederatedFileSharing\Controller\RequestHandlerController;
use OCA\FederatedFileSharing\FederatedShareProvider;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCS\OCSBadRequestException;
use OCP\Constants;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Federation\ICloudFederationFactory;
use OCP\Federation\ICloudFederationProvider;
use OCP\Federation\ICloudFederationProviderManager;
use OCP\Federation\ICloudFederationShare;
use OCP\Federation\ICloudIdManager;
use OCP\IDBConnection;
use OCP\IRequest;
use OCP\Log\Audit\CriticalActionPerformedEvent;
use OCP\Share\IShare;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

/**
 * Class RequestHandlerTest
 *
 * @package OCA\FederatedFileSharing\Tests
 */
#[\PHPUnit\Framework\Attributes\Group(name: 'DB')]
class RequestHandlerControllerTest extends \Test\TestCase {
	private string $owner = 'owner';
	private string $user1 = 'user1';
	private string $user2 = 'user2';
	private string $ownerCloudId = 'owner@server0.org';
	private string $user1CloudId = 'user1@server1.org';

	private RequestHandlerController $requestHandler;
	private FederatedShareProvider&MockObject $federatedShareProvider;
	private IShare&MockObject $share;
	private ICloudIdManager&MockObject $cloudIdManager;
	private LoggerInterface&MockObject $logger;
	private IRequest&MockObject $request;
	private IDBConnection&MockObject $connection;
	private ICloudFederationFactory&MockObject $cloudFederationFactory;
	private ICloudFederationProviderManager&MockObject $cloudFederationProviderManager;
	private ICloudFederationProvider&MockObject $cloudFederationProvider;
	private ICloudFederationShare&MockObject $cloudFederationShare;
	private IEventDispatcher&MockObject $eventDispatcher;

	protected function setUp(): void {
		$this->share = $this->createMock(IShare::class);
		$this->federatedShareProvider = $this->createMock(FederatedShareProvider::class);
		$this->federatedShareProvider->expects($this->any())
			->method('isOutgoingServer2serverShareEnabled')->willReturn(true);
		$this->federatedShareProvider->expects($this->any())
			->method('isIncomingServer2serverShareEnabled')->willReturn(true);
		$this->federatedShareProvider->expects($this->any())->method('getShareById')
			->willReturn($this->share);

		$this->cloudIdManager = $this->createMock(ICloudIdManager::class);
		$this->request = $this->createMock(IRequest::class);
		$this->connection = $this->createMock(IDBConnection::class);
		$this->cloudFederationFactory = $this->createMock(ICloudFederationFactory::class);
		$this->cloudFederationProviderManager = $this->createMock(ICloudFederationProviderManager::class);
		$this->cloudFederationProvider = $this->createMock(ICloudFederationProvider::class);
		$this->cloudFederationShare = $this->createMock(ICloudFederationShare::class);
		$this->eventDispatcher = $this->createMock(IEventDispatcher::class);

		$this->logger = $this->createMock(LoggerInterface::class);

		$this->requestHandler = new RequestHandlerController(
			'federatedfilesharing',
			$this->request,
			$this->federatedShareProvider,
			$this->connection,
			$this->cloudIdManager,
			$this->logger,
			$this->cloudFederationFactory,
			$this->cloudFederationProviderManager,
			$this->eventDispatcher
		);
	}

	public function testCreateShare(): void {
		$this->cloudFederationFactory->expects($this->once())->method('getCloudFederationShare')
			->with(
				$this->user2,
				'name',
				'',
				'1',
				$this->ownerCloudId,
				$this->owner,
				$this->user1CloudId,
				$this->user1,
				'token',
				'user',
				'file'
			)->willReturn($this->cloudFederationShare);

		/** @var ICloudFederationProvider&MockObject $provider */
		$this->cloudFederationProviderManager->expects($this->once())
			->method('getCloudFederationProvider')
			->with('file')
			->willReturn($this->cloudFederationProvider);

		$this->cloudFederationProvider->expects($this->once())->method('shareReceived')
			->with($this->cloudFederationShare);

		$this->expectAuditEvent(
			'federatedfilesharing.share.received',
			'A new federated share with "%s" was shared by "%s" (resource owner is: "%s") and shared with "%s"',
			[
				'name' => 'name',
				'sharedBy' => $this->user1CloudId,
				'owner' => $this->ownerCloudId,
				'shareWith' => $this->user2,
				'remoteId' => '1',
			],
		);

		$result = $this->requestHandler->createShare('localhost', 'token', 'name', $this->owner, $this->user1, $this->user2, '1', $this->user1CloudId, $this->ownerCloudId);

		$this->assertInstanceOf(DataResponse::class, $result);
	}

	public function testCreateShareByOwner(): void {
		$this->cloudFederationFactory->method('getCloudFederationShare')->willReturn($this->cloudFederationShare);
		$this->cloudFederationProviderManager->method('getCloudFederationProvider')
			->with('file')
			->willReturn($this->cloudFederationProvider);

		$this->expectAuditEvent(
			'federatedfilesharing.share.received',
			'A new federated share with "%s" was created by "%s" and shared with "%s"',
			[
				'name' => 'name',
				'owner' => $this->ownerCloudId,
				'shareWith' => $this->user2,
				'remoteId' => '1',
			],
		);

		$result = $this->requestHandler->createShare('localhost', 'token', 'name', $this->owner, $this->owner, $this->user2, '1', $this->ownerCloudId, $this->ownerCloudId);

		$this->assertInstanceOf(DataResponse::class, $result);
	}

	public function testDeclineShare(): void {
		$id = '42';

		$notification = [
			'sharedSecret' => 'token',
			'message' => 'Recipient declined the share'
		];

		$this->cloudFederationProviderManager->expects($this->once())
			->method('getCloudFederationProvider')
			->with('file')
			->willReturn($this->cloudFederationProvider);

		$this->cloudFederationProvider->expects($this->once())
			->method('notificationReceived')
			->with('SHARE_DECLINED', $id, $notification);

		$this->expectAuditEvent('federatedfilesharing.share.declined', 'Federated share with id "%s" was declined', ['shareId' => $id]);

		$result = $this->requestHandler->declineShare($id, 'token');

		$this->assertInstanceOf(DataResponse::class, $result);
	}

	public function testAcceptShare(): void {
		$id = '42';

		$notification = [
			'sharedSecret' => 'token',
			'message' => 'Recipient accept the share'
		];

		$this->cloudFederationProviderManager->expects($this->once())
			->method('getCloudFederationProvider')
			->with('file')
			->willReturn($this->cloudFederationProvider);

		$this->cloudFederationProvider->expects($this->once())
			->method('notificationReceived')
			->with('SHARE_ACCEPTED', $id, $notification);

		$this->expectAuditEvent('federatedfilesharing.share.accepted', 'Federated share with id "%s" was accepted', ['shareId' => $id]);

		$result = $this->requestHandler->acceptShare($id, 'token');

		$this->assertInstanceOf(DataResponse::class, $result);
	}

	public function testUnshare(): void {
		$this->cloudFederationProviderManager->method('getCloudFederationProvider')
			->with('file')
			->willReturn($this->cloudFederationProvider);
		$this->cloudFederationProvider->expects($this->once())
			->method('notificationReceived')
			->with('SHARE_UNSHARED', '42', ['sharedSecret' => 'token']);

		$this->expectAuditEvent('federatedfilesharing.share.unshared', 'Federated share with id "%s" was unshared', ['shareId' => '42']);

		$this->assertInstanceOf(DataResponse::class, $this->requestHandler->unshare('42', 'token'));
	}

	public function testRevoke(): void {
		$this->cloudFederationProviderManager->method('getCloudFederationProvider')
			->with('file')
			->willReturn($this->cloudFederationProvider);
		$this->cloudFederationProvider->expects($this->once())
			->method('notificationReceived')
			->with('RESHARE_UNDO', '42', ['sharedSecret' => 'token']);

		$this->expectAuditEvent('federatedfilesharing.share.revoked', 'Federated share with id "%s" was revoked', ['shareId' => '42']);

		$this->assertInstanceOf(DataResponse::class, $this->requestHandler->revoke('42', 'token'));
	}

	public function testRevokeFailureDoesNotLog(): void {
		$this->cloudFederationProviderManager->method('getCloudFederationProvider')
			->willThrowException(new \Exception());
		$this->eventDispatcher->expects($this->never())->method('dispatchTyped');

		$this->expectException(OCSBadRequestException::class);
		$this->requestHandler->revoke('42', 'token');
	}

	public function testUpdatePermissions(): void {
		$this->cloudFederationProviderManager->method('getCloudFederationProvider')
			->with('file')
			->willReturn($this->cloudFederationProvider);
		$this->cloudFederationProvider->expects($this->once())
			->method('notificationReceived')
			->with('RESHARE_CHANGE_PERMISSION', '42', ['sharedSecret' => 'token', 'permission' => ['share', 'read']]);

		$this->expectAuditEvent(
			'federatedfilesharing.share.permissions_updated',
			'Federated share with id "%s" has updated permissions "%s"',
			['shareId' => '42', 'permissions' => 'share, read'],
		);

		$this->assertInstanceOf(DataResponse::class, $this->requestHandler->updatePermissions('42', 'token', Constants::PERMISSION_READ | Constants::PERMISSION_SHARE));
	}

	private function expectAuditEvent(string $operation, string $message, array $parameters): void {
		$this->eventDispatcher->expects($this->once())
			->method('dispatchTyped')
			->with($this->callback(fn (CriticalActionPerformedEvent $event): bool
				=> $event->getOperation() === $operation
				&& $event->getLogMessage() === $message
				&& $event->getParameters() === $parameters));
	}
}
