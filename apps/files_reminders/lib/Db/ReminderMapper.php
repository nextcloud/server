<?php

declare(strict_types=1);

/**
 * @copyright 2023 Christopher Ng <chrng8@gmail.com>
 *
 * @author Christopher Ng <chrng8@gmail.com>
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
 *
 */

namespace OCA\FilesReminders\Db;

use DateTime;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Files\Node;
use OCP\Files\NotFoundException;
use OCP\IDBConnection;
use OCP\IUser;

/**
 * @template-extends QBMapper<Reminder>
 */
class ReminderMapper extends QBMapper {
	public const TABLE_NAME = 'files_reminders';

	public function __construct(IDBConnection $db) {
		parent::__construct(
			$db,
			static::TABLE_NAME,
			Reminder::class,
		);
	}

	public function markNotified(Reminder $reminder): Reminder {
		$reminderUpdate = new Reminder();
		$reminderUpdate->setId($reminder->getId());
		$reminderUpdate->setNotified(true);
		return $this->update($reminderUpdate);
	}

	public function find(int $id): Reminder {
		$qb = $this->db->getQueryBuilder();

		$qb->select('id', 'user_id', 'file_id', 'due_date', 'updated_at', 'created_at', 'notified')
			->from($this->getTableName())
			->where($qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)));

		return $this->findEntity($qb);
	}

	/**
	 * @throws DoesNotExistException
	 */
	public function findDueForUser(IUser $user, int $fileId): Reminder {
		$qb = $this->db->getQueryBuilder();

		$qb->select('id', 'user_id', 'file_id', 'due_date', 'updated_at', 'created_at', 'notified')
			->from($this->getTableName())
			->where($qb->expr()->eq('user_id', $qb->createNamedParameter($user->getUID(), IQueryBuilder::PARAM_STR)))
			->andWhere($qb->expr()->eq('file_id', $qb->createNamedParameter($fileId, IQueryBuilder::PARAM_INT)))
			->andWhere($qb->expr()->eq('notified', $qb->createNamedParameter(false, IQueryBuilder::PARAM_BOOL)));

		return $this->findEntity($qb);
	}

	/**
	 * @return Reminder[]
	 */
	public function findAll() {
		$qb = $this->db->getQueryBuilder();

		$qb->select('id', 'user_id', 'file_id', 'due_date', 'updated_at', 'created_at', 'notified')
			->from($this->getTableName())
			->orderBy('due_date', 'ASC');

		return $this->findEntities($qb);
	}

	/**
	 * @return Reminder[]
	 */
	public function findAllForUser(IUser $user) {
		$qb = $this->db->getQueryBuilder();

		$qb->select('id', 'user_id', 'file_id', 'due_date', 'updated_at', 'created_at', 'notified')
			->from($this->getTableName())
			->where($qb->expr()->eq('user_id', $qb->createNamedParameter($user->getUID(), IQueryBuilder::PARAM_STR)))
			->orderBy('due_date', 'ASC');

		return $this->findEntities($qb);
	}

	/**
	 * @return Reminder[]
	 */
	public function findAllForNode(Node $node) {
		try {
			$nodeId = $node->getId();
		} catch (NotFoundException $e) {
			return [];
		}

		$qb = $this->db->getQueryBuilder();

		$qb->select('id', 'user_id', 'file_id', 'due_date', 'updated_at', 'created_at', 'notified')
			->from($this->getTableName())
			->where($qb->expr()->eq('file_id', $qb->createNamedParameter($nodeId, IQueryBuilder::PARAM_INT)))
			->orderBy('due_date', 'ASC');

		return $this->findEntities($qb);
	}

	/**
	 * @return Reminder[]
	 */
	public function findOverdue() {
		$qb = $this->db->getQueryBuilder();

		$qb->select('id', 'user_id', 'file_id', 'due_date', 'updated_at', 'created_at', 'notified')
			->from($this->getTableName())
			->where($qb->expr()->lt('due_date', $qb->createFunction('NOW()')))
			->andWhere($qb->expr()->eq('notified', $qb->createNamedParameter(false, IQueryBuilder::PARAM_BOOL)))
			->orderBy('due_date', 'ASC');

		return $this->findEntities($qb);
	}

	/**
	 * @return Reminder[]
	 */
	public function findNotified(DateTime $buffer, ?int $limit = null) {
		$qb = $this->db->getQueryBuilder();

		$qb->select('id', 'user_id', 'file_id', 'due_date', 'updated_at', 'created_at', 'notified')
			->from($this->getTableName())
			->where($qb->expr()->eq('notified', $qb->createNamedParameter(true, IQueryBuilder::PARAM_BOOL)))
			->andWhere($qb->expr()->lt('due_date', $qb->createNamedParameter($buffer, IQueryBuilder::PARAM_DATE)))
			->orderBy('due_date', 'ASC')
			->setMaxResults($limit);

		return $this->findEntities($qb);
	}
}
