<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Authentication\RememberLogin;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IConfig;
use OCP\IDBConnection;
use Override;

/**
 * @template-extends QBMapper<RememberLoginToken>
 */
class RememberLoginTokenMapper extends QBMapper {
	public function __construct(
		IDBConnection $db,
		private IConfig $config,
	) {
		parent::__construct($db, 'remember_login_tokens', RememberLoginToken::class);
	}

	#[Override]
	public function insert(Entity $entity): Entity {
		/** @var RememberLoginToken $entity */
		$entity->setToken($this->hashToken($entity->getToken()));

		return parent::insert($entity);
	}

	/**
	 * @throws DoesNotExistException
	 */
	public function findByToken(string $token): RememberLoginToken {
		$query = $this->db->getQueryBuilder();
		$query->select('*')
			->from($this->getTableName())
			->where($query->expr()->eq('token', $query->createNamedParameter($this->hashToken($token))));

		return $this->findEntity($query);
	}

	public function deleteByToken(string $token): int {
		$query = $this->db->getQueryBuilder();
		$query->delete($this->getTableName())
			->where($query->expr()->eq('token', $query->createNamedParameter($this->hashToken($token))));

		return $query->executeStatement();
	}

	/**
	 * Removes every remembered login token for given user
	 */
	public function deleteByUid(string $uid): int {
		$query = $this->db->getQueryBuilder();
		$query->delete($this->getTableName())
			->where($query->expr()->eq('uid', $query->createNamedParameter($uid)));

		return $query->executeStatement();
	}

	/**
	 * Removes every remembered login token older than the given timestamp
	 */
	public function deleteOlderThan(int $timestamp): int {
		$query = $this->db->getQueryBuilder();
		$query->delete($this->getTableName())
			->where($query->expr()->lt('created', $query->createNamedParameter($timestamp, IQueryBuilder::PARAM_INT)));

		return $query->executeStatement();
	}

	private function hashToken(string $token): string {
		return hash('sha512', $token . $this->config->getSystemValueString('secret'));
	}
}
