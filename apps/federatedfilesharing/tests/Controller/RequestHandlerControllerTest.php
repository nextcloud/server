<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\FederatedFileSharing\Tests;

use OCA\FederatedFileSharing\AddressHandler;
use OCA\FederatedFileSharing\Controller\RequestHandlerController;
use OCA\FederatedFileSharing\FederatedShareProvider;
use OCA\FederatedFileSharing\Notifications;
use OCP\AppFramework\Http\DataResponse;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Federation\ICloudFederationFactory;
use OCP\Federation\ICloudFederationProvider;
use OCP\Federation\ICloudFederationProviderManager;
use OCP\Federation\ICloudFederationShare;
use OCP\Federation\ICloudIdManager;
use OCP\IDBConnection;
use OCP\IRequest;
use OCP\IUserManager;
use OCP\Share;
use OCP\Share\IShare;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

/**
 * Class RequestHandlerTest
 *
 * @package OCA\FederatedFileSharing\Tests
 * @group DB
 */
class RequestHandlerControllerTest extends \Test\TestCase {
	private string $owner = 'owner';
	private string $user1 = 'user1';
	private string $user2 = 'user2';
	private string $ownerCloudId = 'owner@server0.org';
	private string $user1CloudId = 'user1@server1.org';

	private RequestHandlerController $requestHandler;
	private FederatedShareProvider&MockObject $federatedShareProvider;
	private Notifications&MockObject $notifications;
	private AddressHandler&MockObject $addressHandler;
	private IUserManager&MockObject $userManager;
	private IShare&MockObject $share;
	private ICloudIdManager&MockObject $cloudIdManager;
	private LoggerInterface&MockObject $logger;
	private IRequest&MockObject $request;
	private IDBConnection&MockObject $connection;
	private Share\IManager&MockObject $shareManager;
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

		$this->notifications = $this->createMock(Notifications::class);
		$this->addressHandler = $this->createMock(AddressHandler::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->cloudIdManager = $this->createMock(ICloudIdManager::class);
		$this->request = $this->createMock(IRequest::class);
		$this->connection = $this->createMock(IDBConnection::class);
		$this->shareManager = $this->createMock(Share\IManager::class);
		$this->cloudFederationFactory = $this->createMock(ICloudFederationFactory::class);
		$this->cloudFederationProviderManager = $this->createMock(ICloudFederationProviderManager::class);
		$this->cloudFederationProvider = $this->createMock(ICloudFederationProvider::class);
		$this->cloudFederationShare = $this->createMock(ICloudFederationShare::class);
		$this->eventDispatcher = $this->createMock(IEventDispatcher::class);
		$this->eventDispatcher->expects($this->any())->method('dispatchTyped');

		$this->logger = $this->createMock(LoggerInterface::class);

		$this->requestHandler = new RequestHandlerController(
			'federatedfilesharing',
			$this->request,
			$this->federatedShareProvider,
			$this->connection,
			$this->shareManager,
			$this->notifications,
			$this->addressHandler,
			$this->userManager,
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
				1,
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

		$result = $this->requestHandler->createShare('localhost', 'token', 'name', $this->owner, $this->user1, $this->user2, 1, $this->user1CloudId, $this->ownerCloudId);

		$this->assertInstanceOf(DataResponse::class, $result);
	}

	public function testDeclineShare(): void {
		$id = 42;

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

		$result = $this->requestHandler->declineShare($id, 'token');

		$this->assertInstanceOf(DataResponse::class, $result);
	}


	public function testAcceptShare(): void {
		$id = 42;

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

		$result = $this->requestHandler->acceptShare($id, 'token');

		$this->assertInstanceOf(DataResponse::class, $result);
	}
}
