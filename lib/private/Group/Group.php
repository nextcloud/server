<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Johannes Leuker <j.leuker@hosting.de>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Vincent Petry <vincent@nextcloud.com>
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

use OC\Hooks\PublicEmitter;
use OCP\Group\Backend\IAddToGroupBackend;
use OCP\Group\Backend\ICountDisabledInGroup;
use OCP\Group\Backend\ICountUsersBackend;
use OCP\Group\Backend\IDeleteGroupBackend;
use OCP\Group\Backend\IGetDisplayNameBackend;
use OCP\Group\Backend\IHideFromCollaborationBackend;
use OCP\Group\Backend\INamedBackend;
use OCP\Group\Backend\IRemoveFromGroupBackend;
use OCP\Group\Backend\ISetDisplayNameBackend;
use OCP\GroupInterface;
use OCP\IGroup;
use OCP\IUser;
use OCP\IUserManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

class Group implements IGroup {
	protected ?string $displayName;
	private string $gid;

	/** @var IUser[] */
	private array $users = [];
	private bool $usersLoaded = false;

	/** @var GroupInterface[] */
	private array $backends;
	private EventDispatcherInterface $dispatcher;
	/** @var \OC\User\Manager|IUserManager  */
	private $userManager;
	private ?PublicEmitter $emitter;


	/**
	 * @param string $gid
	 * @param list<GroupInterface> $backends
	 * @param EventDispatcherInterface $dispatcher
	 * @param IUserManager $userManager
	 * @param PublicEmitter $emitter
	 * @param string $displayName
	 */
	public function __construct(string $gid, array $backends, EventDispatcherInterface $dispatcher, IUserManager $userManager, PublicEmitter $emitter = null, ?string $displayName = null) {
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
			foreach ($this->backends as $backend) {
				if (($backend instanceof ISetDisplayNameBackend)
					&& $backend->setDisplayName($this->gid, $displayName)) {
					$this->displayName = $displayName;
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Get all users in the group
	 *
	 * @return IUser[]
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
	 * Add a user to the group
	 */
	public function addUser(IUser $user): void {
		if ($this->inGroup($user)) {
			return;
		}

		$this->dispatcher->dispatch(IGroup::class . '::preAddUser', new GenericEvent($this, [
			'user' => $user,
		]));

		if ($this->emitter) {
			$this->emitter->emit('\OC\Group', 'preAddUser', [$this, $user]);
		}
		foreach ($this->backends as $backend) {
			if ($backend instanceof IAddToGroupBackend || $backend->implementsActions(GroupInterface::ADD_TO_GROUP)) {
				/** @var IAddToGroupBackend $backend */
				$backend->addToGroup($user->getUID(), $this->gid);
				if ($this->users) {
					$this->users[$user->getUID()] = $user;
				}

				$this->dispatcher->dispatch(IGroup::class . '::postAddUser', new GenericEvent($this, [
					'user' => $user,
				]));

				if ($this->emitter) {
					$this->emitter->emit('\OC\Group', 'postAddUser', [$this, $user]);
				}
				return;
			}
		}
	}

	/**
	 * Remove a user from the group
	 */
	public function removeUser(IUser $user): void {
		$result = false;
		$this->dispatcher->dispatch(IGroup::class . '::preRemoveUser', new GenericEvent($this, [
			'user' => $user,
		]));
		if ($this->emitter) {
			$this->emitter->emit('\OC\Group', 'preRemoveUser', [$this, $user]);
		}
		foreach ($this->backends as $backend) {
			if (($backend instanceof IRemoveFromGroupBackend || $backend->implementsActions(GroupInterface::REMOVE_FROM_GOUP))
				&& $backend->inGroup($user->getUID(), $this->gid)) {
				/** @var IRemoveFromGroupBackend $backend */
				$backend->removeFromGroup($user->getUID(), $this->gid);
				$result = true;
			}
		}
		if ($result) {
			$this->dispatcher->dispatch(IGroup::class . '::postRemoveUser', new GenericEvent($this, [
				'user' => $user,
			]));
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
			$users = array_merge($users, $backend->searchInGroup($this->gid, $search, $limit ? $limit : -1, $offset ? $offset : 0));
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
	 * @return int|false
	 */
	public function count(string $search = '') {
		$users = false;
		foreach ($this->backends as $backend) {
			if ($backend instanceof ICountUsersBackend || $backend->implementsActions(GroupInterface::COUNT_USERS)) {
				/** @var ICountUsersBackend $backend */
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
	 * @return int|false
	 */
	public function countDisabled() {
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
	 * Search for users in the group by display name
	 *
	 * @return \OCP\IUser[]
	 * @depreacted 25.0.0 Use searchUsers instead (same implementation)
	 */
	public function searchDisplayName(string $search, ?int $limit = null, ?int $offset = null): array {
		$users = [];
		foreach ($this->backends as $backend) {
			$users = $backend->searchInGroup($this->gid, $search, $limit, $offset);
			if (!is_null($limit) and $limit <= 0) {
				return array_values($users);
			}
		}
		return array_values($users);
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
	 * delete the group
	 */
	public function delete(): bool {
		// Prevent users from deleting group admin
		if ($this->getGID() === 'admin') {
			return false;
		}

		$result = false;
		$this->dispatcher->dispatch(IGroup::class . '::preDelete', new GenericEvent($this));
		if ($this->emitter) {
			$this->emitter->emit('\OC\Group', 'preDelete', [$this]);
		}
		foreach ($this->backends as $backend) {
			if ($backend instanceof IDeleteGroupBackend || $backend->implementsActions(GroupInterface::DELETE_GROUP)) {
				/** @var IDeleteGroupBackend $backend */
				$result = $result || $backend->deleteGroup($this->gid);
			}
		}
		if ($result) {
			$this->dispatcher->dispatch(IGroup::class . '::postDelete', new GenericEvent($this));
			if ($this->emitter) {
				$this->emitter->emit('\OC\Group', 'postDelete', [$this]);
			}
		}
		return $result;
	}

	/**
	 * Get all the Users from an array that really exists
	 * @param string[] $userIds an array containing user IDs
	 * @return \OCP\IUser[] an Array with the userId as Key and \OC\User\User as value
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

	public function canRemoveUser(): bool {
		foreach ($this->backends as $backend) {
			if ($backend->implementsActions(GroupInterface::REMOVE_FROM_GOUP)) {
				return true;
			}
		}
		return false;
	}

	public function canAddUser(): bool {
		foreach ($this->backends as $backend) {
			if ($backend->implementsActions(GroupInterface::ADD_TO_GROUP)) {
				return true;
			}
		}
		return false;
	}

	public function hideFromCollaboration(): bool {
		return array_reduce($this->backends, function (bool $hide, GroupInterface $backend): bool {
			return $hide || ($backend instanceof IHideFromCollaborationBackend && $backend->hideGroup($this->gid));
		}, false);
	}
}
