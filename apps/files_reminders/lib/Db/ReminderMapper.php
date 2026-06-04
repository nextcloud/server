<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FilesReminders\Db;

use DateTime;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Files\Folder;
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
			->andWhere($qb->expr()->lt('due_date', $qb->createNamedParameter($buffer, IQueryBuilder::PARAM_DATETIME_MUTABLE)))
			->orderBy('due_date', 'ASC')
			->setMaxResults($limit);

		return $this->findEntities($qb);
	}

	/**
	 * @return Reminder[]
	 */
	public function findAllInFolder(IUser $user, Folder $folder) {
		$qb = $this->db->getQueryBuilder();

		$qb->select('r.id', 'r.user_id', 'r.file_id', 'r.due_date', 'r.updated_at', 'r.created_at', 'r.notified')
			->from($this->getTableName(), 'r')
			->innerJoin('r', 'filecache', 'f', $qb->expr()->eq('r.file_id', 'f.fileid'))
			->where($qb->expr()->eq('r.user_id', $qb->createNamedParameter($user->getUID(), IQueryBuilder::PARAM_STR)))
			->andWhere($qb->expr()->eq('f.parent', $qb->createNamedParameter($folder->getId(), IQueryBuilder::PARAM_INT)))
			->orderBy('r.due_date', 'ASC');

		return $this->findEntities($qb);
	}
}
