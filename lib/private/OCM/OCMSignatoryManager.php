<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\OCM;

use NCU\Security\Signature\Enum\DigestAlgorithm;
use NCU\Security\Signature\Enum\SignatoryType;
use NCU\Security\Signature\Enum\SignatureAlgorithm;
use NCU\Security\Signature\Exceptions\IdentityNotFoundException;
use NCU\Security\Signature\ISignatoryManager;
use NCU\Security\Signature\ISignatureManager;
use NCU\Security\Signature\Model\Signatory;
use OC\Security\IdentityProof\Manager;
use OCP\IAppConfig;
use OCP\IURLGenerator;
use OCP\OCM\Exceptions\OCMProviderException;
use Psr\Log\LoggerInterface;

/**
 * @inheritDoc
 *
 * returns local signatory using IKeyPairManager
 * extract optional signatory (keyId+public key) from ocm discovery service on remote instance
 *
 * @since 31.0.0
 */
class OCMSignatoryManager implements ISignatoryManager {
	public const PROVIDER_ID = 'ocm';
	public const APPCONFIG_SIGN_IDENTITY_EXTERNAL = 'ocm_signed_request_identity_external';
	public const APPCONFIG_SIGN_DISABLED = 'ocm_signed_request_disabled';
	public const APPCONFIG_SIGN_ENFORCED = 'ocm_signed_request_enforced';

	public function __construct(
		private readonly IAppConfig $appConfig,
		private readonly ISignatureManager $signatureManager,
		private readonly IURLGenerator $urlGenerator,
		private readonly Manager $identityProofManager,
		private readonly OCMDiscoveryService $ocmDiscoveryService,
		private readonly LoggerInterface $logger,
	) {
	}

	/**
	 * @inheritDoc
	 *
	 * @return string
	 * @since 31.0.0
	 */
	public function getProviderId(): string {
		return self::PROVIDER_ID;
	}

	/**
	 * @inheritDoc
	 *
	 * @return array
	 * @since 31.0.0
	 */
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
	public function getLocalSignatory(): Signatory {
		/**
		 * TODO: manage multiple identity (external, internal, ...) to allow a limitation
		 * based on the requested interface (ie. only accept shares from globalscale)
		 */
		if ($this->appConfig->hasKey('core', self::APPCONFIG_SIGN_IDENTITY_EXTERNAL, true)) {
			$identity = $this->appConfig->getValueString('core', self::APPCONFIG_SIGN_IDENTITY_EXTERNAL, lazy: true);
			$keyId = 'https://' . $identity . '/ocm#signature';
		} else {
			$keyId = $this->generateKeyId();
		}

		if (!$this->identityProofManager->hasAppKey('core', 'ocm_external')) {
			$this->identityProofManager->generateAppKey('core', 'ocm_external', [
				'algorithm' => 'rsa',
				'private_key_bits' => 2048,
				'private_key_type' => OPENSSL_KEYTYPE_RSA,
			]);
		}
		$keyPair = $this->identityProofManager->getAppKey('core', 'ocm_external');

		$signatory = new Signatory(true);
		$signatory->setKeyId($keyId);
		$signatory->setPublicKey($keyPair->getPublic());
		$signatory->setPrivateKey($keyPair->getPrivate());
		return $signatory;

	}

	/**
	 * - tries to generate a keyId using global configuration (from signature manager) if available
	 * - generate a keyId using the current route to ocm shares
	 *
	 * @return string
	 * @throws IdentityNotFoundException
	 */
	private function generateKeyId(): string {
		try {
			return $this->signatureManager->generateKeyIdFromConfig('/ocm#signature');
		} catch (IdentityNotFoundException) {
		}

		$url = $this->urlGenerator->linkToRouteAbsolute('cloud_federation_api.requesthandlercontroller.addShare');
		$identity = $this->signatureManager->extractIdentityFromUri($url);

		// catching possible subfolder to create a keyId like 'https://hostname/subfolder/ocm#signature
		$path = parse_url($url, PHP_URL_PATH);
		$pos = strpos($path, '/ocm/shares');
		$sub = ($pos) ? substr($path, 0, $pos) : '';

		return 'https://' . $identity . $sub . '/ocm#signature';
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $remote
	 *
	 * @return Signatory|null must be NULL if no signatory is found
	 * @since 31.0.0
	 */
	public function getRemoteSignatory(string $remote): ?Signatory {
		try {
			$ocmProvider = $this->ocmDiscoveryService->discover($remote, true);
			/**
			 * @experimental 31.0.0
			 * @psalm-suppress UndefinedInterfaceMethod
			 */
			$signatory = $ocmProvider->getSignatory();
			$signatory?->setSignatoryType(SignatoryType::TRUSTED);
			return $signatory;
		} catch (OCMProviderException $e) {
			$this->logger->warning('fail to get remote signatory', ['exception' => $e, 'remote' => $remote]);
			return null;
		}
	}
}
