<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Group;

use OC\Hooks\PublicEmitter;
use OC\Settings\AuthorizedGroupMapper;
use OC\SubAdmin;
use OCA\Settings\Settings\Admin\Users;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Group\Backend\IBatchMethodsBackend;
use OCP\Group\Backend\ICreateNamedGroupBackend;
use OCP\Group\Backend\IGroupDetailsBackend;
use OCP\Group\Events\BeforeGroupCreatedEvent;
use OCP\Group\Events\GroupCreatedEvent;
use OCP\GroupInterface;
use OCP\ICacheFactory;
use OCP\IDBConnection;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\Security\Ip\IRemoteAddress;
use OCP\Server;
use Psr\Log\LoggerInterface;
use function is_string;

/**
 * Class Manager
 *
 * Hooks available in scope \OC\Group:
 * - preAddUser(\OC\Group\Group $group, \OC\User\User $user)
 * - postAddUser(\OC\Group\Group $group, \OC\User\User $user)
 * - preRemoveUser(\OC\Group\Group $group, \OC\User\User $user)
 * - postRemoveUser(\OC\Group\Group $group, \OC\User\User $user)
 * - preDelete(\OC\Group\Group $group)
 * - postDelete(\OC\Group\Group $group)
 * - preCreate(string $groupId)
 * - postCreate(\OC\Group\Group $group)
 *
 * @package OC\Group
 */
class Manager extends PublicEmitter implements IGroupManager {
	/** @var GroupInterface[] */
	private $backends = [];

	/** @var array<string, IGroup> */
	private $cachedGroups = [];

	/** @var array<string, list<string>> */
	private $cachedUserGroups = [];

	/** @var SubAdmin */
	private $subAdmin = null;

	private DisplayNameCache $displayNameCache;

	private const MAX_GROUP_LENGTH = 255;

	public function __construct(
		private \OC\User\Manager $userManager,
		private IEventDispatcher $dispatcher,
		private LoggerInterface $logger,
		ICacheFactory $cacheFactory,
		private IRemoteAddress $remoteAddress,
	) {
		$this->displayNameCache = new DisplayNameCache($cacheFactory, $this);

		$this->listen('\OC\Group', 'postDelete', function (IGroup $group): void {
			unset($this->cachedGroups[$group->getGID()]);
			$this->cachedUserGroups = [];
		});
		$this->listen('\OC\Group', 'postAddUser', function (IGroup $group): void {
			$this->cachedUserGroups = [];
		});
		$this->listen('\OC\Group', 'postRemoveUser', function (IGroup $group): void {
			$this->cachedUserGroups = [];
		});
	}

	/**
	 * Checks whether a given backend is used
	 *
	 * @param string $backendClass Full classname including complete namespace
	 * @return bool
	 */
	public function isBackendUsed($backendClass) {
		$backendClass = strtolower(ltrim($backendClass, '\\'));

		foreach ($this->backends as $backend) {
			if (strtolower(get_class($backend)) === $backendClass) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param GroupInterface $backend
	 */
	public function addBackend($backend) {
		$this->backends[] = $backend;
		$this->clearCaches();
	}

	public function clearBackends() {
		$this->backends = [];
		$this->clearCaches();
	}

	/**
	 * Get the active backends
	 *
	 * @return GroupInterface[]
	 */
	public function getBackends() {
		return $this->backends;
	}


	protected function clearCaches() {
		$this->cachedGroups = [];
		$this->cachedUserGroups = [];
	}

	/**
	 * @param string $gid
	 * @return IGroup|null
	 */
	public function get($gid) {
		if (isset($this->cachedGroups[$gid])) {
			return $this->cachedGroups[$gid];
		}
		return $this->getGroupObject($gid);
	}

	/**
	 * @param string $gid
	 * @param string $displayName
	 * @return IGroup|null
	 */
	protected function getGroupObject($gid, $displayName = null) {
		$backends = [];
		foreach ($this->backends as $backend) {
			if ($backend->implementsActions(Backend::GROUP_DETAILS)) {
				$groupData = $backend->getGroupDetails($gid);
				if (is_array($groupData) && !empty($groupData)) {
					// take the display name from the last backend that has a non-null one
					if (is_null($displayName) && isset($groupData['displayName'])) {
						$displayName = $groupData['displayName'];
					}
					$backends[] = $backend;
				}
			} elseif ($backend->groupExists($gid)) {
				$backends[] = $backend;
			}
		}
		if (count($backends) === 0) {
			return null;
		}
		/** @var GroupInterface[] $backends */
		$this->cachedGroups[$gid] = new Group($gid, $backends, $this->dispatcher, $this->userManager, $this, $displayName);
		return $this->cachedGroups[$gid];
	}

	/**
	 * @brief Batch method to create group objects
	 *
	 * @param list<string> $gids List of groupIds for which we want to create a IGroup object
	 * @param array<string, string> $displayNames Array containing already know display name for a groupId
	 * @return array<string, IGroup>
	 */
	protected function getGroupsObjects(array $gids, array $displayNames = []): array {
		$backends = [];
		$groups = [];
		foreach ($gids as $gid) {
			$backends[$gid] = [];
			if (!isset($displayNames[$gid])) {
				$displayNames[$gid] = null;
			}
		}
		foreach ($this->backends as $backend) {
			if ($backend instanceof IGroupDetailsBackend || $backend->implementsActions(GroupInterface::GROUP_DETAILS)) {
				/** @var GroupInterface&IGroupDetailsBackend $backend */
				if ($backend instanceof IBatchMethodsBackend) {
					$groupDatas = $backend->getGroupsDetails($gids);
				} else {
					$groupDatas = [];
					foreach ($gids as $gid) {
						$groupDatas[$gid] = $backend->getGroupDetails($gid);
					}
				}
				foreach ($groupDatas as $gid => $groupData) {
					if (!empty($groupData)) {
						// take the display name from the last backend that has a non-null one
						if (isset($groupData['displayName'])) {
							$displayNames[$gid] = $groupData['displayName'];
						}
						$backends[$gid][] = $backend;
					}
				}
			} else {
				if ($backend instanceof IBatchMethodsBackend) {
					$existingGroups = $backend->groupsExists($gids);
				} else {
					$existingGroups = array_filter($gids, fn (string $gid): bool => $backend->groupExists($gid));
				}
				foreach ($existingGroups as $group) {
					$backends[$group][] = $backend;
				}
			}
		}
		foreach ($gids as $gid) {
			if (count($backends[$gid]) === 0) {
				continue;
			}
			$this->cachedGroups[$gid] = new Group($gid, $backends[$gid], $this->dispatcher, $this->userManager, $this, $displayNames[$gid]);
			$groups[$gid] = $this->cachedGroups[$gid];
		}
		return $groups;
	}

	/**
	 * @param string $gid
	 * @return bool
	 */
	public function groupExists($gid) {
		return $this->get($gid) instanceof IGroup;
	}

	/**
	 * @param string $gid
	 * @return IGroup|null
	 */
	public function createGroup($gid) {
		if ($gid === '' || $gid === null) {
			return null;
		} elseif ($group = $this->get($gid)) {
			return $group;
		} elseif (mb_strlen($gid) > self::MAX_GROUP_LENGTH) {
			throw new \Exception('Group name is limited to ' . self::MAX_GROUP_LENGTH . ' characters');
		} else {
			$this->dispatcher->dispatchTyped(new BeforeGroupCreatedEvent($gid));
			$this->emit('\OC\Group', 'preCreate', [$gid]);
			foreach ($this->backends as $backend) {
				if ($backend->implementsActions(Backend::CREATE_GROUP)) {
					if ($backend instanceof ICreateNamedGroupBackend) {
						$groupName = $gid;
						if (($gid = $backend->createGroup($groupName)) !== null) {
							$group = $this->getGroupObject($gid);
							$this->dispatcher->dispatchTyped(new GroupCreatedEvent($group));
							$this->emit('\OC\Group', 'postCreate', [$group]);
							return $group;
						}
					} elseif ($backend->createGroup($gid)) {
						$group = $this->getGroupObject($gid);
						$this->dispatcher->dispatchTyped(new GroupCreatedEvent($group));
						$this->emit('\OC\Group', 'postCreate', [$group]);
						return $group;
					}
				}
			}
			return null;
		}
	}

	public function search(string $search, ?int $limit = null, ?int $offset = 0) {
		$groups = [];
		foreach ($this->backends as $backend) {
			$groupIds = $backend->getGroups($search, $limit ?? -1, $offset ?? 0);
			$newGroups = $this->getGroupsObjects($groupIds);
			foreach ($newGroups as $groupId => $group) {
				$groups[$groupId] = $group;
			}
			if (!is_null($limit) && $limit <= 0) {
				return array_values($groups);
			}
		}
		return array_values($groups);
	}

	/**
	 * @param IUser|null $user
	 * @return array<string, IGroup>
	 */
	public function getUserGroups(?IUser $user = null): array {
		if (!$user instanceof IUser) {
			return [];
		}
		return $this->getUserIdGroups($user->getUID());
	}

	/**
	 * @param string $uid the user id
	 * @return array<string, IGroup>
	 */
	public function getUserIdGroups(string $uid): array {
		$groups = [];

		foreach ($this->getUserIdGroupIds($uid) as $groupId) {
			$aGroup = $this->get($groupId);
			if ($aGroup instanceof IGroup) {
				$groups[$groupId] = $aGroup;
			} else {
				$this->logger->debug('User "' . $uid . '" belongs to deleted group: "' . $groupId . '"', ['app' => 'core']);
			}
		}

		return $groups;
	}

	/**
	 * Checks if a userId is in the admin group
	 *
	 * @param string $userId
	 * @return bool if admin
	 */
	public function isAdmin($userId) {
		if (!$this->remoteAddress->allowsAdminActions()) {
			return false;
		}

		foreach ($this->backends as $backend) {
			if (is_string($userId) && $backend->implementsActions(Backend::IS_ADMIN) && $backend->isAdmin($userId)) {
				return true;
			}
		}
		return $this->isInGroup($userId, 'admin');
	}

	public function isDelegatedAdmin(string $userId): bool {
		if (!$this->remoteAddress->allowsAdminActions()) {
			return false;
		}

		// Check if the user as admin delegation for users listing
		$authorizedGroupMapper = Server::get(AuthorizedGroupMapper::class);
		$user = $this->userManager->get($userId);
		$authorizedClasses = $authorizedGroupMapper->findAllClassesForUser($user);
		return in_array(Users::class, $authorizedClasses, true);
	}

	/**
	 * Checks if a userId is in a group
	 *
	 * @param string $userId
	 * @param string $group
	 * @return bool if in group
	 */
	public function isInGroup($userId, $group) {
		return in_array($group, $this->getUserIdGroupIds($userId));
	}

	public function getUserGroupIds(IUser $user): array {
		return $this->getUserIdGroupIds($user->getUID());
	}

	/**
	 * @param string $uid the user id
	 * @return list<string>
	 */
	private function getUserIdGroupIds(string $uid): array {
		if (!isset($this->cachedUserGroups[$uid])) {
			$groups = [];
			foreach ($this->backends as $backend) {
				if ($groupIds = $backend->getUserGroups($uid)) {
					$groups = array_merge($groups, $groupIds);
				}
			}
			$this->cachedUserGroups[$uid] = $groups;
		}

		return $this->cachedUserGroups[$uid];
	}

	/**
	 * @param string $groupId
	 * @return ?string
	 */
	public function getDisplayName(string $groupId): ?string {
		return $this->displayNameCache->getDisplayName($groupId);
	}

	/**
	 * get an array of groupid and displayName for a user
	 *
	 * @param IUser $user
	 * @return array ['displayName' => displayname]
	 */
	public function getUserGroupNames(IUser $user) {
		return array_map(function ($group) {
			return ['displayName' => $this->displayNameCache->getDisplayName($group->getGID())];
		}, $this->getUserGroups($user));
	}

	/**
	 * get a list of all display names in a group
	 *
	 * @param string $gid
	 * @param string $search
	 * @param int $limit
	 * @param int $offset
	 * @return array an array of display names (value) and user ids (key)
	 */
	public function displayNamesInGroup($gid, $search = '', $limit = -1, $offset = 0) {
		$group = $this->get($gid);
		if (is_null($group)) {
			return [];
		}

		$search = trim($search);
		$groupUsers = [];

		if (!empty($search)) {
			// only user backends have the capability to do a complex search for users
			$searchOffset = 0;
			$searchLimit = $limit * 100;
			if ($limit === -1) {
				$searchLimit = 500;
			}

			do {
				$filteredUsers = $this->userManager->searchDisplayName($search, $searchLimit, $searchOffset);
				foreach ($filteredUsers as $filteredUser) {
					if ($group->inGroup($filteredUser)) {
						$groupUsers[] = $filteredUser;
					}
				}
				$searchOffset += $searchLimit;
			} while (count($groupUsers) < $searchLimit + $offset && count($filteredUsers) >= $searchLimit);

			if ($limit === -1) {
				$groupUsers = array_slice($groupUsers, $offset);
			} else {
				$groupUsers = array_slice($groupUsers, $offset, $limit);
			}
		} else {
			$groupUsers = $group->searchUsers('', $limit, $offset);
		}

		$matchingUsers = [];
		foreach ($groupUsers as $groupUser) {
			$matchingUsers[(string)$groupUser->getUID()] = $groupUser->getDisplayName();
		}
		return $matchingUsers;
	}

	/**
	 * @return SubAdmin
	 */
	public function getSubAdmin() {
		if (!$this->subAdmin) {
			$this->subAdmin = new SubAdmin(
				$this->userManager,
				$this,
				Server::get(IDBConnection::class),
				$this->dispatcher
			);
		}

		return $this->subAdmin;
	}
}
