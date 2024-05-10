<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Marcel Klehr <mklehr@gmx.net>
 *
 * @author Marcel Klehr <mklehr@gmx.net>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
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
	 * @param string|null $taskType
	 * @return Task
	 * @throws DoesNotExistException
	 * @throws Exception
	 */
	public function findOldestScheduledByType(?string $taskType): Task {
		$qb = $this->db->getQueryBuilder();
		$qb->select(Task::$columns)
			->from($this->tableName)
			->where($qb->expr()->eq('status', $qb->createPositionalParameter(\OCP\TaskProcessing\Task::STATUS_SCHEDULED, IQueryBuilder::PARAM_INT)))
			->setMaxResults(1)
			->orderBy('last_updated', 'ASC');
		if ($taskType !== null) {
			$qb->andWhere($qb->expr()->eq('type', $qb->createPositionalParameter($taskType)));
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
		return array_values($this->findEntities($qb));
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
		return array_values($this->findEntities($qb));
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
}
