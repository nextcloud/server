<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC;

use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\EventDispatcher\IEventListener;
use OCP\Group\Events\GroupDeletedEvent;
use OCP\Group\Events\SubAdminAddedEvent;
use OCP\Group\Events\SubAdminRemovedEvent;
use OCP\Group\ISubAdmin;
use OCP\IDBConnection;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserManager;
use OCP\User\Events\UserDeletedEvent;
use Override;

/**
 * @template-implements IEventListener<UserDeletedEvent|GroupDeletedEvent>
 */
class SubAdmin implements ISubAdmin, IEventListener {
	public function __construct(
		private IUserManager $userManager,
		private IGroupManager $groupManager,
		private IDBConnection $dbConn,
		private IEventDispatcher $eventDispatcher,
	) {
	}

	#[Override]
	public function handle(Event $event): void {
		if ($event instanceof GroupDeletedEvent) {
			$this->post_deleteGroup($event->getGroup());
		}

		if ($event instanceof UserDeletedEvent) {
			$this->post_deleteUser($event->getUser());
		}
	}

	#[Override]
	public function createSubAdmin(IUser $user, IGroup $group): void {
		$qb = $this->dbConn->getQueryBuilder();

		$qb->insert('group_admin')
			->values([
				'gid' => $qb->createNamedParameter($group->getGID()),
				'uid' => $qb->createNamedParameter($user->getUID())
			])
			->executeStatement();

		$this->eventDispatcher->dispatchTyped(new SubAdminAddedEvent($group, $user));
	}

	#[Override]
	public function deleteSubAdmin(IUser $user, IGroup $group): void {
		$qb = $this->dbConn->getQueryBuilder();

		$qb->delete('group_admin')
			->where($qb->expr()->eq('gid', $qb->createNamedParameter($group->getGID())))
			->andWhere($qb->expr()->eq('uid', $qb->createNamedParameter($user->getUID())))
			->executeStatement();

		$this->eventDispatcher->dispatchTyped(new SubAdminRemovedEvent($group, $user));
	}

	#[Override]
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
	 * Get group ids of a SubAdmin
	 * @param IUser $user the SubAdmin
	 * @return string[]
	 */
	public function getSubAdminsGroupIds(IUser $user): array {
		$qb = $this->dbConn->getQueryBuilder();

		$result = $qb->select('gid')
			->from('group_admin')
			->where($qb->expr()->eq('uid', $qb->createNamedParameter($user->getUID())))
			->executeQuery();

		$groups = [];
		while ($row = $result->fetch()) {
			$groups[] = $row['gid'];
		}
		$result->closeCursor();

		return $groups;
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

	#[Override]
	public function getGroupsSubAdmins(IGroup $group): array {
		$qb = $this->dbConn->getQueryBuilder();

		$result = $qb->select('uid')
			->from('group_admin')
			->where($qb->expr()->eq('gid', $qb->createNamedParameter($group->getGID())))
			->executeQuery();

		$users = [];
		while ($row = $result->fetch()) {
			$user = $this->userManager->get($row['uid']);
			if (!is_null($user)) {
				$users[] = $user;
			}
		}
		$result->closeCursor();

		return $users;
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

	#[Override]
	public function isSubAdminOfGroup(IUser $user, IGroup $group): bool {
		$qb = $this->dbConn->getQueryBuilder();

		/*
		 * Primary key is ('gid', 'uid') so max 1 result possible here
		 */
		$result = $qb->select('*')
			->from('group_admin')
			->where($qb->expr()->eq('gid', $qb->createNamedParameter($group->getGID())))
			->andWhere($qb->expr()->eq('uid', $qb->createNamedParameter($user->getUID())))
			->executeQuery();

		$fetch = $result->fetch();
		$result->closeCursor();
		$result = !empty($fetch) ? true : false;

		return $result;
	}

	#[Override]
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

		return $isSubAdmin !== false;
	}

	#[Override]
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
		$userGroups = $this->groupManager->getUserGroupIds($user);

		return !empty(array_intersect($accessibleGroups, $userGroups));
	}

	/**
	 * Delete all SubAdmins by $user
	 */
	private function post_deleteUser(IUser $user): void {
		$qb = $this->dbConn->getQueryBuilder();

		$qb->delete('group_admin')
			->where($qb->expr()->eq('uid', $qb->createNamedParameter($user->getUID())))
			->executeStatement();
	}

	/**
	 * Delete all SubAdmins by $group
	 */
	private function post_deleteGroup(IGroup $group): void {
		$qb = $this->dbConn->getQueryBuilder();

		$qb->delete('group_admin')
			->where($qb->expr()->eq('gid', $qb->createNamedParameter($group->getGID())))
			->executeStatement();
	}
}
