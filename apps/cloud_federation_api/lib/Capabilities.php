<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\CloudFederationAPI;

use NCU\Security\Signature\Exceptions\IdentityNotFoundException;
use NCU\Security\Signature\Exceptions\SignatoryException;
use OC\OCM\OCMSignatoryManager;
use OCP\Capabilities\ICapability;
use OCP\Capabilities\IInitialStateExcludedCapability;
use OCP\IAppConfig;
use OCP\IURLGenerator;
use OCP\OCM\Exceptions\OCMArgumentException;
use OCP\OCM\IOCMProvider;
use Psr\Log\LoggerInterface;

class Capabilities implements ICapability, IInitialStateExcludedCapability {
	public const API_VERSION = '1.1'; // informative, real version.

	public function __construct(
		private IURLGenerator $urlGenerator,
		private IAppConfig $appConfig,
		private IOCMProvider $provider,
		private readonly OCMSignatoryManager $ocmSignatoryManager,
		private readonly LoggerInterface $logger,
	) {
	}

	/**
	 * Function an app uses to return the capabilities
	 *
	 * @return array{
	 *     ocm: array{
	 *     	   apiVersion: '1.0-proposal1',
	 *         enabled: bool,
	 *         endPoint: string,
	 *         publicKey?: array{
	 *             keyId: string,
	 *             publicKeyPem: string,
	 *         },
	 *         resourceTypes: list<array{
	 *             name: string,
	 *             shareTypes: list<string>,
	 *             protocols: array<string, string>
	 *         }>,
	 *         version: string
	 *     }
	 * }
	 * @throws OCMArgumentException
	 */
	public function getCapabilities() {
		$url = $this->urlGenerator->linkToRouteAbsolute('cloud_federation_api.requesthandlercontroller.addShare');
		$pos = strrpos($url, '/');
		if ($pos === false) {
			throw new OCMArgumentException('generated route should contain a slash character');
		}

		$this->provider->setEnabled(true);
		$this->provider->setApiVersion(self::API_VERSION);
		$this->provider->setEndPoint(substr($url, 0, $pos));

		$resource = $this->provider->createNewResourceType();
		$resource->setName('file')
			->setShareTypes(['user', 'group'])
			->setProtocols(['webdav' => '/public.php/webdav/']);

		$this->provider->addResourceType($resource);

		// Adding a public key to the ocm discovery
		try {
			if (!$this->appConfig->getValueBool('core', OCMSignatoryManager::APPCONFIG_SIGN_DISABLED, lazy: true)) {
				/**
				 * @experimental 31.0.0
				 * @psalm-suppress UndefinedInterfaceMethod
				 */
				$this->provider->setSignatory($this->ocmSignatoryManager->getLocalSignatory());
			} else {
				$this->logger->debug('ocm public key feature disabled');
			}
		} catch (SignatoryException|IdentityNotFoundException $e) {
			$this->logger->warning('cannot generate local signatory', ['exception' => $e]);
		}

		return ['ocm' => $this->provider->jsonSerialize()];
	}
}
