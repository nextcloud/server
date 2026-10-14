<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Federation;

use OC\Federation\CloudFederationFactory;
use OC\OCM\Model\OCMProvider;
use OCP\Federation\ICloudFederationShare;
use OCP\Federation\ICloudId;
use OCP\Federation\ICloudIdManager;
use OCP\IURLGenerator;
use OCP\OCM\IOCMDiscoveryService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class CloudFederationFactoryTest extends TestCase {

	private IOCMDiscoveryService&MockObject $discoveryService;
	private IURLGenerator&MockObject $urlGenerator;
	private LoggerInterface&MockObject $logger;
	private OCMProvider $remoteProvider;
	private CloudFederationFactory $factory;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->discoveryService = $this->createMock(IOCMDiscoveryService::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->remoteProvider = new OCMProvider();

		$cloudId = $this->createMock(ICloudId::class);
		$cloudId->method('getRemote')->willReturn('https://remote.example');
		$cloudIdManager = $this->createMock(ICloudIdManager::class);
		$cloudIdManager->expects($this->once())
			->method('resolveCloudId')
			->with('recipient@remote.example')
			->willReturn($cloudId);
		$this->discoveryService->expects($this->once())
			->method('discover')
			->with('https://remote.example')
			->willReturn($this->remoteProvider);

		$this->factory = new CloudFederationFactory(
			$this->discoveryService,
			$cloudIdManager,
			$this->urlGenerator,
			$this->logger,
		);
	}

	public function testExchangeTokenUsesLocalWebDavUri(): void {
		$this->remoteProvider->setCapabilities(['exchange-token']);
		$localProvider = new OCMProvider();
		$resource = $localProvider->createNewResourceType();
		$resource->setName('file')->setProtocols(['webdav' => '/public.php/webdav/']);
		$localProvider->addResourceType($resource);
		$this->discoveryService->expects($this->once())
			->method('getLocalOCMProvider')
			->with(false)
			->willReturn($localProvider);
		$this->urlGenerator->expects($this->once())
			->method('getAbsoluteURL')
			->with('/public.php/webdav/')
			->willReturn('https://local.example/nextcloud/public.php/webdav/');
		$this->logger->expects($this->never())->method('warning');

		$share = $this->createShare();

		$this->assertSame([
			'name' => 'webdav',
			'webdav' => [
				'uri' => 'https://local.example/nextcloud/public.php/webdav/',
				'sharedSecret' => 'share-secret',
				'permissions' => ['{http://open-cloud-mesh.org/ns}share-permissions'],
			],
		], $share->getProtocol());
		$this->assertSame('share-secret', $share->getShareSecret());
	}

	public static function missingLocalWebDavProvider(): array {
		return [
			'missing file resource' => [false, 'resource not found'],
			'missing webdav protocol' => [true, 'protocol not found'],
		];
	}

	#[DataProvider('missingLocalWebDavProvider')]
	public function testMissingLocalWebDavUsesLegacyShare(bool $hasFileResource, string $exceptionMessage): void {
		$this->remoteProvider->setCapabilities(['exchange-token']);
		$localProvider = new OCMProvider();
		if ($hasFileResource) {
			$resource = $localProvider->createNewResourceType();
			$resource->setName('file')->setProtocols(['webapp' => '/files/']);
			$localProvider->addResourceType($resource);
		}
		$this->discoveryService->expects($this->once())
			->method('getLocalOCMProvider')
			->with(false)
			->willReturn($localProvider);
		$this->urlGenerator->expects($this->never())->method('getAbsoluteURL');
		$this->logger->expects($this->once())
			->method('warning')
			->with('No local OCM webdav entry, using legacy share method', [
				'exception' => $exceptionMessage,
			]);

		$this->assertLegacyShare($this->createShare());
	}

	public function testLegacyShareDoesNotQueryLocalDiscovery(): void {
		$this->remoteProvider->setCapabilities(['shares']);
		$this->discoveryService->expects($this->never())->method('getLocalOCMProvider');
		$this->urlGenerator->expects($this->never())->method('getAbsoluteURL');
		$this->logger->expects($this->never())->method('warning');

		$this->assertLegacyShare($this->createShare());
	}

	private function createShare(): ICloudFederationShare {
		return $this->factory->getCloudFederationShare(
			'recipient@remote.example',
			'document.odt',
			'',
			'123',
			'owner@local.example',
			'Owner',
			'owner@local.example',
			'Owner',
			'share-secret',
			'user',
			'file',
		);
	}

	private function assertLegacyShare(ICloudFederationShare $share): void {
		$this->assertSame([
			'name' => 'webdav',
			'options' => [
				'sharedSecret' => 'share-secret',
				'permissions' => '{http://open-cloud-mesh.org/ns}share-permissions',
			],
		], $share->getProtocol());
		$this->assertSame('share-secret', $share->getShareSecret());
	}
}
