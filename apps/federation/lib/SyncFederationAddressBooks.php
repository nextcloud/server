<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Björn Schießle <bjoern@schiessle.org>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\Federation;

use OCA\DAV\CardDAV\SyncService;
use OCP\AppFramework\Http;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SyncFederationAddressBooks {

	/** @var DbHandler */
	protected $dbHandler;

	/** @var SyncService */
	private $syncService;

	/**
	 * @param DbHandler $dbHandler
	 * @param SyncService $syncService
	 */
	function __construct(DbHandler $dbHandler, SyncService $syncService) {
		$this->syncService = $syncService;
		$this->dbHandler = $dbHandler;
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

			if (is_null($sharedSecret)) {
				continue;
			}
			$targetBookId = $trustedServer['url_hash'];
			$targetPrincipal = "principals/system/system";
			$targetBookProperties = [
					'{DAV:}displayname' => $url
			];
			try {
				$newToken = $this->syncService->syncRemoteAddressBook($url, 'system', $sharedSecret, $syncToken, $targetBookId, $targetPrincipal, $targetBookProperties);
				if ($newToken !== $syncToken) {
					$this->dbHandler->setServerStatus($url, TrustedServers::STATUS_OK, $newToken);
				}
			} catch (\Exception $ex) {
				if ($ex->getCode() === Http::STATUS_UNAUTHORIZED) {
					$this->dbHandler->setServerStatus($url, TrustedServers::STATUS_ACCESS_REVOKED);
				}
				$callback($url, $ex);
			}
		}
	}
}
