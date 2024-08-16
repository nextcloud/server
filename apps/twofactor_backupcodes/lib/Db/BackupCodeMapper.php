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
		$qb->execute();
	}
}
