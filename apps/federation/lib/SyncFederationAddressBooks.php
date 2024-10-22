<?php

/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Federation;

use OC\OCS\DiscoveryService;
use OCA\DAV\CardDAV\SyncService;
use OCP\AppFramework\Http;
use OCP\OCS\IDiscoveryService;
use Psr\Log\LoggerInterface;

class SyncFederationAddressBooks {
	private DiscoveryService $ocsDiscoveryService;

	public function __construct(
		protected DbHandler $dbHandler,
		private SyncService $syncService,
		IDiscoveryService $ocsDiscoveryService,
		private LoggerInterface $logger,
	) {
		$this->ocsDiscoveryService = $ocsDiscoveryService;
	}

	/**
	 * @param \Closure $callback
	 */
	public function syncThemAll(\Closure $callback) {
		$trustedServers = $this->dbHandler->getAllServer();
		foreach ($trustedServers as $trustedServer) {
			$url = $trustedServer['url'];
			$callback($url, null);
			$sharedSecret = $trustedServer['shared_secret'];
			$syncToken = $trustedServer['sync_token'];

			$endPoints = $this->ocsDiscoveryService->discover($url, 'FEDERATED_SHARING');
			$cardDavUser = $endPoints['carddav-user'] ?? 'system';
			$addressBookUrl = isset($endPoints['system-address-book']) ? trim($endPoints['system-address-book'], '/') : 'remote.php/dav/addressbooks/system/system/system';

			if (is_null($sharedSecret)) {
				$this->logger->debug("Shared secret for $url is null");
				continue;
			}
			$targetBookId = $trustedServer['url_hash'];
			$targetPrincipal = 'principals/system/system';
			$targetBookProperties = [
				'{DAV:}displayname' => $url
			];
			try {
				$newToken = $this->syncService->syncRemoteAddressBook($url, $cardDavUser, $addressBookUrl, $sharedSecret, $syncToken, $targetBookId, $targetPrincipal, $targetBookProperties);
				if ($newToken !== $syncToken) {
					$this->dbHandler->setServerStatus($url, TrustedServers::STATUS_OK, $newToken);
				} else {
					$this->logger->debug("Sync Token for $url unchanged from previous sync");
					// The server status might have been changed to a failure status in previous runs.
					if ($this->dbHandler->getServerStatus($url) !== TrustedServers::STATUS_OK) {
						$this->dbHandler->setServerStatus($url, TrustedServers::STATUS_OK);
					}
				}
			} catch (\Exception $ex) {
				if ($ex->getCode() === Http::STATUS_UNAUTHORIZED) {
					$this->dbHandler->setServerStatus($url, TrustedServers::STATUS_ACCESS_REVOKED);
					$this->logger->error("Server sync for $url failed because of revoked access.", [
						'exception' => $ex,
					]);
				} else {
					$this->dbHandler->setServerStatus($url, TrustedServers::STATUS_FAILURE);
					$this->logger->error("Server sync for $url failed.", [
						'exception' => $ex,
					]);
				}
				$callback($url, $ex);
			}
		}
	}
}
