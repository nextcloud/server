<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Settings;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\Server;

/**
 * @template-extends QBMapper<AuthorizedGroup>
 * @psalm-api - we cannot use final as this will break unit tests
 */
class AuthorizedGroupMapper extends QBMapper {
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'authorized_groups', AuthorizedGroup::class);
	}

	/**
	 * Returns the class names associated with groups the user belongs to.
	 * A class may appear more than once if multiple of the user's groups authorize it.
	 *
	 * @return list<string>
 	 * @throws Exception
	 */
	public function findAllClassesForUser(IUser $user): array {
		$groupManager = Server::get(IGroupManager::class);
		$groupIds = $groupManager->getUserGroupIds($user);
		if ($groupIds === []) {
			return [];
		}

		$qb = $this->db->getQueryBuilder();
		/** @var list<string> $rows */
		$rows = $qb->select('class')
			->from($this->getTableName())
			->where($qb->expr()->in('group_id', $qb->createNamedParameter($groupIds, IQueryBuilder::PARAM_STR_ARRAY)))
			->executeQuery()
			->fetchFirstColumn();

		return $rows;
	}

	/**
	 * Finds an authorization mapping by its database ID.
	 *
	 * @throws DoesNotExistException
	 * @throws MultipleObjectsReturnedException
	 * @throws Exception
	 */
	public function find(int $id): AuthorizedGroup {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName())
			->where($queryBuilder->expr()->eq('id', $queryBuilder->createNamedParameter($id, IQueryBuilder::PARAM_INT)));

		return $this->findEntity($qb);
	}

	/**
	 * Returns all stored group-to-class authorization mappings.
	 *
	 * @return list<AuthorizedGroup>
	 * @throws Exception
	 */
	public function findAll(): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName());

		return $this->findEntities($qb);
	}

	/**
	 * Finds the authorization mapping for a group and class.
	 *
	 * @throws DoesNotExistException
	 * @throws Exception
	 * @throws MultipleObjectsReturnedException
	 */
	public function findByGroupIdAndClass(string $groupId, string $class): AuthorizedGroup {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('group_id', $qb->createNamedParameter($groupId)))
			->andWhere($qb->expr()->eq('class', $qb->createNamedParameter($class)));

		return $this->findEntity($qb);
	}

	/**
	 * Returns all group authorization mappings for a class.
	 *
	 * @return list<AuthorizedGroup>
	 * @throws Exception
	 */
	public function findExistingGroupsForClass(string $class): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('class', $qb->createNamedParameter($class)));

		return $this->findEntities($qb);
	}

	/**
	 * Removes all authorization mappings for a group.
	 *
	 * @throws Exception
	 */
	public function removeGroup(string $gid): void {
		$qb = $this->db->getQueryBuilder();
		$qb->delete($this->getTableName())
			->where($qb->expr()->eq('group_id', $qb->createNamedParameter($gid)))
			->executeStatement();
	}
}
