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
use OCP\AppFramework\Http\JSONResponse;
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
use OCP\OCM\Events\OCMShareRequestEvent;
use OCP\OCM\IOCMDiscoveryService;
use OCP\Security\Signature\Exceptions\IncomingRequestException;
use OCP\Security\Signature\IIncomingSignedRequest;
use OCP\Share\Exceptions\ShareNotFound;
use PHPUnit\Framework\Attributes\DataProvider;
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
	private IOCMDiscoveryService&MockObject $discoveryService;
	private RequestHandlerController $controller;
	private bool $signingDisabled = true;
	private ?JSONResponse $listenerResponse = null;

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
		$this->discoveryService = $this->createMock(IOCMDiscoveryService::class);

		$appConfig = $this->createMock(IAppConfig::class);
		$appConfig->method('getValueBool')
			->with('core', OCMSignatoryManager::APPCONFIG_SIGN_DISABLED, false, true)
			->willReturnCallback(fn (): bool => $this->signingDisabled);

		$this->providerManager->method('getCloudFederationProvider')->with('file')->willReturn($this->provider);
		$this->dispatcher->method('dispatchTyped')
			->willReturnCallback(function (Event $event): void {
				$this->dispatchedEvents[] = $event;
				if ($event instanceof OCMShareRequestEvent && $this->listenerResponse !== null) {
					$event->setResponse($this->listenerResponse);
				}
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
			$this->discoveryService,
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

	/** @return list<OCMShareRequestEvent> */
	private function getShareRequestEvents(): array {
		return array_values(array_filter(
			$this->dispatchedEvents,
			static fn (Event $event): bool => $event instanceof OCMShareRequestEvent,
		));
	}

	public function testAddShareLogsCriticalAction(): void {
		$this->config->method('getSupportedShareTypes')->with('file')->willReturn(['user']);
		$this->mockRecipient();
		$this->factory->method('getCloudFederationShare')->willReturn($this->createMock(ICloudFederationShare::class));
		$this->provider->expects($this->once())->method('shareReceived');

		$response = $this->controller->addShare(...$this->sharePayload());

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
		$this->assertSame([], $this->getShareRequestEvents());
		$this->assertSame([], $this->getAuditEvents());
	}

	public static function appHandledShares(): array {
		return [
			'created signed share' => [true, Http::STATUS_CREATED],
			'rejected unsigned share' => [false, Http::STATUS_BAD_REQUEST],
		];
	}

	#[DataProvider(methodName: 'appHandledShares')]
	public function testListenerCanHandleCustomRecipientWithEncryption(bool $signed, int $status): void {
		$this->mockRecipient();
		$payload = $this->sharePayload('example-recipient');
		$payload['encryption'] = ['scheme' => 'example', 'resourceId' => 'resource-id'];
		$signedRequest = $signed ? $this->mockSignedRequest($payload) : null;
		if ($signed) {
			$this->discoveryService->expects($this->once())->method('confirmRequestOrigin')->with('remote.example', $payload['owner']);
		}
		$this->listenerResponse = new JSONResponse([], $status);
		$this->config->expects($this->never())->method('getSupportedShareTypes');
		$this->provider->expects($this->never())->method('shareReceived');

		$this->assertSame($this->listenerResponse, $this->controller->addShare(...$payload));
		$events = $this->getShareRequestEvents();
		$this->assertCount(1, $events);
		$this->assertSame($signedRequest, $events[0]->getSignedRequest());
		$payload['sharedBy'] = $payload['owner'];
		$payload['sharedByDisplayName'] = $payload['ownerDisplayName'];
		$this->assertSame($payload, $events[0]->getPayload());
		$this->assertTrue($events[0]->isPropagationStopped());
		$audit = $this->getAuditEvents();
		$this->assertCount($status === Http::STATUS_CREATED ? 1 : 0, $audit);
		if ($status === Http::STATUS_CREATED) {
			$this->assertSame('cloud_federation_api.share.received', $audit[0]->getOperation());
		}
	}

	public function testUnhandledEncryptionIsNotSentToTheBuiltInProvider(): void {
		$this->mockRecipient();
		$this->config->method('getSupportedShareTypes')->with('file')->willReturn(['user']);
		$this->provider->expects($this->never())->method('shareReceived');

		$response = $this->controller->addShare(...$this->sharePayload(), encryption: ['scheme' => 'example']);

		$this->assertSame(Http::STATUS_NOT_IMPLEMENTED, $response->getStatus());
		$this->assertCount(1, $this->getShareRequestEvents());
		$this->assertSame([], $this->getAuditEvents());
	}

	public static function invalidEncryptionMetadata(): array {
		return [
			'not an object' => ['invalid'],
			'invalid scheme' => [['scheme' => false]],
		];
	}

	#[DataProvider(methodName: 'invalidEncryptionMetadata')]
	public function testInvalidEncryptionMetadataIsNotDispatched(mixed $encryption): void {
		$response = $this->controller->addShare(...$this->sharePayload(), encryption: $encryption);

		$this->assertSame(Http::STATUS_BAD_REQUEST, $response->getStatus());
		$this->assertSame([], $this->getShareRequestEvents());
	}

	public function testVerifiedRequestDoesNotDispatchShareFieldsOutsideTheSignedBody(): void {
		$payload = $this->sharePayload();
		$this->mockSignedRequest($payload);
		$payload['encryption'] = ['scheme' => 'example'];

		$response = $this->controller->addShare(...$payload);

		$this->assertSame(Http::STATUS_BAD_REQUEST, $response->getStatus());
		$this->assertSame([], $this->getShareRequestEvents());
	}

	public function testFailedSignatureVerificationDoesNotDispatchShare(): void {
		$this->signingDisabled = false;
		$this->discoveryService->expects($this->once())->method('getIncomingSignedRequest')
			->willThrowException(new IncomingRequestException('Invalid signature'));

		$response = $this->controller->addShare(...$this->sharePayload());

		$this->assertSame(Http::STATUS_BAD_REQUEST, $response->getStatus());
		$this->assertSame([], $this->getShareRequestEvents());
	}

	private function mockRecipient(): void {
		$cloudId = $this->createStub(ICloudId::class);
		$cloudId->method('getUser')->willReturn('bob');
		$this->cloudIdManager->method('resolveCloudId')->with('bob@local.example')->willReturn($cloudId);
		$this->userManager->method('getUserNameFromLoginName')->with('bob')->willReturn('bob');
		$this->userManager->method('userExists')->with('bob')->willReturn(true);
	}

	private function mockSignedRequest(array $payload): IIncomingSignedRequest {
		$this->signingDisabled = false;
		$request = $this->createStub(IIncomingSignedRequest::class);
		$request->method('getOrigin')->willReturn('remote.example');
		$request->method('getBody')->willReturn(json_encode($payload, JSON_THROW_ON_ERROR));
		$this->discoveryService->expects($this->once())->method('getIncomingSignedRequest')->with('alice@remote.example')->willReturn($request);
		return $request;
	}

	/**
	 * @return array{shareWith: string, name: string, description: null, providerId: string, owner: string, ownerDisplayName: string, sharedBy: null, sharedByDisplayName: null, protocol: array{name: string, options: array{sharedSecret: string}}, shareType: string, resourceType: string}
	 */
	private function sharePayload(string $shareType = 'user'): array {
		return [
			'shareWith' => 'bob@local.example',
			'name' => 'file.txt',
			'description' => null,
			'providerId' => '12',
			'owner' => 'alice@remote.example',
			'ownerDisplayName' => 'Alice',
			'sharedBy' => null,
			'sharedByDisplayName' => null,
			'protocol' => ['name' => 'webdav', 'options' => ['sharedSecret' => 'secret']],
			'shareType' => $shareType,
			'resourceType' => 'file',
		];
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
