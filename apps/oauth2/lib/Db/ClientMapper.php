<?php
/**
 * @copyright Copyright (c) 2017 Lukas Reschke <lukas@statuscode.ch>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\OAuth2\Db;

use OCA\OAuth2\Exceptions\ClientNotFoundException;
use OCP\AppFramework\Db\Mapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

class ClientMapper extends Mapper {

	/**
	 * @param IDBConnection $db
	 */
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'oauth2_clients');
	}

	/**
	 * @param string $clientIdentifier
	 * @return Client
	 * @throws ClientNotFoundException
	 */
	public function getByIdentifier($clientIdentifier) {
		$qb = $this->db->getQueryBuilder();
		$qb
			->select('*')
			->from($this->tableName)
			->where($qb->expr()->eq('client_identifier', $qb->createNamedParameter($clientIdentifier)));
		$result = $qb->execute();
		$row = $result->fetch();
		$result->closeCursor();
		if($row === false) {
			throw new ClientNotFoundException();
		}
		return Client::fromRow($row);
	}

	/**
	 * @param string $uid internal uid of the client
	 * @return Client
	 * @throws ClientNotFoundException
	 */
	public function getByUid($uid) {
		$qb = $this->db->getQueryBuilder();
		$qb
			->select('*')
			->from($this->tableName)
			->where($qb->expr()->eq('id', $qb->createNamedParameter($uid, IQueryBuilder::PARAM_INT)));
		$result = $qb->execute();
		$row = $result->fetch();
		$result->closeCursor();
		if($row === false) {
			throw new ClientNotFoundException();
		}
		return Client::fromRow($row);
	}

	/**
	 * @return Client[]
	 */
	public function getClients() {
		$qb = $this->db->getQueryBuilder();
		$qb
			->select('*')
			->from($this->tableName);

		return $this->findEntities($qb->getSQL());
	}
}
