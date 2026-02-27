<?php

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Federation;

use OCP\Federation\ICloudFederationFactory;
use OCP\Federation\ICloudFederationNotification;
use OCP\Federation\ICloudFederationShare;
use OCP\Federation\ICloudIdManager;
use OCP\OCM\Exceptions\OCMProviderException;
use OCP\OCM\IOCMDiscoveryService;
use Psr\Log\LoggerInterface;

class CloudFederationFactory implements ICloudFederationFactory {
	public function __construct(
		private IOCMDiscoveryService $ocmDiscoveryService,
		private ICloudIdManager $cloudIdManager,
		private LoggerInterface $logger,
	) {
	}
	/**
	 * get a CloudFederationShare Object to prepare a share you want to send
	 *
	 * @param string $shareWith
	 * @param string $name resource name (e.g. document.odt)
	 * @param string $description share description (optional)
	 * @param string $providerId resource UID on the provider side
	 * @param string $owner provider specific UID of the user who owns the resource
	 * @param string $ownerDisplayName display name of the user who shared the item
	 * @param string $sharedBy provider specific UID of the user who shared the resource
	 * @param string $sharedByDisplayName display name of the user who shared the resource
	 * @param string $sharedSecret used to authenticate requests across servers
	 * @param string $shareType ('group' or 'user' share)
	 * @param $resourceType ('file', 'calendar',...)
	 * @return ICloudFederationShare
	 *
	 * @since 14.0.0
	 */
	public function getCloudFederationShare($shareWith, $name, $description, $providerId, $owner, $ownerDisplayName, $sharedBy, $sharedByDisplayName, $sharedSecret, $shareType, $resourceType) {
		$useExchangeToken = false;
		$remoteDomain = null;

		try {
			$cloudId = $this->cloudIdManager->resolveCloudId($shareWith);
			$remoteDomain = $cloudId->getRemote();

			try {
				$remoteProvider = $this->ocmDiscoveryService->discover($remoteDomain);
				$capabilities = $remoteProvider->getCapabilities();

				$useExchangeToken = in_array('exchange-token', $capabilities, true);

				$this->logger->debug('OCM provider capabilities discovered', [
					'remote' => $remoteDomain,
					'capabilities' => $capabilities,
					'useExchangeToken' => $useExchangeToken,
				]);
			} catch (OCMProviderException $e) {
				$this->logger->warning('Failed to discover OCM provider, using legacy share method', [
					'remote' => $remoteDomain,
					'exception' => $e->getMessage(),
				]);
			}
		} catch (\InvalidArgumentException $e) {
			$this->logger->warning('Invalid cloud ID format, using legacy share method', [
				'shareWith' => $shareWith,
				'exception' => $e->getMessage(),
			]);
		}

		return new CloudFederationShare(
			$shareWith,
			$name,
			$description,
			$providerId,
			$owner,
			$ownerDisplayName,
			$sharedBy,
			$sharedByDisplayName,
			$shareType,
			$resourceType,
			$sharedSecret,
			$useExchangeToken,
			$remoteDomain
		);
	}

	/**
	 * get a Cloud FederationNotification object to prepare a notification you
	 * want to send
	 *
	 * @return ICloudFederationNotification
	 *
	 * @since 14.0.0
	 */
	public function getCloudFederationNotification() {
		return new CloudFederationNotification();
	}
}
