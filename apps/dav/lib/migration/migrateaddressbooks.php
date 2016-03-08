<?php
/**
 * @author Lukas Reschke <lukas@owncloud.com>
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
namespace OCA\Dav\Migration;

use OCA\DAV\CardDAV\AddressBook;
use OCA\DAV\CardDAV\CardDavBackend;
use OCP\ILogger;
use Sabre\CardDAV\Plugin;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateAddressbooks {

	/** @var AddressBookAdapter */
	protected $adapter;

	/** @var CardDavBackend */
	private $backend;

	/** @var ILogger */
	private $logger;

	/** @var OutputInterface */
	private $consoleOutput;


	/**
	 * @param AddressBookAdapter $adapter
	 * @param CardDavBackend $backend
	 */
	function __construct(AddressBookAdapter $adapter,
						 CardDavBackend $backend,
						 ILogger $logger,
						 OutputInterface $consoleOutput = null
	) {
		$this->adapter = $adapter;
		$this->backend = $backend;
		$this->logger = $logger;
		$this->consoleOutput = $consoleOutput;
	}

	/**
	 * @param string $user
	 */
	public function migrateForUser($user) {

		$this->adapter->foreachBook($user, function($book) use ($user) {
			$principal = "principals/users/$user";
			$knownBooks = $this->backend->getAddressBooksByUri($principal, $book['uri']);
			if (!is_null($knownBooks)) {
				return;
			}

			$newId = $this->backend->createAddressBook($principal, $book['uri'], [
				'{DAV:}displayname' => $book['displayname'],
				'{' . Plugin::NS_CARDDAV . '}addressbook-description' => $book['description']
			]);

			$this->migrateBook($book['id'], $newId);
			$this->migrateShares($book['id'], $newId);
		});
	}

	public function setup() {
		$this->adapter->setup();
	}

	/**
	 * @param int $addressBookId
	 * @param int $newAddressBookId
	 */
	private function migrateBook($addressBookId, $newAddressBookId) {
		$this->adapter->foreachCard($addressBookId, function($card) use ($newAddressBookId) {
			try {
				$this->backend->createCard($newAddressBookId, $card['uri'], $card['carddata']);
			} catch (\Exception $ex) {
				$eventId = $card['id'];
				$addressBookId = $card['addressbookid'];
				$msg = "One event could not be migrated. (id: $eventId, addressbookid: $addressBookId)";
				$this->logger->logException($ex, ['app' => 'dav', 'message' => $msg]);
				if (!is_null($this->consoleOutput)) {
					$this->consoleOutput->writeln($msg);
				}
			}
		});
	}

	/**
	 * @param int $addressBookId
	 * @param int $newAddressBookId
	 */
	private function migrateShares($addressBookId, $newAddressBookId) {
		$shares =$this->adapter->getShares($addressBookId);
		if (empty($shares)) {
			return;
		}

		$add = array_map(function($s) {
			$prefix = 'principal:principals/users/';
			if ((int)$s['share_type'] === 1) {
				$prefix = 'principal:principals/groups/';
			}
			return [
				'href' => $prefix . $s['share_with'],
				'readOnly' => !((int)$s['permissions'] === 31)
			];
		}, $shares);

		$newAddressBook = $this->backend->getAddressBookById($newAddressBookId);
		$book = new AddressBook($this->backend, $newAddressBook);
		$this->backend->updateShares($book, $add, []);
	}
}
