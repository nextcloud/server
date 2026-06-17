<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\TaskProcessing\Db;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\AppFramework\Db\QBMapper;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\DB\Exception;
use OCP\DB\QueryBuilder\ConflictResolutionMode;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/**
 * @extends QBMapper<Task>
 */
class TaskMapper extends QBMapper {
	public function __construct(
		IDBConnection $db,
		private ITimeFactory $timeFactory,
	) {
		parent::__construct($db, 'taskprocessing_tasks', Task::class);
	}

	/**
	 * @param int $id
	 * @return Task
	 * @throws Exception
	 * @throws DoesNotExistException
	 * @throws MultipleObjectsReturnedException
	 */
	public function find(int $id): Task {
		$qb = $this->db->getQueryBuilder();
		$qb->select(Task::$columns)
			->from($this->tableName)
			->where($qb->expr()->eq('id', $qb->createPositionalParameter($id)));
		return $this->findEntity($qb);
	}

	/**
	 * @param list<string> $taskTypes
	 * @param list<int> $taskIdsToIgnore
	 * @return Task
	 * @throws DoesNotExistException
	 * @throws Exception
	 */
	public function findOldestScheduledByType(array $taskTypes, array $taskIdsToIgnore): Task {
		$qb = $this->db->getQueryBuilder();
		$qb->select(Task::$columns)
			->from($this->tableName)
			->where($qb->expr()->eq('status', $qb->createPositionalParameter(\OCP\TaskProcessing\Task::STATUS_SCHEDULED, IQueryBuilder::PARAM_INT)))
			->setMaxResults(1)
			->orderBy('last_updated', 'ASC');

		if (!empty($taskTypes)) {
			$filter = [];
			foreach ($taskTypes as $taskType) {
				$filter[] = $qb->expr()->eq('type', $qb->createPositionalParameter($taskType));
			}

			$qb->andWhere($qb->expr()->orX(...$filter));
		}

		if (!empty($taskIdsToIgnore)) {
			$qb->andWhere($qb->expr()->notIn('id', $qb->createNamedParameter($taskIdsToIgnore, IQueryBuilder::PARAM_INT_ARRAY)));
		}

		return $this->findEntity($qb);
	}

	/**
	 * Atomically claim the oldest scheduled task of the given task types and mark it RUNNING.
	 *
	 * This is the structural fix for the worker "claim loop": instead of every worker
	 * racing for the single oldest task (a thundering herd that grows a per-worker
	 * `id NOT IN (...)` ignore list and slows the SELECT), each worker claims a
	 * *distinct* task in a single claim attempt without a per-worker ignore-list.
	 * On databases that support row-level locking with SKIP LOCKED
	 * (MySQL/MariaDB/PostgreSQL) the claim is a single transaction:
	 *   SELECT ... WHERE status = SCHEDULED [AND type IN (...)]
	 *   ORDER BY last_updated ASC LIMIT 1 FOR UPDATE SKIP LOCKED
	 * followed by a guarded UPDATE to RUNNING. Concurrent workers skip rows already
	 * locked by another transaction, so no two workers ever claim the same task.
	 *
	 * Two databases cannot use the SKIP LOCKED path and fall back to a bounded
	 * lock-and-retry claim instead:
	 *   - SQLite has no SKIP LOCKED (Doctrine throws "Operation 'SKIP LOCKED' is not
	 *     supported by platform").
	 *   - Oracle cannot combine a row-limiting clause with FOR UPDATE: the LIMIT is
	 *     emulated with a ROWNUM sub-select, and selecting FOR UPDATE from that derived
	 *     view raises ORA-02014.
	 * The fallback is still safe because the UPDATE ... WHERE status = SCHEDULED is itself
	 * atomic (SQLite additionally serialises writers).
	 *
	 * A task is only ever transitioned SCHEDULED -> RUNNING here; it is never marked
	 * FAILED by claiming. If the task cannot be claimed (none scheduled, or it was
	 * taken by another worker between SELECT and UPDATE) this returns null.
	 *
	 * @param list<string> $taskTypes When non-empty, only tasks of these task type IDs are considered.
	 * @return Task|null The claimed task (status RUNNING), or null if nothing could be claimed.
	 * @throws Exception
	 */
	public function claimOldestScheduledTask(array $taskTypes): ?Task {
		$provider = $this->db->getDatabaseProvider();
		// SKIP LOCKED is unusable on SQLite (unsupported) and Oracle (LIMIT + FOR UPDATE =>
		// ORA-02014): both fall back to the bounded lock-and-retry claim.
		if ($provider === IDBConnection::PLATFORM_SQLITE || $provider === IDBConnection::PLATFORM_ORACLE) {
			return $this->claimWithBoundedRetry($taskTypes);
		}

		return $this->claimWithSkipLocked($taskTypes);
	}

	/**
	 * Atomic claim using FOR UPDATE SKIP LOCKED in a single transaction.
	 *
	 * @param list<string> $taskTypes
	 * @return Task|null
	 * @throws Exception
	 */
	private function claimWithSkipLocked(array $taskTypes): ?Task {
		$this->db->beginTransaction();
		try {
			$qb = $this->db->getQueryBuilder();
			$qb->select(Task::$columns)
				->from($this->tableName)
				->where($qb->expr()->eq('status', $qb->createPositionalParameter(\OCP\TaskProcessing\Task::STATUS_SCHEDULED, IQueryBuilder::PARAM_INT)))
				->orderBy('last_updated', 'ASC')
				->setMaxResults(1)
				->forUpdate(ConflictResolutionMode::SkipLocked);

			if (!empty($taskTypes)) {
				$filter = [];
				foreach ($taskTypes as $taskType) {
					$filter[] = $qb->expr()->eq('type', $qb->createPositionalParameter($taskType));
				}
				$qb->andWhere($qb->expr()->orX(...$filter));
			}

			$result = $qb->executeQuery();
			$row = $result->fetch();
			$result->closeCursor();

			if ($row === false) {
				// Nothing schedulable (or every candidate is locked by another worker).
				$this->db->commit();
				return null;
			}

			/** @var Task $task */
			$task = $this->mapRowToEntity($row);

			// Record the start time at claim time: because the worker receives the task
			// already in status RUNNING, the later SCHEDULED -> RUNNING transition in
			// Manager::setTaskStatus is skipped and would otherwise never persist started_at.
			$startedAt = $this->timeFactory->now()->getTimestamp();

			// Guarded transition SCHEDULED -> RUNNING. The row is locked for this
			// transaction, so the guard is belt-and-braces rather than strictly required.
			$update = $this->db->getQueryBuilder();
			$update->update($this->tableName)
				->set('status', $update->createPositionalParameter(\OCP\TaskProcessing\Task::STATUS_RUNNING, IQueryBuilder::PARAM_INT))
				->set('started_at', $update->createPositionalParameter($startedAt, IQueryBuilder::PARAM_INT))
				->where($update->expr()->eq('id', $update->createPositionalParameter($task->getId(), IQueryBuilder::PARAM_INT)))
				->andWhere($update->expr()->eq('status', $update->createPositionalParameter(\OCP\TaskProcessing\Task::STATUS_SCHEDULED, IQueryBuilder::PARAM_INT)));
			$affected = $update->executeStatement();

			$this->db->commit();

			if ($affected === 0) {
				// Lost the race (should not happen under SKIP LOCKED); leave the task SCHEDULED.
				return null;
			}

			$task->setStatus(\OCP\TaskProcessing\Task::STATUS_RUNNING);
			$task->setStartedAt($startedAt);
			return $task;
		} catch (\Throwable $e) {
			$this->db->rollBack();
			throw $e;
		}
	}

	/**
	 * Fallback claim for databases that cannot use the SKIP LOCKED path (SQLite, Oracle).
	 *
	 * Repeatedly fetches the oldest scheduled task and attempts the atomic
	 * UPDATE ... WHERE status = SCHEDULED. Tasks lost to another worker are added to a
	 * short ignore list so the next iteration moves on. Bounded to avoid unbounded
	 * looping under contention.
	 *
	 * @param list<string> $taskTypes
	 * @return Task|null
	 * @throws Exception
	 */
	private function claimWithBoundedRetry(array $taskTypes): ?Task {
		$taskIdsToIgnore = [];
		// A handful of attempts is plenty: on SQLite writers are serialised, so at most
		// a few rows can be claimed out from under us before we either win or run dry.
		for ($attempt = 0; $attempt < 10; $attempt++) {
			try {
				$task = $this->findOldestScheduledByType($taskTypes, $taskIdsToIgnore);
			} catch (DoesNotExistException) {
				return null;
			}

			if ($this->lockTask($task) !== 0) {
				// lockTask atomically flipped SCHEDULED -> RUNNING and stamped started_at.
				// Re-read so the returned task reflects the persisted status and started_at.
				return $this->find($task->getId());
			}

			// Another worker took it; skip this id and try the next oldest.
			$taskIdsToIgnore[] = $task->getId();
		}

		return null;
	}

	/**
	 * @param int $id
	 * @param string|null $userId
	 * @return Task
	 * @throws DoesNotExistException
	 * @throws Exception
	 * @throws MultipleObjectsReturnedException
	 */
	public function findByIdAndUser(int $id, ?string $userId): Task {
		$qb = $this->db->getQueryBuilder();
		$qb->select(Task::$columns)
			->from($this->tableName)
			->where($qb->expr()->eq('id', $qb->createPositionalParameter($id)));
		if ($userId === null) {
			$qb->andWhere($qb->expr()->isNull('user_id'));
		} else {
			$qb->andWhere($qb->expr()->eq('user_id', $qb->createPositionalParameter($userId)));
		}
		return $this->findEntity($qb);
	}

	/**
	 * @param string|null $userId
	 * @param string|null $taskType
	 * @param string|null $customId
	 * @return list<Task>
	 * @throws Exception
	 */
	public function findByUserAndTaskType(?string $userId, ?string $taskType = null, ?string $customId = null): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select(Task::$columns)
			->from($this->tableName)
			->where($qb->expr()->eq('user_id', $qb->createPositionalParameter($userId)));
		if ($taskType !== null) {
			$qb->andWhere($qb->expr()->eq('type', $qb->createPositionalParameter($taskType)));
		}
		if ($customId !== null) {
			$qb->andWhere($qb->expr()->eq('custom_id', $qb->createPositionalParameter($customId)));
		}
		return $this->findEntities($qb);
	}

	/**
	 * @param string $userId
	 * @param string $appId
	 * @param string|null $customId
	 * @return list<Task>
	 * @throws Exception
	 */
	public function findUserTasksByApp(?string $userId, string $appId, ?string $customId = null): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select(Task::$columns)
			->from($this->tableName)
			->where($qb->expr()->eq('user_id', $qb->createPositionalParameter($userId)))
			->andWhere($qb->expr()->eq('app_id', $qb->createPositionalParameter($appId)));
		if ($customId !== null) {
			$qb->andWhere($qb->expr()->eq('custom_id', $qb->createPositionalParameter($customId)));
		}
		return $this->findEntities($qb);
	}

	/**
	 * @param string|null $userId
	 * @param string|null $taskType
	 * @param string|null $appId
	 * @param string|null $customId
	 * @param int|null $status
	 * @param int|null $scheduleAfter
	 * @param int|null $endedBefore
	 * @return list<Task>
	 * @throws Exception
	 */
	public function findTasks(
		?string $userId, ?string $taskType = null, ?string $appId = null, ?string $customId = null,
		?int $status = null, ?int $scheduleAfter = null, ?int $endedBefore = null): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select(Task::$columns)
			->from($this->tableName);

		// empty string: no userId filter
		if ($userId !== '') {
			$qb->where($qb->expr()->eq('user_id', $qb->createPositionalParameter($userId)));
		}
		if ($taskType !== null) {
			$qb->andWhere($qb->expr()->eq('type', $qb->createPositionalParameter($taskType)));
		}
		if ($appId !== null) {
			$qb->andWhere($qb->expr()->eq('app_id', $qb->createPositionalParameter($appId)));
		}
		if ($customId !== null) {
			$qb->andWhere($qb->expr()->eq('custom_id', $qb->createPositionalParameter($customId)));
		}
		if ($status !== null) {
			$qb->andWhere($qb->expr()->eq('status', $qb->createPositionalParameter($status, IQueryBuilder::PARAM_INT)));
		}
		if ($scheduleAfter !== null) {
			$qb->andWhere($qb->expr()->isNotNull('scheduled_at'));
			$qb->andWhere($qb->expr()->gt('scheduled_at', $qb->createPositionalParameter($scheduleAfter, IQueryBuilder::PARAM_INT)));
		}
		if ($endedBefore !== null) {
			$qb->andWhere($qb->expr()->isNotNull('ended_at'));
			$qb->andWhere($qb->expr()->lt('ended_at', $qb->createPositionalParameter($endedBefore, IQueryBuilder::PARAM_INT)));
		}
		return $this->findEntities($qb);
	}

	/**
	 * @param int $timeout
	 * @param bool $force If true, ignore the allow_cleanup flag
	 * @return int the number of deleted tasks
	 * @throws Exception
	 */
	public function deleteOlderThan(int $timeout, bool $force = false): int {
		$qb = $this->db->getQueryBuilder();
		$qb->delete($this->tableName)
			->where($qb->expr()->lt('last_updated', $qb->createPositionalParameter($this->timeFactory->getDateTime()->getTimestamp() - $timeout)));
		if (!$force) {
			$qb->andWhere($qb->expr()->eq('allow_cleanup', $qb->createPositionalParameter(1, IQueryBuilder::PARAM_INT)));
		}
		return $qb->executeStatement();
	}

	/**
	 * @param int $timeout
	 * @param bool $force If true, ignore the allow_cleanup flag
	 * @return \Generator<Task>
	 * @throws Exception
	 */
	public function getTasksToCleanup(int $timeout, bool $force = false): \Generator {
		$qb = $this->db->getQueryBuilder();
		$qb->select(Task::$columns)
			->from($this->tableName)
			->where($qb->expr()->lt('last_updated', $qb->createPositionalParameter($this->timeFactory->getDateTime()->getTimestamp() - $timeout)));
		if (!$force) {
			$qb->andWhere($qb->expr()->eq('allow_cleanup', $qb->createPositionalParameter(1, IQueryBuilder::PARAM_INT)));
		}
		foreach ($this->yieldEntities($qb) as $entity) {
			yield $entity;
		};
	}

	#[\Override]
	public function update(Entity $entity): Entity {
		$entity->setLastUpdated($this->timeFactory->now()->getTimestamp());
		return parent::update($entity);
	}

	/**
	 * Atomically claim a task by transitioning it SCHEDULED -> RUNNING.
	 *
	 * The UPDATE is guarded on `status = SCHEDULED` so a task another worker has already
	 * finished (SUCCESSFUL/FAILED) between a caller's SELECT and this UPDATE can never be
	 * re-claimed and processed twice. started_at is stamped in the same statement: the
	 * worker receives the task already RUNNING, so the later SCHEDULED -> RUNNING edge in
	 * Manager::setTaskStatus (which used to set started_at) no longer fires.
	 *
	 * Semantic change: this previously guarded on `status != RUNNING`, which allowed an
	 * already SUCCESSFUL/FAILED task to be re-locked back to RUNNING. Callers must now
	 * treat a 0 return as "the task is no longer claimable" (it is no longer SCHEDULED)
	 * and move on, rather than assuming the lock succeeded.
	 *
	 * @return int Number of rows updated: 1 if the task was claimed, 0 if it was no longer scheduled.
	 */
	public function lockTask(Entity $entity): int {
		$startedAt = $this->timeFactory->now()->getTimestamp();
		$qb = $this->db->getQueryBuilder();
		$qb->update($this->tableName)
			->set('status', $qb->createPositionalParameter(\OCP\TaskProcessing\Task::STATUS_RUNNING, IQueryBuilder::PARAM_INT))
			->set('started_at', $qb->createPositionalParameter($startedAt, IQueryBuilder::PARAM_INT))
			->where($qb->expr()->eq('id', $qb->createPositionalParameter($entity->getId(), IQueryBuilder::PARAM_INT)))
			->andWhere($qb->expr()->eq('status', $qb->createPositionalParameter(\OCP\TaskProcessing\Task::STATUS_SCHEDULED, IQueryBuilder::PARAM_INT)));
		try {
			return $qb->executeStatement();
		} catch (Exception) {
			return 0;
		}
	}

	/**
	 * @param list<string> $taskTypes
	 * @param list<int> $taskIdsToIgnore
	 * @param int $numberOfTasks
	 * @return list<Task>
	 * @throws Exception
	 */
	public function findNOldestScheduledByType(array $taskTypes, array $taskIdsToIgnore, int $numberOfTasks) {
		$qb = $this->db->getQueryBuilder();
		$qb->select(Task::$columns)
			->from($this->tableName)
			->where($qb->expr()->eq('status', $qb->createPositionalParameter(\OCP\TaskProcessing\Task::STATUS_SCHEDULED, IQueryBuilder::PARAM_INT)))
			->setMaxResults($numberOfTasks)
			->orderBy('last_updated', 'ASC');

		if (!empty($taskTypes)) {
			$filter = [];
			foreach ($taskTypes as $taskType) {
				$filter[] = $qb->expr()->eq('type', $qb->createPositionalParameter($taskType));
			}

			$qb->andWhere($qb->expr()->orX(...$filter));
		}

		if (!empty($taskIdsToIgnore)) {
			$qb->andWhere($qb->expr()->notIn('id', $qb->createNamedParameter($taskIdsToIgnore, IQueryBuilder::PARAM_INT_ARRAY)));
		}

		return $this->findEntities($qb);
	}

	/**
	 * @param list<string> $taskTypeIds
	 * @param int $status
	 * @return int
	 * @throws Exception
	 */
	public function countByStatus(array $taskTypeIds, int $status): int {
		if ($taskTypeIds === []) {
			return $this->countByStatusQuery($status);
		}

		$count = 0;
		foreach (array_chunk($taskTypeIds, 900) as $chunk) {
			$count += $this->countByStatusQuery($status, $chunk);
		}
		return $count;
	}

	private function countByStatusQuery(int $status, ?array $taskTypeIds = null): int {
		$qb = $this->db->getQueryBuilder();
		$qb->select($qb->func()->count('id'))
			->from($this->tableName)
			->where($qb->expr()->eq('status', $qb->createNamedParameter($status, IQueryBuilder::PARAM_INT)));

		if ($taskTypeIds !== null) {
			$qb->andWhere($qb->expr()->in('type', $qb->createNamedParameter($taskTypeIds, IQueryBuilder::PARAM_STR_ARRAY)));
		}

		$result = $qb->executeQuery();
		$count = (int)$result->fetchOne();
		$result->closeCursor();
		return $count;
	}

	/**
	 * @throws Exception
	 */
	public function hasRunningTasksForTaskType(string $getTaskTypeId): bool {
		$qb = $this->db->getQueryBuilder();
		$qb->select('id')
			->from($this->tableName);
		$qb->where($qb->expr()->eq('type', $qb->createNamedParameter($getTaskTypeId)));
		$qb->andWhere($qb->expr()->eq('status', $qb->createNamedParameter(\OCP\TaskProcessing\Task::STATUS_RUNNING, IQueryBuilder::PARAM_INT)));
		$qb->setMaxResults(1);
		$result = $qb->executeQuery();
		$hasRunningTasks = $result->fetch() !== false;
		$result->closeCursor();
		return $hasRunningTasks;
	}
}
