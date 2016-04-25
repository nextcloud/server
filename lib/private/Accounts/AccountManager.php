<?php
/**
 * @author Björn Schießle <schiessle@owncloud.com>
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


namespace OC\Accounts;


use OCP\IDBConnection;

/**
 * Class AccountManager
 *
 * Manage system accounts table
 *
 * @group DB
 * @package OC\Accounts
 */
class AccountManager {

	/** @var  IDBConnection database connection */
	private $connection;

	/** @var string table name */
	private $table = 'accounts';

	/**
	 * AccountManager constructor.
	 *
	 * @param IDBConnection $connection
	 */
	public function __construct(IDBConnection $connection) {
		$this->connection = $connection;
	}

	public function updateUser($uid, $data) {
		$userData = $this->getUser($uid);
		if (empty($userData)) {
			$this->insertNewUser($uid, $data);
		} else {
			$this->updateExistingUser($uid, $data);
		}
	}

	/**
	 * get stored data from a given user
	 *
	 * @param $uid
	 * @return array
	 */
	public function getUser($uid) {
		$query = $this->connection->getQueryBuilder();
		$query->select('data')->from($this->table)
			->where($query->expr()->eq('uid', $query->createParameter('uid')))
			->setParameter('uid', $uid);
		$query->execute();
		$result = $query->execute()->fetchAll();

		if (empty($result)) {
			return [];
		}

		return json_decode($result[0]['data'], true);
	}

	/**
	 * add new user to accounts table
	 *
	 * @param string $uid
	 * @param array $data
	 */
	protected function insertNewUser($uid, $data) {
		$jsonEncodedData = json_encode($data);
		$query = $this->connection->getQueryBuilder();
		$query->insert($this->table)
			->values(
				[
					'uid' => $query->createNamedParameter($uid),
					'data' => $query->createNamedParameter($jsonEncodedData),
				]
			)
			->execute();
	}

	/**
	 * update existing user in accounts table
	 *
	 * @param string $uid
	 * @param array $data
	 */
	protected function updateExistingUser($uid, $data) {
		$jsonEncodedData = json_encode($data);
		$query = $this->connection->getQueryBuilder();
		$query->update($this->table)
			->set('data', $query->createNamedParameter($jsonEncodedData))
			->where($query->expr()->eq('uid', $query->createNamedParameter($uid)))
			->execute();
	}
}
