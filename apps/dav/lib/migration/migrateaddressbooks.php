<?php

namespace OCA\Dav\Migration;

use OCA\DAV\CardDAV\AddressBook;
use OCA\DAV\CardDAV\CardDavBackend;
use Sabre\CardDAV\Plugin;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateAddressbooks {

	/** @var AddressBookAdapter */
	protected $adapter;

	/** @var CardDavBackend */
	private $backend;

	/**
	 * @param AddressBookAdapter $adapter
	 * @param CardDavBackend $backend
	 */
	function __construct(AddressBookAdapter $adapter,
						 CardDavBackend $backend
	) {
		$this->adapter = $adapter;
		$this->backend = $backend;
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
			$this->backend->createCard($newAddressBookId, $card['uri'], $card['carddata']);
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
			if ($s['share_type'] === 1) {
				$prefix = 'principal:principals/groups/';
			}
			return [
				'href' => $prefix . $s['share_with']
			];
		}, $shares);

		$newAddressBook = $this->backend->getAddressBookById($newAddressBookId);
		$book = new AddressBook($this->backend, $newAddressBook);
		$this->backend->updateShares($book, $add, []);
	}
}
