<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\OCM;

use OC\OCM\OCMSignatoryManager;
use OC\Security\IdentityProof\Manager as IdentityProofManager;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\Http\Client\IResponse;
use OCP\IAppConfig;
use OCP\ICacheFactory;
use OCP\IConfig;
use OCP\IURLGenerator;
use OCP\Security\Signature\ISignatureManager;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class OCMSignatoryManagerJwksTest extends TestCase {
	/** RFC 7517 §A.1 test vector for an EC P-256 public key. */
	private const TEST_X = 'f83OJ3D2xF1Bg8vub9tLe1gHMzV76e8Tus9uPHvRVEU';
	private const TEST_Y = 'x_FEzRu9m36HLN_tue659LNpXW6pCyStikYjKIWI5a0';

	private IAppConfig&MockObject $appConfig;
	private ISignatureManager&MockObject $signatureManager;
	private IURLGenerator&MockObject $urlGenerator;
	private IdentityProofManager&MockObject $identityProofManager;
	private IClientService&MockObject $clientService;
	private IConfig&MockObject $config;
	private LoggerInterface&MockObject $logger;
	private IClient&MockObject $client;
	private OCMSignatoryManager $signatoryManager;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->signatureManager = $this->createMock(ISignatureManager::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->identityProofManager = $this->createMock(IdentityProofManager::class);
		$this->clientService = $this->createMock(IClientService::class);
		$this->config = $this->createMock(IConfig::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->client = $this->createMock(IClient::class);

		$this->clientService->method('newClient')->willReturn($this->client);

		$cacheFactory = $this->createMock(ICacheFactory::class);
		$cacheFactory->method('createDistributed')->willReturn(new \OC\Memcache\ArrayCache(''));

		$this->signatoryManager = new OCMSignatoryManager(
			$this->appConfig,
			$this->signatureManager,
			$this->urlGenerator,
			$this->identityProofManager,
			$this->clientService,
			$this->config,
			$cacheFactory,
			$this->logger,
		);
	}

	public function testGetRemoteKeyFetchesAndMatchesByKid(): void {
		$kid = 'sender.example.org#key1';
		$jwks = [
			'keys' => [
				$this->ecJwk('other'),
				$this->ecJwk($kid),
			],
		];
		$this->respondWith($jwks);

		$key = $this->signatoryManager->getRemoteKey('sender.example.org', $kid);
		$this->assertNotNull($key);
		$this->assertSame('ES256', $key->getAlgorithm());
	}

	public function testGetRemoteKeyReturnsNullWhenKidMissing(): void {
		$this->respondWith(['keys' => [$this->ecJwk('unrelated')]]);
		$this->assertNull($this->signatoryManager->getRemoteKey('sender.example.org', 'other-kid'));
	}

	public function testGetRemoteKeyReturnsNullOnHttpError(): void {
		$this->client->method('get')->willThrowException(new \RuntimeException('boom'));
		$this->logger->expects($this->once())->method('warning');
		$this->assertNull($this->signatoryManager->getRemoteKey('sender.example.org', 'kid'));
	}

	public function testGetRemoteKeyReturnsNullOnInvalidJson(): void {
		$response = $this->createMock(IResponse::class);
		$response->method('getBody')->willReturn('not json');
		$this->client->method('get')->willReturn($response);
		$this->logger->expects($this->once())->method('warning');
		$this->assertNull($this->signatoryManager->getRemoteKey('sender.example.org', 'kid'));
	}

	public function testGetRemoteKeyReturnsNullWhenKeysMissing(): void {
		$this->respondWith(['no-keys-here' => []]);
		$this->assertNull($this->signatoryManager->getRemoteKey('sender.example.org', 'kid'));
	}

	public function testGetRemoteKeyReturnsNullOnUnparseableJwk(): void {
		// JWK with kty=EC but no crv: parseKey rejects.
		$this->respondWith(['keys' => [['kty' => 'EC', 'kid' => 'kid', 'x' => self::TEST_X, 'y' => self::TEST_Y]]]);
		$this->logger->expects($this->once())->method('warning');
		$this->assertNull($this->signatoryManager->getRemoteKey('sender.example.org', 'kid'));
	}

	public function testGetRemoteKeyUsesWellKnownPath(): void {
		$this->client->expects($this->once())
			->method('get')
			->with(
				$this->equalTo('https://sender.example.org/.well-known/jwks.json'),
				$this->isType('array'),
			)
			->willReturn($this->jsonResponse(['keys' => []]));

		$this->signatoryManager->getRemoteKey('sender.example.org', 'kid');
	}

	public function testGetRemoteKeyPassesSelfSignedFlagThrough(): void {
		$this->config->method('getSystemValueBool')
			->with('sharing.federation.allowSelfSignedCertificates')
			->willReturn(true);

		$this->client->expects($this->once())
			->method('get')
			->with(
				$this->anything(),
				$this->callback(static fn (array $opts): bool => ($opts['verify'] ?? null) === false),
			)
			->willReturn($this->jsonResponse(['keys' => []]));

		$this->signatoryManager->getRemoteKey('sender.example.org', 'kid');
	}

	public function testJwksCachedAcrossCallsToTheSameOrigin(): void {
		$kid = 'sender.example.org#key1';
		$jwks = ['keys' => [$this->ecJwk($kid)]];
		$this->client->expects($this->once())
			->method('get')
			->willReturn($this->jsonResponse($jwks));

		$this->assertNotNull($this->signatoryManager->getRemoteKey('sender.example.org', $kid));
		$this->assertNotNull($this->signatoryManager->getRemoteKey('sender.example.org', $kid));
	}

	public function testCacheMissOnNewKidTriggersRefetchOnce(): void {
		$first = ['keys' => [$this->ecJwk('old')]];
		$second = ['keys' => [$this->ecJwk('new')]];
		$this->client->expects($this->exactly(2))
			->method('get')
			->willReturnOnConsecutiveCalls(
				$this->jsonResponse($first),
				$this->jsonResponse($second),
			);

		$this->assertNotNull($this->signatoryManager->getRemoteKey('sender.example.org', 'old'));
		$this->assertNotNull($this->signatoryManager->getRemoteKey('sender.example.org', 'new'));
	}

	private function respondWith(array $body): void {
		$this->client->method('get')->willReturn($this->jsonResponse($body));
	}

	private function jsonResponse(array $body): IResponse {
		$response = $this->createMock(IResponse::class);
		$response->method('getBody')->willReturn(json_encode($body, JSON_THROW_ON_ERROR));
		return $response;
	}

	/** @return array<string, string> */
	private function ecJwk(string $kid): array {
		return [
			'kty' => 'EC',
			'crv' => 'P-256',
			'kid' => $kid,
			'alg' => 'ES256',
			'use' => 'sig',
			'x' => self::TEST_X,
			'y' => self::TEST_Y,
		];
	}
}
