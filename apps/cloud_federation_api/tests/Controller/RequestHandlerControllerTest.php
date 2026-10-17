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
use OCP\Federation\ICloudFederationFactory;
use OCP\Federation\ICloudFederationNotification;
use OCP\Federation\ICloudFederationProvider;
use OCP\Federation\ICloudFederationProviderManager;
use OCP\Federation\ICloudFederationShare;
use OCP\Federation\ICloudId;
use OCP\Federation\ICloudIdManager;
use OCP\IAppConfig;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\Log\Audit\CriticalActionPerformedEvent;
use OCP\OCM\IOCMDiscoveryService;
use OCP\Share\Exceptions\ShareNotFound;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class RequestHandlerControllerTest extends TestCase {
	private IUserManager&MockObject $userManager;
	private ICloudFederationProviderManager&MockObject $providerManager;
	private ICloudFederationProvider&MockObject $provider;
	private Config&MockObject $config;
	private IEventDispatcher&MockObject $dispatcher;
	private ICloudFederationFactory&MockObject $factory;
	private ICloudIdManager&MockObject $cloudIdManager;
	private RequestHandlerController $controller;

	/** @var list<Event> */
	private array $dispatchedEvents = [];

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->userManager = $this->createMock(IUserManager::class);
		$this->providerManager = $this->createMock(ICloudFederationProviderManager::class);
		$this->provider = $this->createMock(ICloudFederationProvider::class);
		$this->config = $this->createMock(Config::class);
		$this->dispatcher = $this->createMock(IEventDispatcher::class);
		$this->factory = $this->createMock(ICloudFederationFactory::class);
		$this->cloudIdManager = $this->createMock(ICloudIdManager::class);

		$appConfig = $this->createMock(IAppConfig::class);
		$appConfig->method('getValueBool')
			->with('core', OCMSignatoryManager::APPCONFIG_SIGN_DISABLED, false, true)
			->willReturn(true);

		$this->providerManager->method('getCloudFederationProvider')->with('file')->willReturn($this->provider);
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
			$this->createMock(IOCMDiscoveryService::class),
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
		$this->factory->method('getCloudFederationNotification')->willReturn($this->createMock(ICloudFederationNotification::class));

		$response = $this->controller->receiveNotification('SHARE_ACCEPTED', 'file', '12', ['sharedSecret' => 'secret']);

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

		$response = $this->controller->receiveNotification('SHARE_ACCEPTED', 'file', '12', ['sharedSecret' => 'secret']);

		$this->assertSame(Http::STATUS_BAD_REQUEST, $response->getStatus());
		$this->assertSame([], $this->getAuditEvents());
	}
}
