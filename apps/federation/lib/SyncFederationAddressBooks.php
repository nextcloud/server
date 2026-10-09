<?php

/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Federation;

use OCA\DAV\CardDAV\SyncService;
use OCA\DAV\Exception\InvalidSyncTokenException;
use OCP\AppFramework\Http;
use OCP\OCS\IDiscoveryService;
use Psr\Log\LoggerInterface;

class SyncFederationAddressBooks {
	public function __construct(
		protected DbHandler $dbHandler,
		private SyncService $syncService,
		private IDiscoveryService $ocsDiscoveryService,
		private LoggerInterface $logger,
	) {
	}

	/**
	 * @param \Closure $callback
	 */
	public function syncThemAll(\Closure $callback, bool $full = false) {
		$trustedServers = $this->dbHandler->getAllServer();
		foreach ($trustedServers as $trustedServer) {
			$url = $trustedServer['url'];
			$callback($url, null);
			$sharedSecret = $trustedServer['shared_secret'];
			$oldSyncToken = $trustedServer['sync_token'];

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
				$sync = fn (bool $fullSync): ?string => $this->syncAddressBook(
					$fullSync,
					$fullSync ? null : $oldSyncToken,
					$url,
					$cardDavUser,
					$addressBookUrl,
					$sharedSecret,
					$targetBookId,
					$targetPrincipal,
					$targetBookProperties
				);

				try {
					$syncToken = $sync($full);
				} catch (InvalidSyncTokenException $e) {
					// The remote no longer has the changes since our sync token, e.g. because it pruned them.
					// A delta sync would leave us with an incomplete address book, so start over.
					$this->logger->warning("Sync token for $url was rejected by the remote server, performing a full sync", [
						'exception' => $e,
					]);
					$syncToken = $sync(true);
				}

				if ($syncToken !== $oldSyncToken) {
					$this->dbHandler->setServerStatus($url, TrustedServers::STATUS_OK, $syncToken);
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

	/**
	 * @return ?string The new sync token
	 * @throws \Exception
	 */
	private function syncAddressBook(
		bool $full,
		?string $syncToken,
		string $url,
		string $cardDavUser,
		string $addressBookUrl,
		string $sharedSecret,
		string $targetBookId,
		string $targetPrincipal,
		array $targetBookProperties,
	): ?string {
		$book = $this->syncService->ensureSystemAddressBookExists($targetPrincipal, $targetBookId, $targetBookProperties);
		if ($full) {
			$this->syncService->markCardsAsPending($book['id']);
		}

		do {
			[$syncToken, $truncated] = $this->syncService->syncRemoteAddressBook(
				$url,
				$cardDavUser,
				$addressBookUrl,
				$sharedSecret,
				$syncToken,
				$targetBookId,
				$targetPrincipal,
				$targetBookProperties
			);
		} while ($truncated);

		if ($full) {
			$this->syncService->deletePendingCards($book['id']);
		}

		return $syncToken;
	}
}
