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
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\Server;

/**
 * @template-extends QBMapper<AuthorizedGroup>
 */
class AuthorizedGroupMapper extends QBMapper {
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'authorized_groups', AuthorizedGroup::class);
	}

	/**
	 * @throws Exception
	 */
	public function findAllClassesForUser(IUser $user): array {
		$qb = $this->db->getQueryBuilder();

		$groupManager = Server::get(IGroupManager::class);
		$groups = $groupManager->getUserGroups($user);
		if (count($groups) === 0) {
			return [];
		}

		/** @var list<string> $rows */
		$rows = $qb->select('class')
			->from($this->getTableName(), 'auth')
			->where($qb->expr()->in('group_id', array_map(static fn (IGroup $group) => $qb->createNamedParameter($group->getGID()), $groups), IQueryBuilder::PARAM_STR))
			->executeQuery()
			->fetchFirstColumn();

		return $rows;
	}

	/**
	 * @throws DoesNotExistException
	 * @throws MultipleObjectsReturnedException
	 * @throws Exception
	 */
	public function find(int $id): AuthorizedGroup {
		$queryBuilder = $this->db->getQueryBuilder();
		$queryBuilder->select('*')
			->from($this->getTableName())
			->where($queryBuilder->expr()->eq('id', $queryBuilder->createNamedParameter($id)));
		return $this->findEntity($queryBuilder);
	}

	/**
	 * Get all the authorizations stored in the database.
	 *
	 * @return AuthorizedGroup[]
	 * @throws Exception
	 */
	public function findAll(): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')->from($this->getTableName());
		return $this->findEntities($qb);
	}

	/**
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
	 * @throws Exception
	 */
	public function removeGroup(string $gid): void {
		$qb = $this->db->getQueryBuilder();
		$qb->delete($this->getTableName())
			->where($qb->expr()->eq('group_id', $qb->createNamedParameter($gid)))
			->executeStatement();
	}
}
