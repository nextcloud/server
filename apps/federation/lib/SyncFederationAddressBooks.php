<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bjoern Schiessle <bjoern@schiessle.org>
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\Federation;

use OC\OCS\DiscoveryService;
use OCA\DAV\CardDAV\SyncService;
use OCP\AppFramework\Http;
use OCP\OCS\IDiscoveryService;
use Psr\Log\LoggerInterface;

class SyncFederationAddressBooks {
	protected DbHandler $dbHandler;
	private SyncService $syncService;
	private DiscoveryService $ocsDiscoveryService;
	private LoggerInterface $logger;

	public function __construct(DbHandler $dbHandler,
		SyncService $syncService,
		IDiscoveryService $ocsDiscoveryService,
		LoggerInterface $logger
	) {
		$this->syncService = $syncService;
		$this->dbHandler = $dbHandler;
		$this->ocsDiscoveryService = $ocsDiscoveryService;
		$this->logger = $logger;
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
			$targetPrincipal = "principals/system/system";
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
