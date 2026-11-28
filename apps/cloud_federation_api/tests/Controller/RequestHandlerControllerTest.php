<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CloudFederationAPI\Tests\Controller;

use OC\OCM\OCMSignatoryManager;
use OCA\CloudFederationAPI\Config;
use OCA\CloudFederationAPI\Controller\RequestHandlerController;
use OCP\AppFramework\Http;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Federation\Exceptions\ProviderDoesNotExistsException;
use OCP\Federation\ICloudFederationFactory;
use OCP\Federation\ICloudFederationNotification;
use OCP\Federation\ICloudFederationProvider;
use OCP\Federation\ICloudFederationProviderManager;
use OCP\Federation\ICloudFederationShare;
use OCP\Federation\ICloudId;
use OCP\Federation\ICloudIdManager;
use OCP\Federation\ISenderDomainAwareCloudFederationNotification;
use OCP\Federation\ISenderDomainAwareCloudFederationProvider;
use OCP\Federation\ISignedCloudFederationProvider;
use OCP\IAppConfig;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\Log\Audit\CriticalActionPerformedEvent;
use OCP\OCM\Events\OCMNotificationReceivedEvent;
use OCP\OCM\IOCMDiscoveryService;
use OCP\Security\Signature\Exceptions\IncomingRequestException;
use OCP\Security\Signature\IIncomingSignedRequest;
use OCP\Share\Exceptions\ShareNotFound;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class RequestHandlerControllerTest extends TestCase {
	private IUserManager&MockObject $userManager;
	private ICloudFederationProviderManager&MockObject $providerManager;
	private ICloudFederationProvider&MockObject $provider;
	private SignedSenderDomainAwareCloudFederationProvider&MockObject $senderDomainAwareProvider;
	private Config&MockObject $config;
	private IEventDispatcher&MockObject $dispatcher;
	private ICloudFederationFactory&MockObject $factory;
	private ICloudIdManager&MockObject $cloudIdManager;
	private IOCMDiscoveryService&MockObject $ocmDiscoveryService;
	private RequestHandlerController $controller;

	/** @var list<Event> */
	private array $dispatchedEvents = [];

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->userManager = $this->createMock(IUserManager::class);
		$this->providerManager = $this->createMock(ICloudFederationProviderManager::class);
		$this->provider = $this->createMock(ICloudFederationProvider::class);
		$this->senderDomainAwareProvider = $this->createMock(SignedSenderDomainAwareCloudFederationProvider::class);
		$this->config = $this->createMock(Config::class);
		$this->dispatcher = $this->createMock(IEventDispatcher::class);
		$this->factory = $this->createMock(ICloudFederationFactory::class);
		$this->cloudIdManager = $this->createMock(ICloudIdManager::class);
		$this->ocmDiscoveryService = $this->createMock(IOCMDiscoveryService::class);

		$appConfig = $this->createMock(IAppConfig::class);
		$appConfig->method('getValueBool')
			->with('core', OCMSignatoryManager::APPCONFIG_SIGN_DISABLED, false, true)
			->willReturn(false);

		$this->providerManager->method('getCloudFederationProvider')
			->willReturnCallback(fn (?string $resourceType): ICloudFederationProvider => match ($resourceType) {
				'file' => $this->provider,
				'circle' => $this->senderDomainAwareProvider,
				default => throw new ProviderDoesNotExistsException($resourceType),
			});
		$this->factory->method('getCloudFederationNotification')->willReturn($this->createMock(ICloudFederationNotification::class));

		$signedRequest = $this->createMock(IIncomingSignedRequest::class);
		$signedRequest->method('getOrigin')->willReturn('remote.example');
		$this->ocmDiscoveryService->method('getIncomingSignedRequestFromDomain')->with('remote.example')->willReturn($signedRequest);

		$this->dispatcher->method('dispatchTyped')
			->willReturnCallback(function (Event $event): void {
				$this->dispatchedEvents[] = $event;
			});

		$this->controller = new RequestHandlerController(
			'cloud_federation_api',
			$this->createMock(IRequest::class),
			$this->createMock(LoggerInterface::class),
			$this->userManager,
			$this->createMock(IGroupManager::class),
			$this->createMock(IURLGenerator::class),
			$this->providerManager,
			$this->config,
			$this->dispatcher,
			$appConfig,
			$this->factory,
			$this->cloudIdManager,
			$this->ocmDiscoveryService,
		);
	}

	/**
	 * @return list<CriticalActionPerformedEvent>
	 */
	private function getAuditEvents(): array {
		return array_values(array_filter(
			$this->dispatchedEvents,
			static fn (Event $event): bool => $event instanceof CriticalActionPerformedEvent,
		));
	}

	public function testAddShareLogsCriticalAction(): void {
		$this->config->method('getSupportedShareTypes')->with('file')->willReturn(['user']);
		$cloudId = $this->createMock(ICloudId::class);
		$cloudId->method('getUser')->willReturn('bob');
		$this->cloudIdManager->method('resolveCloudId')->with('bob@local.example')->willReturn($cloudId);
		$this->userManager->method('getUserNameFromLoginName')->with('bob')->willReturn('bob');
		$this->userManager->method('userExists')->with('bob')->willReturn(true);
		$this->factory->method('getCloudFederationShare')->willReturn($this->createMock(ICloudFederationShare::class));
		$this->provider->expects($this->once())->method('shareReceived');

		$response = $this->controller->addShare(
			'bob@local.example',
			'file.txt',
			null,
			'12',
			'alice@remote.example',
			'Alice',
			null,
			null,
			['name' => 'webdav', 'options' => ['sharedSecret' => 'secret']],
			'user',
			'file',
		);

		$this->assertSame(Http::STATUS_CREATED, $response->getStatus());
		$auditEvents = $this->getAuditEvents();
		$this->assertCount(1, $auditEvents);
		$this->assertSame('cloud_federation_api.share.received', $auditEvents[0]->getOperation());
		$this->assertSame('A federated share of the %s "%s" with provider ID "%s" owned by "%s" was shared by "%s" with the %s "%s"', $auditEvents[0]->getLogMessage());
		$this->assertSame([
			'resourceType' => 'file',
			'name' => 'file.txt',
			'providerId' => '12',
			'owner' => 'alice@remote.example',
			'sharedBy' => 'alice@remote.example',
			'shareType' => 'user',
			'shareWith' => 'bob',
		], $auditEvents[0]->getParameters());
	}

	public function testAddShareWithMissingArgumentsDoesNotLog(): void {
		/** @psalm-suppress NullArgument, InvalidArgument The HTTP layer can pass missing parameters as null */
		$response = $this->controller->addShare(null, null, null, null, 'alice@remote.example', null, null, null, [], 'user', 'file');

		$this->assertSame(Http::STATUS_BAD_REQUEST, $response->getStatus());
		$this->assertSame([], $this->getAuditEvents());
	}

	public function testReceiveNotificationLogsCriticalAction(): void {
		$this->provider->expects($this->once())
			->method('notificationReceived')
			->with('SHARE_ACCEPTED', '12', ['sharedSecret' => 'secret'])
			->willReturn([]);

		$response = $this->controller->receiveNotification('SHARE_ACCEPTED', 'file', '12', ['sharedSecret' => 'secret'], null, null);

		$this->assertSame(Http::STATUS_CREATED, $response->getStatus());
		$auditEvents = $this->getAuditEvents();
		$this->assertCount(1, $auditEvents);
		$this->assertSame('cloud_federation_api.notification.received', $auditEvents[0]->getOperation());
		$this->assertSame('A federated %s notification was received for the %s share with provider ID "%s"', $auditEvents[0]->getLogMessage());
		$this->assertSame([
			'notificationType' => 'SHARE_ACCEPTED',
			'resourceType' => 'file',
			'providerId' => '12',
		], $auditEvents[0]->getParameters());
	}

	public function testReceiveNotificationForUnknownShareDoesNotLog(): void {
		$this->provider->method('notificationReceived')->willThrowException(new ShareNotFound());

		$response = $this->controller->receiveNotification('SHARE_ACCEPTED', 'file', '12', ['sharedSecret' => 'secret'], null, null);

		$this->assertSame(Http::STATUS_BAD_REQUEST, $response->getStatus());
		$this->assertSame([], $this->getAuditEvents());
	}

	/**
	 * @return list<OCMNotificationReceivedEvent>
	 */
	private function getOCMNotificationReceivedEvents(): array {
		return array_values(array_filter(
			$this->dispatchedEvents,
			static fn (Event $event): bool => $event instanceof OCMNotificationReceivedEvent,
		));
	}

	public function testReceiveNotificationWithSenderDomain(): void {
		$this->senderDomainAwareProvider->expects($this->once())
			->method('senderDomainAwareNotificationReceived')
			->with('CIRCLES_EVENT', null, ['event' => []], 'remote.example', null)
			->willReturn(['result' => []]);

		$response = $this->controller->receiveNotification('CIRCLES_EVENT', 'circle', null, ['event' => []], 'remote.example', null);

		$this->assertSame(Http::STATUS_CREATED, $response->getStatus());
		$this->assertSame(['result' => []], $response->getData());
		$events = $this->getOCMNotificationReceivedEvents();
		$this->assertCount(1, $events);
		$notification = $events[0]->getNotification();
		$this->assertInstanceOf(ISenderDomainAwareCloudFederationNotification::class, $notification);
		$this->assertSame('remote.example', $notification->getSenderDomain());
	}

	public function testReceiveNotificationWithSenderDomainConfirmsSharedSecretOrigin(): void {
		$this->senderDomainAwareProvider->method('getFederationIdFromSharedSecret')
			->with('secret', ['sharedSecret' => 'secret'])
			->willReturn('bob@remote.example');
		$this->ocmDiscoveryService->expects($this->once())
			->method('confirmRequestOrigin')
			->with('remote.example', 'bob@remote.example');
		$this->senderDomainAwareProvider->expects($this->once())->method('senderDomainAwareNotificationReceived')->willReturn([]);

		$response = $this->controller->receiveNotification('CIRCLES_EVENT', 'circle', null, ['sharedSecret' => 'secret'], 'remote.example', null);

		$this->assertSame(Http::STATUS_CREATED, $response->getStatus());
	}

	public function testReceiveNotificationWithSenderDomainRejectsSharedSecretFromOtherOrigin(): void {
		$this->senderDomainAwareProvider->method('getFederationIdFromSharedSecret')
			->willReturn('bob@other.example');
		$this->ocmDiscoveryService->method('confirmRequestOrigin')
			->with('remote.example', 'bob@other.example')
			->willThrowException(new IncomingRequestException('claimed origin does not match signed origin'));
		$this->senderDomainAwareProvider->expects($this->never())->method('senderDomainAwareNotificationReceived');

		$response = $this->controller->receiveNotification('CIRCLES_EVENT', 'circle', null, ['sharedSecret' => 'secret'], 'remote.example', null);

		$this->assertSame(Http::STATUS_BAD_REQUEST, $response->getStatus());
	}

	public function testReceiveNotificationWithSenderDomainWithoutResourceTypeAndShareType(): void {
		$response = $this->controller->receiveNotification('CIRCLES_EVENT', null, null, [], 'remote.example', null);

		$this->assertSame(Http::STATUS_BAD_REQUEST, $response->getStatus());
	}

	public function testReceiveNotificationWithSenderDomainForProviderNotSenderDomainAware(): void {
		$this->provider->expects($this->never())->method('notificationReceived');

		$response = $this->controller->receiveNotification('SHARE_ACCEPTED', 'file', null, [], 'remote.example', null);

		$this->assertSame(Http::STATUS_NOT_IMPLEMENTED, $response->getStatus());
		$this->assertSame([], $this->getOCMNotificationReceivedEvents());
	}

	public function testReceiveNotificationWithSenderDomainWithoutProvider(): void {
		$response = $this->controller->receiveNotification('SHARE_ACCEPTED', null, null, [], 'remote.example', 'user');

		$this->assertSame(Http::STATUS_NOT_IMPLEMENTED, $response->getStatus());
		$this->assertCount(1, $this->getOCMNotificationReceivedEvents());
	}
}

interface SignedSenderDomainAwareCloudFederationProvider extends ISenderDomainAwareCloudFederationProvider, ISignedCloudFederationProvider {
}
