<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Group;

use OC\User\LazyUser;
use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Group\Backend\ABackend;
use OCP\Group\Backend\IAddToGroupBackend;
use OCP\Group\Backend\IBatchMethodsBackend;
use OCP\Group\Backend\ICountDisabledInGroup;
use OCP\Group\Backend\ICountUsersBackend;
use OCP\Group\Backend\ICreateNamedGroupBackend;
use OCP\Group\Backend\IDeleteGroupBackend;
use OCP\Group\Backend\IGetDisplayNameBackend;
use OCP\Group\Backend\IGroupDetailsBackend;
use OCP\Group\Backend\INamedBackend;
use OCP\Group\Backend\IRemoveFromGroupBackend;
use OCP\Group\Backend\ISearchableGroupBackend;
use OCP\Group\Backend\ISetDisplayNameBackend;
use OCP\Group\Exception\CycleDetectedException;
use OCP\IDBConnection;
use OCP\IUserManager;
use OCP\Server;

/**
 * Class for group management in a SQL Database (e.g. MySQL, SQLite)
 */
class Database extends ABackend implements
	IAddToGroupBackend,
	ICountDisabledInGroup,
	ICountUsersBackend,
	ICreateNamedGroupBackend,
	IDeleteGroupBackend,
	IGetDisplayNameBackend,
	IGroupDetailsBackend,
	IRemoveFromGroupBackend,
	ISetDisplayNameBackend,
	ISearchableGroupBackend,
	IBatchMethodsBackend,
	INamedBackend,
	INestedGroupBackend {
	/** @var array<string, array{gid: string, displayname: string}> */
	private $groupCache = [];

	/**
	 * \OC\Group\Database constructor.
	 *
	 * @param IDBConnection|null $dbConn
	 */
	public function __construct(
		private ?IDBConnection $dbConn = null,
	) {
	}

	/**
	 * FIXME: This function should not be required!
	 */
	private function fixDI() {
		if ($this->dbConn === null) {
			$this->dbConn = Server::get(IDBConnection::class);
		}
	}

	public function createGroup(string $name): ?string {
		$this->fixDI();

		$gid = $this->computeGid($name);
		try {
			// Add group
			$builder = $this->dbConn->getQueryBuilder();
			$builder->insert('groups')
				->setValue('gid', $builder->createNamedParameter($gid))
				->setValue('displayname', $builder->createNamedParameter($name))
				->executeStatement();
		} catch (Exception $e) {
			if ($e->getReason() === Exception::REASON_UNIQUE_CONSTRAINT_VIOLATION) {
				return null;
			} else {
				throw $e;
			}
		}

		// Add to cache
		$this->groupCache[$gid] = [
			'gid' => $gid,
			'displayname' => $name
		];

		return $gid;
	}

	/**
	 * delete a group
	 * @param string $gid gid of the group to delete
	 * @return bool
	 *
	 * Deletes a group and removes it from the group_user-table
	 */
	public function deleteGroup(string $gid): bool {
		$this->fixDI();

		// Delete the group
		$qb = $this->dbConn->getQueryBuilder();
		$qb->delete('groups')
			->where($qb->expr()->eq('gid', $qb->createNamedParameter($gid)))
			->executeStatement();

		// Delete the group-user relation
		$qb = $this->dbConn->getQueryBuilder();
		$qb->delete('group_user')
			->where($qb->expr()->eq('gid', $qb->createNamedParameter($gid)))
			->executeStatement();

		// Delete the group-groupadmin relation
		$qb = $this->dbConn->getQueryBuilder();
		$qb->delete('group_admin')
			->where($qb->expr()->eq('gid', $qb->createNamedParameter($gid)))
			->executeStatement();

		// Delete nested-group edges where this group appears on either side
		$qb = $this->dbConn->getQueryBuilder();
		$qb->delete('group_group')
			->where($qb->expr()->orX(
				$qb->expr()->eq('parent_gid', $qb->createNamedParameter($gid)),
				$qb->expr()->eq('child_gid', $qb->createNamedParameter($gid)),
			))
			->executeStatement();

		// Delete group-level sub-admin edges on either side
		$qb = $this->dbConn->getQueryBuilder();
		$qb->delete('group_group_admin')
			->where($qb->expr()->orX(
				$qb->expr()->eq('admin_gid', $qb->createNamedParameter($gid)),
				$qb->expr()->eq('gid', $qb->createNamedParameter($gid)),
			))
			->executeStatement();

		// Delete from cache
		unset($this->groupCache[$gid]);

		return true;
	}

	/**
	 * is user in group?
	 * @param string $uid uid of the user
	 * @param string $gid gid of the group
	 * @return bool
	 *
	 * Checks whether the user is member of a group or not.
	 */
	public function inGroup($uid, $gid) {
		$this->fixDI();

		// check
		$qb = $this->dbConn->getQueryBuilder();
		$cursor = $qb->select('uid')
			->from('group_user')
			->where($qb->expr()->eq('gid', $qb->createNamedParameter($gid)))
			->andWhere($qb->expr()->eq('uid', $qb->createNamedParameter($uid)))
			->executeQuery();

		$result = $cursor->fetch();
		$cursor->closeCursor();

		return $result ? true : false;
	}

	/**
	 * Add a user to a group
	 * @param string $uid Name of the user to add to group
	 * @param string $gid Name of the group in which add the user
	 * @return bool
	 *
	 * Adds a user to a group.
	 */
	public function addToGroup(string $uid, string $gid): bool {
		$this->fixDI();

		// No duplicate entries!
		if (!$this->inGroup($uid, $gid)) {
			$qb = $this->dbConn->getQueryBuilder();
			$qb->insert('group_user')
				->setValue('uid', $qb->createNamedParameter($uid))
				->setValue('gid', $qb->createNamedParameter($gid))
				->executeStatement();
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Removes a user from a group
	 * @param string $uid Name of the user to remove from group
	 * @param string $gid Name of the group from which remove the user
	 * @return bool
	 *
	 * removes the user from a group.
	 */
	public function removeFromGroup(string $uid, string $gid): bool {
		$this->fixDI();

		$qb = $this->dbConn->getQueryBuilder();
		$qb->delete('group_user')
			->where($qb->expr()->eq('uid', $qb->createNamedParameter($uid)))
			->andWhere($qb->expr()->eq('gid', $qb->createNamedParameter($gid)))
			->executeStatement();

		return true;
	}

	/**
	 * Get all groups a user belongs to
	 * @param string $uid Name of the user
	 * @return list<string> an array of group names
	 *
	 * This function fetches all groups a user belongs to. It does not check
	 * if the user exists at all.
	 */
	public function getUserGroups($uid) {
		//guests has empty or null $uid
		if ($uid === null || $uid === '') {
			return [];
		}

		$this->fixDI();

		// No magic!
		$qb = $this->dbConn->getQueryBuilder();
		$cursor = $qb->select('gu.gid', 'g.displayname')
			->from('group_user', 'gu')
			->leftJoin('gu', 'groups', 'g', $qb->expr()->eq('gu.gid', 'g.gid'))
			->where($qb->expr()->eq('uid', $qb->createNamedParameter($uid)))
			->executeQuery();

		$groups = [];
		while ($row = $cursor->fetch()) {
			$groups[] = $row['gid'];
			$this->groupCache[$row['gid']] = [
				'gid' => $row['gid'],
				'displayname' => $row['displayname'],
			];
		}
		$cursor->closeCursor();

		return $groups;
	}

	/**
	 * get a list of all groups
	 * @param string $search
	 * @param int $limit
	 * @param int $offset
	 * @return array an array of group names
	 *
	 * Returns a list with all groups
	 */
	public function getGroups(string $search = '', int $limit = -1, int $offset = 0) {
		$this->fixDI();

		$query = $this->dbConn->getQueryBuilder();
		$query->select('gid', 'displayname')
			->from('groups')
			->orderBy('gid', 'ASC');

		if ($search !== '') {
			$query->where($query->expr()->iLike('gid', $query->createNamedParameter(
				'%' . $this->dbConn->escapeLikeParameter($search) . '%'
			)));
			$query->orWhere($query->expr()->iLike('displayname', $query->createNamedParameter(
				'%' . $this->dbConn->escapeLikeParameter($search) . '%'
			)));
		}

		if ($limit > 0) {
			$query->setMaxResults($limit);
		}
		if ($offset > 0) {
			$query->setFirstResult($offset);
		}
		$result = $query->executeQuery();

		$groups = [];
		while ($row = $result->fetch()) {
			$this->groupCache[$row['gid']] = [
				'displayname' => $row['displayname'],
				'gid' => $row['gid'],
			];
			$groups[] = $row['gid'];
		}
		$result->closeCursor();

		return $groups;
	}

	/**
	 * check if a group exists
	 * @param string $gid
	 * @return bool
	 */
	public function groupExists($gid) {
		$this->fixDI();

		// Check cache first
		if (isset($this->groupCache[$gid])) {
			return true;
		}

		$qb = $this->dbConn->getQueryBuilder();
		$cursor = $qb->select('gid', 'displayname')
			->from('groups')
			->where($qb->expr()->eq('gid', $qb->createNamedParameter($gid)))
			->executeQuery();
		$result = $cursor->fetch();
		$cursor->closeCursor();

		if ($result !== false) {
			$this->groupCache[$gid] = [
				'gid' => $gid,
				'displayname' => $result['displayname'],
			];
			return true;
		}
		return false;
	}

	/**
	 * {@inheritdoc}
	 */
	public function groupsExists(array $gids): array {
		$notFoundGids = [];
		$existingGroups = [];

		// In case the data is already locally accessible, not need to do SQL query
		// or do a SQL query but with a smaller in clause
		foreach ($gids as $gid) {
			if (isset($this->groupCache[$gid])) {
				$existingGroups[] = $gid;
			} else {
				$notFoundGids[] = $gid;
			}
		}

		$qb = $this->dbConn->getQueryBuilder();
		$qb->select('gid', 'displayname')
			->from('groups')
			->where($qb->expr()->in('gid', $qb->createParameter('ids')));
		foreach (array_chunk($notFoundGids, 1000) as $chunk) {
			$qb->setParameter('ids', $chunk, IQueryBuilder::PARAM_STR_ARRAY);
			$result = $qb->executeQuery();
			while ($row = $result->fetch()) {
				$this->groupCache[(string)$row['gid']] = [
					'displayname' => (string)$row['displayname'],
					'gid' => (string)$row['gid'],
				];
				$existingGroups[] = (string)$row['gid'];
			}
			$result->closeCursor();
		}

		return $existingGroups;
	}

	/**
	 * Get a list of all users in a group
	 * @param string $gid
	 * @param string $search
	 * @param int $limit
	 * @param int $offset
	 * @return array<int,string> an array of user ids
	 */
	public function usersInGroup($gid, $search = '', $limit = -1, $offset = 0): array {
		return array_values(array_map(fn ($user) => $user->getUid(), $this->searchInGroup($gid, $search, $limit, $offset)));
	}

	public function searchInGroup(string $gid, string $search = '', int $limit = -1, int $offset = 0): array {
		$this->fixDI();

		$query = $this->dbConn->getQueryBuilder();
		$query->select('g.uid', 'dn.value AS displayname');

		$query->from('group_user', 'g')
			->where($query->expr()->eq('gid', $query->createNamedParameter($gid)))
			->orderBy('g.uid', 'ASC');

		// Join displayname and email from oc_accounts_data
		$query->leftJoin('g', 'accounts_data', 'dn',
			$query->expr()->andX(
				$query->expr()->eq('dn.uid', 'g.uid'),
				$query->expr()->eq('dn.name', $query->expr()->literal('displayname'))
			)
		);

		$query->leftJoin('g', 'accounts_data', 'em',
			$query->expr()->andX(
				$query->expr()->eq('em.uid', 'g.uid'),
				$query->expr()->eq('em.name', $query->expr()->literal('email'))
			)
		);

		if ($search !== '') {
			// sqlite doesn't like re-using a single named parameter here
			$searchParam1 = $query->createNamedParameter('%' . $this->dbConn->escapeLikeParameter($search) . '%');
			$searchParam2 = $query->createNamedParameter('%' . $this->dbConn->escapeLikeParameter($search) . '%');
			$searchParam3 = $query->createNamedParameter('%' . $this->dbConn->escapeLikeParameter($search) . '%');

			$query->andWhere(
				$query->expr()->orX(
					$query->expr()->ilike('g.uid', $searchParam1),
					$query->expr()->ilike('dn.value', $searchParam2),
					$query->expr()->ilike('em.value', $searchParam3)
				)
			)
				->orderBy('g.uid', 'ASC');
		}


		if ($limit !== -1) {
			$query->setMaxResults($limit);
		}
		if ($offset !== 0) {
			$query->setFirstResult($offset);
		}

		$result = $query->executeQuery();

		$users = [];
		$userManager = Server::get(IUserManager::class);
		while ($row = $result->fetch()) {
			$users[$row['uid']] = new LazyUser($row['uid'], $userManager, $row['displayname'] ?? null);
		}
		$result->closeCursor();

		return $users;
	}

	/**
	 * get the number of all users matching the search string in a group
	 * @param string $gid
	 * @param string $search
	 * @return int
	 */
	public function countUsersInGroup(string $gid, string $search = ''): int {
		$this->fixDI();

		$query = $this->dbConn->getQueryBuilder();
		$query->select($query->func()->count('*', 'num_users'))
			->from('group_user')
			->where($query->expr()->eq('gid', $query->createNamedParameter($gid)));

		if ($search !== '') {
			$query->andWhere($query->expr()->like('uid', $query->createNamedParameter(
				'%' . $this->dbConn->escapeLikeParameter($search) . '%'
			)));
		}

		$result = $query->executeQuery();
		$count = $result->fetchOne();
		$result->closeCursor();

		if ($count !== false) {
			$count = (int)$count;
		} else {
			$count = 0;
		}

		return $count;
	}

	/**
	 * get the number of disabled users in a group
	 *
	 * @param string $search
	 *
	 * @return int
	 */
	public function countDisabledInGroup(string $gid): int {
		$this->fixDI();

		$query = $this->dbConn->getQueryBuilder();
		$query->select($query->createFunction('COUNT(DISTINCT ' . $query->getColumnName('uid') . ')'))
			->from('preferences', 'p')
			->innerJoin('p', 'group_user', 'g', $query->expr()->eq('p.userid', 'g.uid'))
			->where($query->expr()->eq('appid', $query->createNamedParameter('core')))
			->andWhere($query->expr()->eq('configkey', $query->createNamedParameter('enabled')))
			->andWhere($query->expr()->eq('configvalue', $query->createNamedParameter('false'), IQueryBuilder::PARAM_STR))
			->andWhere($query->expr()->eq('gid', $query->createNamedParameter($gid), IQueryBuilder::PARAM_STR));

		$result = $query->executeQuery();
		$count = $result->fetchOne();
		$result->closeCursor();

		if ($count !== false) {
			$count = (int)$count;
		} else {
			$count = 0;
		}

		return $count;
	}

	public function getDisplayName(string $gid): string {
		if (isset($this->groupCache[$gid])) {
			$displayName = $this->groupCache[$gid]['displayname'];

			if (isset($displayName) && trim($displayName) !== '') {
				return $displayName;
			}
		}

		$this->fixDI();

		$query = $this->dbConn->getQueryBuilder();
		$query->select('displayname')
			->from('groups')
			->where($query->expr()->eq('gid', $query->createNamedParameter($gid)));

		$result = $query->executeQuery();
		$displayName = $result->fetchOne();
		$result->closeCursor();

		return (string)$displayName;
	}

	public function getGroupDetails(string $gid): array {
		$displayName = $this->getDisplayName($gid);
		if ($displayName !== '') {
			return ['displayName' => $displayName];
		}

		return [];
	}

	/**
	 * {@inheritdoc}
	 */
	public function getGroupsDetails(array $gids): array {
		$notFoundGids = [];
		$details = [];

		$this->fixDI();

		// In case the data is already locally accessible, not need to do SQL query
		// or do a SQL query but with a smaller in clause
		foreach ($gids as $gid) {
			if (isset($this->groupCache[$gid])) {
				$details[$gid] = ['displayName' => $this->groupCache[$gid]['displayname']];
			} else {
				$notFoundGids[] = $gid;
			}
		}

		foreach (array_chunk($notFoundGids, 1000) as $chunk) {
			$query = $this->dbConn->getQueryBuilder();
			$query->select('gid', 'displayname')
				->from('groups')
				->where($query->expr()->in('gid', $query->createNamedParameter($chunk, IQueryBuilder::PARAM_STR_ARRAY)));

			$result = $query->executeQuery();
			while ($row = $result->fetch()) {
				$details[(string)$row['gid']] = ['displayName' => (string)$row['displayname']];
				$this->groupCache[(string)$row['gid']] = [
					'displayname' => (string)$row['displayname'],
					'gid' => (string)$row['gid'],
				];
			}
			$result->closeCursor();
		}

		return $details;
	}

	public function setDisplayName(string $gid, string $displayName): bool {
		if (!$this->groupExists($gid)) {
			return false;
		}

		$this->fixDI();

		$displayName = trim($displayName);
		if ($displayName === '') {
			$displayName = $gid;
		}

		$query = $this->dbConn->getQueryBuilder();
		$query->update('groups')
			->set('displayname', $query->createNamedParameter($displayName))
			->where($query->expr()->eq('gid', $query->createNamedParameter($gid)));
		$query->executeStatement();

		return true;
	}

	/**
	 * Backend name to be shown in group management
	 * @return string the name of the backend to be shown
	 * @since 21.0.0
	 */
	public function getBackendName(): string {
		return 'Database';
	}

	/**
	 * Compute group ID from display name (GIDs are limited to 64 characters in database)
	 */
	private function computeGid(string $displayName): string {
		return mb_strlen($displayName) > 64
			? hash('sha256', $displayName)
			: $displayName;
	}

	public function addGroupToGroup(string $childGid, string $parentGid): bool {
		$this->fixDI();

		if ($childGid === $parentGid) {
			throw new CycleDetectedException('A group cannot be a subgroup of itself');
		}

		// Serialize the cycle check and insert to close the TOCTOU window
		// between "is there already a path back to parent?" and "insert the edge".
		// Concurrent writers on the same backend will contend on this transaction.
		$this->dbConn->beginTransaction();
		try {
			// Reject if the edge would introduce a cycle: if $parent is already
			// reachable as a descendant of $child, adding parent -> child forms a loop.
			if ($this->isDescendantOf($parentGid, $childGid)) {
				$this->dbConn->rollBack();
				throw new CycleDetectedException(
					"Adding group '$childGid' under '$parentGid' would introduce a cycle"
				);
			}

			if ($this->groupInGroup($childGid, $parentGid)) {
				$this->dbConn->rollBack();
				return false;
			}

			try {
				$qb = $this->dbConn->getQueryBuilder();
				$qb->insert('group_group')
					->setValue('parent_gid', $qb->createNamedParameter($parentGid))
					->setValue('child_gid', $qb->createNamedParameter($childGid))
					->executeStatement();
			} catch (Exception $e) {
				$this->dbConn->rollBack();
				if ($e->getReason() === Exception::REASON_UNIQUE_CONSTRAINT_VIOLATION) {
					return false;
				}
				throw $e;
			}
			$this->dbConn->commit();
		} catch (CycleDetectedException $e) {
			// rollBack already called above
			throw $e;
		} catch (\Throwable $e) {
			if ($this->dbConn->inTransaction()) {
				$this->dbConn->rollBack();
			}
			throw $e;
		}
		return true;
	}

	public function removeGroupFromGroup(string $childGid, string $parentGid): bool {
		$this->fixDI();

		$qb = $this->dbConn->getQueryBuilder();
		$affected = $qb->delete('group_group')
			->where($qb->expr()->eq('parent_gid', $qb->createNamedParameter($parentGid)))
			->andWhere($qb->expr()->eq('child_gid', $qb->createNamedParameter($childGid)))
			->executeStatement();

		return $affected > 0;
	}

	public function getChildGroups(string $parentGid): array {
		$this->fixDI();

		$qb = $this->dbConn->getQueryBuilder();
		$result = $qb->select('child_gid')
			->from('group_group')
			->where($qb->expr()->eq('parent_gid', $qb->createNamedParameter($parentGid)))
			->executeQuery();

		$gids = [];
		while ($row = $result->fetch()) {
			$gids[] = $row['child_gid'];
		}
		$result->closeCursor();
		return $gids;
	}

	public function getChildGroupsBatch(array $parentGids): array {
		if ($parentGids === []) {
			return [];
		}
		$this->fixDI();
		$result = [];
		foreach ($parentGids as $gid) {
			$result[$gid] = [];
		}

		$qb = $this->dbConn->getQueryBuilder();
		$cursor = $qb->select('parent_gid', 'child_gid')
			->from('group_group')
			->where($qb->expr()->in(
				'parent_gid',
				$qb->createNamedParameter($parentGids, IQueryBuilder::PARAM_STR_ARRAY)
			))
			->executeQuery();
		while ($row = $cursor->fetch()) {
			$result[$row['parent_gid']][] = $row['child_gid'];
		}
		$cursor->closeCursor();
		return $result;
	}

	public function getParentGroups(string $childGid): array {
		$this->fixDI();

		$qb = $this->dbConn->getQueryBuilder();
		$result = $qb->select('parent_gid')
			->from('group_group')
			->where($qb->expr()->eq('child_gid', $qb->createNamedParameter($childGid)))
			->executeQuery();

		$gids = [];
		while ($row = $result->fetch()) {
			$gids[] = $row['parent_gid'];
		}
		$result->closeCursor();
		return $gids;
	}

	public function getParentGroupsBatch(array $childGids): array {
		if ($childGids === []) {
			return [];
		}
		$this->fixDI();
		$result = [];
		foreach ($childGids as $gid) {
			$result[$gid] = [];
		}

		$qb = $this->dbConn->getQueryBuilder();
		$cursor = $qb->select('parent_gid', 'child_gid')
			->from('group_group')
			->where($qb->expr()->in(
				'child_gid',
				$qb->createNamedParameter($childGids, IQueryBuilder::PARAM_STR_ARRAY)
			))
			->executeQuery();
		while ($row = $cursor->fetch()) {
			$result[$row['child_gid']][] = $row['parent_gid'];
		}
		$cursor->closeCursor();
		return $result;
	}

	public function groupInGroup(string $childGid, string $parentGid): bool {
		$this->fixDI();

		$qb = $this->dbConn->getQueryBuilder();
		$result = $qb->select('parent_gid')
			->from('group_group')
			->where($qb->expr()->eq('parent_gid', $qb->createNamedParameter($parentGid)))
			->andWhere($qb->expr()->eq('child_gid', $qb->createNamedParameter($childGid)))
			->setMaxResults(1)
			->executeQuery();

		$row = $result->fetch();
		$result->closeCursor();
		return $row !== false;
	}

	/**
	 * BFS: is $candidate reachable from $root by following parent -> child edges?
	 */
	private function isDescendantOf(string $candidate, string $root): bool {
		if ($candidate === $root) {
			return true;
		}
		$visited = [$root => true];
		$frontier = [$root];
		while ($frontier !== []) {
			$children = [];
			foreach ($frontier as $gid) {
				foreach ($this->getChildGroups($gid) as $child) {
					if ($child === $candidate) {
						return true;
					}
					if (!isset($visited[$child])) {
						$visited[$child] = true;
						$children[] = $child;
					}
				}
			}
			$frontier = $children;
		}
		return false;
	}
}
