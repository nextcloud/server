<?php

namespace OCA\Dav\Migration;

use OCA\DAV\CardDAV\AddressBook;
use OCA\DAV\CardDAV\CardDavBackend;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\ILogger;
use Sabre\CardDAV\Plugin;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateAddressbooks {

	/** @var \OCP\IDBConnection */
	protected $dbConnection;

	/** @var CardDavBackend */
	private $backend;

	/**
	 * @param IDBConnection $dbConnection
	 * @param IConfig $config
	 * @param ILogger $logger
	 */
	function __construct(IDBConnection $dbConnection,
						 CardDavBackend $backend
	) {
		$this->dbConnection = $dbConnection;
		$this->backend = $backend;
	}

	private function verifyPreconditions() {
		if (!$this->dbConnection->tableExists('contacts_addressbooks')) {
			throw new \DomainException('Contacts tables are missing. Nothing to do.');
		}
	}

	/**
	 * @param string $user
	 */
	public function migrateForUser($user) {
		// get all addressbooks of that user
		$query = $this->dbConnection->getQueryBuilder();
		$books = $query->select()->from('contacts_addressbooks')
			->where($query->expr()->eq('user', $query->createNamedParameter($user)))
			->execute()
			->fetchAll();

		$principal = "principals/users/$user";
		foreach($books as $book) {

			$knownBooks = $this->backend->getAddressBooksByUri($principal, $book['uri']);
			if (!is_null($knownBooks)) {
				continue;
			}

			$newId = $this->backend->createAddressBook($principal, $book['uri'], [
				'{DAV:}displayname' => $book['displayname'],
				'{' . Plugin::NS_CARDDAV . '}addressbook-description' => $book['description']
			]);

			$this->migrateBook($book['id'], $newId);
			$this->migrateShares($book['id'], $newId);
		}
	}

	public function setup() {
		$this->verifyPreconditions();
	}

	/**
	 * @param int $addressBookId
	 * @param int $newAddressBookId
	 */
	private function migrateBook($addressBookId, $newAddressBookId) {
		$query = $this->dbConnection->getQueryBuilder();
		$cards = $query->select()->from('contacts_cards')
			->where($query->expr()->eq('addressbookid', $query->createNamedParameter($addressBookId)))
			->execute()
			->fetchAll();

		foreach ($cards as $card) {
			$this->backend->createCard($newAddressBookId, $card['uri'], $card['carddata']);
		}
	}

	/**
	 * @param int $addressBookId
	 * @param int $newAddressBookId
	 */
	private function migrateShares($addressBookId, $newAddressBookId) {
		$query = $this->dbConnection->getQueryBuilder();
		$shares = $query->select()->from('share')
			->where($query->expr()->eq('item_source', $query->createNamedParameter($addressBookId)))
			->andWhere($query->expr()->eq('item_type', $query->expr()->literal('addressbook')))
			->andWhere($query->expr()->in('share_type', [ $query->expr()->literal(0), $query->expr()->literal(1)]))
			->execute()
			->fetchAll();

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
