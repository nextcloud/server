<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Authentication\RememberLogin;

use OC\AppFramework\ORM\EntityManager;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\ORM\Repository;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\Snowflake\ISnowflakeGenerator;
use Override;

/**
 * @template-extends Repository<RememberLoginToken>
 */
class RememberLoginTokenMapper extends Repository {
	public const string entityClass = RememberLoginToken::class;

	public function __construct(
		IDBConnection $connection,
		EntityManager $entityManager,
		private readonly ISnowflakeGenerator $snowflakeGenerator,
		private readonly IConfig $config,
	) {
		/** @psalm-suppress InternalMethod */
		parent::__construct($connection, $entityManager);
	}

	#[Override]
	public function insert(object $entity): object {
		/** @var RememberLoginToken $entity */
		$entity->token = $this->hashToken($entity->token);

		return parent::insert($entity);
	}

	/**
	 * @throws DoesNotExistException
	 */
	public function findByToken(string $token): RememberLoginToken {
		return $this->findOneBy(['token' => $this->hashToken($token)]);
	}

	public function deleteByToken(string $token): int {
		return $this->deleteBy(['token' => $this->hashToken($token)]);
	}

	/**
	 * Removes every remembered login token for given user
	 */
	public function deleteByUid(string $uid): int {
		return $this->deleteBy(['uid' => $uid]);
	}

	/**
	 * Updates old token with the new one and generates a new snowflake ID,
	 * refreshing the creation timestamp encoded in it
	 *
	 * @return int Number of updated rows
	 */
	public function rotateToken(string $oldToken, string $newToken): int {
		$qb = $this->connection->getQueryBuilder();
		$qb->update($this->getTableName())
			->set('id', $qb->createNamedParameter($this->snowflakeGenerator->nextId()))
			->set('token', $qb->createNamedParameter($this->hashToken($newToken)))
			->where($qb->expr()->eq('token', $qb->createNamedParameter($this->hashToken($oldToken))));

		return $qb->executeStatement();
	}

	/**
	 * Removes every remembered login token older than the given timestamp
	 */
	public function deleteOlderThan(int $timestamp): int {
		$qb = $this->connection->getQueryBuilder();
		$qb->delete($this->getTableName())
			->where($qb->expr()->lt('id', $qb->createNamedParameter($this->snowflakeGenerator->minForTimeId($timestamp))));

		return $qb->executeStatement();
	}

	private function hashToken(string $token): string {
		return hash('sha512', $token . $this->config->getSystemValueString('secret'));
	}
}
