<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Security;

use OCP\IDBConnection;
use OCP\Security\ICredentialsManager;
use OCP\Security\ICrypto;

/**
 * Store and retrieve credentials for external services
 *
 * @package OC\Security
 */
class CredentialsManager implements ICredentialsManager {
	public const DB_TABLE = 'storages_credentials';

	public function __construct(
		protected ICrypto $crypto,
		protected IDBConnection $dbConnection,
	) {
	}

	public function store(string $userId, string $identifier, $credentials): void {
		$value = $this->crypto->encrypt(json_encode($credentials));

		$this->dbConnection->setValues(self::DB_TABLE, [
			'user' => $userId,
			'identifier' => $identifier,
		], [
			'credentials' => $value,
		]);
	}

	public function retrieve(string $userId, string $identifier): mixed {
		$qb = $this->dbConnection->getQueryBuilder();
		$qb->select('credentials')
			->from(self::DB_TABLE)
			->where($qb->expr()->eq('identifier', $qb->createNamedParameter($identifier)));

		if ($userId === '') {
			$qb->andWhere($qb->expr()->emptyString('user'));
		} else {
			$qb->andWhere($qb->expr()->eq('user', $qb->createNamedParameter($userId)));
		}

		$qResult = $qb->execute();
		$result = $qResult->fetch();
		$qResult->closeCursor();

		if (!$result) {
			return null;
		}
		$value = $result['credentials'];

		return json_decode($this->crypto->decrypt($value), true);
	}

	public function delete(string $userId, string $identifier): int {
		$qb = $this->dbConnection->getQueryBuilder();
		$qb->delete(self::DB_TABLE)
			->where($qb->expr()->eq('identifier', $qb->createNamedParameter($identifier)));

		if ($userId === '') {
			$qb->andWhere($qb->expr()->emptyString('user'));
		} else {
			$qb->andWhere($qb->expr()->eq('user', $qb->createNamedParameter($userId)));
		}

		return $qb->execute();
	}

	public function erase(string $userId): int {
		$qb = $this->dbConnection->getQueryBuilder();
		$qb->delete(self::DB_TABLE)
			->where($qb->expr()->eq('user', $qb->createNamedParameter($userId)))
		;
		return $qb->execute();
	}
}
