<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\TextToImage\Db;

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
		parent::__construct($db, 'text2image_tasks', Task::class);
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
	 * @param string $userId
	 * @param string $appId
	 * @param string|null $identifier
	 * @return array
	 * @throws Exception
	 */
	public function findUserTasksByApp(?string $userId, string $appId, ?string $identifier = null): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select(Task::$columns)
			->from($this->tableName)
			->where($qb->expr()->eq('user_id', $qb->createPositionalParameter($userId)))
			->andWhere($qb->expr()->eq('app_id', $qb->createPositionalParameter($appId)));
		if ($identifier !== null) {
			$qb->andWhere($qb->expr()->eq('identifier', $qb->createPositionalParameter($identifier)));
		}
		return $this->findEntities($qb);
	}

	/**
	 * @param int $timeout
	 * @return Task[] the deleted tasks
	 * @throws Exception
	 */
	public function deleteOlderThan(int $timeout): array {
		$datetime = $this->timeFactory->getDateTime();
		$datetime->sub(new \DateInterval('PT' . $timeout . 'S'));
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->tableName)
			->where($qb->expr()->lt('last_updated', $qb->createPositionalParameter($datetime, IQueryBuilder::PARAM_DATETIME_MUTABLE)));
		$deletedTasks = $this->findEntities($qb);
		$qb = $this->db->getQueryBuilder();
		$qb->delete($this->tableName)
			->where($qb->expr()->lt('last_updated', $qb->createPositionalParameter($datetime, IQueryBuilder::PARAM_DATETIME_MUTABLE)));
		$qb->executeStatement();
		return $deletedTasks;
	}

	public function update(Entity $entity): Entity {
		$entity->setLastUpdated($this->timeFactory->getDateTime());
		return parent::update($entity);
	}
}
