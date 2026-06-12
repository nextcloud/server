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

/** JWKS stage / activate / retire lifecycle, with stateful IAppConfig + IdentityProofManager fakes. */
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
		$jwks = $this->signatoryManager->getLocalJwks();
		$this->assertCount(1, $jwks);
		$this->assertSame('https://alice.example/ocm#ecdsa-p256-sha256-1', $jwks[0]['kid']);

		// And the bootstrapped key is the active one for outbound signing.
		$signatory = $this->signatoryManager->getLocalJwksSignatory();
		$this->assertSame($jwks[0]['kid'], $signatory->getKeyId());
	}

	public function testFirstCallProvisionsActiveKey(): void {
		$signatory = $this->signatoryManager->getLocalJwksSignatory();
		$this->assertNotNull($signatory);
		$this->assertSame('https://alice.example/ocm#ecdsa-p256-sha256-1', $signatory->getKeyId());

		$jwks = $this->signatoryManager->getLocalJwks();
		$this->assertCount(1, $jwks);
		$this->assertSame($signatory->getKeyId(), $jwks[0]['kid']);

		$listed = $this->signatoryManager->listJwksKeys();
		$this->assertSame([['poolId' => 1, 'kid' => $signatory->getKeyId(), 'slot' => 'active']], $listed);
	}

	public function testStageDoesNotChangeActiveSignerButPublishesNewJwk(): void {
		$initial = $this->signatoryManager->getLocalJwksSignatory();
		$staged = $this->signatoryManager->stageJwksKey();
		$this->assertNotSame($initial->getKeyId(), $staged->getKeyId());

		// Active signer is unchanged.
		$this->assertSame($initial->getKeyId(), $this->signatoryManager->getLocalJwksSignatory()->getKeyId());

		// JWKS now advertises both kids, active first then pending.
		$jwks = $this->signatoryManager->getLocalJwks();
		$this->assertSame([$initial->getKeyId(), $staged->getKeyId()], array_column($jwks, 'kid'));
	}

	public function testStageRefusesIfPendingAlreadyExists(): void {
		$this->signatoryManager->stageJwksKey();
		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessageMatches('/pending JWKS key already exists/');
		$this->signatoryManager->stageJwksKey();
	}

	public function testActivatePromotesPendingAndDemotesActive(): void {
		$first = $this->signatoryManager->getLocalJwksSignatory();
		$staged = $this->signatoryManager->stageJwksKey();
		$this->signatoryManager->activateStagedJwksKey();

		// New signer is the formerly-staged key.
		$this->assertSame($staged->getKeyId(), $this->signatoryManager->getLocalJwksSignatory()->getKeyId());

		// JWKS still advertises the former active key as retiring so peers
		// verifying in-flight signatures with its kid don't fail.
		$kids = array_column($this->signatoryManager->getLocalJwks(), 'kid');
		$this->assertContains($first->getKeyId(), $kids);
		$this->assertContains($staged->getKeyId(), $kids);
	}

	public function testActivateRefusesIfRetiringStillPopulated(): void {
		$this->signatoryManager->getLocalJwksSignatory();
		$this->signatoryManager->stageJwksKey();
		$this->signatoryManager->activateStagedJwksKey();
		// Retiring slot is now populated; staging again is allowed but
		// activating must refuse until the admin explicitly retires the old
		// key.
		$this->signatoryManager->stageJwksKey();
		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessageMatches('/retiring JWKS key still exists/');
		$this->signatoryManager->activateStagedJwksKey();
	}

	public function testActivateRefusesWithoutPendingKey(): void {
		$this->signatoryManager->getLocalJwksSignatory();
		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessageMatches('/no pending JWKS key/');
		$this->signatoryManager->activateStagedJwksKey();
	}

	public function testRetireRemovesRetiringKeyFromJwks(): void {
		$first = $this->signatoryManager->getLocalJwksSignatory();
		$staged = $this->signatoryManager->stageJwksKey();
		$this->signatoryManager->activateStagedJwksKey();
		$this->signatoryManager->retireJwksKey();

		$kids = array_column($this->signatoryManager->getLocalJwks(), 'kid');
		$this->assertSame([$staged->getKeyId()], $kids);
		// listJwksKeys also drops the retired pool.
		$listed = $this->signatoryManager->listJwksKeys();
		$this->assertCount(1, $listed);
		$this->assertSame($staged->getKeyId(), $listed[0]['kid']);
		$this->assertNotContains($first->getKeyId(), array_column($listed, 'kid'));
	}

	public function testRetireRefusesWhenNothingToRetire(): void {
		$this->signatoryManager->getLocalJwksSignatory();
		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessageMatches('/no retiring JWKS key/');
		$this->signatoryManager->retireJwksKey();
	}

	public function testKidStaysStableThroughLifecycle(): void {
		$first = $this->signatoryManager->getLocalJwksSignatory();
		$staged = $this->signatoryManager->stageJwksKey();
		// kid for the staged key must stay the same once it is activated;
		// peers that cached it during the stage window must still resolve it.
		$this->signatoryManager->activateStagedJwksKey();
		$this->assertSame($staged->getKeyId(), $this->signatoryManager->getLocalJwksSignatory()->getKeyId());

		$this->signatoryManager->retireJwksKey();
		$this->signatoryManager->stageJwksKey();
		// And every newly minted kid must differ from prior ones, no pool
		// counter rewinding.
		$kids = array_column($this->signatoryManager->listJwksKeys(), 'kid');
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
		$manager->getLocalJwksSignatory();
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
		$this->identityProofManager->method('generateEcdsaP256AppKey')->willReturnCallback(
			function (string $app, string $name): Key {
				$res = openssl_pkey_new([
					'private_key_type' => OPENSSL_KEYTYPE_EC,
					'curve_name' => 'prime256v1',
				]);
				openssl_pkey_export($res, $privatePem);
				$publicPem = openssl_pkey_get_details($res)['key'];
				$key = new Key($publicPem, $privatePem);
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
