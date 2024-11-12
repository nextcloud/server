<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\OCM;

use NCU\Security\PublicPrivateKeyPairs\Exceptions\KeyPairConflictException;
use NCU\Security\PublicPrivateKeyPairs\Exceptions\KeyPairNotFoundException;
use NCU\Security\PublicPrivateKeyPairs\IKeyPairManager;
use NCU\Security\Signature\Exceptions\IdentityNotFoundException;
use NCU\Security\Signature\ISignatoryManager;
use NCU\Security\Signature\ISignatureManager;
use NCU\Security\Signature\Model\IIncomingSignedRequest;
use NCU\Security\Signature\Model\ISignatory;
use NCU\Security\Signature\Model\SignatoryType;
use OC\Security\Signature\Model\Signatory;
use OCP\IAppConfig;
use OCP\IURLGenerator;
use OCP\OCM\Exceptions\OCMProviderException;

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
		private readonly IKeyPairManager $keyPairManager,
		private readonly OCMDiscoveryService $ocmDiscoveryService,
	) {
	}

	/**
	 * @inheritDoc
	 *
	 * @since 31.0.0
	 * @return string
	 */
	public function getProviderId(): string {
		return self::PROVIDER_ID;
	}

	/**
	 * @inheritDoc
	 *
	 * @since 31.0.0
	 * @return array
	 */
	public function getOptions(): array {
		return [];
	}

	/**
	 * @inheritDoc
	 *
	 * @return ISignatory
	 * @throws KeyPairConflictException
	 * @throws IdentityNotFoundException
	 * @since 31.0.0
	 */
	public function getLocalSignatory(): ISignatory {
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

		try {
			$keyPair = $this->keyPairManager->getKeyPair('core', 'ocm_external');
		} catch (KeyPairNotFoundException) {
			$keyPair = $this->keyPairManager->generateKeyPair('core', 'ocm_external');
		}

		return new Signatory($keyId, $keyPair->getPublicKey(), $keyPair->getPrivateKey(), local: true);
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
	 * @param IIncomingSignedRequest $signedRequest
	 *
	 * @return ISignatory|null must be NULL if no signatory is found
	 * @throws OCMProviderException on fail to discover ocm services
	 * @since 31.0.0
	 */
	public function getRemoteSignatory(IIncomingSignedRequest $signedRequest): ?ISignatory {
		return $this->getRemoteSignatoryFromHost($signedRequest->getOrigin());
	}

	/**
	 * As host is enough to generate signatory using OCMDiscoveryService
	 *
	 * @param string $host
	 *
	 * @return ISignatory|null
	 * @throws OCMProviderException on fail to discover ocm services
	 * @since 31.0.0
	 */
	public function getRemoteSignatoryFromHost(string $host): ?ISignatory {
		$ocmProvider = $this->ocmDiscoveryService->discover($host, true);
		$signatory = $ocmProvider->getSignatory();

		return $signatory?->setType(SignatoryType::TRUSTED);
	}
}
