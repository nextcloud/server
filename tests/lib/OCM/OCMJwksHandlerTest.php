<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\OCM;

use OC\OCM\OCMJwksHandler;
use OC\OCM\OCMSignatoryManager;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Http\WellKnown\GenericResponse;
use OCP\Http\WellKnown\IRequestContext;
use OCP\Http\WellKnown\IResponse;
use OCP\Http\WellKnown\JrdResponse;
use OCP\IAppConfig;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class OCMJwksHandlerTest extends TestCase {
	private IAppConfig&MockObject $appConfig;
	private OCMSignatoryManager&MockObject $signatoryManager;
	private LoggerInterface&MockObject $logger;
	private IRequestContext&MockObject $context;
	private OCMJwksHandler $handler;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->signatoryManager = $this->createMock(OCMSignatoryManager::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->context = $this->createMock(IRequestContext::class);

		$this->handler = new OCMJwksHandler(
			$this->appConfig,
			$this->signatoryManager,
			$this->logger,
		);
	}

	public function testIgnoresUnrelatedService(): void {
		$previous = new JrdResponse('foo');
		$result = $this->handler->handle('webfinger', $this->context, $previous);
		$this->assertSame($previous, $result);
	}

	public function testEmptyKeySetWhenSigningDisabled(): void {
		$this->appConfig->method('getValueBool')
			->with('core', OCMSignatoryManager::APPCONFIG_SIGN_DISABLED, false, true)
			->willReturn(true);
		$this->signatoryManager->expects($this->never())->method('getLocalJwks');

		$body = $this->jsonBody($this->handler->handle('jwks.json', $this->context, null));
		$this->assertSame(['keys' => []], $body);
	}

	public function testPublishesJwksWhenAvailable(): void {
		$this->appConfig->method('getValueBool')->willReturn(false);
		$jwk = [
			'kty' => 'EC',
			'crv' => 'P-256',
			'kid' => 'https://example.org/ocm#ecdsa-p256-sha256',
			'alg' => 'ES256',
			'use' => 'sig',
			'x' => 'AAAA',
			'y' => 'BBBB',
		];
		$this->signatoryManager->method('getLocalJwks')->willReturn([$jwk]);

		$body = $this->jsonBody($this->handler->handle('jwks.json', $this->context, null));
		$this->assertSame(['keys' => [$jwk]], $body);
	}

	public function testPublishesAllSlotsAdvertisedDuringRotation(): void {
		$this->appConfig->method('getValueBool')->willReturn(false);
		$active = [
			'kty' => 'EC', 'crv' => 'P-256', 'kid' => 'kid-1', 'alg' => 'ES256', 'use' => 'sig', 'x' => 'AAAA', 'y' => 'BBBB',
		];
		$pending = [
			'kty' => 'EC', 'crv' => 'P-256', 'kid' => 'kid-2', 'alg' => 'ES256', 'use' => 'sig', 'x' => 'CCCC', 'y' => 'DDDD',
		];
		$this->signatoryManager->method('getLocalJwks')->willReturn([$active, $pending]);

		$body = $this->jsonBody($this->handler->handle('jwks.json', $this->context, null));
		$this->assertSame(['keys' => [$active, $pending]], $body);
	}

	public function testEmptyKeySetWhenSignatoryUnavailable(): void {
		$this->appConfig->method('getValueBool')->willReturn(false);
		$this->signatoryManager->method('getLocalJwks')->willReturn([]);

		$body = $this->jsonBody($this->handler->handle('jwks.json', $this->context, null));
		$this->assertSame(['keys' => []], $body);
	}

	public function testFailingJwkBuildIsLoggedAndYieldsEmptyKeySet(): void {
		$this->appConfig->method('getValueBool')->willReturn(false);
		$this->signatoryManager->method('getLocalJwks')
			->willThrowException(new \RuntimeException('boom'));
		$this->logger->expects($this->once())->method('warning');

		$body = $this->jsonBody($this->handler->handle('jwks.json', $this->context, null));
		$this->assertSame(['keys' => []], $body);
	}

	private function jsonBody(?IResponse $response): array {
		$this->assertInstanceOf(GenericResponse::class, $response);
		$http = $response->toHttpResponse();
		$this->assertInstanceOf(JSONResponse::class, $http);
		return $http->getData();
	}
}
