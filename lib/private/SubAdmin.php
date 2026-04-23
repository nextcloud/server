<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC;

use OC\Hooks\PublicEmitter;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Group\Events\SubAdminAddedEvent;
use OCP\Group\Events\SubAdminRemovedEvent;
use OCP\Group\ISubAdmin;
use OCP\IDBConnection;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserManager;

class SubAdmin extends PublicEmitter implements ISubAdmin {
	public function __construct(
		private IUserManager $userManager,
		private IGroupManager $groupManager,
		private IDBConnection $dbConn,
		private IEventDispatcher $eventDispatcher,
	) {
		$this->userManager->listen('\OC\User', 'postDelete', function ($user): void {
			$this->post_deleteUser($user);
		});
		$this->groupManager->listen('\OC\Group', 'postDelete', function ($group): void {
			$this->post_deleteGroup($group);
		});
	}

	/**
	 * add a SubAdmin
	 * @param IUser $user user to be SubAdmin
	 * @param IGroup $group group $user becomes subadmin of
	 */
	public function createSubAdmin(IUser $user, IGroup $group): void {
		$qb = $this->dbConn->getQueryBuilder();

		$qb->insert('group_admin')
			->values([
				'gid' => $qb->createNamedParameter($group->getGID()),
				'uid' => $qb->createNamedParameter($user->getUID())
			])
			->executeStatement();

		/** @deprecated 21.0.0 - use type SubAdminAddedEvent instead  */
		$this->emit('\OC\SubAdmin', 'postCreateSubAdmin', [$user, $group]);
		$event = new SubAdminAddedEvent($group, $user);
		$this->eventDispatcher->dispatchTyped($event);
	}

	/**
	 * delete a SubAdmin
	 * @param IUser $user the user that is the SubAdmin
	 * @param IGroup $group the group
	 */
	public function deleteSubAdmin(IUser $user, IGroup $group): void {
		$qb = $this->dbConn->getQueryBuilder();

		$qb->delete('group_admin')
			->where($qb->expr()->eq('gid', $qb->createNamedParameter($group->getGID())))
			->andWhere($qb->expr()->eq('uid', $qb->createNamedParameter($user->getUID())))
			->executeStatement();

		/** @deprecated 21.0.0 - use type SubAdminRemovedEvent instead  */
		$this->emit('\OC\SubAdmin', 'postDeleteSubAdmin', [$user, $group]);
		$event = new SubAdminRemovedEvent($group, $user);
		$this->eventDispatcher->dispatchTyped($event);
	}

	/**
	 * get groups of a SubAdmin
	 * @param IUser $user the SubAdmin
	 * @return IGroup[]
	 */
	public function getSubAdminsGroups(IUser $user): array {
		$groupIds = $this->getSubAdminsGroupIds($user);

		$groups = [];
		foreach ($groupIds as $groupId) {
			$group = $this->groupManager->get($groupId);
			if ($group !== null) {
				$groups[$group->getGID()] = $group;
			}
		}

		return $groups;
	}

	/**
	 * Get group ids of a SubAdmin.
	 *
	 * Returns the effective set including:
	 *   - groups the user is directly admin of ({@code group_admin.uid = $user})
	 *   - groups reached via group-level delegation ({@code group_group_admin}
	 *     where {@code admin_gid} is any effective group of $user)
	 *   - every transitive descendant of the groups above, because being
	 *     admin of a parent group implies the ability to administer its
	 *     subgroups.
	 *
	 * @param IUser $user the SubAdmin
	 * @return string[]
	 */
	public function getSubAdminsGroupIds(IUser $user): array {
		$directTargets = [];

		$qb = $this->dbConn->getQueryBuilder();
		$result = $qb->select('gid')
			->from('group_admin')
			->where($qb->expr()->eq('uid', $qb->createNamedParameter($user->getUID())))
			->executeQuery();
		while ($row = $result->fetch()) {
			$directTargets[$row['gid']] = true;
		}
		$result->closeCursor();

		// Group-level delegation: any effective group of $user designated
		// as admin of some target group.
		$effectiveGroups = $this->groupManager->getUserEffectiveGroupIds($user);
		if ($effectiveGroups !== []) {
			$qb = $this->dbConn->getQueryBuilder();
			$result = $qb->select('gid')
				->from('group_group_admin')
				->where($qb->expr()->in(
					'admin_gid',
					$qb->createNamedParameter($effectiveGroups, IQueryBuilder::PARAM_STR_ARRAY)
				))
				->executeQuery();
			while ($row = $result->fetch()) {
				$directTargets[$row['gid']] = true;
			}
			$result->closeCursor();
		}

		// Descend: if $user admins P, they also admin every subgroup of P.
		$all = [];
		foreach (array_keys($directTargets) as $gid) {
			$group = $this->groupManager->get($gid);
			if ($group === null) {
				$all[$gid] = true;
				continue;
			}
			foreach ($this->groupManager->getGroupEffectiveDescendantIds($group) as $descendant) {
				$all[$descendant] = true;
			}
		}

		return array_keys($all);
	}

	/**
	 * get an array of groupid and displayName for a user
	 * @param IUser $user
	 * @return array ['displayName' => displayname]
	 */
	public function getSubAdminsGroupsName(IUser $user): array {
		return array_map(function ($group) {
			return ['displayName' => $group->getDisplayName()];
		}, $this->getSubAdminsGroups($user));
	}

	/**
	 * Get SubAdmins of a group.
	 *
	 * Collects:
	 *   - direct user sub-admins of $group;
	 *   - direct user sub-admins of any ancestor of $group (inherited);
	 *   - every effective member of a group designated as admin of $group
	 *     or any of its ancestors via {@code group_group_admin}.
	 *
	 * @param IGroup $group the group
	 * @return IUser[]
	 */
	public function getGroupsSubAdmins(IGroup $group): array {
		$targetGids = $this->groupManager->getGroupEffectiveAncestorIds($group);
		if ($targetGids === []) {
			$targetGids = [$group->getGID()];
		}

		$users = [];

		// Direct user sub-admins of $group or any ancestor.
		$qb = $this->dbConn->getQueryBuilder();
		$result = $qb->selectDistinct('uid')
			->from('group_admin')
			->where($qb->expr()->in(
				'gid',
				$qb->createNamedParameter($targetGids, IQueryBuilder::PARAM_STR_ARRAY)
			))
			->executeQuery();
		while ($row = $result->fetch()) {
			$uid = $row['uid'];
			if (isset($users[$uid])) {
				continue;
			}
			$user = $this->userManager->get($uid);
			if ($user !== null) {
				$users[$uid] = $user;
			}
		}
		$result->closeCursor();

		// Group-level sub-admins across the same ancestor set.
		foreach ($targetGids as $targetGid) {
			$target = $this->groupManager->get($targetGid);
			if ($target === null) {
				continue;
			}
			foreach ($this->getGroupSubAdminsOfGroup($target) as $adminGroup) {
				// Every effective member of $adminGroup (including its
				// nested descendants) is a sub-admin of the target.
				foreach ($this->groupManager->getGroupEffectiveDescendantIds($adminGroup) as $descendantGid) {
					$descendant = $this->groupManager->get($descendantGid);
					if ($descendant === null) {
						continue;
					}
					foreach ($descendant->searchUsers('') as $user) {
						$users[$user->getUID()] = $user;
					}
				}
			}
		}

		return array_values($users);
	}

	/**
	 * get all SubAdmins
	 * @return array
	 */
	public function getAllSubAdmins(): array {
		$qb = $this->dbConn->getQueryBuilder();

		$result = $qb->select('*')
			->from('group_admin')
			->executeQuery();

		$subadmins = [];
		while ($row = $result->fetch()) {
			$user = $this->userManager->get($row['uid']);
			$group = $this->groupManager->get($row['gid']);
			if (!is_null($user) && !is_null($group)) {
				$subadmins[] = [
					'user' => $user,
					'group' => $group
				];
			}
		}
		$result->closeCursor();

		return $subadmins;
	}

	/**
	 * Checks if $user is a SubAdmin of $group.
	 *
	 * The check honors group-level delegation and ancestor inheritance:
	 *   - direct row in {@code group_admin} for $user / $group, or any
	 *     ancestor of $group;
	 *   - effective group of $user listed in {@code group_group_admin} for
	 *     $group or any ancestor of $group.
	 *
	 * @param IUser $user
	 * @param IGroup $group
	 * @return bool
	 */
	public function isSubAdminOfGroup(IUser $user, IGroup $group): bool {
		// Candidate target gids: $group and all of its ancestors. Being
		// admin of an ancestor implies being admin of $group.
		$targetGids = $this->groupManager->getGroupEffectiveAncestorIds($group);
		if ($targetGids === []) {
			$targetGids = [$group->getGID()];
		}

		$qb = $this->dbConn->getQueryBuilder();
		$result = $qb->select('gid')
			->from('group_admin')
			->where($qb->expr()->eq('uid', $qb->createNamedParameter($user->getUID())))
			->andWhere($qb->expr()->in(
				'gid',
				$qb->createNamedParameter($targetGids, IQueryBuilder::PARAM_STR_ARRAY)
			))
			->setMaxResults(1)
			->executeQuery();
		$fetch = $result->fetch();
		$result->closeCursor();
		if ($fetch !== false) {
			return true;
		}

		// Group-level delegation across the same ancestor set.
		$effectiveGroups = $this->groupManager->getUserEffectiveGroupIds($user);
		if ($effectiveGroups === []) {
			return false;
		}
		$qb = $this->dbConn->getQueryBuilder();
		$result = $qb->select('admin_gid')
			->from('group_group_admin')
			->where($qb->expr()->in(
				'gid',
				$qb->createNamedParameter($targetGids, IQueryBuilder::PARAM_STR_ARRAY)
			))
			->andWhere($qb->expr()->in(
				'admin_gid',
				$qb->createNamedParameter($effectiveGroups, IQueryBuilder::PARAM_STR_ARRAY)
			))
			->setMaxResults(1)
			->executeQuery();
		$fetch = $result->fetch();
		$result->closeCursor();

		return $fetch !== false;
	}

	/**
	 * checks if a user is a SubAdmin
	 * @param IUser $user
	 * @return bool
	 */
	public function isSubAdmin(IUser $user): bool {
		// Check if the user is already an admin
		if ($this->groupManager->isAdmin($user->getUID())) {
			return true;
		}

		// Check if the user is already an admin
		if ($this->groupManager->isDelegatedAdmin($user->getUID())) {
			return true;
		}

		$qb = $this->dbConn->getQueryBuilder();

		$result = $qb->select('gid')
			->from('group_admin')
			->andWhere($qb->expr()->eq('uid', $qb->createNamedParameter($user->getUID())))
			->setMaxResults(1)
			->executeQuery();

		$isSubAdmin = $result->fetch();
		$result->closeCursor();
		if ($isSubAdmin !== false) {
			return true;
		}

		// Group-level delegation: any of the user's effective groups appears
		// in group_group_admin.admin_gid?
		$effectiveGroups = $this->groupManager->getUserEffectiveGroupIds($user);
		if ($effectiveGroups === []) {
			return false;
		}
		$qb = $this->dbConn->getQueryBuilder();
		$result = $qb->select('gid')
			->from('group_group_admin')
			->where($qb->expr()->in(
				'admin_gid',
				$qb->createNamedParameter($effectiveGroups, IQueryBuilder::PARAM_STR_ARRAY)
			))
			->setMaxResults(1)
			->executeQuery();
		$row = $result->fetch();
		$result->closeCursor();

		return $row !== false;
	}

	/**
	 * checks if a user is a accessible by a subadmin
	 * @param IUser $subadmin
	 * @param IUser $user
	 * @return bool
	 */
	public function isUserAccessible(IUser $subadmin, IUser $user): bool {
		if ($subadmin->getUID() === $user->getUID()) {
			return true;
		}
		if (!$this->isSubAdmin($subadmin)) {
			return false;
		}
		if ($this->groupManager->isAdmin($user->getUID())) {
			return false;
		}

		$accessibleGroups = $this->getSubAdminsGroupIds($subadmin);
		$userGroups = $this->groupManager->getUserEffectiveGroupIds($user);

		return !empty(array_intersect($accessibleGroups, $userGroups));
	}

	/**
	 * delete all SubAdmins by $user
	 * @param IUser $user
	 */
	private function post_deleteUser(IUser $user) {
		$qb = $this->dbConn->getQueryBuilder();

		$qb->delete('group_admin')
			->where($qb->expr()->eq('uid', $qb->createNamedParameter($user->getUID())))
			->executeStatement();
	}

	/**
	 * delete all SubAdmins by $group
	 *
	 * Note: {@see \OC\Group\Database::deleteGroup()} already cleans the
	 * {@code group_group_admin} rows when a database group is removed.
	 * This listener only needs to handle the legacy {@code group_admin} table.
	 *
	 * @param IGroup $group
	 */
	private function post_deleteGroup(IGroup $group) {
		$qb = $this->dbConn->getQueryBuilder();

		$qb->delete('group_admin')
			->where($qb->expr()->eq('gid', $qb->createNamedParameter($group->getGID())))
			->executeStatement();
	}

	public function createGroupSubAdmin(IGroup $adminGroup, IGroup $group): void {
		$qb = $this->dbConn->getQueryBuilder();
		try {
			$qb->insert('group_group_admin')
				->values([
					'admin_gid' => $qb->createNamedParameter($adminGroup->getGID()),
					'gid' => $qb->createNamedParameter($group->getGID()),
				])
				->executeStatement();
		} catch (\OCP\DB\Exception $e) {
			if ($e->getReason() !== \OCP\DB\Exception::REASON_UNIQUE_CONSTRAINT_VIOLATION) {
				throw $e;
			}
			// Idempotent.
		}
	}

	public function deleteGroupSubAdmin(IGroup $adminGroup, IGroup $group): void {
		$qb = $this->dbConn->getQueryBuilder();
		$qb->delete('group_group_admin')
			->where($qb->expr()->eq('admin_gid', $qb->createNamedParameter($adminGroup->getGID())))
			->andWhere($qb->expr()->eq('gid', $qb->createNamedParameter($group->getGID())))
			->executeStatement();
	}

	public function getGroupSubAdminsOfGroup(IGroup $group): array {
		$qb = $this->dbConn->getQueryBuilder();
		$result = $qb->select('admin_gid')
			->from('group_group_admin')
			->where($qb->expr()->eq('gid', $qb->createNamedParameter($group->getGID())))
			->executeQuery();

		$groups = [];
		while ($row = $result->fetch()) {
			$adminGroup = $this->groupManager->get($row['admin_gid']);
			if ($adminGroup !== null) {
				$groups[] = $adminGroup;
			}
		}
		$result->closeCursor();
		return $groups;
	}
}
