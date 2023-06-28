<?php

namespace OC\LanguageModel\Db;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\AppFramework\Db\QBMapper;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\DB\Exception;
use OCP\IDBConnection;

/**
 * @extends QBMapper<Task>
 */
class TaskMapper extends QBMapper {
	public function __construct(
		IDBConnection $db,
		private ITimeFactory $timeFactory,
	) {
		parent::__construct($db, 'llm_tasks', Task::class);
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
	 * @param int $timeout
	 * @return int the number of deleted tasks
	 * @throws Exception
	 */
	public function deleteOlderThan(int $timeout): int {
		$qb = $this->db->getQueryBuilder();
		$qb->delete($this->tableName)
			->where($qb->expr()->lt('last_updated', $qb->createPositionalParameter(time() - $timeout)));
		return $qb->executeStatement();
	}

	public function update(Entity $entity): Entity {
		$entity->setLastUpdated($this->timeFactory->now()->getTimestamp());
		return parent::update($entity);
	}
}
