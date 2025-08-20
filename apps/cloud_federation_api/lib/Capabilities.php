<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\CloudFederationAPI;

use OC\OCM\OCMDiscoveryService;
use OCP\Capabilities\ICapability;
use OCP\Capabilities\IInitialStateExcludedCapability;
use OCP\OCM\Exceptions\OCMArgumentException;

class Capabilities implements ICapability, IInitialStateExcludedCapability {
	public function __construct(
		private readonly OCMDiscoveryService $ocmDiscoveryService,
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
		$provider = $this->ocmDiscoveryService->getLocalOCMProvider(false);
		return ['ocm' => $provider->jsonSerialize()];
	}
}
