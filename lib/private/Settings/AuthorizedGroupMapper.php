<?php

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Settings;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\Entity;
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

		/** @var IGroupManager $groupManager */
		$groupManager = Server::get(IGroupManager::class);
		$groups = $groupManager->getUserGroups($user);
		if (count($groups) === 0) {
			return [];
		}

		$result = $qb->select('class')
			->from($this->getTableName(), 'auth')
			->where($qb->expr()->in('group_id', array_map(function (IGroup $group) use ($qb) {
				return $qb->createNamedParameter($group->getGID());
			}, $groups), IQueryBuilder::PARAM_STR))
			->executeQuery();

		$classes = [];
		while ($row = $result->fetch()) {
			$classes[] = $row['class'];
		}
		$result->closeCursor();
		return $classes;
	}

	/**
	 * @throws DoesNotExistException
	 * @throws MultipleObjectsReturnedException
	 * @throws \OCP\DB\Exception
	 */
	public function find(int $id): AuthorizedGroup {
		$queryBuilder = $this->db->getQueryBuilder();
		$queryBuilder->select('*')
			->from($this->getTableName())
			->where($queryBuilder->expr()->eq('id', $queryBuilder->createNamedParameter($id)));
		/** @var AuthorizedGroup $authorizedGroup */
		$authorizedGroup = $this->findEntity($queryBuilder);
		return $authorizedGroup;
	}

	/**
	 * Get all the authorizations stored in the database.
	 *
	 * @return AuthorizedGroup[]
	 * @throws \OCP\DB\Exception
	 */
	public function findAll(): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')->from($this->getTableName());
		return $this->findEntities($qb);
	}

	public function findByGroupIdAndClass(string $groupId, string $class) {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('group_id', $qb->createNamedParameter($groupId)))
			->andWhere($qb->expr()->eq('class', $qb->createNamedParameter($class)));
		return $this->findEntity($qb);
	}

	/**
	 * @return Entity[]
	 * @throws \OCP\DB\Exception
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
	public function removeGroup(string $gid) {
		$qb = $this->db->getQueryBuilder();
		$qb->delete($this->getTableName())
			->where($qb->expr()->eq('group_id', $qb->createNamedParameter($gid)))
			->executeStatement();
	}
}
