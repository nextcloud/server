<?php

namespace OCA\Federation\Command;

use OCA\DAV\CardDAV\SyncService;
use OCA\Federation\DbHandler;
use OCA\Federation\TrustedServers;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IUserManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SyncFederationAddressBooks extends Command {

	/** @var DbHandler */
	protected $dbHandler;

	/** @var SyncService */
	private $syncService;

	/**
	 * @param IUserManager $userManager
	 * @param IDBConnection $dbHandler
	 * @param IConfig $config
	 */
	function __construct(DbHandler $dbHandler) {
		parent::__construct();

		$this->syncService = \OC::$server->query('CardDAVSyncService');
		$this->dbHandler = $dbHandler;
	}

	protected function configure() {
		$this
			->setName('federation:sync-addressbooks')
			->setDescription('Synchronizes addressbooks of all federated clouds');
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 */
	protected function execute(InputInterface $input, OutputInterface $output) {

		$progress = new ProgressBar($output);
		$progress->start();
		$trustedServers = $this->dbHandler->getAllServer();
		foreach ($trustedServers as $trustedServer) {
			$progress->advance();
			$url = $trustedServer['url'];
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
			$newToken = $this->syncService->syncRemoteAddressBook($url, 'system', $sharedSecret, $syncToken, $targetPrincipal, $targetBookId, $targetBookProperties);
			if ($newToken !== $syncToken) {
				$this->dbHandler->setServerStatus($url, TrustedServers::STATUS_OK, $newToken);
			}
		}
		$progress->finish();
		$output->writeln('');
	}
}
