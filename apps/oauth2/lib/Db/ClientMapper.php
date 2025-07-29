<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\OAuth2\Db;

use OCA\OAuth2\Exceptions\ClientNotFoundException;
use OCP\AppFramework\Db\IMapperException;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/**
 * @template-extends QBMapper<Client>
 */
class ClientMapper extends QBMapper {

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
	public function getByIdentifier(string $clientIdentifier): Client {
		$qb = $this->db->getQueryBuilder();
		$qb
			->select('*')
			->from($this->tableName)
			->where($qb->expr()->eq('client_identifier', $qb->createNamedParameter($clientIdentifier)));

		try {
			$client = $this->findEntity($qb);
		} catch (IMapperException $e) {
			throw new ClientNotFoundException('could not find client ' . $clientIdentifier, 0, $e);
		}
		return $client;
	}

	/**
	 * @param int $id internal id of the client
	 * @return Client
	 * @throws ClientNotFoundException
	 */
	public function getByUid(int $id): Client {
		$qb = $this->db->getQueryBuilder();
		$qb
			->select('*')
			->from($this->tableName)
			->where($qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)));

		try {
			$client = $this->findEntity($qb);
		} catch (IMapperException $e) {
			throw new ClientNotFoundException('could not find client with id ' . $id, 0, $e);
		}
		return $client;
	}

	/**
	 * @return Client[]
	 */
	public function getClients(): array {
		$qb = $this->db->getQueryBuilder();
		$qb
			->select('*')
			->from($this->tableName);

		return $this->findEntities($qb);
	}
}
