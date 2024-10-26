<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\UserStatus\Db;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\UserStatus\IUserStatus;

/**
 * @template-extends QBMapper<UserStatus>
 */
class UserStatusMapper extends QBMapper {

	/**
	 * @param IDBConnection $db
	 */
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'user_status');
	}

	/**
	 * @param int|null $limit
	 * @param int|null $offset
	 * @return UserStatus[]
	 */
	public function findAll(?int $limit = null, ?int $offset = null):array {
		$qb = $this->db->getQueryBuilder();
		$qb
			->select('*')
			->from($this->tableName);

		if ($limit !== null) {
			$qb->setMaxResults($limit);
		}
		if ($offset !== null) {
			$qb->setFirstResult($offset);
		}

		return $this->findEntities($qb);
	}

	/**
	 * @param int|null $limit
	 * @param int|null $offset
	 * @return array
	 */
	public function findAllRecent(?int $limit = null, ?int $offset = null): array {
		$qb = $this->db->getQueryBuilder();

		$qb
			->select('*')
			->from($this->tableName)
			->orderBy('status_message_timestamp', 'DESC')
			->where($qb->expr()->andX(
				$qb->expr()->neq('status_message_timestamp', $qb->createNamedParameter(0, IQueryBuilder::PARAM_INT), IQueryBuilder::PARAM_INT),
				$qb->expr()->orX(
					$qb->expr()->notIn('status', $qb->createNamedParameter([IUserStatus::ONLINE, IUserStatus::AWAY, IUserStatus::OFFLINE], IQueryBuilder::PARAM_STR_ARRAY)),
					$qb->expr()->isNotNull('message_id'),
					$qb->expr()->isNotNull('custom_icon'),
					$qb->expr()->isNotNull('custom_message'),
				),
				$qb->expr()->notLike('user_id', $qb->createNamedParameter($this->db->escapeLikeParameter('_') . '%'))
			));

		if ($limit !== null) {
			$qb->setMaxResults($limit);
		}
		if ($offset !== null) {
			$qb->setFirstResult($offset);
		}

		return $this->findEntities($qb);
	}

	/**
	 * @param string $userId
	 * @return UserStatus
	 * @throws DoesNotExistException
	 */
	public function findByUserId(string $userId, bool $isBackup = false): UserStatus {
		$qb = $this->db->getQueryBuilder();
		$qb
			->select('*')
			->from($this->tableName)
			->where($qb->expr()->eq('user_id', $qb->createNamedParameter($isBackup ? '_' . $userId : $userId, IQueryBuilder::PARAM_STR)));

		return $this->findEntity($qb);
	}

	/**
	 * @param array $userIds
	 * @return array
	 */
	public function findByUserIds(array $userIds): array {
		$qb = $this->db->getQueryBuilder();
		$qb
			->select('*')
			->from($this->tableName)
			->where($qb->expr()->in('user_id', $qb->createNamedParameter($userIds, IQueryBuilder::PARAM_STR_ARRAY)));

		return $this->findEntities($qb);
	}

	/**
	 * @param int $olderThan
	 * @param int $now
	 */
	public function clearStatusesOlderThan(int $olderThan, int $now): void {
		$qb = $this->db->getQueryBuilder();
		$qb->update($this->tableName)
			->set('status', $qb->createNamedParameter(IUserStatus::OFFLINE))
			->set('is_user_defined', $qb->createNamedParameter(false, IQueryBuilder::PARAM_BOOL))
			->set('status_timestamp', $qb->createNamedParameter($now, IQueryBuilder::PARAM_INT))
			->where($qb->expr()->lte('status_timestamp', $qb->createNamedParameter($olderThan, IQueryBuilder::PARAM_INT)))
			->andWhere($qb->expr()->neq('status', $qb->createNamedParameter(IUserStatus::OFFLINE)))
			->andWhere($qb->expr()->orX(
				$qb->expr()->eq('is_user_defined', $qb->createNamedParameter(false, IQueryBuilder::PARAM_BOOL), IQueryBuilder::PARAM_BOOL),
				$qb->expr()->eq('status', $qb->createNamedParameter(IUserStatus::ONLINE))
			));

		$qb->executeStatement();
	}

	/**
	 * Clear all statuses older than a given timestamp
	 *
	 * @param int $timestamp
	 */
	public function clearOlderThanClearAt(int $timestamp): void {
		$qb = $this->db->getQueryBuilder();
		$qb->delete($this->tableName)
			->where($qb->expr()->isNotNull('clear_at'))
			->andWhere($qb->expr()->lte('clear_at', $qb->createNamedParameter($timestamp, IQueryBuilder::PARAM_INT)));

		$qb->executeStatement();
	}


	/**
	 * Deletes a user status so we can restore the backup
	 *
	 * @param string $userId
	 * @param string $messageId
	 * @return bool True if an entry was deleted
	 */
	public function deleteCurrentStatusToRestoreBackup(string $userId, string $messageId): bool {
		$qb = $this->db->getQueryBuilder();
		$qb->delete($this->tableName)
			->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
			->andWhere($qb->expr()->eq('message_id', $qb->createNamedParameter($messageId)))
			->andWhere($qb->expr()->eq('is_backup', $qb->createNamedParameter(false, IQueryBuilder::PARAM_BOOL)));
		return $qb->executeStatement() > 0;
	}

	public function deleteByIds(array $ids): void {
		$qb = $this->db->getQueryBuilder();
		$qb->delete($this->tableName)
			->where($qb->expr()->in('id', $qb->createNamedParameter($ids, IQueryBuilder::PARAM_INT_ARRAY)));
		$qb->executeStatement();
	}

	/**
	 * @param string $userId
	 * @return bool
	 * @throws \OCP\DB\Exception
	 */
	public function createBackupStatus(string $userId): bool {
		// Prefix user account with an underscore because user_id is marked as unique
		// in the table. Starting a username with an underscore is not allowed so this
		// shouldn't create any trouble.
		$qb = $this->db->getQueryBuilder();
		$qb->update($this->tableName)
			->set('is_backup', $qb->createNamedParameter(true, IQueryBuilder::PARAM_BOOL))
			->set('user_id', $qb->createNamedParameter('_' . $userId))
			->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));
		return $qb->executeStatement() > 0;
	}

	public function restoreBackupStatuses(array $ids): void {
		$qb = $this->db->getQueryBuilder();
		$qb->update($this->tableName)
			->set('is_backup', $qb->createNamedParameter(false, IQueryBuilder::PARAM_BOOL))
			->set('user_id', $qb->func()->substring('user_id', $qb->createNamedParameter(2, IQueryBuilder::PARAM_INT)))
			->where($qb->expr()->in('id', $qb->createNamedParameter($ids, IQueryBuilder::PARAM_INT_ARRAY)));

		$qb->executeStatement();
	}
}
