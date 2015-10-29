<?php
/**
 * @author Björn Schießle <schiessle@owncloud.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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


use OC\HintException;
use OCP\IDBConnection;
use OCP\IL10N;

class DbHandler {

	/** @var  IDBConnection */
	private $connection;

	/** @var  IL10N */
	private $l;

	/** @var string  */
	private $dbTable = 'trusted_servers';

	/**
	 * @param IDBConnection $connection
	 * @param IL10N $il10n
	 */
	public function __construct(
		IDBConnection $connection,
		IL10N $il10n
	) {
		$this->connection = $connection;
		$this->IL10N = $il10n;
	}

	/**
	 * add server to the list of trusted ownCloud servers
	 *
	 * @param $url
	 * @return int
	 * @throws HintException
	 */
	public function add($url) {
		$hash = md5($url);
		$query = $this->connection->getQueryBuilder();
		$query->insert($this->dbTable)
			->values(
				[
					'url' =>  $query->createParameter('url'),
					'url_hash' => $query->createParameter('url_hash'),
				]
			)
			->setParameter('url', $url)
			->setParameter('url_hash', $hash);

		$result = $query->execute();

		if ($result) {
			$id = $this->connection->lastInsertId();
			// Fallback, if lastInterId() doesn't work we need to perform a select
			// to get the ID (seems to happen sometimes on Oracle)
			if (!$id) {
				$server = $this->get($url);
				$id = $server['id'];
			}
			return $id;
		} else {
			$message = 'Internal failure, Could not add ownCloud as trusted server: ' . $url;
			$message_t = $this->l->t('Could not add server');
			throw new HintException($message, $message_t);
		}
	}

	/**
	 * remove server from the list of trusted ownCloud servers
	 *
	 * @param int $id
	 */
	public function remove($id) {
		$query = $this->connection->getQueryBuilder();
		$query->delete($this->dbTable)
			->where($query->expr()->eq('id', $query->createParameter('id')))
			->setParameter('id', $id);
		$query->execute();
	}

	/**
	 * get trusted server from database
	 *
	 * @param $url
	 * @return mixed
	 */
	public function get($url) {
		$query = $this->connection->getQueryBuilder();
		$query->select('url', 'id')->from($this->dbTable)
			->where($query->expr()->eq('url_hash', $query->createParameter('url_hash')))
			->setParameter('url_hash', md5($url));

		return $query->execute()->fetch();
	}

	/**
	 * get all trusted servers
	 *
	 * @return array
	 */
	public function getAll() {
		$query = $this->connection->getQueryBuilder();
		$query->select('url', 'id')->from($this->dbTable);
		$result = $query->execute()->fetchAll();
		return $result;
	}

	/**
	 * check if server already exists in the database table
	 *
	 * @param string $url
	 * @return bool
	 */
	public function exists($url) {
		$query = $this->connection->getQueryBuilder();
		$query->select('url')->from($this->dbTable)
			->where($query->expr()->eq('url_hash', $query->createParameter('url_hash')))
			->setParameter('url_hash', md5($url));
		$result = $query->execute()->fetchAll();

		return !empty($result);
	}

}
