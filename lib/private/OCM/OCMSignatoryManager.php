<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\OCM;

use OC\Security\Signature\Model\Signatory;
use OCP\IURLGenerator;
use OCP\OCM\Exceptions\OCMProviderException;
use OCP\Security\PublicPrivateKeyPairs\Exceptions\KeyPairNotFoundException;
use OCP\Security\PublicPrivateKeyPairs\IKeyPairManager;
use OCP\Security\Signature\Exceptions\SignatureIdentityNotFoundException;
use OCP\Security\Signature\ISignatoryManager;
use OCP\Security\Signature\ISignatureManager;
use OCP\Security\Signature\Model\IIncomingSignedRequest;
use OCP\Security\Signature\Model\ISignatory;
use OCP\Security\Signature\Model\SignatoryType;

/**
 * @inheritDoc
 *
 * returns local signatory using IKeyPairManager
 * extract optional signatory (keyId+public key) from ocm discovery service on remote instance
 *
 * @since 30.0.0
 */
class OCMSignatoryManager implements ISignatoryManager {
	public const PROVIDER_ID = 'ocm';

	public function __construct(
		private readonly ISignatureManager $signatureManager,
		private readonly IURLGenerator $urlGenerator,
		private readonly IKeyPairManager $keyPairManager,
		private readonly OCMDiscoveryService $ocmDiscoveryService,
	) {
	}

	/**
	 * @inheritDoc
	 *
	 * @since 30.0.0
	 * @return string
	 */
	public function getProviderId(): string {
		return self::PROVIDER_ID;
	}

	/**
	 * @inheritDoc
	 *
	 * @since 30.0.0
	 * @return array
	 */
	public function getOptions(): array {
		return [];
	}

	/**
	 * @inheritDoc
	 *
	 * @since 30.0.0
	 * @return ISignatory
	 */
	public function getLocalSignatory(): ISignatory {
		try {
			$keyId = $this->signatureManager->generateKeyId('/ocm#signature');
		} catch (SignatureIdentityNotFoundException) {
			$url = $this->urlGenerator->linkToRouteAbsolute('cloud_federation_api.requesthandlercontroller.addShare');

			$hostname = parse_url($url,PHP_URL_HOST);
			$path = parse_url($url,PHP_URL_PATH);

			// tries to create a keyId like 'https://hostname/subfolder/ocm#signature
			$pos = strpos($path, '/ocm/shares');
			if ($pos) {
				$path = substr($path, 0, $pos) . '/ocm';
			} else {
				$path = '/ocm#signature';
			}
			$keyId = 'https://' . $hostname . $path . '#signature';
		}

		try {
			$keyPair = $this->keyPairManager->getKeyPair('core', 'ocm');
		} catch (KeyPairNotFoundException) {
			$keyPair = $this->keyPairManager->generateKeyPair('core', 'ocm', ['keyId' => $keyId]);
		}

		return new Signatory($keyId, $keyPair->getPublicKey(), $keyPair->getPrivateKey(), local: true);
	}

	/**
	 * @inheritDoc
	 *
	 * @param IIncomingSignedRequest $signedRequest
	 *
	 * @return ISignatory|null must be NULL if no signatory is found
	 * @throws OCMProviderException on fail to discover ocm services
	 * @since 30.0.0
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
	 * @since 30.0.0
	 */
	public function getRemoteSignatoryFromHost(string $host): ?ISignatory {
		$ocmProvider = $this->ocmDiscoveryService->discover($host, true);
		$signatory = $ocmProvider->getSignatory();

		return $signatory?->setType(SignatoryType::TRUSTED);
	}
}
