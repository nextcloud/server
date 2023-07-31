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

use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
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
		return parent::update($reminderUpdate);
	}

	public function find(int $id): Reminder {
		$qb = $this->db->getQueryBuilder();

		$qb->select('user_id', 'file_id', 'remind_at', 'created_at', 'notified')
			->from($this->getTableName())
			->where($qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)));

		return $this->findEntity($qb);
	}

	/**
	 * @return Reminder[]
	 */
	public function findAll() {
		$qb = $this->db->getQueryBuilder();

		$qb->select('user_id', 'file_id', 'remind_at', 'created_at', 'notified')
			->from($this->getTableName())
			->orderBy('remind_at', 'ASC');

		return $this->findEntities($qb);
	}

	/**
	 * @return Reminder[]
	 */
	public function findAllForUser(IUser $user) {
		$qb = $this->db->getQueryBuilder();

		$qb->select('user_id', 'file_id', 'remind_at', 'created_at', 'notified')
			->from($this->getTableName())
			->where($qb->expr()->eq('user_id', $qb->createNamedParameter($user->getUID(), IQueryBuilder::PARAM_STR)))
			->orderBy('remind_at', 'ASC');

		return $this->findEntities($qb);
	}

	/**
	 * @return Reminder[]
	 */
	public function findToRemind() {
		$qb = $this->db->getQueryBuilder();

		$qb->select('user_id', 'file_id', 'remind_at', 'created_at', 'notified')
			->from($this->getTableName())
			->where($qb->expr()->lt('remind_at', $qb->createFunction('NOW()')))
			->andWhere($qb->expr()->eq('notified', $qb->createNamedParameter(false, IQueryBuilder::PARAM_BOOL)))
			->orderBy('remind_at', 'ASC');

		return $this->findEntities($qb);
	}

	/**
	 * @return Reminder[]
	 */
	public function findToDelete(?int $limit = null) {
		$qb = $this->db->getQueryBuilder();

		$qb->select('user_id', 'file_id', 'remind_at', 'created_at', 'notified')
			->from($this->getTableName())
			->where($qb->expr()->eq('notified', $qb->createNamedParameter(true, IQueryBuilder::PARAM_BOOL)))
			->orderBy('remind_at', 'ASC')
			->setMaxResults($limit);

		return $this->findEntities($qb);
	}
}
