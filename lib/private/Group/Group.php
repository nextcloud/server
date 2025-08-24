<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Group;

use OC\Hooks\PublicEmitter;
use OC\User\LazyUser;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Group\Backend\ICountDisabledInGroup;
use OCP\Group\Backend\IGetDisplayNameBackend;
use OCP\Group\Backend\IHideFromCollaborationBackend;
use OCP\Group\Backend\INamedBackend;
use OCP\Group\Backend\ISearchableGroupBackend;
use OCP\Group\Backend\ISetDisplayNameBackend;
use OCP\Group\Events\BeforeGroupChangedEvent;
use OCP\Group\Events\BeforeGroupDeletedEvent;
use OCP\Group\Events\BeforeUserAddedEvent;
use OCP\Group\Events\BeforeUserRemovedEvent;
use OCP\Group\Events\GroupChangedEvent;
use OCP\Group\Events\GroupDeletedEvent;
use OCP\Group\Events\UserAddedEvent;
use OCP\Group\Events\UserRemovedEvent;
use OCP\GroupInterface;
use OCP\IGroup;
use OCP\IUser;
use OCP\IUserManager;

class Group implements IGroup {
	/** @var null|string */
	protected $displayName;

	/** @var string */
	private $gid;

	/** @var \OC\User\User[] */
	private $users = [];

	/** @var bool */
	private $usersLoaded;

	/** @var Backend[] */
	private $backends;
	/** @var IEventDispatcher */
	private $dispatcher;
	/** @var \OC\User\Manager|IUserManager */
	private $userManager;
	/** @var PublicEmitter */
	private $emitter;

	public function __construct(string $gid, array $backends, IEventDispatcher $dispatcher, IUserManager $userManager, ?PublicEmitter $emitter = null, ?string $displayName = null) {
		$this->gid = $gid;
		$this->backends = $backends;
		$this->dispatcher = $dispatcher;
		$this->userManager = $userManager;
		$this->emitter = $emitter;
		$this->displayName = $displayName;
	}

	public function getGID(): string {
		return $this->gid;
	}

	public function getDisplayName(): string {
		if (is_null($this->displayName)) {
			foreach ($this->backends as $backend) {
				if ($backend instanceof IGetDisplayNameBackend) {
					$displayName = $backend->getDisplayName($this->gid);
					if (trim($displayName) !== '') {
						$this->displayName = $displayName;
						return $this->displayName;
					}
				}
			}
			return $this->gid;
		}
		return $this->displayName;
	}

	public function setDisplayName(string $displayName): bool {
		$displayName = trim($displayName);
		if ($displayName !== '') {
			$this->dispatcher->dispatchTyped(new BeforeGroupChangedEvent($this, 'displayName', $displayName, $this->displayName));
			foreach ($this->backends as $backend) {
				if (($backend instanceof ISetDisplayNameBackend)
					&& $backend->setDisplayName($this->gid, $displayName)) {
					$this->displayName = $displayName;
					$this->dispatcher->dispatchTyped(new GroupChangedEvent($this, 'displayName', $displayName, ''));
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * get all users in the group
	 *
	 * @return \OC\User\User[]
	 */
	public function getUsers(): array {
		if ($this->usersLoaded) {
			return $this->users;
		}

		$userIds = [];
		foreach ($this->backends as $backend) {
			$diff = array_diff(
				$backend->usersInGroup($this->gid),
				$userIds
			);
			if ($diff) {
				$userIds = array_merge($userIds, $diff);
			}
		}

		$this->users = $this->getVerifiedUsers($userIds);
		$this->usersLoaded = true;
		return $this->users;
	}

	/**
	 * check if a user is in the group
	 *
	 * @param IUser $user
	 * @return bool
	 */
	public function inGroup(IUser $user): bool {
		if (isset($this->users[$user->getUID()])) {
			return true;
		}
		foreach ($this->backends as $backend) {
			if ($backend->inGroup($user->getUID(), $this->gid)) {
				$this->users[$user->getUID()] = $user;
				return true;
			}
		}
		return false;
	}

	/**
	 * add a user to the group
	 *
	 * @param IUser $user
	 */
	public function addUser(IUser $user): void {
		if ($this->inGroup($user)) {
			return;
		}

		$this->dispatcher->dispatchTyped(new BeforeUserAddedEvent($this, $user));

		if ($this->emitter) {
			$this->emitter->emit('\OC\Group', 'preAddUser', [$this, $user]);
		}
		foreach ($this->backends as $backend) {
			if ($backend->implementsActions(\OC\Group\Backend::ADD_TO_GROUP)) {
				$backend->addToGroup($user->getUID(), $this->gid);
				$this->users[$user->getUID()] = $user;

				$this->dispatcher->dispatchTyped(new UserAddedEvent($this, $user));

				if ($this->emitter) {
					$this->emitter->emit('\OC\Group', 'postAddUser', [$this, $user]);
				}
				return;
			}
		}
	}

	/**
	 * remove a user from the group
	 */
	public function removeUser(IUser $user): void {
		$result = false;
		$this->dispatcher->dispatchTyped(new BeforeUserRemovedEvent($this, $user));
		if ($this->emitter) {
			$this->emitter->emit('\OC\Group', 'preRemoveUser', [$this, $user]);
		}
		foreach ($this->backends as $backend) {
			if ($backend->implementsActions(\OC\Group\Backend::REMOVE_FROM_GOUP) and $backend->inGroup($user->getUID(), $this->gid)) {
				$backend->removeFromGroup($user->getUID(), $this->gid);
				$result = true;
			}
		}
		if ($result) {
			$this->dispatcher->dispatchTyped(new UserRemovedEvent($this, $user));
			if ($this->emitter) {
				$this->emitter->emit('\OC\Group', 'postRemoveUser', [$this, $user]);
			}
			if ($this->users) {
				foreach ($this->users as $index => $groupUser) {
					if ($groupUser->getUID() === $user->getUID()) {
						unset($this->users[$index]);
						return;
					}
				}
			}
		}
	}

	/**
	 * Search for users in the group by userid or display name
	 * @return IUser[]
	 */
	public function searchUsers(string $search, ?int $limit = null, ?int $offset = null): array {
		$users = [];
		foreach ($this->backends as $backend) {
			if ($backend instanceof ISearchableGroupBackend) {
				$users += $backend->searchInGroup($this->gid, $search, $limit ?? -1, $offset ?? 0);
			} else {
				$userIds = $backend->usersInGroup($this->gid, $search, $limit ?? -1, $offset ?? 0);
				$userManager = \OCP\Server::get(IUserManager::class);
				foreach ($userIds as $userId) {
					if (!isset($users[$userId])) {
						$users[$userId] = new LazyUser($userId, $userManager);
					}
				}
			}
			if (!is_null($limit) and $limit <= 0) {
				return $users;
			}
		}
		return $users;
	}

	/**
	 * returns the number of users matching the search string
	 *
	 * @param string $search
	 * @return int|bool
	 */
	public function count($search = ''): int|bool {
		$users = false;
		foreach ($this->backends as $backend) {
			if ($backend->implementsActions(\OC\Group\Backend::COUNT_USERS)) {
				if ($users === false) {
					//we could directly add to a bool variable, but this would
					//be ugly
					$users = 0;
				}
				$users += $backend->countUsersInGroup($this->gid, $search);
			}
		}
		return $users;
	}

	/**
	 * returns the number of disabled users
	 *
	 * @return int|bool
	 */
	public function countDisabled(): int|bool {
		$users = false;
		foreach ($this->backends as $backend) {
			if ($backend instanceof ICountDisabledInGroup) {
				if ($users === false) {
					//we could directly add to a bool variable, but this would
					//be ugly
					$users = 0;
				}
				$users += $backend->countDisabledInGroup($this->gid);
			}
		}
		return $users;
	}

	/**
	 * search for users in the group by displayname
	 *
	 * @param string $search
	 * @param int $limit
	 * @param int $offset
	 * @return IUser[]
	 * @deprecated 27.0.0 Use searchUsers instead (same implementation)
	 */
	public function searchDisplayName(string $search, ?int $limit = null, ?int $offset = null): array {
		return $this->searchUsers($search, $limit, $offset);
	}

	/**
	 * Get the names of the backend classes the group is connected to
	 *
	 * @return string[]
	 */
	public function getBackendNames(): array {
		$backends = [];
		foreach ($this->backends as $backend) {
			if ($backend instanceof INamedBackend) {
				$backends[] = $backend->getBackendName();
			} else {
				$backends[] = get_class($backend);
			}
		}

		return $backends;
	}

	/**
	 * Delete the group
	 *
	 * @return bool
	 */
	public function delete(): bool {
		// Prevent users from deleting group admin
		if ($this->getGID() === 'admin') {
			return false;
		}

		$result = false;
		$this->dispatcher->dispatchTyped(new BeforeGroupDeletedEvent($this));
		if ($this->emitter) {
			$this->emitter->emit('\OC\Group', 'preDelete', [$this]);
		}
		foreach ($this->backends as $backend) {
			if ($backend->implementsActions(\OC\Group\Backend::DELETE_GROUP)) {
				$result = $result || $backend->deleteGroup($this->gid);
			}
		}
		if ($result) {
			$this->dispatcher->dispatchTyped(new GroupDeletedEvent($this));
			if ($this->emitter) {
				$this->emitter->emit('\OC\Group', 'postDelete', [$this]);
			}
		}
		return $result;
	}

	/**
	 * returns all the Users from an array that really exists
	 * @param string[] $userIds an array containing user IDs
	 * @return \OC\User\User[] an Array with the userId as Key and \OC\User\User as value
	 */
	private function getVerifiedUsers(array $userIds): array {
		$users = [];
		foreach ($userIds as $userId) {
			$user = $this->userManager->get($userId);
			if (!is_null($user)) {
				$users[$userId] = $user;
			}
		}
		return $users;
	}

	/**
	 * @return bool
	 * @since 14.0.0
	 */
	public function canRemoveUser(): bool {
		foreach ($this->backends as $backend) {
			if ($backend->implementsActions(GroupInterface::REMOVE_FROM_GOUP)) {
				return true;
			}
		}
		return false;
	}

	/**
	 * @return bool
	 * @since 14.0.0
	 */
	public function canAddUser(): bool {
		foreach ($this->backends as $backend) {
			if ($backend->implementsActions(GroupInterface::ADD_TO_GROUP)) {
				return true;
			}
		}
		return false;
	}

	/**
	 * @return bool
	 * @since 16.0.0
	 */
	public function hideFromCollaboration(): bool {
		return array_reduce($this->backends, function (bool $hide, GroupInterface $backend) {
			return $hide || ($backend instanceof IHideFromCollaborationBackend && $backend->hideGroup($this->gid));
		}, false);
	}
}
