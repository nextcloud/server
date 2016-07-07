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

use OCP\IDBConnection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AddressBookAdapter {

	/** @var \OCP\IDBConnection */
	protected $dbConnection;

	/** @var string */
	private $sourceBookTable;

	/** @var string */
	private $sourceCardsTable;

	/**
	 * @param IDBConnection $dbConnection
	 * @param string $sourceBookTable
	 * @param string $sourceCardsTable
	 */
	function __construct(IDBConnection $dbConnection,
						 $sourceBookTable = 'contacts_addressbooks',
						 $sourceCardsTable = 'contacts_cards') {
		$this->dbConnection = $dbConnection;
		$this->sourceBookTable = $sourceBookTable;
		$this->sourceCardsTable = $sourceCardsTable;
	}

	/**
	 * @param string $user
	 * @param \Closure $callBack
	 */
	public function foreachBook($user, \Closure $callBack) {
		// get all addressbooks of that user
		$query = $this->dbConnection->getQueryBuilder();
		$stmt = $query->select('*')->from($this->sourceBookTable)
			->where($query->expr()->eq('userid', $query->createNamedParameter($user)))
			->execute();

		while($row = $stmt->fetch()) {
			$callBack($row);
		}
	}

	public function setup() {
		if (!$this->dbConnection->tableExists($this->sourceBookTable)) {
			throw new NothingToDoException('Contacts tables are missing');
		}
	}

	/**
	 * @param int $addressBookId
	 * @param \Closure $callBack
	 */
	public function foreachCard($addressBookId, \Closure $callBack) {
		$query = $this->dbConnection->getQueryBuilder();
		$stmt = $query->select('*')->from($this->sourceCardsTable)
			->where($query->expr()->eq('addressbookid', $query->createNamedParameter($addressBookId)))
			->execute();

		while($row = $stmt->fetch()) {
			$callBack($row);
		}
	}

	/**
	 * @param int $addressBookId
	 * @return array
	 */
	public function getShares($addressBookId) {
		$query = $this->dbConnection->getQueryBuilder();
		$shares = $query->select('*')->from('share')
			->where($query->expr()->eq('item_source', $query->createNamedParameter($addressBookId)))
			->andWhere($query->expr()->eq('item_type', $query->expr()->literal('addressbook')))
			->andWhere($query->expr()->in('share_type', [ $query->expr()->literal(0), $query->expr()->literal(1)]))
			->execute()
			->fetchAll();

		return $shares;
	}
}
