<?php
/**
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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
namespace OCA\DAV\Command;

use OCA\DAV\CardDAV\CardDavBackend;
use OCA\DAV\CardDAV\Converter;
use OCA\DAV\Connector\Sabre\Principal;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IUser;
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
	 * @param IConfig $config
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

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 */
	protected function execute(InputInterface $input, OutputInterface $output) {
		$principalBackend = new Principal(
				$this->config,
				$this->userManager
		);

		$this->backend = new CardDavBackend($this->dbConnection, $principalBackend);

		// ensure system addressbook exists
		$systemAddressBook = $this->ensureSystemAddressBookExists();
		$converter = new Converter();

		$output->writeln('Syncing users ...');
		$progress = new ProgressBar($output);
		$progress->start();
		$this->userManager->callForAllUsers(function($user) use ($systemAddressBook, $converter, $progress) {
			/** @var IUser $user */
			$name = $user->getBackendClassName();
			$userId = $user->getUID();

			$cardId = "$name:$userId.vcf";
			$card = $this->backend->getCard($systemAddressBook['id'], $cardId);
			if ($card === false) {
				$vCard = $converter->createCardFromUser($user);
				$this->backend->createCard($systemAddressBook['id'], $cardId, $vCard->serialize());
			} else {
				$vCard = Reader::read($card['carddata']);
				if ($converter->updateCard($vCard, $user)) {
					$this->backend->updateCard($systemAddressBook['id'], $cardId, $vCard->serialize());
				}
			}
			$progress->advance();
		});

		// remove no longer existing
		$allCards = $this->backend->getCards($systemAddressBook['id']);
		foreach($allCards as $card) {
			$vCard = Reader::read($card['carddata']);
			$uid = $vCard->UID->getValue();
			// load backend and see if user exists
			if (!$this->userManager->userExists($uid)) {
				$this->backend->deleteCard($systemAddressBook['id'], $card['uri']);
			}
		}

		$progress->finish();
		$output->writeln('');
	}

	protected function ensureSystemAddressBookExists() {
		$book = $this->backend->getAddressBooksByUri('system');
		if (!is_null($book)) {
			return $book;
		}
		$systemPrincipal = "principals/system/system";
		$this->backend->createAddressBook($systemPrincipal, 'system', [
			'{' . Plugin::NS_CARDDAV . '}addressbook-description' => 'System addressbook which holds all users of this instance'
		]);

		return $this->backend->getAddressBooksByUri('system');
	}
}
