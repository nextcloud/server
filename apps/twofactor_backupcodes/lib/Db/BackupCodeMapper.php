<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\TwoFactorBackupCodes\Db;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\ORM\Repository;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IUser;

/**
 * @template-extends Repository<BackupCode>
 */
class BackupCodeMapper extends Repository {
	public const string entityClass = BackupCode::class;

	/**
	 * @return \Generator<BackupCode>
	 */
	public function findByUser(IUser $user): \Generator {
		return $this->findBy([
			'userId' => $user->getUID(),
		]);
	}

	public function deleteByUser(IUser $user): void {
		$this->deleteBy([
			'userId' => $user->getUID(),
		]);
	}

	public function findOneByUser(IUser $user): ?BackupCode {
		try {
			return $this->findOneBy([
				'userId' => $user->getUID(),
			]);
		} catch (DoesNotExistException) {
			return null;
		}
	}

	/**
	 * Marks the backup code as used, if not already marked as used in DB.
	 * @return int number of affected rows
	 */
	public function markUsedIfUnused(BackupCode $code): int {
		$qb = $this->connection->getQueryBuilder();
		$qb->update($this->getTableName())
			->set('used', $qb->createNamedParameter(1, IQueryBuilder::PARAM_INT))
			->where($qb->expr()->eq('id', $qb->createNamedParameter($code->id, IQueryBuilder::PARAM_INT)))
			->andWhere($qb->expr()->eq('used', $qb->createNamedParameter(0, IQueryBuilder::PARAM_INT)));
		return $qb->executeStatement();
	}
}
