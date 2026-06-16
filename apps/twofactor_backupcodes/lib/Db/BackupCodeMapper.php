<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\TwoFactorBackupCodes\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\IUser;

/**
 * @template-extends QBMapper<BackupCode>
 */
class BackupCodeMapper extends QBMapper {
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'twofactor_backupcodes');
	}

	/**
	 * @param IUser $user
	 * @return BackupCode[]
	 */
	public function getBackupCodes(IUser $user): array {
		/* @var IQueryBuilder $qb */
		$qb = $this->db->getQueryBuilder();

		$qb->select('id', 'user_id', 'code', 'used')
			->from('twofactor_backupcodes')
			->where($qb->expr()->eq('user_id', $qb->createNamedParameter($user->getUID())));

		return self::findEntities($qb);
	}

	/**
	 * @param IUser $user
	 */
	public function deleteCodes(IUser $user): void {
		$this->deleteCodesByUserId($user->getUID());
	}

	/**
	 * @param string $uid
	 */
	public function deleteCodesByUserId(string $uid): void {
		/* @var IQueryBuilder $qb */
		$qb = $this->db->getQueryBuilder();

		$qb->delete('twofactor_backupcodes')
			->where($qb->expr()->eq('user_id', $qb->createNamedParameter($uid)));
		$qb->executeStatement();
	}

	/**
	 * Marks the backup code as used, if not already marked as used in DB.
	 * @return int number of affected rows
	 */
	public function markUsedIfUnused(BackupCode $code): int {
		$qb = $this->db->getQueryBuilder();
		$qb->update($this->getTableName())
			->set('used', $qb->createNamedParameter(1, IQueryBuilder::PARAM_INT))
			->where($qb->expr()->eq('id', $qb->createNamedParameter($code->getId(), IQueryBuilder::PARAM_INT)))
			->andWhere($qb->expr()->eq('used', $qb->createNamedParameter(0, IQueryBuilder::PARAM_INT)));
		return $qb->executeStatement();
	}
}
