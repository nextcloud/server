<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CloudFederationAPI;

use OCP\Capabilities\ICapability;
use OCP\IURLGenerator;
use OCP\OCM\Exceptions\OCMArgumentException;
use OCP\OCM\IOCMProvider;

class Capabilities implements ICapability {
	public const API_VERSION = '1.1.0';

	public function __construct(
		private IURLGenerator $urlGenerator,
		private IOCMProvider $provider,
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
	 *         provider: string,
	 *         resourceTypes: array{
	 *             name: string,
	 *             shareTypes: string[],
	 *             protocols: array<string, string>
	 *           }[],
	 *         },
	 *         capabilities: array{
	 *             string,
	 *         }
	 * }
	 * @throws OCMArgumentException
	 */
	public function getCapabilities() {
		$url = $this->urlGenerator->linkToRouteAbsolute('cloud_federation_api.requesthandlercontroller.addShare');

		$this->provider->setEnabled(true);
		$this->provider->setApiVersion(self::API_VERSION);
		$this->provider->setCapabilities(['/invite-accepted', '/notifications', '/shares']);

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

		return ['ocm' => $this->provider->jsonSerialize()];
	}
}
