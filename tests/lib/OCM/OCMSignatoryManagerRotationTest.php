<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\OCM;

use OC\OCM\OCMSignatoryManager;
use OC\Security\IdentityProof\Key;
use OC\Security\IdentityProof\Manager as IdentityProofManager;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\IAppConfig;
use OCP\ICacheFactory;
use OCP\IConfig;
use OCP\IURLGenerator;
use OCP\Security\Signature\Exceptions\IdentityNotFoundException;
use OCP\Security\Signature\ISignatureManager;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

/** Ed25519 stage / activate / retire lifecycle, with stateful IAppConfig + IdentityProofManager fakes. */
class OCMSignatoryManagerRotationTest extends TestCase {
	private IAppConfig&MockObject $appConfig;
	private IdentityProofManager&MockObject $identityProofManager;
	private OCMSignatoryManager $signatoryManager;

	/** @var array<string, string> in-memory backing store for IAppConfig core/* */
	private array $appConfigStore = [];
	/** @var array<string, Key> in-memory backing store for IdentityProofManager appkeys */
	private array $appKeyStore = [];

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->identityProofManager = $this->createMock(IdentityProofManager::class);

		$this->wireAppConfig();
		$this->wireIdentityProofManager();

		$signatureManager = $this->createMock(ISignatureManager::class);
		$signatureManager->method('generateKeyIdFromConfig')
			->willReturnCallback(static fn (string $suffix): string => 'https://alice.example/' . ltrim($suffix, '/'));

		$cacheFactory = $this->createMock(ICacheFactory::class);
		$cacheFactory->method('createDistributed')->willReturn(new \OC\Memcache\ArrayCache(''));

		$this->signatoryManager = new OCMSignatoryManager(
			$this->appConfig,
			$signatureManager,
			$this->createMock(IURLGenerator::class),
			$this->identityProofManager,
			$this->stubClientService(),
			$this->createMock(IConfig::class),
			$cacheFactory,
			$this->createMock(LoggerInterface::class),
		);
	}

	public function testJwksBootstrapsActiveKeyOnFirstFetch(): void {
		// Fresh instance: first JWKS hit must provision the active key.
		$jwks = $this->signatoryManager->getLocalEd25519Jwks();
		$this->assertCount(1, $jwks);
		$this->assertSame('https://alice.example/ocm#ed25519-1', $jwks[0]['kid']);

		// And the bootstrapped key is the active one for outbound signing.
		$signatory = $this->signatoryManager->getLocalEd25519Signatory();
		$this->assertSame($jwks[0]['kid'], $signatory->getKeyId());
	}

	public function testFirstCallProvisionsActiveKey(): void {
		$signatory = $this->signatoryManager->getLocalEd25519Signatory();
		$this->assertNotNull($signatory);
		$this->assertSame('https://alice.example/ocm#ed25519-1', $signatory->getKeyId());

		$jwks = $this->signatoryManager->getLocalEd25519Jwks();
		$this->assertCount(1, $jwks);
		$this->assertSame($signatory->getKeyId(), $jwks[0]['kid']);

		$listed = $this->signatoryManager->listEd25519Keys();
		$this->assertSame([['poolId' => 1, 'kid' => $signatory->getKeyId(), 'slot' => 'active']], $listed);
	}

	public function testStageDoesNotChangeActiveSignerButPublishesNewJwk(): void {
		$initial = $this->signatoryManager->getLocalEd25519Signatory();
		$staged = $this->signatoryManager->stageEd25519Key();
		$this->assertNotSame($initial->getKeyId(), $staged->getKeyId());

		// Active signer is unchanged.
		$this->assertSame($initial->getKeyId(), $this->signatoryManager->getLocalEd25519Signatory()->getKeyId());

		// JWKS now advertises both kids, active first then pending.
		$jwks = $this->signatoryManager->getLocalEd25519Jwks();
		$this->assertSame([$initial->getKeyId(), $staged->getKeyId()], array_column($jwks, 'kid'));
	}

	public function testStageRefusesIfPendingAlreadyExists(): void {
		$this->signatoryManager->stageEd25519Key();
		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessageMatches('/pending Ed25519 key already exists/');
		$this->signatoryManager->stageEd25519Key();
	}

	public function testActivatePromotesPendingAndDemotesActive(): void {
		$first = $this->signatoryManager->getLocalEd25519Signatory();
		$staged = $this->signatoryManager->stageEd25519Key();
		$this->signatoryManager->activateStagedEd25519Key();

		// New signer is the formerly-staged key.
		$this->assertSame($staged->getKeyId(), $this->signatoryManager->getLocalEd25519Signatory()->getKeyId());

		// JWKS still advertises the former active key as retiring so peers
		// verifying in-flight signatures with its kid don't fail.
		$kids = array_column($this->signatoryManager->getLocalEd25519Jwks(), 'kid');
		$this->assertContains($first->getKeyId(), $kids);
		$this->assertContains($staged->getKeyId(), $kids);
	}

	public function testActivateRefusesIfRetiringStillPopulated(): void {
		$this->signatoryManager->getLocalEd25519Signatory();
		$this->signatoryManager->stageEd25519Key();
		$this->signatoryManager->activateStagedEd25519Key();
		// Retiring slot is now populated; staging again is allowed but
		// activating must refuse until the admin explicitly retires the old
		// key.
		$this->signatoryManager->stageEd25519Key();
		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessageMatches('/retiring Ed25519 key still exists/');
		$this->signatoryManager->activateStagedEd25519Key();
	}

	public function testActivateRefusesWithoutPendingKey(): void {
		$this->signatoryManager->getLocalEd25519Signatory();
		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessageMatches('/no pending Ed25519 key/');
		$this->signatoryManager->activateStagedEd25519Key();
	}

	public function testRetireRemovesRetiringKeyFromJwks(): void {
		$first = $this->signatoryManager->getLocalEd25519Signatory();
		$staged = $this->signatoryManager->stageEd25519Key();
		$this->signatoryManager->activateStagedEd25519Key();
		$this->signatoryManager->retireEd25519Key();

		$kids = array_column($this->signatoryManager->getLocalEd25519Jwks(), 'kid');
		$this->assertSame([$staged->getKeyId()], $kids);
		// listEd25519Keys also drops the retired pool.
		$listed = $this->signatoryManager->listEd25519Keys();
		$this->assertCount(1, $listed);
		$this->assertSame($staged->getKeyId(), $listed[0]['kid']);
		$this->assertNotContains($first->getKeyId(), array_column($listed, 'kid'));
	}

	public function testRetireRefusesWhenNothingToRetire(): void {
		$this->signatoryManager->getLocalEd25519Signatory();
		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessageMatches('/no retiring Ed25519 key/');
		$this->signatoryManager->retireEd25519Key();
	}

	public function testKidStaysStableThroughLifecycle(): void {
		$first = $this->signatoryManager->getLocalEd25519Signatory();
		$staged = $this->signatoryManager->stageEd25519Key();
		// kid for the staged key must stay the same once it is activated;
		// peers that cached it during the stage window must still resolve it.
		$this->signatoryManager->activateStagedEd25519Key();
		$this->assertSame($staged->getKeyId(), $this->signatoryManager->getLocalEd25519Signatory()->getKeyId());

		$this->signatoryManager->retireEd25519Key();
		$this->signatoryManager->stageEd25519Key();
		// And every newly minted kid must differ from prior ones, no pool
		// counter rewinding.
		$kids = array_column($this->signatoryManager->listEd25519Keys(), 'kid');
		$this->assertNotContains($first->getKeyId(), $kids);
		$this->assertSame($kids, array_unique($kids));
	}

	public function testSignerReturnsNullWhenIdentityCannotBeDerived(): void {
		// Replace the signature manager with one that cannot derive an
		// identity at all; provisioning the first key should fail loudly so
		// the admin gets a clear message instead of a corrupt half-state.
		$signatureManager = $this->createMock(ISignatureManager::class);
		$signatureManager->method('generateKeyIdFromConfig')
			->willThrowException(new IdentityNotFoundException('no identity'));
		$urlGenerator = $this->createMock(IURLGenerator::class);
		$urlGenerator->method('linkToRouteAbsolute')
			->willThrowException(new IdentityNotFoundException('no url either'));

		$cacheFactory = $this->createMock(ICacheFactory::class);
		$cacheFactory->method('createDistributed')->willReturn(new \OC\Memcache\ArrayCache(''));

		$manager = new OCMSignatoryManager(
			$this->appConfig,
			$signatureManager,
			$urlGenerator,
			$this->identityProofManager,
			$this->stubClientService(),
			$this->createMock(IConfig::class),
			$cacheFactory,
			$this->createMock(LoggerInterface::class),
		);

		$this->expectException(\RuntimeException::class);
		$manager->getLocalEd25519Signatory();
	}

	private function wireAppConfig(): void {
		$this->appConfig->method('hasKey')->willReturnCallback(
			fn (string $app, string $key): bool => $app === 'core' && array_key_exists($key, $this->appConfigStore)
		);
		$this->appConfig->method('getValueInt')->willReturnCallback(
			fn (string $app, string $key, int $default = 0): int => (int)($this->appConfigStore[$key] ?? $default)
		);
		$this->appConfig->method('setValueInt')->willReturnCallback(
			function (string $app, string $key, int $value): bool {
				$this->appConfigStore[$key] = (string)$value;
				return true;
			}
		);
		$this->appConfig->method('getValueString')->willReturnCallback(
			fn (string $app, string $key, string $default = '') => $this->appConfigStore[$key] ?? $default
		);
		$this->appConfig->method('setValueString')->willReturnCallback(
			function (string $app, string $key, string $value): bool {
				$this->appConfigStore[$key] = $value;
				return true;
			}
		);
		$this->appConfig->method('getValueBool')->willReturn(false);
		$this->appConfig->method('deleteKey')->willReturnCallback(
			function (string $app, string $key): void {
				unset($this->appConfigStore[$key]);
			}
		);
	}

	private function wireIdentityProofManager(): void {
		$this->identityProofManager->method('hasAppKey')->willReturnCallback(
			fn (string $app, string $name): bool => isset($this->appKeyStore[$app . '/' . $name])
		);
		$this->identityProofManager->method('generateEd25519AppKey')->willReturnCallback(
			function (string $app, string $name): Key {
				$keyPair = sodium_crypto_sign_keypair();
				$key = new Key(sodium_crypto_sign_publickey($keyPair), sodium_crypto_sign_secretkey($keyPair));
				$this->appKeyStore[$app . '/' . $name] = $key;
				return $key;
			}
		);
		$this->identityProofManager->method('getAppKey')->willReturnCallback(
			fn (string $app, string $name): Key => $this->appKeyStore[$app . '/' . $name]
		);
		$this->identityProofManager->method('deleteAppKey')->willReturnCallback(
			function (string $app, string $name): bool {
				$existed = isset($this->appKeyStore[$app . '/' . $name]);
				unset($this->appKeyStore[$app . '/' . $name]);
				return $existed;
			}
		);
	}

	private function stubClientService(): IClientService&MockObject {
		$service = $this->createMock(IClientService::class);
		$service->method('newClient')->willReturn($this->createMock(IClient::class));
		return $service;
	}
}
