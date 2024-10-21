<?php
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\CloudFederationAPI;

use OCP\Federation\ICloudFederationProviderManager;

/**
 * Class config
 *
 * handles all the config parameters
 *
 * @package OCA\CloudFederationAPI
 */
class Config {

	public function __construct(
		private ICloudFederationProviderManager $cloudFederationProviderManager,
	) {
	}

	/**
	 * get a list of supported share types
	 *
	 * @param string $resourceType
	 * @return array
	 */
	public function getSupportedShareTypes($resourceType) {
		try {
			$provider = $this->cloudFederationProviderManager->getCloudFederationProvider($resourceType);
			return $provider->getSupportedShareTypes();
		} catch (\Exception $e) {
			return [];
		}
	}
}
