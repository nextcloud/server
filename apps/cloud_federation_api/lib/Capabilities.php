<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CloudFederationAPI;

use OC\OCM\OCMSignatoryManager;
use OCP\Capabilities\ICapability;
use OCP\IURLGenerator;
use OCP\OCM\Exceptions\OCMArgumentException;
use OCP\OCM\IOCMProvider;
use OCP\Security\Signature\Exceptions\SignatoryException;
use Psr\Log\LoggerInterface;

class Capabilities implements ICapability {
	public const API_VERSION = '1.1'; // informative, real version.

	public function __construct(
		private IURLGenerator $urlGenerator,
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
	 *         enabled: bool,
	 *         apiVersion: string,
	 *         endPoint: string,
	 *         resourceTypes: array{
	 *             name: string,
	 *             shareTypes: string[],
	 *             protocols: array<string, string>
	 *           }[],
	 *       },
	 * }
	 * @throws OCMArgumentException
	 */
	public function getCapabilities() {
		$url = $this->urlGenerator->linkToRouteAbsolute('cloud_federation_api.requesthandlercontroller.addShare');

		$this->provider->setEnabled(true);
		$this->provider->setApiVersion(self::API_VERSION);

		$pos = strrpos($url, '/');
		if ($pos === false) {
			throw new OCMArgumentException('generated route should contains a slash character');
		}

		$this->provider->setEndPoint(substr($url, 0, $pos));

		$resource = $this->provider->createNewResourceType();
		$resource->setName('file')
				 ->setShareTypes(['user', 'group'])
				 ->setProtocols(['webdav' => '/public.php/webdav/']);

		$this->provider->addResourceType($resource);

		// Adding a public key to the ocm discovery
		try {
			$this->provider->setSignatory($this->ocmSignatoryManager->getLocalSignatory());
		} catch (SignatoryException $e) {
			$this->logger->warning('cannot generate local signatory', ['exception' => $e]);
		}

		return ['ocm' => $this->provider->jsonSerialize()];
	}
}
