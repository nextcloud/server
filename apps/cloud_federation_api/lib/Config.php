<?php

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\CloudFederationAPI;

use OCP\Federation\ICloudFederationProviderManager;
use Psr\Log\LoggerInterface;

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
		private LoggerInterface $logger,
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
			$supportedShareTypes = [];
			$cloudFederationProviders = $this->cloudFederationProviderManager->getAllCloudFederationProviders();
			foreach ($cloudFederationProviders as $providerWrapper) {
				if ($providerWrapper['resourceType'] === $resourceType) {
					$providerSupportedShareTypes = $providerWrapper['provider']->getSupportedShareTypes();
					$supportedShareTypes = array_merge($supportedShareTypes, $providerSupportedShareTypes);
				}
			}
			return array_unique($supportedShareTypes);
		} catch (\Exception $e) {
			$this->logger->error('Failed to create federation provider', ['exception' => $e]);
			return [];
		}
	}
}
