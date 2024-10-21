<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors*
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Federation;

use OC\Files\Filesystem;
use OCP\DB\Exception as DBException;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\HintException;
use OCP\IDBConnection;
use OCP\IL10N;

/**
 * Class DbHandler
 *
 * Handles all database calls for the federation app
 *
 * @todo Port to QBMapper
 *
 * @group DB
 * @package OCA\Federation
 */
class DbHandler {
	private string $dbTable = 'trusted_servers';

	public function __construct(
		private IDBConnection $connection,
		private IL10N $IL10N,
	) {
	}

	/**
	 * Add server to the list of trusted servers
	 *
	 * @throws HintException
	 */
	public function addServer(string $url): int {
		$hash = $this->hash($url);
		$url = rtrim($url, '/');
		$query = $this->connection->getQueryBuilder();
		$query->insert($this->dbTable)
			->values([
				'url' => $query->createParameter('url'),
				'url_hash' => $query->createParameter('url_hash'),
			])
			->setParameter('url', $url)
			->setParameter('url_hash', $hash);

		$result = $query->executeStatement();

		if ($result) {
			return $query->getLastInsertId();
		}

		$message = 'Internal failure, Could not add trusted server: ' . $url;
		$message_t = $this->IL10N->t('Could not add server');
		throw new HintException($message, $message_t);
		return -1;
	}

	/**
	 * Remove server from the list of trusted servers
	 */
	public function removeServer(int $id): void {
		$query = $this->connection->getQueryBuilder();
		$query->delete($this->dbTable)
			->where($query->expr()->eq('id', $query->createParameter('id')))
			->setParameter('id', $id);
		$query->executeStatement();
	}

	/**
	 * Get trusted server with given ID
	 *
	 * @return array{id: int, url: string, url_hash: string, token: ?string, shared_secret: ?string, status: int, sync_token: ?string}
	 * @throws \Exception
	 */
	public function getServerById(int $id): array {
		$query = $this->connection->getQueryBuilder();
		$query->select('*')->from($this->dbTable)
			->where($query->expr()->eq('id', $query->createParameter('id')))
			->setParameter('id', $id, IQueryBuilder::PARAM_INT);

		$qResult = $query->executeQuery();
		$result = $qResult->fetchAll();
		$qResult->closeCursor();

		if (empty($result)) {
			throw new \Exception('No Server found with ID: ' . $id);
		}

		return $result[0];
	}

	/**
	 * Get all trusted servers
	 *
	 * @return list<array{id: int, url: string, url_hash: string, shared_secret: ?string, status: int, sync_token: ?string}>
	 * @throws DBException
	 */
	public function getAllServer(): array {
		$query = $this->connection->getQueryBuilder();
		$query->select(['url', 'url_hash', 'id', 'status', 'shared_secret', 'sync_token'])
			->from($this->dbTable);
		$statement = $query->executeQuery();
		$result = $statement->fetchAll();
		$statement->closeCursor();
		return $result;
	}

	/**
	 * Check if server already exists in the database table
	 */
	public function serverExists(string $url): bool {
		$hash = $this->hash($url);
		$query = $this->connection->getQueryBuilder();
		$query->select('url')
			->from($this->dbTable)
			->where($query->expr()->eq('url_hash', $query->createParameter('url_hash')))
			->setParameter('url_hash', $hash);
		$statement = $query->executeQuery();
		$result = $statement->fetchAll();
		$statement->closeCursor();

		return !empty($result);
	}

	/**
	 * Write token to database. Token is used to exchange the secret
	 */
	public function addToken(string $url, string $token): void {
		$hash = $this->hash($url);
		$query = $this->connection->getQueryBuilder();
		$query->update($this->dbTable)
			->set('token', $query->createParameter('token'))
			->where($query->expr()->eq('url_hash', $query->createParameter('url_hash')))
			->setParameter('url_hash', $hash)
			->setParameter('token', $token);
		$query->executeStatement();
	}

	/**
	 * Get token stored in database
	 * @throws \Exception
	 */
	public function getToken(string $url): string {
		$hash = $this->hash($url);
		$query = $this->connection->getQueryBuilder();
		$query->select('token')->from($this->dbTable)
			->where($query->expr()->eq('url_hash', $query->createParameter('url_hash')))
			->setParameter('url_hash', $hash);

		$statement = $query->executeQuery();
		$result = $statement->fetch();
		$statement->closeCursor();

		if (!isset($result['token'])) {
			throw new \Exception('No token found for: ' . $url);
		}

		return $result['token'];
	}

	/**
	 * Add shared Secret to database
	 */
	public function addSharedSecret(string $url, string $sharedSecret): void {
		$hash = $this->hash($url);
		$query = $this->connection->getQueryBuilder();
		$query->update($this->dbTable)
			->set('shared_secret', $query->createParameter('sharedSecret'))
			->where($query->expr()->eq('url_hash', $query->createParameter('url_hash')))
			->setParameter('url_hash', $hash)
			->setParameter('sharedSecret', $sharedSecret);
		$query->executeStatement();
	}

	/**
	 * Get shared secret from database
	 */
	public function getSharedSecret(string $url): string {
		$hash = $this->hash($url);
		$query = $this->connection->getQueryBuilder();
		$query->select('shared_secret')->from($this->dbTable)
			->where($query->expr()->eq('url_hash', $query->createParameter('url_hash')))
			->setParameter('url_hash', $hash);

		$statement = $query->executeQuery();
		$result = $statement->fetch();
		$statement->closeCursor();
		return (string)$result['shared_secret'];
	}

	/**
	 * Set server status
	 */
	public function setServerStatus(string $url, int $status, ?string $token = null): void {
		$hash = $this->hash($url);
		$query = $this->connection->getQueryBuilder();
		$query->update($this->dbTable)
			->set('status', $query->createNamedParameter($status))
			->where($query->expr()->eq('url_hash', $query->createNamedParameter($hash)));
		if (!is_null($token)) {
			$query->set('sync_token', $query->createNamedParameter($token));
		}
		$query->executeStatement();
	}

	/**
	 * Get server status
	 */
	public function getServerStatus(string $url): int {
		$hash = $this->hash($url);
		$query = $this->connection->getQueryBuilder();
		$query->select('status')->from($this->dbTable)
			->where($query->expr()->eq('url_hash', $query->createParameter('url_hash')))
			->setParameter('url_hash', $hash);

		$statement = $query->executeQuery();
		$result = $statement->fetch();
		$statement->closeCursor();
		return (int)$result['status'];
	}

	/**
	 * Create hash from URL
	 */
	protected function hash(string $url): string {
		$normalized = $this->normalizeUrl($url);
		return sha1($normalized);
	}

	/**
	 * Normalize URL, used to create the sha1 hash
	 */
	protected function normalizeUrl(string $url): string {
		$normalized = $url;

		if (strpos($url, 'https://') === 0) {
			$normalized = substr($url, strlen('https://'));
		} elseif (strpos($url, 'http://') === 0) {
			$normalized = substr($url, strlen('http://'));
		}

		$normalized = Filesystem::normalizePath($normalized);
		$normalized = trim($normalized, '/');

		return $normalized;
	}

	public function auth(string $username, string $password): bool {
		if ($username !== 'system') {
			return false;
		}
		$query = $this->connection->getQueryBuilder();
		$query->select('url')->from($this->dbTable)
			->where($query->expr()->eq('shared_secret', $query->createNamedParameter($password)));

		$statement = $query->executeQuery();
		$result = $statement->fetch();
		$statement->closeCursor();
		return !empty($result);
	}
}
