<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\OCM;

use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use JsonException;
use OC\Security\IdentityProof\Manager;
use OC\Security\Signature\Rfc9421\Algorithm;
use OC\Security\Signature\Rfc9421\IJwkResolvingSignatoryManager;
use OCP\Http\Client\IClientService;
use OCP\IAppConfig;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IConfig;
use OCP\IURLGenerator;
use OCP\OCM\Exceptions\OCMProviderException;
use OCP\OCM\IOCMDiscoveryService;
use OCP\Security\Signature\Enum\DigestAlgorithm;
use OCP\Security\Signature\Enum\SignatoryType;
use OCP\Security\Signature\Enum\SignatureAlgorithm;
use OCP\Security\Signature\Exceptions\IdentityNotFoundException;
use OCP\Security\Signature\Exceptions\SignatureException;
use OCP\Security\Signature\ISignatureManager;
use OCP\Security\Signature\Model\Signatory;
use OCP\Server;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * @inheritDoc
 *
 * returns local signatory using IKeyPairManager
 * extract optional signatory (keyId+public key) from ocm discovery service on remote instance
 *
 * @since 31.0.0
 */
class OCMSignatoryManager implements IJwkResolvingSignatoryManager {
	public const PROVIDER_ID = 'ocm';
	public const APPCONFIG_SIGN_IDENTITY_EXTERNAL = 'ocm_signed_request_identity_external';
	public const APPCONFIG_SIGN_DISABLED = 'ocm_signed_request_disabled';
	public const APPCONFIG_SIGN_ENFORCED = 'ocm_signed_request_enforced';
	private const APPKEY_CAVAGE = 'ocm_external';
	private const KEYID_FRAGMENT_CAVAGE = 'signature';
	private const KEYID_FRAGMENT_JWKS = 'ecdsa-p256-sha256';
	/** JWKS-published keypairs live in numbered pool appkeys; slots point to them by id. */
	private const APPKEY_JWKS_POOL_PREFIX = 'ocm_jwks_pool_';
	private const APPCONFIG_JWKS_POOL_COUNTER = 'ocm_jwks_pool_counter';
	private const APPCONFIG_JWKS_POOL_KID_PREFIX = 'ocm_jwks_pool_kid_';
	public const SLOT_ACTIVE = 'active';
	public const SLOT_PENDING = 'pending';
	public const SLOT_RETIRING = 'retiring';
	/** All slots in advertise order. */
	public const JWKS_SLOTS = [self::SLOT_ACTIVE, self::SLOT_PENDING, self::SLOT_RETIRING];
	/** Remote JWKS cache TTL (seconds). */
	private const JWKS_CACHE_TTL = 3600;

	private readonly ICache $jwksCache;

	public function __construct(
		private readonly IAppConfig $appConfig,
		private readonly ISignatureManager $signatureManager,
		private readonly IURLGenerator $urlGenerator,
		private readonly Manager $identityProofManager,
		private readonly IClientService $clientService,
		private readonly IConfig $config,
		ICacheFactory $cacheFactory,
		private readonly LoggerInterface $logger,
	) {
		$this->jwksCache = $cacheFactory->createDistributed('ocm-jwks');
	}

	/**
	 * @inheritDoc
	 *
	 * @return string
	 * @since 31.0.0
	 */
	#[\Override]
	public function getProviderId(): string {
		return self::PROVIDER_ID;
	}

	/**
	 * @inheritDoc
	 *
	 * @return array
	 * @since 31.0.0
	 */
	#[\Override]
	public function getOptions(): array {
		return [
			'algorithm' => SignatureAlgorithm::RSA_SHA512,
			'digestAlgorithm' => DigestAlgorithm::SHA512,
			'extraSignatureHeaders' => [],
			'ttl' => 300,
			'dateHeader' => 'D, d M Y H:i:s T',
			'ttlSignatory' => 86400 * 3,
			'bodyMaxSize' => 50000,
		];
	}

	/**
	 * @inheritDoc
	 *
	 * @return Signatory
	 * @throws IdentityNotFoundException
	 * @since 31.0.0
	 */
	#[\Override]
	public function getLocalSignatory(): Signatory {
		/**
		 * TODO: manage multiple identity (external, internal, ...) to allow a limitation
		 * based on the requested interface (ie. only accept shares from globalscale)
		 */
		$keyId = $this->buildLocalKeyId(self::KEYID_FRAGMENT_CAVAGE);

		if (!$this->identityProofManager->hasAppKey('core', self::APPKEY_CAVAGE)) {
			$this->identityProofManager->generateAppKey('core', self::APPKEY_CAVAGE, [
				'algorithm' => 'rsa',
				'private_key_bits' => 2048,
				'private_key_type' => OPENSSL_KEYTYPE_RSA,
			]);
		}
		$keyPair = $this->identityProofManager->getAppKey('core', self::APPKEY_CAVAGE);

		$signatory = new Signatory(true);
		$signatory->setKeyId($keyId);
		$signatory->setPublicKey($keyPair->getPublic());
		$signatory->setPrivateKey($keyPair->getPrivate());
		return $signatory;
	}

	/** Active JWKS-published signing key (ECDSA P-256), lazily provisioned. */
	public function getLocalJwksSignatory(): ?Signatory {
		$poolId = $this->getSlotPool(self::SLOT_ACTIVE);
		if ($poolId === null) {
			$poolId = $this->generatePool($this->nextPoolKid());
			$this->setSlotPool(self::SLOT_ACTIVE, $poolId);
		}
		return $this->signatoryFromPool($poolId);
	}

	/**
	 * JWKs for the active/pending/retiring slots, in advertise order. The
	 * active slot is provisioned if missing so first-hit returns a key.
	 *
	 * @return list<array<string, string>>
	 */
	public function getLocalJwks(): array {
		if ($this->getSlotPool(self::SLOT_ACTIVE) === null) {
			$this->getLocalJwksSignatory();
		}

		$jwks = [];
		foreach (self::JWKS_SLOTS as $slot) {
			$poolId = $this->getSlotPool($slot);
			if ($poolId === null) {
				continue;
			}
			$signatory = $this->signatoryFromPool($poolId);
			if ($signatory !== null) {
				$jwks[] = self::buildEcdsaP256JwkArray($signatory->getPublicKey(), $signatory->getKeyId());
			}
		}
		return $jwks;
	}

	/**
	 * Generate a pending keypair (advertised in JWKS, not yet used for
	 * outbound signing).
	 *
	 * @throws \RuntimeException if pending is already populated
	 */
	public function stageJwksKey(): Signatory {
		if ($this->getSlotPool(self::SLOT_PENDING) !== null) {
			throw new \RuntimeException('a pending JWKS key already exists; activate or retire it first');
		}
		// Need an active key first; staging a next from nothing makes no sense.
		if ($this->getSlotPool(self::SLOT_ACTIVE) === null) {
			$this->getLocalJwksSignatory();
		}
		$poolId = $this->generatePool($this->nextPoolKid());
		$this->setSlotPool(self::SLOT_PENDING, $poolId);
		$signatory = $this->signatoryFromPool($poolId);
		if ($signatory === null) {
			throw new \RuntimeException('failed to materialise newly staged JWKS key');
		}
		return $signatory;
	}

	/**
	 * pending -> active, previous active -> retiring. The retiring slot
	 * stays in JWKS until {@see retireJwksKey} is run.
	 *
	 * @throws \RuntimeException if no pending key is staged, or retiring is occupied
	 */
	public function activateStagedJwksKey(): void {
		$pending = $this->getSlotPool(self::SLOT_PENDING);
		if ($pending === null) {
			throw new \RuntimeException('no pending JWKS key to activate; run `ocm:keys:stage` first');
		}
		if ($this->getSlotPool(self::SLOT_RETIRING) !== null) {
			throw new \RuntimeException('a retiring JWKS key still exists; retire it before activating a new one');
		}
		$active = $this->getSlotPool(self::SLOT_ACTIVE);

		$this->setSlotPool(self::SLOT_ACTIVE, $pending);
		$this->clearSlot(self::SLOT_PENDING);
		if ($active !== null) {
			$this->setSlotPool(self::SLOT_RETIRING, $active);
		}
	}

	/**
	 * Delete the retiring key. In-flight signatures referencing its kid
	 * stop verifying after this returns.
	 *
	 * @throws \RuntimeException if retiring is empty
	 */
	public function retireJwksKey(): void {
		$poolId = $this->getSlotPool(self::SLOT_RETIRING);
		if ($poolId === null) {
			throw new \RuntimeException('no retiring JWKS key to remove');
		}
		$this->identityProofManager->deleteAppKey('core', self::APPKEY_JWKS_POOL_PREFIX . $poolId);
		$this->appConfig->deleteKey('core', self::APPCONFIG_JWKS_POOL_KID_PREFIX . $poolId);
		$this->clearSlot(self::SLOT_RETIRING);
	}

	/**
	 * Diagnostics snapshot. `slot` is null for orphaned pools.
	 *
	 * @return list<array{poolId: int, kid: string, slot: ?string}>
	 */
	public function listJwksKeys(): array {
		$bySlot = [];
		foreach (self::JWKS_SLOTS as $slot) {
			$id = $this->getSlotPool($slot);
			if ($id !== null) {
				$bySlot[$id] = $slot;
			}
		}

		$max = $this->appConfig->getValueInt('core', self::APPCONFIG_JWKS_POOL_COUNTER, 0);
		$entries = [];
		for ($id = 1; $id <= $max; $id++) {
			if (!$this->identityProofManager->hasAppKey('core', self::APPKEY_JWKS_POOL_PREFIX . $id)) {
				continue;
			}
			$entries[] = [
				'poolId' => $id,
				'kid' => $this->appConfig->getValueString('core', self::APPCONFIG_JWKS_POOL_KID_PREFIX . $id, ''),
				'slot' => $bySlot[$id] ?? null,
			];
		}
		return $entries;
	}

	/**
	 * Generate keypair into a new pool. Only the opaque key id is persisted;
	 * the host is derived fresh per request at use time, so a key provisioned
	 * under one URL still verifies when the instance is addressed by another
	 * (e.g. the two-port integration rig, or a port change behind a proxy).
	 */
	private function generatePool(string $opaqueKeyId): int {
		$poolId = $this->appConfig->getValueInt('core', self::APPCONFIG_JWKS_POOL_COUNTER, 0) + 1;
		$this->appConfig->setValueInt('core', self::APPCONFIG_JWKS_POOL_COUNTER, $poolId);

		$this->identityProofManager->generateEcdsaP256AppKey('core', self::APPKEY_JWKS_POOL_PREFIX . $poolId);
		$this->appConfig->setValueString('core', self::APPCONFIG_JWKS_POOL_KID_PREFIX . $poolId, $opaqueKeyId);
		return $poolId;
	}

	/** Next opaque key id (`<fragment>-<n>`); the host is added per request. */
	private function nextPoolKid(): string {
		$next = $this->appConfig->getValueInt('core', self::APPCONFIG_JWKS_POOL_COUNTER, 0) + 1;
		return self::KEYID_FRAGMENT_JWKS . '-' . $next;
	}

	private function getSlotPool(string $slot): ?int {
		$key = 'ocm_jwks_slot_' . $slot;
		if (!$this->appConfig->hasKey('core', $key)) {
			return null;
		}
		$value = $this->appConfig->getValueInt('core', $key, 0);
		return $value > 0 ? $value : null;
	}

	private function setSlotPool(string $slot, int $poolId): void {
		$this->appConfig->setValueInt('core', 'ocm_jwks_slot_' . $slot, $poolId);
	}

	private function clearSlot(string $slot): void {
		$this->appConfig->deleteKey('core', 'ocm_jwks_slot_' . $slot);
	}

	/**
	 * Returns null if the underlying appkey was manually deleted. The keyId
	 * is rebuilt per call from the opaque id and the current request URL, so
	 * it tracks the host the instance is actually addressed as.
	 *
	 * @throws \RuntimeException if no instance identity can be derived
	 */
	private function signatoryFromPool(int $poolId): ?Signatory {
		$appKey = self::APPKEY_JWKS_POOL_PREFIX . $poolId;
		if (!$this->identityProofManager->hasAppKey('core', $appKey)) {
			return null;
		}
		$opaqueKeyId = $this->appConfig->getValueString('core', self::APPCONFIG_JWKS_POOL_KID_PREFIX . $poolId, '');
		if ($opaqueKeyId === '') {
			return null;
		}
		$keyPair = $this->identityProofManager->getAppKey('core', $appKey);
		$signatory = new Signatory(true);
		try {
			$signatory->setKeyId($this->buildLocalKeyId($opaqueKeyId));
		} catch (IdentityNotFoundException $e) {
			throw new \RuntimeException('cannot derive instance identity for JWKS kid', 0, $e);
		}
		$signatory->setPublicKey($keyPair->getPublic());
		$signatory->setPrivateKey($keyPair->getPrivate());
		return $signatory;
	}

	/**
	 * Absolute URL of the local JWK Set, advertised as `jwksUri` in the
	 * discovery response.
	 *
	 * @throws IdentityNotFoundException
	 */
	public function getLocalJwksUri(): string {
		return $this->buildLocalUrl('/.well-known/jwks.json');
	}

	/**
	 * @param string $fragment URL fragment (e.g. 'signature' for cavage, 'ecdsa-p256-sha256' for the JWKS-published key)
	 * @return string
	 * @throws IdentityNotFoundException
	 */
	private function buildLocalKeyId(string $fragment): string {
		return $this->buildLocalUrl('/ocm#' . $fragment);
	}

	/**
	 * Absolute local URL for a signing path (keyId fragment or jwksUri),
	 * built via {@see IURLGenerator::getAbsoluteURL()} so the advertised
	 * signing origin matches the instance URL used for federated shares.
	 * keyId callers re-canonicalize to https through {@see Signatory::setKeyId}.
	 *
	 * @param string $path absolute path, starting with a slash
	 * @return string
	 */
	private function buildLocalUrl(string $path): string {
		if ($this->appConfig->hasKey('core', self::APPCONFIG_SIGN_IDENTITY_EXTERNAL, true)) {
			$identity = $this->appConfig->getValueString('core', self::APPCONFIG_SIGN_IDENTITY_EXTERNAL, lazy: true);
			return 'https://' . $identity . $path;
		}

		return $this->urlGenerator->getAbsoluteURL($path);
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $remote
	 *
	 * @return Signatory|null must be NULL if no signatory is found
	 * @since 31.0.0
	 */
	#[\Override]
	public function getRemoteSignatory(string $remote): ?Signatory {
		try {
			$ocmProvider = Server::get(OCMDiscoveryService::class)->discover($remote, true);
			/**
			 * @experimental 31.0.0
			 * @psalm-suppress UndefinedInterfaceMethod
			 */
			$signatory = $ocmProvider->getSignatory();
			$signatory?->setSignatoryType(SignatoryType::TRUSTED);
			return $signatory;
		} catch (NotFoundExceptionInterface|ContainerExceptionInterface|OCMProviderException $e) {
			$this->logger->warning('fail to get remote signatory', ['exception' => $e, 'remote' => $remote]);
			return null;
		}
	}

	/**
	 * Resolve a peer's JWK by kid. Cached per-origin for {@see JWKS_CACHE_TTL}s
	 * with a single refetch on cache-hit-but-kid-missing so rotations propagate.
	 */
	#[\Override]
	public function getRemoteKey(string $origin, string $keyId): ?Key {
		$keys = $this->readCachedJwks($origin);
		$fromCache = $keys !== null;
		if (!$fromCache) {
			$keys = $this->fetchJwks($origin);
			if ($keys !== null) {
				$this->jwksCache->set($origin, json_encode($keys), self::JWKS_CACHE_TTL);
			}
		}

		$key = $this->findKid($keys, $keyId);
		if ($key !== null) {
			return $key;
		}
		// Only refetch when the miss came from cache; fresh is authoritative.
		if (!$fromCache) {
			return null;
		}

		$keys = $this->fetchJwks($origin);
		if ($keys === null) {
			return null;
		}
		$this->jwksCache->set($origin, json_encode($keys), self::JWKS_CACHE_TTL);
		return $this->findKid($keys, $keyId);
	}

	/** @return list<array<string, mixed>>|null null on cold/corrupt cache */
	private function readCachedJwks(string $origin): ?array {
		$cached = $this->jwksCache->get($origin);
		if (!is_string($cached)) {
			return null;
		}
		try {
			$decoded = json_decode($cached, true, 8, JSON_THROW_ON_ERROR);
		} catch (JsonException) {
			return null;
		}
		if (!is_array($decoded)) {
			return null;
		}
		/** @var list<array<string, mixed>> $decoded */
		return array_values(array_filter($decoded, 'is_array'));
	}

	/**
	 * Fetch the peer's JWK Set from the URL advertised in the `jwksUri`
	 * field of its discovery response.
	 *
	 * @return list<array<string, mixed>>|null
	 */
	private function fetchJwks(string $origin): ?array {
		$url = $this->resolveJwksUri($origin);
		if ($url === null) {
			return null;
		}
		$options = [
			'timeout' => 10,
			'connect_timeout' => 10,
		];
		if ($this->config->getSystemValueBool('sharing.federation.allowSelfSignedCertificates') === true) {
			$options['verify'] = false;
		}

		try {
			$response = $this->clientService->newClient()->get($url, $options);
		} catch (Throwable $e) {
			$this->logger->warning('failed to fetch remote JWKS', ['exception' => $e, 'url' => $url]);
			return null;
		}

		try {
			$decoded = json_decode((string)$response->getBody(), true, 8, JSON_THROW_ON_ERROR);
		} catch (JsonException $e) {
			$this->logger->warning('remote JWKS is not valid JSON', ['exception' => $e, 'url' => $url]);
			return null;
		}

		if (!is_array($decoded) || !is_array($decoded['keys'] ?? null)) {
			return null;
		}
		return array_values(array_filter($decoded['keys'], 'is_array'));
	}

	/**
	 * The peer's `jwksUri` from its discovery response. Must be https, or
	 * http from an http-only peer (the spec's testing fallback): an https
	 * peer pointing at an http jwksUri would downgrade the key fetch.
	 */
	private function resolveJwksUri(string $origin): ?string {
		try {
			$provider = Server::get(IOCMDiscoveryService::class)->discover($origin);
		} catch (NotFoundExceptionInterface|ContainerExceptionInterface|OCMProviderException $e) {
			$this->logger->warning('cannot discover remote OCM provider for JWKS', ['exception' => $e, 'origin' => $origin]);
			return null;
		}

		$jwksUri = $provider->getJwksUri();
		if ($jwksUri === '') {
			if ($provider->hasCapability('http-sig')) {
				$this->logger->warning('remote advertises http-sig but no jwksUri; non-conformant peer', ['origin' => $origin]);
			}
			return null;
		}
		$httpFromHttpPeer = str_starts_with($jwksUri, 'http://')
			&& str_starts_with($provider->getEndPoint(), 'http://');
		if (!str_starts_with($jwksUri, 'https://') && !$httpFromHttpPeer) {
			$this->logger->warning('refusing jwksUri: https is required unless the peer itself is http-only', ['origin' => $origin, 'jwksUri' => $jwksUri]);
			return null;
		}
		return $jwksUri;
	}

	/**
	 * @param list<array<string, mixed>>|null $keys
	 */
	private function findKid(?array $keys, string $keyId): ?Key {
		if ($keys === null) {
			return null;
		}
		foreach ($keys as $entry) {
			if (($entry['kid'] ?? null) !== $keyId) {
				continue;
			}
			// every published JWK must carry an `alg` parameter naming an
			// acceptable asymmetric signature algorithm; keys without one
			// are rejected as non-conformant
			$alg = $entry['alg'] ?? null;
			if (!is_string($alg) || $alg === '') {
				$this->logger->warning('remote JWK carries no alg parameter', ['kid' => $keyId]);
				return null;
			}
			try {
				$native = Algorithm::normalize($alg);
				$derived = Algorithm::deriveJoseAlgFromJwk($entry);
				if ($derived !== null && Algorithm::normalize($derived) !== $native) {
					$this->logger->warning('remote JWK alg does not match its key type', ['kid' => $keyId, 'alg' => $alg]);
					return null;
				}
				return JWK::parseKey($entry);
			} catch (SignatureException $e) {
				$this->logger->warning('remote JWK alg is not acceptable', ['exception' => $e, 'kid' => $keyId, 'alg' => $alg]);
				return null;
			} catch (Throwable $e) {
				$this->logger->warning('failed to parse remote JWK', ['exception' => $e, 'kid' => $keyId]);
				return null;
			}
		}
		return null;
	}

	/**
	 * Build an EC P-256 JWK (RFC 7518 §6.2) from a PEM public key. The raw x/y
	 * coordinates from openssl are zero-padded to 32 bytes per RFC 7518 §6.2.1.2.
	 *
	 * @return array<string, string>
	 */
	private static function buildEcdsaP256JwkArray(string $publicKeyPem, string $kid): array {
		$details = openssl_pkey_get_details(openssl_pkey_get_public($publicKeyPem) ?: throw new \RuntimeException('invalid EC public key'));
		if ($details === false || !isset($details['ec']['x'], $details['ec']['y'])) {
			throw new \RuntimeException('invalid EC public key');
		}
		$x = str_pad($details['ec']['x'], 32, "\x00", STR_PAD_LEFT);
		$y = str_pad($details['ec']['y'], 32, "\x00", STR_PAD_LEFT);

		return [
			'kty' => 'EC',
			'crv' => 'P-256',
			'kid' => $kid,
			'alg' => 'ES256',
			'use' => 'sig',
			'x' => JWT::urlsafeB64Encode($x),
			'y' => JWT::urlsafeB64Encode($y),
		];
	}
}
