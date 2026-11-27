<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Federation;

use OC\EventDispatcher\EventDispatcher;
use OC\Federation\CloudFederationProviderManager;
use OC\Federation\CloudFederationShare;
use OC\OCM\OCMSignatoryManager;
use OCP\App\IAppManager;
use OCP\Federation\ICloudId;
use OCP\Federation\ICloudIdManager;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\Http\Client\IResponse;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\OCM\Events\BeforeOCMShareSentEvent;
use OCP\OCM\Exceptions\OCMProviderException;
use OCP\OCM\IOCMDiscoveryService;
use OCP\Security\Signature\ISignatureManager;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\EventDispatcher as SymfonyDispatcher;
use Test\TestCase;

class CloudFederationProviderManagerTest extends TestCase {

	private EventDispatcher $dispatcher;
	private IOCMDiscoveryService&MockObject $discovery;
	private IClient&MockObject $client;
	private CloudFederationProviderManager $manager;

	protected function setUp(): void {
		parent::setUp();
		$logger = new NullLogger();
		$this->dispatcher = new EventDispatcher(new SymfonyDispatcher(), $this->createStub(ContainerInterface::class), $logger);
		$this->discovery = $this->createMock(IOCMDiscoveryService::class);
		$this->client = $this->createMock(IClient::class);
		$clientService = $this->createStub(IClientService::class);
		$clientService->method('newClient')->willReturn($this->client);
		$cloudId = $this->createStub(ICloudId::class);
		$cloudId->method('getRemote')->willReturn('https://remote.example');
		$cloudIds = $this->createStub(ICloudIdManager::class);
		$cloudIds->method('resolveCloudId')->willReturn($cloudId);
		$this->manager = new CloudFederationProviderManager(
			$this->createStub(IConfig::class),
			$this->createStub(IAppManager::class),
			$this->createStub(IAppConfig::class),
			$clientService,
			$cloudIds,
			$this->discovery,
			$this->createStub(ISignatureManager::class),
			$this->createStub(OCMSignatoryManager::class),
			$logger,
			$this->dispatcher,
		);
	}

	public static function sendMethods(): array {
		return [['sendShare'], ['sendCloudShare']];
	}

	#[DataProvider('sendMethods')]
	public function testUnmodifiedShareUsesExistingTransport(string $method): void {
		$share = $this->share();
		$this->expectDelivery($share->getShare(), $method);
		$this->manager->$method($share);
	}

	#[DataProvider('sendMethods')]
	public function testListenersPrepareOneCombinedShare(string $method): void {
		$share = $this->share();
		$this->dispatcher->addListener(BeforeOCMShareSentEvent::class, static function (BeforeOCMShareSentEvent $event): void {
			$share = $event->getShare();
			$share->setResourceType('folder');
			$share->setProtocol([
				'name' => 'multi',
				'webdav' => $share->getProtocol()['options'],
				'webapp' => ['uri' => 'https://local.example/open', 'targets' => ['blank']],
			]);
		});
		$this->dispatcher->addListener(BeforeOCMShareSentEvent::class, static function (BeforeOCMShareSentEvent $event): void {
			$share = $event->getShare();
			$protocol = $share->getProtocol();
			$protocol['webapp']['apps'] = [['name' => 'Example', 'mediaTypes' => ['text/plain']]];
			$share->setProtocol($protocol);
		});
		$expected = $share->getShare();
		$expected['resourceType'] = 'folder';
		$expected['protocol'] = [
			'name' => 'multi',
			'webdav' => $share->getProtocol()['options'],
			'webapp' => [
				'uri' => 'https://local.example/open',
				'targets' => ['blank'],
				'apps' => [['name' => 'Example', 'mediaTypes' => ['text/plain']]],
			],
		];
		$this->expectDelivery($expected, $method);
		$this->manager->$method($share);
	}

	#[DataProvider('sendMethods')]
	public function testListenerCanAbortBeforeAnyDelivery(string $method): void {
		$this->dispatcher->addListener(BeforeOCMShareSentEvent::class, static function (): void {
			throw new \DomainException('The requested protocol is not supported.');
		});
		$this->discovery->expects($this->never())->method('requestRemoteOcmEndpoint');
		$this->client->expects($this->never())->method('getResponseFromThrowable');
		$this->expectException(\DomainException::class);
		$this->manager->$method($this->share());
	}

	#[DataProvider('sendMethods')]
	public function testSendsCustomResourceAndRecipientTypesWithEncryption(string $method): void {
		$share = $this->share();
		$share->setResourceType('calendar');
		$share->setProtocol(['name' => 'caldav', 'caldav' => ['uri' => 'https://local.example/calendar']]);
		$encryption = ['scheme' => 'example', 'resourceId' => 'resource-id'];
		$this->dispatcher->addListener(BeforeOCMShareSentEvent::class, static function (BeforeOCMShareSentEvent $event) use ($encryption): void {
			$event->setShareType('example-recipient');
			$event->setEncryption($encryption);
		});
		$expected = $share->getShare();
		$expected['shareType'] = 'example-recipient';
		$expected['encryption'] = $encryption;
		$this->expectDelivery($expected, $method);
		$this->manager->$method($share);
		self::assertSame('user', $share->getShareType());
		self::assertArrayNotHasKey('encryption', $share->getShare());
	}

	public static function legacyFailureCases(): iterable {
		$cases = [
			'WebDAV to a user' => ['user', 'file', 'webdav', false, false],
			'WebDAV to a group' => ['group', 'file', 'webdav', false, false],
			'multiple protocols' => ['user', 'file', 'multi', false, true],
			'webapp only' => ['user', 'file', 'webapp', false, true],
			'other resource type' => ['user', 'calendar', 'webdav', false, true],
			'other recipient type' => ['example-recipient', 'file', 'webdav', false, true],
			'WebDAV with encryption metadata' => ['user', 'file', 'webdav', true, true],
		];
		foreach ($cases as $name => $case) {
			yield "$name: transport failure" => [...$case, false];
			yield "$name: remote rejection" => [...$case, true];
		}
	}

	#[DataProvider('legacyFailureCases')]
	public function testLegacyFallbackRequiresOrdinaryFileShare(string $shareType, string $resourceType, string $protocol, bool $encrypted, bool $mustThrow, bool $rejected): void {
		$share = $this->share();
		$this->dispatcher->addListener(BeforeOCMShareSentEvent::class, static function (BeforeOCMShareSentEvent $event) use ($shareType, $resourceType, $protocol, $encrypted): void {
			$event->setShareType($shareType);
			$event->getShare()->setResourceType($resourceType);
			$event->getShare()->setProtocol(['name' => $protocol]);
			if ($encrypted) {
				$event->setEncryption(['scheme' => 'example']);
			}
		});
		$request = $this->discovery->expects($this->once())->method('requestRemoteOcmEndpoint');
		if ($rejected) {
			$response = $this->createStub(IResponse::class);
			$response->method('getStatusCode')->willReturn(400);
			$request->willReturn($response);
		} else {
			$request->willThrowException(new OCMProviderException('unavailable'));
		}
		if ($mustThrow) {
			$this->expectException(OCMProviderException::class);
		}
		self::assertFalse($this->manager->sendShare($share));
	}

	private function share(): CloudFederationShare {
		return new CloudFederationShare(
			shareWith: 'recipient@remote.example',
			providerId: '123',
			shareType: 'user',
			resourceType: 'file',
			sharedSecret: 'share-secret',
		);
	}

	private function expectDelivery(array $payload, string $method): void {
		$response = $this->createStub(IResponse::class);
		$response->method('getStatusCode')->willReturn(201);
		$response->method('getBody')->willReturn('{"providerId":"remote-id"}');
		$this->discovery->expects($this->once())
			->method('requestRemoteOcmEndpoint')
			->with(null, 'https://remote.example', '/shares', $payload, 'post', $method === 'sendShare' ? null : $this->client, ['verify' => true])
			->willReturn($response);
	}
}
