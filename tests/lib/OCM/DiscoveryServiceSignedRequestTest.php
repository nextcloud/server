<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\OCM;

use OC\OCM\OCMDiscoveryService;
use OC\OCM\OCMSignatoryManager;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Federation\ICloudIdManager;
use OCP\Http\Client\IClientService;
use OCP\IAppConfig;
use OCP\ICacheFactory;
use OCP\IConfig;
use OCP\IURLGenerator;
use OCP\Security\Signature\Exceptions\IncomingRequestException;
use OCP\Security\Signature\Exceptions\SignatureNotFoundException;
use OCP\Security\Signature\IIncomingSignedRequest;
use OCP\Security\Signature\ISignatureManager;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class DiscoveryServiceSignedRequestTest extends TestCase {
	private ISignatureManager&MockObject $signatureManager;
	private OCMSignatoryManager&MockObject $signatoryManager;
	private OCMDiscoveryService $discoveryService;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->signatureManager = $this->createMock(ISignatureManager::class);
		$this->signatureManager->method('extractIdentityFromUri')
			->with('https://remote.example')
			->willReturn('remote.example');
		$this->signatoryManager = $this->createMock(OCMSignatoryManager::class);

		$this->discoveryService = new OCMDiscoveryService(
			$this->createMock(ICacheFactory::class),
			$this->createMock(IClientService::class),
			$this->createMock(IEventDispatcher::class),
			$this->createMock(IConfig::class),
			$this->createMock(IAppConfig::class),
			$this->createMock(IURLGenerator::class),
			$this->signatureManager,
			$this->signatoryManager,
			$this->createMock(ICloudIdManager::class),
			$this->createMock(LoggerInterface::class),
		);
	}

	private function mockSignedRequest(string $origin): IIncomingSignedRequest&MockObject {
		$signedRequest = $this->createMock(IIncomingSignedRequest::class);
		$signedRequest->method('getOrigin')->willReturn($origin);
		return $signedRequest;
	}

	public function testGetIncomingSignedRequestFromDomain(): void {
		$signedRequest = $this->mockSignedRequest('remote.example');
		$this->signatureManager->expects($this->once())
			->method('getIncomingSignedRequest')
			->with($this->signatoryManager, null, 'remote.example')
			->willReturn($signedRequest);

		$this->assertSame($signedRequest, $this->discoveryService->getIncomingSignedRequestFromDomain('remote.example'));
	}

	public function testGetIncomingSignedRequestFromDomainRejectsOtherOrigin(): void {
		$this->signatureManager->method('getIncomingSignedRequest')->willReturn($this->mockSignedRequest('other.example'));

		$this->expectException(IncomingRequestException::class);
		$this->discoveryService->getIncomingSignedRequestFromDomain('remote.example');
	}

	public function testGetIncomingSignedRequestFromDomainRejectsUnsignedRequest(): void {
		$this->signatureManager->method('getIncomingSignedRequest')->willThrowException(new SignatureNotFoundException());

		$this->expectException(IncomingRequestException::class);
		$this->discoveryService->getIncomingSignedRequestFromDomain('remote.example');
	}
}
