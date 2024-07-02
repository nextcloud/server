<?php

declare(strict_types=1);

namespace OC\OCM;

use OC\Security\Signature\Model\Signatory;
use OCP\IURLGenerator;
use OCP\OCM\Exceptions\OCMProviderException;
use OCP\Security\PublicPrivateKeyPairs\IKeyPairManager;
use OCP\Security\Signature\Exceptions\SignatoryNotFoundException;
use OCP\Security\Signature\ISignatoryManager;
use OCP\Security\Signature\Model\IIncomingSignedRequest;
use OCP\Security\Signature\Model\ISignatory;
use OCP\Security\Signature\Model\SignatoryType;

class OCMSignatoryManager implements ISignatoryManager {
	public const PROVIDER_ID = 'ocm';

	public function __construct(
		private readonly IURLGenerator $urlGenerator,
		private readonly IKeyPairManager $keyPairManager,
		private readonly OCMDiscoveryService $ocmDiscoveryService,
	) {
	}

	public function getProviderId(): string {
		return self::PROVIDER_ID;
	}

	public function getOptions(): array {
		return [];
	}

	public function getLocalSignatory(): ISignatory {
		$url = $this->urlGenerator->linkToRouteAbsolute('cloud_federation_api.requesthandlercontroller.addShare');
		$keyId = $url . '#signature';
		$keyPair = $this->keyPairManager->getKeyPair('core', 'ocm');
		return new Signatory($keyId, $keyPair->getPublicKey(), $keyPair->getPrivateKey(), local: true);
	}

	/**
	 * @param IIncomingSignedRequest $signedRequest
	 *
	 * @return ISignatory|null
	 * @throws OCMProviderException
	 */
	public function getRemoteSignatory(IIncomingSignedRequest $signedRequest): ?ISignatory {
		$ocmProvider = $this->ocmDiscoveryService->discover($signedRequest->getOrigin(), true);
		$signatory = $ocmProvider->getSignatory();

		return $signatory?->setType(SignatoryType::TRUSTED);
	}
}
