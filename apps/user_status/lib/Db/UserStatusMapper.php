<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2020, Georg Ehrke
 *
 * @author Georg Ehrke <oc.list@georgehrke.com>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\UserStatus\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/**
 * Class UserStatusMapper
 *
 * @package OCA\UserStatus\Db
 *
 * @method UserStatus insert(UserStatus $entity)
 * @method UserStatus update(UserStatus $entity)
 * @method UserStatus insertOrUpdate(UserStatus $entity)
 * @method UserStatus delete(UserStatus $entity)
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
			->orderBy('status_timestamp', 'DESC')
			->where($qb->expr()->notIn('status', $qb->createNamedParameter(['online', 'away'], IQueryBuilder::PARAM_STR_ARRAY)))
			->orWhere($qb->expr()->isNotNull('message_id'))
			->orWhere($qb->expr()->isNotNull('custom_icon'))
			->orWhere($qb->expr()->isNotNull('custom_message'));

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
	 * @throws \OCP\AppFramework\Db\DoesNotExistException
	 */
	public function findByUserId(string $userId):UserStatus {
		$qb = $this->db->getQueryBuilder();
		$qb
			->select('*')
			->from($this->tableName)
			->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)));

		return $this->findEntity($qb);
	}

	/**
	 * @param array $userIds
	 * @return array
	 */
	public function findByUserIds(array $userIds):array {
		$qb = $this->db->getQueryBuilder();
		$qb
			->select('*')
			->from($this->tableName)
			->where($qb->expr()->in('user_id', $qb->createNamedParameter($userIds, IQueryBuilder::PARAM_STR_ARRAY)));

		return $this->findEntities($qb);
	}

	/**
	 * Clear all statuses older than a given timestamp
	 *
	 * @param int $timestamp
	 */
	public function clearOlderThan(int $timestamp): void {
		$qb = $this->db->getQueryBuilder();
		$qb->update($this->tableName)
			->set('message_id', $qb->createNamedParameter(null))
			->set('custom_icon', $qb->createNamedParameter(null))
			->set('custom_message', $qb->createNamedParameter(null))
			->set('clear_at', $qb->createNamedParameter(null))
			->where($qb->expr()->isNotNull('clear_at'))
			->andWhere($qb->expr()->lte('clear_at', $qb->createNamedParameter($timestamp, IQueryBuilder::PARAM_INT)));

		$qb->execute();
	}
}
