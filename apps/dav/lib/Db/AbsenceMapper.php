<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
