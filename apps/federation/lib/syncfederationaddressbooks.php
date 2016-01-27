<?php

namespace OCA\Federation;

use OCA\DAV\CardDAV\SyncService;
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
			$targetBookId = sha1($url);
			$targetPrincipal = "principals/system/system";
			$targetBookProperties = [
					'{DAV:}displayname' => $url
			];
			try {
				$newToken = $this->syncService->syncRemoteAddressBook($url, 'system', $sharedSecret, $syncToken, $targetPrincipal, $targetBookId, $targetBookProperties);
				if ($newToken !== $syncToken) {
					$this->dbHandler->setServerStatus($url, TrustedServers::STATUS_OK, $newToken);
				}
			} catch (\Exception $ex) {
				$callback($url, $ex);
			}
		}
	}
}
