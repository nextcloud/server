<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @author Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\DAV\Db;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/**
 * @template-extends QBMapper<Absence>
 */
class AbsenceMapper extends QBMapper {
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'dav_absence', Absence::class);
	}

	/**
	 * @throws DoesNotExistException
	 * @throws \OCP\DB\Exception
	 */
	public function findById(int $id): Absence {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq(
				'id',
				$qb->createNamedParameter($id, IQueryBuilder::PARAM_INT),
				IQueryBuilder::PARAM_INT),
			);
		try {
			return $this->findEntity($qb);
		} catch (MultipleObjectsReturnedException $e) {
			// Won't happen as id is the primary key
			throw new \RuntimeException(
				'The impossible has happened! The query returned multiple absence settings for one user.',
				0,
				$e,
			);
		}
	}

	/**
	 * @throws DoesNotExistException
	 * @throws \OCP\DB\Exception
	 */
	public function findByUserId(string $userId): Absence {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq(
				'user_id',
				$qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR),
				IQueryBuilder::PARAM_STR),
			);
		try {
			return $this->findEntity($qb);
		} catch (MultipleObjectsReturnedException $e) {
			// Won't happen as there is a unique index on user_id
			throw new \RuntimeException(
				'The impossible has happened! The query returned multiple absence settings for one user.',
				0,
				$e,
			);
		}
	}

	/**
	 * @throws \OCP\DB\Exception
	 */
	public function deleteByUserId(string $userId): void {
		$qb = $this->db->getQueryBuilder();
		$qb->delete($this->getTableName())
			->where($qb->expr()->eq(
				'user_id',
				$qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR),
				IQueryBuilder::PARAM_STR),
			);
		$qb->executeStatement();
	}
}
