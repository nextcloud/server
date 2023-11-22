<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Johannes Leuker <j.leuker@hosting.de>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Loki3000 <github@labcms.ru>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author tgrant <tom.grant760@gmail.com>
 * @author Tom Grant <TomG736@users.noreply.github.com>
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
namespace OC\Group;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Group\Backend\ABackend;
use OCP\Group\Backend\IAddToGroupBackend;
use OCP\Group\Backend\IBatchMethodsBackend;
use OCP\Group\Backend\ICountDisabledInGroup;
use OCP\Group\Backend\ICountUsersBackend;
use OCP\Group\Backend\ICreateGroupBackend;
use OCP\Group\Backend\IDeleteGroupBackend;
use OCP\Group\Backend\IGetDisplayNameBackend;
use OCP\Group\Backend\IGroupDetailsBackend;
use OCP\Group\Backend\IRemoveFromGroupBackend;
use OCP\Group\Backend\ISearchableGroupBackend;
use OCP\Group\Backend\ISetDisplayNameBackend;
use OCP\Group\Backend\INamedBackend;
use OCP\IDBConnection;
use OCP\IUserManager;
use OC\User\LazyUser;

/**
 * Class for group management in a SQL Database (e.g. MySQL, SQLite)
 */
class Database extends ABackend implements
	IAddToGroupBackend,
	ICountDisabledInGroup,
	ICountUsersBackend,
	ICreateGroupBackend,
	IDeleteGroupBackend,
	IGetDisplayNameBackend,
	IGroupDetailsBackend,
	IRemoveFromGroupBackend,
	ISetDisplayNameBackend,
	ISearchableGroupBackend,
	IBatchMethodsBackend,
	INamedBackend {
	/** @var array<string, array{gid: string, displayname: string}> */
	private $groupCache = [];
	private ?IDBConnection $dbConn;

	/**
	 * \OC\Group\Database constructor.
	 *
	 * @param IDBConnection|null $dbConn
	 */
	public function __construct(IDBConnection $dbConn = null) {
		$this->dbConn = $dbConn;
	}

	/**
	 * FIXME: This function should not be required!
	 */
	private function fixDI() {
		if ($this->dbConn === null) {
			$this->dbConn = \OC::$server->getDatabaseConnection();
		}
	}

	/**
	 * Try to create a new group
	 * @param string $gid The name of the group to create
	 * @return bool
	 *
	 * Tries to create a new group. If the group name already exists, false will
	 * be returned.
	 */
	public function createGroup(string $gid): bool {
		$this->fixDI();

		try {
			// Add group
			$builder = $this->dbConn->getQueryBuilder();
			$result = $builder->insert('groups')
				->setValue('gid', $builder->createNamedParameter($gid))
				->setValue('displayname', $builder->createNamedParameter($gid))
				->execute();
		} catch (UniqueConstraintViolationException $e) {
			$result = 0;
		}

		// Add to cache
		$this->groupCache[$gid] = [
			'gid' => $gid,
			'displayname' => $gid
		];

		return $result === 1;
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
			->execute();

		// Delete the group-user relation
		$qb = $this->dbConn->getQueryBuilder();
		$qb->delete('group_user')
			->where($qb->expr()->eq('gid', $qb->createNamedParameter($gid)))
			->execute();

		// Delete the group-groupadmin relation
		$qb = $this->dbConn->getQueryBuilder();
		$qb->delete('group_admin')
			->where($qb->expr()->eq('gid', $qb->createNamedParameter($gid)))
			->execute();

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
			->execute();

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
				->execute();
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
			->execute();

		return true;
	}

	/**
	 * Get all groups a user belongs to
	 * @param string $uid Name of the user
	 * @return array an array of group names
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
			->execute();

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
		$result = $query->execute();

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
			->execute();
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
		$query->select('g.uid', 'u.displayname');

		$query->from('group_user', 'g')
			->where($query->expr()->eq('gid', $query->createNamedParameter($gid)))
			->orderBy('g.uid', 'ASC');

		$query->leftJoin('g', 'users', 'u', $query->expr()->eq('g.uid', 'u.uid'));

		if ($search !== '') {
			$query->leftJoin('u', 'preferences', 'p', $query->expr()->andX(
				$query->expr()->eq('p.userid', 'u.uid'),
				$query->expr()->eq('p.appid', $query->expr()->literal('settings')),
				$query->expr()->eq('p.configkey', $query->expr()->literal('email'))
			))
				// sqlite doesn't like re-using a single named parameter here
				->andWhere(
					$query->expr()->orX(
						$query->expr()->ilike('g.uid', $query->createNamedParameter('%' . $this->dbConn->escapeLikeParameter($search) . '%')),
						$query->expr()->ilike('u.displayname', $query->createNamedParameter('%' . $this->dbConn->escapeLikeParameter($search) . '%')),
						$query->expr()->ilike('p.configvalue', $query->createNamedParameter('%' . $this->dbConn->escapeLikeParameter($search) . '%'))
					)
				)
				->orderBy('u.uid_lower', 'ASC');
		}

		if ($limit !== -1) {
			$query->setMaxResults($limit);
		}
		if ($offset !== 0) {
			$query->setFirstResult($offset);
		}

		$result = $query->executeQuery();

		$users = [];
		$userManager = \OCP\Server::get(IUserManager::class);
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

		$result = $query->execute();
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

		$result = $query->execute();
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

		$result = $query->execute();
		$displayName = $result->fetchOne();
		$result->closeCursor();

		return (string) $displayName;
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
		$query->execute();

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
}
