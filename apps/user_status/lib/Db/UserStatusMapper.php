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
			->from($this->tableName)
			->where($qb->expr()->eq('is_backup', $qb->createNamedParameter(false, IQueryBuilder::PARAM_BOOL)));

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
				$qb->expr()->eq('is_backup', $qb->createNamedParameter(false, IQueryBuilder::PARAM_BOOL))
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
			))
			->andWhere($qb->expr()->eq('is_backup', $qb->createNamedParameter(false, IQueryBuilder::PARAM_BOOL)));

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
			->andWhere($qb->expr()->lte('clear_at', $qb->createNamedParameter($timestamp, IQueryBuilder::PARAM_INT)))
			->andWhere($qb->expr()->eq('is_backup', $qb->createNamedParameter(false, IQueryBuilder::PARAM_BOOL)));

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

	/**
	 * Deletes backup rows that can never be restored, because the matching live
	 * status is gone or is no longer on one of the automated statuses that would
	 * revert into it.
	 *
	 * Such a row is not just clutter: while it exists, createBackupStatus() keeps
	 * hitting the unique constraint on user_id, which makes setUserStatus()
	 * silently abort every automated status change for that user.
	 *
	 * @param list<string> $automatedMessageIds Message ids that own a backup
	 * @return int Number of deleted backup rows
	 */
	public function deleteStrandedBackups(array $automatedMessageIds): int {
		return $this->deleteByIds($this->findStrandedBackupIds($automatedMessageIds));
	}

	/**
	 * Ids of backup rows that can never be restored. See deleteStrandedBackups().
	 *
	 * A backup is reachable exactly when the live row it belongs to still carries
	 * one of the automated message ids, because that is what revertUserStatus()
	 * matches on. The live row is the one whose user id is the backup's user id
	 * without the underscore prefix, so the two are matched with a self join.
	 *
	 * @param list<string> $automatedMessageIds
	 * @return list<int>
	 */
	public function findStrandedBackupIds(array $automatedMessageIds): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('b.id')
			->from($this->tableName, 'b')
			->where($qb->expr()->eq('b.is_backup', $qb->createNamedParameter(true, IQueryBuilder::PARAM_BOOL)));

		if ($automatedMessageIds === []) {
			// No automated status can own a backup, so none of them is reachable.
			return $this->fetchIds($qb);
		}

		// Not filtering the live side on is_backup is deliberate: a row whose
		// is_backup is NULL is still treated as a live row, so unexpected data
		// errs towards keeping the backup.
		$qb->leftJoin('b', $this->tableName, 'l', $qb->expr()->andX(
			$qb->expr()->eq('l.user_id', $qb->func()->substring('b.user_id', $qb->createNamedParameter(2, IQueryBuilder::PARAM_INT))),
			$qb->expr()->in('l.message_id', $qb->createNamedParameter($automatedMessageIds, IQueryBuilder::PARAM_STR_ARRAY)),
		))
			->andWhere($qb->expr()->isNull('l.id'));

		return $this->fetchIds($qb);
	}

	/**
	 * Ids of live rows that sit on an automated status with no backup row to
	 * revert into. Those can never be reverted by the automation that set them,
	 * so the user is stuck on that status until it is cleared.
	 *
	 * @param list<string> $automatedMessageIds
	 * @return list<int>
	 */
	public function findOrphanedAutomatedStatusIds(array $automatedMessageIds): array {
		if ($automatedMessageIds === []) {
			return [];
		}

		$qb = $this->db->getQueryBuilder();
		// The backup of a live row carries the same user id with an underscore
		// prefix, so the two are matched with a self join on the concatenation.
		$qb->select('l.id')
			->from($this->tableName, 'l')
			->leftJoin('l', $this->tableName, 'b', $qb->expr()->eq(
				'b.user_id',
				$qb->func()->concat($qb->createNamedParameter('_'), 'l.user_id'),
			))
			->where($qb->expr()->in('l.message_id', $qb->createNamedParameter($automatedMessageIds, IQueryBuilder::PARAM_STR_ARRAY)))
			->andWhere($qb->expr()->isNull('b.id'))
			// Skip backup rows on the live side. Testing the prefix rather than
			// is_backup keeps this correct for rows where is_backup is NULL, and
			// a substring comparison avoids having to escape the underscore for
			// a LIKE pattern.
			->andWhere($qb->expr()->neq(
				$qb->func()->substring('l.user_id', $qb->createNamedParameter(1, IQueryBuilder::PARAM_INT), $qb->createNamedParameter(1, IQueryBuilder::PARAM_INT)),
				$qb->createNamedParameter('_'),
			));

		return $this->fetchIds($qb);
	}

	/**
	 * @return list<int>
	 */
	private function fetchIds(IQueryBuilder $qb): array {
		$result = $qb->executeQuery();
		$ids = [];
		while ($row = $result->fetch()) {
			$ids[] = (int)$row['id'];
		}
		$result->closeCursor();

		return $ids;
	}

	/**
	 * Ids of rows where is_backup is NULL. Those predate the column default and
	 * are invisible to every query that compares is_backup against false.
	 *
	 * @return list<int>
	 */
	public function findStatusesWithoutBackupFlagIds(): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('id')
			->from($this->tableName)
			->where($qb->expr()->isNull('is_backup'));

		return $this->fetchIds($qb);
	}

	/**
	 * @param list<int> $ids
	 * @return int Number of rows that were given an explicit is_backup value
	 */
	public function normalizeBackupFlagByIds(array $ids): int {
		$updated = 0;
		foreach (array_chunk($ids, IQueryBuilder::MAX_IN_PARAMETERS) as $chunk) {
			$qb = $this->db->getQueryBuilder();
			$qb->update($this->tableName)
				->set('is_backup', $qb->createNamedParameter(false, IQueryBuilder::PARAM_BOOL))
				->where($qb->expr()->in('id', $qb->createNamedParameter($chunk, IQueryBuilder::PARAM_INT_ARRAY)));
			$updated += $qb->executeStatement();
		}

		return $updated;
	}

	/**
	 * @param list<int> $ids
	 * @return int Number of deleted rows
	 */
	public function deleteByIds(array $ids): int {
		$deleted = 0;
		foreach (array_chunk($ids, IQueryBuilder::MAX_IN_PARAMETERS) as $chunk) {
			$qb = $this->db->getQueryBuilder();
			$qb->delete($this->tableName)
				->where($qb->expr()->in('id', $qb->createNamedParameter($chunk, IQueryBuilder::PARAM_INT_ARRAY)));
			$deleted += $qb->executeStatement();
		}

		return $deleted;
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
