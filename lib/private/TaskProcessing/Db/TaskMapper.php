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
	 * @return int the number of deleted tasks
	 * @throws Exception
	 */
	public function deleteOlderThan(int $timeout): int {
		$qb = $this->db->getQueryBuilder();
		$qb->delete($this->tableName)
			->where($qb->expr()->lt('last_updated', $qb->createPositionalParameter($this->timeFactory->getDateTime()->getTimestamp() - $timeout)));
		return $qb->executeStatement();
	}

	public function update(Entity $entity): Entity {
		$entity->setLastUpdated($this->timeFactory->now()->getTimestamp());
		return parent::update($entity);
	}

	public function lockTask(Entity $entity): int {
		$qb = $this->db->getQueryBuilder();
		$qb->update($this->tableName)
			->set('status', $qb->createPositionalParameter(\OCP\TaskProcessing\Task::STATUS_RUNNING, IQueryBuilder::PARAM_INT))
			->where($qb->expr()->eq('id', $qb->createPositionalParameter($entity->getId(), IQueryBuilder::PARAM_INT)))
			->andWhere($qb->expr()->neq('status', $qb->createPositionalParameter(2, IQueryBuilder::PARAM_INT)));
		try {
			return $qb->executeStatement();
		} catch (Exception) {
			return 0;
		}
	}
}
