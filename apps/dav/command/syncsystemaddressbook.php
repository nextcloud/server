<?php

namespace OCA\DAV\Command;

use OCA\DAV\CardDAV\CardDavBackend;
use OCA\DAV\Connector\Sabre\Principal;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IUserManager;
use Sabre\CardDAV\Plugin;
use Sabre\VObject\Component\VCard;
use Sabre\VObject\Property\Text;
use Sabre\VObject\Reader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SyncSystemAddressBook extends Command {

	/** @var IUserManager */
	protected $userManager;

	/** @var \OCP\IDBConnection */
	protected $dbConnection;

	/** @var IConfig */
	protected $config;

	/** @var CardDavBackend */
	private $backend;

	/**
	 * @param IUserManager $userManager
	 * @param IDBConnection $dbConnection
	 */
	function __construct(IUserManager $userManager, IDBConnection $dbConnection, IConfig $config) {
		parent::__construct();
		$this->userManager = $userManager;
		$this->dbConnection = $dbConnection;
		$this->config = $config;
	}

	protected function configure() {
		$this
			->setName('dav:sync-system-addressbook')
			->setDescription('Synchronizes users to the system addressbook');
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$principalBackend = new Principal(
				$this->config,
				$this->userManager
		);

		$this->backend = new CardDavBackend($this->dbConnection, $principalBackend);

		// ensure system addressbook exists
		$systemAddressBook = $this->ensureSystemAddressBookExists();

		$output->writeln('Syncing users ...');
		$progress = new ProgressBar($output);
		$progress->start();
		$page = 0;
		foreach( $this->userManager->getBackends() as $backend) {
			$users = $backend->getUsers('', 50, $page++);
			foreach($users as $user) {
				$user = $this->userManager->get($user);
				$name = $user->getBackendClassName();
				$userId = $user->getUID();
				$displayName = $user->getDisplayName();
				$cardId = "$name:$userId.vcf";
				$card = $this->backend->getCard($systemAddressBook['id'], $cardId);
				if ($card === false) {
					$vCard = new VCard();
					$vCard->add(new Text($vCard, 'UID', $user->getUID()));
					$vCard->add(new Text($vCard, 'FN', $displayName));
//					$vCard->add(new Text($vCard, 'EMAIL', $user->getEMailAddress()));
					//$vCard->add(new Binary($vCard, 'PHOTO', $user->getAvatar()));
					$vCard->validate();
					$this->backend->createCard($systemAddressBook['id'], $cardId, $vCard->serialize());
				} else {
					$updated = false;
					$vCard = Reader::read($card['carddata']);
					if($vCard->FN !== $user->getDisplayName()) {
						$vCard->FN = new Text($vCard, 'FN', $displayName);
						$updated = true;
					}
					if ($updated) {
						$this->backend->updateCard($systemAddressBook['id'], $cardId, $vCard->serialize());
					}
				}
				$progress->advance();
			}
		}
		$progress->finish();
	}

	protected function ensureSystemAddressBookExists() {
		$book = $this->backend->getAddressBooksByUri('system');
		if (!is_null($book)) {
			return $book;
		}
		$systemPrincipal = "principals/system";
		$this->backend->createAddressBook($systemPrincipal, 'system', [
			'{' . Plugin::NS_CARDDAV . '}addressbook-description' => 'System addressbook which holds all users of this instance'
		]);

		return $this->backend->getAddressBooksByUri('system');
	}
}
