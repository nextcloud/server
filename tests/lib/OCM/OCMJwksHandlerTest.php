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
		$this->signatoryManager->expects($this->never())->method('getLocalEd25519Jwks');

		$body = $this->jsonBody($this->handler->handle('jwks.json', $this->context, null));
		$this->assertSame(['keys' => []], $body);
	}

	public function testPublishesEd25519JwksWhenAvailable(): void {
		$this->appConfig->method('getValueBool')->willReturn(false);
		$jwk = [
			'kty' => 'OKP',
			'crv' => 'Ed25519',
			'kid' => 'https://example.org/ocm#ed25519',
			'alg' => 'EdDSA',
			'use' => 'sig',
			'x' => 'AAAA',
		];
		$this->signatoryManager->method('getLocalEd25519Jwks')->willReturn([$jwk]);

		$body = $this->jsonBody($this->handler->handle('jwks.json', $this->context, null));
		$this->assertSame(['keys' => [$jwk]], $body);
	}

	public function testPublishesAllSlotsAdvertisedDuringRotation(): void {
		$this->appConfig->method('getValueBool')->willReturn(false);
		$active = [
			'kty' => 'OKP', 'crv' => 'Ed25519', 'kid' => 'kid-1', 'alg' => 'EdDSA', 'use' => 'sig', 'x' => 'AAAA',
		];
		$pending = [
			'kty' => 'OKP', 'crv' => 'Ed25519', 'kid' => 'kid-2', 'alg' => 'EdDSA', 'use' => 'sig', 'x' => 'BBBB',
		];
		$this->signatoryManager->method('getLocalEd25519Jwks')->willReturn([$active, $pending]);

		$body = $this->jsonBody($this->handler->handle('jwks.json', $this->context, null));
		$this->assertSame(['keys' => [$active, $pending]], $body);
	}

	public function testEmptyKeySetWhenSignatoryUnavailable(): void {
		$this->appConfig->method('getValueBool')->willReturn(false);
		$this->signatoryManager->method('getLocalEd25519Jwks')->willReturn([]);

		$body = $this->jsonBody($this->handler->handle('jwks.json', $this->context, null));
		$this->assertSame(['keys' => []], $body);
	}

	public function testFailingJwkBuildIsLoggedAndYieldsEmptyKeySet(): void {
		$this->appConfig->method('getValueBool')->willReturn(false);
		$this->signatoryManager->method('getLocalEd25519Jwks')
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
