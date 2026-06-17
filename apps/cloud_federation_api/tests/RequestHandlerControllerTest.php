<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CloudFederationApi\Tests;

use OC\Federation\CloudFederationNotification;
use OCA\CloudFederationAPI\Config;
use OCA\CloudFederationAPI\Controller\RequestHandlerController;
use OCA\CloudFederationAPI\Db\FederatedInvite;
use OCA\CloudFederationAPI\Db\FederatedInviteMapper;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Federation\ICloudFederationFactory;
use OCP\Federation\ICloudFederationProvider;
use OCP\Federation\ICloudFederationProviderManager;
use OCP\Federation\ICloudIdManager;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\OCM\Events\OCMNotificationReceivedEvent;
use OCP\OCM\IOCMDiscoveryService;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\FakeAppConfig;
use Test\TestCase;

class RequestHandlerControllerTest extends TestCase {
	private IRequest&MockObject $request;
	private LoggerInterface&MockObject $logger;
	private IUserManager&MockObject $userManager;
	private IGroupManager&MockObject $groupManager;
	private IURLGenerator&MockObject $urlGenerator;
	private ICloudFederationProviderManager&MockObject $cloudFederationProviderManager;
	private Config&MockObject $config;
	private IEventDispatcher&MockObject $eventDispatcher;
	private FederatedInviteMapper&MockObject $federatedInviteMapper;

	private ICloudFederationFactory&MockObject $cloudFederationFactory;
	private ICloudIdManager&MockObject $cloudIdManager;
	private IOCMDiscoveryService&MockObject $discoveryService;
	private ITimeFactory&MockObject $timeFactory;

	private RequestHandlerController $requestHandlerController;

	protected function setUp(): void {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->cloudFederationProviderManager = $this->createMock(ICloudFederationProviderManager::class);
		$this->config = $this->createMock(Config::class);
		$this->eventDispatcher = $this->createMock(IEventDispatcher::class);
		$this->federatedInviteMapper = $this->createMock(FederatedInviteMapper::class);
		$this->cloudFederationFactory = $this->createMock(ICloudFederationFactory::class);
		$this->cloudIdManager = $this->createMock(ICloudIdManager::class);
		$this->discoveryService = $this->createMock(IOCMDiscoveryService::class);
		$this->timeFactory = $this->createMock(ITimeFactory::class);

		$this->requestHandlerController = new RequestHandlerController(
			'cloud_federation_api',
			$this->request,
			$this->logger,
			$this->userManager,
			$this->groupManager,
			$this->urlGenerator,
			$this->cloudFederationProviderManager,
			$this->config,
			$this->eventDispatcher,
			$this->federatedInviteMapper,
			new FakeAppConfig(),
			$this->cloudFederationFactory,
			$this->cloudIdManager,
			$this->discoveryService,
			$this->timeFactory,
		);
	}

	public function testInviteAccepted(): void {
		$token = 'token';
		$userId = 'userId';
		$invite = new FederatedInvite();
		$invite->setCreatedAt(1);
		$invite->setUserId($userId);
		$invite->setToken($token);

		$this->federatedInviteMapper->expects(self::once())
			->method('findByToken')
			->with($token)
			->willReturn($invite);

		$this->federatedInviteMapper->expects(self::once())
			->method('update')
			->willReturnArgument(0);

		$user = $this->createMock(IUser::class);
		$user->method('getUID')
			->willReturn($userId);
		$user->method('getEMailAddress')
			->willReturn('email');
		$user->method('getDisplayName')
			->willReturn('displayName');

		$this->userManager->expects(self::once())
			->method('get')
			->with($userId)
			->willReturn($user);

		$recipientProvider = 'http://127.0.0.1';
		$recipientId = 'remote';
		$recipientEmail = 'remote@example.org';
		$recipientName = 'Remote Remoteson';
		$response = ['userID' => $userId, 'email' => 'email', 'name' => 'displayName'];
		$json = new JSONResponse($response, Http::STATUS_OK);

		$this->assertEquals($json, $this->requestHandlerController->inviteAccepted($recipientProvider, $token, $recipientId, $recipientEmail, $recipientName));
	}

	public function testNotificationReceived(): void {
		$notificationType = 'SHARE_ACCEPTED';
		$resourceType = 'file';
		$providerId = '1337';
		$notification = ['sharedSecret' => 'secret'];
		$notificationObject = new CloudFederationNotification();

		$this->appConfig->method('getValueBool')->willReturn(true);
		$provider = $this->createMock(ICloudFederationProvider::class);
		$provider->method('notificationReceived')->willReturn([]);
		$this->cloudFederationFactory->method('getCloudFederationNotification')
			->willReturn($notificationObject);
		$this->cloudFederationProviderManager->method('getCloudFederationProvider')
			->willReturn($provider);

		$this->eventDispatcher->expects(self::once())
			->method('dispatchTyped')
			->with(self::callback(
				fn (
					OCMNotificationReceivedEvent $event)
					   => $event->getNotification() === $notificationObject
			)
			);
		$response = $this->requestHandlerController->receiveNotification(
			$notificationType,
			$resourceType,
			$providerId,
			$notification
		);
		self::assertEquals(Http::STATUS_CREATED, $response->getStatus());
		self::assertEquals([
			'notificationType' => $notificationType,
			'resourceType' => $resourceType,
			'providerId' => $providerId,
			'notification' => $notification,
		], $notificationObject->getMessage());

	}
}
